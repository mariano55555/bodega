<?php

namespace App\Http\Controllers;

use App\Exports\KardexExport;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KardexController extends Controller
{
    public function index()
    {
        return view('livewire.reports.kardex');
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        // Check authorization - super admins can access all companies
        if (! auth()->user()->isSuperAdmin()) {
            if ($product->company_id !== auth()->user()->company_id ||
                $warehouse->company_id !== auth()->user()->company_id) {
                abort(403);
            }
        }

        // Ensure product and warehouse belong to the same company
        if ($product->company_id !== $warehouse->company_id) {
            abort(403, 'Product and warehouse must belong to the same company');
        }

        $query = InventoryMovement::query()
            ->where('company_id', $product->company_id)
            ->where('product_id', $validated['product_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->whereNotNull('balance_quantity')
            ->with(['product', 'warehouse', 'movementReason']);

        if (isset($validated['date_from'])) {
            $query->whereDate('movement_date', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('movement_date', '<=', $validated['date_to']);
        }

        $movements = $query->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('reports.kardex-pdf', [
            'product' => $product,
            'warehouse' => $warehouse,
            'movements' => $movements,
            'dateFrom' => $validated['date_from'] ?? null,
            'dateTo' => $validated['date_to'] ?? null,
        ]);

        $filename = sprintf(
            'kardex_%s_%s_%s.pdf',
            $product->sku,
            $warehouse->slug,
            now()->format('Y-m-d_His')
        );

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        // Check authorization - super admins can access all companies
        if (! auth()->user()->isSuperAdmin()) {
            if ($product->company_id !== auth()->user()->company_id ||
                $warehouse->company_id !== auth()->user()->company_id) {
                abort(403);
            }
        }

        // Ensure product and warehouse belong to the same company
        if ($product->company_id !== $warehouse->company_id) {
            abort(403, 'Product and warehouse must belong to the same company');
        }

        $filename = sprintf(
            'kardex_%s_%s_%s.xlsx',
            $product->sku,
            $warehouse->slug,
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new KardexExport(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null,
                $product->company_id
            ),
            $filename
        );
    }
}
