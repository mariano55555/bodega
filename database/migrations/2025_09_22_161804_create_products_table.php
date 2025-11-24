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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories');
            $table->string('unit_of_measure', 50); // pieces, kg, liters, etc.
            $table->decimal('cost', 10, 2)->nullable(); // Purchase cost
            $table->decimal('price', 10, 2)->nullable(); // Selling price
            $table->string('barcode')->nullable()->unique();
            $table->json('attributes')->nullable(); // Additional product attributes
            $table->string('image_path')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Inventory valuation method for this product
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('fifo');

            // Minimum stock levels
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'active_at']);
            $table->index('sku');
            $table->index('barcode');
            $table->index('category_id');
            $table->index('track_inventory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
