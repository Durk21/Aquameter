<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("maintenance_schedules", function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("meter_id")->constrained()->cascadeOnDelete();
            $table->foreignId("created_by")->constrained("users")->cascadeOnDelete();
            $table->date("scheduled_for");
            $table->text("description");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("maintenance_schedules");
    }
};
