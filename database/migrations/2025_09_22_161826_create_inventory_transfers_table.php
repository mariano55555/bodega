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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique(); // Auto-generated transfer reference
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->enum('status', [
                'pending',     // Transfer created but not started
                'in_transit',  // Items have left source warehouse
                'received',    // Items received at destination
                'completed',   // Transfer fully processed
                'cancelled',    // Transfer cancelled
            ])->default('pending');
            $table->text('reason')->nullable(); // Reason for transfer
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional transfer data

            // Dates and timeline
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable(); // When items left source
            $table->timestamp('received_at')->nullable(); // When items arrived at destination
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Approval workflow
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('approval_notes')->nullable();

            // Shipping information
            $table->foreignId('shipped_by')->nullable()->constrained('users');
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();

            // Receiving information
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->text('receiving_notes')->nullable();
            $table->json('receiving_discrepancies')->nullable(); // Any quantity differences

            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('transfer_number');
            $table->index(['from_warehouse_id', 'to_warehouse_id']);
            $table->index(['to_warehouse_id', 'from_warehouse_id']);
            $table->index('status');
            $table->index(['is_active', 'active_at']);
            $table->index('requested_at');
            $table->index('shipped_at');
            $table->index('received_at');
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
