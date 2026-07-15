<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active       = "active";
    case Overdue      = "overdue";
    case Defaulted    = "defaulted";
    case Disconnected = "disconnected";

    public function label(): string
    {
        return match ($this) {
            self::Active       => "Active",
            self::Overdue      => "Overdue",
            self::Defaulted    => "Defaulted",
            self::Disconnected => "Disconnected",
        };
    }
}
