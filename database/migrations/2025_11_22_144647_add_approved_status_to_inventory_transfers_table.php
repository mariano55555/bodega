<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify ENUM to include 'approved' status
        DB::statement("ALTER TABLE inventory_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'in_transit', 'received', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'approved' from ENUM (only if no records use it)
        DB::statement("ALTER TABLE inventory_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'received', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
