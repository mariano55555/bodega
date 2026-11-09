<?php

declare(strict_types=1);

use App\Models\Area;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\DocumentCorrection;
use App\Models\InternalProduction;
use App\Models\InternalProductionDetail;
use App\Models\Inventory;
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

    $editorRole = Role::query()->firstOrCreate(['name' => 'Editor Manager', 'guard_name' => 'web'], ['slug' => 'editor-manager']);
    $editorRole->givePermissionTo('documents.correct');

    $this->editor = User::factory()->forCompany($this->company)->create();
    $this->editor->assignRole($editorRole);

    $this->regularUser = User::factory()->forCompany($this->company)->create();

    $this->central = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega Central']);
    $this->agroindustria = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Agroindustria']);
    $this->zootecnia = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Zootecnia']);

    $this->unit = UnitOfMeasure::create(['company_id' => $this->company->id, 'name' => 'Unidad', 'abbreviation' => 'U', 'slug' => 'unidad', 'type' => 'quantity']);
    $this->area = Area::create(['company_id' => $this->company->id, 'name' => 'Cocina', 'slug' => 'cocina', 'is_active' => true]);
    $this->maiz = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Maiz', 'unit_of_measure_id' => $this->unit->id]);

    foreach ([
        ['TRANSFER_OUT', 'transfer', 'out'], ['TRANSFER_IN', 'transfer', 'in'], ['DISPATCH', 'outbound', 'out'],
        ['REENTRY', 'inbound', 'in'], ['PROD_IN', 'inbound', 'in'], ['ADJ_NEG', 'adjustment', 'out'],
    ] as [$code, $category, $type]) {
        MovementReason::factory()->create(['code' => $code, 'name' => $code, 'slug' => strtolower($code), 'category' => $category, 'movement_type' => $type]);
    }

    Inventory::query()->create(['product_id' => $this->maiz->id, 'warehouse_id' => $this->central->id, 'quantity' => 50, 'reserved_quantity' => 0, 'unit_cost' => 2.0, 'is_active' => true]);

    $this->actingAs($this->editor);

    $this->transfer = InventoryTransfer::create([
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->agroindustria->id,
        'physical_document_number' => '7701',
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);
    InventoryTransferDetail::create(['transfer_id' => $this->transfer->id, 'product_id' => $this->maiz->id, 'quantity' => 50]);
    $this->transfer->fresh()->ship($this->editor->id);
    $this->transfer->fresh()->receive($this->editor->id);
    $this->transfer->refresh();
});

function warehouseStock(Product $product, Warehouse $warehouse): float
{
    return (float) (Inventory::query()->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity') ?? 0);
}

test('a user with the Editor Manager role sees the correct button and screen without being super admin', function () {
    expect($this->editor->isSuperAdmin())->toBeFalse();

    $this->get(route('transfers.show', $this->transfer))->assertOk()->assertSee(route('transfers.correct', $this->transfer));
    $this->get(route('transfers.correct', $this->transfer))->assertOk()->assertSee('Corregir traslado');
});

test('a user without the permission cannot open the correction screen nor sees the button', function () {
    $this->actingAs($this->regularUser);

    $this->get(route('transfers.correct', $this->transfer))->assertForbidden();
    $this->get(route('transfers.show', $this->transfer))->assertDontSee(route('transfers.correct', $this->transfer));
});

test('an editor of another company cannot correct the document', function () {
    $otherEditor = User::factory()->forCompany(Company::factory()->create())->create();
    $otherEditor->assignRole('Editor Manager');
    $this->actingAs($otherEditor);

    Volt::test('inventory.transfers.correct', ['transfer' => $this->transfer])->assertForbidden();
});

test('the transfer correction screen changes the destination without taking stock again', function () {
    Volt::test('inventory.transfers.correct', ['transfer' => $this->transfer])
        ->assertSet('to_warehouse_id', $this->agroindustria->id)
        ->set('to_warehouse_id', $this->zootecnia->id)
        ->set('reason', 'El traslado era para zootecnia')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('transfers.show', $this->transfer));

    expect(warehouseStock($this->maiz, $this->central))->toBe(0.0)
        ->and(warehouseStock($this->maiz, $this->agroindustria))->toBe(0.0)
        ->and(warehouseStock($this->maiz, $this->zootecnia))->toBe(50.0)
        ->and(DocumentCorrection::query()->first()->created_by)->toBe($this->editor->id);

    $this->get(route('transfers.show', $this->transfer))->assertSee('Correcciones aplicadas')->assertSee('El traslado era para zootecnia');
});

