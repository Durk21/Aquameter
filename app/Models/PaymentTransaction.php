<?php

namespace App\Models;

use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "bill_id",
        "account_id",
        "initiated_by",
        "payment_id",
        "method",
        "status",
        "amount",
        "phone",
        "gateway",
        "checkout_request_id",
        "merchant_reference",
        "order_tracking_id",
        "failure_reason",
        "confirmed_at",
    ];

    protected $casts = [
        "status" => PaymentTransactionStatus::class,
        "amount" => "decimal:2",
        "confirmed_at" => "datetime",
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "initiated_by");
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
