<?php

namespace App\Http\Controllers;

use App\Enums\WorkOrderStatus;
use App\Models\MaintenanceSchedule;
use App\Models\Meter;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function index(): Response
    {
        Gate::authorize("scheduleMaintenance", WorkOrder::class);

        $schedules = MaintenanceSchedule::with(["account.user", "meter", "workOrder"])
            ->orderBy("scheduled_for")
            ->get()
            ->map(fn (MaintenanceSchedule $schedule) => [
                "id" => $schedule->id,
                "meter_number" => $schedule->meter->meter_number,
                "account_number" => $schedule->account->account_number,
                "customer_name" => $schedule->account->user->name,
                "zone" => $schedule->account->zone,
                "scheduled_for" => $schedule->scheduled_for->toDateString(),
                "description" => $schedule->description,
                "status" => $schedule->workOrder?->status->value,
                "status_label" => $schedule->workOrder?->status->label(),
                "is_overdue" => $schedule->scheduled_for->isPast()
                    && $schedule->workOrder?->status !== WorkOrderStatus::Completed
                    && $schedule->workOrder?->status !== WorkOrderStatus::Cancelled,
            ]);

        return Inertia::render("Admin/Maintenance/Index", [
            "schedules" => $schedules,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize("scheduleMaintenance", WorkOrder::class);

        return Inertia::render("Admin/Maintenance/Create", [
            "meters" => Meter::with("account.user")->get()->map(fn (Meter $meter) => [
                "id" => $meter->id,
                "meter_number" => $meter->meter_number,
                "account_number" => $meter->account->account_number,
                "customer_name" => $meter->account->user->name,
                "zone" => $meter->account->zone,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize("scheduleMaintenance", WorkOrder::class);

        $validated = $request->validate([
            "meter_id" => "required|exists:meters,id",
            "scheduled_for" => "required|date|after_or_equal:today",
            "description" => "required|string|max:2000",
        ]);

        $meter = Meter::findOrFail($validated["meter_id"]);

        $schedule = MaintenanceSchedule::create([
            "account_id" => $meter->account_id,
            "meter_id" => $meter->id,
            "created_by" => $request->user()->id,
            "scheduled_for" => $validated["scheduled_for"],
            "description" => $validated["description"],
        ]);

        WorkOrderService::fromMaintenanceSchedule($schedule);

        return redirect()
            ->route("admin.maintenance.index")
            ->with("status", "Maintenance scheduled.");
    }
}
