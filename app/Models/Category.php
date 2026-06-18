<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Post;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function posts(): HasMany {
        return $this->hasMany(Post::class);
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(Category::class);
    }

    public function children(): HasMany {
        return $this->hasMany(Category::class);
    }
}
