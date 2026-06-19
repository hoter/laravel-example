<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable('content', 'is_approved', 'user_id', 'post_id')]
class Comment extends Model
{
    use HasFactory;

    public function post(): BelongsTo {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }
}
