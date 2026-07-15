<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\ComplaintStatus;
use App\Enums\OutageStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\LeakReport;
use App\Models\Meter;
use App\Models\Outage;
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

        $activeOutages = Outage::where(fn ($query) => $query
                ->whereNull("zone")
                ->when($account, fn ($query) => $query->orWhere("zone", $account->zone)))
            ->whereIn("status", [OutageStatus::Scheduled, OutageStatus::Active])
            ->orderBy("starts_at")
            ->get()
            ->map(fn (Outage $outage) => [
                "id" => $outage->id,
                "zone" => $outage->zone,
                "title" => $outage->title,
                "description" => $outage->description,
                "status" => $outage->status->value,
                "status_label" => $outage->status->label(),
                "starts_at" => $outage->starts_at->toDateTimeString(),
                "ends_at" => $outage->ends_at?->toDateTimeString(),
            ]);

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
            "outages" => $activeOutages,
            "stats" => $stats,
        ]);
    }

    public function management(): Response
    {
        $startOfMonth = now()->startOfMonth();
        $stallThreshold = now()->subDays((int) config("utility.stall_threshold_days"));

        $closedWorkOrders = WorkOrder::whereIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])->count();
        $completedWorkOrders = WorkOrder::where("status", WorkOrderStatus::Completed)->count();

        $avgResolutionDays = self::averageDaysBetween(
            WorkOrder::where("status", WorkOrderStatus::Completed)->get(["created_at", "completed_at"]),
            "created_at",
            "completed_at",
        );

        $avgDaysDisconnected = self::averageDaysBetween(
            WorkOrder::where("type", WorkOrderType::Reconnection)
                ->where("status", WorkOrderStatus::Completed)
                ->get()
                ->map(function (WorkOrder $reconnection) {
                    $disconnection = WorkOrder::where("account_id", $reconnection->account_id)
                        ->where("type", WorkOrderType::Disconnection)
                        ->where("status", WorkOrderStatus::Completed)
                        ->where("completed_at", "<=", $reconnection->completed_at)
                        ->orderByDesc("completed_at")
                        ->first();

                    return $disconnection ? (object) [
                        "start" => $disconnection->completed_at,
                        "end" => $reconnection->completed_at,
                    ] : null;
                })
                ->filter(),
            "start",
            "end",
        );

        $byType = WorkOrder::selectRaw("type, count(*) as count")
            ->whereNotIn("status", [WorkOrderStatus::Cancelled])
            ->groupBy("type")
            ->get()
            ->map(fn (WorkOrder $row) => [
                "type" => $row->type->value,
                "label" => $row->type->label(),
                "count" => $row->count,
            ]);

        $byZone = WorkOrder::whereNotIn("status", [WorkOrderStatus::Cancelled])
            ->with(["sourceable", "account"])
            ->get()
            ->groupBy(fn (WorkOrder $workOrder) => $workOrder->dispatchZone() ?? "Unassigned")
            ->map(fn ($group, $zone) => ["zone" => $zone, "count" => $group->count()])
            ->sortByDesc("count")
            ->values();

        $ratedCount = WorkOrder::whereNotNull("rating")->count();

        return Inertia::render("Management/Dashboard", [
            "stats" => [
                "active_pipeline" => WorkOrder::whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])->count(),
                "completed_this_month" => WorkOrder::where("status", WorkOrderStatus::Completed)
                    ->where("completed_at", ">=", $startOfMonth)
                    ->count(),
                "avg_resolution_days" => $avgResolutionDays,
                "completion_rate" => $closedWorkOrders > 0 ? round(($completedWorkOrders / $closedWorkOrders) * 100, 1) : null,
                "currently_disconnected" => Account::where("status", AccountStatus::Disconnected)->count(),
                "disconnections_this_month" => WorkOrder::where("type", WorkOrderType::Disconnection)
                    ->where("status", WorkOrderStatus::Completed)
                    ->where("completed_at", ">=", $startOfMonth)
                    ->count(),
                "reconnections_this_month" => WorkOrder::where("type", WorkOrderType::Reconnection)
                    ->where("status", WorkOrderStatus::Completed)
                    ->where("completed_at", ">=", $startOfMonth)
                    ->count(),
                "avg_days_disconnected" => $avgDaysDisconnected,
                "open_complaints" => Complaint::whereIn("status", self::OPEN_COMPLAINT_STATUSES)->count(),
                "resolved_complaints_this_month" => Complaint::where("status", ComplaintStatus::Resolved)
                    ->where("resolved_at", ">=", $startOfMonth)
                    ->count(),
                "avg_complaint_resolution_days" => self::averageDaysBetween(
                    Complaint::whereNotNull("resolved_at")->get(["created_at", "resolved_at"]),
                    "created_at",
                    "resolved_at",
                ),
                "leak_reports_this_month" => LeakReport::where("created_at", ">=", $startOfMonth)->count(),
                "stalled_work_orders" => WorkOrder::where("status", WorkOrderStatus::Approved)
                    ->where("created_at", "<=", $stallThreshold)
                    ->count(),
                "stalled_complaints" => Complaint::whereIn("status", self::OPEN_COMPLAINT_STATUSES)
                    ->where("created_at", "<=", $stallThreshold)
                    ->count(),
                "avg_rating" => $ratedCount > 0 ? round(WorkOrder::whereNotNull("rating")->avg("rating"), 1) : null,
                "rated_count" => $ratedCount,
            ],
            "byType" => $byType,
            "byZone" => $byZone,
            "stallThresholdDays" => (int) config("utility.stall_threshold_days"),
        ]);
    }

    /**
     * Average whole-day gap between two timestamp fields across a
     * collection, or null if there's nothing to average — never 0,
     * which would misreport "instant" when there's simply no data yet.
     */
    protected static function averageDaysBetween($records, string $startField, string $endField): ?float
    {
        $records = collect($records)->filter(fn ($record) => $record->{$startField} && $record->{$endField});

        if ($records->isEmpty()) {
            return null;
        }

        return round($records->avg(fn ($record) => $record->{$startField}->diffInHours($record->{$endField}) / 24), 1);
    }
}
