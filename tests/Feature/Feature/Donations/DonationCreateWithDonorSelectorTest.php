<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Donor;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

test('can toggle between existing donor and manual entry modes', function () {
    Volt::test('donations.create')
        ->assertSet('use_existing_donor', true)
        ->call('toggleDonorMode')
        ->assertSet('use_existing_donor', false)
        ->call('toggleDonorMode')
        ->assertSet('use_existing_donor', true);
});

test('can select existing donor and populate fields', function () {
    $donor = Donor::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Donor Organization',
        'donor_type' => 'organization',
        'contact_person' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
        'address' => '123 Test Street',
        'is_active' => true,
        'active_at' => now(),
    ]);

    Volt::test('donations.create')
        ->set('donor_id', $donor->id)
        ->assertSet('donor_name', 'Test Donor Organization')
        ->assertSet('donor_type', 'organization')
        ->assertSet('donor_contact', 'John Doe')
        ->assertSet('donor_email', 'john@example.com')
        ->assertSet('donor_phone', '555-1234')
        ->assertSet('donor_address', '123 Test Street');
});

test('can create donation with existing donor', function () {
    $warehouse = Warehouse::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    $donor = Donor::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    $product = \App\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    Volt::test('donations.create')
        ->set('warehouse_id', $warehouse->id)
        ->set('donor_id', $donor->id)
        ->set('document_type', 'acta')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('reception_date', now()->format('Y-m-d'))
        ->set('details.0.product_id', $product->id)
        ->set('details.0.quantity', 10)
        ->set('details.0.estimated_unit_value', 100)
        ->set('details.0.condition', 'nuevo')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('donations', [
        'warehouse_id' => $warehouse->id,
        'donor_id' => $donor->id,
        'donor_name' => $donor->name,
    ]);
});

test('can create donation with manual donor entry', function () {
    $warehouse = Warehouse::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    $product = \App\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    Volt::test('donations.create')
        ->set('use_existing_donor', false)
        ->set('warehouse_id', $warehouse->id)
        ->set('donor_name', 'Manual Donor Name')
        ->set('donor_type', 'individual')
        ->set('document_type', 'acta')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('reception_date', now()->format('Y-m-d'))
        ->set('details.0.product_id', $product->id)
        ->set('details.0.quantity', 5)
        ->set('details.0.estimated_unit_value', 50)
        ->set('details.0.condition', 'nuevo')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('donations', [
        'warehouse_id' => $warehouse->id,
        'donor_id' => null,
        'donor_name' => 'Manual Donor Name',
        'donor_type' => 'individual',
    ]);
});

test('shows only active donors from users company', function () {
    // Create active donor
    $activeDonor = Donor::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    // Create inactive donor
    Donor::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => false,
        'active_at' => null,
    ]);

    // Create donor from different company
    $otherCompany = Company::factory()->create();
    Donor::factory()->create([
        'company_id' => $otherCompany->id,
        'is_active' => true,
        'active_at' => now(),
    ]);

    $component = Volt::test('donations.create');

    $donors = $component->donors;

    expect($donors)->toHaveCount(1);
    expect($donors->first()->id)->toBe($activeDonor->id);
});
