<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class AddressDetailsRequest extends ApiRequest
{
    public function rules()
    {
        return [
            // addresses
            'addresses'                   => 'required|array',

            // address
            'addresses.*.type'            => 'required|string',
            'addresses.*.flat_no'         => 'nullable|string',
            'addresses.*.house_no'        => 'nullable|string',
            'addresses.*.building_name'   => 'nullable|string',
            'addresses.*.street'          => 'required|string',
            'addresses.*.town'            => 'nullable|string',
            'addresses.*.district'        => 'nullable|string',
            'addresses.*.city'            => 'nullable|string',
            'addresses.*.county'          => 'nullable|string',
            'addresses.*.postcode'        => 'required|string',

            // optional for now?
            'addresses.*.time_at_address' => 'required_if:addresses.*.type,current',
            'addresses.*.is_home_owner'   => 'nullable|boolean',
        ];
    }
}
