<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("meters", function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->string("meter_number")->unique();
            $table->string("status")->default("active");
            $table->date("installed_at");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("meters");
    }
};
