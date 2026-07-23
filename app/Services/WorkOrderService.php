<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\LeakReport;
use App\Models\MaintenanceSchedule;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DisconnectionNoticeIssued;
use App\Notifications\WorkOrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    /**
     * Disconnection is never automatic. This issues the required
     * notice step and starts the notice-period clock — it does not
     * approve anything by itself.
     */
    public static function initiateDisconnection(Account $account, User $admin): WorkOrder
    {
        if ($account->status !== AccountStatus::Defaulted) {
            throw ValidationException::withMessages([
                "work_order" => "A disconnection notice can only be issued for a defaulted account.",
            ]);
        }

        if (self::hasOpenWorkOrder($account, WorkOrderType::Disconnection)) {
            throw ValidationException::withMessages([
                "work_order" => "This account already has an open disconnection work order.",
            ]);
        }

        return DB::transaction(function () use ($account, $admin) {
            $workOrder = WorkOrder::create([
                "account_id" => $account->id,
                "type" => WorkOrderType::Disconnection,
                "status" => WorkOrderStatus::NoticeSent,
                "created_by" => $admin->id,
                "notice_deadline" => now()->addDays(config("utility.disconnection_notice_days")),
            ]);

            $account->user->notify(new DisconnectionNoticeIssued($workOrder));

            return $workOrder;
        });
    }

    /**
     * The sign-off gate: an admin may only approve dispatch once the
     * notice period has actually elapsed, and only from notice_sent.
     */
    public static function signOff(WorkOrder $workOrder, User $admin): WorkOrder
    {
        if ($workOrder->status !== WorkOrderStatus::NoticeSent) {
            throw ValidationException::withMessages([
                "work_order" => "Only a work order awaiting notice can be signed off.",
            ]);
        }

        if ($workOrder->notice_deadline && $workOrder->notice_deadline->isFuture()) {
            throw ValidationException::withMessages([
                "work_order" => "The notice period has not elapsed yet.",
            ]);
        }

        return DB::transaction(function () use ($workOrder, $admin) {
            $workOrder->status = WorkOrderStatus::Approved;
            $workOrder->signed_off_by = $admin->id;
            $workOrder->signed_off_at = now();
            $workOrder->save();

            $workOrder->account->user->notify(new WorkOrderStatusUpdated($workOrder));

            return $workOrder;
        });
    }

    public static function cancel(WorkOrder $workOrder, User $admin): WorkOrder
    {
        if (in_array($workOrder->status, [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                "work_order" => "This work order is already closed.",
            ]);
        }

        return DB::transaction(function () use ($workOrder) {
            $workOrder->status = WorkOrderStatus::Cancelled;
            $workOrder->save();

            $workOrder->account->user->notify(new WorkOrderStatusUpdated($workOrder));

            return $workOrder;
        });
    }

    /**
     * A customer dispute pauses the pipeline at any point before a
     * technician has claimed the job.
     */
    public static function dispute(WorkOrder $workOrder, User $customer, string $reason): WorkOrder
    {
        if (! in_array($workOrder->status, [WorkOrderStatus::NoticeSent, WorkOrderStatus::Approved], true)) {
            throw ValidationException::withMessages([
                "work_order" => "This work order can no longer be disputed.",
            ]);
        }

        return DB::transaction(function () use ($workOrder, $customer, $reason) {
            $workOrder->status = WorkOrderStatus::Disputed;
            $workOrder->disputed_by = $customer->id;
            $workOrder->disputed_at = now();
            $workOrder->dispute_reason = $reason;
            $workOrder->save();

            return $workOrder;
        });
    }

    public static function resolveDispute(WorkOrder $workOrder, User $admin, string $resolution): WorkOrder
    {
        if ($workOrder->status !== WorkOrderStatus::Disputed) {
            throw ValidationException::withMessages([
                "work_order" => "This work order is not under dispute.",
            ]);
        }

        if (! in_array($resolution, ["reinstate", "cancel"], true)) {
            throw ValidationException::withMessages([
                "resolution" => "Resolution must be either reinstate or cancel.",
            ]);
        }

        return DB::transaction(function () use ($workOrder, $admin, $resolution) {
            if ($resolution === "reinstate") {
                $workOrder->status = WorkOrderStatus::Approved;
                $workOrder->signed_off_by = $admin->id;
                $workOrder->signed_off_at = now();
            } else {
                $workOrder->status = WorkOrderStatus::Cancelled;
            }

            $workOrder->save();

            $workOrder->account->user->notify(new WorkOrderStatusUpdated($workOrder));

            return $workOrder;
        });
    }

    /**
     * Transaction-safe claiming: row-locks the work order so two
     * technicians racing to claim the same job can't both succeed.
     */
    public static function claim(WorkOrder $workOrder, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $technician) {
            $locked = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WorkOrderStatus::Approved) {
                throw ValidationException::withMessages([
                    "work_order" => "This work order is no longer available to claim.",
                ]);
            }

            $locked->status = WorkOrderStatus::Claimed;
            $locked->assigned_to = $technician->id;
            $locked->claimed_at = now();
            $locked->save();

            return $locked;
        });
    }

    public static function complete(WorkOrder $workOrder, User $technician, ?string $notes = null): WorkOrder
    {
        if ($workOrder->status !== WorkOrderStatus::Claimed || $workOrder->assigned_to !== $technician->id) {
            throw ValidationException::withMessages([
                "work_order" => "Only the technician who claimed this job can complete it.",
            ]);
        }

        return DB::transaction(function () use ($workOrder, $notes) {
            $workOrder->status = WorkOrderStatus::Completed;
            $workOrder->completed_at = now();
            $workOrder->resolution_notes = $notes;
            $workOrder->save();

            $account = $workOrder->account;

            if ($workOrder->type === WorkOrderType::Disconnection) {
                $account->status = AccountStatus::Disconnected;
            } elseif ($workOrder->type === WorkOrderType::Reconnection) {
                $account->status = AccountStatus::Active;
                $account->defaulted_at = null;
            }

            $account->save();

            $workOrder->account->user->notify(new WorkOrderStatusUpdated($workOrder));

            return $workOrder;
        });
    }

    /**
     * Reconnection is its own tracked work order, not a status flip.
     * No notice or sign-off gate applies — it goes straight to the
     * technician dispatch queue.
     */
    public static function initiateReconnection(Account $account, ?User $admin, ?Bill $bill = null): WorkOrder
    {
        $workOrder = WorkOrder::create([
            "account_id" => $account->id,
            "bill_id" => $bill?->id,
            "type" => WorkOrderType::Reconnection,
            "status" => WorkOrderStatus::Approved,
            "created_by" => $admin?->id,
        ]);

        $account->user->notify(new WorkOrderStatusUpdated($workOrder));

        return $workOrder;
    }

    /**
     * A leak is urgent and self-evident — unlike disconnection it goes
     * straight to the technician dispatch queue, no sign-off gate.
     */
    public static function fromLeakReport(LeakReport $leakReport): WorkOrder
    {
        $workOrder = WorkOrder::create([
            "account_id" => $leakReport->account_id,
            "type" => WorkOrderType::LeakRepair,
            "status" => WorkOrderStatus::Approved,
            "created_by" => $leakReport->reported_by,
            "sourceable_type" => LeakReport::class,
            "sourceable_id" => $leakReport->id,
        ]);

        $leakReport->account->user->notify(new WorkOrderStatusUpdated($workOrder));

        return $workOrder;
    }

    /**
     * A service request also goes straight to dispatch — it was asked
     * for by the customer, there's nothing to sign off on.
     */
    public static function fromServiceRequest(ServiceRequest $serviceRequest): WorkOrder
    {
        $workOrder = WorkOrder::create([
            "account_id" => $serviceRequest->account_id,
            "type" => WorkOrderType::ServiceRequest,
            "status" => WorkOrderStatus::Approved,
            "created_by" => $serviceRequest->requested_by,
            "sourceable_type" => ServiceRequest::class,
            "sourceable_id" => $serviceRequest->id,
        ]);

        $serviceRequest->account->user->notify(new WorkOrderStatusUpdated($workOrder));

        return $workOrder;
    }

    /**
     * An admin approving a complaint that needs a field visit dispatches
     * it straight to the technician queue, just like a leak report or
     * service request. Guards against double-dispatch if a complaint is
     * approved more than once (e.g. re-saved without a status change).
     */
    public static function fromComplaint(Complaint $complaint, User $admin): ?WorkOrder
    {
        if (self::hasWorkOrderFor($complaint)) {
            return null;
        }

        $workOrder = WorkOrder::create([
            "account_id" => $complaint->account_id,
            "type" => WorkOrderType::ComplaintFollowUp,
            "status" => WorkOrderStatus::Approved,
            "created_by" => $admin->id,
            "sourceable_type" => Complaint::class,
            "sourceable_id" => $complaint->id,
        ]);

        $complaint->account->user->notify(new WorkOrderStatusUpdated($workOrder));

        return $workOrder;
    }

    protected static function hasWorkOrderFor(Complaint $complaint): bool
    {
        return WorkOrder::where("sourceable_type", Complaint::class)
            ->where("sourceable_id", $complaint->id)
            ->exists();
    }

    /**
     * A scheduled maintenance visit — admin-initiated, no sign-off
     * gate. It enters the claimable pool immediately; the scheduled
     * date is a plan for the technician, not a hard release time.
     */
    public static function fromMaintenanceSchedule(MaintenanceSchedule $schedule): WorkOrder
    {
        $workOrder = WorkOrder::create([
            "account_id" => $schedule->account_id,
            "type" => WorkOrderType::Maintenance,
            "status" => WorkOrderStatus::Approved,
            "created_by" => $schedule->created_by,
            "sourceable_type" => MaintenanceSchedule::class,
            "sourceable_id" => $schedule->id,
        ]);

        $schedule->account->user->notify(new WorkOrderStatusUpdated($workOrder));

        return $workOrder;
    }

    /**
     * A customer may rate a completed work order exactly once.
     */
    public static function rate(WorkOrder $workOrder, User $customer, int $rating, ?string $comment): WorkOrder
    {
        if ($workOrder->status !== WorkOrderStatus::Completed) {
            throw ValidationException::withMessages([
                "rating" => "Only a completed job can be rated.",
            ]);
        }

        if ($workOrder->rating !== null) {
            throw ValidationException::withMessages([
                "rating" => "This job has already been rated.",
            ]);
        }

        return DB::transaction(function () use ($workOrder, $rating, $comment) {
            $workOrder->rating = $rating;
            $workOrder->rating_comment = $comment;
            $workOrder->rated_at = now();
            $workOrder->save();

            return $workOrder;
        });
    }

    public static function hasOpenWorkOrder(Account $account, WorkOrderType $type): bool
    {
        return WorkOrder::where("account_id", $account->id)
            ->where("type", $type)
            ->whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])
            ->exists();
    }
}
