<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            config("roles.admin"),
            config("roles.management"),
            config("roles.customer"),
        ]);
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            return true;
        }

        return $user->id === $serviceRequest->account->user_id;
    }

    /**
     * Whether the user may file a service request on the given account.
     * Called explicitly with the target Account, since a service
     * request does not exist yet at creation time.
     */
    public function createFor(User $user, Account $account): bool
    {
        if (! $user->hasRole(config("roles.customer"))) {
            return false;
        }

        return $user->id === $account->user_id;
    }
}
