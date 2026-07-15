<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        "bill_id",
        "account_id",
        "recorded_by",
        "amount",
        "method",
        "reference",
        "paid_at",
    ];

    protected $casts = [
        "amount" => "decimal:2",
        "paid_at" => "datetime",
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "recorded_by");
    }
}
