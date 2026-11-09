<?php

declare(strict_types=1);

use App\Exceptions\InventoryReversalException;
use App\Models\Area;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\DocumentCorrection;
use App\Models\InternalProduction;
use App\Models\InternalProductionDetail;
use App\Models\Inventory;
use App\Models\InventoryClosure;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DocumentCorrectionService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->central = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega Central']);
    $this->agroindustria = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Agroindustria']);
    $this->zootecnia = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Zootecnia']);

    $this->unit = UnitOfMeasure::create(['company_id' => $this->company->id, 'name' => 'Unidad', 'abbreviation' => 'U', 'slug' => 'unidad', 'type' => 'quantity']);
    $this->area = Area::create(['company_id' => $this->company->id, 'name' => 'Cocina', 'slug' => 'cocina', 'is_active' => true]);
    $this->maiz = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Maiz', 'unit_of_measure_id' => $this->unit->id]);
    $this->frijol = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Frijol', 'unit_of_measure_id' => $this->unit->id]);

    foreach ([
        ['TRANSFER_OUT', 'transfer', 'out'], ['TRANSFER_IN', 'transfer', 'in'], ['DISPATCH', 'outbound', 'out'],
        ['REENTRY', 'inbound', 'in'], ['PROD_IN', 'inbound', 'in'], ['ADJ_NEG', 'adjustment', 'out'],
    ] as [$code, $category, $type]) {
        MovementReason::factory()->create(['code' => $code, 'name' => $code, 'slug' => strtolower($code), 'category' => $category, 'movement_type' => $type]);
    }

    $this->service = app(DocumentCorrectionService::class);
});

function stockOf(Product $product, Warehouse $warehouse): float
{
    return (float) (Inventory::query()->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity') ?? 0);
}

function seedStock(Product $product, Warehouse $warehouse, float $quantity, float $cost): void
{
    Inventory::query()->updateOrCreate(
        ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
        ['quantity' => $quantity, 'reserved_quantity' => 0, 'available_quantity' => $quantity, 'unit_cost' => $cost, 'is_active' => true],
    );
}

function receivedTransfer(Warehouse $from, Warehouse $to, array $lines, string $status = 'received'): InventoryTransfer
{
    $transfer = InventoryTransfer::create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'physical_document_number' => (string) fake()->unique()->numberBetween(1000, 99999),
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    foreach ($lines as [$product, $quantity]) {
        InventoryTransferDetail::create(['transfer_id' => $transfer->id, 'product_id' => $product->id, 'quantity' => $quantity]);
    }

    expect($transfer->fresh()->ship(test()->user->id))->toBeTrue();

    if ($status === 'received') {
        expect($transfer->fresh()->receive(test()->user->id))->toBeTrue();
    }

    return $transfer->fresh();
}

function deliveredDispatch(Warehouse $warehouse, array $lines, ?string $notes = null): Dispatch
{
    $dispatch = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => test()->company->id,
        'warehouse_id' => $warehouse->id,
        'area_id' => test()->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => (string) fake()->unique()->numberBetween(1000, 99999),
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'notes' => $notes,
        'is_active' => true,
        'active_at' => now(),
    ]);

    foreach ($lines as [$product, $quantity]) {
        DispatchDetail::create(['dispatch_id' => $dispatch->id, 'product_id' => $product->id, 'quantity' => $quantity, 'unit_of_measure_id' => test()->unit->id, 'unit_price' => 0]);
    }

    expect($dispatch->fresh()->dispatch(test()->user->id))->toBeTrue()
        ->and($dispatch->fresh()->deliver(test()->user->id, 'Encargado'))->toBeTrue();

    return $dispatch->fresh();
}

