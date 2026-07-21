<?php

namespace App\Http\Controllers;

use App\Enums\OutageStatus;
use App\Models\Account;
use App\Models\LeakReport;
use App\Models\Outage;
use App\Models\PipeSegment;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole([
            config("roles.admin"),
            config("roles.management"),
            config("roles.technician"),
        ]);

        $accountIds = $isStaff
            ? null
            : Account::where("user_id", $user->id)->pluck("id");

        $leakReports = LeakReport::whereNotNull("latitude")
            ->whereNotNull("longitude")
            ->when(! $isStaff, fn ($query) => $query->whereIn("account_id", $accountIds))
            ->with("account:id,zone")
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (LeakReport $leakReport) => [
                "id" => $leakReport->id,
                "type" => "leak_report",
                "label" => "Leak — " . $leakReport->severity->label(),
                "zone" => $leakReport->zone,
                "lat" => (float) $leakReport->latitude,
                "lng" => (float) $leakReport->longitude,
                "created_at" => $leakReport->created_at->toDateString(),
            ]);

        $serviceRequests = ServiceRequest::whereNotNull("latitude")
            ->whereNotNull("longitude")
            ->when(! $isStaff, fn ($query) => $query->whereIn("account_id", $accountIds))
            ->with("account:id,zone")
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (ServiceRequest $serviceRequest) => [
                "id" => $serviceRequest->id,
                "type" => "service_request",
                "label" => "Service Request — " . str($serviceRequest->type)->replace("_", " ")->title(),
                "zone" => $serviceRequest->zone,
                "lat" => (float) $serviceRequest->latitude,
                "lng" => (float) $serviceRequest->longitude,
                "created_at" => $serviceRequest->created_at->toDateString(),
            ]);

        $outages = Outage::whereIn("status", [OutageStatus::Scheduled, OutageStatus::Active])
            ->get()
            ->map(fn (Outage $outage) => [
                "id" => $outage->id,
                "zone" => $outage->zone,
                "title" => $outage->title,
                "status" => $outage->status->value,
                "status_label" => $outage->status->label(),
            ]);

        return Inertia::render("Map/Index", [
            "pipeSegments" => PipeSegment::forMap(),
            "incidents" => $leakReports->concat($serviceRequests)->values(),
            "outages" => $outages,
            "zones" => config("utility.zones"),
            "zoneCenters" => config("utility.zone_centers"),
            "serviceAreaBounds" => config("utility.service_area_bounds"),
        ]);
    }
}
