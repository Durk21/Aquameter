<?php

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;
use App\Models\WorkOrder;

it("prevents a non-management user from viewing the management dashboard", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $response = $this->actingAs($customer)->get("/management/dashboard");

    $response->assertForbidden();
});

it("computes work order and complaint metrics correctly", function () {
    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $account = Account::factory()->create();

    $now = now();
    $fifteenDaysAgo = $now->copy()->subDays(15);
    $tenDaysAgo = $now->copy()->subDays(10);
    $fiveDaysAgo = $now->copy()->subDays(5);
    $fourDaysAgo = $now->copy()->subDays(4);

    // Disconnection: created 15 days ago, completed 10 days ago (5-day resolution).
    $this->travelTo($fifteenDaysAgo);
    $disconnection = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "type" => WorkOrderType::Disconnection,
        "status" => WorkOrderStatus::Approved,
    ]);

    $this->travelTo($tenDaysAgo);
    $disconnection->update(["status" => WorkOrderStatus::Completed, "completed_at" => now()]);

    // Reconnection: created 5 days ago, completed 4 days ago (1-day resolution),
    // 6 days disconnected overall (disconnection completed 10 days ago -> reconnection completed 4 days ago).
    // Overall avg resolution across both completed work orders: (5 + 1) / 2 = 3 days.
    $this->travelTo($fiveDaysAgo);
    $reconnection = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "type" => WorkOrderType::Reconnection,
        "status" => WorkOrderStatus::Approved,
    ]);

    $this->travelTo($fourDaysAgo);
    $reconnection->update(["status" => WorkOrderStatus::Completed, "completed_at" => now()]);

    // A stalled work order: still Approved, created 5 days ago (default threshold is 3 days).
    $this->travelTo($fiveDaysAgo);
    WorkOrder::factory()->create([
        "account_id" => $account->id,
        "type" => WorkOrderType::ServiceRequest,
        "status" => WorkOrderStatus::Approved,
    ]);

    // A complaint resolved 2 days after it was submitted.
    $this->travelTo($tenDaysAgo);
    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $account->user_id,
        "subject" => "Billing issue",
        "description" => "Testing resolution time.",
        "status" => "submitted",
    ]);

    $this->travelTo($tenDaysAgo->copy()->addDays(2));
    $complaint->update(["status" => "resolved", "resolved_at" => now()]);

    $this->travelBack();

    $response = $this->actingAs($management)->get("/management/dashboard");

    $response->assertInertia(fn ($page) => $page
        ->component("Management/Dashboard")
        ->where("stats.avg_resolution_days", 3)
        ->where("stats.avg_days_disconnected", 6)
        ->where("stats.completion_rate", 100)
        ->where("stats.stalled_work_orders", 1)
        ->where("stats.avg_complaint_resolution_days", 2)
    );
});

it("reports null averages instead of zero when there is nothing to average yet", function () {
    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $response = $this->actingAs($management)->get("/management/dashboard");

    $response->assertInertia(fn ($page) => $page
        ->where("stats.avg_resolution_days", null)
        ->where("stats.completion_rate", null)
    );
});
