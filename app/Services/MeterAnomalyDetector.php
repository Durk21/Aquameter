<?php

namespace App\Services;

use App\Models\Meter;

class MeterAnomalyDetector
{
    /**
     * A reading is anomalous if its consumption (delta from the
     * previous reading) exceeds this multiple of the meter''s
     * historical average consumption. Configurable, not hardcoded.
     */
    public static function isAnomalous(Meter $meter, float $newReadingValue): bool
    {
        $lastReading = $meter->readings()->latest("reading_date")->first();

        if (! $lastReading) {
            return false;
        }

        $delta = $newReadingValue - (float) $lastReading->reading_value;

        if ($delta < 0) {
            return true;
        }

        $averageDelta = self::averageConsumption($meter);

        if ($averageDelta === null) {
            return false;
        }

        $threshold = $averageDelta * config("utility.anomaly_multiplier");

        return $delta > $threshold;
    }

    protected static function averageConsumption(Meter $meter): ?float
    {
        $readings = $meter->readings()->orderBy("reading_date")->pluck("reading_value", "reading_date");

        if ($readings->count() < 2) {
            return null;
        }

        $values = $readings->values();
        $deltas = [];

        for ($i = 1; $i < $values->count(); $i++) {
            $deltas[] = (float) $values[$i] - (float) $values[$i - 1];
        }

        return array_sum($deltas) / count($deltas);
    }
}
