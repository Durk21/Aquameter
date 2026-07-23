<?php

namespace App\Http\Controllers;

use App\Enums\OutageStatus;
use App\Models\Account;
use App\Models\Outage;
use App\Models\User;
use App\Notifications\OutageBroadcast;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OutageController extends Controller
{
    public function index(): Response
    {
        Gate::authorize("manage", Outage::class);

        $outages = Outage::with("createdBy")
            ->orderByDesc("starts_at")
            ->get()
            ->map(fn (Outage $outage) => $this->present($outage));

        return Inertia::render("Admin/Outages/Index", [
            "outages" => $outages,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize("manage", Outage::class);

        return Inertia::render("Admin/Outages/Create", [
            "zones" => config("utility.zones"),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize("manage", Outage::class);

        $validated = $request->validate([
            "zone" => ["nullable", "string", Rule::in(config("utility.zones"))],
            "title" => "required|string|max:255",
            "description" => "required|string|max:2000",
            "starts_at" => "required|date",
            "ends_at" => "nullable|date|after:starts_at",
        ]);

        $startsAt = Carbon::parse($validated["starts_at"]);

        $outage = Outage::create([
            "zone" => $validated["zone"] ?: null,
            "title" => $validated["title"],
            "description" => $validated["description"],
            "status" => $startsAt->isFuture() ? OutageStatus::Scheduled : OutageStatus::Active,
            "starts_at" => $startsAt,
            "ends_at" => $validated["ends_at"] ?? null,
            "created_by" => $request->user()->id,
        ]);

        $this->notifyAffectedCustomers($outage);

        return redirect()
            ->route("outages.index")
            ->with("status", "Outage notice broadcast.");
    }

    public function resolve(Outage $outage): RedirectResponse
    {
        Gate::authorize("manage", Outage::class);

        $outage->status = OutageStatus::Resolved;
        $outage->resolved_at = now();
        $outage->save();

        return redirect()
            ->route("outages.index")
            ->with("status", "Outage marked resolved.");
    }

    public function customerIndex(Request $request): Response
    {
        $account = Account::where("user_id", $request->user()->id)->first();

        $outages = Outage::query()
            ->when($account, fn ($query) => $query->where(fn ($query) => $query
                ->whereNull("zone")
                ->orWhere("zone", $account->zone)))
            ->when(! $account, fn ($query) => $query->whereNull("zone"))
            ->orderByDesc("starts_at")
            ->limit(50)
            ->get()
            ->map(fn (Outage $outage) => $this->present($outage));

        return Inertia::render("Customer/Outages/Index", [
            "outages" => $outages,
        ]);
    }

    protected function notifyAffectedCustomers(Outage $outage): void
    {
        $customerIds = Account::when($outage->zone, fn ($query) => $query->where("zone", $outage->zone))
            ->pluck("user_id");

        $customers = User::whereIn("id", $customerIds)->get();

        if ($customers->isNotEmpty()) {
            Notification::send($customers, new OutageBroadcast($outage));
        }
    }

    protected function present(Outage $outage): array
    {
        return [
            "id" => $outage->id,
            "zone" => $outage->zone,
            "title" => $outage->title,
            "description" => $outage->description,
            "status" => $outage->status->value,
            "status_label" => $outage->status->label(),
            "starts_at" => $outage->starts_at->toDateTimeString(),
            "ends_at" => $outage->ends_at?->toDateTimeString(),
            "created_by" => $outage->createdBy?->name,
            "resolved_at" => $outage->resolved_at?->toDateTimeString(),
        ];
    }
}
