<?php

namespace App\Http\Controllers;

use App\Exports\DispatchesMonthlyExport;
use App\Exports\DispatchesSummaryByLineExport;
use App\Exports\DispatchesSummaryByLineQuantityExport;
use App\Exports\StockMovementsExport;
use App\Models\DispatchDetail;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DispatchReportController extends Controller
{
    /**
     * Export monthly dispatches report as PDF.
     */
    public function exportMonthlyPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $warehouseId = $request->get('bodega');

        $data = $this->getMonthlyReportData($companyId, $startDate, $endDate, $warehouseId);

        $pdf = Pdf::loadView('reports.dispatches-monthly-pdf', [
            'groupedByWarehouse' => $data['groupedByWarehouse'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'salidas-mensuales-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export monthly dispatches report as Excel.
     */
    public function exportMonthlyExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $warehouseId = $request->get('bodega');

        $filename = 'salidas-mensuales-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new DispatchesMonthlyExport(
                $companyId,
                $startDate,
                $endDate,
                $warehouseId ? (int) $warehouseId : null
            ),
            $filename
        );
    }

    /**
     * Export summary by line report as PDF.
     */
    public function exportSummaryByLinePdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getSummaryByLineData($companyId, $startDate, $endDate);

        $pdf = Pdf::loadView('reports.dispatches-summary-by-line-pdf', [
            'groupedByParent' => $data['groupedByParent'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = 'resumen-salidas-por-linea-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export summary by line report as Excel.
     */
    public function exportSummaryByLineExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $filename = 'resumen-salidas-por-linea-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new DispatchesSummaryByLineExport($companyId, $startDate, $endDate),
            $filename
        );
    }

    /**
     * Export summary by line (quantity) report as PDF.
     */
    public function exportSummaryByLineQuantityPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getSummaryByLineQuantityData($companyId, $startDate, $endDate);

        $pdf = Pdf::loadView('reports.dispatches-summary-by-line-quantity-pdf', [
            'groupedByParent' => $data['groupedByParent'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = 'resumen-salidas-por-linea-cantidad-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export summary by line (quantity) report as Excel.
     */
    public function exportSummaryByLineQuantityExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $filename = 'resumen-salidas-por-linea-cantidad-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new DispatchesSummaryByLineQuantityExport($companyId, $startDate, $endDate),
            $filename
        );
    }

    /**
     * Get effective company ID based on user permissions.
     */
    protected function getEffectiveCompanyId(Request $request): ?int
    {
        if (auth()->user()->isSuperAdmin()) {
            return $request->get('empresa') ? (int) $request->get('empresa') : null;
        }

        return auth()->user()->company_id;
    }

    /**
     * Get monthly report data.
     */
    protected function getMonthlyReportData(int $companyId, string $startDate, string $endDate, ?string $warehouseId = null): array
    {
        $query = DispatchDetail::query()
            ->select([
                'dispatch_details.*',
                'dispatches.document_date',
                'dispatches.physical_document_number',
                'dispatches.warehouse_id',
                'dispatches.area_id',
                'products.name as product_name',
                'products.sku',
                'products.category_id',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'warehouses.name as warehouse_name',
                'areas.name as area_name',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.name as parent_category_name',
                'parent_categories.legacy_code as parent_category_code',
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'dispatch_details.unit_of_measure_id', '=', 'units_of_measure.id')
            ->join('warehouses', 'dispatches.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('areas', 'dispatches.area_id', '=', 'areas.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $companyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado'])
            ->where('dispatches.document_date', '>=', $startDate)
            ->where('dispatches.document_date', '<=', $endDate);

        if ($warehouseId) {
            $query->where('dispatches.warehouse_id', $warehouseId);
        }

        $reportData = $query
            ->orderBy('warehouses.name')
            ->orderBy('dispatches.document_date')
            ->get();

        $groupedByWarehouse = $reportData->groupBy('warehouse_name')->map(function ($items, $warehouseName) {
            return (object) [
                'warehouse_name' => $warehouseName ?: 'Sin Bodega',
                'items' => $items,
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum('total'),
            ];
        });

        $totals = [
            'total_items' => $reportData->count(),
            'total_quantity' => $reportData->sum('quantity'),
            'total_value' => $reportData->sum('total'),
            'total_warehouses' => $reportData->pluck('warehouse_id')->unique()->count(),
        ];

        return [
            'groupedByWarehouse' => $groupedByWarehouse,
            'totals' => $totals,
        ];
    }

    /**
     * Get summary by line data.
     */
    protected function getSummaryByLineData(int $companyId, string $startDate, string $endDate): array
    {
        $query = DispatchDetail::query()
            ->select([
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
                DB::raw('SUM(dispatch_details.total) as total_amount'),
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $companyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado'])
            ->where('dispatches.document_date', '>=', $startDate)
            ->where('dispatches.document_date', '<=', $endDate)
            ->groupBy([
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'product_categories.parent_id',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->orderBy('parent_categories.legacy_code')
            ->orderBy('product_categories.legacy_code')
            ->get();

        $groupedByParent = $query->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'lines' => $items,
                'subtotal' => $items->sum('total_amount'),
            ];
        });

        $totals = [
            'total_categories' => $query->pluck('parent_id')->unique()->count(),
            'total_lines' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
        ];

        return [
            'groupedByParent' => $groupedByParent,
            'totals' => $totals,
        ];
    }

    /**
     * Get summary by line (quantity) data.
     */
    protected function getSummaryByLineQuantityData(int $companyId, string $startDate, string $endDate): array
    {
        $query = DispatchDetail::query()
            ->select([
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
                DB::raw('SUM(dispatch_details.quantity) as total_quantity'),
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $companyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado'])
            ->where('dispatches.document_date', '>=', $startDate)
            ->where('dispatches.document_date', '<=', $endDate)
            ->groupBy([
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'product_categories.parent_id',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->orderBy('parent_categories.legacy_code')
            ->orderBy('product_categories.legacy_code')
            ->get();

        $groupedByParent = $query->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'lines' => $items,
                'subtotal' => $items->sum('total_quantity'),
            ];
        });

        $totals = [
            'total_categories' => $query->pluck('parent_id')->unique()->count(),
            'total_lines' => $query->count(),
            'total_quantity' => $query->sum('total_quantity'),
        ];

        return [
            'groupedByParent' => $groupedByParent,
            'totals' => $totals,
        ];
    }

    /**
     * Export stock movements report as PDF.
     */
    public function exportStockMovementsPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $warehouseId = $request->get('bodega');
        if (! $warehouseId) {
            return back()->with('error', 'Debe seleccionar una bodega');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $warehouse = Warehouse::find($warehouseId);
        $data = $this->getStockMovementsData($companyId, (int) $warehouseId, $startDate, $endDate);

        $pdf = Pdf::loadView('reports.dispatches-stock-movements-pdf', [
            'groupedByCategory' => $data['groupedByCategory'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouseName' => $warehouse?->name ?? 'N/A',
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'existencias-movimientos-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export stock movements report as Excel.
     */
    public function exportStockMovementsExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $warehouseId = $request->get('bodega');
        if (! $warehouseId) {
            return back()->with('error', 'Debe seleccionar una bodega');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));

        $filename = 'existencias-movimientos-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new StockMovementsExport($companyId, (int) $warehouseId, $startDate, $endDate),
            $filename
        );
    }

    /**
     * Get stock movements data.
     */
    protected function getStockMovementsData(int $companyId, int $warehouseId, string $startDate, string $endDate): array
    {
        // Get products with movements in the period
        $query = DB::table('inventory_movements as im')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
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
            ->where('im.warehouse_id', $warehouseId)
            ->whereNotNull('im.balance_quantity')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('im.movement_date', [$startDate, $endDate])
                    ->orWhere('im.movement_date', '<', $startDate);
            })
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
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

        // For each product, calculate initial stock, entries, exits
        $results = $query->map(function ($product) use ($companyId, $warehouseId, $startDate, $endDate) {
            // Get initial stock (balance just before start_date)
            $initialMovement = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->where('movement_date', '<', $startDate)
                ->whereNotNull('balance_quantity')
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $initialStock = $initialMovement?->balance_quantity ?? 0;

            // Get entries during period
            $entries = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_in');

            // Get exits during period
            $exits = InventoryMovement::where('company_id', $companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $warehouseId)
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_out');

            // Final stock
            $finalStock = (float) $initialStock + (float) $entries - (float) $exits;

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
                'final_stock' => $finalStock,
            ];
        });

        $groupedByCategory = $results->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'items' => $items,
                'subtotals' => (object) [
                    'initial_stock' => $items->sum('initial_stock'),
                    'entries' => $items->sum('entries'),
                    'exits' => $items->sum('exits'),
                    'final_stock' => $items->sum('final_stock'),
                ],
            ];
        });

        $totals = [
            'total_products' => $results->count(),
            'total_categories' => $results->pluck('parent_id')->unique()->count(),
            'initial_stock' => $results->sum('initial_stock'),
            'entries' => $results->sum('entries'),
            'exits' => $results->sum('exits'),
            'final_stock' => $results->sum('final_stock'),
        ];

        return [
            'groupedByCategory' => $groupedByCategory,
            'totals' => $totals,
        ];
    }
}
