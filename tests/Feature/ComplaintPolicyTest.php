<?php

use App\Models\Account;
use App\Models\Complaint;
use App\Models\User;

it("allows a customer to create a complaint on their own account", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);

    expect($customer->can("createFor", [Complaint::class, $account]))->toBeTrue();
});

it("prevents a customer from creating a complaint on someone else''s account", function () {
    $owner = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $owner->id]);

    expect($otherCustomer->can("createFor", [Complaint::class, $account]))->toBeFalse();
});

it("prevents a technician from creating a complaint", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $technician->id]);

    expect($technician->can("createFor", [Complaint::class, $account]))->toBeFalse();
});

it("allows an admin to review a complaint but not a customer", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $complaint = Complaint::create([
        "account_id" => $account->id,
        "submitted_by" => $customer->id,
        "subject" => "Bill seems too high",
        "description" => "My usage this month looks much higher than usual.",
        "status" => "submitted",
    ]);

    expect($admin->can("review", $complaint))->toBeTrue();
    expect($customer->can("review", $complaint))->toBeFalse();
});
