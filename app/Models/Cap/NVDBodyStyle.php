<?php

namespace App\Models\Cap;

use Illuminate\Database\Eloquent\Model;

class NVDBodyStyle extends Model
{
    protected $table ='NVDBodyStyle';

    protected $connection = 'sqlsrv';

    public static function getOrdered($orderBy = 'bs_description'){
       return self::orderBy($orderBy)->get();
    }
}
