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
        // Check if this migration has already been applied (via another migration)
        $hasProductLotId = Schema::hasColumn('inventory_movements', 'product_lot_id');
        $hasMovementReasonId = Schema::hasColumn('inventory_movements', 'movement_reason_id');
        $hasFromStorageLocationId = Schema::hasColumn('inventory_movements', 'from_storage_location_id');

        // If key columns already exist, this migration has already been applied
        if ($hasProductLotId && $hasMovementReasonId && $hasFromStorageLocationId) {
            // Migration changes already exist, skip to avoid duplicate columns
            return;
        }

        // Add movement number if it doesn't exist
        if (! Schema::hasColumn('inventory_movements', 'movement_number')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->string('movement_number')->unique()->after('id')->nullable();
            });
        }

        // Check current movement_type and update if needed
        $currentMovementType = DB::select("SHOW COLUMNS FROM inventory_movements LIKE 'movement_type'")[0] ?? null;
        $needsMovementTypeUpdate = ! $currentMovementType ||
            ! str_contains($currentMovementType->Type, "'in','out','transfer','adjustment'");

        if ($needsMovementTypeUpdate) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropColumn('movement_type');
            });

            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->enum('movement_type', ['in', 'out', 'transfer', 'adjustment'])->after('movement_number');
            });
        }

        // Add missing columns
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Add product lot reference if missing
            if (! Schema::hasColumn('inventory_movements', 'product_lot_id')) {
                $table->foreignId('product_lot_id')->nullable()->after('product_id')->constrained('product_lots')->nullOnDelete();
            }

            // Add movement reason reference if missing
            if (! Schema::hasColumn('inventory_movements', 'movement_reason_id')) {
                $table->foreignId('movement_reason_id')->nullable()->after('movement_type')->constrained('movement_reasons')->nullOnDelete();
            }

            // Add storage location references for better tracking
            $table->foreignId('from_storage_location_id')->nullable()->after('from_warehouse_id')->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('to_storage_location_id')->nullable()->after('to_warehouse_id')->constrained('storage_locations')->nullOnDelete();

            // Add status tracking
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled', 'rejected'])->default('pending')->after('metadata');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->foreignId('completed_by')->nullable()->after('confirmed_by')->constrained('users')->nullOnDelete();

            // Add approval tracking
            $table->foreignId('approved_by')->nullable()->after('completed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('completed_at');
            $table->text('approval_notes')->nullable()->after('approved_at');

            // Add rejection tracking
            $table->foreignId('rejected_by')->nullable()->after('approval_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            // Add inventory level tracking
            $table->decimal('previous_quantity', 15, 4)->nullable()->after('quantity');
            $table->decimal('new_quantity', 15, 4)->nullable()->after('previous_quantity');

            // Add quality control fields
            $table->boolean('requires_quality_check')->default(false)->after('rejection_reason');
            $table->boolean('quality_approved')->nullable()->after('requires_quality_check');
            $table->foreignId('quality_checked_by')->nullable()->after('quality_approved')->constrained('users')->nullOnDelete();
            $table->timestamp('quality_checked_at')->nullable()->after('quality_checked_by');
            $table->text('quality_notes')->nullable()->after('quality_checked_at');

            // Add scheduling support
            $table->timestamp('scheduled_at')->nullable()->after('quality_notes');

            // Add additional tracking fields
            $table->string('batch_number')->nullable()->after('lot_number');
            $table->json('movement_data')->nullable()->after('metadata'); // Additional structured data

            // Add indexes for performance
            $table->index(['movement_type', 'status']);
            $table->index(['product_lot_id', 'status']);
            $table->index(['movement_reason_id', 'created_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['requires_quality_check', 'quality_approved']);
            $table->index('movement_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Remove indexes first
            $table->dropIndex(['movement_type', 'status']);
            $table->dropIndex(['product_lot_id', 'status']);
            $table->dropIndex(['movement_reason_id', 'created_at']);
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['requires_quality_check', 'quality_approved']);
            $table->dropIndex(['movement_number']);

            // Remove foreign key constraints first
            $table->dropForeign(['product_lot_id']);
            $table->dropForeign(['movement_reason_id']);
            $table->dropForeign(['from_storage_location_id']);
            $table->dropForeign(['to_storage_location_id']);
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['quality_checked_by']);

            $table->dropColumn([
                'movement_number',
                'product_lot_id',
                'movement_reason_id',
                'from_storage_location_id',
                'to_storage_location_id',
                'status',
                'completed_at',
                'completed_by',
                'approved_by',
                'approved_at',
                'approval_notes',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'previous_quantity',
                'new_quantity',
                'requires_quality_check',
                'quality_approved',
                'quality_checked_by',
                'quality_checked_at',
                'quality_notes',
                'scheduled_at',
                'batch_number',
                'movement_data',
            ]);

            // Restore original movement_type
            $table->dropColumn('movement_type');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->enum('movement_type', [
                'purchase',     // Incoming from supplier
                'sale',         // Outgoing to customer
                'transfer_out', // Outgoing to another warehouse
                'transfer_in',  // Incoming from another warehouse
                'adjustment',   // Stock adjustment (positive or negative)
                'return',       // Customer return
                'damage',       // Damaged goods
                'theft',        // Theft loss
                'expiry',       // Expired goods
                'production',   // Production consumption/output
                'count',         // Physical count adjustment
            ])->after('warehouse_id');
        });
    }
};
