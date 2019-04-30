<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Round a number to the nearest multiple, i.e. 10, 6, 200, etc
     *
     * @param   number $value    The number to round
     * @param   int    $multiple The multiple to round nearest to
     * @return  int              The rounded value
     */
    public static function roundTo($value, $multiple)
    {
        return floor(round($value / $multiple) * $multiple);
    }

    /**
     * Clamp a number between two values
     *
     * @param   number $value The number to clamp
     * @param   number $min   The minimum clamp limit
     * @param   number $max   The maximum clamp limit
     * @return  number        The clamped value
     */
    public static function clamp($value, $min, $max)
    {
        $_min = min([$min, $max]);
        $_max = max([$min, $max]);
        if ($value < $_min)
        {
            $value = $_min;
        }
        if ($value > $_max)
        {
            $value = $_max;
        }
        return $value;
    }

    /**
     * Clamp and round a number in one operation
     *
     * @param   number $value    The number to round
     * @param   number $min      The minimum clamp limit
     * @param   number $max      The maximum clamp limit
     * @param   int    $multiple The multiple to round nearest to
     * @return number
     */
    public static function clampAndRound($value, $min, $max, $multiple)
    {
        return self::clamp(self::roundTo($value, $multiple), $min, $max);
    }

}
