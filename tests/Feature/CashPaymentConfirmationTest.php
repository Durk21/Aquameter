<?php

use App\Enums\BillStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\PaymentTransaction;
use App\Models\User;

function createCashTestBill(): array
{
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
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
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    return [$customer, $bill];
}

it("allows an admin to view pending cash payments", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->get("/admin/payment-transactions");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component("Admin/Payments/PendingCash"));
});

it("allows an admin to confirm a cash payment intent, marking the bill paid", function () {
    [$customer, $bill] = createCashTestBill();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $customer->id,
        "method" => "cash",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
    ]);

    $response = $this->actingAs($admin)->patch("/admin/payment-transactions/{$transaction->id}/confirm");

    $response->assertRedirect(route("admin.payment-transactions.index"));

    expect($bill->fresh()->status)->toBe(BillStatus::Paid);
    expect($transaction->fresh()->status)->toBe(PaymentTransactionStatus::Completed);

    $this->assertDatabaseHas("payments", [
        "bill_id" => $bill->id,
        "method" => "cash",
        "recorded_by" => $admin->id,
    ]);
});

it("prevents a technician from confirming a cash payment", function () {
    [, $bill] = createCashTestBill();

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "cash",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
    ]);

    $this->actingAs($technician)
        ->patch("/admin/payment-transactions/{$transaction->id}/confirm")
        ->assertForbidden();
});

it("prevents management from confirming a cash payment", function () {
    [, $bill] = createCashTestBill();

    $management = User::factory()->create();
    $management->assignRole(config("roles.management"));

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "cash",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
    ]);

    $this->actingAs($management)
        ->patch("/admin/payment-transactions/{$transaction->id}/confirm")
        ->assertForbidden();
});

it("rejects confirming a transaction that is already completed", function () {
    [$customer, $bill] = createCashTestBill();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $customer->id,
        "method" => "cash",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
    ]);

    $this->actingAs($admin)->patch("/admin/payment-transactions/{$transaction->id}/confirm")->assertRedirect();

    $response = $this->actingAs($admin)->patch("/admin/payment-transactions/{$transaction->id}/confirm");

    $response->assertStatus(422);
    $this->assertDatabaseCount("payments", 1);
});
