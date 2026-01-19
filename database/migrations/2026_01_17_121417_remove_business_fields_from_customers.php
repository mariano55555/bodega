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
        Schema::table('customers', function (Blueprint $table) {
            // Primero eliminar índices relacionados
            $table->dropIndex(['company_id', 'tax_id']);
            $table->dropIndex('customers_credit_limit_index');

            // Eliminar campos business/sales
            $table->dropColumn([
                'type',
                'business_name',
                'registration_number',
                'tax_id',
                'website',
                'legal_name',
                'contact_person',
                'billing_address',
                'billing_city',
                'billing_state',
                'billing_country',
                'billing_postal_code',
                'shipping_address',
                'shipping_city',
                'shipping_state',
                'shipping_country',
                'shipping_postal_code',
                'same_as_billing',

                // Campos payment
                'payment_terms',
                'payment_terms_days',
                'payment_method',
                'currency',
                'credit_limit',
                'discount_percentage',
                'status',

                // Campos redundantes
                'contact_name',
                'contact_email',
                'contact_phone',
                'contact_position',
                'code',
                'description',
                'categories',
                'settings',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir - requeriría recr crear columnas y datos
        Schema::table('customers', function (Blueprint $table) {
            // Restaurar campos básicos
            $table->string('type', 50)->default('individual')->after('name');
            $table->string('business_name')->nullable()->after('type');
            $table->string('registration_number', 100)->nullable()->after('business_name');
            $table->string('tax_id')->nullable()->after('registration_number');
            $table->string('website')->nullable()->after('mobile');
            $table->string('legal_name')->nullable()->after('name');
            $table->string('contact_person')->nullable()->after('same_as_billing');
            $table->string('code', 50)->nullable()->after('slug');
            $table->text('description')->nullable()->after('code');
            $table->json('categories')->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 50)->nullable();

            // Billing fields
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();

            // Shipping fields
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->boolean('same_as_billing')->default(true);

            // Contact fields
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_position')->nullable();

            // Payment fields
            $table->string('payment_terms')->nullable();
            $table->integer('payment_terms_days')->default(0);
            $table->string('payment_method')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();

            // Recrear índices
            $table->index(['company_id', 'tax_id']);
            $table->index('credit_limit');
        });
    }
};
