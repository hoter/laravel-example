<?php

namespace App\Services\Products;

use App\Helpers\NumberHelper;
use Illuminate\Contracts\Support\Arrayable;

define('NEW_CAR_PCP', 'new_car_pcp');
define('CAR_LOAN_HP', 'car_loan_hp');

/**
 * Product class
 *
 * @property $amount
 * @property $term
 * @property $apr
 * @property $monthly_payment
 * @property $total_repayable
 */
abstract class Product implements Arrayable
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties

    protected $amount = 0;

    protected $term = 0;

    protected $apr = 0;

    protected $monthly_payment = 0;

    protected $total_repayable = 0;

    protected $_guarded = ['monthly_payment', 'total_repayable'];

    public static $products = [
        NEW_CAR_PCP,
        CAR_LOAN_HP,
    ];

    public static $classes = [
        NEW_CAR_PCP => PCP::class,
        CAR_LOAN_HP => Loan::class,
    ];


    // -----------------------------------------------------------------------------------------------------------------
    // static

    /**
     * Calculates TOTAL mileage for a loan, based on the user's supplied ANNUAL miles and term
     *
     * Note that to match the database rows the return value is:
     *
     *  - rounded to 10s of 1000s of miles
     *  - clamped between 10 and 200
     *
     * @param int $annualMileage Annual mileage in 1000s of miles, i.e. 5, 10, 40
     * @param int $term          Loan term in months, i.e 12, 18, 48
     *
     * @return int               Total mileage for loan, i.e. 10, 50, 100
     */
    public static function getTotalMileage($annualMileage, $term)
    {
        $mileage = $annualMileage * ($term / 12);
        return NumberHelper::clampAndRound($mileage, 10, 200, 10);
    }

    /**
     * Round and clamp a given term to those expected by the system
     *
     * Note that to match the database columns the return value is:
     *
     *  - rounded to 6 month increments
     *  - clamped between 12 and 60
     *
     * @param number $term A given term
     *
     * @return int         The rounded term
     */
    public static function getTerm($term)
    {
        // FIXME update this for ticket CUS-58, as values will be non-linear
        return NumberHelper::clampAndRound($term, 12, 84, 6);
    }

    /**
     * Clamp interest rate to something sensible
     *
     * @param number $rate
     * @return number
     */
    public static function getRate($rate)
    {
        return NumberHelper::clamp($rate, 3, 20);
    }

    /**
     *
     *
     * @param string $type
     * @param array  ...$values
     * @return self
     */
    public static function create($type, ...$values)
    {
        $class = static::$classes[$type];
        return new $class(...$values);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation

    public function __construct($amount, $term, $apr)
    {
        $this->amount = $amount;
        $this->term   = $term;
        $this->apr    = $apr;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // accessors

    public function __get($name)
    {
        if ($name !== '_guarded')
        {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if ($name !== '_guarded' && !in_array($name, $this->_guarded))
        {
            $fn = 'set' . ucwords($name);
            method_exists($this, $fn)
                ? $this->$fn($value)
                : $this->$name = $value;
            $this->update();
        }
    }

    public function toArray()
    {
        return array_except(get_object_vars($this), '_guarded');
    }


    // -----------------------------------------------------------------------------------------------------------------
    // protected methods

    protected function update()
    {

    }
}