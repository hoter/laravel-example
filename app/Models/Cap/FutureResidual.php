<?php

namespace App\Models\Cap;

use Illuminate\Database\Eloquent\Model;

class FutureResidual extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'FutureResidual';
    protected $primaryKey = 'fr_ID';
}
