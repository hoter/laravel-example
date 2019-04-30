<?php namespace App\Helpers;

use Helpers\ArrayHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * SqlHelper class
 *
 * Various helper methods to manually create SQL strings.
 *
 * Whilst Eloquent and the query builder are useful to build up flexible queries,
 * they can result in extremely dense PHP, especially if you have a lot of DB::raw()
 * statements.
 *
 * In these cases, it's just easier to write the raw SQL out by hand, and this class
 * helps to do that.
 */
class SqlHelper
{

    /**
     * Converts variable inputs to an array of select fields to a SELECT string
     *
     * @usage
     *
     * SqlHelper::select('foo', ['bar' => 'barry', 'baz AS bazzy'])
     *
     *      foo,
     *      bar AS barry,
     *      baz AS bazzy
     *
     * @param string[] $values An array of SELECT fields
     * @return string           An SQL string
     */
    public static function select(...$values)
    {
        $values = ArrayHelper::flatten($values);
        array_walk($values, function ($value, $key) use (&$values) {
            if (!is_numeric($key))
            {
                $values[$key] = "$key AS $value";
            };
        });
        return '    ' . implode(",\n    ", $values);
    }

    /**
     * Convert variable WHERE clauses into a WHERE > AND string
     *
     * @usage
     *
     * SqlHelper::where('foo = 1', ['bar' => 2, 'baz' => 'LIKE "%test%"'])
     *
     *      (foo = 1
     *      AND bar = '2'
     *      AND baz = 'LIKE "%test%"')
     *
     * @param string[] $values An array of where clauses
     * @param string   $op     A where operator, defaults to AND
     * @return string           An SQL string
     */
    public static function where(...$values)
    {
        $values = self::filters($values);
        return '    (' . implode("\n    AND ", $values) . ')';
    }

    /**
     * Convert an array of [key => ..., order => ...] to ORDER BY `key` ASC/DESC
     *
     * @usage
     *
     * SqlHelper::orderBy(['key' => 'foo', 'order' => 1])
     *
     *      ORDER BY `foo` ASC
     *
     * @param $values
     * @return string
     */
    public static function orderBy($values)
    {
        $key = array_get($values, 'key') ?? array_get($values, 'column');
        $order = array_get($values, 'order', 'ASC');
        if ($order == -1) $order = 'DESC';
        else if ($order == 1) $order = 'ASC';
        return $key && $order
            ? "ORDER BY `$key` $order"
            : ($key
                ? "ORDER BY `$key`"
                : '');
    }
    /**
     * Converts variable inputs to a flat array of array of "`$key` = $value" array
     *
     * @param string[] $values At array of key => value pairs
     * @return  array
     */
    public static function filters(...$values)
    {
        $values = ArrayHelper::flatten($values);
        $filters = [];
        foreach ($values as $key => $value)
        {
            if (!empty($value))
            {
                $value = is_numeric($key)
                    ? $value
                    : (preg_match('/[<>=]/', $value)
                        ? "$key $value"
                        : "$key = '$value'");
                array_push($filters, $value);
            }
        }
        return $filters;
    }

    /**
     * Converts a key => value array into a string of SQL variables
     *
     * @param $values
     * @return string
     */
    public static function vars($values)
    {
        $variables = '';
        foreach ($values as $key => $value)
        {
            if (is_string($value))
            {
                $value = "'$value'";
            }
            $variables .= "SET @$key = $value;\n";
        }
        return $variables;
    }

    /**
     * Gera a paginação dos itens de um array ou collection.
     *
     * @param array|Collection $items
     * @param int              $perPage
     * @param int              $page
     * @param array            $options
     *
     * @return LengthAwarePaginator
     */
    public static function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }


}