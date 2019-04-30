<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;

class CheckDepositRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'product'        => 'required|string',
            'deposit_amount' => 'required|numeric',
            'loan_amount'    => 'required|numeric',
        ];
    }
}
