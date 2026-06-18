<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;

//likes: id, user_id, post_id (полиморфное отношение для лайков)
class Like extends Model
{
    /** @use HasFactory<\Database\Factories\LikeFactory> */
    use HasFactory;

    public function users(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function posts(): MorphTo {
        return $this->morphTo();
    }
}
