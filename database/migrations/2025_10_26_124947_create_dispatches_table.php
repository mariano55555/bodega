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
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');

            // Dispatch identification
            $table->string('dispatch_number')->unique();
            $table->string('slug')->unique();

            // Dispatch details
            $table->enum('dispatch_type', ['venta', 'interno', 'externo', 'donacion'])->default('interno');
            $table->string('destination_unit')->nullable(); // Unidad operativa destino
            $table->string('recipient_name')->nullable(); // Nombre del receptor
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->text('delivery_address')->nullable();

            // Document information
            $table->string('document_type')->nullable(); // Factura, Guía de remisión, etc.
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();

            // Financial details
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Workflow status
            $table->enum('status', ['borrador', 'pendiente', 'aprobado', 'despachado', 'entregado', 'cancelado'])->default('borrador');

            // Approval tracking
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();

            // Dispatch tracking
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carrier')->nullable(); // Transportista
            $table->string('tracking_number')->nullable();

            // Delivery tracking
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('received_by_name')->nullable(); // Nombre de quien recibió
            $table->text('delivery_notes')->nullable();

            // Notes and attachments
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('attachments')->nullable();

            // Justification (required for internal dispatches)
            $table->text('justification')->nullable();
            $table->string('project_code')->nullable();
            $table->string('cost_center')->nullable();

            // Active status
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['status', 'created_at']);
            $table->index(['dispatch_type', 'status']);
            $table->index('dispatch_number');
            $table->index('document_number');
            $table->index(['customer_id', 'dispatch_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
