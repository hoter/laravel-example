<?php namespace App\Services;

/**
 * Logger class
 */
class Logger
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function log($type, $text)
    {
        $datetime = date("d-m-Y H:i:s");
        $text = "$datetime, $type: $text \r\n\r\n";
        error_log($text, 3, $this->path);
    }

}