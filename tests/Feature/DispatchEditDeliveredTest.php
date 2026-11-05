<?php

declare(strict_types=1);

use App\Models\Area;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\InventoryClosure;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->unit = UnitOfMeasure::create(['company_id' => $this->company->id, 'name' => 'Unidad', 'abbreviation' => 'U', 'slug' => 'unidad', 'type' => 'quantity']);
    $this->area = Area::create(['company_id' => $this->company->id, 'name' => 'Cocina', 'slug' => 'cocina', 'is_active' => true]);
    $this->otherArea = Area::create(['company_id' => $this->company->id, 'name' => 'Mantenimiento', 'slug' => 'mantenimiento', 'is_active' => true]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Frijol', 'unit_of_measure_id' => $this->unit->id]);
    $this->otherProduct = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Aceite', 'unit_of_measure_id' => $this->unit->id]);

    MovementReason::factory()->create(['code' => 'DISPATCH', 'name' => 'Despacho', 'slug' => 'despacho', 'category' => 'outbound', 'movement_type' => 'out']);

    Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'available_quantity' => 10,
        'unit_cost' => 1.0,
    ]);

    $this->otherInventory = Inventory::factory()->create([
        'product_id' => $this->otherProduct->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 3,
        'reserved_quantity' => 0,
        'available_quantity' => 3,
        'unit_cost' => 4.0,
    ]);

    $this->dispatch = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'area_id' => $this->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => '8001',
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'is_active' => true,
        'active_at' => now(),
    ]);

    $this->detail = DispatchDetail::create([
        'dispatch_id' => $this->dispatch->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_of_measure_id' => $this->unit->id,
        'unit_price' => 1.0,
    ]);

    // The whole stock of the product left the warehouse with this dispatch
    expect($this->dispatch->dispatch($this->user->id))->toBeTrue()
        ->and($this->dispatch->fresh()->deliver($this->user->id, 'Ana López'))->toBeTrue();
});

function existingLine(): array
{
    return [
        'id' => test()->detail->id,
        'product_id' => (string) test()->product->id,
        'quantity' => 10,
        'unit_of_measure_id' => (string) test()->unit->id,
        'unit_price' => 1.0,
        'notes' => null,
    ];
}

test('changing the requesting unit of a delivered dispatch does not ask for the stock that already left', function () {
    Volt::test('dispatches.edit', ['dispatch' => $this->dispatch->fresh()])
        ->set('details', [existingLine()])
        ->set('area_id', $this->otherArea->id)
        ->call('save')
        ->assertHasNoErrors();

    $dispatch = $this->dispatch->fresh();

    expect($dispatch->area_id)->toBe($this->otherArea->id)
        ->and($dispatch->status)->toBe('entregado')
        ->and((float) $dispatch->details->first()->unit_price)->toBe(1.0)
        ->and((float) Inventory::query()->where('product_id', $this->product->id)->value('quantity'))->toBe(0.0);
});

test('adding a line to a delivered dispatch validates only the new quantity against stock', function () {
    Volt::test('dispatches.edit', ['dispatch' => $this->dispatch->fresh()])
        ->set('details', [
            existingLine(),
            ['product_id' => (string) $this->otherProduct->id, 'quantity' => 5, 'unit_of_measure_id' => (string) $this->unit->id, 'unit_price' => 0, 'notes' => null],
        ])
        ->call('save')
        ->assertHasErrors(['details.1.quantity']);

    expect($this->dispatch->fresh()->details)->toHaveCount(1)
        ->and((float) $this->otherInventory->fresh()->quantity)->toBe(3.0);
});

