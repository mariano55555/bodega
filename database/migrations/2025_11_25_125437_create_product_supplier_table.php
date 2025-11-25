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
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            // Código que usa el proveedor para este producto
            $table->string('supplier_code', 50);
            $table->string('supplier_description')->nullable();

            // Información de compra del proveedor
            $table->decimal('supplier_cost', 12, 4)->nullable();
            $table->integer('supplier_unit_measure_code')->nullable();
            $table->string('supplier_unit_measure_name', 50)->nullable();

            // Última vez que se compró de este proveedor
            $table->timestamp('last_purchase_at')->nullable();
            $table->decimal('last_purchase_price', 12, 4)->nullable();

            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['company_id', 'supplier_id', 'supplier_code'], 'product_supplier_unique');
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'supplier_id']);
            $table->index('supplier_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
