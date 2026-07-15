<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        "account_id",
        "bill_id",
        "submitted_by",
        "subject",
        "description",
        "status",
        "resolution_notes",
        "resolved_by",
        "resolved_at",
    ];

    protected $casts = [
        "status" => ComplaintStatus::class,
        "resolved_at" => "datetime",
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "submitted_by");
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "resolved_by");
    }
}
