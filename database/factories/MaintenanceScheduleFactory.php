<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Meter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            "account_id" => Account::factory(),
            "meter_id" => fn (array $attributes) => Meter::factory()->create(["account_id" => $attributes["account_id"]])->id,
            "created_by" => User::factory(),
            "scheduled_for" => fake()->dateTimeBetween("now", "+30 days"),
            "description" => fake()->sentence(),
        ];
    }
}
