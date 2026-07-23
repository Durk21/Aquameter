<?php

namespace App\Policies;

use App\Models\User;

class PipeSegmentPolicy
{
    /**
     * Whether the user may create, edit, or delete pipe segments.
     * Admin only — viewing the network map itself is open to any
     * authenticated role and isn't gated through this policy.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(config("roles.admin"));
    }
}
