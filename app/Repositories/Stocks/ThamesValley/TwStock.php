<?php

namespace App\Repositories\Stocks\ThamesValley;

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
use Mockery\Exception;

class TwStock extends AbstractRepository
{
    protected  $bestMatchableString;
    protected $cderNameLikeWords = [];
    protected  $hp;
    protected  $bhp;
    protected $numOfDoors   = null;

    protected $replaceAbleWordPattern = [
        'BHDI'              => 'BlueHDi',
        'BHDI100'           => 'BlueHDi',
        'PRFL'              =>'Performance Line',
        'P/TECH'            =>'PureTech',
        'CHIC'              =>'Chic',
        'PRESTIGE'          =>'Prestige',
        'PRE'               =>'Performance Line',
        'BLACK'             =>'Black',
        'P/82'              =>'PureTech',
        'P/T68'             =>'PureTech',
        'P/T82'             =>'PureTech',
        'P/T 82'            =>'PureTech',
        'TJET'              => 'T-JET',
        'T/JET'             =>'T-JET',
        'M/AIR'             => 'Multiair',
        'M.AIR'             => 'Multiair',
        'MAIR'              => 'Multiair',
        'M/A'               => 'Multiair',
        'M/JET'             => 'Multijet',
        'MJTE'              => 'Multijet',
        'MJET'              => 'Multijet',
        'M/J'               => 'Multijet',
        'ANNV'              => 'Anniversary',
        'ANNIV'             => 'Anniversary',
        'ETORQ'             => 'E-torQ',
        'O/LAND'            =>'Overland',
        'LTD'               =>'Limited',
        '16'                =>'1.6',
        '140'               =>'1.4',
        'MY'                =>'Multijet',
        'EALGE'             =>'Eagle',
        'QV'                =>'Quadrifoglio',
        'AWD'               =>'Active',
        'COMP'              =>'Competizione',
        'D/LOGIC'           =>'Dualogic',
        'POPSTAR'           =>'pop',
        'POSTAR'            =>'pop',
        'T/ED'              =>'Touch Edition',
        '10TH ANNIVERSARY'  =>'Anniversario',
        '1.2 69 CITY CROSS' =>'1.2 City',
        'CRS'               =>'Cross',
        'T/AIR'             =>'TwinAir',
        '210SUPER'          =>'210 SUPER'
    ];
    protected $replaceAbleModelPatern=[
        'DS3'           => 'DS 3',
        'DS4'           => 'DS 4',
        'DS3C'          =>'DS 3',
        'DS5'           =>'DS 5',
        'C1'            =>'C1',
        'NEW C3'        =>'C3',
        'C4PIC'         =>'C4 PICASSO',
        'C4 CACTUS'     =>'C4 CACTUS',
        'BERLINGO'      =>'BERLINGO',
        'C3'            =>'C3',
        'GRAND CHROKEE' =>'GRAND CHEROKEE',
        '595C'          =>'595',
        '500C'          =>'500',
        'TIPO S/W'      =>'TIPO'

    ];

    /** Not Exist Not Like Words */
    protected  $notExistsNotLikeWords = [
        'NAV',
        'Navigation',
        'EDITION'
    ];

    /** Considerable Words */
    protected  $considerableWords = [
        'NAV',
        'Navigation',
        'EDITION',
        '4X4',
        'S&S',
        '4WD',
        'AWD',
        '120',
        'MTA',
        'TURBO',
        'MANUAL',
        '16DFD',
        '1598',
        '+5DR',
        '140',
        '5DR',
        '150',
        '105',
        '82',
        'CITY'
    ];

    protected static $sheetAccepted = [
        'DS','CITROEN','JEEP STK','ALFA STK','ABARTH STOCK','FIAT STOCK'
    ];

    protected $conditionalConsiderableWord=[
        '1.2S'=>'1.2 S'
    ];
    protected $likeConsiderRow=[
        '1.4 MAIR POSTAR',
        '1.4 MAIR TURBO 140BHP',
        'P/T82 FEEL',
        'P/TECH 130 PLATINUM',
        '2.0 SUPER 150',
        '0.9 T/AIR 105'
    ];

