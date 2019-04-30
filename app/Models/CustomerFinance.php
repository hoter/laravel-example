<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFinance extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['customer_id'];
}
