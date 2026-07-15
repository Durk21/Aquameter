<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\LeakReport;
use App\Models\ServiceRequest;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController extends Controller
{
    public function adminIndex(Request $request): Response
    {
        Gate::authorize("viewAny", WorkOrder::class);

        $defaultedAccounts = Account::where("status", AccountStatus::Defaulted)
            ->whereDoesntHave("workOrders", function ($query) {
                $query->where("type", WorkOrderType::Disconnection)
                    ->whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled]);
            })
            ->with("user")
            ->get()
            ->map(fn (Account $account) => $this->summarizeAccount($account));

        $pipeline = WorkOrder::whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])
            ->with(["account.user", "sourceable"])
            ->orderByDesc("created_at")
            ->get()
            ->map(fn (WorkOrder $workOrder) => $this->summarizeWorkOrder($workOrder));

        $history = WorkOrder::whereIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])
            ->with(["account.user", "sourceable"])
            ->orderByDesc("updated_at")
            ->limit(20)
            ->get()
            ->map(fn (WorkOrder $workOrder) => $this->summarizeWorkOrder($workOrder));

        return Inertia::render("Admin/WorkOrders/Index", [
            "defaultedAccounts" => $defaultedAccounts,
            "pipeline" => $pipeline,
            "history" => $history,
            "noticeDays" => (int) config("utility.disconnection_notice_days"),
        ]);
    }

    public function initiateDisconnection(Request $request, Account $account): RedirectResponse
    {
        Gate::authorize("initiateDisconnection", WorkOrder::class);

        WorkOrderService::initiateDisconnection($account, $request->user());

        return redirect()
            ->route("admin.work-orders.index")
            ->with("status", "Disconnection notice issued.");
    }

    public function signOff(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize("signOff", $workOrder);

        WorkOrderService::signOff($workOrder, $request->user());

        return redirect()
            ->route("admin.work-orders.index")
            ->with("status", "Disconnection approved for dispatch.");
    }

    public function cancel(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize("cancel", $workOrder);

        WorkOrderService::cancel($workOrder, $request->user());

        return redirect()
            ->route("admin.work-orders.index")
            ->with("status", "Work order cancelled.");
    }

    public function resolveDispute(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize("resolveDispute", $workOrder);

        $validated = $request->validate([
            "resolution" => "required|in:reinstate,cancel",
        ]);

        WorkOrderService::resolveDispute($workOrder, $request->user(), $validated["resolution"]);

        return redirect()
            ->route("admin.work-orders.index")
            ->with("status", "Dispute resolved.");
    }

    public function technicianIndex(Request $request): Response
    {
        $user = $request->user();

        $claimable = WorkOrder::where("status", WorkOrderStatus::Approved)
            ->with(["account.user", "sourceable"])
            ->orderBy("created_at")
            ->get()
            ->map(fn (WorkOrder $workOrder) => $this->summarizeWorkOrder($workOrder));

        $myJobs = WorkOrder::where("status", WorkOrderStatus::Claimed)
            ->where("assigned_to", $user->id)
            ->with(["account.user", "sourceable"])
            ->orderBy("claimed_at")
            ->get()
            ->map(fn (WorkOrder $workOrder) => $this->summarizeWorkOrder($workOrder));

        return Inertia::render("Technician/WorkOrders/Index", [
            "claimable" => $claimable,
            "myJobs" => $myJobs,
        ]);
    }

    public function claim(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize("claim", $workOrder);

        WorkOrderService::claim($workOrder, $request->user());

        return redirect()
            ->route("technician.work-orders.index")
            ->with("status", "Job claimed.");
    }

    public function complete(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize("complete", $workOrder);

        $validated = $request->validate([
            "resolution_notes" => "nullable|string|max:2000",
        ]);

        WorkOrderService::complete($workOrder, $request->user(), $validated["resolution_notes"] ?? null);

        return redirect()
            ->route("technician.work-orders.index")
            ->with("status", "Job completed.");
    }

    public function dispute(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize("dispute", $workOrder);

        $validated = $request->validate([
            "reason" => "required|string|max:2000",
        ]);

        WorkOrderService::dispute($workOrder, $request->user(), $validated["reason"]);

        return redirect()
            ->route("customer.dashboard")
            ->with("status", "Dispute submitted. This work order is paused pending admin review.");
    }

    protected function summarizeAccount(Account $account): array
    {
        return [
            "id" => $account->id,
            "account_number" => $account->account_number,
            "customer_name" => $account->user->name,
            "zone" => $account->zone,
            "defaulted_at" => $account->defaulted_at?->toDateString(),
            "outstanding_amount" => $account->bills()
                ->whereIn("status", ["overdue", "defaulted"])
                ->sum("amount"),
        ];
    }

    protected function summarizeWorkOrder(WorkOrder $workOrder): array
    {
        return [
            "id" => $workOrder->id,
            "type" => $workOrder->type->value,
            "type_label" => $workOrder->type->label(),
            "status" => $workOrder->status->value,
            "status_label" => $workOrder->status->label(),
            "account_number" => $workOrder->account->account_number,
            "customer_name" => $workOrder->account->user->name,
            "zone" => $workOrder->account->zone,
            "notice_deadline" => $workOrder->notice_deadline?->toDateString(),
            "notice_elapsed" => $workOrder->notice_deadline ? $workOrder->notice_deadline->isPast() : false,
            "dispute_reason" => $workOrder->dispute_reason,
            "resolution_notes" => $workOrder->resolution_notes,
            "created_at" => $workOrder->created_at->toDateString(),
            "source" => $this->summarizeSource($workOrder),
        ];
    }

    protected function summarizeSource(WorkOrder $workOrder): ?array
    {
        return match (true) {
            $workOrder->sourceable instanceof LeakReport => [
                "severity_label" => $workOrder->sourceable->severity->label(),
                "location_notes" => $workOrder->sourceable->location_notes,
                "description" => $workOrder->sourceable->description,
            ],
            $workOrder->sourceable instanceof ServiceRequest => [
                "request_type" => $workOrder->sourceable->type,
                "description" => $workOrder->sourceable->description,
            ],
            default => null,
        };
    }
}
