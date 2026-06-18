<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Post;
use App\Models\User;

class Comment extends Model
{
    public function post(): BelongsTo {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
