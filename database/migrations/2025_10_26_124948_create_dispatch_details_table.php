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
        Schema::create('dispatch_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('dispatches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_lot_id')->nullable()->constrained('product_lots')->nullOnDelete();

            // Quantity information
            $table->decimal('quantity', 15, 4);
            $table->decimal('quantity_dispatched', 15, 4)->default(0);
            $table->decimal('quantity_delivered', 15, 4)->default(0);
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->onDelete('cascade');

            // Pricing information
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Additional information
            $table->text('notes')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();

            // Stock reservation
            $table->boolean('is_reserved')->default(false);
            $table->timestamp('reserved_at')->nullable();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['dispatch_id', 'product_id']);
            $table->index('product_lot_id');
            $table->index(['is_reserved', 'reserved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_details');
    }
};
