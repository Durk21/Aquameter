<?php

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            "account_id" => Account::factory(),
            "type" => WorkOrderType::Disconnection,
            "status" => WorkOrderStatus::NoticeSent,
            "notice_deadline" => now()->addDays(config("utility.disconnection_notice_days")),
        ];
    }
}
