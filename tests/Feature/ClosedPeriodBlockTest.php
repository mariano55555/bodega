<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\InventoryClosure;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);

    // Create movement reasons needed by the models
    MovementReason::factory()->create([
        'code' => 'DISPATCH',
        'movement_type' => 'out',
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);
    MovementReason::factory()->create([
        'code' => 'PURCHASE',
        'movement_type' => 'in',
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);
    MovementReason::factory()->create([
        'code' => 'ADJ_POS',
        'movement_type' => 'in',
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);
    MovementReason::factory()->create([
        'code' => 'ADJ_NEG',
        'movement_type' => 'out',
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);
});

// ── validatePeriodOpen static method ──

test('validatePeriodOpen throws exception when period is closed', function () {
    InventoryClosure::factory()->closed()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    InventoryClosure::validatePeriodOpen($this->company->id, $this->warehouse->id, '2026-02-15');
})->throws(\Exception::class, 'cierre mensual ya fue realizado');

test('validatePeriodOpen does not throw when period is in process', function () {
    InventoryClosure::factory()->inProcess()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    InventoryClosure::validatePeriodOpen($this->company->id, $this->warehouse->id, '2026-02-15');

    expect(true)->toBeTrue();
});

test('validatePeriodOpen does not throw when period is reopened', function () {
    InventoryClosure::factory()->reopened()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    InventoryClosure::validatePeriodOpen($this->company->id, $this->warehouse->id, '2026-02-15');

    expect(true)->toBeTrue();
});

test('validatePeriodOpen does not throw when period is cancelled', function () {
    InventoryClosure::factory()->cancelled()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    InventoryClosure::validatePeriodOpen($this->company->id, $this->warehouse->id, '2026-02-15');

    expect(true)->toBeTrue();
});

test('validatePeriodOpen does not throw when no closure exists', function () {
    InventoryClosure::validatePeriodOpen($this->company->id, $this->warehouse->id, '2026-02-15');

    expect(true)->toBeTrue();
});

test('validatePeriodOpen does not throw for a different month', function () {
    InventoryClosure::factory()->closed()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    // March should be fine even though February is closed
    InventoryClosure::validatePeriodOpen($this->company->id, $this->warehouse->id, '2026-03-15');

    expect(true)->toBeTrue();
});

test('validatePeriodOpen does not throw for a different warehouse', function () {
    $otherWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);

    InventoryClosure::factory()->closed()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    // Other warehouse should be fine
    InventoryClosure::validatePeriodOpen($this->company->id, $otherWarehouse->id, '2026-02-15');

    expect(true)->toBeTrue();
});

// ── Dispatch blocks on closed period ──

test('dispatch is blocked when period is closed', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    // Create inventory so stock exists
    Inventory::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'available_quantity' => 100,
    ]);

    // Create a balance movement
    InventoryMovement::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $product->id,
        'balance_quantity' => 100,
        'movement_date' => '2026-02-10',
    ]);

    // Close February
    InventoryClosure::factory()->closed()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => 'aprobado',
        'document_date' => '2026-02-15',
    ]);

    DispatchDetail::factory()->create([
        'dispatch_id' => $dispatch->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $dispatch->load('details');

    $result = $dispatch->dispatch($this->user->id);

    expect($result)->toBeFalse();
});

// ── Purchase blocks on closed period ──

test('purchase receive is blocked when period is closed', function () {
    InventoryClosure::factory()->closed()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => 2026,
        'month' => 2,
        'period_start_date' => '2026-02-01',
        'period_end_date' => '2026-02-28',
    ]);

    $purchase = Purchase::factory()->approved()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'document_date' => '2026-02-20',
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $purchase->load('details');

    $result = $purchase->receive($this->user->id);

    expect($result)->toBeFalse();
});

// ── InventoryAdjustment blocks on closed period ──

test('adjustment process is blocked when period is closed', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    // Close current month
    $now = now();
    InventoryClosure::factory()->closed()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => $now->year,
        'month' => $now->month,
        'period_start_date' => $now->copy()->startOfMonth()->toDateString(),
        'period_end_date' => $now->copy()->endOfMonth()->toDateString(),
    ]);

    $adjustment = InventoryAdjustment::factory()->approved()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $product->id,
        'adjustment_type' => 'positive',
        'quantity' => 10,
    ]);

    $result = $adjustment->process($this->user->id);

    expect($result)->toBeFalse();
});
