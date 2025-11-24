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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('quantity', 12, 4)->default(0); // Current stock quantity
            $table->decimal('reserved_quantity', 12, 4)->default(0); // Reserved for orders
            $table->decimal('available_quantity', 12, 4)->default(0); // quantity - reserved_quantity
            $table->string('location')->nullable(); // Aisle, shelf, bin location
            $table->string('lot_number')->nullable(); // Batch/lot tracking
            $table->date('expiration_date')->nullable(); // For perishable items
            $table->decimal('unit_cost', 10, 4)->nullable(); // Current unit cost for valuation
            $table->decimal('total_value', 15, 4)->default(0); // quantity * unit_cost
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Last stock counts
            $table->decimal('last_count_quantity', 12, 4)->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->foreignId('last_counted_by')->nullable()->constrained('users');

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint - one inventory record per product-warehouse combination
            $table->unique(['product_id', 'warehouse_id', 'lot_number']);

            // Indexes
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['is_active', 'active_at']);
            $table->index('quantity');
            $table->index('available_quantity');
            $table->index('expiration_date');
            $table->index('lot_number');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
