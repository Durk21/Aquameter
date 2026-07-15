<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("complaints", function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("bill_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("submitted_by")->constrained("users")->cascadeOnDelete();
            $table->string("subject");
            $table->text("description");
            $table->string("status")->default("submitted");
            $table->text("resolution_notes")->nullable();
            $table->foreignId("resolved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("resolved_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("complaints");
    }
};
