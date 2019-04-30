<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $guarded = ['id'];

    protected $visible = [
        'id',
        'type',
        'postcode',
        'flat_no',
        'house_no',
        'building_name',
        'street',
        'district',
        'town',
        'city',
        'county',
        'time_at_address',
        'residence_status',
    ];

    //$type = 'current' or 'previous'
    public function scopeIsType($query, $type){
        return $query->where('type', $type);
    }
}