test('a new line added to a delivered dispatch leaves the warehouse at the average cost', function () {
    Volt::test('dispatches.edit', ['dispatch' => $this->dispatch->fresh()])
        ->set('details', [
            existingLine(),
            ['product_id' => (string) $this->otherProduct->id, 'quantity' => 2, 'unit_of_measure_id' => (string) $this->unit->id, 'unit_price' => 0, 'notes' => null],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $newDetail = $this->dispatch->fresh()->details->where('product_id', $this->otherProduct->id)->first();

    expect($newDetail)->not->toBeNull()
        ->and((float) $newDetail->unit_price)->toBe(4.0)
        ->and((float) $newDetail->quantity_delivered)->toBe(2.0)
        ->and((float) $this->otherInventory->fresh()->quantity)->toBe(1.0);
});

test('a delivered line keeps its product and quantity even if other values are sent', function () {
    Volt::test('dispatches.edit', ['dispatch' => $this->dispatch->fresh()])
        ->assertSee('Ya despachado')
        ->set('details', [array_merge(existingLine(), [
            'product_id' => (string) $this->otherProduct->id,
            'quantity' => 2,
            'unit_price' => 99,
            'notes' => 'Entregado a cocina',
        ])])
        ->call('save')
        ->assertHasNoErrors();

    $detail = $this->detail->fresh();

    expect($detail->product_id)->toBe($this->product->id)
        ->and((float) $detail->quantity)->toBe(10.0)
        ->and((float) $detail->unit_price)->toBe(1.0)
        ->and($detail->notes)->toBe('Entregado a cocina')
        ->and((float) $this->otherInventory->fresh()->quantity)->toBe(3.0);
});

test('a delivered dispatch keeps its warehouse, type, date and status even if other values are sent', function () {
    $otherWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $original = $this->dispatch->fresh();

    Volt::test('dispatches.edit', ['dispatch' => $original])
        ->set('details', [existingLine()])
        ->set('warehouse_id', $otherWarehouse->id)
        ->set('dispatch_type', 'venta')
        ->set('document_date', now()->subDays(3)->toDateString())
        ->set('status', 'borrador')
        ->set('area_id', $this->otherArea->id)
        ->call('save')
        ->assertHasNoErrors();

    $dispatch = $this->dispatch->fresh();

    expect($dispatch->warehouse_id)->toBe($this->warehouse->id)
        ->and($dispatch->dispatch_type)->toBe('interno')
        ->and($dispatch->document_date->toDateString())->toBe($original->document_date->toDateString())
        ->and($dispatch->status)->toBe('entregado')
        ->and($dispatch->area_id)->toBe($this->otherArea->id);
});

test('a line of another dispatch cannot be changed through this form', function () {
    $otherDispatch = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'area_id' => $this->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => '8002',
        'document_date' => now()->toDateString(),
        'status' => 'pendiente',
        'is_active' => true,
        'active_at' => now(),
    ]);
    $otherDetail = DispatchDetail::create([
        'dispatch_id' => $otherDispatch->id,
        'product_id' => $this->otherProduct->id,
        'quantity' => 1,
        'unit_of_measure_id' => $this->unit->id,
        'unit_price' => 4.0,
        'notes' => 'Original',
    ]);

    Volt::test('dispatches.edit', ['dispatch' => $this->dispatch->fresh()])
        ->set('details', [existingLine(), array_merge(existingLine(), ['id' => $otherDetail->id, 'notes' => 'Alterada'])])
        ->call('save')
        ->assertHasNoErrors();

    expect($otherDetail->fresh()->notes)->toBe('Original');
});

test('a new line cannot be added to a delivered dispatch of a closed month', function () {
    $dispatchedAt = $this->dispatch->fresh()->dispatched_at;

    InventoryClosure::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'year' => $dispatchedAt->year,
        'month' => $dispatchedAt->month,
        'status' => 'cerrado',
    ]);

    Volt::test('dispatches.edit', ['dispatch' => $this->dispatch->fresh()])
        ->set('details', [
            existingLine(),
            ['product_id' => (string) $this->otherProduct->id, 'quantity' => 1, 'unit_of_measure_id' => (string) $this->unit->id, 'unit_price' => 0, 'notes' => null],
        ])
        ->call('save')
        ->assertHasErrors('stock');

    expect($this->dispatch->fresh()->details)->toHaveCount(1)
        ->and((float) $this->otherInventory->fresh()->quantity)->toBe(3.0);
});

test('a pending dispatch can still change its quantity', function () {
    $pending = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'area_id' => $this->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => '8003',
        'document_date' => now()->toDateString(),
        'status' => 'pendiente',
        'is_active' => true,
        'active_at' => now(),
    ]);
    $detail = DispatchDetail::create([
        'dispatch_id' => $pending->id,
        'product_id' => $this->otherProduct->id,
        'quantity' => 1,
        'unit_of_measure_id' => $this->unit->id,
        'unit_price' => 4.0,
    ]);

    Volt::test('dispatches.edit', ['dispatch' => $pending->fresh()])
        ->assertDontSee('Ya despachado')
        ->set('details.0.quantity', 2)
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $detail->fresh()->quantity)->toBe(2.0);
});
