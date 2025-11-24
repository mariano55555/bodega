<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Warehouse Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->forCompany($this->company)->create();
        $this->user = User::factory()->create();
        $this->manager = User::factory()->warehouseManager()->forCompany($this->company)->create();
    });

    describe('relationships', function () {
        it('belongs to company', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse->company)->toBeInstanceOf(Company::class);
            expect($warehouse->company->id)->toBe($this->company->id);
        });

        it('belongs to branch', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse->branch)->toBeInstanceOf(Branch::class);
            expect($warehouse->branch->id)->toBe($this->branch->id);
        });

        it('belongs to manager user when assigned', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'manager_id' => $this->manager->id])
                ->create();

            expect($warehouse->manager)->toBeInstanceOf(User::class);
            expect($warehouse->manager->id)->toBe($this->manager->id);
        });

        it('belongs to creator user', function () {
            $this->actingAs($this->user);
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse->creator)->toBeInstanceOf(User::class);
            expect($warehouse->creator->id)->toBe($this->user->id);
        });

        it('belongs to updater user when updated', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            $this->actingAs($this->user);
            $warehouse->update(['name' => 'Updated Name']);

            expect($warehouse->fresh()->updater)->toBeInstanceOf(User::class);
            expect($warehouse->fresh()->updater->id)->toBe($this->user->id);
        });

        it('belongs to deleter user when soft deleted', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            $this->actingAs($this->user);
            $warehouse->delete();

            expect($warehouse->fresh()->deleter)->toBeInstanceOf(User::class);
            expect($warehouse->fresh()->deleter->id)->toBe($this->user->id);
        });

        it('has many storage locations', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            // Create storage locations directly associated with warehouse
            $storageLocations = collect();
            for ($i = 0; $i < 3; $i++) {
                $storageLocation = new StorageLocation([
                    'name' => "Location {$i}",
                    'warehouse_id' => $warehouse->id,
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ]);
                $storageLocation->save();
                $storageLocations->push($storageLocation);
            }

            expect($warehouse->storageLocations)->toHaveCount(3);
            foreach ($storageLocations as $location) {
                expect($warehouse->storageLocations->contains($location))->toBeTrue();
            }
        });

        it('has many inventory records', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            // Create inventory records (this would require Product model)
            $inventoryRecords = collect();
            for ($i = 0; $i < 2; $i++) {
                $inventory = new Inventory([
                    'warehouse_id' => $warehouse->id,
                    'company_id' => $this->company->id,
                    'quantity' => 100,
                    'unit_cost' => 10.50,
                    'total_value' => 1050.00,
                ]);
                $inventory->save();
                $inventoryRecords->push($inventory);
            }

            expect($warehouse->inventory)->toHaveCount(2);
            foreach ($inventoryRecords as $inventory) {
                expect($warehouse->inventory->contains($inventory))->toBeTrue();
            }
        });

        it('has many inventory movements', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            // Create inventory movements
            $movements = collect();
            for ($i = 0; $i < 2; $i++) {
                $movement = new InventoryMovement([
                    'warehouse_id' => $warehouse->id,
                    'company_id' => $this->company->id,
                    'type' => 'in',
                    'quantity' => 50,
                    'unit_cost' => 10.00,
                    'total_cost' => 500.00,
                    'reference_number' => "REF{$i}",
                ]);
                $movement->save();
                $movements->push($movement);
            }

            expect($warehouse->movements)->toHaveCount(2);
            foreach ($movements as $movement) {
                expect($warehouse->movements->contains($movement))->toBeTrue();
            }
        });

        it('has many inventory alerts', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            // Create inventory alerts
            $alerts = collect();
            for ($i = 0; $i < 2; $i++) {
                $alert = new InventoryAlert([
                    'warehouse_id' => $warehouse->id,
                    'company_id' => $this->company->id,
                    'type' => 'low_stock',
                    'message' => "Alert {$i}",
                    'threshold_value' => 10,
                    'current_value' => 5,
                    'is_active' => true,
                ]);
                $alert->save();
                $alerts->push($alert);
            }

            expect($warehouse->alerts)->toHaveCount(2);
            foreach ($alerts as $alert) {
                expect($warehouse->alerts->contains($alert))->toBeTrue();
            }
        });

        it('has many outgoing transfers', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();
            $toWarehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            // Create outgoing transfers
            $transfers = collect();
            for ($i = 0; $i < 2; $i++) {
                $transfer = new InventoryTransfer([
                    'from_warehouse_id' => $warehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'company_id' => $this->company->id,
                    'reference_number' => "OUT{$i}",
                    'quantity' => 25,
                    'status' => 'pending',
                    'requested_by' => $this->user->id,
                ]);
                $transfer->save();
                $transfers->push($transfer);
            }

            expect($warehouse->outgoingTransfers)->toHaveCount(2);
            foreach ($transfers as $transfer) {
                expect($warehouse->outgoingTransfers->contains($transfer))->toBeTrue();
            }
        });

        it('has many incoming transfers', function () {
            $fromWarehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            // Create incoming transfers
            $transfers = collect();
            for ($i = 0; $i < 2; $i++) {
                $transfer = new InventoryTransfer([
                    'from_warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $warehouse->id,
                    'company_id' => $this->company->id,
                    'reference_number' => "IN{$i}",
                    'quantity' => 25,
                    'status' => 'pending',
                    'requested_by' => $this->user->id,
                ]);
                $transfer->save();
                $transfers->push($transfer);
            }

            expect($warehouse->incomingTransfers)->toHaveCount(2);
            foreach ($transfers as $transfer) {
                expect($warehouse->incomingTransfers->contains($transfer))->toBeTrue();
            }
        });
    });

    describe('scopes', function () {
        it('filters active warehouses', function () {
            $activeWarehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'is_active' => true, 'active_at' => now()])
                ->create();
            $inactiveWarehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->inactive()
                ->create();

            $activeWarehouses = Warehouse::active()->get();

            expect($activeWarehouses->contains($activeWarehouse))->toBeTrue();
            expect($activeWarehouses->contains($inactiveWarehouse))->toBeFalse();
        });
    });

    describe('model events and attributes', function () {
        it('automatically generates slug on creation', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => 'Test Warehouse Name'])
                ->create();

            expect($warehouse->slug)->toBe('test-warehouse-name');
        });

        it('sets active_at when is_active is true on creation', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'is_active' => true])
                ->create();

            expect($warehouse->active_at)->not->toBeNull();
        });

        it('sets active_at to null when is_active is false on creation', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->inactive()
                ->create();

            expect($warehouse->active_at)->toBeNull();
        });

        it('updates active_at when is_active changes', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->inactive()
                ->create();

            $warehouse->update(['is_active' => true]);

            expect($warehouse->fresh()->active_at)->not->toBeNull();
        });

        it('sets created_by on creation when user is authenticated', function () {
            $this->actingAs($this->user);
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse->created_by)->toBe($this->user->id);
        });

        it('sets updated_by on update when user is authenticated', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            $this->actingAs($this->user);
            $warehouse->update(['name' => 'Updated Name']);

            expect($warehouse->fresh()->updated_by)->toBe($this->user->id);
        });

        it('sets deleted_by on soft delete when user is authenticated', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            $this->actingAs($this->user);
            $warehouse->delete();

            expect($warehouse->fresh()->deleted_by)->toBe($this->user->id);
        });

        it('regenerates slug when name changes', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => 'Original Name'])
                ->create();
            $originalSlug = $warehouse->slug;

            $warehouse->update(['name' => 'New Name', 'slug' => null]);

            expect($warehouse->fresh()->slug)->toBe('new-name');
            expect($warehouse->fresh()->slug)->not->toBe($originalSlug);
        });

        it('preserves slug when name changes but slug is manually set', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => 'Original Name'])
                ->create();

            $warehouse->update(['name' => 'New Name', 'slug' => 'custom-slug']);

            expect($warehouse->fresh()->slug)->toBe('custom-slug');
        });
    });

    describe('casts', function () {
        it('casts total_capacity to decimal', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'total_capacity' => 1500.75])
                ->create();

            expect($warehouse->total_capacity)->toBeFloat();
            expect($warehouse->total_capacity)->toBe(1500.75);
        });

        it('casts latitude to decimal', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'latitude' => 40.71280123])
                ->create();

            expect($warehouse->latitude)->toBeFloat();
            expect($warehouse->latitude)->toBe(40.71280123);
        });

        it('casts longitude to decimal', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'longitude' => -74.00600456])
                ->create();

            expect($warehouse->longitude)->toBeFloat();
            expect($warehouse->longitude)->toBe(-74.00600456);
        });

        it('casts operating_hours to array', function () {
            $operatingHours = [
                'monday' => ['open' => '08:00', 'close' => '18:00'],
                'tuesday' => ['open' => '08:00', 'close' => '18:00'],
            ];
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'operating_hours' => $operatingHours])
                ->create();

            expect($warehouse->operating_hours)->toBeArray();
            expect($warehouse->operating_hours)->toBe($operatingHours);
        });

        it('casts settings to array', function () {
            $settings = ['key1' => 'value1', 'key2' => 'value2'];
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'settings' => $settings])
                ->create();

            expect($warehouse->settings)->toBeArray();
            expect($warehouse->settings)->toBe($settings);
        });

        it('casts is_active to boolean', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'is_active' => 1])
                ->create();

            expect($warehouse->is_active)->toBeBool();
            expect($warehouse->is_active)->toBeTrue();
        });

        it('casts active_at to datetime', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'is_active' => true])
                ->create();

            expect($warehouse->active_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });
    });

    describe('route key', function () {
        it('uses slug as route key', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => 'Test Warehouse'])
                ->create();

            expect($warehouse->getRouteKeyName())->toBe('slug');
            expect($warehouse->getRouteKey())->toBe('test-warehouse');
        });
    });

    describe('validation and constraints', function () {
        it('allows multiple warehouses per company', function () {
            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse1->company_id)->toBe($this->company->id);
            expect($warehouse2->company_id)->toBe($this->company->id);
            expect($warehouse1->id)->not->toBe($warehouse2->id);
        });

        it('allows multiple warehouses per branch', function () {
            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse1->branch_id)->toBe($this->branch->id);
            expect($warehouse2->branch_id)->toBe($this->branch->id);
            expect($warehouse1->id)->not->toBe($warehouse2->id);
        });

        it('allows same warehouse name in different companies', function () {
            $company2 = Company::factory()->create();
            $branch2 = Branch::factory()->forCompany($company2)->create();

            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => 'Same Name'])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($company2)
                ->state(['branch_id' => $branch2->id, 'name' => 'Same Name'])
                ->create();

            expect($warehouse1->name)->toBe($warehouse2->name);
            expect($warehouse1->company_id)->not->toBe($warehouse2->company_id);
        });

        it('allows same code in different companies', function () {
            $company2 = Company::factory()->create();
            $branch2 = Branch::factory()->forCompany($company2)->create();

            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'code' => 'SAME'])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($company2)
                ->state(['branch_id' => $branch2->id, 'code' => 'SAME'])
                ->create();

            expect($warehouse1->code)->toBe($warehouse2->code);
            expect($warehouse1->company_id)->not->toBe($warehouse2->company_id);
        });

        it('allows warehouse without branch assignment', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => null])
                ->create();

            expect($warehouse->branch_id)->toBeNull();
            expect($warehouse->company_id)->toBe($this->company->id);
        });
    });

    describe('soft deletes', function () {
        it('supports soft deletion', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();
            $warehouseId = $warehouse->id;

            $warehouse->delete();

            expect(Warehouse::find($warehouseId))->toBeNull();
            expect(Warehouse::withTrashed()->find($warehouseId))->not->toBeNull();
            expect(Warehouse::onlyTrashed()->find($warehouseId))->not->toBeNull();
        });

        it('can be restored after soft deletion', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();
            $warehouseId = $warehouse->id;

            $warehouse->delete();
            expect(Warehouse::find($warehouseId))->toBeNull();

            $warehouse->restore();
            expect(Warehouse::find($warehouseId))->not->toBeNull();
        });
    });

    describe('factory states', function () {
        it('creates inactive warehouse with correct attributes', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->inactive()
                ->create();

            expect($warehouse->is_active)->toBeFalse();
            expect($warehouse->active_at)->toBeNull();
        });

        it('creates warehouse with specific capacity', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->withCapacity(2500.75, 'pallets')
                ->create();

            expect($warehouse->total_capacity)->toBe(2500.75);
            expect($warehouse->capacity_unit)->toBe('pallets');
        });

        it('creates warehouse with manager', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->withManager()
                ->create();

            expect($warehouse->manager_id)->not->toBeNull();
            expect($warehouse->manager)->toBeInstanceOf(User::class);
        });
    });

    describe('edge cases', function () {
        it('handles empty name gracefully for slug generation', function () {
            // This would typically fail validation, but test the model behavior
            $warehouse = new Warehouse([
                'name' => '',
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'is_active' => true,
            ]);

            // The slug should be generated from empty string
            $warehouse->save();
            expect($warehouse->slug)->not->toBeNull();
        });

        it('handles special characters in name for slug generation', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => 'Warehouse with Special Chars! @#$%'])
                ->create();

            expect($warehouse->slug)->toBe('warehouse-with-special-chars');
        });

        it('handles long names for slug generation', function () {
            $longName = str_repeat('Very Long Warehouse Name ', 10);
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'name' => $longName])
                ->create();

            expect(strlen($warehouse->slug))->toBeLessThanOrEqual(255);
            expect($warehouse->slug)->toContain('very-long-warehouse-name');
        });

        it('maintains data integrity when user is not authenticated', function () {
            // Test model behavior without authenticated user
            auth()->logout();

            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id])
                ->create();

            expect($warehouse->created_by)->toBeNull();
            expect($warehouse->slug)->not->toBeNull();
        });

        it('handles null coordinates gracefully', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'latitude' => null, 'longitude' => null])
                ->create();

            expect($warehouse->latitude)->toBeNull();
            expect($warehouse->longitude)->toBeNull();
        });

        it('handles complex operating hours structure', function () {
            $complexHours = [
                'monday' => ['open' => '06:00', 'close' => '22:00'],
                'tuesday' => ['open' => '08:30', 'close' => '17:30'],
                'wednesday' => ['open' => '00:00', 'close' => '23:59'],
                'thursday' => ['open' => '08:00', 'close' => '18:00'],
                'friday' => ['open' => '08:00', 'close' => '20:00'],
                'saturday' => ['open' => '09:00', 'close' => '13:00'],
                'sunday' => ['closed' => true],
            ];

            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $this->branch->id, 'operating_hours' => $complexHours])
                ->create();

            expect($warehouse->operating_hours)->toBe($complexHours);
            expect($warehouse->operating_hours['sunday']['closed'])->toBeTrue();
        });
    });
});
