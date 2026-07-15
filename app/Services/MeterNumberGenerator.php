<?php

namespace App\Services;

use App\Models\Meter;

class MeterNumberGenerator
{
    public static function generate(): string
    {
        $prefix = config("utility.meter_number_prefix");

        do {
            $candidate = $prefix."-".str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
        } while (Meter::where("meter_number", $candidate)->exists());

        return $candidate;
    }
}
