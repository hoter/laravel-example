<?php namespace App\Exceptions;

/**
 * ApiException class
 */
class ApiException extends \Exception
{

    public function __construct($message = 'Unspecified API Error', $httpStatus = 400)
    {
        if (!$httpStatus) {
            $httpStatus = 400;
        }
        parent::__construct($message, $httpStatus);
    }

}
