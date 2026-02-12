<?php

namespace App\Http\Controllers;

use App\Exports\PurchasesBySupplierExport;
use App\Exports\PurchasesDetailedExport;
use App\Exports\PurchasesSummaryByLineExport;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseReportController extends Controller
{
    /**
     * Export detailed purchases report as PDF.
     */
    public function exportDetailedPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('linea');
        $supplierId = $request->get('proveedor');

        $data = $this->getDetailedReportData($companyId, $startDate, $endDate, $categoryId, $supplierId);

        $pdf = Pdf::loadView('reports.purchases-detailed-pdf', [
            'groupedData' => $data['groupedData'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'compras-detalladas-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export detailed purchases report as Excel.
     */
    public function exportDetailedExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->get('linea');
        $supplierId = $request->get('proveedor');

        $filename = 'compras-detalladas-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new PurchasesDetailedExport(
                $companyId,
                $startDate,
                $endDate,
                $categoryId ? (int) $categoryId : null,
                $supplierId ? (int) $supplierId : null
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

        $pdf = Pdf::loadView('reports.purchases-summary-by-line-pdf', [
            'groupedByParent' => $data['groupedByParent'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = 'resumen-por-linea-'.now()->format('Y-m-d').'.pdf';

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

        $filename = 'resumen-por-linea-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new PurchasesSummaryByLineExport($companyId, $startDate, $endDate),
            $filename
        );
    }

    /**
     * Export purchases by supplier report as PDF.
     */
    public function exportBySupplierPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $supplierId = $request->get('proveedor');
        $acquisitionType = $request->get('tipo');

        $data = $this->getBySupplierData($companyId, $startDate, $endDate, $supplierId, $acquisitionType);

        $pdf = Pdf::loadView('reports.purchases-by-supplier-pdf', [
            'supplierData' => $data['supplierData'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = 'compras-por-proveedor-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export purchases by supplier report as Excel.
     */
    public function exportBySupplierExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $supplierId = $request->get('proveedor');
        $acquisitionType = $request->get('tipo');

        $filename = 'compras-por-proveedor-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new PurchasesBySupplierExport(
                $companyId,
                $startDate,
                $endDate,
                $supplierId ? (int) $supplierId : null,
                $acquisitionType
            ),
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
     * Get detailed report data.
     */
    protected function getDetailedReportData(int $companyId, string $startDate, string $endDate, ?string $categoryId = null, ?string $supplierId = null): array
    {
        $query = PurchaseDetail::query()
            ->select([
                'purchase_details.*',
                'purchases.document_date',
                'purchases.document_number',
                'purchases.supplier_id',
                'products.name as product_name',
                'products.category_id',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'suppliers.name as supplier_name',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.name as parent_category_name',
                'parent_categories.legacy_code as parent_category_code',
            ])
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_details.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'products.unit_of_measure_id', '=', 'units_of_measure.id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('purchases.company_id', $companyId)
            ->whereIn('purchases.status', ['aprobado', 'recibido'])
            ->where('purchases.document_date', '>=', $startDate)
            ->where('purchases.document_date', '<=', $endDate);

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        if ($supplierId) {
            $query->where('purchases.supplier_id', $supplierId);
        }

        $reportData = $query
            ->orderBy('category_name')
            ->orderBy('supplier_name')
            ->orderBy('purchases.document_date')
            ->get();

        $groupedData = $reportData->groupBy('category_name')->map(function ($items, $categoryName) {
            $firstItem = $items->first();

            return (object) [
                'category_name' => $categoryName ?: 'Sin Línea Presupuestaria',
                'category_code' => $firstItem->category_code ?? '',
                'parent_name' => $firstItem->parent_category_name ?? 'Sin Categoría',
                'parent_code' => $firstItem->parent_category_code ?? '',
                'items' => $items,
                'subtotal' => $items->sum('total'),
                'by_supplier' => $items->groupBy('supplier_name')->map(fn ($s) => $s->sum('total')),
            ];
        });

        $totals = [
            'total_items' => $reportData->count(),
            'total_quantity' => $reportData->sum('quantity'),
            'total_amount' => $reportData->sum('total'),
            'total_categories' => $reportData->pluck('category_id')->unique()->count(),
            'total_suppliers' => $reportData->pluck('supplier_id')->unique()->count(),
        ];

        return [
            'groupedData' => $groupedData,
            'totals' => $totals,
        ];
    }

    /**
     * Get summary by line data.
     */
    protected function getSummaryByLineData(int $companyId, string $startDate, string $endDate): array
    {
        $query = PurchaseDetail::query()
            ->select([
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
                DB::raw('SUM(purchase_details.total) as total_amount'),
            ])
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_details.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('purchases.company_id', $companyId)
            ->whereIn('purchases.status', ['aprobado', 'recibido'])
            ->where('purchases.document_date', '>=', $startDate)
            ->where('purchases.document_date', '<=', $endDate)
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
     * Get purchases by supplier data.
     */
    protected function getBySupplierData(int $companyId, string $startDate, string $endDate, ?string $supplierId = null, ?string $acquisitionType = null): array
    {
        $query = Purchase::query()
            ->select([
                'supplier_id',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(subtotal) as subtotal_amount'),
                DB::raw('SUM(tax_amount) as tax_amount'),
                DB::raw('SUM(discount_amount) as discount_amount'),
            ])
            ->where('company_id', $companyId)
            ->whereIn('status', ['aprobado', 'recibido'])
            ->where('document_date', '>=', $startDate)
            ->where('document_date', '<=', $endDate)
            ->with(['supplier:id,name,tax_id'])
            ->groupBy('supplier_id');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($acquisitionType) {
            $query->where('acquisition_type', $acquisitionType);
        }

        $supplierData = $query->orderByDesc('total_amount')->get();

        $totals = [
            'total_invoices' => $supplierData->sum('invoice_count'),
            'total_amount' => $supplierData->sum('total_amount'),
            'subtotal_amount' => $supplierData->sum('subtotal_amount'),
            'tax_amount' => $supplierData->sum('tax_amount'),
            'discount_amount' => $supplierData->sum('discount_amount'),
            'total_suppliers' => $supplierData->count(),
        ];

        return [
            'supplierData' => $supplierData,
            'totals' => $totals,
        ];
    }
}
