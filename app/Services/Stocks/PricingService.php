<?php
namespace App\Services\Stocks;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;
use Exception;

class PricingService
{
    /**
     * @param $purchasePrice
     * @param $retailPrice
     * @return array
     */
    public function getUsedCarBCAPrice($purchasePrice, $retailPrice)
    {
        if(floatval($purchasePrice) <= 0 || floatval($retailPrice) <= 0){
            throw  new InvalidArgumentException('Arguments must not be zero or negative');
        }
        $result = [];
        $result['customer_discount_amount'] = config('constants.pricing.used_car.bca.discount');
        $result['customer_price'] = $retailPrice - $result['customer_discount_amount'];
        if($result['customer_price'] <= $purchasePrice + config('constants.pricing.used_car.bca.min_metal_profit')){
            throw new UnexpectedValueException('Minimum profit does not acquire');
        }
        $result['purchase_discount_amount'] = $retailPrice - $purchasePrice;
        $result['purchase_discount_percentage'] = ($result['purchase_discount_amount'] * 100) / $retailPrice;
        $result['customer_discount_percentage'] = ($result['customer_discount_amount'] * 100) / $retailPrice;
        $result['current_price'] = $retailPrice;
        $result['purchase_price'] = $purchasePrice;


        return $result;
    }

    /**
     * @param $purchasePrice
     * @return array
     */
    public function getUsedCarNonBCAPrice($purchasePrice)
    {
        if(floatval($purchasePrice) <= 0){
            throw  new InvalidArgumentException('Argument must not be zero or negative');
        }
        $result = [];
        $result['customer_discount_amount'] = 0;
        $result['customer_price'] = $purchasePrice + config('constants.pricing.used_car.non_bca.metal_profit');
        $result['purchase_discount_amount'] = 0;
        $result['customer_discount_percentage'] = 0;
        $result['purchase_discount_percentage'] = 0;
        $result['current_price'] = 0;
        $result['purchase_price'] = $purchasePrice;

        return $result;
    }

    /**
     * @param $base
     * @param $others
     * @return float|int
     */
    public function calcPercentage($base, $others)
    {
        return   ($others * 100) / $base;
    }


    /**
     * @param $base
     * @param $percentage
     * @return float|int
     */
    public function calcAmount($base, $percentage)
    {
        return $base * $percentage / 100;
    }

    /**
     * @param $purchasePrice
     * @param $retailPrice
     * @return array
     */
    public function getNewCarPrice($purchasePrice, $retailPrice)
    {
        if(floatval($purchasePrice) <= 0 || floatval($retailPrice) <= 0){
            throw  new InvalidArgumentException('Arguments must not be zero or negative');
        }
        $rules = config('constants.pricing.new_car');

        $result = [];
        $result['current_price']  = $retailPrice;
        $result['purchase_price'] = $purchasePrice;
        $result['purchase_discount_amount'] = $retailPrice - $purchasePrice;
        $result['purchase_discount_percentage'] = $this->calcPercentage($retailPrice, $result['purchase_discount_amount']);

        $maxDistributedPercentage  = max(array_column($rules['profit_distribution_rules'],'max_purchase_discount_percentage')); // max percentage when metal profit rule will be activate

        if( $result['purchase_discount_percentage'] >= $maxDistributedPercentage) //calculate using metal profit rule rules
        {
            foreach($rules['metal_profit_rules'] as $metalProfitRule)
            {
                if($metalProfitRule['retail_price_less_than'] > $retailPrice)
                {
                    $result['customer_price']               = $result['purchase_price'] + $metalProfitRule['metal_profit'];
                    $result['customer_discount_amount']     = $retailPrice - $result['customer_price'];
                    $result['customer_discount_percentage'] = $this->calcPercentage($retailPrice, $result['customer_discount_amount']);;

                    return $result;
                }
            }
        } //end if;

        //Calculate using profit share rule
        foreach($rules['profit_distribution_rules'] as $distributionRules)
        {
            if($distributionRules['max_purchase_discount_percentage'] >= $result['purchase_discount_percentage'])
            {
                $profit = $this->calcAmount($result['purchase_discount_amount'], $distributionRules['company_percentage']);
                $result['customer_price']               = $result['purchase_price'] + $profit;
                $result['customer_discount_amount']     = $retailPrice - $result['customer_price'];
                $result['customer_discount_percentage'] = $this->calcPercentage($retailPrice, $result['customer_discount_amount']);

                return $result;
            }
        }

        throw new Exception('Pricing Service Calculation Exceptions');
    }

    protected function compare($operand1,  $operand2, $operator){
        $second_operand = strpos($operand2, '%') !== false ? (integer)$operand2 : $operand2;
        switch ($operator){
            case '==':
                return $operand1 == $operand2;
            case '<=':
                return $operand1 <= $operand2;
            case '>=':
                return $operand1 >= $operand2;
            case '<':
                return $operand1 < $operand2;
            case '>':
                return $operand1 > $second_operand;
            default:
                return false;
        }
    }

}