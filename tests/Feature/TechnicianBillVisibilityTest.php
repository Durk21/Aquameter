<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;

it("flags a meter as having an unpaid bill for the technician", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $customer = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 50 * config("utility.rate_per_unit"),
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    $response = $this->actingAs($technician)->get("/technician/meter-readings/create");

    $response->assertInertia(fn ($page) => $page
        ->where("meters.0.has_unpaid_bill", true)
        ->where("meters.0.outstanding_amount", 7500)
    );
});

it("does not flag a meter with no bills or fully paid bills", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $response = $this->actingAs($technician)->get("/technician/meter-readings/create");

    $response->assertInertia(fn ($page) => $page
        ->where("meters.0.has_unpaid_bill", false)
    );
});
