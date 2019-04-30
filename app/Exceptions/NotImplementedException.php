<?php

namespace App\Exceptions;

use Mockery\Exception;

class NotImplementedException extends Exception
{
    public function __construct($message = 'The method is not implemented')
    {
        $this->message = $message;
        $this->code = 501;
    }
}