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
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'type' => 'nullable|in:individual,fractional,global',
            'empresa' => 'nullable|exists:companies,id',
        ]);

        // Use empresa parameter for super admin, otherwise use user's company_id
        $companyId = auth()->user()->isSuperAdmin()
            ? ($validated['empresa'] ?? null)
            : auth()->user()->company_id;

        $filename = sprintf(
            'inventario_consolidado_%s.xlsx',
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new InventoryConsolidatedExport(
                $validated['warehouse_id'] ?? null,
                $validated['category_id'] ?? null,
                $validated['type'] ?? null,
                $companyId
            ),
            $filename
        );
    }

    /**
     * Export consolidated inventory to PDF
     */
    public function exportConsolidatedPdf(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'type' => 'nullable|in:individual,fractional,global',
            'empresa' => 'nullable|exists:companies,id',
        ]);

        // Use empresa parameter for super admin, otherwise use user's company_id
        $companyId = auth()->user()->isSuperAdmin()
            ? ($validated['empresa'] ?? null)
            : auth()->user()->company_id;

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $query = Inventory::query()
            ->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse', 'storageLocation']);

        if (isset($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        if (isset($validated['type']) && $validated['type'] !== 'global') {
            $query->whereHas('warehouse', function ($q) use ($validated) {
                if ($validated['type'] === 'fractional') {
                    $q->where('warehouse_type', 'fractional');
                } elseif ($validated['type'] === 'individual') {
                    $q->where('warehouse_type', 'general');
                }
            });
        }

        if (isset($validated['category_id'])) {
            $query->whereHas('product', function ($q) use ($validated) {
                $q->where('category_id', $validated['category_id']);
            });
        }

        $inventories = $query->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();

        $pdf = Pdf::loadView('reports.inventory-consolidated-pdf', [
            'inventories' => $inventories,
            'filters' => $validated,
        ]);

        $filename = sprintf(
            'inventario_consolidado_%s.pdf',
            now()->format('Y-m-d_His')
        );

        return $pdf->download($filename);
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
