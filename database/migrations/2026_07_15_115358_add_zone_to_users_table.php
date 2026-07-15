<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable — only meaningful for technicians, used to route work
     * orders in their zone to the front of their dispatch queue.
     */
    public function up(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->string("zone")->nullable()->after("email");
        });
    }

    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn("zone");
        });
    }
};
