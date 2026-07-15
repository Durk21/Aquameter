<?php

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\User;
use App\Models\WorkOrder;

function makeDefaultedAccount(): Account
{
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    return Account::factory()->create([
        "user_id" => $customer->id,
        "status" => AccountStatus::Defaulted,
        "defaulted_at" => now(),
    ]);
}

it("prevents an admin from issuing a disconnection notice on a non-defaulted account", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id, "status" => AccountStatus::Active]);

    $response = $this->actingAs($admin)->post("/admin/accounts/{$account->id}/disconnection-notice");

    $response->assertSessionHasErrors("work_order");
    $this->assertDatabaseCount("work_orders", 0);
});

it("prevents a technician from issuing a disconnection notice", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = makeDefaultedAccount();

    $response = $this->actingAs($technician)->post("/admin/accounts/{$account->id}/disconnection-notice");

    $response->assertForbidden();
});

it("walks a disconnection through notice, sign-off, claim, and completion", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = makeDefaultedAccount();

    $this->actingAs($admin)
        ->post("/admin/accounts/{$account->id}/disconnection-notice")
        ->assertRedirect(route("admin.work-orders.index"));

    $workOrder = WorkOrder::first();
    expect($workOrder->status)->toBe(WorkOrderStatus::NoticeSent);
    expect($workOrder->notice_deadline->toDateString())
        ->toBe(now()->addDays(config("utility.disconnection_notice_days"))->toDateString());

    // Cannot sign off before the notice period elapses.
    $this->actingAs($admin)
        ->patch("/admin/work-orders/{$workOrder->id}/sign-off")
        ->assertSessionHasErrors("work_order");
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::NoticeSent);

    $this->travel(config("utility.disconnection_notice_days") + 1)->days();

    $this->actingAs($admin)
        ->patch("/admin/work-orders/{$workOrder->id}/sign-off")
        ->assertRedirect(route("admin.work-orders.index"));
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Approved);

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect(route("technician.work-orders.index"));
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Claimed);
    expect($workOrder->fresh()->assigned_to)->toBe($technician->id);

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/complete", ["resolution_notes" => "Valve closed."])
        ->assertRedirect(route("technician.work-orders.index"));

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Completed);
    expect($account->fresh()->status)->toBe(AccountStatus::Disconnected);
});

it("prevents a second technician from claiming an already-claimed work order", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $technicianA = User::factory()->create();
    $technicianA->assignRole(config("roles.technician"));

    $technicianB = User::factory()->create();
    $technicianB->assignRole(config("roles.technician"));

    $account = makeDefaultedAccount();
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Approved,
    ]);

    $this->actingAs($technicianA)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect();

    $response = $this->actingAs($technicianB)->patch("/technician/work-orders/{$workOrder->id}/claim");

    $response->assertSessionHasErrors("work_order");
    expect($workOrder->fresh()->assigned_to)->toBe($technicianA->id);
});
