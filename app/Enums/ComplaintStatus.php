<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Submitted   = "submitted";
    case UnderReview = "under_review";
    case Approved    = "approved";
    case Resolved    = "resolved";
    case Rejected    = "rejected";

    public function label(): string
    {
        return match ($this) {
            self::Submitted   => "Submitted",
            self::UnderReview => "Under Review",
            self::Approved    => "Approved",
            self::Resolved    => "Resolved",
            self::Rejected    => "Rejected",
        };
    }
}
