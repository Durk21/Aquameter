<?php

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;
use App\Models\WorkOrder;

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

it("dispatches a claimable work order to technicians when an admin approves a complaint", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Meter not registering usage",
        "description" => "The meter dial hasn't moved in two weeks.",
        "status" => "submitted",
    ]);

    $response = $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", [
        "status" => "approved",
    ]);

    $response->assertRedirect(route("admin.complaints.index"));

    $workOrder = WorkOrder::where("sourceable_type", Complaint::class)
        ->where("sourceable_id", $complaint->id)
        ->first();

    expect($workOrder)->not->toBeNull();
    expect($workOrder->type)->toBe(WorkOrderType::ComplaintFollowUp);
    expect($workOrder->status)->toBe(WorkOrderStatus::Approved);

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Claimed);
    expect($workOrder->fresh()->assigned_to)->toBe($technician->id);
});

it("renders the admin and technician work order lists after a complaint is approved", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Meter not registering usage",
        "description" => "The meter dial hasn't moved in two weeks.",
        "status" => "submitted",
    ]);

    $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", ["status" => "approved"]);

    $this->actingAs($admin)->get("/admin/work-orders")->assertOk();
    $this->actingAs($technician)->get("/technician/work-orders")->assertOk();
});

it("does not dispatch a second work order if a complaint is approved more than once", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Meter not registering usage",
        "description" => "The meter dial hasn't moved in two weeks.",
        "status" => "submitted",
    ]);

    $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", ["status" => "approved"]);
    $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", ["status" => "approved"]);

    $count = WorkOrder::where("sourceable_type", Complaint::class)
        ->where("sourceable_id", $complaint->id)
        ->count();

    expect($count)->toBe(1);
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
