<?php

namespace App\Http\Controllers;

use App\Models\Dispatch;
use App\Models\InternalProduction;
use App\Models\InventoryTransfer;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DigitizedDataReportController extends Controller
{
    protected array $reportTypes = [
        'dispatches' => 'Despachos',
        'internal-productions' => 'Producción Interna',
        'transfers' => 'Traslados',
    ];

    /**
     * Maximum rows DomPDF can render reliably before exhausting memory.
     * Empirically, ~2500 rows ≈ 800 MB; we keep a safety margin.
     */
    protected const PDF_MAX_ROWS = 3000;

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $type = $request->get('tipo', 'dispatches');
        if (! array_key_exists($type, $this->reportTypes)) {
            $type = 'dispatches';
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $warehouseId = $request->get('bodega');

        $vouchers = $this->loadVouchers($companyId, $type, $startDate, $endDate, $warehouseId);

        $estimatedRows = $vouchers->count() * 3 + (int) $vouchers->sum(fn ($v) => $v->items->count());
        if ($estimatedRows > self::PDF_MAX_ROWS) {
            return back()->with('error', sprintf(
                'El rango seleccionado contiene demasiados registros para generar PDF (%d comprobantes / %d líneas). Reduzca el período, filtre por bodega, o utilice la exportación Excel.',
                $vouchers->count(),
                (int) $vouchers->sum(fn ($v) => $v->items->count())
            ));
        }

        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $pdf = Pdf::loadView('reports.digitized-data-pdf', [
            'vouchers' => $vouchers,
            'totals' => $this->buildTotals($vouchers),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type,
            'typeLabel' => $this->reportTypes[$type],
            'warehouseName' => $warehouse?->name,
            'isTransfers' => $type === 'transfers',
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = 'datos-digitados-'.$type.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId($request);

        if (! $companyId) {
            return back()->with('error', 'Debe seleccionar una empresa');
        }

        $type = $request->get('tipo', 'dispatches');
        if (! array_key_exists($type, $this->reportTypes)) {
            $type = 'dispatches';
        }

        $startDate = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('fin', now()->endOfMonth()->format('Y-m-d'));
        $warehouseId = $request->get('bodega');

        $vouchers = $this->loadVouchers($companyId, $type, $startDate, $endDate, $warehouseId);
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $filename = 'datos-digitados-'.$type.'-'.now()->format('Y-m-d').'.xls';

        $html = view('reports.digitized-data-excel', [
            'vouchers' => $vouchers,
            'totals' => $this->buildTotals($vouchers),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type,
            'typeLabel' => $this->reportTypes[$type],
            'warehouseName' => $warehouse?->name,
            'isTransfers' => $type === 'transfers',
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function getEffectiveCompanyId(Request $request): ?int
    {
        if (auth()->user()->isSuperAdmin()) {
            return $request->get('empresa') ? (int) $request->get('empresa') : null;
        }

        return auth()->user()->company_id;
    }

    protected function loadVouchers(int $companyId, string $type, string $startDate, string $endDate, ?string $warehouseId): Collection
    {
        return match ($type) {
            'internal-productions' => $this->loadInternalProductions($companyId, $startDate, $endDate, $warehouseId),
            'transfers' => $this->loadTransfers($companyId, $startDate, $endDate, $warehouseId),
            default => $this->loadDispatches($companyId, $startDate, $endDate, $warehouseId),
        };
    }

    protected function loadDispatches(int $companyId, string $startDate, string $endDate, ?string $warehouseId): Collection
    {
        $query = Dispatch::query()
            ->with([
                'area:id,name',
                'warehouse:id,name',
                'details.product:id,name,sku',
                'details.unitOfMeasure:id,name,abbreviation',
            ])
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['anulado', 'cancelado'])
            ->whereBetween('document_date', [$startDate, $endDate]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->orderBy('warehouse_id')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Dispatch $dispatch) => (object) [
                'id' => $dispatch->id,
                'document_number' => $dispatch->physical_document_number ?: $dispatch->dispatch_number,
                'document_date' => $dispatch->document_date,
                'requesting_unit' => $dispatch->area?->name ?? '-',
                'warehouse_name' => $dispatch->warehouse?->name ?? '-',
                'items' => $dispatch->details->map(fn ($detail) => (object) [
                    'sku' => $detail->product?->sku ?? '-',
                    'description' => $detail->product?->name ?? '-',
                    'unit' => $detail->unitOfMeasure?->abbreviation ?? $detail->unitOfMeasure?->name ?? '-',
                    'quantity' => (float) $detail->quantity,
                    'unit_price' => (float) $detail->unit_price,
                    'total' => (float) $detail->total,
                ]),
                'total_quantity' => (float) $dispatch->details->sum('quantity'),
                'total_amount' => (float) $dispatch->details->sum('total'),
            ]);
    }

    protected function loadInternalProductions(int $companyId, string $startDate, string $endDate, ?string $warehouseId): Collection
    {
        $query = InternalProduction::query()
            ->with([
                'area:id,name',
                'warehouse:id,name',
                'details.product:id,name,sku',
                'details.unitOfMeasure:id,name,abbreviation',
            ])
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['cancelado'])
            ->whereBetween('document_date', [$startDate, $endDate]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->orderBy('warehouse_id')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(fn (InternalProduction $production) => (object) [
                'id' => $production->id,
                'document_number' => $production->physical_document_number ?: $production->production_number,
                'document_date' => $production->document_date,
                'requesting_unit' => $production->area?->name ?? '-',
                'warehouse_name' => $production->warehouse?->name ?? '-',
                'items' => $production->details->map(fn ($detail) => (object) [
                    'sku' => $detail->product?->sku ?? '-',
                    'description' => $detail->product?->name ?? $detail->description ?? '-',
                    'unit' => $detail->unitOfMeasure?->abbreviation ?? $detail->unitOfMeasure?->name ?? '-',
                    'quantity' => (float) $detail->quantity,
                    'unit_price' => (float) $detail->unit_price,
                    'total' => (float) $detail->total,
                ]),
                'total_quantity' => (float) $production->details->sum('quantity'),
                'total_amount' => (float) $production->details->sum('total'),
            ]);
    }

    protected function loadTransfers(int $companyId, string $startDate, string $endDate, ?string $warehouseId): Collection
    {
        $query = InventoryTransfer::query()
            ->with([
                'fromWarehouse:id,name,company_id',
                'toWarehouse:id,name',
                'details.product:id,name,sku',
            ])
            ->whereHas('fromWarehouse', fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('document_date', [$startDate, $endDate]);

        if ($warehouseId) {
            $query->where('from_warehouse_id', $warehouseId);
        }

        return $query
            ->orderBy('from_warehouse_id')
            ->orderBy('document_date')
            ->orderBy('id')
            ->get()
            ->map(fn (InventoryTransfer $transfer) => (object) [
                'id' => $transfer->id,
                'document_number' => $transfer->physical_document_number ?: $transfer->transfer_number,
                'document_date' => $transfer->document_date,
                'requesting_unit' => $transfer->toWarehouse?->name ?? '-',
                'warehouse_name' => $transfer->fromWarehouse?->name ?? '-',
                'items' => $transfer->details->map(fn ($detail) => (object) [
                    'sku' => $detail->product?->sku ?? '-',
                    'description' => $detail->product?->name ?? '-',
                    'unit' => '-',
                    'quantity' => (float) $detail->quantity,
                    'unit_price' => (float) $detail->unit_cost,
                    'total' => (float) $detail->quantity * (float) $detail->unit_cost,
                ]),
                'total_quantity' => (float) $transfer->details->sum('quantity'),
                'total_amount' => (float) $transfer->details->sum(fn ($d) => $d->quantity * $d->unit_cost),
            ]);
    }

    /**
     * @return array{total_vouchers: int, total_items: int, total_quantity: float, total_amount: float}
     */
    protected function buildTotals(Collection $vouchers): array
    {
        return [
            'total_vouchers' => $vouchers->count(),
            'total_items' => (int) $vouchers->sum(fn ($v) => $v->items->count()),
            'total_quantity' => (float) $vouchers->sum('total_quantity'),
            'total_amount' => (float) $vouchers->sum('total_amount'),
        ];
    }
}
