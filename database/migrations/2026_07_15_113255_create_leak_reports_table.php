<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("leak_reports", function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("reported_by")->constrained("users")->cascadeOnDelete();
            $table->foreignId("meter_id")->nullable()->constrained()->nullOnDelete();
            $table->string("severity");
            $table->string("zone");
            $table->string("location_notes")->nullable();
            $table->decimal("latitude", 10, 7)->nullable();
            $table->decimal("longitude", 10, 7)->nullable();
            $table->text("description");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("leak_reports");
    }
};
