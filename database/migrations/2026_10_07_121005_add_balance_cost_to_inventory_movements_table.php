<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the weighted average cost that results from each movement so the
     * kardex, valuation reports and period closures can read the average at any point in time.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('balance_unit_cost', 15, 5)->nullable()->after('balance_quantity');
            $table->decimal('balance_total_cost', 18, 5)->nullable()->after('balance_unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['balance_unit_cost', 'balance_total_cost']);
        });
    }
};
