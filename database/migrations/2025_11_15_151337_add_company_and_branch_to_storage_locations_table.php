<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            // Add company_id and branch_id after warehouse_id (nullable first, we'll populate them)
            $table->foreignId('company_id')->nullable()->after('warehouse_id');
            $table->foreignId('branch_id')->nullable()->after('company_id');

            // Add parent_location_id for hierarchical structure
            $table->foreignId('parent_location_id')->nullable()->after('branch_id');

            // Add level and sort_order for hierarchy management
            $table->unsignedTinyInteger('level')->default(0)->after('parent_location_id');
            $table->unsignedInteger('sort_order')->default(0)->after('level');

            // Add pickable and receivable flags
            $table->boolean('is_pickable')->default(true)->after('is_active');
            $table->boolean('is_receivable')->default(true)->after('is_pickable');
        });

        // Populate company_id and branch_id from warehouse
        DB::statement('
            UPDATE storage_locations sl
            INNER JOIN warehouses w ON sl.warehouse_id = w.id
            SET sl.company_id = w.company_id,
                sl.branch_id = w.branch_id
        ');

        // Now make company_id NOT NULL and add foreign keys
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('parent_location_id')->references('id')->on('storage_locations');

            // Add indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
            $table->index('parent_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropIndex(['branch_id', 'is_active']);
            $table->dropIndex(['parent_location_id']);

            // Drop foreign keys
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['parent_location_id']);

            // Drop columns
            $table->dropColumn([
                'company_id',
                'branch_id',
                'parent_location_id',
                'level',
                'sort_order',
                'is_pickable',
                'is_receivable',
            ]);
        });
    }
};
