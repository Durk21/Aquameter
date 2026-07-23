<?php

use App\Enums\ComplaintStatus;
use App\Models\Account;
use App\Models\ChatMessage;
use App\Models\Complaint;
use App\Models\Meter;
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

it("fetches message history for an admin", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));
    ChatMessage::factory()->create(["user_id" => $admin->id, "role" => "user", "content" => "Anything urgent?"]);

    $response = $this->actingAs($admin)->getJson("/admin/assistant/messages");

    $response->assertOk();
    expect($response->json("messages"))->toHaveCount(1);
    expect($response->json("messages.0.content"))->toBe("Anything urgent?");
});

it("forbids non-admins from viewing the admin assistant", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));

    $this->actingAs($customer)->get("/admin/assistant/messages")->assertForbidden();
});

it("grounds admin replies in the current triage data", function () {
    config(["openai.api_key" => "test-key"]);

    OpenAI::fake([
        CreateResponse::fake([
            "choices" => [
                ["message" => ["content" => "You have 1 open complaint in Lanet that needs attention."]],
            ],
        ]),
    ]);

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $customerAccount = Account::factory()->create(["user_id" => $customer->id, "zone" => "Lanet"]);

    Complaint::create([
        "account_id" => $customerAccount->id,
        "submitted_by" => $customer->id,
        "subject" => "No water pressure",
        "description" => "Pressure has been low for days.",
        "status" => ComplaintStatus::Submitted,
    ]);

    $response = $this->actingAs($admin)->postJson("/admin/assistant/messages", [
        "message" => "What needs my attention?",
    ]);

    $response->assertOk();
    expect($response->json("reply.content"))->toBe("You have 1 open complaint in Lanet that needs attention.");

    OpenAI::assertSent(\OpenAI\Resources\Chat::class, function ($method, $parameters) {
        $system = collect($parameters["messages"])->firstWhere("role", "system")["content"] ?? "";

        return str_contains($system, "No water pressure") && str_contains($system, "Open complaints");
    });
});

it("grounds customer replies in a plain-language anomaly explanation", function () {
    config(["openai.api_key" => "test-key"]);

    OpenAI::fake([
        CreateResponse::fake([
            "choices" => [
                ["message" => ["content" => "That reading was flagged because usage was far above your normal average."]],
            ],
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);

    $meter->readings()->create(["recorded_by" => $customer->id, "reading_value" => 100, "reading_date" => now()->subDays(60)]);
    $meter->readings()->create(["recorded_by" => $customer->id, "reading_value" => 110, "reading_date" => now()->subDays(30)]);
    $meter->readings()->create(["recorded_by" => $customer->id, "reading_value" => 500, "reading_date" => now(), "is_anomalous" => true]);

    $response = $this->actingAs($customer)->postJson("/customer/assistant/messages", [
        "message" => "Why was my last reading flagged?",
    ]);

    $response->assertOk();
    expect($response->json("reply.content"))->toBe("That reading was flagged because usage was far above your normal average.");

    OpenAI::assertSent(\OpenAI\Resources\Chat::class, function ($method, $parameters) {
        $system = collect($parameters["messages"])->firstWhere("role", "system")["content"] ?? "";

        return str_contains($system, "Anomaly on") && str_contains($system, "typical average of 10");
    });
});
