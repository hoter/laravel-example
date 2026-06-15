<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'sku', 'description', 'price', 'old_price', 'stock', 'category_id', 'is_active', 'created_at', 'updated_at'])]
class Product extends Model
{
    // Поля: id, name, sku (уникальный), description, price, old_price,
    //       stock, is_active, category_id, created_at, updated_at
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected $attributes = [
        'views' => 0,
        'stock' => 0,
        'is_active' => false,
    ];

}
