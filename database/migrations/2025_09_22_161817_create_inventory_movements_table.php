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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
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
            ]);
            $table->decimal('quantity', 12, 4); // Can be negative for outgoing movements
            $table->decimal('unit_cost', 10, 4)->nullable(); // Cost per unit at time of movement
            $table->decimal('total_cost', 15, 4)->nullable(); // quantity * unit_cost
            $table->string('reference_number')->nullable(); // Purchase order, sale order, etc.
            $table->text('notes')->nullable();
            $table->string('lot_number')->nullable(); // Batch/lot tracking
            $table->date('expiration_date')->nullable();
            $table->string('location')->nullable(); // Storage location

            // Transfer-specific fields
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            $table->unsignedBigInteger('transfer_id')->nullable(); // Will add foreign key later

            // Related document information
            $table->string('document_type')->nullable(); // invoice, receipt, order, etc.
            $table->string('document_number')->nullable();
            $table->json('metadata')->nullable(); // Additional movement data

            $table->boolean('is_confirmed')->default(true); // For pending movements
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');

            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index('movement_type');
            $table->index('reference_number');
            $table->index('document_number');
            $table->index(['is_active', 'active_at']);
            $table->index(['is_confirmed', 'confirmed_at']);
            $table->index('lot_number');
            $table->index(['from_warehouse_id', 'to_warehouse_id']);
            $table->index('transfer_id');
            $table->index('created_at'); // For chronological queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
