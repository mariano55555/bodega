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
        // Ejemplos usando códigos de categoría del archivo de categorías
        return [
            [
                'PRO-001',
                'Fertilizante NPK 15-15-15',
                'Fertilizante balanceado 50lb',
                'CAT-001-01',
                'Saco',
                'Agroinsumos SA',
                25.00,
                32.00,
                20,
                100,
                '7501234567890',
                'Si',
            ],
            [
                'PRO-002',
                'Semilla Maíz Híbrido H-59',
                'Semilla de alto rendimiento',
                'CAT-001-02',
                'Kilogramo',
                'Semillas del Campo',
                9.00,
                12.00,
                50,
                300,
                '',
                'Si',
            ],
            [
                'PRO-003',
                'Insecticida Cipermetrina 25%',
                'Insecticida piretroide de amplio espectro',
                'CAT-001-03',
                'Litro',
                'AgroQuímicos SA',
                15.00,
                22.00,
                10,
                50,
                '',
                'Si',
            ],
            [
                'PRO-004',
                'Concentrado Bovino 18%',
                'Alimento balanceado para ganado 100lb',
                'CAT-002-01',
                'Saco',
                'Nutrición Animal',
                32.00,
                40.00,
                15,
                60,
                '',
                'Si',
            ],
            [
                'PRO-005',
                'Sales Minerales Bloque 5kg',
                'Bloque de sales minerales para ganado',
                'CAT-002-02',
                'Bloque',
                'Nutrición Animal',
                8.00,
                12.00,
                30,
                120,
                '',
                'Si',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'SKU *',
            'Nombre *',
            'Descripción',
            'Categoría (Código) *',
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
