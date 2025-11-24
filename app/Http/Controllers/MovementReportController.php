<?php

namespace App\Http\Controllers;

use App\Exports\ConsumptionByLineExport;
use App\Exports\MovementSummaryExport;
use App\Exports\TransferReportExport;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MovementReportController extends Controller
{
    /**
     * Display monthly movement summary report
     */
    public function monthly(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'year' => 'nullable|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $companyId = auth()->user()->company_id;
        $year = $validated['year'] ?? now()->year;
        $month = $validated['month'] ?? now()->month;

        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->with(['product', 'warehouse', 'movementReason']);

        if (isset($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $summary = [
            'total_in' => $movements->sum('quantity_in'),
            'total_out' => $movements->sum('quantity_out'),
            'net_movement' => $movements->sum('quantity_in') - $movements->sum('quantity_out'),
            'by_reason' => $movements->groupBy('movementReason.name')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'quantity_in' => $items->sum('quantity_in'),
                    'quantity_out' => $items->sum('quantity_out'),
                ];
            }),
        ];

        return view('livewire.reports.movements.monthly', [
            'movements' => $movements,
            'summary' => $summary,
            'filters' => $validated,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Export monthly movements to Excel
     */
    public function exportMonthly(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'year' => 'nullable|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'empresa' => 'nullable|exists:companies,id',
        ]);

        // Super admin can select company, regular users use their own company
        $companyId = auth()->user()->isSuperAdmin()
            ? ($validated['empresa'] ?? null)
            : auth()->user()->company_id;

        $filename = sprintf(
            'movimientos_mensuales_%s-%s_%s.xlsx',
            $validated['year'] ?? now()->year,
            str_pad($validated['month'] ?? now()->month, 2, '0', STR_PAD_LEFT),
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new MovementSummaryExport(
                $validated['warehouse_id'] ?? null,
                $validated['year'] ?? null,
                $validated['month'] ?? null,
                $companyId
            ),
            $filename
        );
    }

    /**
     * Display income movements report
     */
    public function income(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $companyId = auth()->user()->company_id;
        $dateFrom = $validated['date_from'] ?? now()->startOfMonth();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth();

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->where('quantity_in', '>', 0)
            ->with(['product', 'warehouse', 'movementReason']);

        if (isset($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $summary = [
            'total_in' => $movements->sum('quantity_in'),
            'by_warehouse' => $movements->groupBy('warehouse_id')->map(function ($items) {
                return [
                    'warehouse' => $items->first()->warehouse,
                    'quantity' => $items->sum('quantity_in'),
                ];
            }),
        ];

        return view('livewire.reports.movements.income', [
            'movements' => $movements,
            'summary' => $summary,
            'filters' => $validated,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Display consumption by product line report
     */
    public function consumptionByLine(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $companyId = auth()->user()->company_id;
        $dateFrom = $validated['date_from'] ?? now()->startOfMonth();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth();

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->where('quantity_out', '>', 0)
            ->with(['product.category', 'warehouse']);

        if (isset($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        if (isset($validated['category_id'])) {
            $query->whereHas('product', function ($q) use ($validated) {
                $q->where('category_id', $validated['category_id']);
            });
        }

        $movements = $query->get();

        $byCategory = $movements->groupBy('product.category.name')->map(function ($items) {
            return [
                'category' => $items->first()->product->category,
                'total_quantity' => $items->sum('quantity_out'),
                'total_value' => $items->sum(function ($item) {
                    return $item->quantity_out * ($item->product->cost ?? 0);
                }),
                'products_count' => $items->pluck('product_id')->unique()->count(),
            ];
        })->sortByDesc('total_quantity');

        return view('livewire.reports.movements.consumption-by-line', [
            'movements' => $movements,
            'byCategory' => $byCategory,
            'filters' => $validated,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Export consumption by line to Excel
     */
    public function exportConsumptionByLine(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $filename = sprintf(
            'consumo_por_linea_%s.xlsx',
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new ConsumptionByLineExport(
                $validated['warehouse_id'] ?? null,
                $validated['category_id'] ?? null,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            ),
            $filename
        );
    }

    /**
     * Display transfer report between warehouses
     */
    public function transfers(Request $request)
    {
        $validated = $request->validate([
            'warehouse_from_id' => 'nullable|exists:warehouses,id',
            'warehouse_to_id' => 'nullable|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:draft,pending,approved,in_transit,received,completed,cancelled',
        ]);

        $companyId = auth()->user()->company_id;
        $dateFrom = $validated['date_from'] ?? now()->startOfMonth();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth();

        $query = InventoryTransfer::query()
            ->whereBetween('requested_at', [$dateFrom, $dateTo])
            ->with(['fromWarehouse', 'toWarehouse', 'requestedBy', 'details.product']);

        // Filter by company through warehouse relationship (if not super admin)
        if ($companyId) {
            $query->whereHas('fromWarehouse', fn ($q) => $q->where('company_id', $companyId));
        }

        if (isset($validated['warehouse_from_id'])) {
            $query->where('from_warehouse_id', $validated['warehouse_from_id']);
        }

        if (isset($validated['warehouse_to_id'])) {
            $query->where('to_warehouse_id', $validated['warehouse_to_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $transfers = $query->orderBy('requested_at', 'desc')->get();

        $summary = [
            'total_transfers' => $transfers->count(),
            'by_status' => $transfers->groupBy('status')->map(fn ($items) => $items->count()),
            'total_items' => $transfers->sum(fn ($transfer) => $transfer->details->sum('quantity')),
        ];

        return view('livewire.reports.movements.transfers', [
            'transfers' => $transfers,
            'summary' => $summary,
            'filters' => $validated,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Export transfers to Excel
     */
    public function exportTransfers(Request $request)
    {
        $validated = $request->validate([
            'warehouse_from_id' => 'nullable|exists:warehouses,id',
            'warehouse_to_id' => 'nullable|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:draft,pending,approved,in_transit,received,completed,cancelled',
            'empresa' => 'nullable|exists:companies,id',
        ]);

        // Super admin can select company, regular users use their own company
        $companyId = auth()->user()->isSuperAdmin()
            ? ($validated['empresa'] ?? null)
            : auth()->user()->company_id;

        $filename = sprintf(
            'traslados_%s.xlsx',
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new TransferReportExport(
                $validated['warehouse_from_id'] ?? null,
                $validated['warehouse_to_id'] ?? null,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null,
                $validated['status'] ?? null,
                $companyId
            ),
            $filename
        );
    }
}
