<?php

namespace Helpers;

/**
 * ArrayHelper class
 */
class ArrayHelper
{
    /**
     * Recursively flatten multiple values into a single array
     *
     * - Arrays are flattened recursively
     *   - key => values are added to the base array as key => value
     *   - values are appended to the base array
     * - Primitive types will be appended to the base array
     *
     * This allows for subsequent functions to process keys or values as needed
     *
     * @usage
     *
     *  $arr = ArrayHelper::flatten([['a' => 1, 'b' => 2], 'c' => 3], 'foo', 'bar', 4, 5, 6)
     *
     *      [
     *         "0": "foo",
     *         "1": "bar",
     *         "2": 4,
     *         "3": 5,
     *         "4": 6,
     *         "a": 1,
     *         "b": 2,
     *         "c": 3
     *      ]
     *
     * @param $values
     * @return mixed
     */
    public static function flatten(...$values)
    {
        $output = [];
        array_walk_recursive($values, function ($value, $key) use (&$output) {
            if (is_numeric($key))
            {
                array_push($output, $value);
            }

            else
            {
                $output[$key] = $value;
            }
        });
        return $output;
    }

    public static function flattenKeys($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result = $result + static::flattenKeys($value, $prefix . $key . '.');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    /**
     * Convert a deeply-nested array to a query string
     *
     * foo.bar=1&foo.baz=2&bar.foo=3
     *
     * Useful for creating keys to cache queries
     *
     * @param array $values     The array of values to flatten
     * @param bool  $skipEmpty  An optional flag to skip empty values
     * @param bool  $asArray    An optional flag to return the results as an array, rather than a string
     * @return array|string     The flattened value
     */
    public static function toQuery($values, $skipEmpty = true, $asArray = false)
    {
        $data = ArrayHelper::flattenKeys($values);
        ksort($data);
        if ($skipEmpty)
        {
            $data = array_filter($data, function ($e) {
                return !empty($e);
            });
        }
        return $asArray
            ? $data
            : http_build_query($data);
    }

}