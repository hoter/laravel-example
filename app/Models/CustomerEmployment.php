<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmployment extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['customer_id'];
}
