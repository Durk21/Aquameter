<?php

use Illuminate\Support\Facades\Auth;

test("registration screen can be rendered", function () {
    $response = $this->get("/register");

    $response->assertStatus(200);
});

test("new users can register", function () {
    $response = $this->post("/register", [
        "name" => "Test User",
        "email" => "test@example.com",
        "password" => "password",
        "password_confirmation" => "password",
        "address" => "123 Test Lane",
        "zone" => config("utility.zones")[0],
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route("dashboard", absolute: false));
});

test("registration is rate limited", function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post("/register", [
            "name" => "Test User",
            "email" => "test{$i}@example.com",
            "password" => "password",
            "password_confirmation" => "password",
            "address" => "123 Test Lane",
            "zone" => config("utility.zones")[0],
        ]);
        Auth::logout();
    }

    $response = $this->post("/register", [
        "name" => "Test User",
        "email" => "test-blocked@example.com",
        "password" => "password",
        "password_confirmation" => "password",
        "address" => "123 Test Lane",
        "zone" => config("utility.zones")[0],
    ]);

    $response->assertStatus(429);
});
