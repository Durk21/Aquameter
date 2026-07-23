<?php

use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;
use App\Models\WorkOrder;

it("surfaces customer phone to admin on the work orders pipeline", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create(["phone" => "0711000111"]);
    $workOrder = WorkOrder::factory()->create(["account_id" => $account->id]);

    $response = $this->actingAs($admin)->get("/admin/work-orders");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("pipeline.data.0.phone", "0711000111"));
});

it("surfaces customer phone to a technician's claimed jobs", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["phone" => "0722000222"]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => "claimed",
        "assigned_to" => $technician->id,
    ]);

    $response = $this->actingAs($technician)->get("/technician/work-orders");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("myJobs.0.phone", "0722000222"));
});

it("surfaces customer phone and alternate email to admin reviewing a complaint", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create(["phone" => "0733000333", "alternate_email" => "alt@example.com"]);
    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $account->user_id,
        "subject" => "Test",
        "description" => "Test complaint.",
        "status" => "submitted",
    ]);

    $response = $this->actingAs($admin)->get("/admin/complaints/{$complaint->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("complaint.phone", "0733000333")
        ->where("complaint.alternate_email", "alt@example.com"));
});

it("surfaces customer phone to admin on the bills list", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create(["phone" => "0744000444"]);
    $meter = \App\Models\Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $account->user_id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);
    \App\Models\Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => 150,
        "amount" => 7500,
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    $response = $this->actingAs($admin)->get("/admin/bills");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("bills.data.0.phone", "0744000444"));
});
