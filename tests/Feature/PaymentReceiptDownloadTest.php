<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\Payment;
use App\Models\User;

function createPaidBillFor(User $customer, Account $account): Bill
{
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $customer->id,
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
        "status" => "paid",
        "due_date" => now()->addDays(7),
        "paid_at" => now(),
    ]);

    Payment::create([
        "bill_id" => $bill->id,
        "account_id" => $account->id,
        "recorded_by" => $customer->id,
        "amount" => $bill->amount,
        "method" => "cash",
        "reference" => "REF-123",
        "paid_at" => now(),
    ]);

    return $bill->fresh("payment");
}

it("allows the account owner to download their payment receipt as a pdf", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $bill = createPaidBillFor($customer, $account);

    $response = $this->actingAs($customer)->get("/payments/{$bill->payment->id}/receipt");

    $response->assertOk();
    $response->assertHeader("content-type", "application/pdf");
});

it("prevents a different customer from downloading someone else's receipt", function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $owner->id]);
    $bill = createPaidBillFor($owner, $account);

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $response = $this->actingAs($otherCustomer)->get("/payments/{$bill->payment->id}/receipt");

    $response->assertForbidden();
});

it("lets admin download a receipt for any payment", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $customer->id]);
    $bill = createPaidBillFor($customer, $account);

    $response = $this->actingAs($admin)->get("/payments/{$bill->payment->id}/receipt");

    $response->assertOk();
});

it("exposes the payment id on a paid bill to the customer for a receipt link", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $bill = createPaidBillFor($customer, $account);

    $response = $this->actingAs($customer)->get("/customer/bills");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("bills.0.payment_id", $bill->payment->id));
});

it("does not expose a payment id on an unpaid bill", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $customer->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    Bill::create([
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

    $response = $this->actingAs($customer)->get("/customer/bills");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("bills.0.payment_id", null));
});

it("exposes the payment id on the admin bills list", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $customer->id]);
    $bill = createPaidBillFor($customer, $account);

    $response = $this->actingAs($admin)->get("/admin/bills");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("bills.0.payment_id", $bill->payment->id));
});
