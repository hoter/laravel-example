<?php

namespace App\Http\Requests\Conversion;

use App\Http\Requests\ApiRequest;

class PaymentRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'token'            => 'required',
            'amount'           => 'required',
            'orderDescription' => 'required',
        ];
    }
}
