<?php
namespace App\Repositories\Stocks\Driftbridge;

use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Cap\CAPRange;
use App\Models\Cap\NVDTechnical;
use App\Models\Stock;
use App\Services\Stocks\ColourService;
use App\Repositories\Stocks\AbstractRepository;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\TaxCalculationService;
use App\Services\StockService;
use Carbon\Carbon;


class Volkswagen extends AbstractRepository
{
    protected $splitByEngineArr = [];
    protected $notConsidered = '';
    protected $cmanName     = 'volkswagen';
    protected $addExtraOnCustomerPricePercent = 1.5;
    /**
     * This pattern define already considered
     * @var array
     */
    protected $replaceAbleConsideredPattern = [
        '/[0-9]*\.[0-9]+/'  => ' ', #enzine size
        '/Manual/'          => ' ', #only manual word
        '/\d-?[DRdr]{2}|\d\ ?Door/i' => ' ', #number of doors
        '/(\d+)\s*PS/i' => '  ',    #100ps to 100
        '/edition/i' => '',
        '/nav/i'    => '',
        '/tsi/i'    => '',
    ];

    /** replaceAble Mapped Pattern words */
    protected $replaceAbleMappedPattern = [
        '/R-Line/i'         => ' R Line ',
        '/new/i'             => ' ',
        '/high up/i'         => ' ',
        '/!/i'               => ' ',
        '/\d*(motion)/i'     => ' motion ',
        '/(\d*)-speed/i'     => ' ',
        '/SWB/i'            => '',
        '/ACT/i'        => '',
        '/SCR/i'        => '',
        '/bmt/i'    => '' //bmt word not found in cder_name,
    ];

    /**
     * This word will be found at capmod table
     *
     * @var array
     */
    protected $cmodWords = [
        'Cabriolet',
        'Estate',
        'saloon'
    ];

    /** Not Exist Not Like Words */
    protected  $notExistsNotLikeWords = [
        'Start Stop',
    ];

    /** @var array logic name  */
    protected $logicPerformed = [
        'start' => false,
        'not_edition' => false,
        'discontinued' => false,
        'not_exist_not_like' => false
    ];

    /**
     * Driftbridge constructor.
     *
     * @param $sheetName
     * @param $excelRow
     */
    public function __construct($excelRow, $sheetName = null)
    {
        parent::__construct($excelRow, $sheetName);

        /** Split by engine size */
        $this->splitByEngineArr  = preg_split("/[0-9]*\.[0-9]+/", $this->excelRow->model_description);


        $tempString =  $this->excelRow->model_description;
        /**Replacing considered pattern*/
        foreach( $this->replaceAbleConsideredPattern as  $replaceAblePattern => $replacedByPattern)
        {
            $tempString = preg_replace($replaceAblePattern, $replacedByPattern, $tempString);
        }

        /**Replacing replaceAbleMappedPattern pattern*/
        foreach( $this->replaceAbleMappedPattern as  $replaceAblePattern => $replacedByPattern)
        {
            $tempString = preg_replace($replaceAblePattern, $replacedByPattern, $tempString);
        }

        /**  Replacing transmission word */
        foreach( $this->autoTransmissionWords as   $transmissionWord)
        {
            $tempString = str_ireplace($transmissionWord, ' ', $tempString);
        }

        /**  Replace caprange name*/
        $tempString = str_ireplace( $this->getCranName() , ' ', $tempString);

        /**  Replace capmod name*/
        $tempString = str_ireplace( $this->getCmodName() , ' ', $tempString);

        $this->notConsidered  = $tempString;
    }

    /**
     * Get number of doors
     *
     * @return int
     */
    protected function getNumberOfDoors()
    {
        $doors = null;
        /** Get number of doors */
        preg_match("/\d-?[DRdr]{2}|\d\ ?Door/i", $this->excelRow->model_description, $numOfDoorsArr);
        if(!empty($numOfDoorsArr))
        {
            $doors = (int) filter_var($numOfDoorsArr[0],FILTER_SANITIZE_NUMBER_INT);
        }

        if($this->isExist('Scirocco') && $doors == 2)
        {
            return 3;
        }

        return $doors;
    }

    /**
     * Get bhp form excel
     * @return int
     */
    protected function getBhp()
    {
        /** BHP */
        preg_match("/\d+.ps|\d+.PS/i", $this->excelRow->model_description, $bhpArr);
        if(!empty($bhpArr))
        {
            return (int) filter_var($bhpArr[0],FILTER_SANITIZE_NUMBER_INT);
        }

    }

    /**
     * Get engine size
     *
     * @return mixed
     */
    protected function getEngineSize()
    {
        /** Get engine size */
        preg_match("/[0-9]*\.[0-9]+/", $this->excelRow->model_description, $tempEngineSizeArr);
        return  $tempEngineSizeArr[0];
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
        $isAutomatic = preg_match( "/\b($words)\b/i", $this->excelRow->model_description);
        return $isAutomatic ? 'A' : 'M';
    }

    /**
     * Is it new car or not
     * @return bool
     */
    public function isNew()
    {
        //all volkswagan new car
        return true; //TODO
    }

