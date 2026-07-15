<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;

it("shows a customer only their own bills", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $ownAccount = Account::factory()->create(["user_id" => $owner->id]);
    $ownMeter = Meter::factory()->create(["account_id" => $ownAccount->id]);
    $ownReading = $ownMeter->readings()->create([
        "recorded_by" => $owner->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    Bill::create([
        "account_id" => $ownAccount->id,
        "meter_reading_id" => $ownReading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 50 * config("utility.rate_per_unit"),
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    $otherAccount = Account::factory()->create(["user_id" => $otherCustomer->id]);
    $otherMeter = Meter::factory()->create(["account_id" => $otherAccount->id]);
    $otherReading = $otherMeter->readings()->create([
        "recorded_by" => $otherCustomer->id,
        "reading_value" => 999,
        "reading_date" => now(),
    ]);

    Bill::create([
        "account_id" => $otherAccount->id,
        "meter_reading_id" => $otherReading->id,
        "previous_reading_value" => 900,
        "current_reading_value" => 999,
        "units_consumed" => 99,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 99 * config("utility.rate_per_unit"),
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    $response = $this->actingAs($owner)->get("/customer/bills");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component("Customer/Bills/Index")
        ->has("bills", 1)
        ->where("bills.0.units_consumed", "50.00")
    );
});
