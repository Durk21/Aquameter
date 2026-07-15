<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterFactory extends Factory
{
    public function definition(): array
    {
        return [
            "account_id" => Account::factory(),
            "meter_number" => "MTR-".fake()->unique()->numerify("######"),
            "status" => "active",
            "installed_at" => fake()->dateTimeBetween("-3 years", "now"),
        ];
    }
}
