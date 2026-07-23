<?php

namespace App\Policies;

use App\Models\User;

class OutagePolicy
{
    /**
     * Whether the user may view, broadcast, and resolve outage notices.
     * Admin and management only — this is an operations tool, not a
     * customer-facing form.
     */
    public function manage(User $user): bool
    {
        return $user->hasAnyRole([config("roles.admin"), config("roles.management")]);
    }
}
