<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;

class FinanceOnlyEstimateRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'loan_term'         => 'required|numeric',
            'amount'            => 'required_without:monthly_budget|numeric',
            'monthly_budget' => 'required_without:amount|numeric',
            'self_rating'       => 'required',
        ];
    }
}
