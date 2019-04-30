<?php

namespace App\Http\Requests\Cars;

use App\Http\Requests\ApiRequest;

class CarSearchRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'car'              => 'required|array',
            'car.car_type'     => 'nullable|string|in:new,used',
            'car.body_type'    => ['nullable', 'string', rules('fields.stocks.body_type')],
            'car.doors'        => ['nullable', 'numeric', rules('fields.stocks.doors')],
            'car.fuel'         => 'nullable|string',
            'car.transmission' => 'nullable|string',
            'sort'             => 'nullable|array',
            'sort.key'         => 'nullable|string',
            'sort.order'       => 'nullable|string|in:ASC,DESC',
        ];
    }

}
