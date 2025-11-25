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
        return [
            [
                'CAT-001',
                'Alimentos',
                'Productos alimenticios y comestibles',
            ],
            [
                'CAT-002',
                'Bebidas',
                'Bebidas y líquidos para consumo',
            ],
            [
                'CAT-003',
                'Limpieza',
                'Productos de limpieza y aseo',
            ],
            [
                'CAT-004',
                'Oficina',
                'Artículos de oficina y papelería',
            ],
            [
                'CAT-005',
                'Electrónicos',
                'Equipos y dispositivos electrónicos',
            ],
            [
                'CAT-006',
                'Herramientas',
                'Herramientas manuales y eléctricas',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Código *',
            'Nombre *',
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
