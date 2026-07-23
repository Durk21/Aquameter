<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("activity_logs", function (Blueprint $table) {
            $table->id();
            $table->morphs("subject");
            $table->string("action");
            $table->string("from_status")->nullable();
            $table->string("to_status")->nullable();
            $table->foreignId("caused_by")->nullable()->constrained("users")->nullOnDelete();
            $table->text("notes")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("activity_logs");
    }
};
