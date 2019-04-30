<?php namespace App\Services\Products;

/**
 * PCP Product class
 *
 * @property $gfv
 * @property $ratio
 * @property $theta
 */
class PCP extends Product
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties

    protected $gfv;

    protected $ratio;

    protected $theta;


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation

    /**
     * PCP constructor
     *
     * @param $amount
     * @param $term
     * @param $apr
     * @param $gfv
     */
    public function __construct($amount, $term, $apr, $gfv)
    {
        parent::__construct($amount, $term, $apr);
        $this->gfv = $gfv;
        $this->update();
        //array_push($this->_guarded, ['', '', '', '']);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // methods

    /**
     * Get the ratios needed for database queries, based on a fixed term and apr
     *
     * @param $term
     * @param $apr
     * @return array
     */
    public static function getRatios($term, $apr)
    {
        $ratio = $apr / 12 / 100;
        $theta = 1 - pow(1 + $ratio, -$term);
        return [$ratio, $theta] + compact('ratio', 'theta');
    }

    protected function update()
    {
        // variables
        $amount = $this->amount - $this->gfv;
        $term   = $this->term;
        $gfv    = $this->gfv;
        $apr    = $this->apr;

        // ratios - these two are consistent for any price & gfv, for a fixed term and apr
        list($ratio, $theta) = self::getRatios($term, $apr);

        // factors
        $ratioAmount = $ratio * $amount;
        $ratioGfv    = $ratio * $gfv;

        // result (calculate in db)
        $monthly_payment = ($ratioAmount / $theta) + $ratioGfv;
        $total_repayable = $monthly_payment * $term;

        // final
        $this->amount          = $amount;
        $this->ratio           = $ratio;
        $this->theta           = $theta;
        $this->monthly_payment = $monthly_payment;
        $this->total_repayable = $total_repayable;
    }

}