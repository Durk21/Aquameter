<?php

use App\Models\Account;
use App\Models\LeakReport;
use App\Models\User;

it("only shows a customer their own leak reports on the map", function () {
    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));
    $ownerAccount = Account::factory()->create(["user_id" => $owner->id]);

    $other = User::factory()->create();
    $other->assignRole(config("roles.customer"));
    $otherAccount = Account::factory()->create(["user_id" => $other->id]);

    $bounds = config("utility.service_area_bounds");
    $lat = ($bounds["min_lat"] + $bounds["max_lat"]) / 2;
    $lng = ($bounds["min_lng"] + $bounds["max_lng"]) / 2;

    $ownLeak = LeakReport::factory()->create([
        "account_id" => $ownerAccount->id,
        "reported_by" => $owner->id,
        "latitude" => $lat,
        "longitude" => $lng,
    ]);

    LeakReport::factory()->create([
        "account_id" => $otherAccount->id,
        "reported_by" => $other->id,
        "latitude" => $lat,
        "longitude" => $lng,
    ]);

    $response = $this->actingAs($owner)->get("/map");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component("Map/Index")
        ->has("incidents", 1)
        ->where("incidents.0.id", $ownLeak->id));
});

it("shows an admin every leak report on the map", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customerA = User::factory()->create();
    $customerA->assignRole(config("roles.customer"));
    $accountA = Account::factory()->create(["user_id" => $customerA->id]);

    $customerB = User::factory()->create();
    $customerB->assignRole(config("roles.customer"));
    $accountB = Account::factory()->create(["user_id" => $customerB->id]);

    $bounds = config("utility.service_area_bounds");
    $lat = ($bounds["min_lat"] + $bounds["max_lat"]) / 2;
    $lng = ($bounds["min_lng"] + $bounds["max_lng"]) / 2;

    LeakReport::factory()->create(["account_id" => $accountA->id, "reported_by" => $customerA->id, "latitude" => $lat, "longitude" => $lng]);
    LeakReport::factory()->create(["account_id" => $accountB->id, "reported_by" => $customerB->id, "latitude" => $lat, "longitude" => $lng]);

    $response = $this->actingAs($admin)->get("/map");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component("Map/Index")->has("incidents", 2));
});

it("omits leak reports without coordinates from the map", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    LeakReport::factory()->create([
        "account_id" => $account->id,
        "reported_by" => $customer->id,
        "latitude" => null,
        "longitude" => null,
    ]);

    $response = $this->actingAs($customer)->get("/map");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component("Map/Index")->has("incidents", 0));
});
