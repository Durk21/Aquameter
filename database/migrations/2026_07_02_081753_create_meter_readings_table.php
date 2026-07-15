<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("meter_readings", function (Blueprint $table) {
            $table->id();
            $table->foreignId("meter_id")->constrained()->cascadeOnDelete();
            $table->foreignId("recorded_by")->constrained("users")->cascadeOnDelete();
            $table->decimal("reading_value", 10, 2);
            $table->date("reading_date");
            $table->boolean("is_anomalous")->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("meter_readings");
    }
};
