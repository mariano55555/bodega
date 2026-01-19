<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductCategoriesTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Ejemplos mostrando categorías padre e hijas
        return [
            [
                'CAT-001',
                'Insumos Agrícolas',
                '',
                'Fertilizantes, semillas y agroquímicos',
            ],
            [
                'CAT-002',
                'Alimentos para Ganado',
                '',
                'Concentrados y suplementos',
            ],
            [
                'CAT-001-01',
                'Fertilizantes',
                'CAT-001',
                'Fertilizantes orgánicos e inorgánicos',
            ],
            [
                'CAT-001-02',
                'Semillas',
                'CAT-001',
                'Semillas certificadas',
            ],
            [
                'CAT-001-03',
                'Agroquímicos',
                'CAT-001',
                'Insecticidas, herbicidas y fungicidas',
            ],
            [
                'CAT-002-01',
                'Concentrados',
                'CAT-002',
                'Alimentos balanceados',
            ],
            [
                'CAT-002-02',
                'Suplementos',
                'CAT-002',
                'Sales minerales y vitaminas',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Código *',
            'Nombre *',
            'Código Padre',
            'Descripción',
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
