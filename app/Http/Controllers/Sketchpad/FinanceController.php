<?php

namespace App\Http\Controllers\Sketchpad;

use App\Models\Customer;
use App\Models\Stock;
use App\Repositories\StockRepo;
use App\Services\Api\HarlibService;
use App\Services\Harlib\HarlibApi;
use App\Services\RatingService;

class FinanceController
{

    public function getHarlibData($id = 1, $term = 12, $mileage = 10)
    {
        return Stock::find(1)->getHarlibData($term, $mileage);
    }

    public function getEmployments($id = 1)
    {
        return Customer::find($id)->getEmployment();
    }

}

