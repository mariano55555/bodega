<?php

declare(strict_types=1);

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

describe('Warehouse Form Request Validation', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->forCompany($this->company)->create();
        $this->user = User::factory()->companyAdmin()->forCompany($this->company)->create();
        $this->manager = User::factory()->warehouseManager()->forCompany($this->company)->create();
        $this->actingAs($this->user);
    });

    describe('StoreWarehouseRequest', function () {
        it('passes validation with valid data', function () {
            $data = [
                'name' => 'Almacén Principal',
                'code' => 'AP001',
                'description' => 'Descripción del almacén principal',
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'address' => 'Calle Industrial 123',
                'city' => 'Ciudad',
                'state' => 'Estado',
                'country' => 'País',
                'postal_code' => '12345',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'total_capacity' => 1500.50,
                'capacity_unit' => 'm3',
                'manager_id' => $this->manager->id,
                'is_active' => true,
                'operating_hours' => [
                    'monday' => ['open' => '08:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '08:00', 'close' => '18:00'],
                ],
                'settings' => ['key' => 'value'],
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('requires name field', function () {
            $data = [
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
            expect($validator->errors()->first('name'))->toBe('El nombre del almacén es obligatorio.');
        });

        it('validates name field constraints', function () {
            $data = [
                'name' => str_repeat('a', 256), // Exceeds max length
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
            expect($validator->errors()->first('name'))->toContain('no puede tener más de 255 caracteres');
        });

        it('validates code uniqueness within company', function () {
            $existingWarehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'code' => 'EXISTING'])
                ->create();

            $data = [
                'name' => 'Nuevo Almacén',
                'code' => 'EXISTING', // Duplicate code
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $request->setContainer(app());
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('code'))->toBeTrue();
            expect($validator->errors()->first('code'))->toBe('Este código de almacén ya existe en la empresa.');
        });

        it('allows same code in different companies', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();
            Warehouse::factory()->forCompany($otherCompany)
                ->state(['branch_id' => $otherBranch->id, 'code' => 'SAME'])
                ->create();

            $data = [
                'name' => 'Nuevo Almacén',
                'code' => 'SAME', // Same code but different company
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $request->setContainer(app());
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('validates code format', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'code' => 'Invalid Code!', // Contains invalid characters
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('code'))->toBeTrue();
            expect($validator->errors()->first('code'))->toBe('El código del almacén solo puede contener letras, números, guiones y guiones bajos.');
        });

        it('requires company_id field', function () {
            $data = [
                'name' => 'Nuevo Almacén',
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('company_id'))->toBeTrue();
            expect($validator->errors()->first('company_id'))->toBe('La empresa es obligatoria.');
        });

        it('validates company_id exists', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => 99999, // Non-existent company
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('company_id'))->toBeTrue();
            expect($validator->errors()->first('company_id'))->toBe('La empresa seleccionada no existe.');
        });

        it('validates branch_id exists when provided', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'branch_id' => 99999, // Non-existent branch
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('branch_id'))->toBeTrue();
            expect($validator->errors()->first('branch_id'))->toBe('La sucursal seleccionada no existe.');
        });

        it('validates latitude coordinates', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'latitude' => 91, // Invalid latitude (> 90)
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('latitude'))->toBeTrue();
            expect($validator->errors()->first('latitude'))->toBe('La latitud debe estar entre -90 y 90 grados.');
        });

        it('validates longitude coordinates', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'longitude' => 181, // Invalid longitude (> 180)
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('longitude'))->toBeTrue();
            expect($validator->errors()->first('longitude'))->toBe('La longitud debe estar entre -180 y 180 grados.');
        });

        it('validates total_capacity is numeric and non-negative', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'total_capacity' => -10, // Negative capacity
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('total_capacity'))->toBeTrue();
            expect($validator->errors()->first('total_capacity'))->toBe('La capacidad total debe ser mayor o igual a 0.');
        });

        it('validates manager_id exists when provided', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'manager_id' => 99999, // Non-existent user
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('manager_id'))->toBeTrue();
            expect($validator->errors()->first('manager_id'))->toBe('El gerente seleccionado no existe.');
        });

        it('validates operating_hours structure', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'operating_hours' => [
                    'monday' => [
                        'open' => '25:00', // Invalid time format
                        'close' => '18:00',
                    ],
                    'tuesday' => [
                        'open' => '08:00',
                        'close' => 'invalid', // Invalid time format
                    ],
                ],
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('operating_hours.monday.open'))->toBeTrue();
            expect($validator->errors()->has('operating_hours.tuesday.close'))->toBeTrue();
        });

        it('validates operating_hours time format', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'operating_hours' => [
                    'monday' => [
                        'open' => '08:00',
                        'close' => '18:00',
                    ],
                    'sunday' => [
                        'closed' => true,
                    ],
                ],
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('validates optional field lengths', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'description' => str_repeat('a', 1001), // Exceeds max length
                'address' => str_repeat('a', 501), // Exceeds max length
                'city' => str_repeat('a', 101), // Exceeds max length
                'state' => str_repeat('a', 101), // Exceeds max length
                'country' => str_repeat('a', 101), // Exceeds max length
                'postal_code' => str_repeat('1', 21), // Exceeds max length
                'capacity_unit' => str_repeat('a', 51), // Exceeds max length
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('description'))->toBeTrue();
            expect($validator->errors()->has('address'))->toBeTrue();
            expect($validator->errors()->has('city'))->toBeTrue();
            expect($validator->errors()->has('state'))->toBeTrue();
            expect($validator->errors()->has('country'))->toBeTrue();
            expect($validator->errors()->has('postal_code'))->toBeTrue();
            expect($validator->errors()->has('capacity_unit'))->toBeTrue();
        });

        it('validates boolean fields', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'is_active' => 'not_boolean',
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('is_active'))->toBeTrue();
            expect($validator->errors()->first('is_active'))->toBe('El estado activo debe ser verdadero o falso.');
        });

        it('validates settings as array', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'settings' => 'not_an_array',
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('settings'))->toBeTrue();
            expect($validator->errors()->first('settings'))->toBe('Las configuraciones deben ser un arreglo.');
        });

        it('accepts null for optional fields', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'code' => null,
                'description' => null,
                'branch_id' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'country' => null,
                'postal_code' => null,
                'latitude' => null,
                'longitude' => null,
                'total_capacity' => null,
                'capacity_unit' => null,
                'manager_id' => null,
                'is_active' => null,
                'operating_hours' => null,
                'settings' => null,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('UpdateWarehouseRequest', function () {
        beforeEach(function () {
            $this->warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'code' => 'EXISTING'])
                ->create();
        });

        it('passes validation with valid data', function () {
            $data = [
                'name' => 'Almacén Actualizado',
                'code' => 'UPD001',
                'description' => 'Descripción actualizada',
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
            ];

            $request = new UpdateWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('allows keeping the same code when updating', function () {
            $data = [
                'name' => 'Almacén Actualizado',
                'code' => 'EXISTING', // Same code as current warehouse
                'company_id' => $this->company->id,
            ];

            $request = new UpdateWarehouseRequest;
            $request->setContainer(app());
            $request->setRouteResolver(function () {
                return new class
                {
                    public function parameter($key)
                    {
                        return 'EXISTING'; // Simulate route parameter for warehouse code
                    }
                };
            });
            $request->merge(['company_id' => $this->company->id]);

            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('validates unique code constraint on update', function () {
            $anotherWarehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'code' => 'ANOTHER'])
                ->create();

            $data = [
                'name' => 'Almacén Actualizado',
                'code' => 'ANOTHER', // Code exists on different warehouse
                'company_id' => $this->company->id,
            ];

            $request = new UpdateWarehouseRequest;
            $request->setContainer(app());
            $request->setRouteResolver(function () {
                return new class
                {
                    public function parameter($key)
                    {
                        return 'EXISTING'; // Current warehouse code
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
                'latitude' => 91, // Invalid coordinate
            ];

            $request = new UpdateWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
            expect($validator->errors()->has('latitude'))->toBeTrue();
        });
    });

    describe('Geographic Coordinates Validation', function () {
        it('accepts valid latitude boundary values', function () {
            $request = new StoreWarehouseRequest;

            // Test boundary values
            $validLatitudes = [-90, -45.5, 0, 45.5, 90];

            foreach ($validLatitudes as $lat) {
                $data = [
                    'name' => 'Test Warehouse',
                    'company_id' => $this->company->id,
                    'latitude' => $lat,
                ];

                $validator = Validator::make($data, $request->rules(), $request->messages());
                expect($validator->passes())->toBeTrue("Latitude {$lat} should be valid");
            }
        });

        it('accepts valid longitude boundary values', function () {
            $request = new StoreWarehouseRequest;

            // Test boundary values
            $validLongitudes = [-180, -90.5, 0, 90.5, 180];

            foreach ($validLongitudes as $lng) {
                $data = [
                    'name' => 'Test Warehouse',
                    'company_id' => $this->company->id,
                    'longitude' => $lng,
                ];

                $validator = Validator::make($data, $request->rules(), $request->messages());
                expect($validator->passes())->toBeTrue("Longitude {$lng} should be valid");
            }
        });

        it('rejects invalid latitude values', function () {
            $request = new StoreWarehouseRequest;

            $invalidLatitudes = [-91, -90.1, 90.1, 91];

            foreach ($invalidLatitudes as $lat) {
                $data = [
                    'name' => 'Test Warehouse',
                    'company_id' => $this->company->id,
                    'latitude' => $lat,
                ];

                $validator = Validator::make($data, $request->rules(), $request->messages());
                expect($validator->fails())->toBeTrue("Latitude {$lat} should be invalid");
                expect($validator->errors()->has('latitude'))->toBeTrue();
            }
        });

        it('rejects invalid longitude values', function () {
            $request = new StoreWarehouseRequest;

            $invalidLongitudes = [-181, -180.1, 180.1, 181];

            foreach ($invalidLongitudes as $lng) {
                $data = [
                    'name' => 'Test Warehouse',
                    'company_id' => $this->company->id,
                    'longitude' => $lng,
                ];

                $validator = Validator::make($data, $request->rules(), $request->messages());
                expect($validator->fails())->toBeTrue("Longitude {$lng} should be invalid");
                expect($validator->errors()->has('longitude'))->toBeTrue();
            }
        });
    });

    describe('Capacity Validation', function () {
        it('accepts zero capacity', function () {
            $data = [
                'name' => 'Test Warehouse',
                'company_id' => $this->company->id,
                'total_capacity' => 0,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('accepts decimal capacity values', function () {
            $data = [
                'name' => 'Test Warehouse',
                'company_id' => $this->company->id,
                'total_capacity' => 1500.75,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('rejects negative capacity', function () {
            $data = [
                'name' => 'Test Warehouse',
                'company_id' => $this->company->id,
                'total_capacity' => -1,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('total_capacity'))->toBeTrue();
        });

        it('rejects non-numeric capacity', function () {
            $data = [
                'name' => 'Test Warehouse',
                'company_id' => $this->company->id,
                'total_capacity' => 'not_a_number',
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('total_capacity'))->toBeTrue();
            expect($validator->errors()->first('total_capacity'))->toBe('La capacidad total debe ser un número.');
        });
    });

    describe('Spanish Error Messages', function () {
        it('provides Spanish error messages for all validation rules', function () {
            $request = new StoreWarehouseRequest;
            $messages = $request->messages();

            // Test key messages are in Spanish
            expect($messages['name.required'])->toBe('El nombre del almacén es obligatorio.');
            expect($messages['code.unique'])->toBe('Este código de almacén ya existe en la empresa.');
            expect($messages['company_id.required'])->toBe('La empresa es obligatoria.');
            expect($messages['latitude.between'])->toBe('La latitud debe estar entre -90 y 90 grados.');
            expect($messages['longitude.between'])->toBe('La longitud debe estar entre -180 y 180 grados.');
            expect($messages['total_capacity.min'])->toBe('La capacidad total debe ser mayor o igual a :min.');
        });

        it('uses Spanish messages in actual validation', function () {
            $data = [
                'latitude' => 91,
                'longitude' => 181,
                'total_capacity' => -1,
                'is_active' => 'not_boolean',
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->fails())->toBeTrue();

            $errors = $validator->errors();
            expect($errors->first('name'))->toBe('El nombre del almacén es obligatorio.');
            expect($errors->first('company_id'))->toBe('La empresa es obligatoria.');
            expect($errors->first('latitude'))->toBe('La latitud debe estar entre -90 y 90 grados.');
            expect($errors->first('longitude'))->toBe('La longitud debe estar entre -180 y 180 grados.');
            expect($errors->first('total_capacity'))->toBe('La capacidad total debe ser mayor o igual a 0.');
            expect($errors->first('is_active'))->toBe('El estado activo debe ser verdadero o falso.');
        });
    });

    describe('Edge Cases', function () {
        it('handles special characters in code field', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'code' => 'WH-123_A', // Valid alpha_dash characters
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('handles boundary length values', function () {
            $data = [
                'name' => str_repeat('a', 255), // Exactly max length
                'code' => str_repeat('A', 10), // Exactly max length
                'description' => str_repeat('a', 1000), // Exactly max length
                'company_id' => $this->company->id,
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });

        it('handles complex operating hours', function () {
            $data = [
                'name' => 'Nuevo Almacén',
                'company_id' => $this->company->id,
                'operating_hours' => [
                    'monday' => ['open' => '06:00', 'close' => '22:00'],
                    'tuesday' => ['open' => '08:30', 'close' => '17:30'],
                    'wednesday' => ['open' => '00:00', 'close' => '23:59'],
                    'sunday' => ['closed' => true],
                ],
            ];

            $request = new StoreWarehouseRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            expect($validator->passes())->toBeTrue();
        });
    });
});
