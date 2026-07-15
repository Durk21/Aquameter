<?php

namespace App\Enums;

enum WorkOrderType: string
{
    case Disconnection = "disconnection";
    case Reconnection  = "reconnection";

    public function label(): string
    {
        return match ($this) {
            self::Disconnection => "Disconnection",
            self::Reconnection  => "Reconnection",
        };
    }
}
