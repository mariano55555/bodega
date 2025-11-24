<?php

namespace App\Exports;

use App\Models\InventoryClosure;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryClosureExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected InventoryClosure $closure;

    protected int $headerRows = 10;

    public function __construct(InventoryClosure $closure)
    {
        $this->closure = $closure->load(['warehouse', 'details.product.unitOfMeasure', 'approver', 'closer']);
    }

    public function collection()
    {
        return $this->closure->details;
    }

    public function headings(): array
    {
        $statusLabels = [
            'en_proceso' => 'En Proceso',
            'cerrado' => 'Cerrado',
            'reabierto' => 'Reabierto',
            'cancelado' => 'Cancelado',
        ];

        return [
            ['REPORTE DE CIERRE DE INVENTARIO'],
            [],
            ['Número de Cierre:', $this->closure->closure_number],
            ['Bodega:', $this->closure->warehouse->name],
            ['Período:', $this->closure->monthName.' '.$this->closure->year],
            ['Fecha de Cierre:', $this->closure->closure_date->format('d/m/Y')],
            ['Estado:', $statusLabels[$this->closure->status] ?? $this->closure->status],
            ['Generado el:', now()->format('d/m/Y H:i')],
            [],
            [
                'SKU',
                'Producto',
                'Unidad',
                'Saldo Inicial',
                'Entradas',
                'Salidas',
                'Ajustes',
                'Saldo Calculado',
                'Conteo Físico',
                'Diferencia',
                'Costo Unitario',
                'Valor Total',
            ],
        ];
    }

    /**
     * @param  mixed  $detail
     */
    public function map($detail): array
    {
        $difference = $detail->physical_count_quantity !== null
            ? $detail->physical_count_quantity - $detail->calculated_closing_quantity
            : null;

        return [
            $detail->product->sku,
            $detail->product->name,
            $detail->product->unitOfMeasure?->abbreviation ?? '',
            number_format($detail->opening_quantity, 2),
            number_format($detail->quantity_in, 2),
            number_format($detail->quantity_out, 2),
            number_format($detail->quantity_adjustment, 2),
            number_format($detail->calculated_closing_quantity, 2),
            $detail->physical_count_quantity !== null ? number_format($detail->physical_count_quantity, 2) : '-',
            $difference !== null ? number_format($difference, 2) : '-',
            '$'.number_format($detail->calculated_closing_unit_cost, 4),
            '$'.number_format($detail->calculated_closing_value, 2),
        ];
    }

    public function title(): string
    {
        return 'Cierre '.$this->closure->closure_number;
    }

    public function styles(Worksheet $sheet)
    {
        // Add summary at the bottom
        $lastRow = $this->closure->details->count() + $this->headerRows;
        $summaryRow = $lastRow + 2;

        $sheet->setCellValue('A'.$summaryRow, 'RESUMEN');
        $sheet->setCellValue('A'.($summaryRow + 1), 'Total Productos:');
        $sheet->setCellValue('B'.($summaryRow + 1), $this->closure->total_products);
        $sheet->setCellValue('A'.($summaryRow + 2), 'Total Cantidad:');
        $sheet->setCellValue('B'.($summaryRow + 2), number_format($this->closure->total_quantity, 2));
        $sheet->setCellValue('A'.($summaryRow + 3), 'Valor Total:');
        $sheet->setCellValue('B'.($summaryRow + 3), '$'.number_format($this->closure->total_value, 2));

        if ($this->closure->products_with_discrepancies > 0) {
            $sheet->setCellValue('A'.($summaryRow + 4), 'Productos con Discrepancias:');
            $sheet->setCellValue('B'.($summaryRow + 4), $this->closure->products_with_discrepancies);
            $sheet->setCellValue('A'.($summaryRow + 5), 'Valor Discrepancias:');
            $sheet->setCellValue('B'.($summaryRow + 5), '$'.number_format($this->closure->total_discrepancy_value, 2));
        }

        return [
            // Title row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
            ],
            // Info rows
            '3:8' => [
                'font' => [
                    'size' => 11,
                ],
            ],
            // Table header row
            10 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'],
                ],
            ],
            // Summary section
            $summaryRow => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
            ],
            ($summaryRow + 1).':'.($summaryRow + 5) => [
                'font' => [
                    'size' => 11,
                ],
            ],
        ];
    }
}