test('the correction screen requires a reason and a different destination', function () {
    Volt::test('inventory.transfers.correct', ['transfer' => $this->transfer])
        ->set('to_warehouse_id', $this->central->id)
        ->set('reason', 'corto')
        ->call('save')
        ->assertHasErrors(['reason' => 'min', 'to_warehouse_id' => 'different']);

    expect(DocumentCorrection::query()->count())->toBe(0);
});

test('the correction screen shows the reason when the stock cannot be moved', function () {
    Inventory::query()->where('product_id', $this->maiz->id)->where('warehouse_id', $this->agroindustria->id)->update(['quantity' => 5, 'available_quantity' => 5]);

    Volt::test('inventory.transfers.correct', ['transfer' => $this->transfer])
        ->set('to_warehouse_id', $this->zootecnia->id)
        ->set('reason', 'El traslado era para zootecnia')
        ->call('save')
        ->assertHasErrors('correction')
        ->assertSee('Agroindustria');

    expect(warehouseStock($this->maiz, $this->zootecnia))->toBe(0.0);
});

test('a pending transfer is sent back to its detail page', function () {
    $pending = InventoryTransfer::create([
        'from_warehouse_id' => $this->central->id,
        'to_warehouse_id' => $this->agroindustria->id,
        'physical_document_number' => '7702',
        'document_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Volt::test('inventory.transfers.correct', ['transfer' => $pending])->assertRedirect(route('transfers.show', $pending));
});

test('the dispatch correction screen applies only the difference', function () {
    Inventory::query()->create(['product_id' => $this->maiz->id, 'warehouse_id' => $this->zootecnia->id, 'quantity' => 0, 'reserved_quantity' => 0, 'unit_cost' => 2.0, 'is_active' => true]);
    Inventory::query()->where('warehouse_id', $this->agroindustria->id)->update(['quantity' => 50, 'available_quantity' => 50]);

    $dispatch = Dispatch::create([
        'dispatch_number' => 'DIS-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'warehouse_id' => $this->agroindustria->id,
        'area_id' => $this->area->id,
        'dispatch_type' => 'interno',
        'document_type' => 'remision',
        'physical_document_number' => '7703',
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'is_active' => true,
        'active_at' => now(),
    ]);
    DispatchDetail::create(['dispatch_id' => $dispatch->id, 'product_id' => $this->maiz->id, 'quantity' => 10, 'unit_of_measure_id' => $this->unit->id, 'unit_price' => 0]);
    $dispatch->fresh()->dispatch($this->editor->id);
    $dispatch->fresh()->deliver($this->editor->id, 'Encargado');

    $this->get(route('dispatches.show', $dispatch->fresh()))->assertSee(route('dispatches.correct', $dispatch->fresh()));

    Volt::test('dispatches.correct', ['dispatch' => $dispatch->fresh()])
        ->set('lines.0.quantity', 12)
        ->set('reason', 'Se entregaron 12 unidades')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dispatches.show', $dispatch->fresh()));

    expect(warehouseStock($this->maiz, $this->agroindustria))->toBe(38.0)
        ->and((float) $dispatch->fresh()->details->first()->quantity)->toBe(12.0);
});

test('the production correction screen corrects the production', function () {
    $production = InternalProduction::create([
        'production_number' => 'PI-TEST-'.uniqid(),
        'company_id' => $this->company->id,
        'area_id' => $this->area->id,
        'warehouse_id' => $this->zootecnia->id,
        'physical_document_number' => '7704',
        'document_date' => now()->toDateString(),
        'status' => 'aprobado',
        'is_active' => true,
    ]);
    InternalProductionDetail::create(['internal_production_id' => $production->id, 'product_id' => $this->maiz->id, 'quantity' => 20, 'unit_of_measure_id' => $this->unit->id, 'unit_price' => 1.5]);
    $production->fresh()->complete($this->editor->id);

    Volt::test('internal-productions.correct', ['internalProduction' => $production->fresh()])
        ->assertSet('lines.0.unit_price', 1.5)
        ->set('lines.0.quantity', 15)
        ->set('reason', 'Se produjeron 15 unidades')
        ->call('save')
        ->assertHasNoErrors();

    expect(warehouseStock($this->maiz, $this->zootecnia))->toBe(15.0)
        ->and((float) $production->fresh()->details->first()->quantity)->toBe(15.0);
});
