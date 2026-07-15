<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\LeakReport;
use App\Models\Meter;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected const OPEN_BILL_STATUSES = [BillStatus::Pending, BillStatus::Overdue, BillStatus::Defaulted];
    protected const OPEN_COMPLAINT_STATUSES = [ComplaintStatus::Submitted, ComplaintStatus::UnderReview];
    protected const OPEN_WORK_ORDER_STATUSES = [WorkOrderStatus::NoticeSent, WorkOrderStatus::Approved, WorkOrderStatus::Disputed, WorkOrderStatus::Claimed];

    public function admin(): Response
    {
        $accountCounts = Account::selectRaw("status, count(*) as count")
            ->groupBy("status")
            ->pluck("count", "status");

        $pendingSignOff = WorkOrder::where("status", WorkOrderStatus::NoticeSent)
            ->whereNotNull("notice_deadline")
            ->where("notice_deadline", "<=", now())
            ->count();

        return Inertia::render("Admin/Dashboard", [
            "stats" => [
                "accounts" => [
                    "active" => $accountCounts["active"] ?? 0,
                    "overdue" => $accountCounts["overdue"] ?? 0,
                    "defaulted" => $accountCounts["defaulted"] ?? 0,
                    "disconnected" => $accountCounts["disconnected"] ?? 0,
                ],
                "outstanding_amount" => Bill::whereIn("status", self::OPEN_BILL_STATUSES)->sum("amount"),
                "open_complaints" => Complaint::whereIn("status", self::OPEN_COMPLAINT_STATUSES)->count(),
                "pending_sign_off" => $pendingSignOff,
                "active_pipeline" => WorkOrder::whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])->count(),
                "completed_this_week" => WorkOrder::where("status", WorkOrderStatus::Completed)
                    ->where("completed_at", ">=", now()->startOfWeek())
                    ->count(),
                "technicians" => User::role(config("roles.technician"))->count(),
                "technicians_zoned" => User::role(config("roles.technician"))->whereNotNull("zone")->count(),
            ],
        ]);
    }

    public function technician(Request $request): Response
    {
        $user = $request->user();

        $availableInZone = 0;
        if ($user->zone) {
            $availableInZone = WorkOrder::where("status", WorkOrderStatus::Approved)
                ->with(["sourceable", "account"])
                ->get()
                ->filter(fn (WorkOrder $workOrder) => $workOrder->dispatchZone() === $user->zone)
                ->count();
        }

        return Inertia::render("Technician/Dashboard", [
            "stats" => [
                "my_active_jobs" => WorkOrder::where("assigned_to", $user->id)->where("status", WorkOrderStatus::Claimed)->count(),
                "available_in_zone" => $availableInZone,
                "completed_total" => WorkOrder::where("assigned_to", $user->id)->where("status", WorkOrderStatus::Completed)->count(),
                "completed_this_week" => WorkOrder::where("assigned_to", $user->id)
                    ->where("status", WorkOrderStatus::Completed)
                    ->where("completed_at", ">=", now()->startOfWeek())
                    ->count(),
                "zone" => $user->zone,
            ],
        ]);
    }

    public function customer(Request $request): Response
    {
        $account = Account::where("user_id", $request->user()->id)->first();

        $activeWorkOrder = $account
            ? WorkOrder::where("account_id", $account->id)
                ->whereIn("status", [WorkOrderStatus::NoticeSent, WorkOrderStatus::Approved, WorkOrderStatus::Disputed])
                ->latest()
                ->first()
            : null;

        $stats = null;

        if ($account) {
            $nextBill = Bill::where("account_id", $account->id)
                ->whereIn("status", [BillStatus::Pending, BillStatus::Overdue])
                ->orderBy("due_date")
                ->first();

            $openLeakReports = LeakReport::where("account_id", $account->id)
                ->whereHas("workOrder", fn ($query) => $query->whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled]))
                ->count();

            $openServiceRequests = ServiceRequest::where("account_id", $account->id)
                ->whereHas("workOrder", fn ($query) => $query->whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled]))
                ->count();

            $stats = [
                "account_number" => $account->account_number,
                "status" => $account->status->value,
                "status_label" => $account->status->label(),
                "zone" => $account->zone,
                "outstanding_amount" => Bill::where("account_id", $account->id)
                    ->whereIn("status", self::OPEN_BILL_STATUSES)
                    ->sum("amount"),
                "next_due_date" => $nextBill?->due_date->toDateString(),
                "meter_count" => Meter::where("account_id", $account->id)->count(),
                "open_complaints" => Complaint::where("account_id", $account->id)
                    ->whereIn("status", self::OPEN_COMPLAINT_STATUSES)
                    ->count(),
                "open_requests" => $openLeakReports + $openServiceRequests,
            ];
        }

        return Inertia::render("Customer/Dashboard", [
            "activeWorkOrder" => $activeWorkOrder ? [
                "id" => $activeWorkOrder->id,
                "type" => $activeWorkOrder->type->value,
                "type_label" => $activeWorkOrder->type->label(),
                "status" => $activeWorkOrder->status->value,
                "status_label" => $activeWorkOrder->status->label(),
                "notice_deadline" => $activeWorkOrder->notice_deadline?->toDateString(),
            ] : null,
            "stats" => $stats,
        ]);
    }
}
