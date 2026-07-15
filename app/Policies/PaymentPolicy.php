<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management"), config("roles.technician")])) {
            return true;
        }

        return $user->id === $payment->account->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(config("roles.admin"));
    }
}
