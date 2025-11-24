<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovementSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $warehouseId = null,
        protected ?int $year = null,
        protected ?int $month = null,
        protected ?int $companyId = null
    ) {}

    public function collection()
    {
        $companyId = $this->companyId ?? auth()->user()->company_id;
        if (! $companyId) {
            return collect();
        }

        $year = $this->year ?? now()->year;
        $month = $this->month ?? now()->month;

        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->with(['product', 'warehouse', 'movementReason']);

        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        return $query->orderBy('movement_date', 'desc')->get();
    }

    public function headings(): array
    {
        $year = $this->year ?? now()->year;
        $month = $this->month ?? now()->month;
        $monthName = \Carbon\Carbon::create($year, $month)->locale('es')->monthName;

        return [
            ['RESUMEN DE MOVIMIENTOS MENSUALES'],
            ['Período: '.$monthName.' '.$year],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['Fecha', 'Bodega', 'Producto', 'SKU', 'Motivo', 'Entrada', 'Salida', 'Saldo', 'Documento', 'Referencia'],
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y'),
            $movement->warehouse->name,
            $movement->product->name,
            $movement->product->sku,
            $movement->movementReason?->name ?? $movement->movement_type_spanish,
            $movement->quantity_in > 0 ? $movement->quantity_in : '',
            $movement->quantity_out > 0 ? $movement->quantity_out : '',
            $movement->balance_quantity ?? '',
            $movement->document_number ?? '',
            $movement->reference_number ?? '',
        ];
    }

    public function title(): string
    {
        return 'Movimientos Mensuales';
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
