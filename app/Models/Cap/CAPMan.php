<?php

namespace App\Models\Cap;

use App\Models\TableNameTrait;
use Illuminate\Database\Eloquent\Model;

class CAPMan extends Model
{

    protected $connection = 'sqlsrv';
    protected $table = 'CAPMan';
    protected $primaryKey = 'cman_code';
    //

    public function CAPRange()
    {
        return $this->hasMany(CAPRange::class, 'cran_mantextcode', 'cman_code');
    }

    public function CAPDer()
    {
        return $this->hasMany(CAPDer::class, 'cder_mancode', 'cman_code');
    }
}
