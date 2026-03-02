<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            // Modify ENUM to include 'approved' status
            DB::statement("ALTER TABLE inventory_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'in_transit', 'received', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
        // SQLite doesn't support ENUM or MODIFY COLUMN; the column already accepts any string value
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            // Remove 'approved' from ENUM (only if no records use it)
            DB::statement("ALTER TABLE inventory_transfers MODIFY COLUMN status ENUM('pending', 'in_transit', 'received', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
