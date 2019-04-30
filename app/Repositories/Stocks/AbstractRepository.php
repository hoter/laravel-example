<?php
namespace App\Repositories\Stocks;

use App\Models\Cap\NVDTechnical;
use Carbon\Carbon;
use App\Models\Cap\CAPDer;
use Illuminate\Database\Eloquent\Collection;

abstract class AbstractRepository
{
    protected  $sheetName;
    protected  $excelRow;

    protected  $addRegistrationFeeToPrice = false;

    protected $addRoadTax = false;
    /**
     * Having this words means transmission automatic otherwise manual
     * @var array
     */
    protected  $autoTransmissionWords = [
        'MTA',
        'DDCT',
        'DCT',
        'Automatic',
        'Auto',
        'Dualogic',
        'DSG',
        'ASG',
        'CVT'
    ];


    /**
     * AbstractRepository constructor.
     * @param $sheetName
     * @param $excelRow
     */
    public function __construct($excelRow, $sheetName = '')
    {
        $this->sheetName  = $sheetName;
        $this->excelRow   = $excelRow;
    }


    public abstract  function findCderAndFormatStockArr();
    public abstract static function importStocks($filePath, $supplierSource);


    protected function getBestMatchableString(){
        //Default implementation
    }

    protected function getTechnicalDataAsString($capId) {
        $data = CAPDer::with(['range', 'make', 'NVDTechnical','NVDPrices' , 'standardOptions'])->find($capId);
        return $data->standardOptionsString;
    }



    /**
     * Get matching score between two string
     *
     * @param $string1
     * @param $string2
     * @return int
     */
    private function getWordMatchingScore($string1, $string2)
    {

        $score = 0;

        foreach(explode(' ', $string1) as $word1 )
        {
            if($word1)
                if(stripos($string2, $word1) !== false)
                    $score++;
                else
                    $score--;
        }

        foreach(explode(' ', $string2)  as $word2 )
        {
            if($word2)
                if(stripos($string1, $word2) !== false)
                    $score++;
                else
                    $score--;
        }

        return $score;
    }


    /**
     * @param $cderList
     * @return mixed
     */
    public function getBestMatchCder($cderList)
    {
        $bestMatch = $cderList->first();

        $maxScore = $this->getWordMatchingScore($bestMatch->cder_name, $this->getBestMatchableString());

        foreach($cderList as $cder)
        {
            $score = $this->getWordMatchingScore($cder->cder_name, $this->getBestMatchableString());
            if($maxScore < $score )
            {
                $bestMatch = $cder;
                $maxScore = $score;
            }
        }
        return $bestMatch;
    }


    /**
     * Get technical data
     *
     * @param Collection $nvdTechnicals
     * @param $techCode
     * @return null
     */
    protected function getTechnicalData(Collection $nvdTechnicals, $techCode)
    {
        $technicalData = $nvdTechnicals->where('TECH_TechCode', NVDTechnical::$technicalDictionary[$techCode])->first();

        return $technicalData ? $technicalData->TECH_Value_Float : null;
    }

    /**
     * Calculate value using percentage
     *
     * @param $number
     * @param $percentage
     * @return float|int
     */
    protected function calcPercentageAmount($number, $percentage )
    {
        return ($number * $percentage) / 100;
    }

    /**
     * First convert $make to lowercase
     * then remove all unwanted character
     *
     * @throws \Exception when makes not found in config("constants.stocks.makes")
     *
     * @param $make string
     * @return null | $formattedMake
     */
    protected function formatMake($make)
    {
        return self::sFormatMake($make);
    }

    public static function sFormatMake($make)
    {
        $makeKey = preg_replace("/[^a-zA-Z0-9]/i", "", snake_case($make));

        $makeKey =  preg_replace("/_|-/", "", snake_case($makeKey));

        if(array_search($makeKey, config("constants.stocks.makes")) !== false)
            return $makeKey;

        throw new \Exception("Make {$makeKey} not found in config(\"constants.stocks.makes\")");
    }

    /**
     * Map sale location
     *  If sale location not found then default sale location will be used that
     *  already configured in  config('constants.feeds.{$importedTab}.sale_location.default')
     *
     * @param $importedTab  - Identifier of which excel file
     * @param $saleLocation - From excel file
     * @return null | $mappedSaleLocation | Default Sale Location for $importedTab
     */
    protected function mapSaleLocation($importedTab, $saleLocation = '')
    {
        $saleLocationKey = str_replace('_','', snake_case(trim($saleLocation)));
        $mappedSaleLocation = config("constants.feeds.{$importedTab}.sale_location.mapping.{$saleLocationKey}");

        if($mappedSaleLocation)
            return $mappedSaleLocation;

        return config("constants.feeds.{$importedTab}.sale_location.default");
    }

    /**
     *  Check is price are valid to business decision
     *
     * @param array $prices
     * @return bool
     */
    public function isValidPrice(array $prices)
    {
        foreach($prices as $price)
        {
            if($price < 0)
            {
                return false;
            }
        }

        if($prices['purchase_price'] < config('constants.pricing.min_purchase_price') ||
            $prices['customer_price'] > config('constants.pricing.max_customer_price') ||
            $prices['customer_price'] < config('constants.pricing.min_customer_price'))
        {
            return false;
        }

        return true;
    }

    public function isValidUsedCarMileage($mileage){

        return empty($mileage) ? false : $mileage <= config('constants.stocks.max_used_car_mileage');
    }


    public function isValidUsedCarAge($registrationYear)
    {
        return $registrationYear >= date('Y') - config('constants.stocks.max_used_car_age');

    }
    public function updateCustomerPrice($customerPrice){

        if($this->addRegistrationFeeToPrice){
            $customerPrice += config('constants.pricing.first_registration_fee');
        }
        return$customerPrice;
    }
}