<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchesTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'SUC-001',
                'Sucursal Central',
                'Sucursal principal en San Salvador',
                'Boulevard Los Héroes #100',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
            ],
            [
                'SUC-002',
                'Sucursal Santa Ana',
                'Sucursal en zona occidental',
                'Avenida Independencia #50',
                'Santa Ana',
                'Santa Ana',
                'El Salvador',
                '02201',
            ],
            [
                'SUC-003',
                'Sucursal San Miguel',
                'Sucursal en zona oriental',
                'Calle Chaparrastique #200',
                'San Miguel',
                'San Miguel',
                'El Salvador',
                '03301',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Código *',
            'Nombre *',
            'Descripción',
            'Dirección',
            'Ciudad',
            'Departamento',
            'País',
            'Código Postal',
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
