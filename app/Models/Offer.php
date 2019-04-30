<?php namespace App\Models;

/**
 * Offer class
 *
 * Yet to be implemented!
 */
class Offer
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation


    // -----------------------------------------------------------------------------------------------------------------
    // methods

    public function toArray()
    {
        $keys = [
            'lender_id',
            'lender_name',
            'lender_logo',
            'apr',
            'cost_after_default',
            'early_termination_fee',
            'status',
            'gfv',
            'monthly_payment',
            'min_deposit_requirements',
            'total_cost',
            'gfv_flex',
            'total_payable',
            'saved_offer_expiry_days',
        ];
        return array_only((array)$this, $keys);
    }


}