<?php

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;
use App\Models\WorkOrder;

function makeDisconnectedAccountWithBill(User $customer, User $technician): Bill
{
    $account = Account::factory()->create(["user_id" => $customer->id, "status" => AccountStatus::Disconnected]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    return Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 50 * config("utility.rate_per_unit"),
        "status" => BillStatus::Defaulted,
        "due_date" => now()->subDays(30),
    ]);
}

it("automatically opens a reconnection work order once a disconnected account's balance is fully paid", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $bill = makeDisconnectedAccountWithBill($customer, $technician);
    $account = $bill->account;

    $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ])->assertRedirect(route("admin.bills.index"));

    expect($account->fresh()->status)->toBe(AccountStatus::Disconnected);

    $workOrder = WorkOrder::where("type", WorkOrderType::Reconnection)->first();
    expect($workOrder)->not->toBeNull();
    expect($workOrder->status)->toBe(WorkOrderStatus::Approved);

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/claim")
        ->assertRedirect();

    $this->actingAs($technician)
        ->patch("/technician/work-orders/{$workOrder->id}/complete")
        ->assertRedirect();

    expect($account->fresh()->status)->toBe(AccountStatus::Active);
    expect($account->fresh()->defaulted_at)->toBeNull();
});

it("clears an overdue account back to active without a reconnection work order when it was never disconnected", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id, "status" => AccountStatus::Overdue]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);
    $bill = Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 50 * config("utility.rate_per_unit"),
        "status" => BillStatus::Overdue,
        "due_date" => now()->subDays(5),
    ]);

    $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ])->assertRedirect(route("admin.bills.index"));

    expect($account->fresh()->status)->toBe(AccountStatus::Active);
    $this->assertDatabaseCount("work_orders", 0);
});
