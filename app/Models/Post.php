<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


#[Fillable(['title', 'slug', 'content', 'excerpt'])]
class Post extends Model
{
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
