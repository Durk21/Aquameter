<?php

use App\Models\Meter;
use App\Models\Account;
use App\Models\User;
use App\Services\MeterAnomalyDetector;

it("does not flag the first reading as anomalous", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    expect(MeterAnomalyDetector::isAnomalous($meter, 100))->toBeFalse();
});

it("flags a reading that is far above the historical average", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 100,
        "reading_date" => now()->subDays(60),
    ]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 110,
        "reading_date" => now()->subDays(30),
    ]);

    expect(MeterAnomalyDetector::isAnomalous($meter, 500))->toBeTrue();
});

it("does not flag a normal incremental reading", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 100,
        "reading_date" => now()->subDays(60),
    ]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 110,
        "reading_date" => now()->subDays(30),
    ]);

    expect(MeterAnomalyDetector::isAnomalous($meter, 120))->toBeFalse();
});
