<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("work_orders", function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("bill_id")->nullable()->constrained()->nullOnDelete();
            $table->string("type");
            $table->string("status");
            $table->foreignId("created_by")->nullable()->constrained("users")->nullOnDelete();
            $table->foreignId("assigned_to")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("claimed_at")->nullable();
            $table->foreignId("signed_off_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("signed_off_at")->nullable();
            $table->timestamp("notice_deadline")->nullable();
            $table->foreignId("disputed_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("disputed_at")->nullable();
            $table->text("dispute_reason")->nullable();
            $table->timestamp("completed_at")->nullable();
            $table->text("resolution_notes")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("work_orders");
    }
};
