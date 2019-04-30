<?php
namespace App\Repositories\Stocks\Driftbridge;

use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Cap\NVDOption;
use App\Models\Stock;
use App\Repositories\Stocks\AbstractRepository;
use App\Services\Stocks\TaxCalculationService;
use App\Services\StockService;
use App\Services\Stocks\ColourService;
use App\Services\Stocks\PricingService;
use Carbon\Carbon;


class Audi extends AbstractRepository
{

   protected $cmanName     = 'audi';

   protected $addExtraOnCustomerPricePercent = 1.5;

   protected $considerEngineSizeWords = [
     'S1',
     'S3',
     'S4',
     'S5',
     'S6',
     'S7',
     'S8',
     'SQ5',
     'SQ7',
   ];

  protected $capRangeWordmapping = [
        'S(\d)' => 'A$1',   // Ex. S1 means a1, S2 means A2
        'SQ(\d)' => 'Q$1',  // Ex. SQ7 means Q7 , SQ5 means Q5
        'TTS'   => 'TT',   // TT
        'RS.(\d)' => 'RS$1'
    ];

  protected $secoundWordOfCapRange = [
        'ALLROAD',
        '7',
        'Q3'
  ];

   protected $cmodNameWords = [
        'SPORTBACK',
        'SALOON',
        'AVANT',
        'CABRIOLET',
        'ALLROAD',
        'COUPE',
        'Coup-',
        'Spyder',
        'Roadster'
    ];

    protected $manualWordCheckingList = [
        "s line",
        "s tronic",
        "tiptronic",
        "quattro",
        "ultra",
        "tfsi",
        "edition",
        "sport",
        "TDI"
    ];

    protected $notExistsNotLike = [
      'edition',
      'quattro',
      'ultra',
      'Vorsprung'
    ];

    /**
     * Audi constructor.
     * @param $sheetName
     * @param $excelRow
     */
    public function __construct($excelRow, $sheetName = null)
    {
        parent::__construct($excelRow, $sheetName);
    }

    public function getCranName()
    {
        $cranMappedModelDescription  = $this->excelRow->model_description;

        foreach($this->capRangeWordmapping as $pattern => $replacement)
        {
            $cranMappedModelDescription = preg_replace("/{$pattern}/i", $replacement, $cranMappedModelDescription);
        }

        $words = explode(' ', $cranMappedModelDescription);

        if(isset($words[2]) && in_array($words[2], $this->secoundWordOfCapRange))
        {
            return $words[1] . ' ' . $words[2];
        }

        return  $words[1];
    }

    public function cderNamePattern($wordNo = 0)
    {
        $tempStr = str_ireplace($this->getCranName(),'', $this->matchableWords);
        $tempWordList = array_values(array_filter(explode(' ', $tempStr)));
        return isset($tempWordList[$wordNo]) ? "%" . $tempWordList[$wordNo] ."%" : '%%';
    }

    public function getCmodNamePattern()
    {
        $temp = $this->excelRow->model_description;
        $temp = preg_replace('/Coup-/i', 'Coupe', $temp);
        preg_match("/".(join('|', $this->cmodNameWords)). "/i", $temp, $bodyStyleWords );
        return  isset($bodyStyleWords[0]) ? "%" . $bodyStyleWords[0] . "%" : '%';

    }

    public function  isNew()
    {
        return true;//driftbridge only sell new car
    }


    protected $logicPerformed = [
        'discontinued' => false,
        'technology_pack' => false,
        'notedition' => false,
        'bhp'       => false,
        'tfsi'      => false,
        'notexistsnotlike' => false,
    ];

    public function getNextLogic()
    {
        foreach($this->logicPerformed as $logic => $performed)
        {
            if($performed == false)
                return $logic;
        }
    }

