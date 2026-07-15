<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "user_id",
        "account_number",
        "address",
        "zone",
        "status",
        "defaulted_at",
    ];

    protected $casts = [
        "status" => AccountStatus::class,
        "defaulted_at" => "datetime",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
public function complaints(): HasMany
{
    return $this->hasMany(Complaint::class);
}
}
