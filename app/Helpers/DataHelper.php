<?php namespace Helpers;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * DbHelper class
 */
class DataHelper
{

    /**
     * Helper function to partition old and new data into buckets to:
     *
     *  - create
     *  - update
     *  - delete
     *
     * These buckets can then be used to update the database
     *
     * @usage
     *
     *      $oldData = Customer::find(1)->addresses;
     *      $newData = $request->all();
     *      $buckets = DataHelper::crudPartition($oldData, $newData);
     *
     *      $buckets['create']
     *
     * @param   array $oldData
     * @param   array $newData
     * @return  Collection[]
     */
    public static function crudPartition($oldData, $newData)
    {
        // ids
        $oldIds = array_pluck($oldData, 'id');
        $newIds = array_filter(array_pluck($newData, 'id'), 'is_numeric');

        // ensure new data is an array of arrays
        $newData = collect($newData)
            ->map(function ($data) { return (array) $data; })
            ->values();

        // groups
        $create = collect($newData)
            ->filter(function ($model) {
                return !array_get($model, 'id');
            })
            ->values();

        $update = collect($newData)
            ->filter(function ($model) use ($oldIds) {
                return array_key_exists('id', $model) && in_array($model['id'], $oldIds);
            })
            ->values();

        $delete = collect($oldData)
            ->filter(function ($model) use ($newIds) {
                return !in_array($model->id, $newIds);
            })
            ->values();

        // return
        return compact('create', 'update', 'delete');
    }
    
}