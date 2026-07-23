<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Photo extends Model
{
    protected $fillable = [
        "photoable_type",
        "photoable_id",
        "uploaded_by",
        "path",
        "original_filename",
        "mime_type",
        "size",
    ];

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "uploaded_by");
    }
}
