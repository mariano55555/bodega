<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoriesTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Return sample rows to guide the user
        // Note: These are examples - user must replace with actual SKUs and warehouse names
        return [
            [
                '[SKU del producto]',
                '[Nombre exacto de la bodega]',
                100.00,
                0,
                15.50,
                'A-01-01',
                'LOTE-2024-001',
                '2025-12-31',
                'Ejemplo: reemplace con sus datos reales',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'SKU *',
            'Bodega *',
            'Cantidad *',
            'Cantidad Reservada',
            'Costo Unitario',
            'Ubicación',
            'Número de Lote',
            'Fecha de Vencimiento',
            'Notas',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F46E5'],
                ],
            ],
        ];
    }
}
