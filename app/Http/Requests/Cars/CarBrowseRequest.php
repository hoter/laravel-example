<?php

namespace App\Http\Requests\Cars;

class CarBrowseRequest extends CarSearchRequest
{
    public function rules()
    {
        return array_merge(parent::rules(), [
            'loan'         => 'required|array',
            'loan.deposit' => 'required|numeric|min:99',
            'loan.term'    => 'required|numeric',
            'loan.mileage' => 'required|numeric',
            'loan.product' => ['required', 'string', rules('fields.finance.product')],
            'rating'       => ['required', rules('fields.finance.credit_rating')],
            'budget'       => 'nullable|array',
            'budget.min'   => 'nullable|numeric',
            'budget.max'   => 'nullable|numeric',
            'page'         => 'nullable|numeric',
            'per_page'     => 'nullable|numeric',
            'car.car_type'     => ['nullable', rules('fields.stocks.car_type')],
            'car.body_type'     => ['nullable', rules('fields.stocks.body_type')],
            'car.fuel_type'     => ['nullable', rules('fields.stocks.fuel_type')],
            'car.transmission'     => ['nullable', rules('fields.stocks.transmission')],
            'car.doors'     => ['nullable', rules('fields.stocks.doors')],
            'car.age'       => 'nullable',
            'car.mileage'   => 'nullable',
            'car.colour'    => ['nullable', rules('constants.colours')],
        ]);
    }

}
