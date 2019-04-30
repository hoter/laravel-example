<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * class Derivative
 *
 * @package App\Models
 */
class Derivative extends Model
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public $dates = [
        'created_at',
        'updated_at',
    ];

}
