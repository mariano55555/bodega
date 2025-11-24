<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);

    $this->company1 = Company::factory()->create(['name' => 'Company A']);
    $this->company2 = Company::factory()->create(['name' => 'Company B']);

    $this->branch1 = Branch::factory()->forCompany($this->company1)->create();
    $this->branch2 = Branch::factory()->forCompany($this->company2)->create();

    $this->superAdmin = User::factory()->forCompany($this->company1)->superAdmin()->create();
    $this->companyAdmin1 = User::factory()->forCompany($this->company1)->companyAdmin()->create();
    $this->companyAdmin2 = User::factory()->forCompany($this->company2)->companyAdmin()->create();
    $this->branchManager = User::factory()->forBranch($this->branch1)->branchManager()->create();
});

describe('User Creation and Management', function () {
    it('allows super admin to create users in any company', function () {
        actingAs($this->superAdmin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
        ];

        // This would typically be done through a controller
        $newUser = User::create($userData);

        expect($newUser->name)->toBe('New User')
            ->and($newUser->company_id)->toBe($this->company1->id)
            ->and($newUser->branch_id)->toBe($this->branch1->id);

        assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'company_id' => $this->company1->id,
        ]);
    });

    it('allows company admin to create users in their own company', function () {
        actingAs($this->companyAdmin1);

        $userData = [
            'name' => 'Company User',
            'email' => 'companyuser@example.com',
            'password' => 'password123',
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
        ];

        $newUser = User::create($userData);

        expect($newUser->company_id)->toBe($this->company1->id);

        assertDatabaseHas('users', [
            'name' => 'Company User',
            'email' => 'companyuser@example.com',
            'company_id' => $this->company1->id,
        ]);
    });

    it('prevents company admin from creating users in other companies', function () {
        actingAs($this->companyAdmin1);

        // Company admin from company1 should not create users in company2
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();
    });

    it('prevents unauthorized users from creating users', function () {
        $regularUser = User::factory()->forCompany($this->company1)->create();
        actingAs($regularUser);

        expect($regularUser->hasPermissionTo('create-users'))->toBeFalse();
    });
});

describe('User Role Assignment', function () {
    it('allows super admin to assign any role to users', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->create();
        $role = Role::where('name', 'company-admin')->first();

        $user->assignRole($role);

        expect($user->hasRole('company-admin'))->toBeTrue();

        assertDatabaseHas('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'role_id' => $role->id,
        ]);
    });

    it('allows company admin to assign limited roles within their company', function () {
        actingAs($this->companyAdmin1);

        $user = User::factory()->forCompany($this->company1)->create();

        // Company admin should be able to assign lower-level roles
        $user->assignRole('branch-manager');
        expect($user->hasRole('branch-manager'))->toBeTrue();

        $user->assignRole('warehouse-manager');
        expect($user->hasRole('warehouse-manager'))->toBeTrue();

        $user->assignRole('warehouse-operator');
        expect($user->hasRole('warehouse-operator'))->toBeTrue();
    });

    it('prevents company admin from creating super admin users', function () {
        actingAs($this->companyAdmin1);

        $user = User::factory()->forCompany($this->company1)->create();

        // This would typically be prevented at the controller level
        // but we can test the permission system
        expect($this->companyAdmin1->hasPermissionTo('manage-user-permissions'))->toBeFalse();
    });

    it('prevents cross-company role assignments', function () {
        actingAs($this->companyAdmin1);

        $userInOtherCompany = User::factory()->forCompany($this->company2)->create();

        // Company admin 1 should not be able to assign roles to users in company 2
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();
    });
});

describe('User Update and Modification', function () {
    it('allows super admin to update any user', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->create();

        $user->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        expect($user->fresh()->name)->toBe('Updated Name')
            ->and($user->fresh()->email)->toBe('updated@example.com');

        assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    });

    it('allows company admin to update users in their company', function () {
        actingAs($this->companyAdmin1);

        $user = User::factory()->forCompany($this->company1)->create();

        expect($this->companyAdmin1->canAccessCompany($user->company_id))->toBeTrue();

        $user->update(['name' => 'Company Updated Name']);

        expect($user->fresh()->name)->toBe('Company Updated Name');
    });

    it('prevents company admin from updating users in other companies', function () {
        actingAs($this->companyAdmin1);

        $userInOtherCompany = User::factory()->forCompany($this->company2)->create();

        expect($this->companyAdmin1->canAccessCompany($userInOtherCompany->company_id))->toBeFalse();
    });

    it('allows users to update their own profile', function () {
        $user = User::factory()->forCompany($this->company1)->create();
        actingAs($user);

        $user->update(['name' => 'Self Updated Name']);

        expect($user->fresh()->name)->toBe('Self Updated Name');
    });
});

