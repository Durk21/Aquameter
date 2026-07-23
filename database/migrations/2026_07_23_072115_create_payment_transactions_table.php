<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("payment_transactions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("bill_id")->constrained()->cascadeOnDelete();
            $table->foreignId("account_id")->constrained()->cascadeOnDelete();
            $table->foreignId("initiated_by")->constrained("users")->cascadeOnDelete();
            $table->foreignId("payment_id")->nullable()->constrained()->nullOnDelete();
            $table->string("method");
            $table->string("status")->default("pending");
            $table->decimal("amount", 10, 2);
            $table->string("phone")->nullable();
            $table->string("gateway")->nullable();
            $table->string("checkout_request_id")->nullable();
            $table->string("merchant_reference")->nullable()->unique();
            $table->string("order_tracking_id")->nullable();
            $table->text("failure_reason")->nullable();
            $table->timestamp("confirmed_at")->nullable();
            $table->timestamps();

            $table->index("bill_id");
            $table->index("checkout_request_id");
            $table->index("order_tracking_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("payment_transactions");
    }
};
