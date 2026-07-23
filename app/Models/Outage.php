<?php

namespace App\Models;

use App\Enums\OutageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outage extends Model
{
    use HasFactory;

    protected $fillable = [
        "zone",
        "title",
        "description",
        "status",
        "starts_at",
        "ends_at",
        "created_by",
        "resolved_at",
    ];

    protected $casts = [
        "status" => OutageStatus::class,
        "starts_at" => "datetime",
        "ends_at" => "datetime",
        "resolved_at" => "datetime",
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    /**
     * Whether this outage is currently relevant to the given zone —
     * either it's zone-wide (null zone) or matches exactly.
     */
    public function affectsZone(?string $zone): bool
    {
        return $this->zone === null || $this->zone === $zone;
    }
}
