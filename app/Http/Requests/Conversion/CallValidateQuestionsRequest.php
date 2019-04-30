<?php

namespace App\Http\Requests\Conversion;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class CallValidateQuestionsRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'purpose' => ['required', Rule::in(['identification', 'delivery'])],
        ];
    }
}
