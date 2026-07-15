<?php

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;

class MeterReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            config("roles.technician"),
            config("roles.admin"),
            config("roles.management"),
            config("roles.customer"),
        ]);
    }

    public function view(User $user, MeterReading $meterReading): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management"), config("roles.technician")])) {
            return true;
        }

        return $user->id === $meterReading->meter->account->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([config("roles.technician"), config("roles.admin")]);
    }

    public function update(User $user, MeterReading $meterReading): bool
    {
        return false;
    }

    public function delete(User $user, MeterReading $meterReading): bool
    {
        return false;
    }
}
