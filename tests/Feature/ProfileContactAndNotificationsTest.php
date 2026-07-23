<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\User;
use App\Notifications\BillGenerated;
use Illuminate\Support\Facades\Notification;

it("shows a customer their account contact info and notification preferences", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $customer->id, "phone" => "0712345678"]);

    $response = $this->actingAs($customer)->get("/profile");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("account.phone", "0712345678")
        ->has("notificationCategories")
        ->has("notificationPreferences"));
});

it("hides contact info and notification preferences for non-customers", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->get("/profile");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where("account", null)
        ->where("notificationCategories", null));
});

it("lets a customer update their phone and alternate email", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->patch("/profile/contact", [
        "phone" => "0798765432",
        "alternate_email" => "backup@example.com",
    ]);

    $response->assertRedirect(route("profile.edit"));
    $account->refresh();
    expect($account->phone)->toBe("0798765432");
    expect($account->alternate_email)->toBe("backup@example.com");
});

it("rejects an invalid alternate email", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->patch("/profile/contact", [
        "alternate_email" => "not-an-email",
    ]);

    $response->assertSessionHasErrors("alternate_email");
});

it("forbids a non-customer from updating contact info", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $this->actingAs($admin)->patch("/profile/contact", ["phone" => "0712345678"])
        ->assertForbidden();
});

it("lets a customer disable email for a notification category and it's respected", function () {
    Notification::fake();

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $this->actingAs($customer)->patch("/profile/notifications", [
        "preferences" => [
            "bill_generated" => false,
            "work_order_updates" => true,
            "disconnection_notices" => true,
            "complaint_updates" => true,
            "outage_broadcasts" => true,
        ],
    ])->assertRedirect(route("profile.edit"));

    $customer->refresh();
    expect($customer->wantsEmailFor("bill_generated"))->toBeFalse();
    expect($customer->wantsEmailFor("work_order_updates"))->toBeTrue();

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
        "rate_applied" => 150,
        "amount" => 7500,
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);

    $notification = new BillGenerated($bill);
    expect($notification->via($customer))->toBe(["database"]);
});

it("defaults every category to enabled when no preference has ever been saved", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    foreach (config("notifications.categories") as $category => $label) {
        expect($customer->wantsEmailFor($category))->toBeTrue();
    }
});
