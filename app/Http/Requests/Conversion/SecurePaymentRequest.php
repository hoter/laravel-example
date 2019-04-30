<?php

namespace App\Http\Requests\Conversion;

use App\Http\Requests\ApiRequest;

class SecurePaymentRequest extends ApiRequest
{
    public function rules()
    {
        // not sure if we can validate any of these, but these are the values expected
        return [
            // post vars from card issuer
            'PaRes'     => 'required',

            // get vars from client termUrl
            'orderCode' => 'nullable',
            'clientUrl' => 'nullable',
            'amount'    => 'nullable',
        ];
    }
}