test('changing the destination of a received transfer moves the goods without taking them again from the origin', function () {
    seedStock($this->maiz, $this->central, 50, 2.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 50]]);

    // The origin is now empty: the old edit form failed here asking for stock again
    expect(stockOf($this->maiz, $this->central))->toBe(0.0);

    $correction = $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->zootecnia->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 50]],
    ], 'El traslado era para zootecnia', $this->user->id);

    expect(stockOf($this->maiz, $this->central))->toBe(0.0)
        ->and(stockOf($this->maiz, $this->agroindustria))->toBe(0.0)
        ->and(stockOf($this->maiz, $this->zootecnia))->toBe(50.0)
        ->and((float) Inventory::query()->where('product_id', $this->maiz->id)->where('warehouse_id', $this->zootecnia->id)->value('unit_cost'))->toBe(2.0)
        ->and($transfer->fresh()->to_warehouse_id)->toBe($this->zootecnia->id)
        ->and($correction->reason)->toBe('El traslado era para zootecnia')
        ->and($correction->before['to_warehouse'])->toBe('Agroindustria')
        ->and($correction->after['to_warehouse'])->toBe('Zootecnia')
        ->and($correction->movement_ids)->toHaveCount(2)
        ->and($correction->created_by)->toBe($this->user->id);

    $movement = InventoryMovement::query()->whereKey($correction->movement_ids)->where('warehouse_id', $this->zootecnia->id)->firstOrFail();
    expect($movement->document_type)->toBe('transfer_correction')
        ->and($movement->movement_date->toDateString())->toBe($transfer->document_date->toDateString())
        ->and($movement->metadata['correction'])->toBeTrue();
});

test('reducing the quantity of a received transfer returns the difference to the origin', function () {
    seedStock($this->maiz, $this->central, 100, 2.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 60]]);

    $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->agroindustria->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 45]],
    ], 'Se enviaron 45, no 60', $this->user->id);

    expect(stockOf($this->maiz, $this->central))->toBe(55.0)
        ->and(stockOf($this->maiz, $this->agroindustria))->toBe(45.0)
        ->and((float) $transfer->fresh()->details->first()->quantity)->toBe(45.0);
});

test('replacing the product of a transfer swaps the stock in both warehouses', function () {
    seedStock($this->maiz, $this->central, 30, 2.0);
    seedStock($this->frijol, $this->central, 30, 3.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 10]]);

    $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->agroindustria->id,
        'lines' => [['product_id' => $this->frijol->id, 'quantity' => 10]],
    ], 'Era frijol', $this->user->id);

    expect(stockOf($this->maiz, $this->central))->toBe(30.0)
        ->and(stockOf($this->maiz, $this->agroindustria))->toBe(0.0)
        ->and(stockOf($this->frijol, $this->central))->toBe(20.0)
        ->and(stockOf($this->frijol, $this->agroindustria))->toBe(10.0)
        ->and((float) $transfer->fresh()->details->first()->unit_cost)->toBe(3.0);
});

test('a transfer in transit only corrects the origin', function () {
    seedStock($this->maiz, $this->central, 100, 2.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 40]], 'in_transit');

    $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->zootecnia->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 30]],
    ], 'Ajuste antes de recibir', $this->user->id);

    expect(stockOf($this->maiz, $this->central))->toBe(70.0)
        ->and(stockOf($this->maiz, $this->zootecnia))->toBe(0.0);

    expect($transfer->fresh()->receive($this->user->id))->toBeTrue()
        ->and(stockOf($this->maiz, $this->zootecnia))->toBe(30.0);
});

test('a correction is rejected and nothing changes when the wrong destination already used the goods', function () {
    seedStock($this->maiz, $this->central, 50, 2.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 50]]);
    Inventory::query()->where('product_id', $this->maiz->id)->where('warehouse_id', $this->agroindustria->id)->update(['quantity' => 20, 'available_quantity' => 20]);

    expect(fn () => $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->zootecnia->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 50]],
    ], 'Destino equivocado', $this->user->id))->toThrow(InventoryReversalException::class, 'Agroindustria');

    expect(stockOf($this->maiz, $this->zootecnia))->toBe(0.0)
        ->and($transfer->fresh()->to_warehouse_id)->toBe($this->agroindustria->id)
        ->and(DocumentCorrection::query()->count())->toBe(0);
});

test('a document of a closed month cannot be corrected', function () {
    seedStock($this->maiz, $this->central, 50, 2.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 50]]);

    InventoryClosure::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->agroindustria->id,
        'year' => now()->year,
        'month' => now()->month,
        'status' => 'cerrado',
    ]);

    expect(fn () => $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->zootecnia->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 50]],
    ], 'Destino equivocado', $this->user->id))->toThrow(InventoryReversalException::class, 'cierre mensual');

    expect(stockOf($this->maiz, $this->agroindustria))->toBe(50.0);
});

