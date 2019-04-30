<?php namespace App\Services\Harlib;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * HarlibGeneralException class
 */
class HarlibConnectionException extends ApiException
{
    public function __construct($message, $status = 400)
    {
        parent::__construct($message, $status);
    }
}