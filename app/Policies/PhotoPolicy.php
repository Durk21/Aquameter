<?php

namespace App\Policies;

use App\Models\LeakReport;
use App\Models\Photo;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;

class PhotoPolicy
{
    public function view(User $user, Photo $photo): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management"), config("roles.technician")])) {
            return true;
        }

        $photoable = $photo->photoable;

        $accountUserId = match (true) {
            $photoable instanceof LeakReport,
            $photoable instanceof ServiceRequest,
            $photoable instanceof WorkOrder => $photoable->account->user_id,
            default => null,
        };

        return $user->id === $accountUserId;
    }
}
