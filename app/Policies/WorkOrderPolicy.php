<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            config("roles.admin"),
            config("roles.management"),
            config("roles.technician"),
        ]);
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        if ($user->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            return true;
        }

        if ($user->hasRole(config("roles.technician"))) {
            return true;
        }

        return $user->id === $workOrder->account->user_id;
    }

    public function initiateDisconnection(User $user): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    public function signOff(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    public function cancel(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    public function resolveDispute(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    /**
     * Only the customer who owns the affected account may dispute
     * the work order — never another customer, and never staff.
     */
    public function dispute(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->hasRole(config("roles.customer"))) {
            return false;
        }

        return $user->id === $workOrder->account->user_id;
    }

    public function claim(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasRole(config("roles.technician"));
    }

    /**
     * Only the technician who claimed the work order may complete it.
     */
    public function complete(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->hasRole(config("roles.technician"))) {
            return false;
        }

        return $user->id === $workOrder->assigned_to;
    }
}
