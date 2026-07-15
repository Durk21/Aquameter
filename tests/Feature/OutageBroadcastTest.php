<?php

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Outage;
use App\Models\User;
use App\Notifications\OutageBroadcast;
use Illuminate\Support\Facades\Notification;

it("lets admin broadcast a zone-scoped outage and notifies only customers in that zone", function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $zones = config("utility.zones");

    $inZoneCustomer = User::factory()->create();
    $inZoneCustomer->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $inZoneCustomer->id, "zone" => $zones[0]]);

    $otherZoneCustomer = User::factory()->create();
    $otherZoneCustomer->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $otherZoneCustomer->id, "zone" => $zones[1]]);

    $response = $this->actingAs($admin)->post("/outages", [
        "zone" => $zones[0],
        "title" => "Pipe repair",
        "description" => "Water will be off for maintenance.",
        "starts_at" => now()->addDay()->format("Y-m-d\TH:i"),
    ]);

    $response->assertRedirect(route("outages.index"));
    $this->assertDatabaseHas("outages", ["title" => "Pipe repair", "zone" => $zones[0], "status" => "scheduled"]);

    Notification::assertSentTo($inZoneCustomer, OutageBroadcast::class);
    Notification::assertNotSentTo($otherZoneCustomer, OutageBroadcast::class);
});

it("notifies every customer when an outage has no zone", function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $zones = config("utility.zones");

    $customerA = User::factory()->create();
    $customerA->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $customerA->id, "zone" => $zones[0]]);

    $customerB = User::factory()->create();
    $customerB->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $customerB->id, "zone" => $zones[1]]);

    $this->actingAs($admin)->post("/outages", [
        "zone" => "",
        "title" => "Citywide flushing",
        "description" => "Routine flushing across all zones.",
        "starts_at" => now()->format("Y-m-d\TH:i"),
    ]);

    $this->assertDatabaseHas("outages", ["title" => "Citywide flushing", "zone" => null, "status" => "active"]);

    Notification::assertSentTo($customerA, OutageBroadcast::class);
    Notification::assertSentTo($customerB, OutageBroadcast::class);
});

it("marks a future outage as scheduled and a starting-now outage as active", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $this->actingAs($admin)->post("/outages", [
        "zone" => "",
        "title" => "Future work",
        "description" => "Not started yet.",
        "starts_at" => now()->addWeek()->format("Y-m-d\TH:i"),
    ]);

    $this->assertDatabaseHas("outages", ["title" => "Future work", "status" => "scheduled"]);
});

it("lets admin mark an outage resolved and records an activity log entry", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $outage = Outage::create([
        "zone" => null,
        "title" => "Ongoing repair",
        "description" => "Test.",
        "status" => "active",
        "starts_at" => now(),
        "created_by" => $admin->id,
    ]);

    $response = $this->actingAs($admin)->patch("/outages/{$outage->id}/resolve");

    $response->assertRedirect(route("outages.index"));
    $outage->refresh();
    expect($outage->status->value)->toBe("resolved");
    expect($outage->resolved_at)->not->toBeNull();

    $log = ActivityLog::first();
    expect($log->subject_type)->toBe(Outage::class);
    expect($log->from_status)->toBe("active");
    expect($log->to_status)->toBe("resolved");
});

it("shows a customer only outages affecting their own zone or all zones", function () {
    $zones = config("utility.zones");

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $customer->id, "zone" => $zones[0]]);

    $admin = User::factory()->create();

    Outage::create([
        "zone" => $zones[0],
        "title" => "My zone",
        "description" => "Test.",
        "status" => "active",
        "starts_at" => now(),
        "created_by" => $admin->id,
    ]);

    Outage::create([
        "zone" => $zones[1],
        "title" => "Other zone",
        "description" => "Test.",
        "status" => "active",
        "starts_at" => now(),
        "created_by" => $admin->id,
    ]);

    Outage::create([
        "zone" => null,
        "title" => "All zones",
        "description" => "Test.",
        "status" => "active",
        "starts_at" => now(),
        "created_by" => $admin->id,
    ]);

    $response = $this->actingAs($customer)->get("/customer/outages");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component("Customer/Outages/Index")
        ->has("outages", 2));
});

it("restricts outage management to admin and management", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $this->actingAs($admin)->get("/outages")->assertOk();
    $this->actingAs($management)->get("/outages")->assertOk();
    $this->actingAs($customer)->get("/outages")->assertForbidden();
});

it("rejects an outage where the end time is before the start time", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->post("/outages", [
        "zone" => "",
        "title" => "Bad dates",
        "description" => "Test.",
        "starts_at" => now()->addDay()->format("Y-m-d\TH:i"),
        "ends_at" => now()->format("Y-m-d\TH:i"),
    ]);

    $response->assertSessionHasErrors("ends_at");
});
