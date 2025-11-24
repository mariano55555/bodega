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
        Schema::create('donation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Quantity and valuation
            $table->decimal('quantity', 15, 4);
            $table->decimal('estimated_unit_value', 15, 2)->default(0); // Valor estimado unitario
            $table->decimal('estimated_total_value', 15, 2)->default(0); // quantity * estimated_unit_value

            // Product condition
            $table->string('condition')->default('nuevo'); // nuevo, usado, reacondicionado
            $table->text('condition_notes')->nullable(); // Notas sobre el estado del producto

            // Additional info
            $table->string('lot_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['donation_id']);
            $table->index(['product_id']);
            $table->index(['lot_number']);
            $table->index(['condition']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_details');
    }
};
