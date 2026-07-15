<?php

namespace App\Models;

use App\Enums\LeakSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class LeakReport extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "reported_by",
        "meter_id",
        "severity",
        "zone",
        "location_notes",
        "latitude",
        "longitude",
        "description",
    ];

    protected $casts = [
        "severity" => LeakSeverity::class,
        "latitude" => "decimal:7",
        "longitude" => "decimal:7",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "reported_by");
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function workOrder(): MorphOne
    {
        return $this->morphOne(WorkOrder::class, "sourceable");
    }
}
