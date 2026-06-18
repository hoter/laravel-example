<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'sku', 'description', 'price', 'old_price', 'stock', 'category_id', 'is_active', 'created_at', 'updated_at'])]
class Product extends Model
{

    use HasFactory;
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

    protected function formattedPrice():Attribute {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['price'] . ' руб.'
        );
    }

}
