<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;

class GetFinanceOnlyRequest extends ApiRequest
{
    public function rules()
    {
        return [
            //Loan
            'loan.type' => 'required',
            'loan.finance_only' => 'required|boolean',
            'loan.amount' => 'required|numeric',
            'loan.term' => 'required|numeric|min:0',
        ];
    }
}
