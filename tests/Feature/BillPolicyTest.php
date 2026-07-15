<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;

it("allows the account owner to view their own bill", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $customer->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = Bill::create([
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

    expect($customer->can("view", $bill))->toBeTrue();
});

it("prevents a different customer from viewing someone else''s bill", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $owner->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $owner->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = Bill::create([
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

    expect($otherCustomer->can("view", $bill))->toBeFalse();
});
