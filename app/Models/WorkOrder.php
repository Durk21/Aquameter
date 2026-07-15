<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "bill_id",
        "type",
        "status",
        "created_by",
        "assigned_to",
        "claimed_at",
        "signed_off_by",
        "signed_off_at",
        "notice_deadline",
        "disputed_by",
        "disputed_at",
        "dispute_reason",
        "completed_at",
        "resolution_notes",
    ];

    protected $casts = [
        "type" => WorkOrderType::class,
        "status" => WorkOrderStatus::class,
        "claimed_at" => "datetime",
        "signed_off_at" => "datetime",
        "notice_deadline" => "datetime",
        "disputed_at" => "datetime",
        "completed_at" => "datetime",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, "assigned_to");
    }

    public function signedOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "signed_off_by");
    }

    public function disputedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "disputed_by");
    }
}
