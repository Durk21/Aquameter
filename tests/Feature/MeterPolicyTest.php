<?php

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;

it("allows an admin to create a meter", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    expect($admin->can("create", Meter::class))->toBeTrue();
});

it("prevents a technician from creating a meter", function () {
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    expect($technician->can("create", Meter::class))->toBeFalse();
});

it("prevents a customer from creating a meter", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    expect($customer->can("create", Meter::class))->toBeFalse();
});

it("allows the account owner to view their own meter", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    expect($customer->can("view", $meter))->toBeTrue();
});
