<?php namespace App\Services\Products;

/**
 * PCP Product class
 *
 * @property $ratio
 */
class Loan extends Product
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties

    protected $ratio;


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation

    /**
     * Loan constructor
     *
     * @param $amount
     * @param $term
     * @param $apr
     */
    public function __construct($amount, $term, $apr)
    {
        parent::__construct($amount, $term, $apr);
        $this->update();
    }


    // -----------------------------------------------------------------------------------------------------------------
    // methods

    /**
     * Gets the ratio of a repayment to original loan value, for a given rate and term
     *
     * @param int    $term The loan term in months
     * @param number $apr
     * @return float
     *
     */
    public static function getRatio($term, $apr)
    {
        return (new self(1000000, $term, $apr))->ratio;
    }

    protected function update()
    {
        // Monthly interest rate = ((APR / 100) + 1) ^ (1 / 12) - 1
        $monthlyRate = pow(($this->apr / 100) + 1, 1 / 12) - 1; // from money.co.uk

        // Monthly payment = Loan amount * ((1 + Monthly interest rate) ^ (Loan term * Monthly interest rate)) / ((1 + Monthly interest rate) ^ (Loan term) -1)
        $pmt                   = $monthlyRate + ($monthlyRate / (pow(1 + $monthlyRate, $this->term) - 1));
        $this->monthly_payment = $this->amount * $pmt;
        $this->total_repayable = $this->monthly_payment * $this->term;

        // ratio of total repayable to loan amount
        $this->ratio = $this->total_repayable / $this->amount;
    }

}