test('cancelling a corrected transfer returns every warehouse to its original stock', function () {
    seedStock($this->maiz, $this->central, 100, 2.0);
    $transfer = receivedTransfer($this->central, $this->agroindustria, [[$this->maiz, 60]]);

    $this->service->correctTransfer($transfer, [
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->zootecnia->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 40]],
    ], 'Destino y cantidad equivocados', $this->user->id);

    expect($transfer->fresh()->cancel($this->user->id))->toBeTrue();

    expect(stockOf($this->maiz, $this->central))->toBe(100.0)
        ->and(stockOf($this->maiz, $this->agroindustria))->toBe(0.0)
        ->and(stockOf($this->maiz, $this->zootecnia))->toBe(0.0);
});

test('changing the warehouse of a delivered dispatch restores the old one and takes the stock from the new one', function () {
    seedStock($this->maiz, $this->central, 20, 2.0);
    seedStock($this->maiz, $this->agroindustria, 20, 2.5);
    $dispatch = deliveredDispatch($this->central, [[$this->maiz, 8]]);

    $this->service->correctDispatch($dispatch, [
        'warehouse_id' => $this->agroindustria->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 8]],
    ], 'Salió de agroindustria', $this->user->id);

    $dispatch->refresh();

    expect(stockOf($this->maiz, $this->central))->toBe(20.0)
        ->and(stockOf($this->maiz, $this->agroindustria))->toBe(12.0)
        ->and($dispatch->warehouse_id)->toBe($this->agroindustria->id)
        ->and($dispatch->status)->toBe('entregado')
        ->and((float) $dispatch->details->first()->unit_price)->toBe(2.5)
        ->and((float) $dispatch->details->first()->quantity_delivered)->toBe(8.0)
        ->and((float) $dispatch->total)->toBe(20.0);
});

test('increasing the quantity of a delivered dispatch only takes the difference from stock', function () {
    seedStock($this->maiz, $this->central, 20, 2.0);
    $dispatch = deliveredDispatch($this->central, [[$this->maiz, 8]]);

    $this->service->correctDispatch($dispatch, [
        'warehouse_id' => $this->central->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 10]],
    ], 'Eran 10', $this->user->id);

    expect(stockOf($this->maiz, $this->central))->toBe(10.0);

    // Cancelling afterwards returns the whole quantity once
    expect($dispatch->fresh()->cancel($this->user->id))->toBeTrue()
        ->and(stockOf($this->maiz, $this->central))->toBe(20.0);
});

test('a document must keep at least one product', function () {
    seedStock($this->maiz, $this->central, 20, 2.0);
    $dispatch = deliveredDispatch($this->central, [[$this->maiz, 8]]);

    expect(fn () => $this->service->correctDispatch($dispatch, [
        'warehouse_id' => $this->central->id,
        'lines' => [],
    ], 'Sin productos', $this->user->id))->toThrow(InventoryReversalException::class, 'al menos un producto');
});

test('correcting a production also corrects its automatic dispatch together', function () {
    $production = InternalProduction::create([
        'production_number' => 'PI-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'area_id' => $this->area->id,
        'warehouse_id' => $this->central->id,
        'physical_document_number' => '9001',
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'is_active' => true,
    ]);
    InternalProductionDetail::create([
        'internal_production_id' => $production->id,
        'product_id' => $this->maiz->id,
        'quantity' => 30,
        'unit_of_measure_id' => $this->unit->id,
        'unit_price' => 1.5,
    ]);

    expect($production->fresh()->complete($this->user->id))->toBeTrue()
        ->and(stockOf($this->maiz, $this->central))->toBe(30.0);

    $dispatch = deliveredDispatch($this->central, [[$this->maiz, 30]], "Despacho automático desde Producción Interna {$production->production_number}");

    expect(stockOf($this->maiz, $this->central))->toBe(0.0);

    // It was produced in agroindustria and it was 25, not 30
    $correction = $this->service->correctProduction($production->fresh(), [
        'warehouse_id' => $this->agroindustria->id,
        'lines' => [['product_id' => $this->maiz->id, 'quantity' => 25, 'unit_price' => 1.5]],
    ], 'Se produjo en agroindustria', $this->user->id);

    $dispatch->refresh();

    expect(stockOf($this->maiz, $this->central))->toBe(0.0)
        ->and(stockOf($this->maiz, $this->agroindustria))->toBe(0.0)
        ->and($production->fresh()->warehouse_id)->toBe($this->agroindustria->id)
        ->and((float) $production->fresh()->details->first()->quantity)->toBe(25.0)
        ->and($dispatch->warehouse_id)->toBe($this->agroindustria->id)
        ->and((float) $dispatch->details->first()->quantity)->toBe(25.0)
        ->and($correction->after['auto_dispatches'])->toHaveCount(1);
});
