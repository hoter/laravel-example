<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class CreateAccountRequest extends ApiRequest
{
    public function rules()
    {
        return [
            // personal
            'personal.title'              => 'required',
            'personal.first_name'         => 'required',
            'personal.last_name'          => 'required',
            'personal.dob'                => 'required|date',
            'personal.mobile'             => 'required|min:10|max:15',
            // 'personal.gender'             => 'required',
            // 'personal.marital_status'     => 'nullable',

            // auth
            'personal.email'              => 'required|email|unique:customers,email,:email',
            'personal.password'           => 'required|min:8',

            // marketing
            'marketing'                   => 'array',
            'marketing.is_email_opt_in'   => 'required|boolean',
            'marketing.is_sms_opt_in'     => 'required|boolean',

            // address
            'addresses'                   => 'required|array',
            'addresses.*.type'            => 'required',
            'addresses.*.flat_no'         => 'required_without_all:addresses.*.building_name,addresses.*.house_no',
            'addresses.*.building_name'   => 'required_without_all:addresses.*.flat_no,addresses.*.house_no',
            'addresses.*.house_no'        => 'required_without_all:addresses.*.flat_no,addresses.*.building_name',
            'addresses.*.street'          => 'required',
            'addresses.*.district'        => 'nullable',
            'addresses.*.town'            => 'nullable',
            'addresses.*.city'            => 'nullable',
            'addresses.*.county'          => 'nullable',
            'addresses.*.postcode'        => 'required',
            'addresses.*.time_at_address' => 'required_if:addresses.*.type,current',
            'addresses.*.is_home_owner'   => 'nullable|boolean',
        ];
    }
}
