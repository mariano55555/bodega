<?php

declare(strict_types=1);

use App\Models\Area;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->fromWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->toWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->unit = UnitOfMeasure::create(['company_id' => $this->company->id, 'name' => 'Unidad', 'abbreviation' => 'U', 'slug' => 'unidad', 'type' => 'quantity']);
    $this->area = Area::create(['company_id' => $this->company->id, 'name' => 'Cocina', 'slug' => 'cocina', 'is_active' => true]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'unit_of_measure_id' => $this->unit->id]);

    MovementReason::factory()->create(['code' => 'TRANSFER_OUT', 'name' => 'Salida por Traslado', 'slug' => 'salida-por-traslado', 'category' => 'transfer', 'movement_type' => 'out']);
    MovementReason::factory()->create(['code' => 'DISPATCH', 'name' => 'Despacho', 'slug' => 'despacho', 'category' => 'outbound', 'movement_type' => 'out']);

    $this->inventory = Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->fromWarehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'available_quantity' => 100,
        'unit_cost' => 1.19999,
    ]);
});

function enableManualCost(?string $until = null): void
{
    $settings = test()->company->settings ?? [];
    $settings['manual_unit_cost_enabled'] = true;

    if ($until === null) {
        unset($settings['manual_unit_cost_until']);
    } else {
        $settings['manual_unit_cost_until'] = $until;
    }

    test()->company->update(['settings' => $settings]);
}

function manualCostTransfer(float $unitCost): InventoryTransfer
{
    $transfer = InventoryTransfer::create([
        'from_warehouse_id' => test()->fromWarehouse->id,
        'to_warehouse_id' => test()->toWarehouse->id,
        'physical_document_number' => (string) fake()->unique()->numberBetween(1000, 9999),
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $transfer->id,
        'product_id' => test()->product->id,
        'quantity' => 10,
        'unit_cost' => $unitCost,
    ]);

    return $transfer;
}

function manualCostDispatch(float $unitPrice): Dispatch
{
    $dispatch = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => test()->company->id,
        'warehouse_id' => test()->fromWarehouse->id,
        'area_id' => test()->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => (string) fake()->unique()->numberBetween(1000, 9999),
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'is_active' => true,
        'active_at' => now(),
    ]);

    DispatchDetail::create([
        'dispatch_id' => $dispatch->id,
        'product_id' => test()->product->id,
        'quantity' => 10,
        'unit_of_measure_id' => test()->unit->id,
        'unit_price' => $unitPrice,
    ]);

    return $dispatch;
}

test('the manual unit cost is disabled by default', function () {
    expect($this->company->fresh()->allowsManualUnitCost())->toBeFalse();
});

test('the manual unit cost is allowed until the end date inclusive and expires the day after', function () {
    enableManualCost(now()->toDateString());
    expect($this->company->fresh()->allowsManualUnitCost())->toBeTrue();

    enableManualCost(now()->subDay()->toDateString());
    expect($this->company->fresh()->allowsManualUnitCost())->toBeFalse();

    enableManualCost();
    expect($this->company->fresh()->allowsManualUnitCost())->toBeTrue();
});

test('a super admin enables the manual unit cost for one company and it defaults to the end of the month', function () {
    Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['slug' => 'super-admin']);
    $superAdmin = User::factory()->create(['company_id' => null]);
    $superAdmin->assignRole('super-admin');
    $otherCompany = Company::factory()->create();

    $this->actingAs($superAdmin);

    Volt::test('settings.general')
        ->call('updateManualUnitCost', $this->company->id, true)
        ->assertSet("company_manual_unit_cost_until.{$this->company->id}", now()->endOfMonth()->toDateString());

    expect($this->company->fresh()->allowsManualUnitCost())->toBeTrue()
        ->and($this->company->fresh()->settings['manual_unit_cost_until'])->toBe(now()->endOfMonth()->toDateString())
        ->and($otherCompany->fresh()->allowsManualUnitCost())->toBeFalse();
});

test('a user who is not super admin cannot enable the manual unit cost', function () {
    Role::query()->firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web'], ['slug' => 'company-admin']);
    $this->user->assignRole('company-admin');

    Volt::test('settings.general')
        ->call('updateManualUnitCost', $this->company->id, true)
        ->assertForbidden();

    expect($this->company->fresh()->allowsManualUnitCost())->toBeFalse();
});

