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
        Schema::table('products', function (Blueprint $table) {
            // Add multi-company support
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies');

            // Add supplier relationship
            $table->foreignId('primary_supplier_id')->nullable()->after('category_id')->constrained('suppliers');

            // Replace string unit_of_measure with foreign key relationship
            $table->foreignId('unit_of_measure_id')->nullable()->after('primary_supplier_id')->constrained('units_of_measure');

            // Add indexes for performance
            $table->index(['company_id', 'is_active', 'active_at']);
            $table->index('primary_supplier_id');
            $table->index('unit_of_measure_id');
        });

        // Note: The string unit_of_measure field will be kept for now to avoid data loss
        // A separate data migration should handle converting existing values to the new structure
        // After migration, the old field can be dropped in a subsequent migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign keys first (before dropping indexes)
            $table->dropForeign(['company_id']);
            $table->dropForeign(['primary_supplier_id']);
            $table->dropForeign(['unit_of_measure_id']);

            // Now drop indexes (after foreign keys are removed)
            $table->dropIndex(['company_id', 'is_active', 'active_at']);
            $table->dropIndex(['primary_supplier_id']);
            $table->dropIndex(['unit_of_measure_id']);

            // Drop columns
            $table->dropColumn('company_id');
            $table->dropColumn('primary_supplier_id');
            $table->dropColumn('unit_of_measure_id');
        });
    }
};
