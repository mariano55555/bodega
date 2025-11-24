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
            // Add dispatch_id foreign key after transfer_id
            if (! Schema::hasColumn('inventory_movements', 'dispatch_id')) {
                $table->foreignId('dispatch_id')->nullable()->after('transfer_id')->constrained('dispatches')->nullOnDelete();
                $table->index('dispatch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'dispatch_id')) {
                $table->dropForeign(['dispatch_id']);
                $table->dropIndex(['dispatch_id']);
                $table->dropColumn('dispatch_id');
            }
        });
    }
};
