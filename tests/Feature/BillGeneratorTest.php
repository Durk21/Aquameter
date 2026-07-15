<?php

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;
use App\Services\BillGenerator;

it("does not generate a bill for the first reading on a meter", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $reading = $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = BillGenerator::generateFor($reading);

    expect($bill)->toBeNull();
    $this->assertDatabaseCount("bills", 0);
});

it("generates a correctly calculated bill from the delta between two readings", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 100,
        "reading_date" => now()->subDays(30),
    ]);

    $secondReading = $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 130,
        "reading_date" => now(),
    ]);

    $bill = BillGenerator::generateFor($secondReading);

    expect($bill)->not->toBeNull();
    expect((float) $bill->units_consumed)->toBe(30.0);
    expect((float) $bill->rate_applied)->toBe((float) config("utility.rate_per_unit"));
    expect((float) $bill->amount)->toBe(30.0 * (float) config("utility.rate_per_unit"));
    expect($bill->due_date->toDateString())->toBe(now()->addDays(config("utility.bill_due_days"))->toDateString());
});

it("never generates a negative bill if a reading appears lower than the previous one", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 100,
        "reading_date" => now()->subDays(30),
    ]);

    $secondReading = $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 90,
        "reading_date" => now(),
    ]);

    $bill = BillGenerator::generateFor($secondReading);

    expect((float) $bill->units_consumed)->toBe(0.0);
    expect((float) $bill->amount)->toBe(0.0);
});
