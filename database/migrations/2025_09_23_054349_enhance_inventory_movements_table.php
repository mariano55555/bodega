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
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Agregar campos para seguimiento de lotes
            $table->foreignId('product_lot_id')->nullable()->after('product_id')->constrained()->onDelete('set null');

            // Agregar campos para razones de movimiento
            $table->foreignId('movement_reason_id')->nullable()->after('movement_type')->constrained()->onDelete('set null');

            // Campos para workflow de aprobación mejorado
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending')->after('metadata');
            $table->foreignId('approved_by')->nullable()->after('confirmed_by')->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('confirmed_at');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable()->after('approval_notes');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            // Campos para seguimiento de ubicaciones específicas
            $table->foreignId('from_storage_location_id')->nullable()->after('from_warehouse_id')->constrained('storage_locations')->onDelete('set null');
            $table->foreignId('to_storage_location_id')->nullable()->after('to_warehouse_id')->constrained('storage_locations')->onDelete('set null');

            // Campos adicionales para auditoría completa
            $table->decimal('previous_quantity', 15, 4)->nullable()->after('quantity'); // Cantidad anterior
            $table->decimal('new_quantity', 15, 4)->nullable()->after('previous_quantity'); // Nueva cantidad
            $table->json('movement_data')->nullable()->after('metadata'); // Datos específicos del movimiento
            $table->string('batch_number')->nullable()->after('lot_number'); // Número de lote de procesamiento
            $table->timestamp('scheduled_at')->nullable()->after('rejected_at'); // Fecha programada
            $table->timestamp('completed_at')->nullable()->after('scheduled_at'); // Fecha de completado
            $table->foreignId('completed_by')->nullable()->after('rejected_by')->constrained('users')->onDelete('set null');

            // Campos para validación de calidad
            $table->boolean('requires_quality_check')->default(false)->after('is_confirmed');
            $table->boolean('quality_approved')->nullable()->after('requires_quality_check');
            $table->foreignId('quality_checked_by')->nullable()->after('completed_by')->constrained('users')->onDelete('set null');
            $table->timestamp('quality_checked_at')->nullable()->after('completed_at');
            $table->text('quality_notes')->nullable()->after('quality_checked_at');

            // Índices adicionales para optimización
            $table->index(['product_lot_id', 'movement_type']);
            $table->index(['movement_reason_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['approved_by', 'approved_at']);
            $table->index(['completed_at', 'status']);
            $table->index(['from_storage_location_id', 'to_storage_location_id'], 'inv_mvmt_storage_locations_idx');
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Eliminar foreign keys primero (antes de los índices)
            $table->dropForeign(['product_lot_id']);
            $table->dropForeign(['movement_reason_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['quality_checked_by']);
            $table->dropForeign(['from_storage_location_id']);
            $table->dropForeign(['to_storage_location_id']);

            // Eliminar índices después de las foreign keys
            $table->dropIndex(['product_lot_id', 'movement_type']);
            $table->dropIndex(['movement_reason_id', 'status']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['approved_by', 'approved_at']);
            $table->dropIndex(['completed_at', 'status']);
            $table->dropIndex('inv_mvmt_storage_locations_idx');
            $table->dropIndex(['scheduled_at', 'status']);

            // Eliminar columnas
            $table->dropColumn([
                'product_lot_id',
                'movement_reason_id',
                'status',
                'approved_by',
                'approved_at',
                'approval_notes',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'from_storage_location_id',
                'to_storage_location_id',
                'previous_quantity',
                'new_quantity',
                'movement_data',
                'batch_number',
                'scheduled_at',
                'completed_at',
                'completed_by',
                'requires_quality_check',
                'quality_approved',
                'quality_checked_by',
                'quality_checked_at',
                'quality_notes',
            ]);
        });
    }
};
