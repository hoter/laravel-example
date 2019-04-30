<?php

namespace App\Http\Controllers\Sketchpad;

use App\Services\Api\HarlibService;
use App\Services\Harlib\HarlibApi;

class HarlibController
{

    protected $service;
    protected $api;
    protected $data;

    protected $token = 'cd1decf3db409b5295067dce7caae7f3';

    public function __construct()
    {
        $this->api     = app(HarlibApi::class);
        $this->service = app(HarlibService::class);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // login
    // -----------------------------------------------------------------------------------------------------------------

    public function createAccount($email = 'test@test.com', $password = 'test')
    {
        // load data and update email
        $this->load('customer-create');
        array_set($this->data, 'customer.email', $email);
        array_set($this->data, 'customer.password', $password);

        // create account and save token
        $data = $this->post('customer/create');
        cache('harlib_token', $data->api_token); // not working

        // show data
        pr($data);
    }

    public function login($email = 'test@test.com', $password = 'test')
    {
        // login
        $data = $this
            ->load(compact('email', 'password'))
            ->post('customer/login');

        // store data
        cache('harlib_email', $email);
        cache('harlib_token', $data->api_token); // not working

        return $data;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // offers
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @field    select $product   options:PCP=new_car_pcp,HP=car_loan_hp
     */
    public function getDeposits($product = 'new_car_pcp', $loan_amount = 10000, $deposit_amount = 1000)
    {

        $this->data = compact('product', 'loan_amount', 'deposit_amount');
        return $this->get('finance/application/deposit', true);
    }

    public function getLikelyOffers($email = '')
    {
        return $this
            ->setEmail($email)
            ->load('likely-offers')
            ->post('finance/likely-offers', true);
    }

    public function createApplication()
    {
        return $this
            ->load('create-application')
            ->post('finance/application/create', true);
    }

    public function getOffers($application_id = '')
    {
        if ($application_id)
        {
            return $this
                ->load(compact('application_id'))
                ->get("finance/application/$application_id/offers", true);
        }
    }

    public function selectLender($application_id = '', $lender_id = 13)
    {
        if ($application_id)
        {
            return $this
                ->load(compact('application_id', 'lender_id'))
                ->post('select-lender', true);
        }
    }


    // -------------------------------------------------------------------------------------------------------------------
    // protected
    // -------------------------------------------------------------------------------------------------------------------

    /**
     * Loads file data, sets or clears data
     */
    protected function load($data = null)
    {
        if (is_string($data))
        {
            $path       = base_path("sketchpad/resources/$data.json");
            $this->data = json_decode(file_get_contents($path), JSON_OBJECT_AS_ARRAY);
        }
        else if (is_array($data))
        {
            $this->data = $data;
        }
        else
        {
            $this->data = [];
        }
        return $this;
    }

    /**
     * Sets a user
     */
    protected function setEmail($email)
    {
        if (array_has($this->data, 'customer'))
        {
            $this->data['customer']->email = $email;
        }
        return $this;
    }


    public function post($url, $authenticate = false)
    {
        return $this->call('post', $url, $authenticate);
    }

    public function get($url, $authenticate = false)
    {
        return $this->call('get', $url, $authenticate);
    }

    /**
     * Sends the request
     */
    protected function call($method, $url, $authenticate = false)
    {
        // data
        $data = $this->data;

        // login
        if ($authenticate)
        {
            // $token = cache('harlib_token');
            $data = $data + [
                "api_username" => "harlib",
                "api_password" => "c!U'KKXUjWfG5r9^!Hc]x(T%M=psVc",
                "api_token"    => $this->token,
            ];
        }

        // send
        try
        {
            return $this->api->$method($url, $data);
        }
        catch (\Exception $e)
        {
            dd($e);
        }
    }


}

