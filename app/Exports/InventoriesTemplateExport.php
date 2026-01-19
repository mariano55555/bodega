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
        // Ejemplos usando SKUs del archivo de productos
        return [
            [
                'PRO-001',
                'Bodega General',
                50,
                0,
                25.00,
                'Pasillo A Estante 1',
                '',
                '',
                'Inventario inicial',
            ],
            [
                'PRO-001',
                'Bodega Agronomía',
                20,
                0,
                25.00,
                'Zona Fertilizantes',
                '',
                '',
                'Inventario inicial',
            ],
            [
                'PRO-002',
                'Bodega General',
                100,
                0,
                9.00,
                '',
                'LOTE-2024-001',
                '2025-06-30',
                'Semillas certificadas',
            ],
            [
                'PRO-003',
                'Bodega General',
                25,
                0,
                15.00,
                'Químicos Estante 2',
                '',
                '',
                '',
            ],
            [
                'PRO-004',
                'Bodega General',
                30,
                0,
                32.00,
                'Alimentos A1',
                '',
                '',
                'Inventario inicial',
            ],
            [
                'PRO-005',
                'Bodega de Cocina',
                15,
                0,
                8.00,
                '',
                '',
                '',
                'Stock de sales',
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
