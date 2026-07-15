<?php

use App\Models\Account;
use App\Models\User;

it("allows an admin to create a meter with an auto-generated number", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create();

    $response = $this->actingAs($admin)->post("/admin/meters", [
        "account_id" => $account->id,
        "installed_at" => now()->toDateString(),
    ]);

    $response->assertRedirect(route("admin.dashboard"));

    $this->assertDatabaseHas("meters", [
        "account_id" => $account->id,
        "status" => "active",
    ]);

    $meter = \App\Models\Meter::where("account_id", $account->id)->first();
    expect($meter->meter_number)->toStartWith(config("utility.meter_number_prefix"));
});

it("prevents a technician from creating a meter", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create();

    $response = $this->actingAs($technician)->post("/admin/meters", [
        "account_id" => $account->id,
        "installed_at" => now()->toDateString(),
    ]);

    $response->assertForbidden();
});
