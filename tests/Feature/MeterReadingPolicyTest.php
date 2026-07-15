<?php

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;

it("allows a technician to create a meter reading", function () {
    $user = User::factory()->create();
    $user->assignRole(config("roles.technician"));

    expect($user->can("create", \App\Models\MeterReading::class))->toBeTrue();
});

it("prevents a customer from creating a meter reading", function () {
    $user = User::factory()->create();
    $user->assignRole(config("roles.customer"));

    expect($user->can("create", \App\Models\MeterReading::class))->toBeFalse();
});

it("prevents anyone from updating a meter reading", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    expect($technician->can("update", $reading))->toBeFalse();
});

it("allows the account owner to view their own reading", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    expect($customer->can("view", $reading))->toBeTrue();
});

it("prevents a different customer from viewing someone else''s reading", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $owner->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    expect($otherCustomer->can("view", $reading))->toBeFalse();
});
