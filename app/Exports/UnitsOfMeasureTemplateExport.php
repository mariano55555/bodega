<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitsOfMeasureTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Unidad',
                'UND',
                'unit',
                'Unidad básica para contar artículos individuales',
                '',
                1,
                0,
                'Si',
            ],
            [
                'Kilogramo',
                'KG',
                'weight',
                'Unidad de peso',
                '',
                1,
                3,
                'No',
            ],
            [
                'Gramo',
                'GR',
                'weight',
                'Unidad de peso pequeña',
                'KG',
                0.001,
                3,
                'No',
            ],
            [
                'Litro',
                'LT',
                'volume',
                'Unidad de volumen',
                '',
                1,
                2,
                'No',
            ],
            [
                'Mililitro',
                'ML',
                'volume',
                'Unidad de volumen pequeña',
                'LT',
                0.001,
                0,
                'No',
            ],
            [
                'Metro',
                'MT',
                'length',
                'Unidad de longitud',
                '',
                1,
                2,
                'No',
            ],
            [
                'Caja',
                'CJ',
                'unit',
                'Caja de productos',
                'UND',
                12,
                0,
                'No',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre *',
            'Abreviatura *',
            'Tipo * (unit/weight/volume/length/area/time)',
            'Descripción',
            'Unidad Base (Abreviatura)',
            'Factor de Conversión',
            'Precisión Decimal',
            'Es Predeterminada (Si/No)',
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
