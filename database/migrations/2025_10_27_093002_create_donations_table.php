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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();

            // Donation information
            $table->string('donation_number')->unique();
            $table->string('slug')->unique();
            $table->string('donor_name'); // Nombre del donante
            $table->string('donor_type')->default('individual'); // individual, organization, government
            $table->string('donor_contact')->nullable(); // Contacto del donante
            $table->string('donor_email')->nullable();
            $table->string('donor_phone')->nullable();
            $table->text('donor_address')->nullable();

            // Document information
            $table->enum('document_type', ['acta', 'carta', 'convenio', 'otro'])->default('acta');
            $table->string('document_number')->nullable();
            $table->date('document_date');
            $table->date('reception_date'); // Fecha de recepción física

            // Purpose and usage
            $table->string('purpose')->nullable(); // Propósito de la donación
            $table->string('intended_use')->nullable(); // Uso previsto (proyecto, área, etc.)
            $table->string('project_name')->nullable(); // Nombre del proyecto (si aplica)

            // Amounts
            $table->decimal('estimated_value', 15, 2)->default(0); // Valor estimado total
            $table->decimal('tax_deduction_value', 15, 2)->nullable(); // Valor para deducción fiscal

            // Status and workflow
            $table->enum('status', ['borrador', 'pendiente', 'aprobado', 'recibido', 'cancelado'])->default('borrador');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');

            // Notes and attachments
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('conditions')->nullable(); // Condiciones de la donación
            $table->json('attachments')->nullable(); // URLs to uploaded documents (actas, fotos, etc.)

            // Tax information
            $table->boolean('tax_receipt_required')->default(false); // Requiere comprobante fiscal
            $table->string('tax_receipt_number')->nullable();
            $table->date('tax_receipt_date')->nullable();

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
            $table->index(['document_date']);
            $table->index(['reception_date']);
            $table->index(['donor_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
