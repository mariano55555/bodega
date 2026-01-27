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
        Schema::create('internal_production_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_production_id')->constrained('internal_productions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Product information
            $table->text('description')->nullable(); // Descripcion del producto
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->onDelete('cascade');

            // Quantity and pricing
            $table->decimal('quantity', 15, 5);
            $table->decimal('unit_price', 15, 5)->default(0);
            $table->decimal('subtotal', 15, 5)->default(0);
            $table->decimal('total', 15, 5)->default(0);

            // Additional information
            $table->text('notes')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiration_date')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['internal_production_id', 'product_id'], 'int_prod_details_prod_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_production_details');
    }
};