    /**
     * TwStock constructor.
     * @param $fileName
     * @param $sheetName
     * @param $excelRow
     */
    public function __construct($excelRow, $sheetName )
    {
        parent::__construct($excelRow, $sheetName);
        /** HP */
        preg_match("/([0-9])+(HP)/", $this->excelRow->version, $hpArr);
        if(!empty($hpArr))
        {
            $this->hp = (int) filter_var($hpArr[0],FILTER_SANITIZE_NUMBER_INT);
        }

        /** BHP */

        preg_match("/([0-9])+(BHP)/", $this->excelRow->version, $bhpArr);
        if(!empty($bhpArr))
        {
            $this->bhp = (int) filter_var($bhpArr[0],FILTER_SANITIZE_NUMBER_INT);
        }

        /**
         * Replacing pattern that already considered
         */
        $tempString = $this->excelRow->version;

        /** @var  Replacing un-wanted words */
        foreach( $this->replaceAbleWordPattern as   $search => $replace)
        {
            $tempString = preg_replace('~\b'. $search.'\b~', $replace, $tempString);
        }
        /**  replacing hp */

        $from = '/'.preg_quote( $this->hp.'BHP', '/').'/i';
        $tempString=  preg_replace($from, ' ', $tempString, 1);

        $from = '/'.preg_quote( $this->hp.'HP', '/').'/i';
        $tempString=  preg_replace($from, ' ', $tempString, 1);

        /*Removing Empty Parentheses*/
        $tempString=  trim(preg_replace("/\( *\)/", "", $tempString));
        /** Removing space */
        $tempString = preg_replace('/\s+/', ' ', $tempString);
        $this->cderNameLikeWords = explode(' ', trim($tempString));

        /** Get number of doors */
        preg_match("/\d-?[DRdr]{2}|\d\ ?Door/i", $this->excelRow->model, $numOfDoorsArr);
        if(!empty($numOfDoorsArr))
        {
            $this->numOfDoors = (int) filter_var($numOfDoorsArr[0],FILTER_SANITIZE_NUMBER_INT);
        }

    }

    protected function getTechValue()
    {
        preg_match("#/(\d+)#", $this->excelRow->version, $techValueArr);
        return $techValueArr[1];

    }

    protected function getNumOfDoors()
    {

        if(is_null($this->numOfDoors)){
            $this->numOfDoors=substr( $this->excelRow->model, -1) == 'C' ?  2 : 'Undefined';
        }
        return $this->numOfDoors;
    }
    /**
     * Get cder id excel data
     *
     * @return string|null
     */
    public function getCderId()
    {
        if(!is_null($this->hp) || !is_null($this->bhp))
        {
            $cderListQuery =   CAPDer::select('cder_id', 'cder_name', 'cder_doors', 'cder_discontinued','cder_transmission','TECH_Value_Float')
           ->join('NVDTechnical', 'TECH_id', '=', 'cder_id');
        }
        else
        {
            $cderListQuery =   CAPDer::select('cder_id', 'cder_name', 'cder_doors', 'cder_discontinued','cder_transmission');
        }
        $cderListQuery->where('cder_rancode', $this->getCranCode());

        foreach( $this->cderNameLikeWords as $key => $word )
        {
            if(empty($word) || in_array($word,$this->considerableWords))//HP already considered
                continue;
            if(array_key_exists($word,$this->conditionalConsiderableWord))
            {
                $word=$this->conditionalConsiderableWord[$word];
            }
            $cderListQuery->where('cder_name', 'like', "%{$word}%" );
        }

        if(!in_array(trim($this->excelRow->version),$this->likeConsiderRow)){
            foreach( array_map('strtolower', $this->notExistsNotLikeWords) as $singleWord )
            {
                if(in_array($singleWord,array_map('strtolower', $this->cderNameLikeWords)))
                {
                    $cderListQuery->where('cder_name', 'LIKE', "%{$singleWord}%" );
                }
                else{
                    $cderListQuery->where('cder_name', 'NOT LIKE', "%{$singleWord}%" );
                }
            }
        }
        else
        {
            if(trim($this->excelRow->version)=='1.4 MAIR TURBO 140BHP')
            {
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Plus%" );
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Lusso%" );
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Dualogic%" );
            }
            if(trim($this->excelRow->version)=='P/T82 FEEL'){
                $cderListQuery->where('cder_name', 'NOT LIKE', "%82%" );
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Edition%" );
            }
            if(trim($this->excelRow->version)=='P/TECH 130 PLATINUM'){
                $cderListQuery->where('cder_name', 'NOT LIKE', "%feel%" );
            }
            if(trim($this->excelRow->version)=='2.0 SUPER 150'){
                $cderListQuery->where('cder_name', 'NOT LIKE', "%TCT%" );
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Lusso%" );
            }

            if(trim($this->excelRow->version)=='0.9 T/AIR 105'){
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Speciale%" );
                $cderListQuery->where('cder_name', 'NOT LIKE', "%Super%" );
            }
        }



        if($this->isNew())
        {
            $cderListQuery->whereNull('cder_discontinued');
        }
        $cderList = $cderListQuery->groupBy('cder_id')
            ->groupBy('cder_name')
            ->groupBY('cder_doors')
            ->groupBy('cder_discontinued')
            ->groupBy('cder_introduced')
            ->groupBy('cder_transmission')
            ->when(!is_null($this->hp), function ($query) {
                return $query->groupBy('TECH_Value_Float');
            })
            ->when(!is_null($this->bhp), function ($query)  {
                return $query->groupBy('TECH_Value_Float');
            })
            ->orderBy('cder_introduced', 'DESC')
            ->limit(20)
            ->get();

        if($cderList->count() == 0)
        {
            LogHelper::save('WARNING', new Exception(sprintf("Cder not found. \nJson encoded excel row: %s",
                json_encode($this->excelRow,JSON_PRETTY_PRINT))));
            return null;
        }
        if($cderList->count() > 1)
        {

            if(!is_null($this->hp)){
                $filtered = $cderList->filter(function ($value, $key) {
                    return $value->TECH_Value_Float == $this->hp;
                });

                $cderList=$filtered->all() == null ? $cderList :collect($filtered->all());
            }
            elseif (!is_null($this->bhp)){
                $filtered = $cderList->filter(function ($value, $key) {
                    return $value->TECH_Value_Float == $this->bhp;
                });

                $cderList=$filtered->all() == null ? $cderList :collect($filtered->all());
            }

            $filtered = $cderList->filter(function ($value, $key) {
                return $value->cder_transmission == $this->getCderTransmission();
            });
            $cderList=$filtered->all() == null ? $cderList :collect($filtered->all());



            if($cderList->count() == 1)
                return $cderList->first()->cder_id;

            $filtered = $cderList->filter(function ($value, $key) {
                $door=$this->getNumOfDoors();
                if($door !='Undefined')
                {
                    return $value->cder_doors == $this->getNumOfDoors();
                }
                else
                {
                    return $value->cder_doors != 2;
                }
            });
            $cderList=$filtered->all() == null ? $cderList :collect($filtered->all());


        }
        if($cderList->count() == 1)
            return $cderList->first()->cder_id;

        LogHelper::save('WARNING', new Exception(sprintf("Multiple cder found. \nJson encoded excel row: %s,\nJson encoded Excel data:%s",
            json_encode($this->excelRow,JSON_PRETTY_PRINT),
            json_encode($cderList->toArray(), JSON_PRETTY_PRINT))));

        return $cderList->first()->cder_id;
    }

