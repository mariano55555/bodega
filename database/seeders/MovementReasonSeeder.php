<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MovementReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            // Entradas (Inbound)
            [
                'code' => 'PURCH_RCV',
                'name' => 'Recepción de Compra',
                'description' => 'Entrada de productos por compra a proveedor',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'approval_threshold' => 10000.00,
                'required_fields' => json_encode(['supplier_id', 'purchase_order', 'invoice_number']),
                'sort_order' => 1,
            ],
            [
                'code' => 'PROD_MFG',
                'name' => 'Producción Manufacturada',
                'description' => 'Entrada de productos por proceso de manufactura',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['production_order', 'quality_certificate']),
                'sort_order' => 2,
            ],
            [
                'code' => 'RETURN_CUST',
                'name' => 'Devolución de Cliente',
                'description' => 'Entrada por devolución de productos de clientes',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['customer_id', 'return_reason', 'original_invoice']),
                'sort_order' => 3,
            ],
            [
                'code' => 'ADJ_POS',
                'name' => 'Ajuste Positivo',
                'description' => 'Ajuste de inventario positivo por diferencias encontradas',
                'category' => 'adjustment',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 1000.00,
                'required_fields' => json_encode(['adjustment_reason', 'counted_by', 'variance_report']),
                'sort_order' => 4,
            ],

            // Salidas (Outbound)
            [
                'code' => 'SALE_SHIP',
                'name' => 'Venta/Envío',
                'description' => 'Salida de productos por venta a clientes',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['customer_id', 'invoice_number', 'shipping_address']),
                'sort_order' => 10,
            ],
            [
                'code' => 'INTERNAL_USE',
                'name' => 'Uso Interno',
                'description' => 'Salida de productos para uso interno de la empresa',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 500.00,
                'required_fields' => json_encode(['department', 'purpose', 'authorized_by']),
                'sort_order' => 11,
            ],
            [
                'code' => 'DAMAGE_LOSS',
                'name' => 'Daño/Pérdida',
                'description' => 'Salida por productos dañados o perdidos',
                'category' => 'disposal',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 100.00,
                'required_fields' => json_encode(['damage_reason', 'incident_report', 'photos']),
                'sort_order' => 12,
            ],
            [
                'code' => 'EXPIRY_DISP',
                'name' => 'Disposición por Vencimiento',
                'description' => 'Salida de productos vencidos o próximos a vencer',
                'category' => 'disposal',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['expiry_date', 'disposal_method', 'authorized_by']),
                'sort_order' => 13,
            ],
            [
                'code' => 'ADJ_NEG',
                'name' => 'Ajuste Negativo',
                'description' => 'Ajuste de inventario negativo por diferencias encontradas',
                'category' => 'adjustment',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 1000.00,
                'required_fields' => json_encode(['adjustment_reason', 'counted_by', 'variance_report']),
                'sort_order' => 14,
            ],
            [
                'code' => 'RETURN_SUPP',
                'name' => 'Devolución a Proveedor',
                'description' => 'Salida por devolución de productos a proveedores',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['supplier_id', 'return_reason', 'authorization_number']),
                'sort_order' => 15,
            ],

            // Transferencias (Transfer)
            [
                'code' => 'TRANSFER_OUT',
                'name' => 'Transferencia Salida',
                'description' => 'Salida de productos por transferencia entre ubicaciones',
                'category' => 'transfer',
                'movement_type' => 'transfer',
                'requires_approval' => false,
                'requires_documentation' => true,
                'approval_threshold' => 5000.00,
                'required_fields' => json_encode(['destination_warehouse', 'transfer_order', 'transport_method']),
                'sort_order' => 20,
            ],
            [
                'code' => 'TRANSFER_IN',
                'name' => 'Transferencia Entrada',
                'description' => 'Entrada de productos por transferencia entre ubicaciones',
                'category' => 'transfer',
                'movement_type' => 'transfer',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['origin_warehouse', 'transfer_order', 'received_by']),
                'sort_order' => 21,
            ],

            // Motivos adicionales específicos para El Salvador
            [
                'code' => 'DONATION_IN',
                'name' => 'Donación Recibida',
                'description' => 'Entrada de productos por donación de terceros',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['donor_name', 'donation_certificate', 'tax_exemption']),
                'sort_order' => 5,
            ],
            [
                'code' => 'DONATION_OUT',
                'name' => 'Donación Entregada',
                'description' => 'Salida de productos por donación a terceros',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 500.00,
                'required_fields' => json_encode(['recipient_name', 'donation_purpose', 'authorization']),
                'sort_order' => 16,
            ],
            [
                'code' => 'SAMPLE_OUT',
                'name' => 'Muestra Promocional',
                'description' => 'Salida de productos como muestras promocionales',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['marketing_campaign', 'recipient', 'purpose']),
                'sort_order' => 17,
            ],
            [
                'code' => 'DEMO_USE',
                'name' => 'Uso para Demostración',
                'description' => 'Salida de productos para demostraciones y ferias',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 1000.00,
                'required_fields' => json_encode(['event_name', 'event_date', 'responsible_person']),
                'sort_order' => 18,
            ],
            [
                'code' => 'QUALITY_TEST',
                'name' => 'Prueba de Calidad',
                'description' => 'Salida de productos para pruebas de control de calidad',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['test_type', 'lab_name', 'test_order']),
                'sort_order' => 19,
            ],
            [
                'code' => 'REPACK_IN',
                'name' => 'Reempaque Entrada',
                'description' => 'Entrada de productos después de proceso de reempaque',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['repack_order', 'new_packaging', 'quality_check']),
                'sort_order' => 6,
            ],
            [
                'code' => 'REPACK_OUT',
                'name' => 'Reempaque Salida',
                'description' => 'Salida de productos para proceso de reempaque',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['repack_order', 'packaging_type', 'expected_return']),
                'sort_order' => 20,
            ],
            [
                'code' => 'QUARANTINE_IN',
                'name' => 'Entrada a Cuarentena',
                'description' => 'Entrada de productos a zona de cuarentena',
                'category' => 'transfer',
                'movement_type' => 'transfer',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['quarantine_reason', 'isolation_period', 'responsible_qc']),
                'sort_order' => 22,
            ],
            [
                'code' => 'QUARANTINE_OUT',
                'name' => 'Salida de Cuarentena',
                'description' => 'Salida de productos de zona de cuarentena tras aprobación',
                'category' => 'transfer',
                'movement_type' => 'transfer',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['qc_approval', 'test_results', 'release_certificate']),
                'sort_order' => 23,
            ],
            [
                'code' => 'CONSIGN_OUT',
                'name' => 'Consignación Salida',
                'description' => 'Salida de productos en consignación',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 2000.00,
                'required_fields' => json_encode(['consignee_name', 'consignment_agreement', 'expected_return']),
                'sort_order' => 21,
            ],
            [
                'code' => 'CONSIGN_RET',
                'name' => 'Retorno de Consignación',
                'description' => 'Entrada de productos devueltos de consignación',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'required_fields' => json_encode(['consignment_return', 'condition_report', 'quantity_sold']),
                'sort_order' => 7,
            ],
            [
                'code' => 'THEFT_LOSS',
                'name' => 'Pérdida por Robo',
                'description' => 'Salida de productos por robo o hurto',
                'category' => 'disposal',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'approval_threshold' => 1.00,
                'required_fields' => json_encode(['police_report', 'incident_date', 'security_report']),
                'sort_order' => 24,
            ],
            [
                'code' => 'RECALL_OUT',
                'name' => 'Retiro del Mercado',
                'description' => 'Salida de productos por retiro del mercado',
                'category' => 'disposal',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['recall_notice', 'authority_order', 'disposal_method']),
                'sort_order' => 25,
            ],
            [
                'code' => 'INITIAL_STOCK',
                'name' => 'Inventario Inicial',
                'description' => 'Entrada de inventario inicial del sistema',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'required_fields' => json_encode(['inventory_date', 'counted_by', 'verification_method']),
                'sort_order' => 8,
            ],
            [
                'code' => 'CYCLE_COUNT',
                'name' => 'Conteo Cíclico',
                'description' => 'Ajuste por conteo cíclico de inventario',
                'category' => 'adjustment',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'approval_threshold' => 500.00,
                'required_fields' => json_encode(['count_date', 'counter_name', 'variance_reason']),
                'sort_order' => 26,
            ],
        ];

        // Clear existing data first (respecting foreign key constraints)
        DB::table('movement_reasons')->delete();

        foreach ($reasons as $reason) {
            $reason['slug'] = Str::slug($reason['name']);
            $reason['is_active'] = true;
            $reason['active_at'] = now();
            $reason['created_at'] = now();
            $reason['updated_at'] = now();

            // Add validation rules based on category
            if (! isset($reason['validation_rules'])) {
                $reason['validation_rules'] = $this->getValidationRules($reason['category'], $reason['movement_type']);
            }

            DB::table('movement_reasons')->insert($reason);
        }
    }

    /**
     * Get validation rules based on category and movement type.
     */
    private function getValidationRules(string $category, string $movementType): ?string
    {
        $rules = match ($category) {
            'inbound' => [
                'quantity' => 'required|numeric|min:0.01',
                'unit_cost' => 'required|numeric|min:0',
                'supplier_id' => 'sometimes|exists:suppliers,id',
            ],
            'outbound' => [
                'quantity' => 'required|numeric|min:0.01',
                'customer_id' => 'sometimes|exists:customers,id',
            ],
            'transfer' => [
                'quantity' => 'required|numeric|min:0.01',
                'from_warehouse_id' => 'sometimes|exists:warehouses,id',
                'to_warehouse_id' => 'sometimes|exists:warehouses,id|different:from_warehouse_id',
            ],
            'adjustment' => [
                'quantity' => 'required|numeric',
                'reason_detail' => 'required|string|min:10',
            ],
            'disposal' => [
                'quantity' => 'required|numeric|min:0.01',
                'disposal_reason' => 'required|string',
            ],
            default => null,
        };

        return $rules ? json_encode($rules) : null;
    }
}
