<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
   protected $table='car_model';
   protected $primaryKey = 'model_cap_id';
   protected $guarded = [];
}
