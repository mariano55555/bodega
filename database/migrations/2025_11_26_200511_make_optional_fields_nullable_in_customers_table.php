<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Campos obligatorios (NOT NULL):
     * - code, name, type (del Excel)
     * - company_id, slug (del sistema)
     *
     * Todos los demás campos se hacen nullable.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Campos de dirección de facturación
            $table->string('billing_address')->nullable()->change();
            $table->string('billing_city')->nullable()->change();
            $table->string('billing_state')->nullable()->change();
            $table->string('billing_country')->nullable()->change();
            $table->string('billing_postal_code')->nullable()->change();

            // Campos de dirección de envío
            $table->string('shipping_address')->nullable()->change();
            $table->string('shipping_city')->nullable()->change();
            $table->string('shipping_state')->nullable()->change();
            $table->string('shipping_country')->nullable()->change();
            $table->string('shipping_postal_code')->nullable()->change();

            // Campos financieros
            $table->decimal('credit_limit', 15, 2)->nullable()->change();
            $table->decimal('discount_percentage', 5, 2)->nullable()->change();

            // Campos de contacto y pago
            $table->string('currency')->nullable()->change();
            $table->string('payment_terms')->nullable()->change();
            $table->string('payment_method')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Revertir a NOT NULL con valores por defecto
            $table->string('billing_address')->default('')->change();
            $table->string('billing_city')->default('')->change();
            $table->string('billing_state')->default('')->change();
            $table->string('billing_country')->default('El Salvador')->change();
            $table->string('billing_postal_code')->default('')->change();

            $table->string('shipping_address')->default('')->change();
            $table->string('shipping_city')->default('')->change();
            $table->string('shipping_state')->default('')->change();
            $table->string('shipping_country')->default('El Salvador')->change();
            $table->string('shipping_postal_code')->default('')->change();

            $table->decimal('credit_limit', 15, 2)->default(0)->change();
            $table->decimal('discount_percentage', 5, 2)->default(0)->change();

            $table->string('currency')->default('USD')->change();
            $table->string('payment_terms')->default('')->change();
            $table->string('payment_method')->default('')->change();
        });
    }
};
