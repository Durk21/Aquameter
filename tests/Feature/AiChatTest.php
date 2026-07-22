<?php

use App\Models\Account;
use App\Models\ChatMessage;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

it("fetches message history for a customer", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    ChatMessage::factory()->create(["user_id" => $customer->id, "role" => "user", "content" => "Hi"]);

    $response = $this->actingAs($customer)->getJson("/customer/assistant/messages");

    $response->assertOk();
    expect($response->json("messages"))->toHaveCount(1);
    expect($response->json("messages.0.content"))->toBe("Hi");
});

it("forbids non-customers from viewing the assistant", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $this->actingAs($admin)->get("/customer/assistant/messages")->assertForbidden();
});

it("responds with a friendly message when the API key isn't configured", function () {
    config(["openai.api_key" => ""]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $response = $this->actingAs($customer)->postJson("/customer/assistant/messages", [
        "message" => "When is my bill due?",
    ]);

    $response->assertOk();
    expect($response->json("reply.content"))->toContain("isn't configured yet");
    expect($response->json("reply.role"))->toBe("assistant");

    $this->assertDatabaseCount("chat_messages", 2);
    $this->assertDatabaseHas("chat_messages", ["user_id" => $customer->id, "role" => "user", "content" => "When is my bill due?"]);
});

it("sends a message and stores both the user message and the model's reply", function () {
    config(["openai.api_key" => "test-key"]);

    OpenAI::fake([
        CreateResponse::fake([
            "choices" => [
                ["message" => ["content" => "Your next bill is due on the 5th."]],
            ],
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->postJson("/customer/assistant/messages", [
        "message" => "When is my bill due?",
    ]);

    $response->assertOk();
    expect($response->json("reply.content"))->toBe("Your next bill is due on the 5th.");

    $this->assertDatabaseCount("chat_messages", 2);
    $this->assertDatabaseHas("chat_messages", ["user_id" => $customer->id, "role" => "assistant", "content" => "Your next bill is due on the 5th."]);
});

it("falls back gracefully when the OpenAI request fails", function () {
    config(["openai.api_key" => "test-key"]);

    OpenAI::fake([
        new \RuntimeException("The server had an error processing your request."),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $response = $this->actingAs($customer)->postJson("/customer/assistant/messages", [
        "message" => "Why is my water off?",
    ]);

    $response->assertOk();
    expect($response->json("reply.content"))->toContain("couldn't process that");
    $this->assertDatabaseCount("chat_messages", 2);
});

it("rate limits chat messages", function () {
    config(["utility.ai_chat_rate_limit" => 2, "utility.ai_chat_rate_window_minutes" => 1]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $this->actingAs($customer)->postJson("/customer/assistant/messages", ["message" => "one"])->assertOk();
    $this->actingAs($customer)->postJson("/customer/assistant/messages", ["message" => "two"])->assertOk();
    $this->actingAs($customer)->postJson("/customer/assistant/messages", ["message" => "three"])->assertStatus(429);
});

it("rejects an empty message", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $response = $this->actingAs($customer)->postJson("/customer/assistant/messages", ["message" => ""]);

    $response->assertStatus(422);
    $this->assertDatabaseCount("chat_messages", 0);
});
