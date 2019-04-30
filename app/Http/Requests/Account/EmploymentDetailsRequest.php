<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class EmploymentDetailsRequest extends ApiRequest
{
    public function rules()
    {
        return [
            // personal
            'job_title'           => 'required|string',
            'type'                => 'required_if:status,employed,self_employed,temporary_employment,working_student',
            'basis'               => 'required_if:status,employed,self_employed,temporary_employment,working_student',
            // company
            'employer'            => 'required|string',
            'street_no'           => 'required',
            'street'              => 'required|string',
            'phone'               => 'nullable|string|min:10|max:15',
            'postcode'            => 'required|string',

            // time
            'time_at_employment'  => 'required_if:status,employed,self_employed,temporary_employment|numeric',
        ];
    }
}
