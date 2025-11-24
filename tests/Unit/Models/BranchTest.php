<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Branch Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
    });

    describe('relationships', function () {
        it('belongs to company', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            expect($branch->company)->toBeInstanceOf(Company::class);
            expect($branch->company->id)->toBe($this->company->id);
        });

        it('belongs to creator user', function () {
            $this->actingAs($this->user);
            $branch = Branch::factory()->forCompany($this->company)->create();

            expect($branch->creator)->toBeInstanceOf(User::class);
            expect($branch->creator->id)->toBe($this->user->id);
        });

        it('belongs to updater user when updated', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $this->actingAs($this->user);
            $branch->update(['name' => 'Updated Name']);

            expect($branch->fresh()->updater)->toBeInstanceOf(User::class);
            expect($branch->fresh()->updater->id)->toBe($this->user->id);
        });

        it('belongs to deleter user when soft deleted', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $this->actingAs($this->user);
            $branch->delete();

            expect($branch->fresh()->deleter)->toBeInstanceOf(User::class);
            expect($branch->fresh()->deleter->id)->toBe($this->user->id);
        });

        it('has many users', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $users = User::factory(3)->forCompany($this->company)->forBranch($branch)->create();

            expect($branch->users)->toHaveCount(3);
            foreach ($users as $user) {
                expect($branch->users->contains($user))->toBeTrue();
            }
        });

        it('has many warehouses', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouses = Warehouse::factory(3)->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            expect($branch->warehouses)->toHaveCount(3);
            foreach ($warehouses as $warehouse) {
                expect($branch->warehouses->contains($warehouse))->toBeTrue();
            }
        });

        it('has many storage locations', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            // Create storage locations directly associated with branch
            // Note: This depends on StorageLocation having branch_id field
            $storageLocations = collect();
            for ($i = 0; $i < 2; $i++) {
                $storageLocation = new StorageLocation([
                    'name' => "Location {$i}",
                    'branch_id' => $branch->id,
                    'company_id' => $this->company->id,
                ]);
                $storageLocation->save();
                $storageLocations->push($storageLocation);
            }

            expect($branch->storageLocations)->toHaveCount(2);
            foreach ($storageLocations as $location) {
                expect($branch->storageLocations->contains($location))->toBeTrue();
            }
        });
    });

    describe('scopes', function () {
        it('filters active branches', function () {
            $activeBranch = Branch::factory()->forCompany($this->company)->create(['is_active' => true, 'active_at' => now()]);
            $inactiveBranch = Branch::factory()->forCompany($this->company)->inactive()->create();

            $activeBranches = Branch::active()->get();

            expect($activeBranches->contains($activeBranch))->toBeTrue();
            expect($activeBranches->contains($inactiveBranch))->toBeFalse();
        });

        it('filters by type', function () {
            $mainBranch = Branch::factory()->forCompany($this->company)->type('principal')->create();
            $regularBranch = Branch::factory()->forCompany($this->company)->type('sucursal')->create();

            $principalBranches = Branch::byType('principal')->get();

            expect($principalBranches->contains($mainBranch))->toBeTrue();
            expect($principalBranches->contains($regularBranch))->toBeFalse();
        });

        it('filters main branches', function () {
            $mainBranch = Branch::factory()->forCompany($this->company)->main()->create();
            $regularBranch = Branch::factory()->forCompany($this->company)->create(['is_main_branch' => false]);

            $mainBranches = Branch::main()->get();

            expect($mainBranches->contains($mainBranch))->toBeTrue();
            expect($mainBranches->contains($regularBranch))->toBeFalse();
        });

        it('filters by company', function () {
            $company2 = Company::factory()->create();
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($company2)->create();

            $company1Branches = Branch::forCompany($this->company->id)->get();

            expect($company1Branches->contains($branch1))->toBeTrue();
            expect($company1Branches->contains($branch2))->toBeFalse();
        });
    });

    describe('model events and attributes', function () {
        it('automatically generates slug on creation', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['name' => 'Test Branch Name']);

            expect($branch->slug)->toBe('test-branch-name');
        });

        it('automatically generates code on creation when not provided', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['code' => null]);

            expect($branch->code)->not->toBeNull();
            expect(strlen($branch->code))->toBe(6);
            expect(ctype_upper($branch->code))->toBeTrue();
        });

        it('preserves provided code during creation', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['code' => 'CUSTOM']);

            expect($branch->code)->toBe('CUSTOM');
        });

        it('sets active_at when is_active is true on creation', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['is_active' => true]);

            expect($branch->active_at)->not->toBeNull();
        });

        it('sets active_at to null when is_active is false on creation', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['is_active' => false, 'active_at' => null]);

            expect($branch->active_at)->toBeNull();
        });

        it('updates active_at when is_active changes', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['is_active' => false, 'active_at' => null]);

            $branch->update(['is_active' => true]);

            expect($branch->fresh()->active_at)->not->toBeNull();
        });

        it('sets created_by on creation when user is authenticated', function () {
            $this->actingAs($this->user);
            $branch = Branch::factory()->forCompany($this->company)->create();

            expect($branch->created_by)->toBe($this->user->id);
        });

        it('sets updated_by on update when user is authenticated', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $this->actingAs($this->user);
            $branch->update(['name' => 'Updated Name']);

            expect($branch->fresh()->updated_by)->toBe($this->user->id);
        });

        it('sets deleted_by on soft delete when user is authenticated', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $this->actingAs($this->user);
            $branch->delete();

            expect($branch->fresh()->deleted_by)->toBe($this->user->id);
        });

        it('regenerates slug when name changes', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['name' => 'Original Name']);
            $originalSlug = $branch->slug;

            $branch->update(['name' => 'New Name', 'slug' => null]);

            expect($branch->fresh()->slug)->toBe('new-name');
            expect($branch->fresh()->slug)->not->toBe($originalSlug);
        });

        it('preserves slug when name changes but slug is manually set', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['name' => 'Original Name']);

            $branch->update(['name' => 'New Name', 'slug' => 'custom-slug']);

            expect($branch->fresh()->slug)->toBe('custom-slug');
        });
    });

    describe('casts', function () {
        it('casts settings to array', function () {
            $settings = ['key1' => 'value1', 'key2' => 'value2'];
            $branch = Branch::factory()->forCompany($this->company)->create(['settings' => $settings]);

            expect($branch->settings)->toBeArray();
            expect($branch->settings)->toBe($settings);
        });

        it('casts is_active to boolean', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['is_active' => 1]);

            expect($branch->is_active)->toBeBool();
            expect($branch->is_active)->toBeTrue();
        });

        it('casts is_main_branch to boolean', function () {
            $branch = Branch::factory()->forCompany($this->company)->main()->create();

            expect($branch->is_main_branch)->toBeBool();
            expect($branch->is_main_branch)->toBeTrue();
        });

        it('casts active_at to datetime', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['is_active' => true]);

            expect($branch->active_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });
    });

    describe('route key', function () {
        it('uses slug as route key', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['name' => 'Test Branch']);

            expect($branch->getRouteKeyName())->toBe('slug');
            expect($branch->getRouteKey())->toBe('test-branch');
        });
    });

    describe('computed attributes', function () {
        it('generates full address from address components', function () {
            $branch = Branch::factory()->forCompany($this->company)->create([
                'address' => '123 Main St',
                'city' => 'Test City',
                'state' => 'Test State',
                'postal_code' => '12345',
                'country' => 'Test Country',
            ]);

            $expectedAddress = '123 Main St, Test City, Test State, 12345, Test Country';
            expect($branch->full_address)->toBe($expectedAddress);
        });

        it('handles partial address components gracefully', function () {
            $branch = Branch::factory()->forCompany($this->company)->create([
                'address' => '123 Main St',
                'city' => 'Test City',
                'state' => null,
                'postal_code' => '12345',
                'country' => null,
            ]);

            $expectedAddress = '123 Main St, Test City, 12345';
            expect($branch->full_address)->toBe($expectedAddress);
        });

        it('generates display name with type', function () {
            $branch = Branch::factory()->forCompany($this->company)->create([
                'name' => 'Central Branch',
                'type' => 'principal',
            ]);

            expect($branch->display_name)->toBe('Central Branch (principal)');
        });
    });

    describe('validation and constraints', function () {
        it('allows multiple branches per company', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            expect($branch1->company_id)->toBe($this->company->id);
            expect($branch2->company_id)->toBe($this->company->id);
            expect($branch1->id)->not->toBe($branch2->id);
        });

        it('allows same branch name in different companies', function () {
            $company2 = Company::factory()->create();

            $branch1 = Branch::factory()->forCompany($this->company)->create(['name' => 'Same Name']);
            $branch2 = Branch::factory()->forCompany($company2)->create(['name' => 'Same Name']);

            expect($branch1->name)->toBe($branch2->name);
            expect($branch1->company_id)->not->toBe($branch2->company_id);
        });

        it('allows same code in different companies', function () {
            $company2 = Company::factory()->create();

            $branch1 = Branch::factory()->forCompany($this->company)->create(['code' => 'SAME']);
            $branch2 = Branch::factory()->forCompany($company2)->create(['code' => 'SAME']);

            expect($branch1->code)->toBe($branch2->code);
            expect($branch1->company_id)->not->toBe($branch2->company_id);
        });
    });

    describe('soft deletes', function () {
        it('supports soft deletion', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $branchId = $branch->id;

            $branch->delete();

            expect(Branch::find($branchId))->toBeNull();
            expect(Branch::withTrashed()->find($branchId))->not->toBeNull();
            expect(Branch::onlyTrashed()->find($branchId))->not->toBeNull();
        });

        it('can be restored after soft deletion', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $branchId = $branch->id;

            $branch->delete();
            expect(Branch::find($branchId))->toBeNull();

            $branch->restore();
            expect(Branch::find($branchId))->not->toBeNull();
        });
    });

    describe('factory states', function () {
        it('creates main branch with correct attributes', function () {
            $branch = Branch::factory()->forCompany($this->company)->main()->create();

            expect($branch->is_main_branch)->toBeTrue();
            expect($branch->type)->toBe('principal');
        });

        it('creates inactive branch with correct attributes', function () {
            $branch = Branch::factory()->forCompany($this->company)->inactive()->create();

            expect($branch->is_active)->toBeFalse();
            expect($branch->active_at)->toBeNull();
        });

        it('creates branch with specific type', function () {
            $branch = Branch::factory()->forCompany($this->company)->type('almacen')->create();

            expect($branch->type)->toBe('almacen');
        });
    });

    describe('edge cases', function () {
        it('handles empty name gracefully for slug generation', function () {
            // This would typically fail validation, but test the model behavior
            $branch = new Branch([
                'name' => '',
                'company_id' => $this->company->id,
                'type' => 'sucursal',
                'is_active' => true,
            ]);

            // The slug should be generated from empty string
            $branch->save();
            expect($branch->slug)->not->toBeNull();
        });

        it('handles special characters in name for slug generation', function () {
            $branch = Branch::factory()->forCompany($this->company)->create([
                'name' => 'Branch with Special Chars! @#$%',
            ]);

            expect($branch->slug)->toBe('branch-with-special-chars');
        });

        it('handles long names for slug generation', function () {
            $longName = str_repeat('Very Long Branch Name ', 10);
            $branch = Branch::factory()->forCompany($this->company)->create(['name' => $longName]);

            expect(strlen($branch->slug))->toBeLessThanOrEqual(255);
            expect($branch->slug)->toContain('very-long-branch-name');
        });

        it('maintains data integrity when user is not authenticated', function () {
            // Test model behavior without authenticated user
            auth()->logout();

            $branch = Branch::factory()->forCompany($this->company)->create();

            expect($branch->created_by)->toBeNull();
            expect($branch->slug)->not->toBeNull();
            expect($branch->code)->not->toBeNull();
        });
    });
});
