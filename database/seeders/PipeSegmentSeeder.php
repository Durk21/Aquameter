<?php

namespace Database\Seeders;

use App\Models\PipeSegment;
use Illuminate\Database\Seeder;

class PipeSegmentSeeder extends Seeder
{
    /**
     * Demo pipe lines only — there's no real GIS survey data for this
     * utility's network. Each segment is a short, plausible polyline
     * near its zone's configured center (config('utility.zone_centers')),
     * just enough to give the network map something to show out of the
     * box before an admin draws the real thing.
     */
    public function run(): void
    {
        $segments = [
            ["name" => "Njoro Main Line A", "zone" => "Njoro", "status" => "active", "points" => [
                ["lat" => -0.3310, "lng" => 35.9390], ["lat" => -0.3335, "lng" => 35.9415], ["lat" => -0.3360, "lng" => 35.9440],
            ]],
            ["name" => "Njoro Feeder Line B", "zone" => "Njoro", "status" => "maintenance", "points" => [
                ["lat" => -0.3335, "lng" => 35.9415], ["lat" => -0.3300, "lng" => 35.9450],
            ]],
            ["name" => "Nakuru Town Trunk Line", "zone" => "Nakuru Town", "status" => "active", "points" => [
                ["lat" => -0.2980, "lng" => 36.0740], ["lat" => -0.3030, "lng" => 36.0800], ["lat" => -0.3080, "lng" => 36.0860],
            ]],
            ["name" => "Nakuru CBD Distribution Line", "zone" => "Nakuru Town", "status" => "active", "points" => [
                ["lat" => -0.3030, "lng" => 36.0800], ["lat" => -0.3010, "lng" => 36.0840], ["lat" => -0.2990, "lng" => 36.0880],
            ]],
            ["name" => "Egerton University Line", "zone" => "Egerton", "status" => "active", "points" => [
                ["lat" => -0.3640, "lng" => 35.9280], ["lat" => -0.3700, "lng" => 35.9300], ["lat" => -0.3750, "lng" => 35.9330],
            ]],
            ["name" => "Egerton Damaged Section", "zone" => "Egerton", "status" => "damaged", "points" => [
                ["lat" => -0.3700, "lng" => 35.9300], ["lat" => -0.3720, "lng" => 35.9350],
            ]],
            ["name" => "Lanet Main Line", "zone" => "Lanet", "status" => "active", "points" => [
                ["lat" => -0.2280, "lng" => 36.1440], ["lat" => -0.2330, "lng" => 36.1500], ["lat" => -0.2390, "lng" => 36.1550],
            ]],
            ["name" => "Molo Trunk Line", "zone" => "Molo", "status" => "active", "points" => [
                ["lat" => -0.2450, "lng" => 35.8650], ["lat" => -0.2500, "lng" => 35.8700], ["lat" => -0.2550, "lng" => 35.8750],
            ]],
        ];

        foreach ($segments as $segment) {
            PipeSegment::firstOrCreate(
                ["name" => $segment["name"]],
                ["zone" => $segment["zone"], "status" => $segment["status"], "points" => $segment["points"]],
            );
        }
    }
}
