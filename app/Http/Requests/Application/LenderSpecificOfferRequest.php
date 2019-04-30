<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\ApiRequest;

class LenderSpecificOfferRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'application_id' => 'required|numeric',
            'lender_id'      => 'required|numeric',
        ];
    }
}
