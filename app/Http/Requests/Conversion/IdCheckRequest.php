<?php

namespace App\Http\Requests\Conversion;

use App\Http\Requests\ApiRequest;

class IdCheckRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'driving_license' => 'image',
            'selfie'          => 'image',
        ];
    }
}
