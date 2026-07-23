<?php

use App\Models\User;

it("allows an admin to create a technician account with a zone", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $zone = config("utility.zones")[0];

    $response = $this->actingAs($admin)->post("/admin/staff", [
        "name" => "Tim Technician",
        "email" => "tim@aquameter.test",
        "password" => "password123",
        "password_confirmation" => "password123",
        "role" => "technician",
        "zone" => $zone,
    ]);

    $response->assertRedirect(route("admin.staff.index"));

    $staff = User::where("email", "tim@aquameter.test")->first();
    expect($staff)->not->toBeNull();
    expect($staff->hasRole("technician"))->toBeTrue();
    expect($staff->zone)->toBe($zone);
    expect($staff->email_verified_at)->not->toBeNull();
});

it("ignores a submitted zone for a non-technician role", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->post("/admin/staff", [
        "name" => "Mandy Management",
        "email" => "mandy@aquameter.test",
        "password" => "password123",
        "password_confirmation" => "password123",
        "role" => "management",
        "zone" => config("utility.zones")[0],
    ]);

    $response->assertRedirect(route("admin.staff.index"));

    $staff = User::where("email", "mandy@aquameter.test")->first();
    expect($staff->hasRole("management"))->toBeTrue();
    expect($staff->zone)->toBeNull();
});

it("rejects an unassignable role", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->post("/admin/staff", [
        "name" => "Someone",
        "email" => "someone@aquameter.test",
        "password" => "password123",
        "password_confirmation" => "password123",
        "role" => "customer",
    ]);

    $response->assertSessionHasErrors("role");
    $this->assertDatabaseMissing("users", ["email" => "someone@aquameter.test"]);
});

it("prevents a non-admin from creating a staff account", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $response = $this->actingAs($technician)->post("/admin/staff", [
        "name" => "Someone",
        "email" => "someone@aquameter.test",
        "password" => "password123",
        "password_confirmation" => "password123",
        "role" => "admin",
    ]);

    $response->assertForbidden();
});
