<?php
namespace App\Repositories\Stocks\Driftbridge;

use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Cap\CAPMan;
use App\Models\Cap\CAPRange;
use App\Models\Stock;
use App\Repositories\Stocks\AbstractRepository;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\ColourService;
use App\Services\Stocks\TaxCalculationService;
use App\Services\StockService;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class Mazda extends AbstractRepository
{
    protected $cmanName     = 'Mazda';

    protected $notExistsNotLike = [
      'SE-L',
      'Leather'
    ];

    protected static $sheetAccepted = [
        'Croydon',
        'Stock'
    ];

    protected $ifExistsLike = [
        'Fastback',
        'Saloon',
        'Tourer'
    ];


    public function __construct($excelRow, $sheetName = '')
    {
        parent::__construct($excelRow, $sheetName);
        $this->cmanName = 'Mazda';
    }

    protected function getNumDoors()
    {
        preg_match("/(\d*)dr/i", $this->excelRow->model, $filterArr);
        return isset($filterArr[1]) ? $filterArr[1] : 0;
    }

    protected function getPs()
    {
        preg_match("/(\d*)ps/i", $this->excelRow->model, $filterArr);
        return isset($filterArr[1]) ? $filterArr[1] : 0;
    }

    protected function getEnginSize()
    {
        preg_match("/\d*\.\d*/i", $this->excelRow->model, $filterArr);
        return isset($filterArr[0]) ? $filterArr[0] : 0;
    }

    protected function getCranName()
    {
        $model = trim($this->excelRow->model);
        preg_match("/mazda(\d+)|mazda\d*\ ([a-z]+\-[0-9]*)/i", $model, $filterArr);

        if(isset($filterArr[2]))
            $cranName = $filterArr[2];
        else
            $cranName = $filterArr[1];

        return (string) $cranName;
    }

    protected function cderNameLikeWords()
    {
        $model = trim($this->excelRow->model);
        $filterArr = preg_replace("/\//i", "", $model);
        $filterArr = preg_replace("/\(\d*\)/i", "", $filterArr);

        $filterArr = preg_replace("/2WD/i", "", $filterArr);
        $filterArr = preg_replace("/AWD/i", "", $filterArr);

        $filterArr = preg_split("/\d+ps/i", $filterArr);
        $wordWrr   = array_values(array_filter(explode(' ', $filterArr[1])));

        return $wordWrr;
    }

    public function getCderId()
    {
        $cman_code = CAPMan::where('cman_name', '=', $this->cmanName)->first()->cman_code;

        $cran_code = CAPRange::where('cran_mantextcode', $cman_code)
            ->where('cran_name', $this->getCranName())->first()->cran_code;

        $cderListQuery = CAPDer::where('cder_rancode', $cran_code)
                        ->join('NVDTechnical', 'TECH_id', '=', 'cder_id')
                        ->leftJoin('CAPMod', 'cder_modcode', '=', 'cmod_code');

        if($this->getNumDoors())
        {
            $cderListQuery->where('cder_doors', $this->getNumDoors());
        }

        if($this->getEnginSize())
        {
            $cderListQuery->where('cder_name', 'like', $this->getEnginSize().'%');
        }

        if($this->getPs())
        {
            $cderListQuery->where('TECH_Value_Float', '=', $this->getPs())
                ->where('TECH_TechCode', '=', 145);
        }

        $cderListQuery->whereNull('cder_discontinued');
        $cderListQuery->where('cder_transmission', $this->getCderTransmission());

        foreach($this->notExistsNotLike as $word)
        {
            if(stripos($this->excelRow->model, $word) === false)
                $cderListQuery->where('cder_name', 'not like', "%{$word}%");
        }

        foreach($this->ifExistsLike as $word)
        {
            if(strpos($this->excelRow->model, $word) !== false)
                $cderListQuery->where('cmod_name', 'like', "%{$word}%");
        }

        if(strpos($this->excelRow->model, 'MX-5') !== false ) {
            $cderListQuery->where('cmod_name', 'like', "%RF%");
        }

        if(strpos($this->excelRow->model, 'Mazda3') !== false ) {
            if(strpos($this->excelRow->model, 'Fastback') === false) {

                $cderListQuery->where('cmod_name', 'not like', "%Fastback%");
            }
        }

        foreach($this->cderNameLikeWords() as $key => $word)
        {

            if ($key < 2)
            {
                $cderListQuery->where('cder_name', 'like', "%{$word}%");
                $cderList = $cderListQuery->get();
                if($cderList->count() == 1 ) {
                    break;
                    //return $cderList->first()->cder_ID;
                }
            }else {
                if($word == 'Leather') {
                    $cderListQuery->where('cder_name', 'like', "%{$word}%");
                    $cderList = $cderListQuery->get();
                }
            }

        }

        if($cderList->count() == 0)
        {
            LogHelper::save('WARNING', new \Exception(sprintf("Cder not found. \nJson encoded excel row: %s",
                json_encode($this->excelRow,JSON_PRETTY_PRINT))));
            return null;
        }

        if($cderList->count() > 1)
        {
            LogHelper::save('WARNING', new \Exception(sprintf("Multiple cder found. \nJson encoded excel row: %s",
                json_encode($this->excelRow,JSON_PRETTY_PRINT))));
            return null;
        }

        return $cderList->first()->cder_ID;
    }

    /**
     * Get Transmission
     *  -- Its manual if it does not say Auto or Automatic
     *  -- A for automatic
     *     M for manual
     *
     * @return A|M
     */
    protected function getCderTransmission()
    {
        $words = implode('|',  $this->autoTransmissionWords);
        $isAutomatic = preg_match( "/\b($words)\b/i", $this->excelRow->model);
        return $isAutomatic ? 'A' : 'M';
    }

    public function isNew()
    {
        //all volkswagan new car
        return true; //TODO
    }

    public function  findCderAndFormatStockArr()
    {
        $cderId = $this->getCderId();
        if(!$cderId ) return null;

        //return $cderId;

        $capder   =  CAPDer::with(['range','make','NVDTechnical','NVDPrices' , 'standardOptions'])->find($cderId);

        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();

        //FIXME implement real purchase price and retail price
        $purchasePrice = rand(5000,10000);
        $retailPrice   = $purchasePrice + ($purchasePrice * rand(5,10) / 100) ;

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


        $stock                  = new \stdClass();
        $stock->stock_ref       = null;
        $stock->chassis_no      = null;
        $stock->vin_number      = trim(preg_replace("/ /i", "", $this->excelRow->vin));
        $stock->car_type        = $this->isNew() ? 'new' : 'used';
        $stock->make            = $this->formatMake($capder->make->cman_name);
        $stock->model_id        = $capder->range->cran_code;
        $stock->model           = trim($capder->range->cran_name);
        $stock->derivative_id   = $capder->cder_ID;
        $stock->cap_code        = trim($capder->cder_capcode);
        $stock->derivative      = $capder->cder_name;
        $stock->supplier_spec   = trim($this->excelRow->model);
        $stock->body_type       = $capder->isConvertible() ? 'convertible' : strtolower($capder->capmod->bodyStyle->bs_description);
        $stock->doors           = $capder->cder_doors;
        $stock->transmission    = strtolower(CAPDer::getTransmission($capder->cder_transmission));
        $stock->fuel_type       = strtolower(CAPDer::getFuelType($capder->cder_fueltype));
        $stock->colour_spec          = strtolower(trim($this->excelRow->{"ext._colour"}));

        $colourService          = app(ColourService::class);
        $stock->colour          = $colourService->parse(strtolower(trim($this->excelRow->{"ext._colour"})));

        $stock->standard_option = $capder->standardOptionsString;
        $stock->additional_option = null;
        $stock->additional_option_price = null;
        $stock->registration_no = null;
        $stock->registration_date = null;
        $stock->mpg             = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp             = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2             = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage = 0;
        $stock->grade           = null;

        $stock->sale_location   = $this->mapSaleLocation( 'driftbridge_mazda', $this->excelRow->location2);
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $stock->purchase_price  = $price['purchase_price'] ?? null;
        $stock->customer_price  = $price['customer_price'] ?? null;
        $stock->customer_discount_percentage  = $price['customer_discount_percentage'] ?? null;
        $stock->customer_discount_amount  = $price['customer_discount_amount'] ?? null;
        $stock->purchase_discount_percentage  =  $price['purchase_discount_percentage'] ?? null;
        $stock->purchase_discount_amount =  $price['purchase_discount_amount'] ?? null;
        $stock->current_price = $price['current_price'] ?? null;

        $tax = $taxService->getTaxAmount( $stock->co2, $stock->fuel_type);
        $stock->tax_amount_six_month   = $tax['6_months_tax'] ?? null;
        $stock->tax_amount_twelve_month = $tax['12_months_tax'] ?? null;
        return (array) $stock;
    }

    public static function importStocks($filePath, $importedTab)
    {
        $stocks = [];
        
        $service = app(StockService::class);
        $service->moveToStockHistoryByImportedTab($importedTab);

        Excel::load($filePath, function($reader) use($importedTab,$stocks) {
            $reader->skipRows(9);
            $reader->noHeading();
            $reader->toObject();
            $reader->each(function($sheet)use($stocks,$importedTab) {
                if(in_array($sheet->getTitle(),self::$sheetAccepted)){
                    // Loop through all rows
                    $sheet->each(function($row) use($sheet,$importedTab,$stocks) {
                        if (!is_null($row[2]) && !is_null($row[3])){
                            $row=[
                                'id'=>$row[0],
                                'vin'=>$row[1],
                                'model'=>$row[2],
                                'ext._colour'=>$row[3],
                                'location2'=>$row[5]
                            ];
                            $row=(object) $row;
                            $stock = (new static($row, $sheet->getTitle()))->findCderAndFormatStockArr();

                            if($stock) {
                                $stock['supplier']          = config("constants.feeds.{$importedTab}.supplier");
                                $stock['imported_tab']      = $importedTab;
                                $stock['created_at']        = (string) Carbon::now();
                                $stock['updated_at']        = (string) Carbon::now();

                                Stock::incrementOrInsert($stock);
                            }
                            //not found
                        }
                    });
                }
            });
        });
    }
}

