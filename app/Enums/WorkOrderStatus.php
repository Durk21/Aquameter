<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case NoticeSent = "notice_sent";
    case Approved   = "approved";
    case Disputed   = "disputed";
    case Claimed    = "claimed";
    case Completed  = "completed";
    case Cancelled  = "cancelled";

    public function label(): string
    {
        return match ($this) {
            self::NoticeSent => "Notice Sent",
            self::Approved   => "Approved",
            self::Disputed   => "Disputed",
            self::Claimed    => "Claimed",
            self::Completed  => "Completed",
            self::Cancelled  => "Cancelled",
        };
    }
}
