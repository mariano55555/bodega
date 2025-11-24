<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Policies\CompanyPolicy;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);

    $this->policy = new CompanyPolicy;

    $this->company1 = Company::factory()->create(['name' => 'Company A']);
    $this->company2 = Company::factory()->create(['name' => 'Company B']);

    $this->superAdmin = User::factory()->forCompany($this->company1)->superAdmin()->create();
    $this->companyAdmin1 = User::factory()->forCompany($this->company1)->companyAdmin()->create();
    $this->companyAdmin2 = User::factory()->forCompany($this->company2)->companyAdmin()->create();
    $this->branchManager = User::factory()->forCompany($this->company1)->branchManager()->create();
    $this->warehouseManager = User::factory()->forCompany($this->company1)->warehouseManager()->create();
    $this->warehouseOperator = User::factory()->forCompany($this->company1)->warehouseOperator()->create();
    $this->regularUser = User::factory()->forCompany($this->company1)->create();
});

describe('CompanyPolicy viewAny Method', function () {
    it('allows super admin to view any companies', function () {
        expect($this->policy->viewAny($this->superAdmin))->toBeTrue();
    });

    it('allows company admin to view companies', function () {
        expect($this->policy->viewAny($this->companyAdmin1))->toBeTrue();
    });

    it('allows branch manager to view companies', function () {
        expect($this->policy->viewAny($this->branchManager))->toBeTrue();
    });

    it('allows warehouse manager to view companies', function () {
        expect($this->policy->viewAny($this->warehouseManager))->toBeTrue();
    });

    it('denies warehouse operator from viewing companies', function () {
        expect($this->policy->viewAny($this->warehouseOperator))->toBeFalse();
    });

    it('denies regular user from viewing companies', function () {
        expect($this->policy->viewAny($this->regularUser))->toBeFalse();
    });

    it('denies guest users from viewing companies', function () {
        $guestUser = User::factory()->create(['company_id' => null]);

        expect($this->policy->viewAny($guestUser))->toBeFalse();
    });
});

describe('CompanyPolicy view Method', function () {
    it('allows super admin to view any company', function () {
        expect($this->policy->view($this->superAdmin, $this->company1))->toBeTrue()
            ->and($this->policy->view($this->superAdmin, $this->company2))->toBeTrue();
    });

    it('allows company admin to view their own company', function () {
        expect($this->policy->view($this->companyAdmin1, $this->company1))->toBeTrue();
    });

    it('denies company admin from viewing other companies', function () {
        expect($this->policy->view($this->companyAdmin1, $this->company2))->toBeFalse();
    });

    it('allows users to view their own company only', function () {
        expect($this->policy->view($this->branchManager, $this->company1))->toBeTrue()
            ->and($this->policy->view($this->branchManager, $this->company2))->toBeFalse();
    });

    it('denies users without company assignment', function () {
        $userWithoutCompany = User::factory()->create(['company_id' => null]);

        expect($this->policy->view($userWithoutCompany, $this->company1))->toBeFalse();
    });

    it('handles non-existent company gracefully', function () {
        $nonExistentCompany = new Company(['id' => 99999]);

        expect($this->policy->view($this->companyAdmin1, $nonExistentCompany))->toBeFalse();
    });
});

describe('CompanyPolicy create Method', function () {
    it('allows only super admin to create companies', function () {
        expect($this->policy->create($this->superAdmin))->toBeTrue();
    });

    it('denies company admin from creating companies', function () {
        expect($this->policy->create($this->companyAdmin1))->toBeFalse();
    });

    it('denies all lower-level roles from creating companies', function () {
        expect($this->policy->create($this->branchManager))->toBeFalse()
            ->and($this->policy->create($this->warehouseManager))->toBeFalse()
            ->and($this->policy->create($this->warehouseOperator))->toBeFalse()
            ->and($this->policy->create($this->regularUser))->toBeFalse();
    });

    it('denies users without roles from creating companies', function () {
        $userWithoutRole = User::factory()->create();

        expect($this->policy->create($userWithoutRole))->toBeFalse();
    });
});

describe('CompanyPolicy update Method', function () {
    it('allows super admin to update any company', function () {
        expect($this->policy->update($this->superAdmin, $this->company1))->toBeTrue()
            ->and($this->policy->update($this->superAdmin, $this->company2))->toBeTrue();
    });

    it('allows company admin to update their own company', function () {
        expect($this->policy->update($this->companyAdmin1, $this->company1))->toBeTrue();
    });

    it('denies company admin from updating other companies', function () {
        expect($this->policy->update($this->companyAdmin1, $this->company2))->toBeFalse();
    });

    it('denies all other roles from updating companies', function () {
        expect($this->policy->update($this->branchManager, $this->company1))->toBeFalse()
            ->and($this->policy->update($this->warehouseManager, $this->company1))->toBeFalse()
            ->and($this->policy->update($this->warehouseOperator, $this->company1))->toBeFalse()
            ->and($this->policy->update($this->regularUser, $this->company1))->toBeFalse();
    });

    it('denies users without company assignment', function () {
        $userWithoutCompany = User::factory()->create(['company_id' => null]);

        expect($this->policy->update($userWithoutCompany, $this->company1))->toBeFalse();
    });
});

