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
        Schema::create('dte_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('slug')->unique();

            // Identificación del DTE
            $table->uuid('codigo_generacion')->unique();
            $table->string('numero_control')->nullable();
            $table->string('tipo_dte', 10);
            $table->date('fecha_emision');
            $table->time('hora_emision')->nullable();

            // Datos del emisor (proveedor)
            $table->string('emisor_nit', 20);
            $table->string('emisor_nrc', 20)->nullable();
            $table->string('emisor_nombre');

            // Totales
            $table->decimal('total_gravado', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total_pagar', 12, 2)->default(0);

            // JSON original completo
            $table->json('json_original');

            // Relaciones con entidades creadas
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();

            // Estado del procesamiento
            $table->enum('status', [
                'pending',      // Subido, pendiente de revisión
                'reviewing',    // En proceso de mapeo
                'ready',        // Listo para crear compra
                'processed',    // Compra creada exitosamente
                'failed',       // Error en procesamiento
                'cancelled',     // Cancelado por usuario
            ])->default('pending');

            $table->text('processing_notes')->nullable();
            $table->json('mapping_data')->nullable(); // Guarda el mapeo de productos realizado
            $table->timestamp('processed_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'emisor_nit']);
            $table->index('fecha_emision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_imports');
    }
};
