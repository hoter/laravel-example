<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';
    protected $guarded = ['id'];

    public static function getValue($name = null)
    {
            $setting = self::where('name', $name)->where('is_active', 1)->first();
            if (!empty($setting)) {
                return $setting->value;
            }
            return false;
    }
}