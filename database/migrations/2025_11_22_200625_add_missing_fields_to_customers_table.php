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
            // Add missing columns
            $table->string('code', 50)->nullable()->after('slug');
            $table->text('description')->nullable()->after('code');
            $table->string('type', 50)->default('individual')->after('name'); // individual, business
            $table->string('business_name')->nullable()->after('legal_name');
            $table->string('registration_number', 100)->nullable()->after('business_name');
            $table->string('mobile', 50)->nullable()->after('phone');
            $table->string('contact_name')->nullable()->after('contact_person');
            $table->string('contact_position')->nullable()->after('contact_email');
            $table->boolean('same_as_billing')->default(true)->after('shipping_postal_code');
            $table->integer('payment_terms_days')->default(0)->after('payment_terms');
            $table->string('payment_method', 100)->nullable()->after('payment_terms_days');
            $table->string('currency', 3)->default('USD')->after('payment_method');
            $table->string('status', 50)->nullable()->after('currency');
            $table->json('categories')->nullable()->after('notes');
            $table->json('settings')->nullable()->after('categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'description',
                'type',
                'business_name',
                'registration_number',
                'mobile',
                'contact_name',
                'contact_position',
                'same_as_billing',
                'payment_terms_days',
                'payment_method',
                'currency',
                'status',
                'categories',
                'settings',
            ]);
        });
    }
};
