<?php

namespace Database\Seeders;

use App\Models\MovementReason;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder para los tipos de transacciones de la Escuela Nacional de Agricultura (ENA).
 *
 * Este seeder crea las 42 razones de movimiento basadas en el sistema anterior del cliente.
 * Los códigos legacy (E0, S1, etc.) se mantienen para migración de datos históricos.
 */
class ENAMovementReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primero, eliminar las razones existentes para evitar duplicados
        MovementReason::query()->forceDelete();

        $reasons = $this->getENATransactionTypes();

        foreach ($reasons as $reason) {
            MovementReason::create([
                'code' => $reason['code'],
                'legacy_code' => $reason['legacy_code'],
                'legacy_name' => $reason['legacy_name'],
                'name' => $reason['name'],
                'slug' => Str::slug($reason['name']),
                'description' => $reason['description'],
                'category' => $reason['category'],
                'movement_type' => $reason['movement_type'],
                'requires_approval' => $reason['requires_approval'] ?? false,
                'requires_documentation' => $reason['requires_documentation'] ?? true,
                'affects_cost' => $reason['affects_cost'],
                'approval_threshold' => $reason['approval_threshold'] ?? null,
                'required_fields' => $reason['required_fields'] ?? null,
                'sort_order' => $reason['sort_order'],
                'is_active' => true,
                'active_at' => now(),
            ]);
        }

        $this->command->info('Se crearon '.count($reasons).' tipos de transacciones ENA.');
    }

    /**
     * Get all ENA transaction types from PDF document.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getENATransactionTypes(): array
    {
        return [
            // =====================================================
            // ENTRADAS (25 tipos) - Códigos E*
            // =====================================================

            // E0 - COMPRAS LOCALES (Valorizado: S)
            [
                'code' => 'PURCH_LOCAL',
                'legacy_code' => 'E0',
                'legacy_name' => 'COMPRAS LOCALES',
                'name' => 'Compras Locales',
                'description' => 'Entrada de productos por compras a proveedores locales',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true,
                'approval_threshold' => 10000.00,
                'required_fields' => json_encode(['supplier_id', 'invoice_number']),
                'sort_order' => 1,
            ],

            // E2 - INGRESOS A BODEGA POR CAMBIO (Valorizado: S)
            [
                'code' => 'CHANGE_IN',
                'legacy_code' => 'E2',
                'legacy_name' => 'INGRESOS A BODEGA POR CAMBIO',
                'name' => 'Ingresos a Bodega por Cambio',
                'description' => 'Entrada de productos por cambio o devolución',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 2,
            ],

            // E3 - DEV. DE CLIENTES (VTA.CONTADO) (Valorizado: S)
            [
                'code' => 'RETURN_CASH',
                'legacy_code' => 'E3',
                'legacy_name' => 'DEV. DE CLIENTES (VTA.CONTADO)',
                'name' => 'Devolución de Clientes (Venta Contado)',
                'description' => 'Entrada por devolución de clientes de ventas al contado',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['customer_id', 'return_reason', 'original_invoice']),
                'sort_order' => 3,
            ],

            // E4 - C/FONDO DE MTTO DE ESTUDIANTES (Valorizado: S)
            [
                'code' => 'STUDENT_FUND',
                'legacy_code' => 'E4',
                'legacy_name' => 'C/FONDO DE MTTO DE ESTUDIANTES',
                'name' => 'Cargo Fondo Mantenimiento Estudiantes',
                'description' => 'Entrada por fondo de mantenimiento de estudiantes',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 4,
            ],

            // E5 - ENTRADA POR OBSOLENCIA (Valorizado: S)
            [
                'code' => 'OBSOL_IN',
                'legacy_code' => 'E5',
                'legacy_name' => 'ENTRADA POR OBSOLENCIA',
                'name' => 'Entrada por Obsolescencia',
                'description' => 'Entrada de productos por reclasificación de obsolescencia',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 5,
            ],

            // E6 - DEVOLUCION POR VENTA (Valorizado: N)
            [
                'code' => 'RETURN_SALE',
                'legacy_code' => 'E6',
                'legacy_name' => 'DEVOLUCION POR VENTA',
                'name' => 'Devolución por Venta',
                'description' => 'Entrada por devolución de productos vendidos',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['customer_id', 'return_reason']),
                'sort_order' => 6,
            ],

            // E7 - ENTRADA POR PROYECTOS (Valorizado: S)
            [
                'code' => 'PROJECT_IN',
                'legacy_code' => 'E7',
                'legacy_name' => 'ENTRADA POR PROYECTOS',
                'name' => 'Entrada por Proyectos',
                'description' => 'Entrada de productos adquiridos para proyectos específicos',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['project_code', 'project_name']),
                'sort_order' => 7,
            ],

            // E8 - ENTRADA POR REINGRESO A BODEGA (Valorizado: S)
            [
                'code' => 'REENTRY',
                'legacy_code' => 'E8',
                'legacy_name' => 'ENTRADA POR REINGRESO A BODEGA',
                'name' => 'Entrada por Reingreso a Bodega',
                'description' => 'Reingreso de productos que habían salido temporalmente',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 8,
            ],

            // E9 - INGRESO POR PERMUTAS (Valorizado: S)
            [
                'code' => 'EXCHANGE_IN',
                'legacy_code' => 'E9',
                'legacy_name' => 'INGRESO POR PERMUTAS',
                'name' => 'Ingreso por Permutas',
                'description' => 'Entrada de productos por intercambio o permuta',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 9,
            ],

            // EA - ENTRADA POR AVERIA (Valorizado: N)
            [
                'code' => 'DAMAGE_IN',
                'legacy_code' => 'EA',
                'legacy_name' => 'ENTRADA POR AVERIA',
                'name' => 'Entrada por Avería',
                'description' => 'Entrada de productos con averías para registro',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['damage_description', 'photos']),
                'sort_order' => 10,
            ],

            // EB - ENTRADA POR BONIFICACION (Valorizado: N)
            [
                'code' => 'BONUS_IN',
                'legacy_code' => 'EB',
                'legacy_name' => 'ENTRADA POR BONIFICACION',
                'name' => 'Entrada por Bonificación',
                'description' => 'Entrada de productos recibidos como bonificación de proveedores',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['supplier_id']),
                'sort_order' => 11,
            ],

            // EC - ENTRADA POR CONSIGNACION (Valorizado: N)
            [
                'code' => 'CONSIGN_IN',
                'legacy_code' => 'EC',
                'legacy_name' => 'ENTRADA POR CONSIGNACION',
                'name' => 'Entrada por Consignación',
                'description' => 'Entrada de productos recibidos en consignación',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['consignment_agreement', 'supplier_id']),
                'sort_order' => 12,
            ],

            // ED - NOTAS DE CREDITO (Valorizado: N)
            [
                'code' => 'CREDIT_NOTE',
                'legacy_code' => 'ED',
                'legacy_name' => 'NOTAS DE CREDITO',
                'name' => 'Notas de Crédito',
                'description' => 'Entrada por aplicación de notas de crédito',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['credit_note_number']),
                'sort_order' => 13,
            ],

            // EI - INGRESOS POR CONVENIO (Valorizado: S)
            [
                'code' => 'AGREEMENT_IN',
                'legacy_code' => 'EI',
                'legacy_name' => 'INGRESOS POR CONVENIO',
                'name' => 'Ingresos por Convenio',
                'description' => 'Entrada de productos por convenios institucionales',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['agreement_number', 'institution']),
                'sort_order' => 14,
            ],

            // EJ - ENTRADAS POR AJUSTE DE INV. (Valorizado: S)
            [
                'code' => 'ADJ_POS',
                'legacy_code' => 'EJ',
                'legacy_name' => 'ENTRADAS POR AJUSTE DE INV.',
                'name' => 'Entrada por Ajuste de Inventario',
                'description' => 'Ajuste positivo de inventario por diferencias encontradas',
                'category' => 'adjustment',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'approval_threshold' => 1000.00,
                'required_fields' => json_encode(['adjustment_reason', 'counted_by']),
                'sort_order' => 15,
            ],

            // EM - AJUSTE POR MEDICIONES (Valorizado: N)
            [
                'code' => 'MEASURE_IN',
                'legacy_code' => 'EM',
                'legacy_name' => 'AJUSTE POR MEDICIONES',
                'name' => 'Ajuste por Mediciones (Entrada)',
                'description' => 'Ajuste positivo por diferencias en mediciones físicas',
                'category' => 'adjustment',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'sort_order' => 16,
            ],

            // EN - ENTRADA POR DONACION (Valorizado: S)
            [
                'code' => 'DONATION_IN',
                'legacy_code' => 'EN',
                'legacy_name' => 'ENTRADA POR DONACION',
                'name' => 'Entrada por Donación',
                'description' => 'Entrada de productos recibidos por donación',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['donor_name', 'donation_certificate']),
                'sort_order' => 17,
            ],

            // EP - INGRESOS DE PRODUCCION (Valorizado: S)
            [
                'code' => 'PROD_IN',
                'legacy_code' => 'EP',
                'legacy_name' => 'INGRESOS DE PRODUCCION',
                'name' => 'Ingresos de Producción',
                'description' => 'Entrada de productos de producción propia',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['production_order', 'department']),
                'sort_order' => 18,
            ],

            // ER - DEVOLUCION DE REPUESTOS (Valorizado: D - Depende)
            [
                'code' => 'PARTS_RETURN',
                'legacy_code' => 'ER',
                'legacy_name' => 'DEVOLUCION DE REPUESTOS',
                'name' => 'Devolución de Repuestos',
                'description' => 'Entrada por devolución de repuestos no utilizados',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true, // D = depende, asumimos true por defecto
                'sort_order' => 19,
            ],

            // ES - COMPRAS LOCALES (duplicado de E0, Valorizado: S)
            // Nota: Se omite por ser duplicado - confirmar con cliente

            // ET - ENTRADA POR TRASLADO/BODEGA (Valorizado: N)
            [
                'code' => 'TRANSFER_IN',
                'legacy_code' => 'ET',
                'legacy_name' => 'ENTRADA POR TRASLADO/BODEGA',
                'name' => 'Entrada por Traslado de Bodega',
                'description' => 'Entrada de productos por transferencia desde otra bodega',
                'category' => 'transfer',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['origin_warehouse', 'transfer_number']),
                'sort_order' => 20,
            ],

            // EU - ENTRADA POR AUTOCONSUMO (Valorizado: S)
            [
                'code' => 'SELF_CONSUME_IN',
                'legacy_code' => 'EU',
                'legacy_name' => 'ENTRADA POR AUTOCONSUMO',
                'name' => 'Entrada por Autoconsumo',
                'description' => 'Entrada de productos de autoconsumo interno',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 21,
            ],

            // EX - DESCUENTOS (Valorizado: S)
            [
                'code' => 'DISCOUNT_IN',
                'legacy_code' => 'EX',
                'legacy_name' => 'DESCUENTOS',
                'name' => 'Descuentos',
                'description' => 'Entrada por aplicación de descuentos',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true,
                'sort_order' => 22,
            ],

            // EY - ENTRADA POR PRODUCTO TERMINADO (Valorizado: S)
            [
                'code' => 'FINISHED_PROD',
                'legacy_code' => 'EY',
                'legacy_name' => 'ENTRADA POR PRODUCTO TERMINADO',
                'name' => 'Entrada por Producto Terminado',
                'description' => 'Entrada de productos terminados de manufactura',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['production_order', 'quality_check']),
                'sort_order' => 23,
            ],

            // EZ - INVENTARIO INICIAL (Valorizado: S)
            [
                'code' => 'INITIAL_STOCK',
                'legacy_code' => 'EZ',
                'legacy_name' => 'INVENTARIO INICIAL',
                'name' => 'Inventario Inicial',
                'description' => 'Entrada de inventario inicial del sistema',
                'category' => 'inbound',
                'movement_type' => 'in',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['inventory_date', 'counted_by']),
                'sort_order' => 24,
            ],

            // =====================================================
            // SALIDAS (17 tipos) - Códigos S*
            // =====================================================

            // S0 - VENTAS-CREDITO FISCAL (Valorizado: N)
            [
                'code' => 'SALE_CREDIT',
                'legacy_code' => 'S0',
                'legacy_name' => 'VENTAS-CREDITO FISCAL',
                'name' => 'Ventas - Crédito Fiscal',
                'description' => 'Salida por ventas con crédito fiscal',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['customer_id', 'invoice_number']),
                'sort_order' => 30,
            ],

            // S1 - VENTA A CONSUMIDOR FINAL (Valorizado: N)
            [
                'code' => 'SALE_FINAL',
                'legacy_code' => 'S1',
                'legacy_name' => 'VENTA A CONSUMIDOR FINAL',
                'name' => 'Venta a Consumidor Final',
                'description' => 'Salida por ventas a consumidor final',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['invoice_number']),
                'sort_order' => 31,
            ],

            // S2 - TICKETS (Valorizado: N)
            [
                'code' => 'SALE_TICKET',
                'legacy_code' => 'S2',
                'legacy_name' => 'TICKETS',
                'name' => 'Tickets',
                'description' => 'Salida por ventas con ticket',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['ticket_number']),
                'sort_order' => 32,
            ],

            // S3 - SALIDA POR CAMBIO DE PDTOS. (Valorizado: N)
            [
                'code' => 'CHANGE_OUT',
                'legacy_code' => 'S3',
                'legacy_name' => 'SALIDA POR CAMBIO DE PDTOS.',
                'name' => 'Salida por Cambio de Productos',
                'description' => 'Salida de productos por cambio o reemplazo',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'sort_order' => 33,
            ],

            // S4 - SALIDA POR DESCARTE DE PROD. (Valorizado: S)
            [
                'code' => 'DISCARD',
                'legacy_code' => 'S4',
                'legacy_name' => 'SALIDA POR DESCARTE DE PROD.',
                'name' => 'Salida por Descarte de Productos',
                'description' => 'Salida de productos por descarte o baja',
                'category' => 'disposal',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['discard_reason', 'authorized_by']),
                'sort_order' => 34,
            ],

            // S5 - SALIDA POR OBSOLECENCIA (Valorizado: N)
            [
                'code' => 'OBSOL_OUT',
                'legacy_code' => 'S5',
                'legacy_name' => 'SALIDA POR OBSOLECENCIA',
                'name' => 'Salida por Obsolescencia',
                'description' => 'Salida de productos obsoletos',
                'category' => 'disposal',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['obsolescence_reason']),
                'sort_order' => 35,
            ],

            // SB - DESPACHO DE BODEGA (Valorizado: N)
            [
                'code' => 'DISPATCH',
                'legacy_code' => 'SB',
                'legacy_name' => 'DESPACHO DE BODEGA',
                'name' => 'Despacho de Bodega',
                'description' => 'Salida por despacho general de bodega',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['dispatch_number', 'destination']),
                'sort_order' => 36,
            ],

            // SC - CONSUMO COMBUSTIBLE/LUBR (Valorizado: N)
            [
                'code' => 'FUEL_CONSUME',
                'legacy_code' => 'SC',
                'legacy_name' => 'CONSUMO COMBUSTIBLE/LUBR',
                'name' => 'Consumo Combustible/Lubricantes',
                'description' => 'Salida por consumo de combustibles y lubricantes',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['vehicle_id', 'mileage']),
                'sort_order' => 37,
            ],

            // SD - DEVOLUCIONES A PROVEEDORES (Valorizado: N)
            [
                'code' => 'RETURN_SUPP',
                'legacy_code' => 'SD',
                'legacy_name' => 'DEVOLUCIONES A PROVEEDORES',
                'name' => 'Devoluciones a Proveedores',
                'description' => 'Salida por devolución de productos a proveedores',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['supplier_id', 'return_reason', 'authorization']),
                'sort_order' => 38,
            ],

            // SG - SALIDA POR GARANTIA (Valorizado: N)
            [
                'code' => 'WARRANTY_OUT',
                'legacy_code' => 'SG',
                'legacy_name' => 'SALIDA POR GARANTIA',
                'name' => 'Salida por Garantía',
                'description' => 'Salida de productos por aplicación de garantía',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['warranty_claim', 'original_invoice']),
                'sort_order' => 39,
            ],

            // SJ - SALIDA POR AJUSTE DE INV. (Valorizado: N)
            [
                'code' => 'ADJ_NEG',
                'legacy_code' => 'SJ',
                'legacy_name' => 'SALIDA POR AJUSTE DE INV.',
                'name' => 'Salida por Ajuste de Inventario',
                'description' => 'Ajuste negativo de inventario por diferencias encontradas',
                'category' => 'adjustment',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'approval_threshold' => 1000.00,
                'required_fields' => json_encode(['adjustment_reason', 'counted_by']),
                'sort_order' => 40,
            ],

            // SM - REQUISICIONES / MANTENIMIENTO (Valorizado: S)
            [
                'code' => 'REQ_MAINT',
                'legacy_code' => 'SM',
                'legacy_name' => 'REQUISICIONES / MANTENIMIENTO',
                'name' => 'Requisiciones / Mantenimiento',
                'description' => 'Salida por requisiciones para mantenimiento',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => true,
                'required_fields' => json_encode(['requisition_number', 'department']),
                'sort_order' => 41,
            ],

            // SN - AJUSTE POR MEDICIONES (Valorizado: N)
            [
                'code' => 'MEASURE_OUT',
                'legacy_code' => 'SN',
                'legacy_name' => 'AJUSTE POR MEDICIONES',
                'name' => 'Ajuste por Mediciones (Salida)',
                'description' => 'Ajuste negativo por diferencias en mediciones físicas',
                'category' => 'adjustment',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'sort_order' => 42,
            ],

            // SP - SALIDA POR VTA DE KIT (Valorizado: N)
            [
                'code' => 'KIT_SALE',
                'legacy_code' => 'SP',
                'legacy_name' => 'SALIDA POR VTA DE KIT',
                'name' => 'Salida por Venta de Kit',
                'description' => 'Salida de productos por venta de kits o paquetes',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['kit_code', 'invoice_number']),
                'sort_order' => 43,
            ],

            // SR - REQUISICIONES (Valorizado: N)
            [
                'code' => 'REQUISITION',
                'legacy_code' => 'SR',
                'legacy_name' => 'REQUISICIONES',
                'name' => 'Requisiciones',
                'description' => 'Salida por requisiciones internas',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => true,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['requisition_number', 'department', 'requested_by']),
                'sort_order' => 44,
            ],

            // ST - SALIDA POR TRASLADO / BODEGA (Valorizado: N)
            [
                'code' => 'TRANSFER_OUT',
                'legacy_code' => 'ST',
                'legacy_name' => 'SALIDA POR TRASLADO / BODEGA',
                'name' => 'Salida por Traslado de Bodega',
                'description' => 'Salida de productos por transferencia a otra bodega',
                'category' => 'transfer',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['destination_warehouse', 'transfer_number']),
                'sort_order' => 45,
            ],

            // SV - REMISIONES A CLIENTES (Valorizado: N)
            [
                'code' => 'REMISSION',
                'legacy_code' => 'SV',
                'legacy_name' => 'REMISIONES A CLIENTES',
                'name' => 'Remisiones a Clientes',
                'description' => 'Salida de productos por remisión a clientes',
                'category' => 'outbound',
                'movement_type' => 'out',
                'requires_approval' => false,
                'requires_documentation' => true,
                'affects_cost' => false,
                'required_fields' => json_encode(['customer_id', 'remission_number']),
                'sort_order' => 46,
            ],
        ];
    }
}
