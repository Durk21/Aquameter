<?php

namespace Database\Factories;

use App\Enums\ChatRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            "user_id" => User::factory(),
            "role" => ChatRole::User,
            "content" => fake()->sentence(),
        ];
    }
}
