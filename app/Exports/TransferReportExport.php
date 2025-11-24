<?php

namespace App\Exports;

use App\Models\InventoryTransfer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransferReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $warehouseFromId = null,
        protected ?int $warehouseToId = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?string $status = null,
        protected ?int $companyId = null
    ) {}

    public function collection()
    {
        $companyId = $this->companyId ?? auth()->user()->company_id;
        if (! $companyId) {
            return collect();
        }

        $dateFrom = $this->dateFrom ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $this->dateTo ?? now()->endOfMonth()->format('Y-m-d');

        $query = InventoryTransfer::query()
            ->whereHas('fromWarehouse', fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('requested_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->with(['fromWarehouse', 'toWarehouse', 'requestedBy', 'details.product']);

        if ($this->warehouseFromId) {
            $query->where('from_warehouse_id', $this->warehouseFromId);
        }

        if ($this->warehouseToId) {
            $query->where('to_warehouse_id', $this->warehouseToId);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->orderBy('requested_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE TRASLADOS ENTRE BODEGAS'],
            ['Período: '.($this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d/m/Y') : 'Inicio').' - '.($this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d/m/Y') : 'Fin')],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['Fecha', 'Bodega Origen', 'Bodega Destino', 'Solicitante', 'Estado', 'Total Items', 'Notas'],
        ];
    }

    public function map($transfer): array
    {
        $statusLabels = [
            'draft' => 'Borrador',
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'in_transit' => 'En Tránsito',
            'received' => 'Recibido',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
        ];

        return [
            $transfer->requested_at?->format('d/m/Y') ?? '',
            $transfer->fromWarehouse?->name ?? '-',
            $transfer->toWarehouse?->name ?? '-',
            $transfer->requestedBy?->name ?? '-',
            $statusLabels[$transfer->status] ?? $transfer->status,
            $transfer->details->sum('quantity'),
            $transfer->notes ?? '',
        ];
    }

    public function title(): string
    {
        return 'Traslados';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
            ],
            5 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'],
                ],
            ],
        ];
    }
}
