<?php

namespace App\Models;

use App\Enums\BillStatus;
use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "account_id",
        "meter_reading_id",
        "previous_reading_value",
        "current_reading_value",
        "units_consumed",
        "rate_applied",
        "amount",
        "status",
        "due_date",
        "paid_at",
    ];

    protected $casts = [
        "previous_reading_value" => "decimal:2",
        "current_reading_value" => "decimal:2",
        "units_consumed" => "decimal:2",
        "rate_applied" => "decimal:2",
        "amount" => "decimal:2",
        "status" => BillStatus::class,
        "due_date" => "date",
        "paid_at" => "datetime",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * The in-flight gateway attempt (if any), so the payment page can
     * resume polling instead of re-showing the method picker.
     */
    public function pendingPaymentTransaction(): ?PaymentTransaction
    {
        return $this->paymentTransactions()
            ->where("status", PaymentTransactionStatus::Pending)
            ->latest()
            ->first();
    }
}