    private function isNew()
    {
        //TODO all car of Thames vally
        return true;
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
        $isAutomatic = preg_match( "/\b($words)\b/i", $this->excelRow->version);
        return $isAutomatic ? 'A' : 'M';
    }

    /**
     *  Get cman code form database
     *
     *
     * @return int
     */
    protected function getCmanCode()
    {
        switch ($this->sheetName)
        {
            case 'DS':
                $cmanName =  'DS';
                break;
            case 'CITROEN':
                $cmanName =  'CITROEN';
                break;
            case 'JEEP STK':
                $cmanName =  'JEEP';
                break;
            case 'ALFA STK':
                $cmanName =  'ALFA ROMEO';
                break;
            case 'ABARTH STOCK':
                $cmanName =  'ABARTH';
                break;
            case 'FIAT STOCK':
                $cmanName =  'FIAT';
                break;
        }

        return CAPMan::where('cman_name', $cmanName)->first()->cman_code;
    }


    protected function getCranName()
    {
        $model = trim($this->excelRow->model);
        return $this->replaceAbleModelPatern[$model] ?? $model;
    }

    /**
     * Get cran code form database
     *
     * @return int|null
     */
    protected function getCranCode()
    {
        $capRangeList =  CAPRange::where('cran_mantextcode', $this->getCmanCode())
            ->where('cran_name', '=', $this->getCranName())
            ->get();
        if( $capRangeList->count() == 1)
            return $capRangeList->first()->cran_code;

        LogHelper::save('ERROR',new \Exception("Multiple or No cran code found. Excel row:" . json_encode($this->excelRow,
                JSON_PRETTY_PRINT)));

        return null;
    }

