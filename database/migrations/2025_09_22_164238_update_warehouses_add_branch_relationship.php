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
            // Add branch relationship
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches');

            // Add index for performance
            $table->index(['branch_id', 'is_active', 'active_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Drop foreign key first (before dropping index)
            $table->dropForeign(['branch_id']);

            // Drop index after foreign key is removed
            $table->dropIndex(['branch_id', 'is_active', 'active_at']);

            // Drop column
            $table->dropColumn('branch_id');
        });
    }
};
