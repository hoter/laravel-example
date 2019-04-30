<?php

namespace App\Http\Requests\Conversion;

use App\Http\Requests\ApiRequest;

class CallValidateAnswersRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'application_id'     => 'required|integer',
            'purpose'            => 'required|string',
            'answers'            => 'required|array',
            'answers.*.question' => 'required|string',
            'answers.*.answer'   => 'required|string',
        ];
    }
}
