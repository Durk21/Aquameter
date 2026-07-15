<?php

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;

it("shows a customer only their own account''s readings", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $ownAccount = Account::factory()->create(["user_id" => $owner->id]);
    $ownMeter = Meter::factory()->create(["account_id" => $ownAccount->id]);
    $ownMeter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 120,
        "reading_date" => now(),
    ]);

    $otherAccount = Account::factory()->create(["user_id" => $otherCustomer->id]);
    $otherMeter = Meter::factory()->create(["account_id" => $otherAccount->id]);
    $otherMeter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 999,
        "reading_date" => now(),
    ]);

    $response = $this->actingAs($owner)->get("/customer/meter-readings");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component("Customer/MeterReadings/Index")
        ->has("readings", 1)
        ->where("readings.0.reading_value", "120.00")
    );
});

it("prevents an unauthenticated user from viewing meter readings", function () {
    $response = $this->get("/customer/meter-readings");

    $response->assertRedirect(route("login"));
});
