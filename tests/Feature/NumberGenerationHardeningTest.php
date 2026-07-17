<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\InventoryClosure;
use App\Models\Warehouse;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

function makeDispatch(int $companyId, int $warehouseId, string $number): Dispatch
{
    $dispatch = new Dispatch;
    $dispatch->forceFill([
        'company_id' => $companyId,
        'warehouse_id' => $warehouseId,
        'dispatch_type' => 'interno',
        'dispatch_number' => $number,
        'slug' => Str::slug($number),
    ]);
    $dispatch->save();

    return $dispatch;
}

test('dispatch generation shares the series across warehouses of the same suffix', function () {
    $warehouseA = Warehouse::factory()->create(['company_id' => $this->company->id, 'code' => 'BOD-003']);
    $warehouseB = Warehouse::factory()->create(['company_id' => $this->company->id, 'code' => 'AAA-003']);

    // A dispatch of the same suffix lives under a different warehouse.
    makeDispatch($this->company->id, $warehouseB->id, 'BOD-003-D-05');

    $number = Dispatch::generateDispatchNumber($warehouseA->id);

    expect($number)->toBe('BOD-003-D-06');
    expect(Dispatch::withTrashed()->where('dispatch_number', $number)->exists())->toBeFalse();
});

test('dispatch generation does not reuse a soft-deleted number', function () {
    $warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'code' => 'BOD-003']);

    $dispatch = makeDispatch($this->company->id, $warehouse->id, 'BOD-003-D-01');
    $dispatch->delete();

    $number = Dispatch::generateDispatchNumber($warehouse->id);

    expect($number)->toBe('BOD-003-D-02');
});

test('closure generation does not reuse a soft-deleted number', function () {
    $warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'code' => 'BOD-003']);

    $closure = InventoryClosure::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $warehouse->id,
        'closure_number' => 'CLS-202601-0001',
        'slug' => 'cls-202601-0001',
        'year' => 2026,
        'month' => 1,
        'closure_date' => '2026-01-31',
        'period_start_date' => '2026-01-01',
        'period_end_date' => '2026-01-31',
    ]);
    $closure->delete();

    $number = InventoryClosure::generateClosureNumber(2026, 1);

    expect($number)->toBe('CLS-202601-0002');
});
