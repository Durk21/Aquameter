<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MeterReading extends Model
{
    use HasFactory;

    protected $fillable = [
        "meter_id",
        "recorded_by",
        "reading_value",
        "reading_date",
        "is_anomalous",
    ];

    protected $casts = [
        "reading_value" => "decimal:2",
        "reading_date" => "date",
        "is_anomalous" => "boolean",
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "recorded_by");
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }
}
