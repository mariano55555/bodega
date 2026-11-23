<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryValuationService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->reason = MovementReason::factory()->create(['code' => 'RECALC_TEST', 'name' => 'Prueba recalculo', 'slug' => 'prueba-recalculo', 'movement_type' => 'in', 'category' => 'inbound']);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function kardexMovement(float $in, float $out, ?float $unitCost, array $overrides = []): InventoryMovement
{
    return InventoryMovement::create(array_merge([
        'company_id' => test()->company->id,
        'warehouse_id' => test()->warehouse->id,
        'product_id' => test()->product->id,
        'movement_reason_id' => test()->reason->id,
        'movement_type' => $in > 0 ? 'transfer_in' : 'transfer_out',
        'movement_date' => now(),
        'quantity' => $in > 0 ? $in : -$out,
        'quantity_in' => $in,
        'quantity_out' => $out,
        'unit_cost' => $unitCost,
        'total_cost' => $unitCost === null ? null : ($in + $out) * $unitCost,
        'status' => 'completed',
        'is_active' => true,
        'active_at' => now(),
        'created_by' => test()->user->id,
    ], $overrides));
}

/**
 * Reproduces the ENROLAB PLUS 100ML case: 9 units at $14.15, 15 received at $3.18,
 * two outbound movements typed manually at $3.18, and an inventory row that kept
 * the double-counted average of $9.93077.
 */
function enrolabScenario(): Inventory
{
    kardexMovement(9, 0, 14.15, ['movement_type' => 'adjustment']);
    kardexMovement(15, 0, 3.18);
    kardexMovement(0, 1, 3.18);
    kardexMovement(0, 6, 3.18);

    return Inventory::factory()->create([
        'product_id' => test()->product->id,
        'warehouse_id' => test()->warehouse->id,
        'quantity' => 17,
        'reserved_quantity' => 0,
        'available_quantity' => 17,
        'unit_cost' => 9.93077,
        'total_value' => 168.82309,
    ]);
}

test('the current valuation no longer counts the incoming quantity twice', function () {
    $service = app(InventoryValuationService::class);

    $service->applyMovement(kardexMovement(9, 0, 14.15));
    $inventory = $service->applyMovement(kardexMovement(15, 0, 3.18));

    expect((float) $inventory->quantity)->toBe(24.0)
        ->and((float) $inventory->unit_cost)->toBe(7.29375)
        ->and(round((float) $inventory->quantity * (float) $inventory->unit_cost, 2))->toBe(175.05);
});

test('the replay rebuilds the average from the kardex', function () {
    enrolabScenario();

    $balance = app(InventoryValuationService::class)->replayAverageCosts()[$this->product->id.'-'.$this->warehouse->id];

    expect($balance['quantity'])->toBe(17.0)
        ->and($balance['unit_cost'])->toBe(7.29375);
});

test('the command corrects the stored average cost and the inventory value', function () {
    $inventory = enrolabScenario();

    $this->artisan('inventory:recalculate-average-cost')
        ->expectsOutputToContain('1 registros de inventario con costo promedio incorrecto.')
        ->assertSuccessful();

    $inventory->refresh();

    expect((float) $inventory->unit_cost)->toBe(7.29375)
        ->and((float) $inventory->total_value)->toBe(123.99375)
        ->and((float) $inventory->quantity)->toBe(17.0);
});

test('the command does not modify any kardex movement', function () {
    enrolabScenario();
    $before = InventoryMovement::query()->orderBy('id')->get(['id', 'unit_cost', 'total_cost', 'balance_unit_cost', 'balance_total_cost', 'updated_at'])->toArray();

    $this->artisan('inventory:recalculate-average-cost')->assertSuccessful();

    $after = InventoryMovement::query()->orderBy('id')->get(['id', 'unit_cost', 'total_cost', 'balance_unit_cost', 'balance_total_cost', 'updated_at'])->toArray();

    expect($after)->toBe($before);
});

test('dry run reports the change without saving it', function () {
    $inventory = enrolabScenario();

    $this->artisan('inventory:recalculate-average-cost', ['--dry-run' => true])
        ->expectsOutputToContain('1 registros de inventario con costo promedio incorrecto.')
        ->assertSuccessful();

    expect((float) $inventory->refresh()->unit_cost)->toBe(9.93077);
});

test('deleted and cancelled movements are left out of the average', function () {
    kardexMovement(10, 0, 100.0)->delete();
    kardexMovement(5, 0, 50.0, ['status' => 'cancelled']);
    $inventory = enrolabScenario();

    $this->artisan('inventory:recalculate-average-cost')->assertSuccessful();

    expect((float) $inventory->refresh()->unit_cost)->toBe(7.29375);
});

test('a row whose stock does not match the kardex is skipped and reported', function () {
    $inventory = enrolabScenario();
    $inventory->update(['quantity' => 20, 'available_quantity' => 20]);

    $this->artisan('inventory:recalculate-average-cost')
        ->expectsOutputToContain('la existencia no coincide con el kardex')
        ->assertSuccessful();

    expect((float) $inventory->refresh()->unit_cost)->toBe(9.93077);
});

test('a correct average is left untouched', function () {
    $inventory = enrolabScenario();
    $inventory->update(['unit_cost' => 7.29375]);

    $this->artisan('inventory:recalculate-average-cost')
        ->expectsOutputToContain('0 registros de inventario con costo promedio incorrecto.')
        ->assertSuccessful();

    expect((float) $inventory->refresh()->unit_cost)->toBe(7.29375);
});

test('the product option limits the recalculation to one product', function () {
    $inventory = enrolabScenario();
    $other = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);

    $this->artisan('inventory:recalculate-average-cost', ['--product' => $other->id])->assertSuccessful();
    expect((float) $inventory->refresh()->unit_cost)->toBe(9.93077);

    $this->artisan('inventory:recalculate-average-cost', ['--product' => $this->product->id])->assertSuccessful();
    expect((float) $inventory->refresh()->unit_cost)->toBe(7.29375);
});
