<?php
namespace App\Repositories\Stocks\Hendy;
use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Stock;
use App\Repositories\Stocks\AbstractRepository;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\ColourService;
use App\Services\Stocks\TaxCalculationService;
use App\Services\StockService;
use Carbon\Carbon;
class NewCar extends AbstractRepository
{
    private static $cderList = [];  //that cder already searched to cap database

    protected $addRegistrationFeeToPrice = true;

    protected $addRoadTax = true;

    public function isNew()
    {
        //hendy only sell new car
        return true; //TODO
    }
    public function  findCderAndFormatStockArr()
    {
        //cap_vin means cder_id of cap database
        $capder = static::$cderList[trim($this->excelRow->cap_vin)] ?? null;
        if(trim($this->excelRow->cap_vin) && !$capder)
        {
            $capder   =  CAPDer::where('cder_id', trim($this->excelRow->cap_vin))->with([
                'range',
                'make',
                'NVDTechnical',
                'NVDPrices' => function($query){
                    $query->orderBy('PR_Id', 'DESC');
                }
                , 'standardOptions'
            ])->first();
            static::$cderList[trim($this->excelRow->cap_vin)] = $capder;
        }
        if( !$capder )
            return null; //cder not found


        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();

        $purchasePrice = filter_var($this->excelRow->overall_sale_net,  FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION);
        $retailPrice   = filter_var($this->excelRow->retail_sales_value,  FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION);

        try{
            $price    = $priceService->getNewCarPrice($purchasePrice, $retailPrice);
        }
        catch (\Exception $e)
        {
            LogHelper::save('ERROR', new \Exception($e->getMessage() ."Excel data:".json_encode($this->excelRow, JSON_PRETTY_PRINT)));
            return null;
        }

        if(!$this->isValidPrice($price)) {
            LogHelper::save('ERROR', new \Exception("Price problem :" . json_encode([$this->excelRow, $price], JSON_PRETTY_PRINT)));
            return null;
        }
        if(!$this->checkCapData($capder,$this->excelRow)){
            LogHelper::save('ERROR', new \Exception("Make,model not matched :" . json_encode([$this->excelRow], JSON_PRETTY_PRINT)));
            return null;
        }

        $stock                  = new \stdClass();
        $stock->stock_ref       = trim($this->excelRow->stockno);
        $stock->chassis_no      = null;
        $stock->vin_number      = trim($this->excelRow->vehicle_identification_number);
        $stock->car_type        = $this->isNew() ? 'new' : 'used';
        $stock->make            = $this->formatMake($capder->make->cman_name);
        $stock->model_id        = $capder->range->cran_code;
        $stock->model           = trim($capder->range->cran_name);
        $stock->derivative_id   = $capder->cder_ID;
        $stock->cap_code        = trim($capder->cder_capcode);
        $stock->derivative      = trim($capder->cder_name);
        $stock->supplier_spec   = trim($this->excelRow->vehicle_description);
        $stock->doors           = $capder->cder_doors;
        $stock->body_type       = $capder->isConvertible() ? 'convertible' : CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description));
        $stock->fuel_type       = CAPDer::getFuelType(trim($capder->cder_fueltype));
        $stock->transmission    = CAPDer::getTransmission(trim($capder->cder_transmission));
        $stock->colour_spec     = strtolower(trim($this->excelRow->colour));

        $colourService          = app(ColourService::class);
        $stock->colour          = $colourService->parse(strtolower(trim($this->excelRow->colour)));

        $stock->standard_option = $capder->standardOptionsString;
        $stock->additional_option = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->excelRow->factory_fitted_options);
        $stock->additional_option = trim($stock->additional_option, ', ');
        $stock->additional_option_price = null;
        $stock->registration_no = null;
        $stock->registration_date = null;
        $stock->mpg             = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp             = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2             = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage = 0;
        $stock->grade           = null;

        $stock->sale_location   = $this->mapSaleLocation( 'hendy');
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $stock->purchase_price  = $price['purchase_price'] ?? null;


        $tax = $taxService->getTaxAmount( $stock->co2, $stock->fuel_type);
        $stock->tax_amount_six_month   = $tax['6_months_tax'] ?? null;
        $stock->tax_amount_twelve_month   = $tax['12_months_tax'] ?? null;

        if(isset($this->addRoadTax)){
            $payableTax = $stock->tax_amount_six_month ?? $stock->tax_amount_twelve_month;
            $stock->added_road_tax_amount  = $payableTax;
            $payableTax = $payableTax ?? 0;
            $price['purchase_price']  += $payableTax;
        }

        if($this->addRegistrationFeeToPrice){
            $stock->customer_price  = $price['purchase_price'] + config('constants.pricing.first_registration_fee');
            $stock->added_first_registration_amount  = config('constants.pricing.first_registration_fee');
        }

        $stock->customer_discount_percentage  = $price['customer_discount_percentage'] ?? null;
        $stock->customer_discount_amount  = $price['customer_discount_amount'] ?? null;
        $stock->purchase_discount_percentage  =  $price['purchase_discount_percentage'] ?? null;
        $stock->purchase_discount_amount =  $price['purchase_discount_amount'] ?? null;
        $stock->current_price = $price['current_price'];


        return (array) $stock;
    }
    /**
     * Import stock form excel file
     */
    public static function importStocks($filePath, $importedTab)
    {
        $stockService = app(StockService::class);
        $stockService->moveToStockHistoryByImportedTab($importedTab);
        /**Filtering*/
        $excelRows = $stockService->getStock($filePath);
        foreach ($excelRows as $excelRow)
        {
            LogHelper::save("INFO", new \Exception("Processing handi stock feed 2 excel row excel data:" . json_encode($excelRow,JSON_PRETTY_PRINT)));
//            /** Get cder id from other rows */
//            $capVin = $excelRow->cap_vin;
//            if(empty(trim($capVin))){
//                $another = $excelRows->where('vehicle_description', $excelRow->vehicle_description)
//                    ->where('cap_vin','!=','')
//                    ->first();
//                if($another)
//                    $excelRow->cap_vin = $another->cap_vin;
//            }
            $stock = (new static($excelRow))->findCderAndFormatStockArr();
            if(!$stock){
                LogHelper::save("INFO", new \Exception("Cder not found stock feed 2 excel row excel data:" .
                    json_encode($excelRow,JSON_PRETTY_PRINT)));
                continue;
            };
            $stock['supplier']    = config("constants.feeds.{$importedTab}.supplier");
            $stock['imported_tab']= $importedTab;
            $stock['created_at']  = (string) Carbon::now();
            $stock['updated_at']  = (string) Carbon::now();

            Stock::incrementOrInsert($stock);
        }
    }
    private function checkCapData($capder,$excelRow){
        $capMake    =   strtolower(trim($capder->make->cman_name));
        $capModel   =   strtolower(trim($capder->range->cran_name));
        $excelMake  =   strtolower(trim($excelRow->make));
        $excelModel =   strtolower(trim($excelRow->vehicle_description));
        $make       =   strcmp($capMake, $excelMake) == 0 ? true : false;
        $model      =   preg_match( "/$capModel\b/i", $excelModel, $array )  ? true : false;
        return $result = $make & $model;
    }
}
