<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id]);
});

test('command fixes a document date year typo and recalculates balances', function () {
    // Inbound movement earlier in the timeline.
    $inbound = InventoryMovement::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'movement_type' => 'purchase',
        'movement_date' => '2026-01-01',
        'quantity_in' => 10,
        'quantity_out' => 0,
        'dispatch_id' => null,
    ]);

    // Dispatch with a year typo (0026 instead of 2026).
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'document_date' => '0026-06-24',
        'status' => 'entregado',
    ]);

    // Outbound movement pinned to the bad year via the dispatch.
    $outbound = InventoryMovement::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'movement_type' => 'transfer_out',
        'movement_date' => '0026-06-24',
        'quantity_in' => 0,
        'quantity_out' => 3,
        'dispatch_id' => $dispatch->id,
    ]);

    $this->artisan('inventory:fix-document-date-typos --force')->assertSuccessful();

    // Dispatch document date corrected.
    expect($dispatch->fresh()->document_date->format('Y-m-d'))->toBe('2026-06-24');

    // Movement date resynced to the corrected document date.
    expect($outbound->fresh()->movement_date->format('Y-m-d'))->toBe('2026-06-24');

    // Balances recalculated in chronological order: inbound first, then dispatch.
    expect((float) $inbound->fresh()->balance_quantity)->toBe(10.0);
    expect((float) $outbound->fresh()->previous_quantity)->toBe(10.0);
    expect((float) $outbound->fresh()->balance_quantity)->toBe(7.0);

    // No movement is left in the erroneous year range.
    expect(InventoryMovement::whereRaw('YEAR(movement_date) < 2000')->count())->toBe(0);
});

test('command reports nothing to fix when all dates are valid', function () {
    Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'document_date' => '2026-06-24',
    ]);

    $this->artisan('inventory:fix-document-date-typos --dry-run')
        ->expectsOutputToContain('No hay fechas que corregir')
        ->assertSuccessful();
});
