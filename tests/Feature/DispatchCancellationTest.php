<?php

declare(strict_types=1);

use App\Exceptions\InventoryReversalException;
use App\Models\Area;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\InventoryClosure;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega General']);
    $this->unit = UnitOfMeasure::create(['company_id' => $this->company->id, 'name' => 'Unidad', 'abbreviation' => 'U', 'slug' => 'unidad', 'type' => 'quantity']);
    $this->area = Area::create(['company_id' => $this->company->id, 'name' => 'Cocina', 'slug' => 'cocina', 'is_active' => true]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'unit_of_measure_id' => $this->unit->id]);

    MovementReason::factory()->create(['code' => 'DISPATCH', 'name' => 'Despacho', 'slug' => 'despacho', 'category' => 'outbound', 'movement_type' => 'out']);
    MovementReason::factory()->create(['code' => 'REENTRY', 'name' => 'Reingreso', 'slug' => 'reingreso', 'category' => 'inbound', 'movement_type' => 'in']);

    $this->inventory = Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'available_quantity' => 100,
        'unit_cost' => 1.25,
    ]);

    $this->dispatch = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'area_id' => $this->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => '7001',
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'is_active' => true,
        'active_at' => now(),
    ]);

    $this->detail = DispatchDetail::create([
        'dispatch_id' => $this->dispatch->id,
        'product_id' => $this->product->id,
        'quantity' => 30,
        'unit_of_measure_id' => $this->unit->id,
        'unit_price' => 9.99,
    ]);
});

test('dispatching uses the average cost of the warehouse instead of the price captured on the line', function () {
    expect($this->dispatch->dispatch($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()->where('dispatch_id', $this->dispatch->id)->firstOrFail();

    expect((float) $this->detail->fresh()->unit_price)->toBe(1.25)
        ->and((float) $movement->unit_cost)->toBe(1.25)
        ->and((float) $movement->total_cost)->toBe(37.5)
        ->and((float) $movement->balance_unit_cost)->toBe(1.25)
        ->and((float) $this->inventory->fresh()->quantity)->toBe(70.0)
        ->and((float) $this->inventory->fresh()->available_quantity)->toBe(70.0)
        ->and((float) $this->dispatch->fresh()->total)->toBe(37.5);
});

test('cancelling a dispatched dispatch returns the stock to the warehouse at the cost it left with', function () {
    expect($this->dispatch->dispatch($this->user->id))->toBeTrue();

    // A later purchase moved the average; the reversal must still use the original cost
    $this->inventory->fresh()->update(['unit_cost' => 2.0]);

    expect($this->dispatch->fresh()->cancel($this->user->id, 'Despacho duplicado'))->toBeTrue();

    $dispatch = $this->dispatch->fresh();
    $inventory = $this->inventory->fresh();

    expect($dispatch->status)->toBe('cancelado')
        ->and($dispatch->cancelled_by)->toBe($this->user->id)
        ->and($dispatch->cancellation_reason)->toBe('Despacho duplicado')
        ->and($inventory->quantity)->toEqual(100)
        ->and($inventory->available_quantity)->toEqual(100)
        // 70 units at 2.00 plus 30 returning at 1.25 = 100 units at 1.775
        ->and((float) $inventory->unit_cost)->toBe(1.775);

    $reversal = InventoryMovement::query()
        ->where('dispatch_id', $dispatch->id)
        ->where('document_type', 'dispatch_cancellation')
        ->firstOrFail();

    expect($reversal->movement_type)->toBe('return')
        ->and((float) $reversal->quantity_in)->toBe(30.0)
        ->and((float) $reversal->unit_cost)->toBe(1.25);
});

test('cancelling a delivered dispatch also returns the stock', function () {
    expect($this->dispatch->dispatch($this->user->id))->toBeTrue()
        ->and($this->dispatch->fresh()->deliver($this->user->id, 'Juan Pérez'))->toBeTrue()
        ->and($this->dispatch->fresh()->cancel($this->user->id))->toBeTrue();

    expect($this->dispatch->fresh()->status)->toBe('cancelado')
        ->and($this->inventory->fresh()->quantity)->toEqual(100);
});

test('a dispatched dispatch cannot be cancelled when the period is closed', function () {
    expect($this->dispatch->dispatch($this->user->id))->toBeTrue();

    InventoryClosure::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => now()->year,
        'month' => now()->month,
        'status' => 'cerrado',
    ]);

    $dispatch = $this->dispatch->fresh();

    expect(fn () => $dispatch->cancel($this->user->id))
        ->toThrow(InventoryReversalException::class, 'cierre mensual');

    expect($this->dispatch->fresh()->status)->toBe('despachado')
        ->and((float) $this->inventory->fresh()->quantity)->toBe(70.0);
});

test('cancelling a dispatch that has not left the warehouse only changes its status', function () {
    expect($this->dispatch->cancel($this->user->id))->toBeTrue()
        ->and($this->dispatch->fresh()->status)->toBe('cancelado')
        ->and((float) $this->inventory->fresh()->quantity)->toBe(100.0)
        ->and(InventoryMovement::query()->where('dispatch_id', $this->dispatch->id)->count())->toBe(0);
});
