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
        Schema::create('movement_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código único del motivo
            $table->string('name'); // Nombre descriptivo en español
            $table->string('slug')->unique(); // Para routing amigable
            $table->text('description')->nullable(); // Descripción detallada
            $table->enum('category', ['inbound', 'outbound', 'transfer', 'adjustment', 'disposal']); // Categoría del movimiento
            $table->enum('movement_type', ['in', 'out', 'transfer']); // Tipo de movimiento (entrada, salida, transferencia)
            $table->boolean('requires_approval')->default(false); // Si requiere aprobación
            $table->boolean('requires_documentation')->default(false); // Si requiere documentación
            $table->decimal('approval_threshold', 15, 2)->nullable(); // Valor mínimo que requiere aprobación
            $table->json('required_fields')->nullable(); // Campos requeridos para este tipo de movimiento
            $table->json('validation_rules')->nullable(); // Reglas de validación específicas
            $table->integer('sort_order')->default(0); // Orden de visualización
            $table->text('notes')->nullable(); // Notas adicionales

            // Campos de auditoría estándar del proyecto
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['category', 'movement_type']);
            $table->index(['is_active', 'active_at']);
            $table->index(['requires_approval', 'category']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily to allow dropping
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('movement_reasons');
        Schema::enableForeignKeyConstraints();
    }
};
