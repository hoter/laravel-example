<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;
use App\Services\RatingService;
use Illuminate\Validation\Rule;

class RatingRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'rating' => ['nullable', 'number', 'string', Rule::in(RatingService::$ratings)],
            'agency' => ['nullable', 'string', Rule::in(RatingService::$agencies)],
            'score'  => 'nullable|numeric',
        ];
    }
}
