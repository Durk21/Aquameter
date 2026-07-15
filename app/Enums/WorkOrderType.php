<?php

namespace App\Enums;

enum WorkOrderType: string
{
    case Disconnection  = "disconnection";
    case Reconnection   = "reconnection";
    case LeakRepair     = "leak_repair";
    case ServiceRequest = "service_request";

    public function label(): string
    {
        return match ($this) {
            self::Disconnection  => "Disconnection",
            self::Reconnection   => "Reconnection",
            self::LeakRepair     => "Leak Repair",
            self::ServiceRequest => "Service Request",
        };
    }
}