    /**
     * Get cran name
     *
     * @return string
     */
    protected function getCranName()
    {
        $cranNameContainWords = [
            'CAMPER',
            'MAXI',
            'LIFE',
            'ALLTRACK',
            'PLUS',
            'SV',
            'CC',
            'ALLSPACE'
        ];

        /**Remove unwanted word*/
        $firstPartOfExcelModel  =  str_ireplace(
            ['!', 'new' , 'High'],
            ['', '', ''],
            $this->splitByEngineArr[0]);


        $tempCranArr       =  explode(' ', trim($firstPartOfExcelModel));
        $cranNameArr[]     = $tempCranArr[0]; //first word

        //others word if matched
        foreach($tempCranArr as $word)
        {
            if($word && in_array($word, $cranNameContainWords))
            {
                $cranNameArr[] = trim($word);
            }
        }

        return  implode(' ', $cranNameArr);
    }

    /**
     * Get cman name
     * @return string
     */
    protected function getCmanName()
    {
        return $this->cmanName;
    }

    /**
     * Get cman code
     * @return int
     */
    protected function getCmanCode()
    {
        return 12243;
    }

    /**
     * Get cran code form database
     *
     * @return int|null
     */
    protected function getCranCode()
    {
        return '';
        $capRangeList =  CAPRange::where('cran_mantextcode', $this->getCmanCode())
            ->where('cran_name', '=', $this->getCranName())
            ->get();

        return $capRangeList->first()->cran_code;
    }

    /**
     * Get cmod name
     *
     * @return null| string
     */
    protected function getCmodName()
    {
        foreach( $this->cmodWords as   $cmodWord)
        {
            if(preg_match("/{$cmodWord}/i", $this->excelRow))
            {
                return $cmodWord;
            }
        }
    }


    protected  function  isExist($word)
    {
       return (boolean) preg_match( "/{$word}/i", $this->excelRow->model_description);
    }

    /**
     * Get cder id excel data
     *
     * @return string|null|array
     */
    public function getCderId()
    {
        $cderListQuery =   CAPDer::select('cder_id')
          ->join('CAPMan', 'cman_code', '=', 'cder_mancode')
            ->join('CAPRange', 'cran_code', '=', 'cder_rancode')
            ->join('CAPMod', 'cmod_code', '=', 'cder_modcode')
            ->where('cman_name',  $this->getCmanName())
            ->where('cran_name','like', $this->getCranName())
            ->where('cder_transmission',  $this->getCderTransmission())
            ->where('cder_name','like',  "%".($this->getEngineSize())."%");

        if( $this->getNumberOfDoors() )
        {
            $cderListQuery->where('cder_doors', $this->getNumberOfDoors() );
        }

        if($this->getCmodName())
        {
            $cderListQuery->where('cmod_name', 'like', "%" . $this->getCmodName() . "%" );
        }

        if( $this->isExist('nav') )
        {
            $cderListQuery->where('cder_name', 'like', '%nav%' );
        }

        if(  $this->isExist('edition') )
        {
            $cderListQuery->where('cder_name', 'like', '%edition%' );
        }

        if( $this->isExist('high up') )
        {
            $cderListQuery->where('cder_name', 'like', '%high up%' );
        }

        if( $this->getBhp() )
        {
            $cderListQuery->join('NVDTechnical', 'TECH_id', '=', 'cder_id')
                ->where('TECH_TechCode', NVDTechnical::$technicalDictionary['BHP'])
                ->where('TECH_Value_Float', $this->getBhp());
        }


        /**
         * Model specific rule
         */
        switch ( strtoupper($this->getCranName()) )
        {
            case 'GOLF':
                if(!$this->getCmodName())
                {
                    $cderListQuery->where('cmod_name', 'not like', '%ESTATE%');
                }
                break;

            case 'BEETLE':
                if(!$this->getCmodName())
                {
                    $cderListQuery->where('cmod_name', 'not like', '%Cabriolet%');
                }
                break;
        }

        $words = explode(' ', $this->notConsidered);
        foreach(array_filter($words) as $word)
        {
            if($word == '2WD')
            {
                $cderListQuery->where('cder_name', 'not like', '%4motion%');
            }
            else if(strtoupper(trim($word)) === 'S')
            {
                $cderListQuery->where('cder_name', 'not like', '%SE%');
            }
            else if(strtoupper(trim($word)) == 'EVO')
            {
                if(!$this->isExist('line'))
                {
                    $cderListQuery->where('cder_name' , 'not like', '%line%');
                }
                $cderListQuery->where('cder_name', 'like', "%evo%" );
            }
            else
            {
                $cderListQuery->where('cder_name', 'like', "%{$word}%" );
            }
        }

        return $this->performLogic('start', $cderListQuery);
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
     * Perform logic to find exact cder id
     *
     * @param $logic
     * @param $cderListQuery
     * @return null
     */
    public function performLogic($logic, $cderListQuery)
    {
        switch($logic)
        {
            case 'start':
                $this->logicPerformed['start'] = true;
                $clonedQuery = clone $cderListQuery;
                break;

            case 'not_edition':
                $this->logicPerformed['not_edition'] = true;
                if($this->isExist('edition'))
                {
                    return $this->performLogic($this->getNextLogic(), $cderListQuery);
                }
                $clonedQuery = clone $cderListQuery;
                $clonedQuery->where('cder_name', 'not like', "%edition%");
                break;

            case 'discontinued':
                $this->logicPerformed['discontinued'] = true;
                $clonedQuery = clone $cderListQuery;
                $clonedQuery->whereNull('cder_discontinued');
                break;
            case 'not_exist_not_like':
                $this->logicPerformed['not_exist_not_like'] = true;
                $clonedQuery = clone $cderListQuery;
                foreach ($this->notExistsNotLikeWords as $word)
                {

                    if( !$this->isExist($word) )
                        $clonedQuery->where('cder_name', 'not like', "%{$word}%");
                }
                break;
            default:
                return null; //last exit point
        }


        $cderList = $clonedQuery->get();

        if($cderList->count() == 1)
        {
            return $cderList->first()->cder_id;
        }
        elseif($cderList->count() > 1)
        {
            return $this->performLogic($this->getNextLogic(), $clonedQuery);
        }

        return $this->performLogic($this->getNextLogic(), $cderListQuery);
    }

    public function  findCderAndFormatStockArr()
    {
        $cderId  = $this->getCderId();
        if(!$cderId ) return null;

        $capder   =  CAPDer::with(['range','make','NVDTechnical','NVDPrices' => function($query){
            $query->orderBy('PR_Id', 'DESC');
        } , 'standardOptions'])->find($cderId);

        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();

        try{
            $price  = $priceService->getNewCarPrice($this->excelRow->offer_price,  $this->excelRow->rrp);
        }
        catch (\Exception $exception)
        {
            LogHelper::save('ERROR', $exception );
            return null;
        }

        if( ! $this->isValidPrice($price) )
        {
            LogHelper::save('ERROR', new \Exception("Pricing problem:". json_encode([$this->excelRow, $price], JSON_PRETTY_PRINT)));
            return null;
        }

        $stock                              = new \stdClass();
        $stock->stock_ref                   = trim($this->excelRow->stock_id);
        $stock->chassis_no                  = null;
        $stock->car_type                    = $this->isNew() ? 'new' : 'used';
        $stock->make                        = $this->formatMake($capder->make->cman_name);
        $stock->model_id                    = $capder->range->cran_code;
        $stock->model                       = trim($capder->range->cran_name);
        $stock->derivative_id               = $capder->cder_ID;
        $stock->cap_code                    = trim($capder->cder_capcode);
        $stock->derivative                  = trim($capder->cder_name);
        $stock->supplier_spec               = trim($this->excelRow->model_description);
        $stock->body_type                   = $capder->isConvertible() ? 'convertible' : CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description));
        $stock->transmission                = CAPDer::getTransmission($capder->cder_transmission);
        $stock->fuel_type                   = CAPDer::getFuelType($capder->cder_fueltype);
        $stock->colour_spec                 = strtolower(trim($this->excelRow->external_color_description));

