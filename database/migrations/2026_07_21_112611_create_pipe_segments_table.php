<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("pipe_segments", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("zone");
            $table->string("status")->default("active");
            $table->json("points");
            $table->foreignId("created_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("pipe_segments");
    }
};
