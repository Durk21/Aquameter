<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "requested_by",
        "type",
        "zone",
        "description",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "requested_by");
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
