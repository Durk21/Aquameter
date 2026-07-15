<?php

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;

it("automatically generates a bill when a technician submits a second reading", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now()->subDays(30),
    ]);

    $response = $this->actingAs($technician)->post("/technician/meter-readings", [
        "meter_id" => $meter->id,
        "reading_value" => 145,
        "reading_date" => now()->toDateString(),
    ]);

    $response->assertRedirect(route("technician.dashboard"));

    $this->assertDatabaseHas("bills", [
        "account_id" => $account->id,
        "units_consumed" => 45,
    ]);
});

it("does not generate a bill for a meter''s very first reading submitted through the route", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $response = $this->actingAs($technician)->post("/technician/meter-readings", [
        "meter_id" => $meter->id,
        "reading_value" => 100,
        "reading_date" => now()->toDateString(),
    ]);

    $response->assertRedirect(route("technician.dashboard"));

    $this->assertDatabaseCount("bills", 0);
});
