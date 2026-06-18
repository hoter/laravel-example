<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable('product_id', 'user_name', 'comment', 'is_approved')]
class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    // Поля: id, product_id (внешний ключ), user_name, rating (1-5), comment, is_approved, created_at, updated_at

}
