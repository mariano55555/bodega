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
        Schema::create('inventory_closures', function (Blueprint $table) {
            $table->id();

            // Company and Warehouse scope
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();

            // Closure identification
            $table->string('closure_number')->unique(); // CLS-YYYYMM-XXXX
            $table->string('slug')->unique();
            $table->integer('year'); // 2025
            $table->integer('month'); // 1-12
            $table->date('closure_date'); // Date when closure was performed
            $table->date('period_start_date'); // First day of month
            $table->date('period_end_date'); // Last day of month

            // Closure status
            $table->enum('status', ['en_proceso', 'cerrado', 'reabierto', 'cancelado'])->default('en_proceso');

            // Summary totals
            $table->integer('total_products')->default(0); // Number of distinct products
            $table->integer('total_movements')->default(0); // Number of movements in period
            $table->decimal('total_value', 15, 2)->default(0); // Total inventory value
            $table->decimal('total_quantity', 15, 4)->default(0); // Total quantity across all products

            // Discrepancy tracking
            $table->integer('products_with_discrepancies')->default(0);
            $table->decimal('total_discrepancy_value', 15, 2)->default(0);
            $table->text('discrepancy_notes')->nullable();

            // Approval and validation
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Closure execution
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();

            // Reopening tracking
            $table->foreignId('reopened_by')->nullable()->constrained('users');
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopening_reason')->nullable();

            // Additional information
            $table->text('notes')->nullable();
            $table->text('observations')->nullable();
            $table->json('metadata')->nullable(); // Store additional data like pre-closure validations

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamp('active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'warehouse_id', 'year', 'month']);
            $table->index(['status']);
            $table->index(['closure_date']);
            $table->unique(['company_id', 'warehouse_id', 'year', 'month']); // One closure per warehouse per month
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_closures');
    }
};
