<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("bill_id")->unique()->constrained()->cascadeOnDelete();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("recorded_by")->constrained("users")->cascadeOnDelete();
            $table->decimal("amount", 10, 2);
            $table->string("method");
            $table->string("reference")->nullable();
            $table->timestamp("paid_at");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};
