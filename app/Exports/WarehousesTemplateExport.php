<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehousesTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'BOD-001',
                'Bodega Principal',
                'SUC-001',
                'principal',
                '',
                'Bodega central de almacenamiento',
                'Boulevard Los Héroes #100',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                1000,
                'm2',
            ],
            [
                'BOD-002',
                'Bodega Refrigerada',
                'SUC-001',
                'refrigerado',
                'BOD-001',
                'Almacenamiento de productos perecederos',
                'Boulevard Los Héroes #100-A',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                200,
                'm2',
            ],
            [
                'BOD-003',
                'Bodega Santa Ana',
                'SUC-002',
                'principal',
                '',
                'Bodega de distribución zona occidental',
                'Avenida Independencia #50',
                'Santa Ana',
                'Santa Ana',
                'El Salvador',
                '02201',
                500,
                'm2',
            ],
            [
                'BOD-004',
                'Bodega San Miguel',
                'SUC-003',
                'principal',
                '',
                'Bodega de distribución zona oriental',
                'Calle Chaparrastique #200',
                'San Miguel',
                'San Miguel',
                'El Salvador',
                '03301',
                400,
                'm2',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Código *',
            'Nombre *',
            'Código Sucursal *',
            'Tipo * (principal/secundario/transito/refrigerado/externo)',
            'Código Bodega Padre',
            'Descripción',
            'Dirección',
            'Ciudad',
            'Departamento',
            'País',
            'Código Postal',
            'Capacidad Total',
            'Unidad Capacidad (m2/m3/pallets/unidades)',
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
