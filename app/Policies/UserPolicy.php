<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Whether the user may view the staff list and create new staff
     * accounts. Admin-only — this is the account-provisioning path
     * for admin/technician/management, none of which self-register.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    public function manageZone(User $user, User $technician): bool
    {
        if (! $user->hasRole(config("roles.admin"))) {
            return false;
        }

        return $technician->hasRole(config("roles.technician"));
    }
}
