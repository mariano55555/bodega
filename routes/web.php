<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\MovementReportController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Warehouse Management Routes - Dashboard and Hierarchy
    Volt::route('warehouse', 'warehouse.dashboard')->name('warehouse.dashboard');
    Volt::route('warehouse/hierarchy', 'warehouse.hierarchy.index')->name('warehouse.hierarchy.index');

    // Branch Management Routes
    Route::prefix('warehouse/branches')->name('warehouse.branches.')->group(function () {
        Volt::route('/', 'warehouse.branches.index')->name('index');
        Volt::route('create', 'warehouse.branches.create')->name('create');
        Route::post('/', [BranchController::class, 'store'])->name('store');
        Route::get('{branch}', [BranchController::class, 'show'])->name('show');
        Volt::route('{branch}/edit', 'warehouse.branches.edit')->name('edit');
        Route::put('{branch}', [BranchController::class, 'update'])->name('update');
        Route::delete('{branch}', [BranchController::class, 'destroy'])->name('destroy');

        // Additional branch endpoints
        Route::patch('{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('by-company/{company?}', [BranchController::class, 'getByCompany'])->name('by-company');
    });

    // Warehouse Management Routes
    Route::prefix('warehouse/warehouses')->name('warehouse.warehouses.')->group(function () {
        Volt::route('/', 'warehouse.warehouses.index')->name('index');
        Volt::route('create', 'warehouse.warehouses.create')->name('create');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::get('{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Volt::route('{warehouse}/edit', 'warehouse.warehouses.edit')->name('edit');
        Route::put('{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');

        // Additional warehouse endpoints
        Route::patch('{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('by-company/{company?}', [WarehouseController::class, 'getByCompany'])->name('by-company');
        Route::get('by-branch/{branch}', [WarehouseController::class, 'getByBranch'])->name('by-branch');
        Route::get('capacity-summary', [WarehouseController::class, 'capacitySummary'])->name('capacity-summary');
    });

    // Company Management Routes (keeping Volt for now but could be converted)
    Volt::route('warehouse/companies', 'warehouse.companies.index')->name('warehouse.companies.index');
    Volt::route('warehouse/companies/create', 'warehouse.companies.create')->name('warehouse.companies.create');
    Volt::route('warehouse/companies/{company}/edit', 'warehouse.companies.edit')->name('warehouse.companies.edit');
    Volt::route('warehouse/capacity', 'warehouse.capacity.index')->name('warehouse.capacity.index');

    // Product Catalog Routes (master list)
    Volt::route('products/catalog', 'products.index')->name('products.catalog');

    // Inventory Management Routes
    Volt::route('inventory', 'inventory.dashboard')->name('inventory.dashboard');

    // Product Management Routes
    Route::prefix('inventory/products')->name('inventory.products.')->group(function () {
        Volt::route('/', 'inventory.products.index')->name('index');
        Route::get('export', [InventoryReportController::class, 'exportProducts'])->name('export');
        Volt::route('create', 'inventory.products.create')->name('create');
        Volt::route('{product:slug}', 'inventory.products.show')->name('show');
        Volt::route('{product:slug}/edit', 'inventory.products.edit')->name('edit');
    });

    Volt::route('inventory/scanner', 'inventory.scanner')->name('inventory.scanner');
    Volt::route('inventory/stock-query', 'inventory.stock-query')->name('inventory.stock.query');
    Volt::route('inventory/movements', 'inventory.movements.index')->name('inventory.movements.index');
    Volt::route('inventory/alerts', 'inventory.alerts.index')->name('inventory.alerts.index');
    Volt::route('inventory/alerts/resolved', 'inventory.alerts.resolved')->name('inventory.alerts.resolved');

    // Transfer Management Routes
    Route::prefix('inventory/transfers')->name('transfers.')->group(function () {
        Volt::route('/', 'inventory.transfers.index')->name('index');
        Volt::route('create', 'inventory.transfers.create')->name('create');
        Volt::route('{transfer}', 'inventory.transfers.show')->name('show');
        Volt::route('{transfer}/edit', 'inventory.transfers.edit')->name('edit');
    });

    // Supplier Management Routes (must be before Purchase routes to avoid route collision)
    Route::prefix('purchases/suppliers')->name('purchases.suppliers.')->group(function () {
        Volt::route('/', 'purchases.suppliers.index')->name('index');
        Volt::route('create', 'purchases.suppliers.create')->name('create');
        Volt::route('{supplier:slug}/edit', 'purchases.suppliers.edit')->name('edit');
    });

    // Purchase Management Routes
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Volt::route('/', 'purchases.index')->name('index');
        Volt::route('create', 'purchases.create')->name('create');
        Volt::route('{purchase:slug}', 'purchases.show')->name('show');
        Volt::route('{purchase:slug}/edit', 'purchases.edit')->name('edit');
    });

    // Dispatch Management Routes
    Route::prefix('dispatches')->name('dispatches.')->group(function () {
        Volt::route('/', 'dispatches.index')->name('index');
        Volt::route('create', 'dispatches.create')->name('create');
        Volt::route('{dispatch:slug}', 'dispatches.show')->name('show');
        Volt::route('{dispatch:slug}/edit', 'dispatches.edit')->name('edit');
        Route::get('{dispatch:slug}/fuel-pdf', [\App\Http\Controllers\DispatchReportController::class, 'exportFuelDispatchPdf'])->name('fuel-pdf');
    });

    // Internal Production Management Routes
    Route::prefix('internal-productions')->name('internal-productions.')->group(function () {
        Volt::route('/', 'internal-productions.index')->name('index');
        Volt::route('create', 'internal-productions.create')->name('create');
        Volt::route('{internalProduction:slug}', 'internal-productions.show')->name('show');
        Volt::route('{internalProduction:slug}/edit', 'internal-productions.edit')->name('edit');
    });

    // Donation Management Routes
    Route::prefix('donations')->name('donations.')->group(function () {
        Volt::route('/', 'donations.index')->name('index');
        Volt::route('create', 'donations.create')->name('create');
        Volt::route('{donation:slug}', 'donations.show')->name('show');
        Volt::route('{donation:slug}/edit', 'donations.edit')->name('edit');
    });

    // Employee Management Routes
    Route::prefix('employees')->name('employees.')->group(function () {
        Volt::route('/', 'employees.index')->name('index');
        Volt::route('create', 'employees.create')->name('create');
        Volt::route('{employee:slug}/edit', 'employees.edit')->name('edit');
    });

    // Donor Management Routes
    Route::prefix('donors')->name('donors.')->group(function () {
        Volt::route('/', 'donors.index')->name('index');
        Volt::route('create', 'donors.create')->name('create');
        Volt::route('{donor:slug}/edit', 'donors.edit')->name('edit');
    });

    // Inventory Adjustments Routes
    Route::prefix('adjustments')->name('adjustments.')->group(function () {
        Volt::route('/', 'adjustments.index')->name('index');
        Volt::route('create', 'adjustments.create')->name('create');
        Volt::route('{inventoryAdjustment:slug}', 'adjustments.show')->name('show');
        Volt::route('{inventoryAdjustment:slug}/edit', 'adjustments.edit')->name('edit');
    });

    // Inventory Closures Routes
    Route::prefix('closures')->name('closures.')->group(function () {
        Volt::route('/', 'closures.index')->name('index');
        Volt::route('create', 'closures.create')->name('create');
        Volt::route('{closure:slug}', 'closures.show')->name('show');
        Route::get('{closure:slug}/export', \App\Http\Controllers\InventoryClosureExportController::class)->name('export');
    });

    // Notifications Routes
    Volt::route('notifications', 'notifications.index')->name('notifications.index');

    // Storage Locations Routes
    Route::prefix('storage-locations')->name('storage-locations.')->group(function () {
        Volt::route('/', 'warehouse.storage-locations.index')->name('index');
        Volt::route('create', 'warehouse.storage-locations.create')->name('create');
        Volt::route('{location:slug}', 'warehouse.storage-locations.show')->name('show');
        Volt::route('{location:slug}/edit', 'warehouse.storage-locations.edit')->name('edit');
    });

    // Queries Routes
    Route::prefix('queries')->name('queries.')->group(function () {
        Volt::route('advanced-search', 'queries.advanced-search')->name('advanced-search');
        Volt::route('kardex', 'queries.kardex')->name('kardex');
        Volt::route('stock-realtime', 'queries.stock-realtime')->name('stock-realtime');
        Volt::route('expiring-products', 'queries.expiring-products')->name('expiring-products');
        Volt::route('low-stock', 'queries.low-stock')->name('low-stock');
    });

    // Traceability Routes
    Route::prefix('traceability')->name('traceability.')->group(function () {
        Volt::route('product-timeline', 'traceability.product-timeline')->name('product-timeline');
        Volt::route('system-log', 'traceability.system-log')->name('system-log');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        // Kardex Reports
        Volt::route('kardex', 'reports.kardex')->name('kardex');
        Route::get('kardex/pdf', [KardexController::class, 'exportPdf'])->name('kardex.pdf');
        Route::get('kardex/excel', [KardexController::class, 'exportExcel'])->name('kardex.excel');

        // Inventory Reports
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Volt::route('/', 'reports.inventory.index')->name('index');
            Volt::route('consolidated', 'reports.inventory.consolidated')->name('consolidated');
            Route::get('consolidated/export', [InventoryReportController::class, 'exportConsolidated'])->name('consolidated.export');
            Route::get('consolidated/pdf', [InventoryReportController::class, 'exportConsolidatedPdf'])->name('consolidated.pdf');
            Volt::route('value', 'reports.inventory.value')->name('value');
            Route::get('value/export', [InventoryReportController::class, 'exportValue'])->name('value.export');
            Volt::route('rotation', 'reports.inventory.rotation')->name('rotation');
            Route::get('rotation/export', [InventoryReportController::class, 'exportRotation'])->name('rotation.export');
        });

        // Movement Reports
        Route::prefix('movements')->name('movements.')->group(function () {
            Volt::route('monthly', 'reports.movements.monthly')->name('monthly');
            Route::get('monthly/export', [MovementReportController::class, 'exportMonthly'])->name('monthly.export');
            Volt::route('income', 'reports.movements.income')->name('income');
            Volt::route('consumption-by-line', 'reports.movements.consumption-by-line')->name('consumption-by-line');
            Route::get('consumption-by-line/export', [MovementReportController::class, 'exportConsumptionByLine'])->name('consumption-by-line.export');
            Volt::route('transfers', 'reports.movements.transfers')->name('transfers');
            Route::get('transfers/export', [MovementReportController::class, 'exportTransfers'])->name('transfers.export');
        });

        // Administrative Reports
        Volt::route('administrative', 'reports.administrative')->name('administrative');
        Volt::route('purchases-by-supplier', 'reports.purchases-by-supplier')->name('purchases-by-supplier');
        Route::get('purchases-by-supplier/pdf', [\App\Http\Controllers\PurchaseReportController::class, 'exportBySupplierPdf'])->name('purchases-by-supplier.pdf');
        Route::get('purchases-by-supplier/excel', [\App\Http\Controllers\PurchaseReportController::class, 'exportBySupplierExcel'])->name('purchases-by-supplier.excel');
        Volt::route('self-consumption', 'reports.self-consumption')->name('self-consumption');
        Volt::route('donations-consolidated', 'reports.donations-consolidated')->name('donations-consolidated');
        Route::get('donations-consolidated/pdf', [\App\Http\Controllers\DonationReportController::class, 'exportConsolidatedPdf'])->name('donations-consolidated.pdf');
        Route::get('donations-consolidated/excel', [\App\Http\Controllers\DonationReportController::class, 'exportConsolidatedExcel'])->name('donations-consolidated.excel');
        Volt::route('pre-closure-differences', 'reports.pre-closure-differences')->name('pre-closure-differences');

        // Purchase Reports Hub
        Route::prefix('purchases')->name('purchases.')->group(function () {
            Volt::route('/', 'reports.purchases.hub')->name('hub');
            Volt::route('detailed', 'reports.purchases.detailed')->name('detailed');
            Route::get('detailed/pdf', [\App\Http\Controllers\PurchaseReportController::class, 'exportDetailedPdf'])->name('detailed.pdf');
            Route::get('detailed/excel', [\App\Http\Controllers\PurchaseReportController::class, 'exportDetailedExcel'])->name('detailed.excel');
            Volt::route('summary-by-line', 'reports.purchases.summary-by-line')->name('summary-by-line');
            Route::get('summary-by-line/pdf', [\App\Http\Controllers\PurchaseReportController::class, 'exportSummaryByLinePdf'])->name('summary-by-line.pdf');
            Route::get('summary-by-line/excel', [\App\Http\Controllers\PurchaseReportController::class, 'exportSummaryByLineExcel'])->name('summary-by-line.excel');
        });

        // Dispatch Reports Hub (Salidas)
        Route::prefix('dispatches')->name('dispatches.')->group(function () {
            Volt::route('/', 'reports.dispatches.hub')->name('hub');
            Volt::route('monthly', 'reports.dispatches.monthly')->name('monthly');
            Route::get('monthly/pdf', [\App\Http\Controllers\DispatchReportController::class, 'exportMonthlyPdf'])->name('monthly.pdf');
            Route::get('monthly/excel', [\App\Http\Controllers\DispatchReportController::class, 'exportMonthlyExcel'])->name('monthly.excel');
            Volt::route('summary-by-line', 'reports.dispatches.summary-by-line')->name('summary-by-line');
            Route::get('summary-by-line/pdf', [\App\Http\Controllers\DispatchReportController::class, 'exportSummaryByLinePdf'])->name('summary-by-line.pdf');
            Route::get('summary-by-line/excel', [\App\Http\Controllers\DispatchReportController::class, 'exportSummaryByLineExcel'])->name('summary-by-line.excel');
            Volt::route('summary-by-line-quantity', 'reports.dispatches.summary-by-line-quantity')->name('summary-by-line-quantity');
            Route::get('summary-by-line-quantity/pdf', [\App\Http\Controllers\DispatchReportController::class, 'exportSummaryByLineQuantityPdf'])->name('summary-by-line-quantity.pdf');
            Route::get('summary-by-line-quantity/excel', [\App\Http\Controllers\DispatchReportController::class, 'exportSummaryByLineQuantityExcel'])->name('summary-by-line-quantity.excel');
            Volt::route('stock-movements', 'reports.dispatches.stock-movements')->name('stock-movements');
            Route::get('stock-movements/pdf', [\App\Http\Controllers\DispatchReportController::class, 'exportStockMovementsPdf'])->name('stock-movements.pdf');
            Route::get('stock-movements/excel', [\App\Http\Controllers\DispatchReportController::class, 'exportStockMovementsExcel'])->name('stock-movements.excel');
        });

        // Custom Reports
        Volt::route('custom', 'reports.custom')->name('custom');

        // Data Exports
        Volt::route('exports', 'reports.exports')->name('exports');
    });

    // Import/Export Routes
    Route::prefix('imports')->name('imports.')->group(function () {
        Volt::route('/', 'imports.index')->name('index');
    });

    // DTE Import Routes
    Route::prefix('dte-imports')->name('dte-imports.')->group(function () {
        Volt::route('/', 'dte-imports.index')->name('index');
        Volt::route('{dteImport:slug}', 'dte-imports.review')->name('review');
    });

    // Document Management Routes
    Route::prefix('documents')->name('documents.')->group(function () {
        Volt::route('/', 'documents.index')->name('index');
        Volt::route('/upload', 'documents.upload')->name('upload');
        Route::get('{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])->name('download');
        Route::get('{document}/view', [\App\Http\Controllers\DocumentController::class, 'view'])->name('view');
        Route::delete('{document}', [\App\Http\Controllers\DocumentController::class, 'destroy'])->name('destroy');
        Route::post('{document}/approve', [\App\Http\Controllers\DocumentController::class, 'approve'])->name('approve');
        Route::post('{document}/create-version', [\App\Http\Controllers\DocumentController::class, 'createVersion'])->name('create-version');
    });

    // User Management Routes
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Volt::route('/', 'admin.users.index')->name('index');
        Volt::route('create', 'admin.users.create')->name('create');
        Volt::route('{user}/edit', 'admin.users.edit')->name('edit');
        Volt::route('{user}/profile', 'admin.users.profile')->name('profile');
    });

    // Role Management Routes
    Route::prefix('admin/roles')->name('admin.roles.')->group(function () {
        Volt::route('/', 'admin.roles.index')->name('index');
        Volt::route('create', 'admin.roles.create')->name('create');
        Volt::route('{role}/edit', 'admin.roles.edit')->name('edit');
    });

    // Permission Management Routes
    Route::prefix('admin/permissions')->name('admin.permissions.')->group(function () {
        Volt::route('/', 'admin.permissions.index')->name('index');
        Volt::route('create', 'admin.permissions.create')->name('create');
        Volt::route('{permission}/edit', 'admin.permissions.edit')->name('edit');
    });

    // Activity Logs Routes
    Route::prefix('admin/activity-logs')->name('admin.activity-logs.')->group(function () {
        Volt::route('/', 'admin.activity-logs.index')->name('index');
    });

    // Product Categories Management Routes
    Route::prefix('admin/categories')->name('admin.categories.')->group(function () {
        Volt::route('/', 'admin.categories.index')->name('index');
        Volt::route('create', 'admin.categories.create')->name('create');
        Volt::route('{category:slug}/edit', 'admin.categories.edit')->name('edit');
    });

    // Units of Measure Management Routes
    Route::prefix('admin/units')->name('admin.units.')->group(function () {
        Volt::route('/', 'admin.units.index')->name('index');
        Volt::route('create', 'admin.units.create')->name('create');
        Volt::route('{unit:slug}/edit', 'admin.units.edit')->name('edit');
    });

    // Areas Management Routes
    Route::prefix('admin/areas')->name('admin.areas.')->group(function () {
        Volt::route('/', 'admin.areas.index')->name('index');
        Volt::route('create', 'admin.areas.create')->name('create');
        Volt::route('{area:slug}/edit', 'admin.areas.edit')->name('edit');
    });

    // Company User Management Routes
    Route::prefix('admin/company-users')->name('admin.company-users.')->group(function () {
        Volt::route('/', 'admin.company-users.index')->name('index');
        Volt::route('{company}/users', 'admin.company-users.company')->name('company');
    });

    // Documentation Routes
    Volt::route('help', 'help.index')->name('help.index');

    // Settings Routes
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/general', 'settings.general')->name('settings.general');
});

require __DIR__.'/auth.php';