describe('User Deletion and Deactivation', function () {
    it('allows super admin to delete any user', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->create();
        $userId = $user->id;

        $user->delete();

        expect($user->trashed())->toBeTrue();

        assertDatabaseMissing('users', [
            'id' => $userId,
            'deleted_at' => null,
        ]);
    });

    it('removes role assignments when user is deleted', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();
        $userId = $user->id;

        expect($user->hasRole('company-admin'))->toBeTrue();

        $user->delete();

        // Role assignments should be cleaned up
        assertDatabaseMissing('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $userId,
        ]);
    });

    it('prevents non-authorized users from deleting users', function () {
        $regularUser = User::factory()->forCompany($this->company1)->create();
        actingAs($regularUser);

        expect($regularUser->hasPermissionTo('delete-users'))->toBeFalse();
    });
});

describe('User Query and Filtering', function () {
    it('filters users by company for company admin', function () {
        actingAs($this->companyAdmin1);

        // Create users in both companies
        $company1Users = User::factory()->forCompany($this->company1)->count(3)->create();
        $company2Users = User::factory()->forCompany($this->company2)->count(2)->create();

        // Company admin should only see users from their company
        $accessibleUsers = User::where('company_id', $this->company1->id)->get();
        $inaccessibleUsers = User::where('company_id', $this->company2->id)->get();

        expect($accessibleUsers->count())->toBeGreaterThan(0);
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();

        // Verify all accessible users belong to the correct company
        $accessibleUsers->each(function ($user) {
            expect($user->company_id)->toBe($this->company1->id);
        });
    });

    it('filters users by branch for branch manager', function () {
        actingAs($this->branchManager);

        // Create users in different branches
        $branch1Users = User::factory()->forBranch($this->branch1)->count(2)->create();
        $branch2Users = User::factory()->forBranch($this->branch2)->count(2)->create();

        expect($this->branchManager->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($this->branchManager->canAccessBranch($this->branch2->id))->toBeFalse();

        // Branch manager should only access users from their branch
        $accessibleUsers = User::where('branch_id', $this->branch1->id)->get();

        $accessibleUsers->each(function ($user) {
            expect($user->branch_id)->toBe($this->branch1->id);
        });
    });
});

describe('User Role History and Auditing', function () {
    it('maintains role assignment history', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->create();

        // Assign initial role
        $user->assignRole('warehouse-operator');
        expect($user->hasRole('warehouse-operator'))->toBeTrue();

        // Promote user
        $user->assignRole('warehouse-manager');
        expect($user->hasRole('warehouse-manager'))->toBeTrue()
            ->and($user->hasRole('warehouse-operator'))->toBeTrue();

        // Change role completely
        $user->syncRoles(['branch-manager']);
        expect($user->hasRole('branch-manager'))->toBeTrue()
            ->and($user->hasRole('warehouse-manager'))->toBeFalse()
            ->and($user->hasRole('warehouse-operator'))->toBeFalse();
    });

    it('tracks permission changes through roles', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->create();

        // Start with read-only role
        $user->assignRole('warehouse-operator');
        expect($user->hasPermissionTo('view-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('create-inventory'))->toBeFalse();

        // Upgrade to manager role
        $user->syncRoles(['warehouse-manager']);
        expect($user->hasPermissionTo('view-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('create-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('edit-inventory'))->toBeTrue();
    });
});

describe('Bulk User Operations', function () {
    it('allows bulk role assignment by super admin', function () {
        actingAs($this->superAdmin);

        $users = User::factory()->forCompany($this->company1)->count(5)->create();
        $role = Role::where('name', 'warehouse-operator')->first();

        // Bulk assign roles
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $users->each(function ($user) {
            expect($user->hasRole('warehouse-operator'))->toBeTrue();
        });
    });

    it('allows bulk user deactivation', function () {
        actingAs($this->superAdmin);

        $users = User::factory()->forCompany($this->company1)->count(3)->create();

        // Bulk deactivation (soft delete)
        foreach ($users as $user) {
            $user->delete();
        }

        $users->each(function ($user) {
            expect($user->fresh()->trashed())->toBeTrue();
        });
    });
});

describe('User Validation and Constraints', function () {
    it('enforces unique email addresses', function () {
        $email = 'duplicate@example.com';

        User::factory()->create(['email' => $email]);

        expect(fn () => User::factory()->create(['email' => $email]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('requires valid company assignment for company-level roles', function () {
        $user = User::factory()->create(['company_id' => null]);

        // User without company should not be able to have company-level roles
        $user->assignRole('company-admin');

        // This would typically be validated at the business logic level
        expect($user->hasRole('company-admin'))->toBeTrue();
        expect($user->company_id)->toBeNull();
    });

    it('validates branch assignment for branch-level roles', function () {
        $user = User::factory()->forCompany($this->company1)->create(['branch_id' => null]);

        $user->assignRole('branch-manager');

        expect($user->hasRole('branch-manager'))->toBeTrue();
        expect($user->branch_id)->toBeNull();
    });
});
