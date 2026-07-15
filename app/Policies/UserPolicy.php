<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageZone(User $user, User $technician): bool
    {
        if (! $user->hasRole(config("roles.admin"))) {
            return false;
        }

        return $technician->hasRole(config("roles.technician"));
    }
}
