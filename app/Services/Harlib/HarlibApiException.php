<?php namespace App\Services\Harlib;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Support\Jsonable;

/**
 * HarlibApiException class
 */
class HarlibApiException extends ApiException implements Jsonable
{

    /**
     * @var mixed
     */
    public $data;

    public function __construct($message, $data = null)
    {
        parent::__construct('Harlib API exception: ' . $message);
        $this->data = $data;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return $this->data;
    }
}