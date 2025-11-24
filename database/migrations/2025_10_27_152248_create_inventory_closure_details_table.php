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
        Schema::create('inventory_closure_details', function (Blueprint $table) {
            $table->id();

            // Parent closure
            $table->foreignId('inventory_closure_id')->constrained()->cascadeOnDelete();

            // Product information
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Opening balance (from previous closure or initial)
            $table->decimal('opening_quantity', 15, 4)->default(0);
            $table->decimal('opening_unit_cost', 15, 2)->default(0);
            $table->decimal('opening_total_value', 15, 2)->default(0);

            // Period movements
            $table->decimal('quantity_in', 15, 4)->default(0); // Total inbound during period
            $table->decimal('quantity_out', 15, 4)->default(0); // Total outbound during period
            $table->integer('movement_count')->default(0); // Number of movements in period

            // Calculated closing balance (opening + in - out)
            $table->decimal('calculated_closing_quantity', 15, 4)->default(0);
            $table->decimal('calculated_closing_unit_cost', 15, 2)->default(0);
            $table->decimal('calculated_closing_value', 15, 2)->default(0);

            // Physical count (optional, for reconciliation)
            $table->decimal('physical_count_quantity', 15, 4)->nullable();
            $table->decimal('physical_count_unit_cost', 15, 2)->nullable();
            $table->decimal('physical_count_value', 15, 2)->nullable();
            $table->timestamp('physical_count_date')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users');

            // Discrepancy (calculated vs physical)
            $table->decimal('discrepancy_quantity', 15, 4)->default(0);
            $table->decimal('discrepancy_value', 15, 2)->default(0);
            $table->boolean('has_discrepancy')->default(false);
            $table->text('discrepancy_notes')->nullable();

            // Adjusted closing balance (after reconciliation)
            $table->decimal('adjusted_closing_quantity', 15, 4)->default(0);
            $table->decimal('adjusted_closing_unit_cost', 15, 2)->default(0);
            $table->decimal('adjusted_closing_value', 15, 2)->default(0);
            $table->boolean('is_adjusted')->default(false);
            $table->text('adjustment_notes')->nullable();

            // Product status at closure
            $table->boolean('is_active')->default(true);
            $table->boolean('below_minimum')->default(false);
            $table->boolean('above_maximum')->default(false);
            $table->boolean('needs_reorder')->default(false);

            // Additional tracking
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store breakdown by location, lot, etc

            $table->timestamps();

            // Indexes
            $table->index(['inventory_closure_id', 'product_id']);
            $table->index(['has_discrepancy']);
            $table->unique(['inventory_closure_id', 'product_id']); // One record per product per closure
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_closure_details');
    }
};
