<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Meter;
use App\Services\MeterNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MeterController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize("create", Meter::class);

        return Inertia::render("Meters/Create", [
            "accounts" => Account::with("user")->get()->map(function (Account $account) {
                return [
                    "id" => $account->id,
                    "account_number" => $account->account_number,
                    "customer_name" => $account->user->name,
                    "zone" => $account->zone,
                    "has_meter" => $account->meters()->exists(),
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize("create", Meter::class);

        $validated = $request->validate([
            "account_id" => "required|exists:accounts,id",
            "installed_at" => "required|date|before_or_equal:today",
        ]);

        Meter::create([
            "account_id" => $validated["account_id"],
            "meter_number" => MeterNumberGenerator::generate(),
            "status" => "active",
            "installed_at" => $validated["installed_at"],
        ]);

        return redirect()
            ->route("admin.dashboard")
            ->with("status", "Meter installed and registered successfully.");
    }
}
