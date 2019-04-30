<?php

namespace App\Http\Controllers\Api\v2\Account;

use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Account\EmploymentDetailsRequest;
use App\Services\Api\ProfileService;
use Illuminate\Http\JsonResponse;

/**
 * Resource controller for employment
 */
class EmploymentController extends ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store($request)
    {

    }

    public function employment(EmploymentDetailsRequest $request, ProfileService $service)
    {
        $response = $service->customerEmploymentDetails($request);
        return $this->send($response);
    }
}
