<?php

use App\Enums\BillStatus;
use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Meter;
use App\Models\User;
use App\Models\WorkOrder;

it("rate limits leak report submissions past the configured threshold", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $limit = (int) config("utility.submission_rate_limit");

    for ($i = 0; $i < $limit; $i++) {
        $this->actingAs($customer)->post("/customer/leak-reports", [
            "severity" => "low",
            "zone" => $account->zone,
            "description" => "Leak number {$i}.",
        ])->assertRedirect();
    }

    $response = $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "low",
        "zone" => $account->zone,
        "description" => "One too many.",
    ]);

    $response->assertStatus(429);
});

it("does not rate limit a different customer''s submissions", function () {
    $customerA = User::factory()->create();
    $customerA->assignRole(config("roles.customer"));
    $accountA = Account::factory()->create(["user_id" => $customerA->id]);

    $customerB = User::factory()->create();
    $customerB->assignRole(config("roles.customer"));
    $accountB = Account::factory()->create(["user_id" => $customerB->id]);

    $limit = (int) config("utility.submission_rate_limit");

    for ($i = 0; $i < $limit; $i++) {
        $this->actingAs($customerA)->post("/customer/leak-reports", [
            "severity" => "low",
            "zone" => $accountA->zone,
            "description" => "Leak number {$i}.",
        ]);
    }

    $response = $this->actingAs($customerB)->post("/customer/leak-reports", [
        "severity" => "low",
        "zone" => $accountB->zone,
        "description" => "First one for me.",
    ]);

    $response->assertRedirect();
});

it("soft deletes a bill instead of removing it", function () {
    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $account->user_id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => 150,
        "amount" => 7500,
        "status" => BillStatus::Pending,
        "due_date" => now()->addDays(7),
    ]);

    $bill->delete();

    expect(Bill::find($bill->id))->toBeNull();
    expect(Bill::withTrashed()->find($bill->id))->not->toBeNull();
    $this->assertDatabaseHas("bills", ["id" => $bill->id]);
});

it("soft deletes a complaint instead of removing it", function () {
    $account = Account::factory()->create();
    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $account->user_id,
        "subject" => "Test",
        "description" => "Test complaint.",
        "status" => "submitted",
    ]);

    $complaint->delete();

    expect(Complaint::find($complaint->id))->toBeNull();
    expect(Complaint::withTrashed()->find($complaint->id))->not->toBeNull();
});

it("records an activity log entry when a bill's status changes", function () {
    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $account->user_id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => 150,
        "amount" => 7500,
        "status" => BillStatus::Pending,
        "due_date" => now()->addDays(7),
    ]);

    $this->assertDatabaseCount("activity_logs", 0);

    $bill->status = BillStatus::Overdue;
    $bill->save();

    $log = ActivityLog::first();
    expect($log)->not->toBeNull();
    expect($log->subject_type)->toBe(Bill::class);
    expect($log->subject_id)->toBe($bill->id);
    expect($log->from_status)->toBe("pending");
    expect($log->to_status)->toBe("overdue");
});

it("records an activity log entry when a complaint's status changes", function () {
    $account = Account::factory()->create();
    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $account->user_id,
        "subject" => "Test",
        "description" => "Test complaint.",
        "status" => ComplaintStatus::Submitted,
    ]);

    $complaint->status = ComplaintStatus::UnderReview;
    $complaint->save();

    $log = ActivityLog::first();
    expect($log->from_status)->toBe("submitted");
    expect($log->to_status)->toBe("under_review");
});

it("records an activity log entry when a work order's status changes", function () {
    $workOrder = WorkOrder::factory()->create(["status" => WorkOrderStatus::NoticeSent]);

    $workOrder->status = WorkOrderStatus::Approved;
    $workOrder->save();

    $log = ActivityLog::first();
    expect($log->subject_type)->toBe(WorkOrder::class);
    expect($log->from_status)->toBe("notice_sent");
    expect($log->to_status)->toBe("approved");
});

it("does not log an activity entry when a non-status field changes", function () {
    $account = Account::factory()->create();
    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $account->user_id,
        "subject" => "Test",
        "description" => "Test complaint.",
        "status" => ComplaintStatus::Submitted,
    ]);

    $complaint->resolution_notes = "Just a note, no status change.";
    $complaint->save();

    $this->assertDatabaseCount("activity_logs", 0);
});

it("restricts the activity log to admin and management", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $this->actingAs($admin)->get("/activity")->assertOk();
    $this->actingAs($management)->get("/activity")->assertOk();
    $this->actingAs($customer)->get("/activity")->assertForbidden();
});

it("restricts the admin bills index to admin and management", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $this->actingAs($admin)->get("/admin/bills")->assertOk();
    $this->actingAs($management)->get("/admin/bills")->assertOk();
    $this->actingAs($technician)->get("/admin/bills")->assertForbidden();
});

it("keeps payment recording admin-only", function () {
    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $account->user_id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => 150,
        "amount" => 7500,
        "status" => BillStatus::Pending,
        "due_date" => now()->addDays(7),
    ]);

    $this->actingAs($management)->get("/admin/bills/{$bill->id}/payments/create")->assertForbidden();
});
