<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("bills", function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("meter_reading_id")->constrained()->cascadeOnDelete();
            $table->decimal("previous_reading_value", 10, 2);
            $table->decimal("current_reading_value", 10, 2);
            $table->decimal("units_consumed", 10, 2);
            $table->decimal("rate_applied", 10, 2);
            $table->decimal("amount", 10, 2);
            $table->string("status")->default("pending");
            $table->date("due_date");
            $table->timestamp("paid_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("bills");
    }
};
