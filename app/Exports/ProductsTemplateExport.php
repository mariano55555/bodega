<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Return sample rows to guide the user
        return [
            [
                'PROD-001',
                'Producto de Ejemplo 1',
                'Descripción del producto de ejemplo',
                'Categoría Ejemplo',
                'Unidad',
                'Proveedor Ejemplo',
                10.50,
                15.00,
                10,
                100,
                '1234567890123',
                'Si',
            ],
            [
                'PROD-002',
                'Producto Perecedero',
                'Ejemplo de producto perecedero',
                'Alimentos',
                'Kilogramo',
                'Proveedor ABC',
                5.00,
                8.50,
                5,
                50,
                '9876543210987',
                'No',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'SKU *',
            'Nombre *',
            'Descripción',
            'Categoría *',
            'Unidad de Medida *',
            'Proveedor',
            'Costo',
            'Precio',
            'Stock Mínimo',
            'Stock Máximo',
            'Código de Barras',
            'Seguir Inventario (Si/No)',
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
