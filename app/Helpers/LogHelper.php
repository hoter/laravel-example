<?php

namespace App\Helpers;

use App\Models\Setting;
use Exception;

/**
 * Legacy error-logging class
 */
class LogHelper
{

    /**
     * Save log to file
     *
     * @param string    $type An error type / path to identify the error type
     * @param Exception $e
     *
     * @return bool
     * @internal   param bool $logFilePath
     * @deprecated use the Logger class instead
     */
    public static function save($type, Exception $e)
    {
        try {
            $message = $type . ' ' . self::getExceptionStr($e);
            $datetime = date("d-m-Y H:i:s");
            $logfile = Setting::getValue('log_file_path');
            error_log($datetime . ", " . $message . "\r\n\r\n", 3, $logfile);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public static function getExceptionStr(Exception $e)
    {
        return "File: " . $e->getFile() . " Line: " . $e->getLine() . " Message: " . $e->getMessage() . "Type" . get_class($e);
    }
}