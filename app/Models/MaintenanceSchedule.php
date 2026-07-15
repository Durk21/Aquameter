<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "meter_id",
        "created_by",
        "scheduled_for",
        "description",
    ];

    protected $casts = [
        "scheduled_for" => "date",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function workOrder(): MorphOne
    {
        return $this->morphOne(WorkOrder::class, "sourceable");
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, "photoable");
    }
}
