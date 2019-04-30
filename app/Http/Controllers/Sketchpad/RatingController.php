<?php

namespace App\Http\Controllers\Sketchpad;

use App\Models\Customer;
use App\Services\Harlib\HarlibApi;
use App\Services\RatingService;
use GuzzleHttp\Client;

class RatingController
{

    /**
     * Set the user rating. Pass either an estimated rating, i.e. *excellent* or a numeric score and agency
     *
     * @group Front office
     *
     * @field select $agency options:none,credit_expert,clearscore,noddle
     * @param int           $id
     * @param string|number $rating
     * @param string        $agency
     * @return null|string
     */
    public function setUserRating($id = 1, $rating = '', $agency = '')
    {
        $user = Customer::find($id);
        $user = Customer::current();
        $rating = app(RatingService::class)->setRating($user, $rating, $agency);
        dd($rating, $user, session()->all());
    }


    /**
     * @group API
     *
     * @field select $agency options:credit_expert,clearscore,noddle
     * @return mixed
     */
    public function getRatingFromScore($agency = 'experian', $score = 0)
    {
        return app(RatingService::class)->getRatingFromScore($score, $agency);
    }

    /**
     * @field select $rating options:,excellent,very_good,good,fair,poor,very_poor
     * @field select $product options:new_car_pcp,car_loan_hp
     * @param string $product
     * @param string $rating
     * @return mixed
     */
    public function getAprForProduct($product = 'new_car_pcp', $rating = 'good')
    {
        return app(RatingService::class)->getAprForProduct($product, $rating);
    }

    public function getIndicativeAprs()
    {
        return app(RatingService::class)->getIndicativeAprs();
    }

    public function getAgencyThresholds()
    {
        return app(RatingService::class)->getAgencyThresholds();
    }

    public function getAgencyLimits()
    {
        return app(RatingService::class)->getAgencyLimits();
    }

    /**
     * @group Core methods
     */
    public function fetchAgencyThresholds()
    {
        return app(RatingService::class)->fetchAgencyThresholds();
    }

    public function fetchIndicativeAprs()
    {
        return app(RatingService::class)->fetchIndicativeAprs();
    }

}

