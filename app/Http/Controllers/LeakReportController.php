<?php

namespace App\Http\Controllers;

use App\Enums\LeakSeverity;
use App\Models\Account;
use App\Models\LeakReport;
use App\Models\Meter;
use App\Services\PhotoUploadService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeakReportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize("viewAny", LeakReport::class);

        $accountIds = Account::where("user_id", $request->user()->id)->pluck("id");

        $leakReports = LeakReport::whereIn("account_id", $accountIds)
            ->with(["workOrder", "photos"])
            ->orderByDesc("created_at")
            ->get()
            ->map(fn (LeakReport $leakReport) => $this->summarize($leakReport));

        return Inertia::render("Customer/LeakReports/Index", [
            "leakReports" => $leakReports,
        ]);
    }

    public function create(Request $request): Response
    {
        $account = Account::where("user_id", $request->user()->id)->firstOrFail();

        Gate::authorize("createFor", [LeakReport::class, $account]);

        return Inertia::render("Customer/LeakReports/Create", [
            "meters" => Meter::where("account_id", $account->id)->get(["id", "meter_number"]),
            "zones" => config("utility.zones"),
            "severities" => array_map(
                fn (LeakSeverity $severity) => ["value" => $severity->value, "label" => $severity->label()],
                LeakSeverity::cases(),
            ),
            "defaultZone" => $account->zone,
            "serviceAreaBounds" => config("utility.service_area_bounds"),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::where("user_id", $request->user()->id)->firstOrFail();

        Gate::authorize("createFor", [LeakReport::class, $account]);

        $bounds = config("utility.service_area_bounds");

        $validated = $request->validate(array_merge([
            "meter_id" => "nullable|exists:meters,id",
            "severity" => ["required", Rule::in(array_column(LeakSeverity::cases(), "value"))],
            "zone" => ["required", Rule::in(config("utility.zones"))],
            "location_notes" => "nullable|string|max:255",
            "latitude" => "nullable|numeric|between:{$bounds['min_lat']},{$bounds['max_lat']}",
            "longitude" => "nullable|numeric|between:{$bounds['min_lng']},{$bounds['max_lng']}",
            "description" => "required|string|max:2000",
        ], PhotoUploadService::validationRules()));

        if (! empty($validated["meter_id"])) {
            $meter = Meter::findOrFail($validated["meter_id"]);
            if ($meter->account_id !== $account->id) {
                abort(403);
            }
        }

        $leakReport = LeakReport::create([
            "account_id" => $account->id,
            "reported_by" => $request->user()->id,
            "meter_id" => $validated["meter_id"] ?? null,
            "severity" => $validated["severity"],
            "zone" => $validated["zone"],
            "location_notes" => $validated["location_notes"] ?? null,
            "latitude" => $validated["latitude"] ?? null,
            "longitude" => $validated["longitude"] ?? null,
            "description" => $validated["description"],
        ]);

        PhotoUploadService::store($leakReport, $request->file("photos", []), $request->user());

        WorkOrderService::fromLeakReport($leakReport);

        return redirect()
            ->route("customer.leak-reports.index")
            ->with("status", "Leak reported. A technician has been dispatched to the queue.");
    }

    protected function summarize(LeakReport $leakReport): array
    {
        return [
            "id" => $leakReport->id,
            "severity" => $leakReport->severity->value,
            "severity_label" => $leakReport->severity->label(),
            "zone" => $leakReport->zone,
            "location_notes" => $leakReport->location_notes,
            "description" => $leakReport->description,
            "status" => $leakReport->workOrder?->status->value,
            "status_label" => $leakReport->workOrder?->status->label(),
            "created_at" => $leakReport->created_at->toDateString(),
            "photos" => $leakReport->photos->map(fn ($photo) => [
                "id" => $photo->id,
                "url" => route("photos.show", $photo->id),
            ]),
        ];
    }
}