describe('CompanyPolicy delete Method', function () {
    it('allows only super admin to delete companies', function () {
        expect($this->policy->delete($this->superAdmin, $this->company1))->toBeTrue()
            ->and($this->policy->delete($this->superAdmin, $this->company2))->toBeTrue();
    });

    it('denies company admin from deleting companies', function () {
        expect($this->policy->delete($this->companyAdmin1, $this->company1))->toBeFalse()
            ->and($this->policy->delete($this->companyAdmin1, $this->company2))->toBeFalse();
    });

    it('denies all other roles from deleting companies', function () {
        expect($this->policy->delete($this->branchManager, $this->company1))->toBeFalse()
            ->and($this->policy->delete($this->warehouseManager, $this->company1))->toBeFalse()
            ->and($this->policy->delete($this->warehouseOperator, $this->company1))->toBeFalse()
            ->and($this->policy->delete($this->regularUser, $this->company1))->toBeFalse();
    });
});

describe('CompanyPolicy restore Method', function () {
    it('allows only super admin to restore companies', function () {
        expect($this->policy->restore($this->superAdmin, $this->company1))->toBeTrue()
            ->and($this->policy->restore($this->superAdmin, $this->company2))->toBeTrue();
    });

    it('denies all other roles from restoring companies', function () {
        expect($this->policy->restore($this->companyAdmin1, $this->company1))->toBeFalse()
            ->and($this->policy->restore($this->branchManager, $this->company1))->toBeFalse()
            ->and($this->policy->restore($this->warehouseManager, $this->company1))->toBeFalse()
            ->and($this->policy->restore($this->warehouseOperator, $this->company1))->toBeFalse()
            ->and($this->policy->restore($this->regularUser, $this->company1))->toBeFalse();
    });
});

describe('CompanyPolicy forceDelete Method', function () {
    it('allows only super admin to force delete companies', function () {
        expect($this->policy->forceDelete($this->superAdmin, $this->company1))->toBeTrue()
            ->and($this->policy->forceDelete($this->superAdmin, $this->company2))->toBeTrue();
    });

    it('denies all other roles from force deleting companies', function () {
        expect($this->policy->forceDelete($this->companyAdmin1, $this->company1))->toBeFalse()
            ->and($this->policy->forceDelete($this->branchManager, $this->company1))->toBeFalse()
            ->and($this->policy->forceDelete($this->warehouseManager, $this->company1))->toBeFalse()
            ->and($this->policy->forceDelete($this->warehouseOperator, $this->company1))->toBeFalse()
            ->and($this->policy->forceDelete($this->regularUser, $this->company1))->toBeFalse();
    });
});

describe('CompanyPolicy Multi-Company Security', function () {
    it('prevents cross-company access for company admins', function () {
        expect($this->policy->view($this->companyAdmin1, $this->company2))->toBeFalse()
            ->and($this->policy->update($this->companyAdmin1, $this->company2))->toBeFalse();
    });

    it('maintains proper isolation between companies', function () {
        // Company 1 admin should not access Company 2 data
        expect($this->policy->view($this->companyAdmin1, $this->company2))->toBeFalse();

        // Company 2 admin should not access Company 1 data
        expect($this->policy->view($this->companyAdmin2, $this->company1))->toBeFalse();

        // But each can access their own
        expect($this->policy->view($this->companyAdmin1, $this->company1))->toBeTrue()
            ->and($this->policy->view($this->companyAdmin2, $this->company2))->toBeTrue();
    });

    it('handles edge case of user changing companies', function () {
        // User originally assigned to Company 1
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($user->canAccessCompany($this->company2->id))->toBeFalse();

        // User company assignment changes to Company 2
        $user->update(['company_id' => $this->company2->id]);

        expect($user->canAccessCompany($this->company2->id))->toBeTrue()
            ->and($user->canAccessCompany($this->company1->id))->toBeFalse();
    });
});

describe('CompanyPolicy Edge Cases and Error Handling', function () {
    it('handles null company_id gracefully', function () {
        $userWithoutCompany = User::factory()->create(['company_id' => null]);

        expect($this->policy->view($userWithoutCompany, $this->company1))->toBeFalse()
            ->and($this->policy->update($userWithoutCompany, $this->company1))->toBeFalse();
    });

    it('handles users with multiple roles correctly', function () {
        $user = User::factory()->forCompany($this->company1)->create();
        $user->assignRole(['company-admin', 'branch-manager']);

        // Should have highest privilege (company-admin)
        expect($this->policy->update($user, $this->company1))->toBeTrue();
    });

    it('handles users without any roles', function () {
        $userWithoutRole = User::factory()->forCompany($this->company1)->create();

        expect($this->policy->viewAny($userWithoutRole))->toBeFalse()
            ->and($this->policy->view($userWithoutRole, $this->company1))->toBeFalse()
            ->and($this->policy->create($userWithoutRole))->toBeFalse()
            ->and($this->policy->update($userWithoutRole, $this->company1))->toBeFalse()
            ->and($this->policy->delete($userWithoutRole, $this->company1))->toBeFalse();
    });

    it('maintains security when role is removed', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($this->policy->update($user, $this->company1))->toBeTrue();

        // Remove the role
        $user->removeRole('company-admin');

        expect($this->policy->update($user, $this->company1))->toBeFalse();
    });
});
