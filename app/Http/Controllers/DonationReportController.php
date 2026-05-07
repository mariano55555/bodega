<?php

namespace App\Http\Controllers;

use App\Exports\DonationsConsolidatedExport;
use App\Models\Donation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DonationReportController extends Controller
{
    public function exportConsolidatedPdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfYear()->format('Y-m-d'));
        $donorId = $request->get('donante');
        $warehouseId = $request->get('bodega');
        $categoryId = $request->get('categoria');

        $data = $this->getConsolidatedData($companyId, $startDate, $endDate, $donorId, $warehouseId, $categoryId);

        $pdf = Pdf::loadView('reports.donations-consolidated-pdf', [
            'donorData' => $data['donorData'],
            'categoryData' => $data['categoryData'],
            'monthlyTrend' => $data['monthlyTrend'],
            'totals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'donaciones-consolidadas-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function exportConsolidatedExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $startDate = $request->get('inicio', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfYear()->format('Y-m-d'));
        $donorId = $request->get('donante');
        $warehouseId = $request->get('bodega');
        $categoryId = $request->get('categoria');

        $filename = 'donaciones-consolidadas-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new DonationsConsolidatedExport(
                $companyId,
                $startDate,
                $endDate,
                $donorId ? (int) $donorId : null,
                $warehouseId ? (int) $warehouseId : null,
                $categoryId ? (int) $categoryId : null,
            ),
            $filename
        );
    }

    protected function getEffectiveCompanyId(Request $request): ?int
    {
        if (auth()->user()->isSuperAdmin()) {
            return $request->get('empresa') ? (int) $request->get('empresa') : null;
        }

        return auth()->user()->company_id;
    }

    public static function getConsolidatedData(int $companyId, string $startDate, string $endDate, ?string $donorId = null, ?string $warehouseId = null, ?string $categoryId = null): array
    {
        $query = Donation::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['aprobado', 'recibido'])
            ->where('document_date', '>=', $startDate)
            ->where('document_date', '<=', $endDate)
            ->with(['donor:id,name,tax_id', 'warehouse:id,name', 'details.product.category']);

        if ($donorId) {
            $query->where('donor_id', $donorId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $donations = $query->latest('document_date')->get();

        // Group by donor
        $donorData = [];
        foreach ($donations as $donation) {
            $donorKey = $donation->donor_id ?? 'anonymous';
            $donorName = $donation->donor ? $donation->donor->name : $donation->donor_name;

            if (! isset($donorData[$donorKey])) {
                $donorData[$donorKey] = [
                    'donor' => $donation->donor,
                    'donor_name' => $donorName,
                    'donation_count' => 0,
                    'total_value' => 0,
                    'products' => [],
                    'categories' => [],
                ];
            }

            $donorData[$donorKey]['donation_count']++;
            $donorData[$donorKey]['total_value'] += $donation->estimated_value ?? 0;

            foreach ($donation->details as $detail) {
                if ($categoryId && $detail->product->category_id != $categoryId) {
                    continue;
                }

                $productKey = $detail->product_id;
                if (! isset($donorData[$donorKey]['products'][$productKey])) {
                    $donorData[$donorKey]['products'][$productKey] = [
                        'product' => $detail->product,
                        'quantity' => 0,
                        'value' => 0,
                    ];
                }

                $donorData[$donorKey]['products'][$productKey]['quantity'] += $detail->quantity;
                $donorData[$donorKey]['products'][$productKey]['value'] += $detail->estimated_total_value ?? 0;

                if ($detail->product->category) {
                    $donorData[$donorKey]['categories'][] = $detail->product->category->name;
                }
            }
        }

        $donorData = collect($donorData)->map(function ($item) {
            $item['products'] = collect($item['products']);
            $item['categories'] = collect($item['categories']);

            return $item;
        })->sortByDesc('total_value');

        // Group by category
        $categoryData = [];
        foreach ($donations as $donation) {
            foreach ($donation->details as $detail) {
                if ($categoryId && $detail->product->category_id != $categoryId) {
                    continue;
                }

                $categoryKey = $detail->product->category_id ?? 'uncategorized';
                $categoryName = $detail->product->category->name ?? 'Sin categoría';

                if (! isset($categoryData[$categoryKey])) {
                    $categoryData[$categoryKey] = [
                        'name' => $categoryName,
                        'total_value' => 0,
                        'total_quantity' => 0,
                    ];
                }

                $categoryData[$categoryKey]['total_value'] += $detail->estimated_total_value ?? 0;
                $categoryData[$categoryKey]['total_quantity'] += $detail->quantity;
            }
        }

        $categoryData = collect($categoryData)->sortByDesc('total_value');

        // Monthly trend (last 12 months)
        $monthlyTrend = Donation::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['aprobado', 'recibido'])
            ->where('document_date', '>=', \Carbon\Carbon::parse($startDate)->subMonths(12)->startOfMonth())
            ->where('document_date', '<=', $endDate)
            ->select([
                DB::raw('DATE_FORMAT(document_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as donation_count'),
                DB::raw('SUM(estimated_value) as total_value'),
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $totals = [
            'total_donations' => $donations->count(),
            'total_donors' => $donorData->count(),
            'total_value' => $donorData->sum('total_value'),
            'average_donation' => $donations->count() > 0 ? $donorData->sum('total_value') / $donations->count() : 0,
        ];

        return [
            'donorData' => $donorData,
            'categoryData' => $categoryData,
            'monthlyTrend' => $monthlyTrend,
            'totals' => $totals,
        ];
    }
}
