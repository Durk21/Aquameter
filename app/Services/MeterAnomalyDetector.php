<?php

namespace App\Services;

use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Support\Collection;

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

        $averageDelta = self::averageConsumption(
            $meter->readings()->orderBy("reading_date")->pluck("reading_value", "reading_date"),
        );

        if ($averageDelta === null) {
            return false;
        }

        $threshold = $averageDelta * config("utility.anomaly_multiplier");

        return $delta > $threshold;
    }

    /**
     * Plain-language reconstruction of why a stored reading was flagged,
     * reusing the same delta/threshold math as isAnomalous() — grounds
     * the AI assistant's explanation in the actual numbers instead of
     * letting it guess.
     */
    public static function explain(MeterReading $reading): string
    {
        $meter = $reading->meter;

        $previous = $meter->readings()
            ->where("reading_date", "<", $reading->reading_date)
            ->latest("reading_date")
            ->first();

        if (! $previous) {
            return "flagged because it was the meter's first reading on file, so there was nothing to compare it against.";
        }

        $delta = (float) $reading->reading_value - (float) $previous->reading_value;

        if ($delta < 0) {
            return "flagged because the reading ({$reading->reading_value}) is lower than the previous reading "
                . "({$previous->reading_value} on {$previous->reading_date->toDateString()}), which usually means a meter "
                . "reset, rollover, or a reading error rather than actual usage.";
        }

        $averageDelta = self::averageConsumption(
            $meter->readings()
                ->where("reading_date", "<", $reading->reading_date)
                ->orderBy("reading_date")
                ->pluck("reading_value", "reading_date"),
        );

        if ($averageDelta === null) {
            return "used ".round($delta, 2)." units since the previous reading, but there wasn't enough reading "
                . "history yet to compare that against a typical average.";
        }

        $multiplier = config("utility.anomaly_multiplier");
        $threshold = round($averageDelta * $multiplier, 2);

        return "used ".round($delta, 2)." units since the previous reading on {$previous->reading_date->toDateString()}, "
            . "compared to this meter's typical average of ".round($averageDelta, 2)." units — that's more than "
            . "{$multiplier}x the usual amount (flag threshold: {$threshold} units).";
    }

    protected static function averageConsumption(Collection $readings): ?float
    {
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
