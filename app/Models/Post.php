<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    #[Fillable([
        'title', 'slug', 'content', 'excerpt', 'is_published', 'published_at'
    ])]

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'views' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
