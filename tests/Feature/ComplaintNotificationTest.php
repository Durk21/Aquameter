<?php

use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintStatusUpdated;
use App\Notifications\ComplaintSubmitted;
use Illuminate\Support\Facades\Notification;

it("notifies every admin when a customer submits a complaint", function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $this->actingAs($customer)->post("/customer/complaints", [
        "subject" => "Bill too high",
        "description" => "Usage looks wrong.",
    ]);

    Notification::assertSentTo($admin, ComplaintSubmitted::class);
    Notification::assertNotSentTo($customer, ComplaintSubmitted::class);
});

it("notifies the customer when their complaint is resolved", function () {
    Notification::fake();

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

    $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", [
        "status" => "resolved",
        "resolution_notes" => "Verified and adjusted.",
    ]);

    Notification::assertSentTo($customer, ComplaintStatusUpdated::class);
});

it("does not notify anyone else when a complaint is resolved", function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $unrelatedCustomer = User::factory()->create();
    $unrelatedCustomer->assignRole(config("roles.customer"));

    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Bill too high",
        "description" => "Usage looks wrong.",
        "status" => "submitted",
    ]);

    $this->actingAs($admin)->patch("/admin/complaints/{$complaint->id}", [
        "status" => "resolved",
        "resolution_notes" => "Verified and adjusted.",
    ]);

    Notification::assertNotSentTo($unrelatedCustomer, ComplaintStatusUpdated::class);
});
