<?php

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\LeakReport;
use App\Models\User;
use App\Models\WorkOrder;

it("allows an admin to set a technician''s zone", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $zone = config("utility.zones")[0];

    $response = $this->actingAs($admin)->patch("/admin/technicians/{$technician->id}/zone", [
        "zone" => $zone,
    ]);

    $response->assertRedirect(route("admin.technicians.index"));
    expect($technician->fresh()->zone)->toBe($zone);
});

it("prevents a technician from setting their own zone", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $response = $this->actingAs($technician)->patch("/admin/technicians/{$technician->id}/zone", [
        "zone" => config("utility.zones")[0],
    ]);

    $response->assertForbidden();
});

it("rejects setting a zone on a non-technician user", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    expect($admin->can("manageZone", $customer))->toBeFalse();
});

it("puts a leak-repair job in a matching technician''s in-zone queue and the other in their other-zones queue", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $zones = config("utility.zones");
    $matchingTechnician = User::factory()->create(["zone" => $zones[0]]);
    $matchingTechnician->assignRole(config("roles.technician"));

    $otherTechnician = User::factory()->create(["zone" => $zones[1]]);
    $otherTechnician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id, "zone" => $zones[0]]);

    $leakReport = LeakReport::factory()->create(["account_id" => $account->id, "zone" => $zones[0]]);
    $workOrder = WorkOrder::create([
        "account_id" => $account->id,
        "type" => WorkOrderType::LeakRepair,
        "status" => WorkOrderStatus::Approved,
        "sourceable_type" => LeakReport::class,
        "sourceable_id" => $leakReport->id,
    ]);

    $matchingResponse = $this->actingAs($matchingTechnician)->get("/technician/work-orders");
    $matchingResponse->assertInertia(fn ($page) => $page
        ->where("inZone", fn ($jobs) => collect($jobs)->pluck("id")->contains($workOrder->id))
        ->where("otherZones", fn ($jobs) => ! collect($jobs)->pluck("id")->contains($workOrder->id))
    );

    $otherResponse = $this->actingAs($otherTechnician)->get("/technician/work-orders");
    $otherResponse->assertInertia(fn ($page) => $page
        ->where("otherZones", fn ($jobs) => collect($jobs)->pluck("id")->contains($workOrder->id))
        ->where("inZone", fn ($jobs) => ! collect($jobs)->pluck("id")->contains($workOrder->id))
    );

    // Zone is only a prioritization hint — the other technician can still claim it manually.
    $this->actingAs($otherTechnician)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect();

    expect($workOrder->fresh()->assigned_to)->toBe($otherTechnician->id);
});
