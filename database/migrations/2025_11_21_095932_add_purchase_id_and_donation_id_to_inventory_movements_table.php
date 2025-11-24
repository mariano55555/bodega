<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('dispatch_id')->constrained('purchases')->nullOnDelete();
            $table->foreignId('donation_id')->nullable()->after('purchase_id')->constrained('donations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropForeign(['donation_id']);
            $table->dropColumn(['purchase_id', 'donation_id']);
        });
    }
};
