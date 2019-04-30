<?php

namespace App\Repositories\Stocks;

use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Cap\NVDTechnical;
use App\Models\Stock;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\ColourService;
use App\Services\Stocks\TaxCalculationService;
use App\Services\StockService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BritishCarAuction extends AbstractRepository
{
    protected  $versionWordsArr = [];
    protected  $co2;
    protected $wordList;
    protected $numberList;

    /**
     * Mapping excel words with database word
     * $key    : excel sheet word
     * $value  : capder word
     */
    protected $wordMapping = [
        'P/T'  => 'PureTech',
        'E/B'  => 'EcoBoost',
        'BMT'  => 'BlueMotion Tech',
        'E/F'   => 'ecoFLEX',
        'B/TEC' => 'BlueTEC',
        'B/T'   => 'BlueTEC'
    ];

    protected $notExistsNotLikeWords = [
        'Nav',
        'plus',
        'i-Dynamique'
    ];

    protected $cranWordMapping = [
        'R/R'    => 'RANGE ROVER',
        'UP!'    => 'UP',
    ];

    protected $cmanMapping = [
        'DS AUTOMOBILES'  => 'DS',
        'MERCEDES'        => 'MERCEDES-BENZ'
    ];


    protected $engineSizeMapping = [
        'MERCEDES-BENZ' => [
            'CLS 350 D S/B' => 'CLS 350D',
            'CLS 220 D S/B' => 'CLS 220d',
            'SL63 AMG' => 'SL 63'
        ],
        'AUDI' => [
            'S4' => 'S4',
        ],
        'SMART' => [
            'FORTWO' => '',
            'FORFOUR' => ''
        ],
        'VOLVO' => [
            'V60 SPORTWAGON' => ''
        ],
        'BMW' => [
            'X3' => '',
            'X4' => '',
            'Z4' => '',
            'ALPINA' => '',
            '420D GRAN COUPE' => '420D',
            '640D GRAN COUPE' => '640D',
            '535D GT' => '535D',
            '330E' => '3 M'
        ],
        'LEXUS' => [
            'IS 220D' => '220D',
            'IS 300H' => '300H',
            'IS 250'  => '250',
            'IS 250C'  => '250C'
        ],
        'JAGUAR' => [
            'XJ8' => 'XJ8'  //XJ8 {engine_size}
        ]

    ];


    protected $cranMapping = [
        'ABARTH' => [
            '500C' => '500'
        ],
        'AUDI' => [
            'A1 SPORTBACK' => 'A1',
            'A3 CABRIOLET' => 'A3',
            'A3 SPORTBACK' => 'A3',
            'A4 AVANT' => 'A4',
            'A4 CABRIOLET' => 'A4',
            'A5 CABRIOLET' => 'A5',
            'A6 AVANT' => 'A6',
            'A7 SPORTBACK' => 'A7',
            'R8 SPYDER' => 'R8',
            'SQ5' => 'A5',
            'TTS ROADSTER' => 'TT',
            'TT ROADSTER' => 'TT',
            'S4'    => 'A4'
        ],
        'FERRARI' => [
            '612 SCAGLIETTI' => '612'
        ],
        'HYUNDAI'  => [
            'I40 TOURER' => 'I40'
        ],
        'KIA'  => [
            'CEE D' => 'CEED'
        ],
        'LEXUS'  => [
            'CT 200H' => 'CT',
            'IS 220D' => 'IS',
            'IS 300H' => 'IS',
            'IS 250'  => 'IS',
            'IS 250C' => 'IS'
        ],
        'MAZDA' => [
            '6 TOURER' => '6'
        ],
        'PEUGEOT' => [
            '207 SW' => '207',
            '207 CC' => '207',
            '308 SW' => '308',
            '308 CC' => '308',
            '508 SW' => '508',
            'PARTNER L1' => '%PARTNER%',
            'PARTNER' => '%PARTNER%',
            '108 TOP!' => '108'
        ],
        'SAAB' => [
            '9-3 SPORTWAGON' => '9-3'
        ],
        'SEAT' => [
            'IBIZA SC' => 'IBIZA',
            'IBIZA ST' => 'IBIZA'
        ],
        'SUBARU' => [
            'LEGACY OUTBACK' => 'LEGACY',
            'IBIZA ST' => 'IBIZA'
        ],
        'SUZUKI' => [
            'SX4 S CROSS' => 'SX4 S-CROSS',
            'IBIZA ST' => 'IBIZA'
        ],
        'VAUXHALL' => [
            'ASTRA TOURER' => 'ASTRA',
            'ASTRA TWINTOP' => 'ASTRA',
            'INSIGNIA TOURER' => 'INSIGNIA'
        ],
        'VOLKSWAGEN' => [
            'UP!' => 'UP',
            'GOLF CABRIOLET' => 'GOLF',
        ],
        'VOLVO' => [
            'V40CROSSCOUNTRY' => 'V40',
            'V60 SPORTWAGON' => 'V60'
        ],
        'MINI' => [
            'ONE' => 'ONE CLUBMAN',
            'COOPER CLUBMAN' => 'CLUBMAN',
        ],
        'BENTLEY' => [
            'FLYING SPUR' => 'CONTINENTAL FLYING SPUR'
        ],
        'CITROEN' => [
            'BERLINGO' => '%BERLINGO%' //todo pattern will be '%berlingo%
        ],
        'LAND ROVER' => [
            'R/R EVOQUE' => 'RANGE ROVER EVOQUE',
            'R/R SPORT' => 'RANGE ROVER SPORT',
            'FREELANDER 2' => 'FREELANDER',
            'DISCOVERY 4' => 'DISCOVERY',
            'DISCOVERY 3' => 'DISCOVERY'
        ],
        'SKODA' => [
            'OCTAVIA SCOUT' => 'OCTAVIA'
        ],
        'JAGUAR' => [
            'XF SPORTBRAKE' => 'XF',
            'XJ SERIES LWB' => 'XJ',
            'XJ8' => 'V8 XJ SERIES'
        ],
        'BMW' => [
            'X3' => 'X3',
            'X4' => 'X4',
            'Z4' => 'Z4',
            'ALPINA' => 'ALPINA',
            '330E' => '3 SERIES'
        ],
        'FIAT' => [
            '500C' => '500'
        ]
    ];

    protected $logicPerformed =[
        'first_word' => false,
        'first_number' => false,
        'second_word' => false,
        'co2' => false,
        'price' => false,
        'third_word' => false,
        'not_exists_not_like' => false,
    ];
    //Transmission mapping
    //B	Petrol/LPG
    //C	Hydrogen Fuel Cell
    //D	Diesel
    //E	Electric
    //F	Petrol/Bio Ethanol (E85)
    //G	Petrol/CNG
    //H	Petrol/Electric Hybrid
    //P	Petrol
    //X	Petrol/PlugIn Elec Hybrid
    //Y	Diesel/Electric Hybrid
    //Z	Diesel/PlugIn Elec Hybrid
    protected $fuelTypeMapping = [
        'Diesel'    => 'D',
        'Petrol'    => 'P',
        'Electric'  => 'E',
        'Petrol/Electric'   => 'H',
        'Diesel/Electric'   => 'Y',
    ];
    private $validGradeList = [1,2];

    /**
     * BCA constructor.
     * @param $excelRow
     * @param $sheetName
     */
    public function __construct($excelRow, $sheetName )
    {
        parent::__construct($excelRow, $sheetName);
        $this->co2 = $this->getCo2();
    }


    /**
     * Get co2
     * @return bool|string
     */
    protected function getCo2()
    {
        if(preg_match("/CO2 Emissions\D*(\d*)\D*g/i", $this->excelRow->equipment, $matches))
        {
            return (int) $matches[1];
        }
    }

    /**
     * Get range name like form excel row
     *
     * @return string
     */
    protected function getCapRangePattern()
    {
        $model = str_replace(array_keys($this->cranWordMapping), array_values($this->cranWordMapping),
        $this->excelRow->model);
        $words = explode(' ', $model);
        return '%' . $words[0] .'%';
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
        $isAutomatic = preg_match( "/\b($words)\b/i", $this->excelRow->transmission);
        return $isAutomatic ? 'A' : 'M';
    }

    /**
     * Get fuel code from excel row
     */
    public function getFuelType()
    {
        $fuelType = trim($this->excelRow->fuel);
        return isset($this->fuelTypeMapping[$fuelType]) ?
            $this->fuelTypeMapping[$fuelType] : 'P';
    }

    /**
     * @param $wordNo
     * @return string
     */
    protected function cderNameWord($wordNo)
    {
        if(!$this->wordList)
        {
            $dirivitive = $this->excelRow->deriviative;
            $dirivitive = str_replace(array_keys($this->wordMapping), array_values($this->wordMapping), $dirivitive);
            $dirivitive = preg_replace('/ \d* |\d* | \d*/i', ' ', $dirivitive);//replacing number
            $tempWordList       = array_filter(explode(' ', $dirivitive));
            $this->wordList = ($tempWordList);
        }
        return$this->wordList[$wordNo-1] ??  '';
    }

    /**
     * @param $numberNo
     * @return string
     */
    protected function cderNumber($numberNo)
    {
        if(empty($this->numberList))
        {
            preg_match('/ \d* |\d* | \d*/i', $this->excelRow->deriviative, $numbers);//replacing number
            $this->numberList   = array_filter($numbers);
        }

        return (int) ($this->numberList[$numberNo-1] ??  '');
    }

    /**
     * Get cder id from excel data
     *
     * @return string|null
     */
    public function getCderId()
    {
        $cderListQuery =   CAPDer::join('CAPMan', 'cder_mancode', '=', 'cman_code')
            ->join('CAPRange', 'cder_rancode', '=', 'cran_code')
            ->join('NVDPrices', 'pr_id', 'cder_id')
            ->where('cman_name', $this->getCmanName());

        $cderListQuery->where('cder_name', 'like', "%" . $this->getEngineSize() . "%")
            ->where('cder_doors', $this->getNumerOfDoors())
            ->where('cder_introduced', '<=', $this->getRegistrationDate()->format('Y-m-d'))
            ->where('cder_transmission', $this->getCderTransmission())
            ->where('cder_fueltype',  $this->getFuelType())
            ->where('cran_name','like' , $this->getCranNameLike());
        return $this->performLogic($this->getNextLogic(), $cderListQuery);
    }


    /**
     * Get which logic have perform next
     *
     * @return int|string
     */
    public function getNextLogic()
    {
        foreach($this->logicPerformed as $logic => $performed)
        {
            if($performed == false)
                return $logic;
        }
    }

    /**
     * Perform logic to find exact ceder id
     *
     * @param $logic
     * @param $cderListQuery
     * @return null
     */
    public function performLogic($logic, $cderListQuery)
    {
        LogHelper::save('INFO', new \Exception("Logic working". $logic));

        switch($logic)
        {
            case 'first_word':
                $this->logicPerformed['first_word'] = true;
                $clonedQuery = clone $cderListQuery;
                $word = $this->cderNameWord(1);

                if(empty($word))
                {
                    $this->logicPerformed['second_word'] = true;
                    $this->logicPerformed['third_word'] = true;
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }

                $clonedQuery->where('cder_name', 'like', "%{$word}%");
                break;

            case 'second_word':
                $this->logicPerformed['second_word'] = true;
                $clonedQuery = clone $cderListQuery;
                $word = $this->cderNameWord(2);

                if(empty($word))
                {
                    $this->logicPerformed['third_word'] = true;
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }

                $clonedQuery->where('cder_name', 'like', "%{$word}%");
                break;

            case 'third_word':
                $this->logicPerformed['third_word'] = true;
                $clonedQuery = clone $cderListQuery;
                $word = $this->cderNameWord(3);

                if(empty($word))
                {
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }

                $clonedQuery->where('cder_name', 'like', "%{$word}%");
                break;

            case 'price':
                $this->logicPerformed['price'] = true;
                $clonedQuery = clone $cderListQuery;

                $capPrice = filter_var( $this->excelRow->cap_new_price, FILTER_SANITIZE_NUMBER_INT);
                if(!$capPrice)
                {
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }

                $clonedQuery->whereRaw(sprintf('(PR_Basic + PR_Vat + PR_Delivery) = %d', $capPrice));
                break;

            case 'co2':
                $this->logicPerformed['co2'] = true;
                $clonedQuery = clone $cderListQuery;
                if (!$this->co2) {
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }
                $clonedQuery->join('NVDTechnical', 'TECH_id', '=', 'cder_id')
                    ->where('TECH_TechCode', NVDTechnical::$technicalDictionary['CO2'])
                    ->where('TECH_Value_Float', $this->co2);

                break;

            case 'first_number':
                $this->logicPerformed['first_number'] = true;
                $clonedQuery = clone $cderListQuery;
                $number = $this->cderNumber(1);

                if(empty($number))
                {
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }

                $clonedQuery->where('cder_name', 'like', "%{$number}%");
                break;

            case 'not_exists_not_like':
                $this->logicPerformed['not_exists_not_like'] = true;
                $clonedQuery = clone $cderListQuery;
                foreach ($this->notExistsNotLikeWords as $word)
                {
                    if( !$this->isExist($word) )
                        $clonedQuery->where('cder_name', 'not like', "%{$word}%");
                }
                break;
            default:
                return ''; //last exit point
        }


        $cderList = $clonedQuery->get();

        if($cderList->count() == 1)
        {
            return $cderList->first()->cder_ID;
        }
        elseif($cderList->count() > 1)
        {
            return $this->performLogic($this->getNextLogic(), $clonedQuery);
        }

        return $this->performLogic($this->getNextLogic(), $cderListQuery);
    }

    private function isValidGrade()
    {
        return in_array($this->excelRow->grade, $this->validGradeList);
    }

    public function  findCderAndFormatStockArr()
    {
        if(!$this->isValidGrade() || !$this->isValidUsedCarAge($this->getModelYear()) || !$this->isValidUsedCarMileage(filter_var($this->excelRow->mileage, FILTER_SANITIZE_NUMBER_INT)))
        {
            LogHelper::save('ERROR', new \Exception("Very old car :". json_encode($this->excelRow, JSON_PRETTY_PRINT)));
            return null;
        }

        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);

        $purchasePrice = filter_var($this->excelRow->buy_now_price, FILTER_SANITIZE_NUMBER_INT);
        $retailPrice = filter_var($this->excelRow->cap_retail_price, FILTER_SANITIZE_NUMBER_INT);
        
        try{
            $price    = $priceService->getUsedCarBCAPrice($purchasePrice, $retailPrice);
        }
        catch (\Exception $e)
        {
            LogHelper::save('ERROR', new \Exception($e->getMessage() ." Pricing Service Exception Excel data:".json_encode($this->excelRow, JSON_PRETTY_PRINT)));
            return null;
        }

        if(!$this->isValidPrice($price))
        {
            LogHelper::save('ERROR', new \Exception("Price problem :". json_encode([$this->excelRow, $price], JSON_PRETTY_PRINT)));
            return null;
        }

        $cderId  = $this->getCderId();

        if(!$cderId ) return null;

        $capder   =  CAPDer::with(['range', 'make', 'NVDTechnical','NVDPrices' => function($query){
            $query->orderBy('PR_Id', 'DESC');
        }])->find($cderId);
        $capPrice = $capder->NVDPrices->first();

        $stock                  = new \stdClass();
        $stock->stock_ref       = trim($this->excelRow->registration_number);
        $stock->car_type        = $this->isNew() ? 'new' : 'used';
        $stock->make            = $this->formatMake($capder->make->cman_name);
        $stock->model_id        = $capder->range->cran_code;
        $stock->model           = trim($capder->range->cran_name);
        $stock->model_year      = $this->getModelYear();
        $stock->derivative_id   = $capder->cder_ID;
        $stock->cap_code        = trim($capder->cder_capcode);
        $stock->derivative      = trim($capder->cder_name);
        $stock->supplier_spec   = trim($this->excelRow->deriviative);
        $stock->body_type       = $capder->isConvertible() ? 'convertible' : CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description));
        $stock->fuel_type       = CAPDer::getFuelType(trim($capder->cder_fueltype));
        $stock->transmission    = CAPDer::getTransmission(trim($capder->cder_transmission));
        $stock->doors           = $capder->cder_doors;
        $stock->colour_spec     = strtolower(trim($this->excelRow->colour));

        $colourService          = app(ColourService::class);
        $stock->colour          = $colourService->parse(strtolower(trim($this->excelRow->colour)));


        $stock->standard_option = trim($this->excelRow->equipment, ', ');
        $stock->additional_option = null;
        $stock->additional_option_price = null;
        $stock->registration_no = $this->excelRow->registration_number;
        $stock->registration_date= $this->getRegistrationDate()->format(config('constants.stocks.date_format'));
        $stock->mpg             = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp             = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2             = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage = filter_var($this->excelRow->mileage, FILTER_SANITIZE_NUMBER_INT);
        $stock->grade           = $this->excelRow->grade;
        $stock->sale_location   = $this->mapSaleLocation('bca', trim($this->excelRow->sale_location));

        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $stock->purchase_price  = $price['purchase_price'] ?? 0;
        $stock->customer_price  = $price['customer_price'] ?? 0;
        $stock->customer_discount_percentage  = $price['customer_discount_percentage'] ?? 0;
        $stock->customer_discount_amount  = $price['customer_discount_amount'] ?? 0;
        $stock->purchase_discount_percentage  =  $price['purchase_discount_percentage'] ?? 0;
        $stock->purchase_discount_amount =  $price['purchase_discount_amount'] ?? 0;
        $stock->current_price   = $price['current_price'] ?? 0;

        $tax = $taxService->getTaxAmount( $stock->co2, $stock->fuel_type,  $this->getRegistrationDate(), true);
        $stock->tax_amount_six_month   = $tax['6_months_tax'] ?? null;
        $stock->tax_amount_twelve_month   = $tax['12_months_tax'] ?? null;
        return (array) $stock;
    }

    /**
     * Import stock form excel file
     */
    public static function importStocks($filePath, $importedTab)
    {
        $service = app(StockService::class);
        $service->moveToStockHistoryByImportedTab($importedTab);

        Excel::load($filePath, function ($reader) use ($importedTab) {

            $reader->noHeading();
            $reader->skip(config("constants.feeds.{$importedTab}.sheet_config.skip_row"));

//            this line need for dubuging
//            $excelNotFoundRows = array();
//
//            \DB::connection('sqlsrv')->enableQueryLog();
//            $notFounds = [];
            foreach ($reader->get() as $key => $excelRow) {
                //$temp = $excelRow;
                $values = $excelRow->toArray();
                $tempKeys = array_fill(0, count($values), null);
                $mappingFromConfig = config("constants.feeds.{$importedTab}.sheet_config.column_mapping");
                $mapping =  $mappingFromConfig + $tempKeys;
                ksort($mapping);
                $excelRow = (object) array_combine($mapping, $values);

                LogHelper::save('INFO', new \Exception("Getting cder of : " . $excelRow->registration_number));
                if(!$excelRow->deriviative) continue;

                $stock = (new static($excelRow, null))->findCderAndFormatStockArr();
                if(!$stock){
//                    $excelNotFoundRows[$excelRow->registration_number] = $temp;
//                    $notFounds[] = $temp->toArray();
                    LogHelper::save('WARNING', new \Exception("Cder not found registration no: " . $excelRow->registration_number));
                    continue;
                }

                $stock['supplier']          = config("constants.feeds.{$importedTab}.supplier");
                $stock['imported_tab']      = $importedTab;
                $stock['created_at']        = (string) Carbon::now();
                $stock['updated_at']        = (string) Carbon::now();

                Stock::incrementOrInsert($stock);;
            }

//            $notFoundFileName = 'BCA not found list ' . Carbon::now();
//            Excel::create($notFoundFileName, function($excel) use($notFounds) {
//                $excel->sheet('File Not found', function($sheet) use($notFounds) {
//                    $sheet->fromArray($notFounds);
//                });
//            })->download('xls');

        });
    }

    private function isNew()
    {
        return false;
    }

    public function getCmanName()
    {
        $cmanName = $this->cmanMapping[$this->excelRow->make] ?? $this->excelRow->make;
        return trim($cmanName);
    }

    public function getCranNameLike()
    {
        if(in_array($this->getCmanName(), ['MERCEDES-BENZ', 'BMW']) )
        {
            return $this->cranMapping[$this->getCmanName()][$this->excelRow->model] ?? '%';
        }
        
        $cranName = $this->cranMapping[$this->getCmanName()][$this->excelRow->model] ?? '%' . $this->excelRow->model . '%';
        return trim($cranName);
    }

    public function getEngineSize()
    {
        if($this->excelRow->make == 'JAGUAR' && $this->excelRow->model == 'XKR' && $this->excelRow->engine_size = '5.0')
            return '';
        elseif($this->excelRow->engine_size == '0.0')
            return '';
        elseif( in_array( $this->getCmanName() , ['MASERATI']))
            return '';
        elseif($this->excelRow->make == 'FERRARI' && $this->excelRow->model == '612 SCAGLIETTI')
            return '';
        elseif($this->excelRow->make == 'VOLVO' && 0 !== preg_match('/2.4/i', $this->excelRow->engine_size))
            return '';
        else
        {
            switch ($this->getCmanName())
            {
                case 'MERCEDES-BENZ':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? str_ireplace(' ', '%', trim($this->excelRow->model));

                case 'BMW':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? str_ireplace(' ', '%', trim($this->excelRow->model));

                case 'AUDI':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? trim($this->excelRow->engine_size);

                case 'SMART':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? '';

                case 'VOLVO':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? trim($this->excelRow->engine_size);

                case 'LEXUS':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? trim($this->excelRow->engine_size);
                case 'JAGUAR':
                    return $this->engineSizeMapping[$this->getCmanName()][$this->excelRow->model] ?? trim($this->excelRow->engine_size);
            }
        }

        return trim($this->excelRow->engine_size) ?? '';
    }

    public function getRegistrationDate()
    {
        return $this->excelRow->registration_date ? Carbon::createFromFormat('d/m/Y', $this->excelRow->registration_date) : 0;
    }

    public function getNumerOfDoors()
    {
        return (int)($this->excelRow->number_of_doors);
    }

    /**
     * Get bhp
     * @return bool|string
     */
    protected function getBhp()
    {
        return null;
    }

    /**
     * Get model year
     *
     * @return int|null
     */
    private function getModelYear()
    {

        if(!preg_match('/\d+/i', $this->excelRow->registration_number, $matches) )
        {
            return null;
        }

        $numberFromReg = $matches[0];

        if($numberFromReg < 50)
        {
            return 2000 + $numberFromReg;
        }

        if($numberFromReg > 50)
        {
            return 2000 + ($numberFromReg % 50);
        }
    }

    private function isExist($word)
    {
        return (boolean) preg_match( "/{$word}/i", $this->excelRow->deriviative);
    }
}