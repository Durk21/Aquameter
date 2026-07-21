<?php

namespace App\Enums;

enum PipeSegmentStatus: string
{
    case Active      = "active";
    case Maintenance = "maintenance";
    case Damaged     = "damaged";

    public function label(): string
    {
        return match ($this) {
            self::Active      => "Active",
            self::Maintenance => "Under Maintenance",
            self::Damaged     => "Damaged",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active      => "#249cac",
            self::Maintenance => "#d97706",
            self::Damaged     => "#dc2626",
        };
    }
}
