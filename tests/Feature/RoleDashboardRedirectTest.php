<?php

use App\Models\User;

it("redirects a customer to the customer dashboard", function () {
    $user = User::factory()->create(["email_verified_at" => now()]);
    $user->assignRole(config("roles.customer"));

    $response = $this->actingAs($user)->get("/dashboard");

    $response->assertRedirect(route("customer.dashboard"));
});

it("redirects a technician to the technician dashboard", function () {
    $user = User::factory()->create(["email_verified_at" => now()]);
    $user->assignRole(config("roles.technician"));

    $response = $this->actingAs($user)->get("/dashboard");

    $response->assertRedirect(route("technician.dashboard"));
});

it("blocks a customer from accessing the admin dashboard", function () {
    $user = User::factory()->create(["email_verified_at" => now()]);
    $user->assignRole(config("roles.customer"));

    $response = $this->actingAs($user)->get("/admin/dashboard");

    $response->assertForbidden();
});
