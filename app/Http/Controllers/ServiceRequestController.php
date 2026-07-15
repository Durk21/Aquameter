<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ServiceRequest;
use App\Services\PhotoUploadService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize("viewAny", ServiceRequest::class);

        $accountIds = Account::where("user_id", $request->user()->id)->pluck("id");

        $serviceRequests = ServiceRequest::whereIn("account_id", $accountIds)
            ->with(["workOrder", "photos"])
            ->orderByDesc("created_at")
            ->get()
            ->map(fn (ServiceRequest $serviceRequest) => $this->summarize($serviceRequest));

        return Inertia::render("Customer/ServiceRequests/Index", [
            "serviceRequests" => $serviceRequests,
        ]);
    }

    public function create(Request $request): Response
    {
        $account = Account::where("user_id", $request->user()->id)->firstOrFail();

        Gate::authorize("createFor", [ServiceRequest::class, $account]);

        return Inertia::render("Customer/ServiceRequests/Create", [
            "types" => config("utility.service_request_types"),
            "defaultZone" => $account->zone,
            "zones" => config("utility.zones"),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::where("user_id", $request->user()->id)->firstOrFail();

        Gate::authorize("createFor", [ServiceRequest::class, $account]);

        $validated = $request->validate(array_merge([
            "type" => ["required", Rule::in(config("utility.service_request_types"))],
            "zone" => ["required", Rule::in(config("utility.zones"))],
            "description" => "required|string|max:2000",
        ], PhotoUploadService::validationRules()));

        $serviceRequest = ServiceRequest::create([
            "account_id" => $account->id,
            "requested_by" => $request->user()->id,
            "type" => $validated["type"],
            "zone" => $validated["zone"],
            "description" => $validated["description"],
        ]);

        PhotoUploadService::store($serviceRequest, $request->file("photos", []), $request->user());

        WorkOrderService::fromServiceRequest($serviceRequest);

        return redirect()
            ->route("customer.service-requests.index")
            ->with("status", "Service request submitted. A technician has been dispatched to the queue.");
    }

    protected function summarize(ServiceRequest $serviceRequest): array
    {
        return [
            "id" => $serviceRequest->id,
            "type" => $serviceRequest->type,
            "zone" => $serviceRequest->zone,
            "description" => $serviceRequest->description,
            "status" => $serviceRequest->workOrder?->status->value,
            "status_label" => $serviceRequest->workOrder?->status->label(),
            "created_at" => $serviceRequest->created_at->toDateString(),
            "photos" => $serviceRequest->photos->map(fn ($photo) => [
                "id" => $photo->id,
                "url" => route("photos.show", $photo->id),
            ]),
        ];
    }
}
