<?php

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\User;
use App\Models\WorkOrder;

it("allows only an admin to initiate a disconnection", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    expect($admin->can("initiateDisconnection", WorkOrder::class))->toBeTrue();
    expect($technician->can("initiateDisconnection", WorkOrder::class))->toBeFalse();
});

it("allows only the owning customer to dispute a work order", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create(["user_id" => $owner->id, "status" => AccountStatus::Defaulted]);
    $workOrder = WorkOrder::factory()->create(["account_id" => $account->id]);

    expect($owner->can("dispute", $workOrder))->toBeTrue();
    expect($otherCustomer->can("dispute", $workOrder))->toBeFalse();
    expect($admin->can("dispute", $workOrder))->toBeFalse();
});

it("allows only a technician to claim a work order", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $workOrder = WorkOrder::factory()->create(["status" => WorkOrderStatus::Approved]);

    expect($technician->can("claim", $workOrder))->toBeTrue();
    expect($admin->can("claim", $workOrder))->toBeFalse();
});

it("allows only the assigned technician to complete a work order", function () {
    $assigned = User::factory()->create();
    $assigned->assignRole(config("roles.technician"));

    $other = User::factory()->create();
    $other->assignRole(config("roles.technician"));

    $workOrder = WorkOrder::factory()->create([
        "status" => WorkOrderStatus::Claimed,
        "assigned_to" => $assigned->id,
    ]);

    expect($assigned->can("complete", $workOrder))->toBeTrue();
    expect($other->can("complete", $workOrder))->toBeFalse();
});
