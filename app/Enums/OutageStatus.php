<?php

namespace App\Enums;

enum OutageStatus: string
{
    case Scheduled = "scheduled";
    case Active    = "active";
    case Resolved  = "resolved";

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => "Scheduled",
            self::Active    => "Active",
            self::Resolved  => "Resolved",
        };
    }
}
