<?php

namespace App\Http\Requests\Cars;

use App\Http\Requests\ApiRequest;

class CarViewRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'loan'         => 'array',
            'loan.deposit' => 'numeric|min:99',
            'loan.term'    => 'numeric|min:6|max:84',
            'loan.mileage' => 'numeric|min:5',
            'loan.product' => ['required', 'string', rules('fields.finance.product')],
            'rating'       => ['required', 'string', rules('fields.finance.credit_rating')],
        ];
    }

}
