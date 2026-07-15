<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\MeterReading;
use App\Notifications\BillGenerated;

class BillGenerator
{
    /**
     * Generate a bill from a newly recorded reading, based on the
     * delta from the previous reading. Returns null if this is the
     * meter''s first ever reading — there is nothing to bill yet.
     */
    public static function generateFor(MeterReading $reading): ?Bill
    {
        $previousReading = MeterReading::where("meter_id", $reading->meter_id)
            ->where("id", "!=", $reading->id)
            ->where(function ($query) use ($reading) {
                $query->where("reading_date", "<", $reading->reading_date)
                    ->orWhere(function ($query) use ($reading) {
                        $query->where("reading_date", "=", $reading->reading_date)
                            ->where("id", "<", $reading->id);
                    });
            })
            ->orderByDesc("reading_date")
            ->orderByDesc("id")
            ->first();

        if (! $previousReading) {
            return null;
        }

        $unitsConsumed = max(0, (float) $reading->reading_value - (float) $previousReading->reading_value);
        $rate = config("utility.rate_per_unit");
        $amount = round($unitsConsumed * $rate, 2);

        $account = $reading->meter->account;

        $bill = Bill::create([
            "account_id" => $account->id,
            "meter_reading_id" => $reading->id,
            "previous_reading_value" => $previousReading->reading_value,
            "current_reading_value" => $reading->reading_value,
            "units_consumed" => $unitsConsumed,
            "rate_applied" => $rate,
            "amount" => $amount,
            "status" => BillStatus::Pending,
            "due_date" => now()->addDays(config("utility.bill_due_days"))->toDateString(),
        ]);

        $account->user->notify(new BillGenerated($bill));

        return $bill;
    }
}
