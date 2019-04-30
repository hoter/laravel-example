<?php namespace App\Helpers;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * JSONPaginator class
 *
 * Quick and easy JSON-serializable paginator for returning database results
 */
class JsonPaginator implements Jsonable
{

    public $pagination;

    public $results;

    public function __construct($items, $page = 1, $pageSize = 30)
    {
        // parameters
        $items    = collect($items)->toArray();
        $page     = (int) \Request::get('page', $page);
        $pageSize = (int) \Request::get('per_page', $pageSize);
        if ($page < 1)
        {
            $page = 1;
        }

        // calculate
        $total     = count($items);
        $slice     = array_slice($items, ($page - 1) * $pageSize, $pageSize);
        $paginator = new LengthAwarePaginator($slice, $total, $pageSize, $page);

        // return
        $this->pagination = array_except($paginator->toArray(), 'data');
        $this->results    = $slice;
    }

    public static function create($items, $page = 1, $pageSize = 30)
    {
        return new static($items, $page, $pageSize);
    }

    public function toJson($options = 0)
    {
        return json_encode($this);
    }
}