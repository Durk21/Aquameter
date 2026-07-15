<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            config("roles.admin"),
            config("roles.management"),
            config("roles.customer"),
        ]);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            return true;
        }

        return $user->id === $complaint->account->user_id;
    }

    /**
     * Whether the user may submit a complaint on the given account.
     * Called explicitly with the target Account, since a complaint
     * does not exist yet at creation time.
     */
    public function createFor(User $user, Account $account): bool
    {
        if (! $user->hasRole(config("roles.customer"))) {
            return false;
        }

        return $user->id === $account->user_id;
    }

    public function review(User $user, Complaint $complaint): bool
    {
        return $user->hasAnyRole([config("roles.admin"), config("roles.management")]);
    }
}
