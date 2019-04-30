<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;

class LikelyOffersRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'car_id'       => 'required',
            'loan'         => 'required|array',
            'loan.term'    => 'required|numeric',
            'loan.deposit' => 'required|numeric',
            'loan.mileage' => 'required|numeric',
        ];
    }
}
