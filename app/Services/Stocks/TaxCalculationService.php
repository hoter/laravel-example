<?php

namespace App\Services\Stocks;

use Carbon\Carbon;
use Exception;

class TaxCalculationService
{
    /**
     * This property hold carbon object any car registered after this will
     * be considered as new car otherwise old car
     */
    protected $newCarRegistrationDate;

    /**
     * This property hold tax configuration.
     */
    protected $taxConfigurations;


    /**
     * TaxCalculationService constructor.
     */
    public function __construct()
    {
        $this->taxConfigurations = config('constants.car_tax', []);
        $this->newCarRegistrationDate = Carbon::createFromFormat('d-m-Y', $this->taxConfigurations['new_rule_date']);
    }


    /**
     * Get tax amount of a car
     *
     * @param $co2
     * @param $fuelType
     * @param null $registrationInfo
     * @internal param $ Carbon|string|null
     *        -$registrationInfo string will be considered as registration number
     *        -$registrationInfo Carbon will be considered as registration date
     * @param bool $isUsed
     *@return int
     */
    public function getTaxAmount($co2, $fuelType, $registrationInfo = null, $isUsed = false)
    {
        if($registrationInfo == null || $this->canApplyNewRule($registrationInfo))
        {
            //new car
            return $this->getNewRuleTaxAmount($co2, $fuelType, $isUsed);
        }
        //old car
        return $this->getPreviousRuleTaxAmount($co2, $fuelType);
    }


    /**
     * Get is it new or not form registration date
     *
     * @param null $registrationInfo
     * @return bool
     * @throws Exception
     */
    private function canApplyNewRule($registrationInfo = null)
    {
        if($registrationInfo instanceof Carbon)
        {
            return $registrationInfo >= $this->newCarRegistrationDate;
        }

        //calculating using registration number
        if(!preg_match('/\d+/i', $this->excelRow->registration_number, $matches) )
        {
            throw new \Exception('Cannot find when car is registered in April to Aug or September to March');
        }

        $numberFromReg = $matches[0];

        if( ($numberFromReg < 50 && $numberFromReg >= 17) || $numberFromReg > 67 ) { //67 means 50 + 17
            return true;
        }

        return false;
    }


    /**
     * Get old car tax amount
     *
     * @param $co2
     * @param $fuelType
     * @return
     * @throws Exception
     */
    public function  getPreviousRuleTaxAmount($co2, $fuelType)
    {
        foreach ($this->taxConfigurations['old_rule'] as $minCO2 => $tax) {
            if ($co2 >= $minCO2) {
                $fuelType = in_array($fuelType, ['petrol', 'diesel']) ? $fuelType : 'alternative';
                return  $tax[$fuelType];
            }
        }//end foreach

        throw new Exception('Unwanted value : ', ['co2' => $co2, 'fuel_type' => $fuelType]);
    }


    /**
     * Get new car tax amount
     *
     * @param $co2
     * @param $fuelType
     * @param bool $isUsed
     * @return
     * @throws Exception
     */
    public function  getNewRuleTaxAmount($co2, $fuelType, $isUsed = false)
    {
        if($isUsed)
        {
            $fuelType = in_array($fuelType, ['petrol', 'diesel', 'electric']) ? $fuelType : 'alternative';
            return $this->taxConfigurations['new_rule']['second_tax_payment'][$fuelType];
        }

        foreach ($this->taxConfigurations['new_rule']['first_tax_payment'] as $minCO2 => $tax) {
            if ($co2 >= $minCO2) {
                $fuelType = in_array($fuelType, ['petrol', 'diesel']) ? $fuelType : 'alternative';
                return  $tax[$fuelType];
            }
        }//end foreach

        throw new Exception('Unwanted value : ', ['co2' => $co2, 'fuel_type' => $fuelType]);
    }
}