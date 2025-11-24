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
        Schema::table('product_categories', function (Blueprint $table) {
            // Add multi-company support
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies');

            // Add index for performance
            $table->index(['company_id', 'is_active', 'active_at']);
        });

        // Note: No need to drop slug unique constraint as it doesn't exist
        // Add new unique constraint for slug within company scope
        Schema::table('product_categories', function (Blueprint $table) {
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // Drop foreign key first (before dropping index)
            $table->dropForeign(['company_id']);

            // Now drop index (after foreign key is removed)
            $table->dropIndex(['company_id', 'is_active', 'active_at']);

            // Drop column (this will automatically drop any unique constraints involving this column)
            $table->dropColumn('company_id');
        });
    }
};
