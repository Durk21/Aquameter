<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;

function createTestBill(User $customer, User $technician): Bill
{
    $account = Account::factory()->create(["user_id" => $customer->id]);
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
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);
}

it("allows an admin to record a full payment and marks the bill paid", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $bill = createTestBill($customer, $technician);

    $response = $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "mpesa",
        "reference" => "QWE123XYZ",
        "paid_at" => now()->toDateString(),
    ]);

    $response->assertRedirect(route("admin.bills.index"));

    $this->assertDatabaseHas("payments", [
        "bill_id" => $bill->id,
        "method" => "mpesa",
        "reference" => "QWE123XYZ",
    ]);

    expect($bill->fresh()->status)->toBe(\App\Enums\BillStatus::Paid);
    expect($bill->fresh()->paid_at)->not->toBeNull();
});

it("rejects a partial payment that does not match the full bill amount", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $bill = createTestBill($customer, $technician);

    $response = $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => (float) $bill->amount - 500,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors("amount");
    $this->assertDatabaseCount("payments", 0);
    expect($bill->fresh()->status)->toBe(\App\Enums\BillStatus::Pending);
});

it("prevents a technician from recording a payment", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $bill = createTestBill($customer, $technician);

    $response = $this->actingAs($technician)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ]);

    $response->assertForbidden();
});

it("prevents recording a second payment against an already-paid bill", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $bill = createTestBill($customer, $technician);

    $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ]);

    $response = $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount("payments", 1);
});
