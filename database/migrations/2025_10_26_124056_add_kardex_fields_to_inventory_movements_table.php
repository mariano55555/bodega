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
            // Add Kardex-specific fields for tracking running balances
            $table->decimal('quantity_in', 15, 4)->default(0)->after('quantity');
            $table->decimal('quantity_out', 15, 4)->default(0)->after('quantity_in');
            $table->decimal('balance_quantity', 15, 4)->nullable()->after('quantity_out');
            $table->date('movement_date')->nullable()->after('movement_type');

            // Add company_id for multi-company support (if not exists)
            if (! Schema::hasColumn('inventory_movements', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            }

            // Add indexes for better query performance
            $table->index(['product_id', 'warehouse_id', 'movement_date'], 'kardex_query_idx');
            $table->index(['company_id', 'product_id', 'warehouse_id'], 'company_product_warehouse_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('kardex_query_idx');
            $table->dropIndex('company_product_warehouse_idx');

            if (Schema::hasColumn('inventory_movements', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }

            $table->dropColumn(['quantity_in', 'quantity_out', 'balance_quantity', 'movement_date']);
        });
    }
};
