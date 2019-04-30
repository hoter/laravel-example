<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\ApiRequest;

class CreateApplicationRequest extends ApiRequest
{
    public function rules()
    {
        $rules = [
            // car
            'car_id'                          => 'required',

            // loan
            'loan'                            => 'required|array',
            'loan.product'                    => ['required', 'string', rules('fields.finance.product')],
            'loan.deposit'                    => 'required|numeric|min:99',
            'loan.term'                       => 'required|numeric',
            'loan.mileage'                    => 'required|numeric',

            // bank details
            'finances.account_no'             => 'required|size:8|regex:/^-?[0-9]\d*(\.\d+)?$/',
            'finances.sort_code'              => 'required|size:6',
            'finances.time_with_bank'         => 'required|numeric',

            // finances
            'finances.gross_annual_income'    => 'required|numeric',
            'finances.net_monthly_income'     => 'required|numeric',
            'finances.total_monthly_expenses' => 'required|numeric',
            'finances.has_mortgage'           => 'required|boolean',

            // security
            'security.question'               => 'required',
            'security.answer'                 => 'required',

            // terms
            'agreed_terms'                    => 'nullable',
            'is_terms_agreed'                 => 'required|boolean|in:1',
        ];

        // can't seem to get required_if working as expected, so need to do this manually
        if ($this->pluck('finances.has_mortgage') == 1)
        {
            $rules['finances.time_with_mortgage'] = 'required|numeric';
        };

        return $rules;
    }

}
