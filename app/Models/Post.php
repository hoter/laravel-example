<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'slug', 'content', 'excerpt'])]
class Post extends Model
{
    use HasFactory;

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'views' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
