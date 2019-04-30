<?php

namespace App\Models\Cap;

use App\Models\TableNameTrait;
use Illuminate\Database\Eloquent\Model;

class CAPRange extends Model
{

    protected $connection = 'sqlsrv';
    protected $table = 'CAPRange';
    protected $primaryKey = 'cran_code';
    //

    public function CAPMan()
    {
        return $this->belongsTo(CAPMan::class,'cran_mantextcode', 'cman_code');
    }

    public function CAPDer()
    {
        return $this->hasMany(CAPDer::class, 'cder_rancode', 'cran_code');
    }
}
