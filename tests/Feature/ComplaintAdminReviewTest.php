<?php

use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;

it("allows an admin to view the complaints list", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->get("/admin/complaints");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component("Admin/Complaints/Index"));
});

it("prevents a customer from viewing the admin complaints list", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $response = $this->actingAs($customer)->get("/admin/complaints");

    $response->assertForbidden();
});

it("allows an admin to resolve a complaint with notes", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Bill too high",
        "description" => "Usage looks wrong.",
        "status" => "submitted",
    ]);

    $response = $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", [
        "status" => "resolved",
        "resolution_notes" => "Verified reading was correct, but rate was miscalculated. Adjusted.",
    ]);

    $response->assertRedirect(route("admin.complaints.index"));

    $this->assertDatabaseHas("complaints", [
        "id" => $complaint->id,
        "status" => "resolved",
        "resolved_by" => $admin->id,
    ]);

    expect($complaint->fresh()->resolved_at)->not->toBeNull();
});

it("prevents a customer from resolving their own complaint", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Bill too high",
        "description" => "Usage looks wrong.",
        "status" => "submitted",
    ]);

    $response = $this->actingAs($customer)->patch("/admin/complaints/{$complaint->id}", [
        "status" => "resolved",
        "resolution_notes" => "Trying to resolve my own complaint.",
    ]);

    $response->assertForbidden();
});
