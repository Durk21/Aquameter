<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;

it("allows a customer to submit a complaint about their own bill", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
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
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    $response = $this->actingAs($customer)->post("/customer/complaints", [
        "bill_id" => $bill->id,
        "subject" => "Bill seems too high",
        "description" => "My usage jumped a lot this month and I''m not sure why.",
    ]);

    $response->assertRedirect(route("customer.complaints.index"));

    $this->assertDatabaseHas("complaints", [
        "account_id" => $account->id,
        "bill_id" => $bill->id,
        "submitted_by" => $customer->id,
        "status" => "submitted",
    ]);
});

it("prevents a customer from submitting a complaint about someone else''s bill", function () {
    $owner = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $ownerAccount = Account::factory()->create(["user_id" => $owner->id]);
    $meter = Meter::factory()->create(["account_id" => $ownerAccount->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $owner->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    $bill = Bill::create([
        "account_id" => $ownerAccount->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 50 * config("utility.rate_per_unit"),
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    Account::factory()->create(["user_id" => $otherCustomer->id]);

    $response = $this->actingAs($otherCustomer)->post("/customer/complaints", [
        "bill_id" => $bill->id,
        "subject" => "Not my bill",
        "description" => "Trying to complain about someone else''s bill.",
    ]);

    $response->assertForbidden();
});
