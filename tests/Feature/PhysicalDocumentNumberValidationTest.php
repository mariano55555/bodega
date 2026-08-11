<?php

declare(strict_types=1);

use App\Http\Requests\StoreDispatchRequest;
use App\Http\Requests\StoreInternalProductionRequest;
use App\Http\Requests\UpdateDispatchRequest;
use App\Http\Requests\UpdateInternalProductionRequest;
use App\Http\Requests\UpdateInventoryTransferRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);
});

dataset('invalid document numbers', [
    'pipe' => '0033836|',
    'letters' => 'ABC123',
    'spaces' => '123 456',
    'dash' => '123-456',
    'decimal' => '123.45',
    'symbols' => '123#456',
]);

dataset('form requests with physical document number', [
    'store dispatch' => StoreDispatchRequest::class,
    'update dispatch' => UpdateDispatchRequest::class,
    'store internal production' => StoreInternalProductionRequest::class,
    'update internal production' => UpdateInternalProductionRequest::class,
    'update inventory transfer' => UpdateInventoryTransferRequest::class,
]);

test('form requests reject non-numeric physical document numbers', function (string $requestClass) {
    $rules = (new $requestClass)->rules();

    $validator = Validator::make(
        ['physical_document_number' => '0033836|'],
        ['physical_document_number' => $rules['physical_document_number']]
    );

    expect($validator->errors()->has('physical_document_number'))->toBeTrue();
})->with('form requests with physical document number');

test('form requests accept numeric physical document numbers', function (string $requestClass) {
    $rules = (new $requestClass)->rules();

    $validator = Validator::make(
        ['physical_document_number' => '0033836'],
        ['physical_document_number' => $rules['physical_document_number']]
    );

    expect($validator->errors()->has('physical_document_number'))->toBeFalse();
})->with('form requests with physical document number');

test('dispatch create component strips non-numeric characters from physical document number', function (string $value) {
    Volt::test('dispatches.create')
        ->set('physical_document_number', $value)
        ->assertSet('physical_document_number', preg_replace('/\D+/', '', $value));
})->with('invalid document numbers');

test('dispatch create component keeps numeric physical document number intact', function () {
    Volt::test('dispatches.create')
        ->set('physical_document_number', '0033836')
        ->assertSet('physical_document_number', '0033836');
});

test('transfer create component strips non-numeric characters from physical document number', function () {
    Volt::test('inventory.transfers.create')
        ->set('physical_document_number', '0033836|')
        ->assertSet('physical_document_number', '0033836');
});

test('internal production create component strips non-numeric characters from physical document number', function () {
    Volt::test('internal-productions.create')
        ->set('physical_document_number', '0033836|')
        ->assertSet('physical_document_number', '0033836');
});
