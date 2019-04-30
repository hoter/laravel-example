<?php
namespace App\Repositories\Stocks\Hendy;
use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Stock;
use App\Repositories\StockRepo;
use App\Repositories\Stocks\AbstractRepository;
use App\Services\Stocks\ColourService;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\TaxCalculationService;
use App\Services\StockService;
use Carbon\Carbon;

class UsedCar extends AbstractRepository
{
    public function  findCderAndFormatStockArr()
    {


        if(!$this->isRowAccepted($this->excelRow)){
            return null;
        }
        $capder = null;
        $cap_id = trim($this->excelRow->cap_id);

        if($cap_id)
        {
            $capder   =  CAPDer::where('cder_id', $cap_id)->with([
                'range',
                'make',
                'NVDTechnical',
                'NVDPrices' => function($query){
                    $query->orderBy('PR_Id', 'DESC');
                }
            ])->first();
        }
        if( !$capder )
            return null; //cder not found


        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();
        $bodyType = CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description));

        $purchasePrice = filter_var($this->excelRow->price,  FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION);

        if($this->findWordInArray(array(trim($this->excelRow->variant),trim($this->excelRow->bodytype)),config('constants.stocks.ignore_body_type')))
        {
            LogHelper::save("INFO", new \Exception("Body type ignore.:" .
                json_encode($this->excelRow)));
            return null;
        }
        try{
            $price    = $priceService->getUsedCarNonBCAPrice($purchasePrice);
        }
        catch (\Exception $e)
        {
            LogHelper::save('ERROR', new \Exception($e->getMessage() ."Excel data:".json_encode($this->excelRow, JSON_PRETTY_PRINT)));
            return null;
        }

        if(!$this->isValidPrice($price))
        {
            LogHelper::save('ERROR', new \Exception("Price problem :". json_encode([$this->excelRow, $price], JSON_PRETTY_PRINT)));
            return null;
        }

        if(!$this->checkCapData($capder,$this->excelRow)){
            LogHelper::save('ERROR', new \Exception("Make,model not matched :" . json_encode([$this->excelRow], JSON_PRETTY_PRINT)));
            return null;
        }


        $stock                  = new \stdClass();
        $stock->stock_ref       = trim($this->excelRow->vehicle_id);
        $stock->chassis_no      = null;
        $stock->vin_number      = null;
        $stock->car_type        = 'used';
        $stock->make            = $this->formatMake($capder->make->cman_name);
        $stock->model_id        = $capder->range->cran_code;
        $stock->model           = trim($capder->range->cran_name);
        $stock->model_year      = trim($this->excelRow->year);
        $stock->derivative_id   = $capder->cder_ID;
        $stock->cap_code        = trim($capder->cder_capcode);
        $stock->derivative      = trim($capder->cder_name);
        $stock->supplier_spec   = trim($this->excelRow->variant);
        $stock->doors           = $capder->cder_doors;
        $stock->body_type       = $capder->isConvertible() ? 'convertible' : $bodyType;
        $stock->fuel_type       = CAPDer::getFuelType(trim($capder->cder_fueltype));
        $stock->transmission    = CAPDer::getTransmission(trim($capder->cder_transmission));
        $stock->colour_spec     = strtolower(trim($this->excelRow->colour));

        $colourService          = app(ColourService::class);
        $stock->colour          = $colourService->parse(strtolower(trim($this->excelRow->colour)));

        $stock->standard_option =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->excelRow->options);
        $stock->standard_option = trim($stock->standard_option, ', ');
        if(strlen(trim($this->excelRow->options)) <= 10) {
            $optionData = $this->getTechnicalDataAsString($capder->cder_ID);
        } else {
            $optionData = trim($this->excelRow->options, ', ');
        }

        $stock->standard_option = $optionData;

        $stock->standard_option  = str_replace('* No Admin Fees. Request A Video For This Car. HPI Checked. 90 Point Vehicle Check. Free MOTs For Life. P/X Welcome.', '', $stock->standard_option );
        $stock->standard_option  = str_replace('*No Admin Fees. Request A Video For This Car. HPI Checked. 90 Point Vehicle Check. Free MOTs For Life. P/X Welcome.', '', $stock->standard_option );
        $stock->standard_option  = str_replace('No Admin Fees. Request A Video For This Car. HPI Checked. 90 Point Vehicle Check. Free MOTs For Life. P/X Welcome.', '', $stock->standard_option );
        $stock->standard_option  = str_replace('* No Admin Fees. Request A Video For This Car. HPI Checked. 90 Point Vehicle Check. * ', '', $stock->standard_option );
        $stock->standard_option  = str_replace('*No Admin Fees. Request A Video For This Car. HPI Checked. 90 Point Vehicle Check. * ', '', $stock->standard_option );
        $stock->standard_option  = str_replace('No Admin Fees. Request A Video For This Car. HPI Checked. 90 Point Vehicle Check. * ', '', $stock->standard_option );

        $stock->standard_option = trim($stock->standard_option);

        $stock->additional_option = null;
        $stock->additional_option_price = null;
        $stock->registration_no = trim($this->excelRow->fullregistration);
        $stock->registration_date= $this->getRegistrationDate() ? $this->getRegistrationDate()->format(config('constants.stocks.date_format'))  : null;
        $stock->mpg             = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp             = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2             = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage = trim($this->excelRow->mileage);
        $stock->grade           = null;

        $stock->sale_location   = $this->mapSaleLocation('hendy_used_car', trim($this->excelRow->feed_id));
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $tax = $taxService->getTaxAmount( $stock->co2, $stock->fuel_type,  $this->getRegistrationDate(), true);
        $stock->tax_amount_six_month   = $tax['6_months_tax'] ?? null;
        $stock->tax_amount_twelve_month   = $tax['12_months_tax'] ?? null;
        $payableTax = $stock->tax_amount_six_month > 0 ?  $stock->tax_amount_six_month :  $stock->tax_amount_twelve_month;

        $stock->customer_discount_percentage  = $price['customer_discount_percentage'] ?? null;
        $stock->customer_discount_amount  = $price['customer_discount_amount'] ?? null;
        $stock->purchase_discount_percentage  =  $price['purchase_discount_percentage'] ?? null;
        $stock->purchase_discount_amount =  $price['purchase_discount_amount'] ?? null;
        $stock->current_price = $price['current_price'];
        $stock->purchase_price = $price['purchase_price'];

        if(isset($payableTax)){
            $stock->added_road_tax_amount  = $payableTax;
        }
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
            LogHelper::save("INFO", new \Exception("Processing Hendy used car stock feed to excel row excel data:" . json_encode($excelRow,JSON_PRETTY_PRINT)));

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

            $objStock = Stock::incrementOrInsert($stock);

            if($objStock instanceof Stock){
                $images = null;
                if(!empty($excelRow->picturerefs)) {
                    $imageData = $excelRow->picturerefs;
                    $images = explode(",", $imageData);
                }
                try {
                    StockRepo::addStockImages($objStock, $images);
                }catch (\Exception $e) {
                    LogHelper::save('ERROR', new \Exception($e->getMessage() .
                        " Could issue with storing/resizing images. stock id: ". $objStock->_id .
                        json_encode([$objStock->derivative_id], JSON_PRETTY_PRINT)));

                }
            }
        }
    }

    private function getRegistrationDate()
    {
       return $this->excelRow->regdate ?
           Carbon::createFromFormat('d/m/Y', trim($this->excelRow->regdate)) : null;
    }

    private function isRowAccepted($excelRow){

        if(!$this->isValidUsedCarMileage($excelRow->mileage)){
            LogHelper::save("INFO", new \Exception("Mileage is not valid.:" .
                json_encode($excelRow,JSON_PRETTY_PRINT)));
            return false;
        }
        elseif(!$this->isValidUsedCarAge($excelRow->year)){
            LogHelper::save("INFO", new \Exception("Car registration older than 5 years:" .
                json_encode($excelRow,JSON_PRETTY_PRINT)));
            return false;
        }
        return true;
    }

    private function findWordInArray($haystacks, $needle) {
        if(!is_array($needle)) $needle = array($needle);
        foreach($needle as $query) {
            foreach($haystacks as $haystack){
                if(preg_match("/\b$query\b/i", $haystack, $output_array)){
                    return true;
                }
            }

        }
        return false;
    }

    private function checkCapData($capder,$excelRow){
        $capMake    =   strtolower(trim($capder->make->cman_name));
        $capModel   =   strtolower(trim($capder->range->cran_name));
        $excelMake  =   strtolower(trim($excelRow->make));
        $excelModel =   strtolower(trim($excelRow->model));
        $make       =   strcmp($capMake, $excelMake) == 0 ? true : false;
        $model      =   strcmp($capModel, $excelModel) == 0 ? true : false;
        return $result = $make & $model;
    }
}
