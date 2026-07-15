<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meter extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "meter_number",
        "status",
        "installed_at",
    ];

    protected $casts = [
        "installed_at" => "date",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }
}
