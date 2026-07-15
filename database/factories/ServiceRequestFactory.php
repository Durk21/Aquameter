<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            "account_id" => Account::factory(),
            "requested_by" => fn (array $attributes) => Account::find($attributes["account_id"])->user_id,
            "type" => fake()->randomElement(config("utility.service_request_types")),
            "zone" => fake()->randomElement(config("utility.zones")),
            "description" => fake()->sentence(),
        ];
    }
}