    public function performLogic($logic, $cderListQuery)
    {
        switch($logic)
        {
            case 'technology_pack':
                $this->logicPerformed['technology_pack'] = true;

                $clonedQuery = clone $cderListQuery;
                if(preg_match('/WB4/i', $this->excelRow->optionals_importer, $wordArr))
                {
                    $clonedQuery->where('cder_name', 'like', "%Tech%");
                }
                else
                {
                    $clonedQuery->where('cder_name', 'not like', "%Tech%");
                }
                break;

            case 'discontinued':
                $this->logicPerformed['discontinued'] = true;
                $clonedQuery = clone $cderListQuery;
                $clonedQuery->whereNull('cder_discontinued');
                break;

            case 'notedition':
                $this->logicPerformed['notedition'] = true;
                $clonedQuery = clone $cderListQuery;

                if($this->isExist('edition'))
                    return $this->performLogic($this->getNextLogic(), $clonedQuery);

                    $clonedQuery->where('cder_name', 'not like', '%edition%');
                break;

            case 'tfsi':
                $this->logicPerformed['tfsi'] = true;
                $clonedQuery = clone $cderListQuery;

                if(!$this->isExist('tfsi'))
                    return $this->performLogic($this->getNextLogic(), $clonedQuery);

                $clonedQuery->where('cder_name', 'like', '%FSI%');
                break;

            case 'notexistsnotlike':
                $this->logicPerformed['notexistsnotlike'] = true;
                $clonedQuery = clone $cderListQuery;

                foreach($this->notExistsNotLike as $word)
                {
                    if($this->isExist($word))
                        $clonedQuery->where('cder_name', 'like', "%$word%");
                    else
                        $clonedQuery->where('cder_name', 'not like', "%$word%");
                }
                break;
            case 'bhp':
                $this->logicPerformed['bhp'] = true;
                $clonedQuery = clone $cderListQuery;
                $clonedQuery->where('cder_name', 'like', '%' . $this->getBhp() . '%');
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

    public function getManName()
    {
        return 'audi';
    }

    protected function getEngineSize()
    {
        preg_match("/".(join('|', $this->considerEngineSizeWords)). "/i",  $this->excelRow->model_description , $matches);

        if( isset($matches[0]) )
            return $matches[0];
        /** Get engine size */
        preg_match("/[0-9]*\.[0-9]+/", $this->excelRow->model_description, $tempEngineSizeArr);
        return  isset($tempEngineSizeArr[0]) ? $tempEngineSizeArr[0] : '';
    }

    /**
     * Get bhp form excel
     * @return int
     */
    protected function getBhp()
    {
        preg_match("/\d+.ps|\d+.PS/i", $this->excelRow->model_description, $bhpArr);
        if(!empty($bhpArr))
        {
            return (int) filter_var($bhpArr[0],FILTER_SANITIZE_NUMBER_INT);
        }
    }

    /**
     * Is given word exists in excel row
     *
     * @return string
     */
    protected  function  isExist($word)
    {
        return (boolean) preg_match( "/{$word}/i", $this->excelRow->model_description);
    }

    /**
     * Get bhp form excel
     * @return int
     */
    protected function getUnconsidered()
    {
        $tempStr = $this->excelRow->model_description;

        foreach($this->capRangeWordmapping as $pattern => $replacement)
        {
            $tempStr = preg_replace("/{$pattern}/i", $replacement, $tempStr);
        }

        $words = explode(' ', $tempStr);

        if(isset($words[2]) && in_array($words[2], $this->secoundWordOfCapRange))
        {
            $words[0] = $words[1] =  $words[2] = null;
        }
        else
        {
            $words[0] = $words[1] = null;
        }

        $tempStr = implode(' ', $words);

        $tempStr = preg_replace("/".(join('|', $this->cmodNameWords)). "/i", '', $tempStr );
        $tempStr = preg_replace("/".(join('|', $this->considerEngineSizeWords)). "/i", '', $tempStr );
        $tempStr = preg_replace("/\d+.ps|\d+.PS/i", '' ,$tempStr );
        $tempStr = preg_replace("/[0-9]*\.[0-9]+/i", '' ,$tempStr );
        $tempStr = preg_replace("/\d-speed/i", '' ,$tempStr );
        $tempStr = preg_replace("/cylinder on demand/i", '' ,$tempStr );

        foreach($this->manualWordCheckingList as $word)
        {
            $tempStr = preg_replace("/{$word}/i", '' ,$tempStr );
        }

       return  preg_replace("/ * /", " ", trim($tempStr));
    }

    public function getCderId()
    {
         $cderListQuery = CAPDer::select('cder_id')
            ->join('CAPMan', 'cman_code', 'cder_mancode')
            ->join('CAPRange', 'cran_code', 'cder_rancode')
            ->join('CAPMod', 'cmod_code', 'cder_modcode')
            ->where('cman_name', $this->cmanName)
            ->where('cran_name',  $this->getCranName() )
            ->where('cder_name', 'like', "%". $this->getEngineSize() ."%");

         $doors = $this->getNumberOfDoors();
         if(is_array($doors))
         {
             $cderListQuery->whereIn('cder_doors', $doors);
         }
         else
         {
             $cderListQuery->where('cder_doors', $doors);
         }

         $cderListQuery->where('cmod_name', 'like', $this->getCmodNamePattern());

         foreach(explode(' ', $this->getUnconsidered()) as $word)
         {
             $cderListQuery->where('cder_name', 'like', "%{$word}%");
         }

         if($this->isExist('s line'))
             $cderListQuery->where('cder_name', 'like', "%s line%");

        if($this->isExist('s tronic'))
            $cderListQuery->where('cder_name', 'like', "%s tronic%");

        if($this->isExist('edition'))
            $cderListQuery->where('cder_name', 'like', "%edition%");

        if($this->isExist('quattro'))
            $cderListQuery->where('cder_name', 'like', "%quattro%");

         $cderListQuery->join('NVDTechnical', 'cder_id', 'TEch_id')
             ->where('TECH_TEchCode', 21)
             ->where('TECH_Value_Float', $this->getBhp());

         if($this->isExist('tip'))
            $cderListQuery->where('cder_name', 'like', "%tip%");


        if($this->isExist('tdi') && $this->getEngineSize() != 'SQ7')
            $cderListQuery->where('cder_name', 'like', "%tdi%");

        if($this->isExist(' sport ') )
            $cderListQuery->where('cder_name', 'like', "%sport%");


        /*Not exists not like*/
        if(!$this->isExist('s tronic') )
            $cderListQuery->where('cder_name', 'not like', "%s tronic%");


        return $this->performLogic('discontinued', $cderListQuery);

    }

    public function findCderAndFormatStockArr()
    {
         $cderId  = $this->getCderId();

        if(!$cderId ) return null;

        $capder   =  CAPDer::with(['range', 'make', 'NVDTechnical','NVDPrices' , 'standardOptions'])->find($cderId);

        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();


        try{
            $price    = $priceService->getNewCarPrice($this->excelRow->offer, $this->excelRow->rrp);
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
        $stock->stock_ref       = trim($this->excelRow->order);
        $stock->chassis_no      = null;
        $stock->vin_number      = trim($this->excelRow->vin);
        $stock->car_type        = $this->isNew() ? 'new' : 'used';
        $stock->make            = $this->formatMake($capder->make->cman_name);
        $stock->model_id        = $capder->range->cran_code;
        $stock->model           = trim($capder->range->cran_name);
        $stock->derivative_id   = $capder->cder_ID;
        $stock->cap_code        = trim($capder->cder_capcode);
        $stock->derivative      = trim($capder->cder_name);
        $stock->supplier_spec   = trim($this->excelRow->model_description);
        $stock->body_type       = $capder->isConvertible() ? 'convertible' : CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description));
        $stock->fuel_type       = CAPDer::getFuelType(trim($capder->cder_fueltype));
        $stock->transmission    = CAPDer::getTransmission(trim($capder->cder_transmission));
        $stock->doors           = $capder->cder_doors;
        $stock->colour_spec     = strtolower(trim($this->excelRow->external_color_description));

        $colourService          = app(ColourService::class);
        $stock->colour          = $colourService->parse(strtolower(trim($this->excelRow->external_color_description)));

        $stock->standard_option   = $capder->standardOptionsString;
        $stock->additional_option = trim($this->excelRow->optionals_importer, ', ');
        $stock->additional_option_price = null;
        $stock->registration_no     = null;
        $stock->registration_date   = null;
        $stock->mpg             = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp             = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2             = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');
        $stock->current_mileage = 0;
        $stock->grade           = null;

        $stock->sale_location   = $this->mapSaleLocation( 'driftbridge_audi');
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $stock->purchase_price  = $price['purchase_price'] ?? null;

        if($this->addExtraOnCustomerPricePercent > 0) {
            $price['customer_price'] = $price['customer_price']  +
                    ($price['customer_price']  * $this->addExtraOnCustomerPricePercent / 100);
        }

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
        $stocks = [];
        \DB::connection('sqlsrv')->enableQueryLog();
        $notFounds = [];
        foreach ($excelRows as $excelRow)
        {

            $stocks[$excelRow->order] = $stock = (new static($excelRow))->findCderAndFormatStockArr();

            if($stock === null){
                $notFounds[] = $excelRow->toArray();
                LogHelper::save("INFO", new \Exception("Cder not found stock feed 2 excel row excel data:" .
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

    private function getNumberOfDoors()
    {
        if($this->isExist('saloon'))
            return 4;

        switch ($this->getCranName()) {
            case 'A5':
                return in_array($this->excelRow->doors, [2,3]) ? 2 : 5;

            case 'A6':
                return in_array($this->excelRow->doors, [7]) ? [4,5] : $this->excelRow->doors;

            case 'R8':
                return in_array($this->excelRow->doors, [2,3]) ? 2 : 5;

            case 'RS5':
                return in_array($this->excelRow->doors, [2,3]) ? 2 : 5;

            case 'TT':
                return in_array($this->excelRow->doors, [2,3]) ? 2 : 5;
                break;
        }

        return $this->excelRow->doors;
    }
}

