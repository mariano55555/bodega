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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Adjustment identification
            $table->string('adjustment_number')->unique(); // ADJ-YYYYMMDD-XXXXXX
            $table->string('slug')->unique();

            // Adjustment details
            $table->enum('adjustment_type', [
                'positive',     // Surplus/Found inventory
                'negative',     // Shortage/Missing inventory
                'damage',       // Damaged goods
                'expiry',       // Expired products
                'loss',         // Lost/Stolen inventory
                'correction',   // Counting error correction
                'return',       // Customer/Supplier return
                'other',        // Other reasons
            ])->default('correction');

            $table->decimal('quantity', 15, 4); // Can be positive or negative
            $table->decimal('unit_cost', 15, 4)->nullable(); // Cost per unit at adjustment time
            $table->decimal('total_value', 15, 2)->default(0); // Total monetary impact

            // Reason and justification
            $table->text('reason'); // Short reason/title
            $table->text('justification')->nullable(); // Detailed explanation
            $table->text('corrective_actions')->nullable(); // What will be done to prevent this

            // Reference information
            $table->string('reference_document')->nullable(); // External document reference
            $table->string('reference_number')->nullable();
            $table->json('attachments')->nullable(); // Supporting documents/photos

            // Workflow status
            $table->enum('status', [
                'borrador',     // Draft - being created
                'pendiente',    // Pending approval
                'aprobado',     // Approved - ready to process
                'procesado',    // Processed - inventory updated
                'rechazado',    // Rejected
                'cancelado',    // Cancelled
            ])->default('borrador');

            // Approval tracking
            $table->timestamp('submitted_at')->nullable(); // When submitted for approval
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();

            // Rejection tracking
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Processing tracking
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();

            // Additional details
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable(); // Internal admin notes

            // Cost center and project tracking
            $table->string('cost_center')->nullable();
            $table->string('project_code')->nullable();
            $table->string('department')->nullable();

            // Active status
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['company_id', 'product_id']);
            $table->index(['status', 'created_at']);
            $table->index(['adjustment_type', 'status']);
            $table->index('adjustment_number');
            $table->index(['warehouse_id', 'product_id', 'status']);
            $table->index(['approved_at', 'approved_by']);
            $table->index(['processed_at', 'processed_by']);
            $table->index('inventory_movement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
