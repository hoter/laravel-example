<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;

class ProductRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'products'        => 'required',
            'is_terms_agreed' => 'required|boolean|in:1',
            'agreed_terms'    => 'nullable',
            'net_income'      => 'required',
            'loan'            => 'required|array',
            'loan.deposit'    => 'required|numeric',
            'loan.term'       => 'required|numeric',
            'loan.mileage'    => 'required|numeric',
            'monthly_budget'  => 'numeric',
        ];
    }
}
