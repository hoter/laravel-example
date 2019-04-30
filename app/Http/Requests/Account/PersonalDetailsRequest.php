<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class PersonalDetailsRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'title'           => 'nullable',
            'first_name'      => 'required',
            'last_name'       => 'required',
            'email'           => 'required',
            'mobile'          => 'required|min:10|max:15',
            'gender'          => 'required',
            'dob'             => 'required',
            'marital_status'  => 'required',
            'driving_licence' => 'required',
        ];
    }
}
