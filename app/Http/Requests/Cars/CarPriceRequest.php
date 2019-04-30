<?php

namespace App\Http\Requests\Cars;

use App\Http\Requests\ApiRequest;
use App\Models\Stock;

class CarPriceRequest extends ApiRequest
{
    public function rules()
    {

        /**
         * Max Deposit rule
         *    --Minimum Loan amount must be 5000
         *    --Deposit should not exceed half of customer price
         *    --Maximum deposit amount 10000
         */
        $stock = Stock::findOrFail(request()->id);
        $maxDeposit = min(10000,$stock->customer_price / 2, $stock->customer_price - 5000);

        return [
            'id'       => 'required|int',
            'deposit'  => "required|numeric|min:99|max:{$maxDeposit}",
            'term'     => 'required|numeric',
            'mileage'  => 'required|numeric',
            'car_type' => ['required', 'string', rules('fields.stocks.car_type')],
            'product'  => ['required', 'string', rules('fields.finance.product')],
            'rating'   => ['required', 'string', rules('fields.finance.credit_rating')],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'deposit.max' => 'Minimum loan advance is £5000, please adjust your deposit',
        ];
    }

}
