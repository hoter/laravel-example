<?php

namespace App\Models\Cap;

use Illuminate\Database\Eloquent\Model;

class DailySTFForecast extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'DailySTFForecast';
    protected $primaryKey = 'df_Id';
}