    public function  findCderAndFormatStockArr()
    {
        //TODO  import stooped until  thamesValley will give us purchase_price and retail price in excel sheet
        return null;

        $cderId  = $this->getCderId();

        if(!$cderId ) return null;

        $capder   =  CAPDer::with(['range', 'make', 'NVDTechnical','NVDPrices' => function($query){
            $query->orderBy('PR_Id', 'DESC');
        } , 'standardOptions'])->find($cderId);

        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();

        //FIXME thamesValley will give us purchase_price and retail price in excel sheet
        $purchasePrice = rand(5000,10000); //dummy implementation
        $retailPrice     = $purchasePrice + ($purchasePrice * rand(5,20) / 100); //dummy implementation

        try{
            $price    = $priceService->getNewCarPrice($purchasePrice, $retailPrice);
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

        $stock                              = new \stdClass();
        $stock->stock_ref                   = trim($this->excelRow->stk_no);
        $stock->chassis_no                  = $this->excelRow->chassis;
        $stock->car_type                    = $this->isNew() ? 'new' : 'used';
        $stock->make                        = $this->formatMake($capder->make->cman_name);
        $stock->model_id                    = $capder->range->cran_code;
        $stock->model                       = strtolower(trim($capder->range->cran_name));
        $stock->derivative_id               = trim($capder->cder_ID);
        $stock->cap_code                    = trim($capder->cder_capcode);
        $stock->derivative                  = trim($capder->cder_name);
        $stock->supplier_spec               = $this->excelRow->version;
        $stock->body_type                   = $capder->isConvertible() ? 'convertible' : CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description));
        $stock->fuel_type                   = CAPDer::getFuelType(trim($capder->cder_fueltype));
        $stock->transmission                = CAPDer::getTransmission(trim($capder->cder_transmission));
        $stock->colour_spec                 = strtolower($this->excelRow->colour);

        $colourService                      = app(ColourService::class);
        $stock->colour                      = $colourService->parse(strtolower($this->excelRow->colour));

        $stock->doors                       = $capder->cder_doors;
        $stock->standard_option             = $capder->standardOptionsString;
        $stock->additional_option           = trim($this->excelRow->options, ', ');
        $stock->additional_option_price     = null;
        $stock->registration_no             = null;
        $stock->registration_date           = null;
        $stock->mpg                         = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp                         = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2                         = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage             = 0;
        $stock->grade                       = null;

        $stock->sale_location   = $this->mapSaleLocation( 'thames_valley_tw_stock', $this->excelRow->location);
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $stock->purchase_price  = $price['purchase_price'] ?? null;
        $stock->customer_price  = $price['customer_price'] ?? null;
        $stock->customer_discount_percentage  = $price['customer_discount_percentage'] ?? null;
        $stock->customer_discount_amount  = $price['customer_discount_amount'] ?? null;
        $stock->purchase_discount_percentage  =  $price['purchase_discount_percentage'] ?? null;
        $stock->purchase_discount_amount =  $price['purchase_discount_amount'] ?? null;
        $stock->current_price = $price['current_price'];

        $tax = $taxService->getTaxAmount( $stock->co2, $stock->fuel_type);
        $stock->tax_amount_six_month   = $tax['6_months_tax'] ?? null;
        $stock->tax_amount_twelve_month = $tax['12_months_tax'] ?? null;
        return (array) $stock;
    }


    protected  function getSaleLocation()
    {
        return $this->excelRow->location;

    }
    /**
     * Import stock form excel file
     */

    public static function importStocks($filePath, $importedTab)
    {
        $stocks = [];

        $service = app(StockService::class);
        $service->moveToStockHistoryByImportedTab($importedTab);

        Excel::load($filePath, function($reader) use($importedTab,$stocks) {
            $reader->skipRows(2);
            $reader->noHeading();
            $reader->toObject();
            $reader->each(function($sheet)use($stocks,$importedTab) {
                if(in_array($sheet->getTitle(),self::$sheetAccepted)){
                    $sheet->each(function($row) use($sheet,$importedTab,$stocks) {
                        if (!is_null($row[2]) && !is_null($row[3])){
                            if($row[1]!="STK NO" && $row[2]!="MODEL" && $row[3]!="VERSION")
                            {                // Loop through all rows
                                $row=[
                                    'stk_no'=>trim($row[1]),
                                    'model'=>trim($row[2]),
                                    'version'=>trim($row[3]),
                                    'colour'=>trim($row[4]),
                                    'options'=>trim($row[5]),
                                    'chassis'=>trim($row[6]),
                                    'location'=>trim($row[7]),
                                    'order_no'=>trim($row[8]),
                                    'cons_date'=>trim($row[9])
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
                            }
                            //not found
                        }
                    });
                }
            });
        });
    }

}