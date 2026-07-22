<?php

use App\Models\Account;
use App\Models\Complaint;
use App\Models\Outage;
use App\Models\User;
use App\Notifications\OutageBroadcast;

it("queues every notification class instead of sending synchronously", function () {
    $files = glob(app_path("Notifications/*.php"));

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $class = "App\\Notifications\\" . basename($file, ".php");

        expect(is_subclass_of($class, \Illuminate\Contracts\Queue\ShouldQueue::class))
            ->toBeTrue("{$class} should implement ShouldQueue so mail sends don't block the request.");
    }
});

it("paginates the admin complaints list at 20 per page", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    for ($i = 0; $i < 25; $i++) {
        Complaint::create([
            "account_id" => $account->id,
            "submitted_by" => $customer->id,
            "subject" => "Complaint {$i}",
            "description" => "Description {$i}",
            "status" => "submitted",
        ]);
    }

    $firstPage = $this->actingAs($admin)->get("/admin/complaints");
    $firstPage->assertOk();
    $firstPage->assertInertia(fn ($page) => $page
        ->component("Admin/Complaints/Index")
        ->has("complaints.data", 20)
        ->where("complaints.total", 25)
        ->where("complaints.current_page", 1));

    $secondPage = $this->actingAs($admin)->get("/admin/complaints?page=2");
    $secondPage->assertOk();
    $secondPage->assertInertia(fn ($page) => $page
        ->has("complaints.data", 5)
        ->where("complaints.current_page", 2));
});

it("paginates a user's notification history separately from the shared bell dropdown data", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    for ($i = 0; $i < 3; $i++) {
        $outage = Outage::create([
            "zone" => null,
            "title" => "Outage {$i}",
            "description" => "Description {$i}",
            "status" => "active",
            "starts_at" => now(),
        ]);
        $customer->notify(new OutageBroadcast($outage));
    }

    $response = $this->actingAs($customer)->get("/notifications");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component("Notifications/Index")
        ->has("notificationHistory.data", 3)
        ->where("notifications.unread_count", fn ($count) => is_int($count)));
});
