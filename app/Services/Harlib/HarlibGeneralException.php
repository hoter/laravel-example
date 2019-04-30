<?php namespace App\Services\Harlib;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * HarlibGeneralException class
 */
class HarlibGeneralException extends ApiException
{

    /**
     * @var array
     */
    public $html;

    public function __construct($html, $status = 500)
    {
        parent::__construct('Harlib general exception', $status);
        $this->html = $html;
    }
}