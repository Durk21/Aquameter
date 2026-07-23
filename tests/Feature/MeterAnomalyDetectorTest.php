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

it("explains a reading flagged for exceeding the historical average", function () {
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

    $flagged = $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 500,
        "reading_date" => now(),
        "is_anomalous" => true,
    ]);

    $explanation = MeterAnomalyDetector::explain($flagged);

    expect($explanation)->toContain("390")
        ->toContain("typical average of 10")
        ->toContain("3x");
});

it("explains a reading flagged for being lower than the previous one", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 200,
        "reading_date" => now()->subDays(30),
    ]);

    $flagged = $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 50,
        "reading_date" => now(),
        "is_anomalous" => true,
    ]);

    $explanation = MeterAnomalyDetector::explain($flagged);

    expect($explanation)->toContain("lower than the previous reading")
        ->toContain("meter reset");
});

it("explains a reading flagged as the meter's first reading", function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $flagged = $meter->readings()->create([
        "recorded_by" => $user->id,
        "reading_value" => 100,
        "reading_date" => now(),
        "is_anomalous" => true,
    ]);

    expect(MeterAnomalyDetector::explain($flagged))->toContain("nothing to compare it against");
});
