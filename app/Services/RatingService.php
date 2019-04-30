<?php namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Services\Harlib\HarlibApi;
use App\Services\Products\Product;
use Cache;
use Illuminate\Validation\Rule;
use Validator;

/**
 * RatingService class
 */
class RatingService
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties

    public static $agencies = [
        'credit_expert',
        'clearscore',
        'noddle',
    ];

    public static $ratings = [
        'excellent',
        'good',
        'fair',
        'poor',
        'very_poor',
    ];


    // -----------------------------------------------------------------------------------------------------------------
    // methods
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Get and save a user's credit rating using an estimated rating, or an agency and score
     *
     * @param Customer $customer
     * @param number   $score  If an agency is supplied, then a score, if not a rating
     * @param string   $agency An optional credit agency
     * @return null|string
     */
    public function setRating(Customer $customer, $score, $agency = null)
    {
        $rating = $this->getRating($score, $agency);

        // save
        if ($rating && in_array($rating, static::$ratings))
        {
            $customer->rating = $rating;
            $customer->save();
        }

        // return
        return $rating;
    }

    /**
     * Get a credit rating using an estimated rating, or an agency and score
     *
     * @param      $score
     * @param null $agency
     * @return null|string
     */
    public function getRating($score, $agency = null)
    {
        $rating = null;

        // agency
        if (in_array($agency, static::$agencies))
        {
            $rating = $this->getRatingFromScore($score, $agency);
        }

        // estimated rating
        else if (is_string($score))
        {
            $rating = $score;
            if (!in_array($rating, static::$ratings))
            {
                return null;
            }
        }
        return $rating;
    }

    /**
     * Get a user's APR for a product, based on their rating
     *
     * @param string $product
     * @param string $rating
     * @return mixed
     */
    public function getAprForProduct($product, $rating)
    {
        $this->validateProduct($product);
        $this->validateRating($rating);

        $aprs = $this->getIndicativeAprs();

        return array_get($aprs, "$product.$rating", -1);
    }

    /**
     * Get a user's Flat rate for a product, based on their rating
     *
     * @param string $product
     * @param string $rating
     * @return mixed
     */
    public function getFlatRateForProduct($product, $rating)
    {
        $this->validateProduct($product);
        $this->validateRating($rating);

        $key    = 'indicative_aprs';
        $values = Cache::get($key);
        if (!$values)
        {
            $values = $this->fetchIndicativeAprs();
            Cache::put($key, $values, 60 * 6);
        }

        return array_get($values, "$product.$rating", -1);
    }

    /**
     * Get a credit rating for the user, based on a KNOWN agency credit score
     *
     * @param number $score  The user's credit score
     * @param string $agency The agency id, must be credit_expert, clearscore, or noddle
     * @return string
     * @throws ApiException
     */
    public function getRatingFromScore($score, $agency)
    {
        // validation
        $this->validateAgency($agency);
        if (!is_numeric($score))
        {
            return null;
        }

        // data
        $score    = (int) $score;
        $agencies = $this->getAgencyThresholds();

        // determine score
        $ratings = array_get($agencies, $agency);
        function getRating($ratings, $score)
        {
            foreach ($ratings as $rating => $scores)
            {
                if ($score >= $scores[0] && $score <= $scores[1])
                {
                    return $rating;
                }
            }
        }

        // get rating
        if ($ratings)
        {
            $rating = getRating($ratings, $score);
            if ($rating)
            {
                // FIXME when we have personal loans, we'll need to pass back a value per product
                return $rating;
            }
            // FIXME need to properly validate and pass back validation error for agency score ranges
            throw new ApiException("Invalid credit agency score: $score");
        }
        throw new ApiException("Invalid credit agency: $agency");

    }


    // -----------------------------------------------------------------------------------------------------------------
    // cache methods - these pull values from the cache
    // -----------------------------------------------------------------------------------------------------------------

    public function getIndicativeAprs($force = false)
    {
        $key    = 'indicative_aprs';
        $values = Cache::get($key);
        if ($force || !$values)
        {
            $values = $this->fetchIndicativeAprs();
            Cache::put($key, $values, 60 * 6);
        }

        $newVal = array();

        foreach($values as $key => $value) {
            $term = 36;
            $amount = 12201;
            $deposit = 200;
            $gfv = 0;
            foreach($value as $rating => $flatRate) {
                if(!empty($flatRate)) {
                    $aprCacheKey = $amount . $deposit . $term . $gfv . $flatRate . "0" . "FLAT_RATE_TO_APR";
                    $apr = Cache::get($aprCacheKey);
                    if(!$apr) {
                        $financeInput = [
                            'dummy' => false,
                            'operation' => 'FLAT_RATE_TO_APR',
                            'car_price' => $amount,
                            'deposit' => $deposit,
                            'term' => $term,
                            'gfv' => $gfv,
                            'apr' => 0,
                            'flat_rate' => $flatRate,
                            'interest_rate' => 0,
                        ];
                        $apr = self::getFinanceCalculation($financeInput);
                        Cache::put($aprCacheKey, $apr, 60 * 24);
                    }
                    $newVal[$key][$rating] = $apr;
                } else {
                    $newVal[$key][$rating] = null;
                }

            }
        }

        return $newVal;
    }

    public static function getFinanceCalculation($inputs){
        $api    = app(HarlibApi::class);
        return $api->post('financial-calculation', $inputs)->all();
    }

    public function getAgencyThresholds($force = false)
    {
        $key    = 'agency_ratings';
        $values = Cache::get($key);
        if ($force || !$values)
        {
            $values = $this->fetchAgencyThresholds();
            Cache::put($key, $values, 60 * 6);
        }
        return $values;
    }

    public function getAgencyLimits()
    {
        $thresholds = $this->getAgencyThresholds();
        $limits = array_map(function ($value) {
            return [$value['very_poor'][0], $value['excellent'][1]];
        }, $thresholds);
        return $limits;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // fetch methods - these get new values from the API
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Returns a list of product > bucket > aprs
     *
     * @return mixed
     */
    public function fetchIndicativeAprs()
    {
        return app(HarlibApi::class)
            ->get('settings/ratings', [
                'products' => Product::$products
            ])
            ->all();
    }


    public function fetchAgencyThresholds()
    {
        // FIXME need to look again at this when we add personal loans as the thresholds may be different for this product
        return app(HarlibApi::class)
            ->get('settings/agency-ratings', [
                'products' => Product::$products,
            ])
            ->get('new_car_pcp');
    }

    // -----------------------------------------------------------------------------------------------------------------
    // validation
    // -----------------------------------------------------------------------------------------------------------------

    public function validateProduct($name)
    {
        return $this->validate('product', $name, Product::$products);
    }

    public function validateRating($name)
    {
        return $this->validate('rating', $name, static::$ratings);
    }

    public function validateAgency($name)
    {
        return $this->validate('agency', $name, static::$agencies);
    }

    protected function validate($field, $value, $fields)
    {
        if (!in_array($value, $fields)) {
            $values = implode(', ', $fields);
            throw new \Exception("Invalid value `$value` for field `$field`. Valid values are `$values`");
        }
        return true;
    }


}
