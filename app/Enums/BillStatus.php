<?php

namespace App\Enums;

enum BillStatus: string
{
    case Pending    = "pending";
    case Paid       = "paid";
    case Overdue    = "overdue";
    case Defaulted  = "defaulted";

    public function label(): string
    {
        return match ($this) {
            self::Pending   => "Pending",
            self::Paid      => "Paid",
            self::Overdue   => "Overdue",
            self::Defaulted => "Defaulted",
        };
    }
}
