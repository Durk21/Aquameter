<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\AccountNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            "user_id" => User::factory(),
            "account_number" => AccountNumberGenerator::generate(),
            "address" => fake()->streetAddress(),
            "zone" => fake()->randomElement(config("utility.zones")),
            "status" => AccountStatus::Active,
            "defaulted_at" => null,
        ];
    }
}
