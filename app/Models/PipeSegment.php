<?php

namespace App\Models;

use App\Enums\PipeSegmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipeSegment extends Model
{
    protected $fillable = [
        "name",
        "zone",
        "status",
        "points",
        "created_by",
    ];

    protected $casts = [
        "status" => PipeSegmentStatus::class,
        "points" => "array",
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    /**
     * Lightweight shape for rendering on the map — used by the public
     * landing page, the shared Network Map, and any form that shows the
     * pipe network for location-picking context.
     */
    public static function forMap(): array
    {
        return static::get()->map(fn (PipeSegment $pipeSegment) => [
            "id" => $pipeSegment->id,
            "name" => $pipeSegment->name,
            "zone" => $pipeSegment->zone,
            "status" => $pipeSegment->status->value,
            "status_label" => $pipeSegment->status->label(),
            "color" => $pipeSegment->status->color(),
            "points" => $pipeSegment->points,
        ])->all();
    }
}
