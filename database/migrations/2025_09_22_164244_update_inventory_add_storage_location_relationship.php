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
        Schema::table('inventory', function (Blueprint $table) {
            // Add storage location relationship
            $table->foreignId('storage_location_id')->nullable()->after('warehouse_id')->constrained('storage_locations');

            // Add index for performance
            $table->index('storage_location_id');

            // Note: The existing string 'location' field will be kept for backward compatibility
            // A data migration can later move location data to the new storage_location_id relationship
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Drop foreign key first (before dropping index)
            $table->dropForeign(['storage_location_id']);

            // Drop index after foreign key is removed
            $table->dropIndex(['storage_location_id']);

            // Drop column
            $table->dropColumn('storage_location_id');
        });
    }
};
