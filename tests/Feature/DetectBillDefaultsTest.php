<?php

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;

function makeBillWithDueDate(AccountStatus $accountStatus, BillStatus $billStatus, $dueDate): Bill
{
    $user = User::factory()->create();
    $account = Account::factory()->create(["user_id" => $user->id, "status" => $accountStatus]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $user->id,
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
        "status" => $billStatus,
        "due_date" => $dueDate,
    ]);
}

it("marks a pending bill past its due date as overdue, and the account as overdue", function () {
    $bill = makeBillWithDueDate(AccountStatus::Active, BillStatus::Pending, now()->subDays(2));

    $this->artisan("bills:detect-defaults")->assertSuccessful();

    expect($bill->fresh()->status)->toBe(BillStatus::Overdue);
    expect($bill->fresh()->account->status)->toBe(AccountStatus::Overdue);
});

it("does not touch a pending bill that is not yet due", function () {
    $bill = makeBillWithDueDate(AccountStatus::Active, BillStatus::Pending, now()->addDays(3));

    $this->artisan("bills:detect-defaults")->assertSuccessful();

    expect($bill->fresh()->status)->toBe(BillStatus::Pending);
    expect($bill->fresh()->account->status)->toBe(AccountStatus::Active);
});

it("marks an overdue bill past the grace period as defaulted, and the account as defaulted", function () {
    $graceDays = (int) config("utility.default_grace_days");
    $bill = makeBillWithDueDate(AccountStatus::Overdue, BillStatus::Overdue, now()->subDays($graceDays + 2));

    $this->artisan("bills:detect-defaults")->assertSuccessful();

    expect($bill->fresh()->status)->toBe(BillStatus::Defaulted);
    expect($bill->fresh()->account->status)->toBe(AccountStatus::Defaulted);
    expect($bill->fresh()->account->defaulted_at)->not->toBeNull();
});

it("does not default an overdue bill still inside its grace period", function () {
    $graceDays = (int) config("utility.default_grace_days");
    $bill = makeBillWithDueDate(AccountStatus::Overdue, BillStatus::Overdue, now()->subDays($graceDays - 2));

    $this->artisan("bills:detect-defaults")->assertSuccessful();

    expect($bill->fresh()->status)->toBe(BillStatus::Overdue);
    expect($bill->fresh()->account->status)->toBe(AccountStatus::Overdue);
});

it("does not downgrade an already-disconnected account when its bill crosses the default threshold", function () {
    $graceDays = (int) config("utility.default_grace_days");
    $bill = makeBillWithDueDate(AccountStatus::Disconnected, BillStatus::Overdue, now()->subDays($graceDays + 2));

    $this->artisan("bills:detect-defaults")->assertSuccessful();

    expect($bill->fresh()->status)->toBe(BillStatus::Defaulted);
    expect($bill->fresh()->account->status)->toBe(AccountStatus::Disconnected);
});
