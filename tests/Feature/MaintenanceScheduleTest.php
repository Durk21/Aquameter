<?php

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\MaintenanceSchedule;
use App\Models\Meter;
use App\Models\User;
use App\Models\WorkOrder;

it("allows an admin to schedule maintenance for a meter, opening a work order", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create();
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $response = $this->actingAs($admin)->post("/admin/maintenance", [
        "scope" => "meter",
        "meter_id" => $meter->id,
        "scheduled_for" => now()->addDays(5)->toDateString(),
        "description" => "Routine inspection and valve check.",
    ]);

    $response->assertRedirect(route("admin.maintenance.index"));

    $this->assertDatabaseHas("maintenance_schedules", [
        "account_id" => $account->id,
        "meter_id" => $meter->id,
        "created_by" => $admin->id,
    ]);

    $schedule = MaintenanceSchedule::first();
    $workOrder = WorkOrder::where("sourceable_type", MaintenanceSchedule::class)
        ->where("sourceable_id", $schedule->id)
        ->first();

    expect($workOrder)->not->toBeNull();
    expect($workOrder->type)->toBe(WorkOrderType::Maintenance);
    expect($workOrder->status)->toBe(WorkOrderStatus::Approved);
    expect($workOrder->account_id)->toBe($account->id);
});

it("prevents a non-admin from scheduling maintenance", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $meter = Meter::factory()->create();

    $response = $this->actingAs($technician)->post("/admin/maintenance", [
        "scope" => "meter",
        "meter_id" => $meter->id,
        "scheduled_for" => now()->addDays(5)->toDateString(),
        "description" => "Not allowed.",
    ]);

    $response->assertForbidden();
});

it("rejects a scheduled date in the past", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $meter = Meter::factory()->create();

    $response = $this->actingAs($admin)->post("/admin/maintenance", [
        "scope" => "meter",
        "meter_id" => $meter->id,
        "scheduled_for" => now()->subDays(1)->toDateString(),
        "description" => "Backdated.",
    ]);

    $response->assertSessionHasErrors("scheduled_for");
    $this->assertDatabaseCount("maintenance_schedules", 0);
});

it("schedules maintenance for every meter in a zone", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $zones = config("utility.zones");
    $targetZone = $zones[0];
    $otherZone = $zones[1];

    $accountInZone = Account::factory()->create(["zone" => $targetZone]);
    $meterOne = Meter::factory()->create(["account_id" => $accountInZone->id]);

    $otherAccountInZone = Account::factory()->create(["zone" => $targetZone]);
    $meterTwo = Meter::factory()->create(["account_id" => $otherAccountInZone->id]);

    $accountElsewhere = Account::factory()->create(["zone" => $otherZone]);
    $meterElsewhere = Meter::factory()->create(["account_id" => $accountElsewhere->id]);

    $response = $this->actingAs($admin)->post("/admin/maintenance", [
        "scope" => "zone",
        "zone" => $targetZone,
        "scheduled_for" => now()->addDays(5)->toDateString(),
        "description" => "Zone-wide inspection.",
    ]);

    $response->assertRedirect(route("admin.maintenance.index"));

    $this->assertDatabaseCount("maintenance_schedules", 2);
    $this->assertDatabaseHas("maintenance_schedules", ["meter_id" => $meterOne->id]);
    $this->assertDatabaseHas("maintenance_schedules", ["meter_id" => $meterTwo->id]);
    $this->assertDatabaseMissing("maintenance_schedules", ["meter_id" => $meterElsewhere->id]);

    expect(WorkOrder::where("type", WorkOrderType::Maintenance)->count())->toBe(2);
});

it("rejects a zone-wide schedule for a zone with no meters", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $emptyZone = collect(config("utility.zones"))->last();

    $response = $this->actingAs($admin)->post("/admin/maintenance", [
        "scope" => "zone",
        "zone" => $emptyZone,
        "scheduled_for" => now()->addDays(5)->toDateString(),
        "description" => "No meters here yet.",
    ]);

    $response->assertSessionHasErrors("zone");
    $this->assertDatabaseCount("maintenance_schedules", 0);
});

it("makes a maintenance work order immediately claimable by a technician", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $meter = Meter::factory()->create();

    $this->actingAs($admin)->post("/admin/maintenance", [
        "scope" => "meter",
        "meter_id" => $meter->id,
        "scheduled_for" => now()->addDays(3)->toDateString(),
        "description" => "Inspect meter housing.",
    ]);

    $workOrder = WorkOrder::where("type", WorkOrderType::Maintenance)->first();

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect();

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Claimed);
    expect($workOrder->fresh()->assigned_to)->toBe($technician->id);
});
