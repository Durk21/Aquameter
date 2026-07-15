<?php

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;

it("allows a technician to record a meter reading", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $response = $this->actingAs($technician)->post("/technician/meter-readings", [
        "meter_id" => $meter->id,
        "reading_value" => 150,
        "reading_date" => now()->toDateString(),
    ]);

    $response->assertRedirect(route("technician.dashboard"));

    $this->assertDatabaseHas("meter_readings", [
        "meter_id" => $meter->id,
        "recorded_by" => $technician->id,
        "is_anomalous" => false,
    ]);
});

it("flags an anomalous reading correctly through the full request cycle", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now()->subDays(60),
    ]);

    $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 110,
        "reading_date" => now()->subDays(30),
    ]);

    $response = $this->actingAs($technician)->post("/technician/meter-readings", [
        "meter_id" => $meter->id,
        "reading_value" => 800,
        "reading_date" => now()->toDateString(),
    ]);

    $response->assertRedirect(route("technician.dashboard"));

    $this->assertDatabaseHas("meter_readings", [
        "meter_id" => $meter->id,
        "reading_value" => 800,
        "is_anomalous" => true,
    ]);
});

it("prevents a customer from recording a meter reading", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $response = $this->actingAs($customer)->post("/technician/meter-readings", [
        "meter_id" => $meter->id,
        "reading_value" => 150,
        "reading_date" => now()->toDateString(),
    ]);

    $response->assertForbidden();
});
