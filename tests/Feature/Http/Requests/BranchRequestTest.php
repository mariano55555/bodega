<?php

declare(strict_types=1);

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

describe('Branch Form Request Validation', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->companyAdmin()->forCompany($this->company)->create();
        $this->actingAs($this->user);
    });

    describe('StoreBranchRequest', function () {
        it('passes validation with valid data', function () {
            $data = [
                'name' => 'Sucursal Principal',
                'code' => 'SP001',
                'description' => 'Descripción de la sucursal principal',
                'company_id' => $this->company->id,
                'email' => 'sucursal@empresa.com',
                'phone' => '+1234567890',
                'manager_name' => 'Juan Pérez',
                'address' => 'Calle Principal 123',
                'city' => 'Ciudad',
                'state' => 'Estado',
                'postal_code' => '12345',
                'country' => 'País',
                'type' => 'main',
                'settings' => ['key' => 'value'],
                'is_active' => true,
                'is_main_branch' => true,
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('requires name field', function () {
            $data = [
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
            expect($validator->errors()->first('name'))->toBe('El nombre de la sucursal es obligatorio.');
        });

        it('validates name field constraints', function () {
            $data = [
                'name' => str_repeat('a', 256), // Exceeds max length
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
            expect($validator->errors()->first('name'))->toContain('no puede tener más de 255 caracteres');
        });

        it('validates code uniqueness within company', function () {
            $existingBranch = Branch::factory()->forCompany($this->company)->create(['code' => 'EXISTING']);

            $data = [
                'name' => 'Nueva Sucursal',
                'code' => 'EXISTING', // Duplicate code
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $request->setContainer(app());
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('code'))->toBeTrue();
            expect($validator->errors()->first('code'))->toBe('Este código de sucursal ya existe en la empresa.');
        });

        it('allows same code in different companies', function () {
            $otherCompany = Company::factory()->create();
            Branch::factory()->forCompany($otherCompany)->create(['code' => 'SAME']);

            $data = [
                'name' => 'Nueva Sucursal',
                'code' => 'SAME', // Same code but different company
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $request->setContainer(app());
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('validates code format', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'code' => 'Invalid Code!', // Contains invalid characters
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('code'))->toBeTrue();
            expect($validator->errors()->first('code'))->toBe('El código de la sucursal solo puede contener letras, números, guiones y guiones bajos.');
        });

        it('validates code length', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'code' => 'VERYLONGCODE', // Exceeds max length
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('code'))->toBeTrue();
            expect($validator->errors()->first('code'))->toContain('no puede tener más de 10 caracteres');
        });

        it('requires company_id field', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('company_id'))->toBeTrue();
            expect($validator->errors()->first('company_id'))->toBe('La empresa es obligatoria.');
        });

        it('validates company_id exists', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => 99999, // Non-existent company
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('company_id'))->toBeTrue();
            expect($validator->errors()->first('company_id'))->toBe('La empresa seleccionada no existe.');
        });

        it('requires type field', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('type'))->toBeTrue();
            expect($validator->errors()->first('type'))->toBe('El tipo de sucursal es obligatorio.');
        });

        it('validates type field values', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'type' => 'invalid_type',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('type'))->toBeTrue();
            expect($validator->errors()->first('type'))->toBe('El tipo de sucursal seleccionado no es válido.');
        });

        it('validates email format', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'type' => 'branch',
                'email' => 'invalid-email',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('email'))->toBeTrue();
            expect($validator->errors()->first('email'))->toBe('El correo electrónico debe tener un formato válido.');
        });

        it('validates optional field lengths', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'type' => 'branch',
                'description' => str_repeat('a', 1001), // Exceeds max length
                'email' => str_repeat('a', 250).'@test.com', // Exceeds max length
                'phone' => str_repeat('1', 21), // Exceeds max length
                'manager_name' => str_repeat('a', 256), // Exceeds max length
                'address' => str_repeat('a', 501), // Exceeds max length
                'city' => str_repeat('a', 101), // Exceeds max length
                'state' => str_repeat('a', 101), // Exceeds max length
                'postal_code' => str_repeat('1', 21), // Exceeds max length
                'country' => str_repeat('a', 101), // Exceeds max length
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('description'))->toBeTrue();
            expect($validator->errors()->has('email'))->toBeTrue();
            expect($validator->errors()->has('phone'))->toBeTrue();
            expect($validator->errors()->has('manager_name'))->toBeTrue();
            expect($validator->errors()->has('address'))->toBeTrue();
            expect($validator->errors()->has('city'))->toBeTrue();
            expect($validator->errors()->has('state'))->toBeTrue();
            expect($validator->errors()->has('postal_code'))->toBeTrue();
            expect($validator->errors()->has('country'))->toBeTrue();
        });

        it('validates boolean fields', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'type' => 'branch',
                'is_active' => 'not_boolean',
                'is_main_branch' => 'not_boolean',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('is_active'))->toBeTrue();
            expect($validator->errors()->has('is_main_branch'))->toBeTrue();
            expect($validator->errors()->first('is_active'))->toBe('El estado activo debe ser verdadero o falso.');
            expect($validator->errors()->first('is_main_branch'))->toBe('La sucursal principal debe ser verdadero o falso.');
        });

        it('validates settings as array', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'type' => 'branch',
                'settings' => 'not_an_array',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('settings'))->toBeTrue();
            expect($validator->errors()->first('settings'))->toBe('Las configuraciones deben ser un arreglo.');
        });

        it('accepts null for optional fields', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'type' => 'branch',
                'code' => null,
                'description' => null,
                'email' => null,
                'phone' => null,
                'manager_name' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'settings' => null,
                'is_active' => null,
                'is_main_branch' => null,
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('UpdateBranchRequest', function () {
        beforeEach(function () {
            $this->branch = Branch::factory()->forCompany($this->company)->create([
                'code' => 'EXISTING',
            ]);
        });

        it('passes validation with valid data', function () {
            $data = [
                'name' => 'Sucursal Actualizada',
                'code' => 'UPD001',
                'description' => 'Descripción actualizada',
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new UpdateBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('allows keeping the same code when updating', function () {
            $data = [
                'name' => 'Sucursal Actualizada',
                'code' => 'EXISTING', // Same code as current branch
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new UpdateBranchRequest;
            $request->setContainer(app());
            $request->setRouteResolver(function () {
                return new class
                {
                    public function parameter($key)
                    {
                        return 'EXISTING'; // Simulate route parameter for branch code
                    }
                };
            });
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('validates unique code constraint on update', function () {
            $anotherBranch = Branch::factory()->forCompany($this->company)->create(['code' => 'ANOTHER']);

            $data = [
                'name' => 'Sucursal Actualizada',
                'code' => 'ANOTHER', // Code exists on different branch
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new UpdateBranchRequest;
            $request->setContainer(app());
            $request->setRouteResolver(function () {
                return new class
                {
                    public function parameter($key)
                    {
                        return 'EXISTING'; // Current branch code
                    }
                };
            });
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('code'))->toBeTrue();
        });

        it('follows same validation rules as store request', function () {
            $data = [
                'name' => '', // Invalid empty name
                'company_id' => $this->company->id,
                'type' => 'invalid_type',
            ];

            $request = new UpdateBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
            expect($validator->errors()->has('type'))->toBeTrue();
        });
    });

    describe('Spanish Error Messages', function () {
        it('provides Spanish error messages for all validation rules', function () {
            $request = new StoreBranchRequest;
            $messages = $request->messages();

            // Test key messages are in Spanish
            expect($messages['name.required'])->toBe('El nombre de la sucursal es obligatorio.');
            expect($messages['code.unique'])->toBe('Este código de sucursal ya existe en la empresa.');
            expect($messages['company_id.required'])->toBe('La empresa es obligatoria.');
            expect($messages['type.required'])->toBe('El tipo de sucursal es obligatorio.');
            expect($messages['email.email'])->toBe('El correo electrónico debe tener un formato válido.');
            expect($messages['is_active.boolean'])->toBe('El estado activo debe ser verdadero o falso.');
        });

        it('uses Spanish messages in actual validation', function () {
            $data = [
                'type' => 'invalid_type',
                'email' => 'invalid-email',
                'is_active' => 'not_boolean',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();

            $errors = $validator->errors();
            expect($errors->first('name'))->toBe('El nombre de la sucursal es obligatorio.');
            expect($errors->first('company_id'))->toBe('La empresa es obligatoria.');
            expect($errors->first('type'))->toBe('El tipo de sucursal seleccionado no es válido.');
            expect($errors->first('email'))->toBe('El correo electrónico debe tener un formato válido.');
            expect($errors->first('is_active'))->toBe('El estado activo debe ser verdadero o falso.');
        });
    });

    describe('Edge Cases', function () {
        it('handles special characters in code field', function () {
            $data = [
                'name' => 'Nueva Sucursal',
                'code' => 'ABC-123_', // Valid alpha_dash characters
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('handles boundary length values', function () {
            $data = [
                'name' => str_repeat('a', 255), // Exactly max length
                'code' => str_repeat('A', 10), // Exactly max length
                'description' => str_repeat('a', 1000), // Exactly max length
                'company_id' => $this->company->id,
                'type' => 'branch',
            ];

            $request = new StoreBranchRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('validates all allowed type values', function () {
            $allowedTypes = ['main', 'branch', 'warehouse', 'distribution', 'retail', 'office'];

            foreach ($allowedTypes as $type) {
                $data = [
                    'name' => 'Sucursal Test',
                    'company_id' => $this->company->id,
                    'type' => $type,
                ];

                $request = new StoreBranchRequest;
                $validator = Validator::make($data, $request->rules(), $request->messages());

                expect($validator->passes())->toBeTrue("Type '{$type}' should be valid");
            }
        });
    });
});
