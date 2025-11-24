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
            // Add company_id column after id
            $table->foreignId('company_id')->after('id')->constrained('companies')->cascadeOnDelete();

            // Add index for company_id and related fields
            $table->index(['company_id', 'is_active', 'active_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['company_id', 'is_active', 'active_at']);

            // Drop the foreign key constraint and column
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
