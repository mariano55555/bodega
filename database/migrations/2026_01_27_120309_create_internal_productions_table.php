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
        Schema::create('internal_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Origin and Destination
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade'); // Unidad de Origen
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade'); // Bodega Destino
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // Persona que entrega

            // Production identification
            $table->string('production_number')->unique(); // PI-XX-BOD-YYY format
            $table->string('slug')->unique();

            // Document information
            $table->string('physical_document_number', 100); // Numero de documento fisico (manual, required)
            $table->date('document_date')->nullable();

            // Financial details
            $table->decimal('subtotal', 15, 5)->default(0);
            $table->decimal('total', 15, 5)->default(0);

            // Workflow status
            $table->enum('status', ['borrador', 'pendiente', 'aprobado', 'completado', 'cancelado'])->default('borrador');

            // Approval tracking
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();

            // Completion tracking
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            // Notes and attachments
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('attachments')->nullable();

            // Active status (following existing pattern)
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
            $table->index(['company_id', 'area_id']);
            $table->index(['status', 'created_at']);
            $table->index('production_number');
            $table->index('physical_document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_productions');
    }
};
