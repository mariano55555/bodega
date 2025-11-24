<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ENADemoDataSeeder extends Seeder
{
    private $company;

    private $warehouses = [];

    private $products = [];

    private $supplier;

    /**
     * Seed comprehensive demo data for ENA warehouse management system.
     * Implements scenarios 1-5 and 10 from ENA-DEMO-FLUJO.md
     */
    public function run(): void
    {
        $this->command->info('🎓 Iniciando carga de datos demo para ENA...');
        $this->command->newLine();

        // Load base data
        $this->loadBaseData();

        if (! $this->company || empty($this->warehouses)) {
            $this->command->error('❌ Error: Datos base no encontrados. Ejecute los seeders ENA primero.');

            return;
        }

        // Execute scenarios
        $this->scenario1_InitialPurchase();
        $this->scenario2_TransferToCultivos();
        $this->scenario3_InternalDispatch();
        $this->scenario4_TransferBetweenFractional();
        $this->scenario5_FAODonation();
        $this->scenario10_InventoryAdjustment();

        $this->command->newLine();
        $this->command->info('✅ Datos demo ENA cargados exitosamente');
        $this->command->line('   - 1 Compra inicial procesada');
        $this->command->line('   - 2 Traslados completados');
        $this->command->line('   - 1 Despacho interno registrado');
        $this->command->line('   - 1 Donación FAO recibida');
        $this->command->line('   - 1 Ajuste de inventario aprobado');
    }

    /**
     * Load company, warehouses, products, and suppliers from database.
     */
    private function loadBaseData(): void
    {
        $this->command->info('📋 Cargando datos base...');

        // Load company
        $this->company = DB::table('companies')
            ->where('slug', 'escuela-nacional-agricultura')
            ->first();

        if (! $this->company) {
            return;
        }

        // Load warehouses
        $warehouseCodes = ['ENA-BG-001', 'ENA-BF-CULTIVOS', 'ENA-BF-PROCESO', 'ENA-BF-MANT'];
        foreach ($warehouseCodes as $code) {
            $warehouse = DB::table('warehouses')->where('code', $code)->first();
            if ($warehouse) {
                $this->warehouses[$code] = $warehouse;
            }
        }

        // Load products
        $productCodes = ['PRO-001', 'PRO-003', 'PRO-013', 'PRO-023'];
        foreach ($productCodes as $code) {
            $product = DB::table('products')->where('sku', $code)->first();
            if ($product) {
                $this->products[$code] = $product;
            }
        }

        // Load supplier DISAGRO
        $this->supplier = DB::table('suppliers')
            ->where('company_id', $this->company->id)
            ->where('name', 'DISAGRO S.A. de C.V.')
            ->first();

        $this->command->line('✓ Datos base cargados correctamente');
    }

    /**
     * SCENARIO 1: Initial Purchase - DISAGRO supplies NPK Fertilizer.
     * Date: November 1, 2024
     */
    private function scenario1_InitialPurchase(): void
    {
        $this->command->info('📦 Escenario 1: Compra inicial de fertilizante...');

        if (! isset($this->products['PRO-001']) || ! $this->supplier) {
            $this->command->warn('⚠ Saltando escenario 1: producto o proveedor no encontrado');

            return;
        }

        $product = $this->products['PRO-001'];
        $warehouseCentral = $this->warehouses['ENA-BG-001'];
        $date = now()->subDays(19)->setTime(9, 0); // November 1, 2024

        // Create purchase
        $purchaseId = DB::table('purchases')->insertGetId([
            'company_id' => $this->company->id,
            'warehouse_id' => $warehouseCentral->id,
            'supplier_id' => $this->supplier->id,
            'purchase_number' => 'COM-2024-001',
            'slug' => 'com-2024-001',
            'document_type' => 'factura',
            'document_number' => 'FAC-2024-001',
            'document_date' => $date->toDateString(),
            'due_date' => $date->copy()->addDays(30)->toDateString(),
            'purchase_type' => 'credito',
            'payment_status' => 'pagado',
            'payment_method' => 'transferencia',
            'fund_source' => 'Presupuesto MAG 2024',
            'subtotal' => 1250.00,
            'tax_amount' => 162.50, // 13% IVA El Salvador
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total' => 1412.50,
            'status' => 'recibido',
            'approved_at' => $date->copy()->addHours(2),
            'approved_by' => null,
            'received_at' => $date->copy()->addHours(4),
            'received_by' => null,
            'notes' => 'Compra inicial de fertilizante para inicio de ciclo agrícola 2024-2025',
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(4),
        ]);

        // Create purchase detail
        DB::table('purchase_details')->insert([
            'purchase_id' => $purchaseId,
            'product_id' => $product->id,
            'quantity' => 50.0000,
            'unit_cost' => 25.00,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 13.00,
            'tax_amount' => 162.50,
            'subtotal' => 1250.00,
            'total' => 1412.50,
            'lot_number' => 'NPK-2024-08-001',
            'expiration_date' => $date->copy()->addYears(2)->toDateString(),
            'notes' => 'Fertilizante NPK 15-15-15, sacos de 50 lb',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Create inventory movement (purchase)
        $movementId = DB::table('inventory_movements')->insertGetId([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseCentral->id,
            'movement_type' => 'purchase',
            'quantity' => 50.0000,
            'unit_cost' => 25.0000,
            'total_cost' => 1250.0000,
            'reference_number' => 'COM-2024-001',
            'notes' => 'Compra inicial de fertilizante NPK desde DISAGRO',
            'lot_number' => 'NPK-2024-08-001',
            'expiration_date' => $date->copy()->addYears(2)->toDateString(),
            'document_type' => 'purchase',
            'document_number' => 'FAC-2024-001',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(4),
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date->copy()->addHours(4),
            'updated_at' => $date->copy()->addHours(4),
        ]);

        // Update or create inventory record
        $this->updateInventory($warehouseCentral->id, $product->id, 50.0000, 25.0000, 50.0000, $date);

        $this->command->line('✓ Compra COM-2024-001: 50 sacos NPK @ $25.00 = $1,250.00');
        $this->command->line('  → Bodega Central: +50 sacos (Saldo: 50)');
    }

    /**
     * SCENARIO 2: Transfer from Central to Cultivos warehouse.
     * Date: November 5, 2024
     */
    private function scenario2_TransferToCultivos(): void
    {
        $this->command->info('🔄 Escenario 2: Traslado a Bodega Cultivos...');

        if (! isset($this->products['PRO-001'])) {
            $this->command->warn('⚠ Saltando escenario 2: producto no encontrado');

            return;
        }

        $product = $this->products['PRO-001'];
        $warehouseCentral = $this->warehouses['ENA-BG-001'];
        $warehouseCultivos = $this->warehouses['ENA-BF-CULTIVOS'];
        $date = now()->subDays(15)->setTime(10, 30); // November 5, 2024

        // Create inventory transfer
        $transferId = DB::table('inventory_transfers')->insertGetId([
            'transfer_number' => 'TRF-2024-001',
            'from_warehouse_id' => $warehouseCentral->id,
            'to_warehouse_id' => $warehouseCultivos->id,
            'status' => 'completed',
            'reason' => 'Abastecimiento mensual para prácticas de cultivo',
            'notes' => 'Traslado de fertilizante NPK para actividades prácticas del mes de noviembre',
            'requested_at' => $date,
            'approved_at' => $date->copy()->addHours(1),
            'shipped_at' => $date->copy()->addHours(2),
            'received_at' => $date->copy()->addHours(3),
            'completed_at' => $date->copy()->addHours(3),
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(3),
        ]);

        // Movement OUT from Central
        DB::table('inventory_movements')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseCentral->id,
            'movement_type' => 'transfer_out',
            'quantity' => -10.0000,
            'unit_cost' => 25.0000,
            'total_cost' => -250.0000,
            'reference_number' => 'TRF-2024-001',
            'notes' => 'Traslado a Bodega Cultivos',
            'from_warehouse_id' => $warehouseCentral->id,
            'to_warehouse_id' => $warehouseCultivos->id,
            'transfer_id' => $transferId,
            'document_type' => 'transfer',
            'document_number' => 'TRF-2024-001',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(2),
            'is_active' => true,
            'active_at' => $date->copy()->addHours(2),
            'created_at' => $date->copy()->addHours(2),
            'updated_at' => $date->copy()->addHours(2),
        ]);

        // Movement IN to Cultivos
        DB::table('inventory_movements')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseCultivos->id,
            'movement_type' => 'transfer_in',
            'quantity' => 10.0000,
            'unit_cost' => 25.0000,
            'total_cost' => 250.0000,
            'reference_number' => 'TRF-2024-001',
            'notes' => 'Recepción desde Bodega Central',
            'from_warehouse_id' => $warehouseCentral->id,
            'to_warehouse_id' => $warehouseCultivos->id,
            'transfer_id' => $transferId,
            'document_type' => 'transfer',
            'document_number' => 'TRF-2024-001',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(3),
            'is_active' => true,
            'active_at' => $date->copy()->addHours(3),
            'created_at' => $date->copy()->addHours(3),
            'updated_at' => $date->copy()->addHours(3),
        ]);

        // Update inventory balances
        $this->updateInventory($warehouseCentral->id, $product->id, -10.0000, 25.0000, 40.0000, $date->copy()->addHours(2));
        $this->updateInventory($warehouseCultivos->id, $product->id, 10.0000, 25.0000, 10.0000, $date->copy()->addHours(3));

        $this->command->line('✓ Traslado TRF-2024-001: 10 sacos NPK');
        $this->command->line('  → Bodega Central: -10 sacos (Saldo: 40)');
        $this->command->line('  → Bodega Cultivos: +10 sacos (Saldo: 10)');
    }

    /**
     * SCENARIO 3: Internal Dispatch from Cultivos warehouse.
     * Date: November 12, 2024
     */
    private function scenario3_InternalDispatch(): void
    {
        $this->command->info('📤 Escenario 3: Despacho interno desde Bodega Cultivos...');

        if (! isset($this->products['PRO-001'])) {
            $this->command->warn('⚠ Saltando escenario 3: producto no encontrado');

            return;
        }

        $product = $this->products['PRO-001'];
        $warehouseCultivos = $this->warehouses['ENA-BF-CULTIVOS'];
        $date = now()->subDays(8)->setTime(14, 0); // November 12, 2024

        // Create internal dispatch
        $dispatchId = DB::table('dispatches')->insertGetId([
            'company_id' => $this->company->id,
            'warehouse_id' => $warehouseCultivos->id,
            'customer_id' => null,
            'dispatch_number' => 'DSP-2024-023',
            'slug' => 'dsp-2024-023',
            'dispatch_type' => 'interno',
            'destination_unit' => 'Área de Cultivos - Parcela #3',
            'recipient_name' => 'Práctica 2° Año - Ing. José Martínez',
            'recipient_phone' => '+503 7890-1234',
            'recipient_email' => 'jmartinez@ena.gob.sv',
            'document_type' => 'Requisición',
            'document_number' => 'REQ-2024-023',
            'document_date' => $date->toDateString(),
            'subtotal' => 50.00,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total' => 50.00,
            'status' => 'entregado',
            'approved_at' => $date->copy()->addHour(),
            'dispatched_at' => $date->copy()->addHours(2),
            'delivered_at' => $date->copy()->addHours(3),
            'notes' => 'Fertilizante para práctica de aplicación en parcela demostrativa',
            'justification' => 'Práctica estudiantil de fertilización y manejo de cultivos',
            'project_code' => 'EDU-CULTIVOS-2024',
            'cost_center' => 'CULTIVOS',
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(3),
        ]);

        // Create dispatch detail
        DB::table('dispatch_details')->insert([
            'dispatch_id' => $dispatchId,
            'product_id' => $product->id,
            'quantity' => 2.0000,
            'quantity_dispatched' => 2.0000,
            'quantity_delivered' => 2.0000,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'unit_price' => 25.00,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'subtotal' => 50.00,
            'total' => 50.00,
            'batch_number' => 'NPK-2024-08-001',
            'notes' => 'Entregado para Parcela Demostrativa #3',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Create inventory movement (dispatch)
        DB::table('inventory_movements')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseCultivos->id,
            'movement_type' => 'sale',
            'quantity' => -2.0000,
            'unit_cost' => 25.0000,
            'total_cost' => -50.0000,
            'reference_number' => 'DSP-2024-023',
            'notes' => 'Despacho interno: Práctica 2° Año - Parcela #3',
            'document_type' => 'dispatch',
            'document_number' => 'REQ-2024-023',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(3),
            'is_active' => true,
            'active_at' => $date->copy()->addHours(3),
            'created_at' => $date->copy()->addHours(3),
            'updated_at' => $date->copy()->addHours(3),
        ]);

        // Update inventory
        $this->updateInventory($warehouseCultivos->id, $product->id, -2.0000, 25.0000, 8.0000, $date->copy()->addHours(3));

        $this->command->line('✓ Despacho DSP-2024-023: 2 sacos NPK');
        $this->command->line('  → Bodega Cultivos: -2 sacos (Saldo: 8)');
        $this->command->line('  → Destino: Práctica 2° Año - Parcela #3');
    }

    /**
     * SCENARIO 4: Transfer between fractional warehouses.
     * Date: November 15, 2024
     */
    private function scenario4_TransferBetweenFractional(): void
    {
        $this->command->info('🔄 Escenario 4: Traslado entre fraccionarias...');

        if (! isset($this->products['PRO-013'])) {
            $this->command->warn('⚠ Saltando escenario 4: producto no encontrado');

            return;
        }

        $product = $this->products['PRO-013']; // Palas de punta
        $warehouseCultivos = $this->warehouses['ENA-BF-CULTIVOS'];
        $warehouseMantenimiento = $this->warehouses['ENA-BF-MANT'];
        $date = now()->subDays(5)->setTime(11, 0); // November 15, 2024

        // First, ensure Cultivos has some shovels in inventory
        $this->updateInventory($warehouseCultivos->id, $product->id, 15.0000, 12.0000, 15.0000, $date->copy()->subDay());

        // Create inventory transfer
        $transferId = DB::table('inventory_transfers')->insertGetId([
            'transfer_number' => 'TRF-2024-002',
            'from_warehouse_id' => $warehouseCultivos->id,
            'to_warehouse_id' => $warehouseMantenimiento->id,
            'status' => 'completed',
            'reason' => 'Redistribución de herramientas por exceso en área agrícola',
            'notes' => 'Traslado de palas excedentes a Bodega Mantenimiento para uso en reparaciones',
            'requested_at' => $date,
            'approved_at' => $date->copy()->addMinutes(30),
            'shipped_at' => $date->copy()->addHour(),
            'received_at' => $date->copy()->addHours(2),
            'completed_at' => $date->copy()->addHours(2),
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(2),
        ]);

        // Movement OUT from Cultivos
        DB::table('inventory_movements')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseCultivos->id,
            'movement_type' => 'transfer_out',
            'quantity' => -5.0000,
            'unit_cost' => 12.0000,
            'total_cost' => -60.0000,
            'reference_number' => 'TRF-2024-002',
            'notes' => 'Traslado a Bodega Mantenimiento - Redistribución',
            'from_warehouse_id' => $warehouseCultivos->id,
            'to_warehouse_id' => $warehouseMantenimiento->id,
            'transfer_id' => $transferId,
            'document_type' => 'transfer',
            'document_number' => 'TRF-2024-002',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHour(),
            'is_active' => true,
            'active_at' => $date->copy()->addHour(),
            'created_at' => $date->copy()->addHour(),
            'updated_at' => $date->copy()->addHour(),
        ]);

        // Movement IN to Mantenimiento
        DB::table('inventory_movements')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseMantenimiento->id,
            'movement_type' => 'transfer_in',
            'quantity' => 5.0000,
            'unit_cost' => 12.0000,
            'total_cost' => 60.0000,
            'reference_number' => 'TRF-2024-002',
            'notes' => 'Recepción desde Bodega Cultivos',
            'from_warehouse_id' => $warehouseCultivos->id,
            'to_warehouse_id' => $warehouseMantenimiento->id,
            'transfer_id' => $transferId,
            'document_type' => 'transfer',
            'document_number' => 'TRF-2024-002',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(2),
            'is_active' => true,
            'active_at' => $date->copy()->addHours(2),
            'created_at' => $date->copy()->addHours(2),
            'updated_at' => $date->copy()->addHours(2),
        ]);

        // Update inventory balances
        $this->updateInventory($warehouseCultivos->id, $product->id, -5.0000, 12.0000, 10.0000, $date->copy()->addHour());
        $this->updateInventory($warehouseMantenimiento->id, $product->id, 5.0000, 12.0000, 5.0000, $date->copy()->addHours(2));

        $this->command->line('✓ Traslado TRF-2024-002: 5 palas de punta');
        $this->command->line('  → Bodega Cultivos: -5 unidades (Saldo: 10)');
        $this->command->line('  → Bodega Mantenimiento: +5 unidades (Saldo: 5)');
    }

    /**
     * SCENARIO 5: FAO Donation of corn seeds.
     * Date: November 18, 2024
     */
    private function scenario5_FAODonation(): void
    {
        $this->command->info('🎁 Escenario 5: Donación FAO de semilla de maíz...');

        if (! isset($this->products['PRO-003'])) {
            $this->command->warn('⚠ Saltando escenario 5: producto no encontrado');

            return;
        }

        $product = $this->products['PRO-003']; // Semilla Maíz Híbrido H-59
        $warehouseCentral = $this->warehouses['ENA-BG-001'];
        $date = now()->subDays(2)->setTime(10, 0); // November 18, 2024

        // Create donation
        $donationId = DB::table('donations')->insertGetId([
            'company_id' => $this->company->id,
            'warehouse_id' => $warehouseCentral->id,
            'donation_number' => 'DON-2024-005',
            'slug' => 'don-2024-005',
            'donor_name' => 'FAO - Organización ONU Alimentación',
            'donor_type' => 'organization',
            'donor_contact' => 'Coordinación Regional Centroamérica',
            'donor_email' => 'fao-ca@fao.org',
            'donor_phone' => '+503 2000-0000',
            'donor_address' => 'Edificio Naciones Unidas, Boulevard Los Héroes, San Salvador',
            'document_type' => 'acta',
            'document_number' => 'Acta Donación FAO-ENA-2024-05',
            'document_date' => $date->toDateString(),
            'reception_date' => $date->toDateString(),
            'purpose' => 'Fortalecimiento de capacidades educativas agrícolas',
            'intended_use' => 'Prácticas estudiantiles y parcelas demostrativas',
            'project_name' => 'Fortalecimiento Educación Agrícola 2024',
            'estimated_value' => 1800.00,
            'tax_deduction_value' => 0,
            'status' => 'recibido',
            'approved_at' => $date->copy()->addHours(2),
            'received_at' => $date->copy()->addHours(3),
            'notes' => 'Donación de semilla de maíz mejorado para fortalecer programa educativo',
            'conditions' => 'Uso exclusivo para fines educativos y prácticas estudiantiles',
            'tax_receipt_required' => false,
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(3),
        ]);

        // Create donation detail
        DB::table('donation_details')->insert([
            'donation_id' => $donationId,
            'product_id' => $product->id,
            'quantity' => 200.0000, // 200 kg
            'estimated_unit_value' => 9.00,
            'estimated_total_value' => 1800.00,
            'condition' => 'nuevo',
            'condition_notes' => 'Semilla certificada en excelente estado',
            'lot_number' => 'H59-FAO-2024',
            'expiration_date' => $date->copy()->addMonths(6)->toDateString(),
            'notes' => 'Semilla Maíz Híbrido H-59 certificada FAO',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Create inventory movement (donation)
        DB::table('inventory_movements')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseCentral->id,
            'movement_type' => 'adjustment',
            'quantity' => 200.0000,
            'unit_cost' => 9.0000,
            'total_cost' => 1800.0000,
            'reference_number' => 'DON-2024-005',
            'notes' => 'Donación FAO - Semilla de maíz híbrido para educación',
            'lot_number' => 'H59-FAO-2024',
            'expiration_date' => $date->copy()->addMonths(6)->toDateString(),
            'document_type' => 'donation',
            'document_number' => 'Acta Donación FAO-ENA-2024-05',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(3),
            'is_active' => true,
            'active_at' => $date->copy()->addHours(3),
            'created_at' => $date->copy()->addHours(3),
            'updated_at' => $date->copy()->addHours(3),
        ]);

        // Update inventory
        $this->updateInventory($warehouseCentral->id, $product->id, 200.0000, 9.0000, 200.0000, $date->copy()->addHours(3));

        $this->command->line('✓ Donación DON-2024-005: 200 kg Semilla Maíz H-59');
        $this->command->line('  → Valor estimado: $1,800.00');
        $this->command->line('  → Bodega Central: +200 kg (Saldo: 200)');
    }

    /**
     * SCENARIO 10: Inventory Adjustment - Expired yeast detected.
     * Date: November 20, 2024
     */
    private function scenario10_InventoryAdjustment(): void
    {
        $this->command->info('⚖️ Escenario 10: Ajuste de inventario por vencimiento...');

        if (! isset($this->products['PRO-023'])) {
            $this->command->warn('⚠ Saltando escenario 10: producto no encontrado');

            return;
        }

        $product = $this->products['PRO-023']; // Levadura fresca
        $warehouseProceso = $this->warehouses['ENA-BF-PROCESO'];
        $date = now()->setTime(15, 30); // November 20, 2024 (today)

        // First, ensure Procesamiento has some yeast in inventory
        $this->updateInventory($warehouseProceso->id, $product->id, 10.0000, 12.0000, 10.0000, $date->copy()->subDays(7));

        // Create inventory adjustment
        $adjustmentId = DB::table('inventory_adjustments')->insertGetId([
            'company_id' => $this->company->id,
            'warehouse_id' => $warehouseProceso->id,
            'product_id' => $product->id,
            'adjustment_number' => 'ADJ-2024-0123',
            'slug' => 'adj-2024-0123',
            'adjustment_type' => 'expiry',
            'quantity' => -2.0000, // Negative for loss
            'unit_cost' => 12.0000,
            'total_value' => -24.00,
            'reason' => 'Producto vencido detectado en conteo físico',
            'justification' => 'Durante la inspección mensual de inventario se detectaron 2 kg de levadura fresca que superaron su fecha de vencimiento. Producto destruido según protocolo.',
            'corrective_actions' => 'Implementar sistema de rotación FIFO más estricto. Aumentar frecuencia de inspección de productos perecederos.',
            'reference_document' => 'Acta de Destrucción',
            'reference_number' => 'ACT-DEST-123',
            'status' => 'procesado',
            'submitted_at' => $date,
            'approved_at' => $date->copy()->addHour(),
            'processed_at' => $date->copy()->addHours(2),
            'notes' => 'Producto perecedero vencido - Destrucción verificada',
            'cost_center' => 'PROCESAMIENTO',
            'is_active' => true,
            'active_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(2),
        ]);

        // Create inventory movement (adjustment)
        $movementId = DB::table('inventory_movements')->insertGetId([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseProceso->id,
            'movement_type' => 'expiry',
            'quantity' => -2.0000,
            'unit_cost' => 12.0000,
            'total_cost' => -24.0000,
            'reference_number' => 'ADJ-2024-0123',
            'notes' => 'Ajuste por producto vencido - Levadura fresca',
            'document_type' => 'adjustment',
            'document_number' => 'ACT-DEST-123',
            'is_confirmed' => true,
            'confirmed_at' => $date->copy()->addHours(2),
            'is_active' => true,
            'active_at' => $date->copy()->addHours(2),
            'created_at' => $date->copy()->addHours(2),
            'updated_at' => $date->copy()->addHours(2),
        ]);

        // Link movement to adjustment
        DB::table('inventory_adjustments')
            ->where('id', $adjustmentId)
            ->update(['inventory_movement_id' => $movementId]);

        // Update inventory
        $this->updateInventory($warehouseProceso->id, $product->id, -2.0000, 12.0000, 8.0000, $date->copy()->addHours(2));

        $this->command->line('✓ Ajuste ADJ-2024-0123: -2 kg Levadura fresca');
        $this->command->line('  → Tipo: Producto vencido (pérdida)');
        $this->command->line('  → Costo: $24.00');
        $this->command->line('  → Bodega Procesamiento: -2 kg (Saldo: 8)');
    }

    /**
     * Update inventory balance for a product in a warehouse.
     */
    private function updateInventory(int $warehouseId, int $productId, float $quantityChange, float $unitCost, float $newBalance, $timestamp): void
    {
        $inventory = DB::table('inventory')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNull('lot_number')
            ->first();

        if ($inventory) {
            // Update existing inventory
            $newQuantity = max(0, $inventory->quantity + $quantityChange);
            $newAvailable = max(0, $newQuantity - $inventory->reserved_quantity);
            $newTotalValue = $newQuantity * $unitCost;

            DB::table('inventory')
                ->where('id', $inventory->id)
                ->update([
                    'quantity' => $newQuantity,
                    'available_quantity' => $newAvailable,
                    'unit_cost' => $unitCost,
                    'total_value' => $newTotalValue,
                    'updated_at' => $timestamp,
                ]);
        } else {
            // Create new inventory record
            DB::table('inventory')->insert([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => max(0, $quantityChange),
                'reserved_quantity' => 0,
                'available_quantity' => max(0, $quantityChange),
                'unit_cost' => $unitCost,
                'total_value' => max(0, $quantityChange) * $unitCost,
                'is_active' => true,
                'active_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}
