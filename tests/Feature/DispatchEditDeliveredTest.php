<?php

declare(strict_types=1);

use App\Models\Area;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
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