        $colourService                      = app(ColourService::class);
        $stock->colour                      = $colourService->parse(strtolower(trim($this->excelRow->external_color_description)));

        $stock->doors                       = $capder->cder_doors;
        $stock->standard_option             = $capder->standardOptionsString;
        $stock->additional_option           = trim($this->excelRow->options);
        $stock->additional_option_price     = null;
        $stock->registration_no             = null;
        $stock->registration_date           = null;
        $stock->mpg                         = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp                         = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2                         = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage             = (int)filter_var($this->excelRow->mileage, FILTER_SANITIZE_NUMBER_INT);
        $stock->grade                       = null;
        $stock->model_year                  = trim($this->excelRow->model_year);

        $stock->sale_location   = $this->mapSaleLocation( 'driftbridge_volkswagen');
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        if($this->addExtraOnCustomerPricePercent > 0) {
            $price['customer_price'] = $price['customer_price']  +
                ($price['customer_price']  * $this->addExtraOnCustomerPricePercent / 100);
        }
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

    /**
     * Import stock form excel file
     */
    public static function importStocks($filePath, $importedTab)
    {
        $service = app(StockService::class);
        $service->moveToStockHistoryByImportedTab($importedTab);

        /**Filtering*/
        $excelRows = $service->getStock($filePath)
            ->filter(function ($excelRow) {
                return !empty(trim($excelRow->model_description));
            });
        foreach ($excelRows as $excelRow)
        {

            $stock = (new static($excelRow))->findCderAndFormatStockArr();

            if(!$stock){
                LogHelper::save("INFO", new \Exception("Cder not found stock feed excel row excel data:" .
                    json_encode($excelRow,JSON_PRETTY_PRINT)));
                continue;
            };

            $stock['supplier']          = config("constants.feeds.{$importedTab}.supplier");
            $stock['imported_tab']      = $importedTab;
            $stock['created_at']        = (string) Carbon::now();
            $stock['updated_at']        = (string) Carbon::now();

            Stock::incrementOrInsert($stock);
        }
    }
}