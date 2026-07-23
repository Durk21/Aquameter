<?php

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\User;
use App\Models\WorkOrder;

it("lets the account owner dispute an active disconnection notice, pausing it", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id, "status" => AccountStatus::Defaulted]);
    $workOrder = WorkOrder::factory()->create(["account_id" => $account->id, "status" => WorkOrderStatus::NoticeSent]);

    $this->actingAs($customer)
        ->post("/customer/work-orders/{$workOrder->id}/dispute", ["reason" => "I paid in cash at the office."])
        ->assertRedirect(route("customer.dashboard"));

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Disputed);

    // A disputed work order is not claimable.
    $response = $this->actingAs($technician)->patch("/technician/work-orders/{$workOrder->id}/claim");
    $response->assertSessionHasErrors("work_order");
});

it("prevents a customer from disputing someone else''s work order", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $owner->id, "status" => AccountStatus::Defaulted]);
    $workOrder = WorkOrder::factory()->create(["account_id" => $account->id, "status" => WorkOrderStatus::NoticeSent]);

    $response = $this->actingAs($otherCustomer)->post("/customer/work-orders/{$workOrder->id}/dispute", [
        "reason" => "Not mine.",
    ]);

    $response->assertForbidden();
});

it("lets an admin reinstate a disputed work order back into the approved queue", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id, "status" => AccountStatus::Defaulted]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Disputed,
        "disputed_by" => $customer->id,
        "disputed_at" => now(),
        "dispute_reason" => "Disagree with the default.",
    ]);

    $this->actingAs($admin)
        ->patch("/admin/work-orders/{$workOrder->id}/resolve-dispute", ["resolution" => "reinstate"])
        ->assertRedirect(route("admin.work-orders.index"));

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Approved);
});

it("lets an admin cancel a disputed work order outright", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id, "status" => AccountStatus::Defaulted]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Disputed,
        "disputed_by" => $customer->id,
        "disputed_at" => now(),
        "dispute_reason" => "Disagree with the default.",
    ]);

    $this->actingAs($admin)
        ->patch("/admin/work-orders/{$workOrder->id}/resolve-dispute", ["resolution" => "cancel"])
        ->assertRedirect(route("admin.work-orders.index"));

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Cancelled);
});
