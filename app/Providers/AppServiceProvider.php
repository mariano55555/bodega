<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use App\Policies\BranchPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Branch::class => BranchPolicy::class,
        Warehouse::class => WarehousePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    /**
     * Register the application's policies.
     */
    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Register custom gates for the application.
     */
    protected function registerGates(): void
    {
        // Gate for warehouse management access
        Gate::define('access-warehouse-management', function ($user) {
            return $user->hasRole(['super_admin', 'company_admin', 'branch_manager', 'warehouse_manager']);
        });

        // Gate for multi-company access (super admin only)
        Gate::define('access-all-companies', function ($user) {
            return $user->hasRole('super_admin');
        });

        // Gate for company administration
        Gate::define('administer-company', function ($user, $company = null) {
            if ($user->hasRole('super_admin')) {
                return true;
            }

            if ($user->hasRole('company_admin') && $company) {
                return $user->company_id === $company->id;
            }

            return false;
        });

        // Gate for branch management within user's company
        Gate::define('manage-company-branches', function ($user, $company = null) {
            if ($user->hasRole(['super_admin', 'company_admin'])) {
                return $company ? $user->company_id === $company->id || $user->hasRole('super_admin') : true;
            }

            return false;
        });

        // Gate for warehouse management within user's access
        Gate::define('manage-warehouses', function ($user, $branch = null) {
            if ($user->hasRole(['super_admin', 'company_admin'])) {
                return $branch ? $user->company_id === $branch->company_id || $user->hasRole('super_admin') : true;
            }

            if ($user->hasRole('branch_manager') && $branch) {
                return $user->branch_id === $branch->id;
            }

            return false;
        });

         // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
