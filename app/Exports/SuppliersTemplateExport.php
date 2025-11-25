<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuppliersTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Distribuidora ABC',
                'Distribuidora ABC S.A. de C.V.',
                '0614-123456-123-4',
                'proveedor@abc.com',
                '2222-3333',
                'www.abc.com',
                'Calle Principal #123',
                'San Salvador',
                'San Salvador',
                'El Salvador',
                '01101',
                'Juan Pérez',
                '7777-8888',
                'juan@abc.com',
                '30 días',
                5000.00,
                5,
                'Proveedor principal de alimentos',
            ],
            [
                'Importadora XYZ',
                'Importadora XYZ S.A. de C.V.',
                '0614-654321-321-9',
                'ventas@xyz.com',
                '2233-4455',
                '',
                'Boulevard Los Héroes #456',
                'Santa Ana',
                'Santa Ana',
                'El Salvador',
                '02201',
                'María García',
                '7788-9900',
                'maria@xyz.com',
                '15 días',
                10000.00,
                4,
                'Importador de productos electrónicos',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre *',
            'Razón Social',
            'NIT/NRC *',
            'Correo Electrónico',
            'Teléfono',
            'Sitio Web',
            'Dirección',
            'Ciudad',
            'Departamento',
            'País',
            'Código Postal',
            'Persona de Contacto',
            'Teléfono Contacto',
            'Correo Contacto',
            'Términos de Pago',
            'Límite de Crédito',
            'Calificación (1-5)',
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
