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
        Schema::create('product_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->string('lot_number')->unique(); // Número de lote único
            $table->string('slug')->unique(); // Para routing amigable
            $table->date('manufactured_date')->nullable(); // Fecha de fabricación
            $table->date('expiration_date')->nullable(); // Fecha de vencimiento
            $table->decimal('quantity_produced', 15, 4)->default(0); // Cantidad producida original
            $table->decimal('quantity_remaining', 15, 4)->default(0); // Cantidad restante
            $table->decimal('unit_cost', 15, 4)->nullable(); // Costo unitario del lote
            $table->enum('status', ['active', 'expired', 'quarantine', 'disposed'])->default('active'); // Estado del lote
            $table->string('batch_certificate')->nullable(); // Certificado de lote/calidad
            $table->json('quality_attributes')->nullable(); // Atributos de calidad específicos
            $table->text('notes')->nullable(); // Notas adicionales
            $table->json('metadata')->nullable(); // Datos adicionales flexibles

            // Campos de auditoría estándar del proyecto
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['product_id', 'status']);
            $table->index(['expiration_date', 'status']);
            $table->index(['lot_number', 'product_id']);
            $table->index(['is_active', 'active_at']);
            $table->index(['status', 'expiration_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily to allow dropping
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('product_lots');
        Schema::enableForeignKeyConstraints();
    }
};
