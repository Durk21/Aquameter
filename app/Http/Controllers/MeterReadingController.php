<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\BillGenerator;
use App\Services\MeterAnomalyDetector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MeterReadingController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize("create", MeterReading::class);

        return Inertia::render("MeterReadings/Create", [
            "meters" => Meter::with(["account.bills" => function ($query) {
                $query->where("status", "!=", "paid");
            }])->get()->map(function (Meter $meter) {
                $unpaidBills = $meter->account->bills;

                return [
                    "id" => $meter->id,
                    "meter_number" => $meter->meter_number,
                    "account_number" => $meter->account->account_number,
                    "zone" => $meter->account->zone,
                    "has_unpaid_bill" => $unpaidBills->isNotEmpty(),
                    "outstanding_amount" => $unpaidBills->sum("amount"),
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize("create", MeterReading::class);

        $validated = $request->validate([
            "meter_id" => "required|exists:meters,id",
            "reading_value" => "required|numeric|min:0",
            "reading_date" => "required|date|before_or_equal:today",
        ]);

        $meter = Meter::findOrFail($validated["meter_id"]);

        $isAnomalous = MeterAnomalyDetector::isAnomalous($meter, (float) $validated["reading_value"]);

        $reading = MeterReading::create([
            "meter_id" => $meter->id,
            "recorded_by" => $request->user()->id,
            "reading_value" => $validated["reading_value"],
            "reading_date" => $validated["reading_date"],
            "is_anomalous" => $isAnomalous,
        ]);

        BillGenerator::generateFor($reading);

        return redirect()
            ->route("technician.dashboard")
            ->with("status", $isAnomalous
                ? "Reading recorded — flagged as anomalous for review."
                : "Reading recorded successfully.");
    }

    public function index(Request $request): Response
    {
        Gate::authorize("viewAny", MeterReading::class);

        $accountIds = Account::where("user_id", $request->user()->id)->pluck("id");
        $meterIds = Meter::whereIn("account_id", $accountIds)->pluck("id");

        $readings = MeterReading::whereIn("meter_id", $meterIds)
            ->with("meter")
            ->orderByDesc("reading_date")
            ->get()
            ->map(function (MeterReading $reading) {
                return [
                    "id" => $reading->id,
                    "meter_number" => $reading->meter->meter_number,
                    "reading_value" => $reading->reading_value,
                    "reading_date" => $reading->reading_date->toDateString(),
                    "is_anomalous" => $reading->is_anomalous,
                ];
            });

        return Inertia::render("Customer/MeterReadings/Index", [
            "readings" => $readings,
        ]);
    }
}
