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
        Schema::table('warehouses', function (Blueprint $table) {
            // Add warehouse type: 'general', 'fractional', 'manufacturing', 'port', etc.
            $table->string('warehouse_type', 50)->default('general')->after('code');

            // Add parent warehouse for hierarchy (fractional warehouses have a parent general warehouse)
            $table->foreignId('parent_warehouse_id')->nullable()->after('warehouse_type')->constrained('warehouses')->onDelete('set null');

            // Add level for hierarchy: 0 = General, 1 = Fractional, 2 = Sub-fractional
            $table->integer('level')->default(0)->after('parent_warehouse_id');

            // Add indexes for performance
            $table->index('warehouse_type');
            $table->index('parent_warehouse_id');
            $table->index(['warehouse_type', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['parent_warehouse_id']);
            $table->dropIndex(['warehouses_warehouse_type_index']);
            $table->dropIndex(['warehouses_parent_warehouse_id_index']);
            $table->dropIndex(['warehouses_warehouse_type_level_index']);
            $table->dropColumn(['warehouse_type', 'parent_warehouse_id', 'level']);
        });
    }
};
