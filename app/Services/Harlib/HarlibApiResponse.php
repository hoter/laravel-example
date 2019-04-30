<?php

namespace App\Services\Harlib;

use App\Helpers\JsonHelper;

/**
 * HarlibApiResponse
 *
 * Custom class to manage Harlib API response data
 *
 * @see JsonHelper for usage and methods
 *
 * Note:
 *
 * - class serializes automatically when returned from controllers
 * - original data is stored in array format
 */
class HarlibApiResponse extends JsonHelper
{
    public function __construct($data)
    {
        parent::__construct($data);
    }
}