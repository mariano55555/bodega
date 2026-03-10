<?php

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\DispatchFuelDetail;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);
});

test('dispatch model has fuelDetail relationship', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
    ]);

    $fuelDetail = DispatchFuelDetail::factory()->create([
        'dispatch_id' => $dispatch->id,
    ]);

    expect($dispatch->fuelDetail)->toBeInstanceOf(DispatchFuelDetail::class);
    expect($dispatch->fuelDetail->id)->toBe($fuelDetail->id);
});

test('dispatch fuel detail belongs to dispatch', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
    ]);

    $fuelDetail = DispatchFuelDetail::factory()->create([
        'dispatch_id' => $dispatch->id,
    ]);

    expect($fuelDetail->dispatch)->toBeInstanceOf(Dispatch::class);
    expect($fuelDetail->dispatch->id)->toBe($dispatch->id);
});

test('combustible dispatch type has correct spanish label', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
    ]);

    expect($dispatch->dispatch_type_spanish)->toBe('Combustibles y Lubricantes');
});

test('dispatch fuel detail stores vehicle information', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
    ]);

    $fuelDetail = DispatchFuelDetail::create([
        'dispatch_id' => $dispatch->id,
        'vehicle_class' => 'Camioneta',
        'vehicle_brand' => 'Toyota',
        'vehicle_model' => 'Hilux 2020',
        'vehicle_plate' => 'P-123-456',
        'odometer_reading' => 45000.50,
        'horometer_reading' => 1200.00,
        'place_to_visit' => 'Finca El Café',
        'mission_description' => 'Transporte de materiales',
        'kilometers_to_travel' => 85.50,
    ]);

    $fuelDetail->refresh();

    expect($fuelDetail->vehicle_class)->toBe('Camioneta');
    expect($fuelDetail->vehicle_brand)->toBe('Toyota');
    expect($fuelDetail->vehicle_model)->toBe('Hilux 2020');
    expect($fuelDetail->vehicle_plate)->toBe('P-123-456');
    expect((float) $fuelDetail->odometer_reading)->toBe(45000.50);
    expect((float) $fuelDetail->horometer_reading)->toBe(1200.00);
    expect($fuelDetail->place_to_visit)->toBe('Finca El Café');
    expect($fuelDetail->mission_description)->toBe('Transporte de materiales');
    expect((float) $fuelDetail->kilometers_to_travel)->toBe(85.50);
});

test('dispatch fuel detail sets audit fields on creation', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
    ]);

    $fuelDetail = DispatchFuelDetail::create([
        'dispatch_id' => $dispatch->id,
        'vehicle_class' => 'Tractor',
        'vehicle_brand' => 'John Deere',
        'vehicle_plate' => 'P-999-000',
        'place_to_visit' => 'Campo',
        'mission_description' => 'Arado',
    ]);

    expect($fuelDetail->created_by)->toBe($this->user->id);
    expect($fuelDetail->is_active)->toBeTrue();
    expect($fuelDetail->active_at)->not->toBeNull();
});

test('deleting dispatch cascades to fuel detail via soft delete', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
        'status' => 'cancelado',
    ]);

    $fuelDetail = DispatchFuelDetail::factory()->create([
        'dispatch_id' => $dispatch->id,
    ]);

    $dispatch->delete();

    expect(DispatchFuelDetail::find($fuelDetail->id))->toBeNull();
    expect(DispatchFuelDetail::withTrashed()->find($fuelDetail->id))->not->toBeNull();
});

test('fuel dispatch pdf route returns 200 for combustible dispatch', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'combustible',
        'status' => 'borrador',
    ]);

    DispatchFuelDetail::factory()->create([
        'dispatch_id' => $dispatch->id,
    ]);

    $response = $this->get(route('dispatches.fuel-pdf', $dispatch));

    $response->assertSuccessful();
});

test('fuel dispatch pdf route redirects for non-combustible dispatch', function () {
    $dispatch = Dispatch::factory()->create([
        'company_id' => $this->company->id,
        'dispatch_type' => 'interno',
        'status' => 'borrador',
    ]);

    $response = $this->get(route('dispatches.fuel-pdf', $dispatch));

    $response->assertRedirect();
});
