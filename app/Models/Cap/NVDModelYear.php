<?php

namespace App\Models\Cap;

use Illuminate\Database\Eloquent\Model;

class NVDModelYear extends Model
{
    // connection
    protected $connection = 'sqlsrv';
    // table
    protected $table = 'NVDModelYear';

}
