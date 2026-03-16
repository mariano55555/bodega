<?php

namespace App\Http\Controllers;

use App\Exports\InventoryConsolidatedExport;
use App\Exports\InventoryProductsExport;
use App\Exports\InventoryRotationExport;
use App\Exports\InventoryValueExport;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    /**
     * Display consolidated inventory report index
     */
    public function index()
    {
        return view('livewire.reports.inventory.index');
    }

    /**
     * Get consolidated inventory by warehouse - renders the Livewire Volt component
     */
    public function consolidated(Request $request)
    {
        return view('pages.reports.inventory.consolidated');
    }

    /**
     * Export consolidated inventory to Excel
     */
    public function exportConsolidated(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $warehouseParam = $request->get('bodega');
        if (! $warehouseParam) {
            return back()->with('error', 'Debe seleccionar una bodega');
        }

        $isAllWarehouses = $warehouseParam === 'all';
        $warehouseId = $isAllWarehouses ? null : (int) $warehouseParam;
        $warehouseName = $isAllWarehouses ? 'TODAS LAS BODEGAS' : (Warehouse::find($warehouseId)?->name ?? 'N/A');

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $filename = 'inventario-consolidado-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new InventoryConsolidatedExport(
                (int) $companyId,
                $warehouseId,
                $startDate,
                $endDate,
                $warehouseName
            ),
            $filename
        );
    }

    /**
     * Export consolidated inventory to PDF
     */
    public function exportConsolidatedPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $warehouseParam = $request->get('bodega');
        if (! $warehouseParam) {
            return back()->with('error', 'Debe seleccionar una bodega');
        }

        $isAllWarehouses = $warehouseParam === 'all';
        $warehouseId = $isAllWarehouses ? null : (int) $warehouseParam;
        $warehouseName = $isAllWarehouses ? 'TODAS LAS BODEGAS' : (Warehouse::find($warehouseId)?->name ?? 'N/A');

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getConsolidatedData((int) $companyId, $warehouseId, $startDate, $endDate);

        $pdf = Pdf::loadView('reports.inventory-consolidated-pdf', [
            'groupedByCategory' => $data['groupedByCategory'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouseName' => $warehouseName,
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'inventario-consolidado-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get consolidated inventory data grouped by budget line.
     * Pass warehouseId = null for all warehouses.
     */
    public function getConsolidatedData(int $companyId, ?int $warehouseId, string $startDate, string $endDate): array
    {
        $isAllWarehouses = is_null($warehouseId);

        $query = DB::table('inventory_movements as im')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.cost as product_cost',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
            ])
            ->join('products', 'im.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'products.unit_of_measure_id', '=', 'units_of_measure.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('im.company_id', $companyId)
            ->when(! $isAllWarehouses, fn ($q) => $q->where('im.warehouse_id', $warehouseId))
            ->whereNotNull('im.balance_quantity')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('im.movement_date', [$startDate, $endDate])
                    ->orWhere('im.movement_date', '<', $startDate);
            })
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
                'products.cost',
                'units_of_measure.abbreviation',
                'units_of_measure.name',
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->get();

        $warehouseIds = $isAllWarehouses
            ? Warehouse::where('company_id', $companyId)->where('is_active', true)->pluck('id')
            : null;

        $results = $query->map(function ($product) use ($companyId, $warehouseId, $isAllWarehouses, $warehouseIds, $startDate, $endDate) {
            if ($isAllWarehouses) {
                // Sum initial stock across all warehouses
                $initialStock = 0;
                foreach ($warehouseIds as $whId) {
                    $movement = InventoryMovement::where('company_id', $companyId)
                        ->where('product_id', $product->product_id)
                        ->where('warehouse_id', $whId)
                        ->where('movement_date', '<', $startDate)
                        ->whereNotNull('balance_quantity')
                        ->orderByDesc('movement_date')
                        ->orderByDesc('id')
                        ->first();
                    $initialStock += (float) ($movement?->balance_quantity ?? 0);
                }
            } else {
                $initialMovement = InventoryMovement::where('company_id', $companyId)
                    ->where('product_id', $product->product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->where('movement_date', '<', $startDate)
                    ->whereNotNull('balance_quantity')
                    ->orderByDesc('movement_date')
                    ->orderByDesc('id')
                    ->first();
                $initialStock = $initialMovement?->balance_quantity ?? 0;
            }

            $entries = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->when(! $isAllWarehouses, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_in');

            $exits = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->when(! $isAllWarehouses, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_out');

            $currentStock = (float) $initialStock + (float) $entries - (float) $exits;

            // Get unit cost from the most recent movement
            $lastCostMovement = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->when(! $isAllWarehouses, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('movement_date', '<=', $endDate)
                ->whereNotNull('balance_quantity')
                ->where('unit_cost', '>', 0)
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $unitCost = (float) ($lastCostMovement?->unit_cost ?? $product->product_cost ?? 0);
            $totalCost = $currentStock * $unitCost;

            return (object) [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'unit' => $product->unit_abbreviation ?? $product->unit_name ?? '-',
                'category_id' => $product->category_id,
                'category_name' => $product->category_name,
                'category_code' => $product->category_code,
                'parent_id' => $product->parent_id,
                'parent_name' => $product->parent_name,
                'parent_code' => $product->parent_code,
                'initial_stock' => (float) $initialStock,
                'entries' => (float) $entries,
                'exits' => (float) $exits,
                'current_stock' => $currentStock,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ];
        })->filter(fn ($item) => abs($item->current_stock) >= 0.001);

        // Group by parent category, then by subcategory within each parent
        $groupedByCategory = $results->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            // Group items by subcategory, sorted by category_code
            $subcategories = $items->groupBy('category_name')->map(function ($subItems, $categoryName) {
                $first = $subItems->first();

                return (object) [
                    'category_name' => $categoryName ?: 'Sin Subcategoría',
                    'category_code' => $first->category_code ?? '',
                    'items' => $subItems->sortBy('sku')->values(),
                    'subtotals' => (object) [
                        'initial_stock' => $subItems->sum('initial_stock'),
                        'entries' => $subItems->sum('entries'),
                        'exits' => $subItems->sum('exits'),
                        'current_stock' => $subItems->sum('current_stock'),
                        'total_cost' => $subItems->sum('total_cost'),
                    ],
                ];
            })->sortBy('category_code')->values();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'subcategories' => $subcategories,
                'parent_subtotals' => (object) [
                    'initial_stock' => $items->sum('initial_stock'),
                    'entries' => $items->sum('entries'),
                    'exits' => $items->sum('exits'),
                    'current_stock' => $items->sum('current_stock'),
                    'total_cost' => $items->sum('total_cost'),
                ],
            ];
        })->sortBy(fn ($group) => $group->parent_code)->values();

        $totals = [
            'total_products' => $results->count(),
            'total_categories' => $results->pluck('parent_id')->unique()->count(),
            'initial_stock' => $results->sum('initial_stock'),
            'entries' => $results->sum('entries'),
            'exits' => $results->sum('exits'),
            'current_stock' => $results->sum('current_stock'),
            'total_cost' => $results->sum('total_cost'),
        ];

        return [
            'groupedByCategory' => $groupedByCategory,
            'totals' => $totals,
        ];
    }

    /**
     * Display inventory value report
     */
    public function value(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $companyId = auth()->user()->company_id;

        $query = Inventory::query()
            ->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse']);

        if (isset($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        $inventories = $query->get();

        $totalValue = $inventories->sum(function ($inventory) {
            return $inventory->quantity * ($inventory->product->cost ?? 0);
        });

        $byWarehouse = $inventories->groupBy('warehouse_id')->map(function ($items) {
            return [
                'warehouse' => $items->first()->warehouse,
                'total_value' => $items->sum(function ($inventory) {
                    return $inventory->quantity * ($inventory->product->cost ?? 0);
                }),
                'total_quantity' => $items->sum('quantity'),
            ];
        });

        return view('livewire.reports.inventory.value', [
            'inventories' => $inventories,
            'totalValue' => $totalValue,
            'byWarehouse' => $byWarehouse,
            'filters' => $validated,
        ]);
    }

    /**
     * Export inventory value report to Excel
     */
    public function exportValue(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'empresa' => 'nullable|exists:companies,id',
        ]);

        // Use empresa parameter for super admin, otherwise use user's company_id
        $companyId = auth()->user()->isSuperAdmin()
            ? ($validated['empresa'] ?? null)
            : auth()->user()->company_id;

        $filename = sprintf(
            'valor_inventario_%s.xlsx',
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new InventoryValueExport(
                $validated['warehouse_id'] ?? null,
                $companyId
            ),
            $filename
        );
    }

    /**
     * Display inventory rotation report
     */
    public function rotation(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $companyId = auth()->user()->company_id;
        $dateFrom = $validated['date_from'] ?? now()->subMonths(3)->startOfMonth();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth();

        // Calculate rotation per product
        $products = Product::where('company_id', $companyId)
            ->with(['inventories' => function ($q) use ($validated) {
                if (isset($validated['warehouse_id'])) {
                    $q->where('warehouse_id', $validated['warehouse_id']);
                }
            }])
            ->get()
            ->map(function ($product) use ($dateFrom, $dateTo, $validated, $companyId) {
                $avgInventory = $product->inventories->avg('quantity') ?? 0;

                $totalMovements = InventoryMovement::where('company_id', $companyId)
                    ->where('product_id', $product->id)
                    ->when(isset($validated['warehouse_id']), function ($q) use ($validated) {
                        $q->where('warehouse_id', $validated['warehouse_id']);
                    })
                    ->whereBetween('movement_date', [$dateFrom, $dateTo])
                    ->where('quantity_out', '>', 0)
                    ->sum('quantity_out');

                $rotationRate = $avgInventory > 0 ? ($totalMovements / $avgInventory) : 0;

                return [
                    'product' => $product,
                    'avg_inventory' => $avgInventory,
                    'total_out' => $totalMovements,
                    'rotation_rate' => $rotationRate,
                ];
            })
            ->sortByDesc('rotation_rate');

        return view('livewire.reports.inventory.rotation', [
            'products' => $products,
            'filters' => $validated,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Export inventory rotation report to Excel
     */
    public function exportRotation(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'empresa' => 'nullable|exists:companies,id',
        ]);

        // Use empresa parameter for super admin, otherwise use user's company_id
        $companyId = auth()->user()->isSuperAdmin()
            ? ($validated['empresa'] ?? null)
            : auth()->user()->company_id;

        $filename = sprintf(
            'rotacion_inventario_%s.xlsx',
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new InventoryRotationExport(
                $validated['warehouse_id'] ?? null,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null,
                $companyId
            ),
            $filename
        );
    }

    /**
     * Get the effective company ID based on user role.
     */
    protected function getEffectiveCompanyId(Request $request): ?int
    {
        if (auth()->user()->isSuperAdmin()) {
            return $request->get('empresa') ? (int) $request->get('empresa') : null;
        }

        return auth()->user()->company_id;
    }

    /**
     * Export inventory products to Excel
     */
    public function exportProducts(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'search' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'stock_level' => 'nullable|in:available,reserved,zero',
            'show_low_stock' => 'nullable|boolean',
            'show_expiring' => 'nullable|boolean',
        ]);

        // If user is not super admin, use their company_id
        if (! auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = auth()->user()->company_id;
        }

        $filename = sprintf(
            'inventario_productos_%s.xlsx',
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new InventoryProductsExport(
                $validated['company_id'] ?? null,
                $validated['search'] ?? null,
                $validated['warehouse_id'] ?? null,
                $validated['category_id'] ?? null,
                $validated['stock_level'] ?? null,
                $validated['show_low_stock'] ?? false,
                $validated['show_expiring'] ?? false
            ),
            $filename
        );
    }
}
