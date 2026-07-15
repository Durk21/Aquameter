<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
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

    public function view(User $user, Bill $bill): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management"), config("roles.technician")])) {
            return true;
        }

        return $user->id === $bill->account->user_id;
    }
}
