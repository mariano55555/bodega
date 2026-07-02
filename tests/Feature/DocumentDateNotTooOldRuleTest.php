<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Rules\DocumentDateNotTooOld;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
});

function validateDate(array $data): \Illuminate\Contracts\Validation\Validator
{
    return Validator::make($data, [
        'document_date' => ['required', 'date', new DocumentDateNotTooOld],
    ]);
}

test('company minDocumentDate is null when not configured', function () {
    expect($this->company->documentDateMinYearsBack())->toBeNull();
    expect($this->company->minDocumentDate())->toBeNull();
});

test('company minDocumentDate reflects configured years back', function () {
    $this->company->update(['settings' => ['document_date_min_years_back' => 0]]);

    expect($this->company->fresh()->minDocumentDate()->format('Y-m-d'))
        ->toBe(now()->startOfYear()->format('Y-m-d'));

    $this->company->update(['settings' => ['document_date_min_years_back' => 2]]);

    expect($this->company->fresh()->minDocumentDate()->format('Y-m-d'))
        ->toBe(now()->startOfYear()->subYears(2)->format('Y-m-d'));
});

test('rule passes when company has no restriction configured', function () {
    $this->actingAs($this->user);

    $validator = validateDate(['document_date' => '0026-06-24']);

    expect($validator->passes())->toBeTrue();
});

test('rule rejects a year typo when current year is enforced', function () {
    $this->company->update(['settings' => ['document_date_min_years_back' => 0]]);
    $this->actingAs($this->user);

    $validator = validateDate(['document_date' => '0026-06-24']);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('document_date'))->toContain('no puede ser anterior');
});

test('rule accepts a current year date when current year is enforced', function () {
    $this->company->update(['settings' => ['document_date_min_years_back' => 0]]);
    $this->actingAs($this->user);

    $validator = validateDate(['document_date' => now()->format('Y-m-d')]);

    expect($validator->passes())->toBeTrue();
});

test('rule resolves company from warehouse for a super admin without company', function () {
    $this->company->update(['settings' => ['document_date_min_years_back' => 1]]);

    $superAdmin = User::factory()->create(['company_id' => null]);
    $this->actingAs($superAdmin);

    $validator = Validator::make(
        ['document_date' => now()->subYears(5)->format('Y-m-d'), 'warehouse_id' => $this->warehouse->id],
        ['document_date' => ['required', 'date', new DocumentDateNotTooOld]]
    );

    expect($validator->fails())->toBeTrue();
});

test('rule resolves company from explicit company_id in payload', function () {
    $this->company->update(['settings' => ['document_date_min_years_back' => 0]]);

    $validator = Validator::make(
        ['document_date' => now()->subYears(3)->format('Y-m-d'), 'company_id' => $this->company->id],
        ['document_date' => ['required', 'date', new DocumentDateNotTooOld]]
    );

    expect($validator->fails())->toBeTrue();
});
