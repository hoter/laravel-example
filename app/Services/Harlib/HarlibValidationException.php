<?php namespace App\Services\Harlib;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * HarlibApiException class
 */
class HarlibValidationException extends ApiException implements Arrayable
{

    /**
     * @var array
     */
    public $data;

    public function __construct($message, array $data)
    {
        parent::__construct('Harlib Validation exception: ' . $message);
        $this->data = $data;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->data,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}