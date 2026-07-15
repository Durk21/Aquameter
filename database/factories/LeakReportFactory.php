<?php

namespace Database\Factories;

use App\Enums\LeakSeverity;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeakReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            "account_id" => Account::factory(),
            "reported_by" => fn (array $attributes) => Account::find($attributes["account_id"])->user_id,
            "severity" => LeakSeverity::Medium,
            "zone" => fake()->randomElement(config("utility.zones")),
            "description" => fake()->sentence(),
        ];
    }
}
