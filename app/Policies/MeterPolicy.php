<?php

namespace App\Policies;

use App\Models\Meter;
use App\Models\User;

class MeterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            config("roles.admin"),
            config("roles.management"),
            config("roles.technician"),
            config("roles.customer"),
        ]);
    }

    public function view(User $user, Meter $meter): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management"), config("roles.technician")])) {
            return true;
        }

        return $user->id === $meter->account->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    public function update(User $user, Meter $meter): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    public function delete(User $user, Meter $meter): bool
    {
        return $user->hasRole(config("roles.admin"));
    }
}
