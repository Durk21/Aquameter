<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\LeakReport;
use App\Models\User;

class LeakReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            config("roles.admin"),
            config("roles.management"),
            config("roles.customer"),
        ]);
    }

    public function view(User $user, LeakReport $leakReport): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            return true;
        }

        return $user->id === $leakReport->account->user_id;
    }

    /**
     * Whether the user may report a leak on the given account.
     * Called explicitly with the target Account, since a leak report
     * does not exist yet at creation time.
     */
    public function createFor(User $user, Account $account): bool
    {
        if (! $user->hasRole(config("roles.customer"))) {
            return false;
        }

        return $user->id === $account->user_id;
    }
}
