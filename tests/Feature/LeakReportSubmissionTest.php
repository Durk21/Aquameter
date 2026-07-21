<?php

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\LeakReport;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\LeakReportSubmitted;
use Illuminate\Support\Facades\Notification;

it("notifies every admin when a customer reports a leak", function () {
    Notification::fake();

    $adminOne = User::factory()->create();
    $adminOne->assignRole(config("roles.admin"));

    $adminTwo = User::factory()->create();
    $adminTwo->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "high",
        "zone" => $account->zone,
        "description" => "Water bubbling up from the road surface.",
    ]);

    Notification::assertSentTo($adminOne, LeakReportSubmitted::class);
    Notification::assertSentTo($adminTwo, LeakReportSubmitted::class);
    Notification::assertNotSentTo($technician, LeakReportSubmitted::class);
    Notification::assertNotSentTo($customer, LeakReportSubmitted::class);
});

it("allows a customer to report a leak on their own account, opening a work order", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "high",
        "zone" => $account->zone,
        "location_notes" => "Near the main gate",
        "description" => "Water bubbling up from the road surface.",
    ]);

    $response->assertRedirect(route("customer.leak-reports.index"));

    $this->assertDatabaseHas("leak_reports", [
        "account_id" => $account->id,
        "reported_by" => $customer->id,
        "severity" => "high",
    ]);

    $leakReport = LeakReport::first();
    $workOrder = WorkOrder::where("sourceable_type", LeakReport::class)
        ->where("sourceable_id", $leakReport->id)
        ->first();

    expect($workOrder)->not->toBeNull();
    expect($workOrder->type)->toBe(WorkOrderType::LeakRepair);
    expect($workOrder->status)->toBe(WorkOrderStatus::Approved);
    expect($workOrder->account_id)->toBe($account->id);
});

it("rejects a leak report with coordinates outside the configured service area", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "low",
        "zone" => $account->zone,
        "description" => "Suspicious leak far away.",
        "latitude" => 40.7128,
        "longitude" => -74.0060,
    ]);

    $response->assertSessionHasErrors("latitude");
    $this->assertDatabaseCount("leak_reports", 0);
});

it("prevents a technician from reporting a leak", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $technician->id]);

    $response = $this->actingAs($technician)->post("/customer/leak-reports", [
        "severity" => "low",
        "zone" => $account->zone,
        "description" => "Not really a customer.",
    ]);

    $response->assertForbidden();
});

it("makes a leak-repair work order immediately claimable by a technician", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id]);

    $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "medium",
        "zone" => $account->zone,
        "description" => "Slow leak by the meter.",
    ]);

    $workOrder = WorkOrder::where("type", WorkOrderType::LeakRepair)->first();

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Claimed);
    expect($workOrder->fresh()->assigned_to)->toBe($technician->id);
});
