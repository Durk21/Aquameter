<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        "subject_type",
        "subject_id",
        "action",
        "from_status",
        "to_status",
        "caused_by",
        "notes",
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "caused_by");
    }

    /**
     * A backed enum's original value survives a status change as
     * either the enum instance or its raw string, depending on
     * Eloquent's cast timing — normalize either way to a plain string.
     */
    public static function recordStatusChange(Model $subject, mixed $from, mixed $to, ?string $notes = null): self
    {
        return self::create([
            "subject_type" => $subject::class,
            "subject_id" => $subject->id,
            "action" => "status_changed",
            "from_status" => self::rawValue($from),
            "to_status" => self::rawValue($to),
            "caused_by" => auth()->id(),
            "notes" => $notes,
        ]);
    }

    protected static function rawValue(mixed $value): ?string
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
