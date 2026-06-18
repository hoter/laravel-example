<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable('name', 'slug', 'description', 'parent_id')]
class Category extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'slug' => 'string',
        ];
    }
}
