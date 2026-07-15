<?php

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;

it("allows a customer to submit a service request on their own account, opening a work order", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/service-requests", [
        "type" => "meter_inspection",
        "zone" => $account->zone,
        "description" => "Meter seems to be reading inconsistently.",
    ]);

    $response->assertRedirect(route("customer.service-requests.index"));

    $this->assertDatabaseHas("service_requests", [
        "account_id" => $account->id,
        "requested_by" => $customer->id,
        "type" => "meter_inspection",
    ]);

    $serviceRequest = ServiceRequest::first();
    $workOrder = WorkOrder::where("sourceable_type", ServiceRequest::class)
        ->where("sourceable_id", $serviceRequest->id)
        ->first();

    expect($workOrder)->not->toBeNull();
    expect($workOrder->type)->toBe(WorkOrderType::ServiceRequest);
    expect($workOrder->status)->toBe(WorkOrderStatus::Approved);
});

it("rejects a service request type that is not in the configured list", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/service-requests", [
        "type" => "not_a_real_type",
        "zone" => $account->zone,
        "description" => "Testing an invalid type.",
    ]);

    $response->assertSessionHasErrors("type");
    $this->assertDatabaseCount("service_requests", 0);
});

it("prevents a customer from viewing another customer''s service requests", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $owner->id]);
    $serviceRequest = ServiceRequest::factory()->create(["account_id" => $account->id, "requested_by" => $owner->id]);

    expect($owner->can("view", $serviceRequest))->toBeTrue();
    expect($otherCustomer->can("view", $serviceRequest))->toBeFalse();
});
