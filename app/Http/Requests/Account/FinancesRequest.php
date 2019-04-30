<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class FinancesRequest extends ApiRequest
{
    public function rules()
    {
        return [
            // income
            'gross_annual_income'           => 'required|integer',
            'net_monthly_income'                => 'required|integer',
            'total_monthly_expenses'               => 'required|integer',
            // mortgage
            'has_mortgage'            => 'required|integer',
            'time_with_mortgage'           => 'required_if:has_mortgage,1|numeric|nullable',
        ];
    }
}
