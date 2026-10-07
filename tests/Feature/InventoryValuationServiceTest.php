<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryValuationService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->service = app(InventoryValuationService::class);
});

function valuationMovement(array $overrides): InventoryMovement
{
    return InventoryMovement::create(array_merge([
        'company_id' => test()->company->id,
        'warehouse_id' => test()->warehouse->id,
        'product_id' => test()->product->id,
        'movement_reason_id' => MovementReason::factory()->create(['code' => 'TEST_IN_'.uniqid(), 'name' => 'Entrada '.uniqid(), 'slug' => 'entrada-'.uniqid(), 'movement_type' => 'in', 'category' => 'inbound'])->id,
        'movement_type' => 'purchase',
        'movement_date' => now(),
        'quantity' => 0,
        'quantity_in' => 0,
        'quantity_out' => 0,
        'is_active' => true,
        'active_at' => now(),
        'created_by' => test()->user->id,
    ], $overrides));
}

test('weighted average mixes the existing value with the incoming value', function () {
    expect(round($this->service->weightedAverage(50, 1.5, 100, 1.0), 4))->toBe(1.1667)
        ->and($this->service->weightedAverage(0, 0, 10, 2.5))->toBe(2.5)
        ->and($this->service->weightedAverage(10, 2.5, 0, 9.0))->toBe(2.5);
});

test('the first inbound movement creates the inventory row with the incoming cost', function () {
    $movement = valuationMovement(['quantity' => 10, 'quantity_in' => 10, 'unit_cost' => 2.0, 'total_cost' => 20]);

    $inventory = $this->service->applyMovement($movement);

    expect((float) $inventory->quantity)->toBe(10.0)
        ->and((float) $inventory->unit_cost)->toBe(2.0)
        ->and((float) $inventory->available_quantity)->toBe(10.0)
        ->and((float) $movement->fresh()->balance_unit_cost)->toBe(2.0)
        ->and((float) $movement->fresh()->balance_total_cost)->toBe(20.0);
});

test('an inbound movement recalculates the weighted average instead of overwriting the cost', function () {
    Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'available_quantity' => 100,
        'unit_cost' => 1.0,
    ]);

    $movement = valuationMovement(['quantity' => 100, 'quantity_in' => 100, 'unit_cost' => 1.5, 'total_cost' => 150]);

    $inventory = $this->service->applyMovement($movement);

    expect((float) $inventory->quantity)->toBe(200.0)
        ->and((float) $inventory->unit_cost)->toBe(1.25)
        ->and((float) $inventory->total_value)->toBe(250.0)
        ->and((float) $movement->fresh()->balance_unit_cost)->toBe(1.25);
});

test('an outbound movement reduces stock and keeps the average cost', function () {
    Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 200,
        'reserved_quantity' => 0,
        'available_quantity' => 200,
        'unit_cost' => 1.25,
    ]);

    $movement = valuationMovement([
        'movement_type' => 'transfer_out',
        'quantity' => -50,
        'quantity_in' => 0,
        'quantity_out' => 50,
        'unit_cost' => 1.25,
        'total_cost' => 62.5,
    ]);

    $inventory = $this->service->applyMovement($movement);

    expect((float) $inventory->quantity)->toBe(150.0)
        ->and((float) $inventory->available_quantity)->toBe(150.0)
        ->and((float) $inventory->unit_cost)->toBe(1.25)
        ->and((float) $movement->fresh()->balance_unit_cost)->toBe(1.25)
        ->and((float) $movement->fresh()->balance_total_cost)->toBe(187.5);
});

test('receiving two purchases at different costs leaves the inventory at the weighted average', function () {
    MovementReason::factory()->create(['code' => 'PURCH_LOCAL', 'name' => 'Compra Local', 'slug' => 'compra-local', 'movement_type' => 'in', 'category' => 'inbound']);
    $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);

    $receivePurchase = function (float $quantity, float $unitCost) use ($supplier): Purchase {
        $purchase = Purchase::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $supplier->id,
            'document_type' => 'factura',
            'document_number' => 'FAC-'.fake()->unique()->numerify('####'),
            'document_date' => now()->toDateString(),
            'purchase_type' => 'efectivo',
            'status' => 'aprobado',
        ]);

        PurchaseDetail::create([
            'purchase_id' => $purchase->id,
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
        ]);

        expect($purchase->fresh()->receive($this->user->id))->toBeTrue();

        return $purchase;
    };

    $receivePurchase(100, 1.00);
    $receivePurchase(100, 1.50);

    $inventory = Inventory::query()
        ->where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->firstOrFail();

    expect((float) $inventory->quantity)->toBe(200.0)
        ->and((float) $inventory->unit_cost)->toBe(1.25)
        ->and((float) $inventory->total_value)->toBe(250.0);

    $lastMovement = InventoryMovement::query()->where('product_id', $this->product->id)->orderByDesc('id')->firstOrFail();

    expect((float) $lastMovement->unit_cost)->toBe(1.5)
        ->and((float) $lastMovement->balance_unit_cost)->toBe(1.25);
});
