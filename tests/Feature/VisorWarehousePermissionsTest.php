<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\VisorWarehouseAccessSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([RolesAndPermissionsSeeder::class]);

    // The read-only "visor-warehouse" role lives only in production; recreate it here.
    Role::firstOrCreate(['name' => 'visor-warehouse', 'guard_name' => 'web']);

    seed([VisorWarehouseAccessSeeder::class]);

    $this->visor = User::factory()->create();
    $this->visor->assignRole('visor-warehouse');

    $this->manager = User::factory()->create();
    $this->manager->assignRole('warehouse-manager');

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super-admin');
});

it('blocks the viewer from write (create) routes', function (string $routeName) {
    actingAs($this->visor)->get(route($routeName))->assertForbidden();
})->with([
    'inventory.products.create',
    'dispatches.create',
    'donations.create',
    'transfers.create',
    'internal-productions.create',
    'adjustments.create',
    'closures.create',
    'admin.categories.create',
]);

it('blocks the viewer from hidden menu sections', function (string $routeName) {
    actingAs($this->visor)->get(route($routeName))->assertForbidden();
})->with([
    'warehouse.dashboard',
    'inventory.dashboard',
    'queries.kardex',
    'traceability.product-timeline',
    'admin.users.index',
    'admin.roles.index',
    'admin.units.index',
    'dte-imports.index',
    'imports.index',
    'purchases.suppliers.index',
    'donors.index',
    'employees.index',
]);

it('allows the viewer on read-only listings and reports', function (string $routeName) {
    actingAs($this->visor)->get(route($routeName))->assertOk();
})->with([
    'dispatches.index',
    'donations.index',
    'products.catalog',
    'admin.categories.index',
    'reports.kardex',
]);

it('keeps full access for non-viewer roles (no regression)', function (string $routeName) {
    actingAs($this->manager)->get(route($routeName))->assertOk();
})->with([
    'purchases.suppliers.index',
    'donors.index',
    'employees.index',
    'admin.units.index',
    'dispatches.index',
    'dispatches.create',
]);

it('lets super-admin through write routes via the gate bypass', function () {
    actingAs($this->superAdmin)->get(route('admin.categories.create'))->assertOk();
});

it('blocks the viewer from invoking a write action directly on a read-only component', function () {
    actingAs($this->visor);

    Volt::test('dispatches.index')
        ->call('delete', 999999)
        ->assertForbidden();
});

it('renders role permission labels grouped and in Spanish', function () {
    actingAs($this->superAdmin);

    Volt::test('admin.roles.index')
        ->assertSee('Producción Interna')
        ->assertSee('Consultas e Inventario')
        ->assertSee('Control de Usuarios')
        ->assertSee('Gestión de Almacenes')
        ->assertDontSee('Internal productions')
        ->assertDontSee('Inventory queries');
});
