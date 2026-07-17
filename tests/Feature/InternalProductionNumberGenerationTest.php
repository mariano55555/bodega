<?php

declare(strict_types=1);

use App\Models\Area;
use App\Models\Company;
use App\Models\InternalProduction;
use App\Models\Warehouse;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->company = Company::factory()->create();

    $this->area = new Area;
    $this->area->forceFill([
        'company_id' => $this->company->id,
        'name' => 'Zootecnia',
        'slug' => 'zootecnia',
    ]);
    $this->area->save();

    $this->warehouseA = Warehouse::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'BOD-003',
    ]);

    $this->warehouseB = Warehouse::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'BOD-006',
    ]);
});

function makeProduction(int $companyId, int $areaId, int $warehouseId, string $productionNumber): InternalProduction
{
    $production = new InternalProduction;
    $production->forceFill([
        'company_id' => $companyId,
        'area_id' => $areaId,
        'warehouse_id' => $warehouseId,
        'production_number' => $productionNumber,
        'slug' => Str::slug($productionNumber),
        'physical_document_number' => uniqid('doc-', true),
    ]);
    $production->save();

    return $production;
}

test('generation ignores warehouse and finds numbers of the same suffix elsewhere', function () {
    // Same suffix (003) number lives under a different warehouse (BOD-006).
    makeProduction($this->company->id, $this->area->id, $this->warehouseA->id, 'PI-1-BOD-003');
    makeProduction($this->company->id, $this->area->id, $this->warehouseB->id, 'PI-2-BOD-003');

    $number = InternalProduction::generateProductionNumber($this->warehouseA->id);

    expect($number)->toBe('PI-3-BOD-003');
    expect(InternalProduction::withTrashed()->where('production_number', $number)->exists())->toBeFalse();
});

test('generation skips a number already taken globally including soft-deleted', function () {
    // Stray number occupying PI-1-BOD-003 under another warehouse.
    $stray = makeProduction($this->company->id, $this->area->id, $this->warehouseB->id, 'PI-1-BOD-003');
    $stray->delete();

    // Warehouse A has no productions yet, so it would naively start at PI-1.
    $number = InternalProduction::generateProductionNumber($this->warehouseA->id);

    expect($number)->toBe('PI-2-BOD-003');
});

test('creating a production assigns a unique number despite a stray duplicate', function () {
    makeProduction($this->company->id, $this->area->id, $this->warehouseB->id, 'PI-1-BOD-003');

    $production = new InternalProduction;
    $production->forceFill([
        'company_id' => $this->company->id,
        'area_id' => $this->area->id,
        'warehouse_id' => $this->warehouseA->id,
        'physical_document_number' => uniqid('doc-', true),
    ]);
    $production->save();

    expect($production->production_number)->toBe('PI-2-BOD-003');
    expect(InternalProduction::where('production_number', 'PI-2-BOD-003')->count())->toBe(1);
});
