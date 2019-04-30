<?php

namespace App\Models\Cap;

use Illuminate\Database\Eloquent\Model;

class NVDPrices extends Model
{

    protected $connection = 'sqlsrv';
    protected $table = 'NVDPrices';
}
