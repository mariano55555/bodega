<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();

    $this->fromWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega Horticultura']);
    $this->toWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega de Cocina']);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Limon Persico Grande']);

    Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->fromWarehouse->id,
        'quantity' => 200,
        'reserved_quantity' => 0,
        'available_quantity' => 200,
        'unit_cost' => 0.088,
    ]);

    $this->transfer = InventoryTransfer::create([
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'physical_document_number' => '20287',
        'document_date' => now()->toDateString(),
        'reason' => 'Alimentacion alumnos',
        'status' => 'pending',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $this->transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_cost' => 0.088,
    ]);
});

test('edit form lists the warehouses and products of the origin warehouse company', function () {
    $this->actingAs($this->user);

    $component = Volt::test('inventory.transfers.edit', ['transfer' => $this->transfer])
        ->assertSet('company_id', $this->company->id)
        ->assertSet('from_warehouse_id', $this->fromWarehouse->id)
        ->assertSet('products.0.product_id', $this->product->id)
        ->assertSee('Bodega Horticultura')
        ->assertSee('Bodega de Cocina')
        ->assertSee('Limon Persico Grande')
        ->assertDontSee('No hay productos con stock disponible en la bodega seleccionada');

    expect($component->instance()->productsData)->toHaveKey($this->product->id);
});

test('edit form does not list warehouses from another company', function () {
    $this->actingAs($this->user);

    $otherWarehouse = Warehouse::factory()->create(['is_active' => true, 'name' => 'Bodega Ajena']);

    Volt::test('inventory.transfers.edit', ['transfer' => $this->transfer])
        ->assertSee('Bodega Horticultura')
        ->assertDontSee($otherWarehouse->name);
});

test('a user without company can still edit a transfer because the company comes from the origin warehouse', function () {
    $userWithoutCompany = User::factory()->create(['company_id' => null]);
    $this->actingAs($userWithoutCompany);

    Volt::test('inventory.transfers.edit', ['transfer' => $this->transfer])
        ->assertSet('company_id', $this->company->id)
        ->assertSee('Bodega Horticultura')
        ->set('products.0.quantity', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $this->transfer->fresh()->details->first()->quantity)->toBe(3.0);
});

test('editing the quantity of a pending transfer saves the new quantity', function () {
    $this->actingAs($this->user);

    Volt::test('inventory.transfers.edit', ['transfer' => $this->transfer])
        ->set('products.0.quantity', 25)
        ->call('save')
        ->assertHasNoErrors();

    $detail = $this->transfer->fresh()->details->first();

    expect((float) $detail->quantity)->toBe(25.0)
        ->and((int) $detail->product_id)->toBe($this->product->id);
});
