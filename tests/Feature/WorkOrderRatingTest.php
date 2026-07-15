<?php

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\User;
use App\Models\WorkOrder;

it("allows the owning customer to rate a completed work order", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Completed,
        "completed_at" => now(),
    ]);

    $response = $this->actingAs($customer)->post("/customer/work-orders/{$workOrder->id}/rate", [
        "rating" => 5,
        "rating_comment" => "Great service, quick turnaround.",
    ]);

    $response->assertRedirect();

    $workOrder->refresh();
    expect($workOrder->rating)->toBe(5);
    expect($workOrder->rating_comment)->toBe("Great service, quick turnaround.");
    expect($workOrder->rated_at)->not->toBeNull();
});

it("prevents rating a work order that isn't completed yet", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Approved,
    ]);

    $response = $this->actingAs($customer)->post("/customer/work-orders/{$workOrder->id}/rate", [
        "rating" => 4,
    ]);

    $response->assertSessionHasErrors("rating");
    expect($workOrder->fresh()->rating)->toBeNull();
});

it("prevents rating a work order twice", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Completed,
        "completed_at" => now(),
        "rating" => 3,
        "rated_at" => now(),
    ]);

    $response = $this->actingAs($customer)->post("/customer/work-orders/{$workOrder->id}/rate", [
        "rating" => 5,
    ]);

    $response->assertSessionHasErrors("rating");
    expect($workOrder->fresh()->rating)->toBe(3);
});

it("prevents a different customer from rating someone else''s work order", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $owner->id]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Completed,
        "completed_at" => now(),
    ]);

    $response = $this->actingAs($otherCustomer)->post("/customer/work-orders/{$workOrder->id}/rate", [
        "rating" => 1,
    ]);

    $response->assertForbidden();
});

it("rejects a rating outside the 1-5 range", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Completed,
        "completed_at" => now(),
    ]);

    $response = $this->actingAs($customer)->post("/customer/work-orders/{$workOrder->id}/rate", [
        "rating" => 6,
    ]);

    $response->assertSessionHasErrors("rating");
});
