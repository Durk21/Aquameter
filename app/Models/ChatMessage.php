<?php

namespace App\Models;

use App\Enums\ChatRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        "user_id",
        "role",
        "content",
    ];

    protected $casts = [
        "role" => ChatRole::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
