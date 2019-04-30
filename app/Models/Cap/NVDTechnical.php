<?php

namespace App\Models\Cap;

use Illuminate\Database\Eloquent\Model;

class NVDTechnical extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'NVDTechnical';

    public static $technicalDictionary = [
      'BHP'     => 21,
      'BPH'     => 21, //FIXME it will be bhp not bph
      'CO2'     => 67,
      'MPG'     => 11,
      'HP'      => 145,
      'ENGINE_SIZE' => 146
    ];
}