test('creating a transfer keeps the typed cost when the company allows it', function () {
    enableManualCost(now()->endOfMonth()->toDateString());

    Volt::test('inventory.transfers.create')
        ->set('from_warehouse_id', $this->fromWarehouse->id)
        ->set('to_warehouse_id', $this->toWarehouse->id)
        ->set('physical_document_number', '6001')
        ->set('products', [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 1.14, 'notes' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect((float) InventoryTransferDetail::query()->where('product_id', $this->product->id)->value('unit_cost'))->toBe(1.14);
});

test('creating a transfer requires a valid cost when the company allows the manual cost', function () {
    enableManualCost();

    Volt::test('inventory.transfers.create')
        ->set('from_warehouse_id', $this->fromWarehouse->id)
        ->set('to_warehouse_id', $this->toWarehouse->id)
        ->set('physical_document_number', '6002')
        ->set('products', [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => -1, 'notes' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['products.0.unit_cost' => 'min']);
});

test('creating a transfer ignores the typed cost when the permission expired', function () {
    enableManualCost(now()->subDay()->toDateString());

    Volt::test('inventory.transfers.create')
        ->set('from_warehouse_id', $this->fromWarehouse->id)
        ->set('to_warehouse_id', $this->toWarehouse->id)
        ->set('physical_document_number', '6003')
        ->set('products', [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 1.14, 'notes' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect((float) InventoryTransferDetail::query()->where('product_id', $this->product->id)->value('unit_cost'))->toBe(1.19999);
});

test('shipping a transfer uses the typed cost and marks the movement as manual while the company allows it', function () {
    enableManualCost();
    $transfer = manualCostTransfer(1.14);

    expect($transfer->ship($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()->where('transfer_id', $transfer->id)->where('movement_type', 'transfer_out')->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.14)
        ->and((float) $movement->total_cost)->toBe(11.4)
        ->and($movement->metadata['manual_unit_cost'])->toBeTrue()
        ->and((float) $movement->metadata['average_unit_cost'])->toBe(1.19999)
        ->and($movement->metadata['manual_cost_user_id'])->toBe($this->user->id)
        // An outbound movement never changes the average of the origin warehouse
        ->and((float) $this->inventory->fresh()->unit_cost)->toBe(1.19999)
        ->and((float) $this->inventory->fresh()->quantity)->toBe(90.0);
});

test('shipping a transfer falls back to the average when the company does not allow the manual cost', function () {
    $transfer = manualCostTransfer(1.14);

    expect($transfer->ship($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()->where('transfer_id', $transfer->id)->where('movement_type', 'transfer_out')->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.19999)
        ->and($movement->metadata)->toBeNull()
        ->and((float) $transfer->fresh()->details->first()->unit_cost)->toBe(1.19999);
});

test('dispatching uses the typed price while the company allows the manual cost', function () {
    enableManualCost();
    $dispatch = manualCostDispatch(1.14);

    expect($dispatch->dispatch($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()->where('dispatch_id', $dispatch->id)->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.14)
        ->and($movement->metadata['manual_unit_cost'])->toBeTrue()
        ->and((float) $dispatch->fresh()->details->first()->unit_price)->toBe(1.14)
        ->and((float) $dispatch->fresh()->total)->toBe(11.4);
});

test('dispatching ignores the typed price when the company does not allow the manual cost', function () {
    $dispatch = manualCostDispatch(1.14);

    expect($dispatch->dispatch($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()->where('dispatch_id', $dispatch->id)->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.19999)
        ->and($movement->metadata)->toBeNull();
});

test('editing a pending dispatch keeps the typed price while the company allows the manual cost', function () {
    enableManualCost();
    $dispatch = manualCostDispatch(1.14);
    $dispatch->update(['status' => 'pendiente']);
    $detail = $dispatch->details->first();

    Volt::test('dispatches.edit', ['dispatch' => $dispatch->fresh()])
        ->assertSet('details.0.unit_price', '1.14000')
        ->set('details.0.unit_price', 1.10)
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $detail->fresh()->unit_price)->toBe(1.1);
});
