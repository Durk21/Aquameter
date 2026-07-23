<?php

namespace App\Policies;

use App\Models\PaymentTransaction;
use App\Models\User;

class PaymentTransactionPolicy
{
    public function view(User $user, PaymentTransaction $transaction): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            return true;
        }

        return $user->id === $transaction->initiated_by;
    }
}
