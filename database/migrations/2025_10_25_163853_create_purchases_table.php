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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

            // Document information
            $table->string('purchase_number')->unique();
            $table->string('slug')->unique();
            $table->enum('document_type', ['factura', 'ccf', 'ticket', 'otro'])->default('factura');
            $table->string('document_number')->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();

            // Purchase type and payment
            $table->enum('purchase_type', ['efectivo', 'credito'])->default('efectivo');
            $table->enum('payment_status', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $table->string('payment_method')->nullable(); // transferencia, efectivo, cheque, etc.
            $table->string('fund_source')->nullable(); // origen de fondos

            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Status and workflow
            $table->enum('status', ['borrador', 'pendiente', 'aprobado', 'recibido', 'cancelado'])->default('borrador');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');

            // Notes and attachments
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('attachments')->nullable(); // URLs to uploaded documents

            // Audit fields
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['document_date']);
            $table->index(['purchase_type', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
