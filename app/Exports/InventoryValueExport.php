<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryValueExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $warehouseId = null,
        protected ?int $companyId = null
    ) {}

    public function collection()
    {
        $companyId = $this->companyId ?? auth()->user()->company_id;

        if (! $companyId) {
            return collect();
        }

        $query = Inventory::query()
            ->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse']);

        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        return $query->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            ['VALOR DE INVENTARIOS'],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['Bodega', 'SKU', 'Producto', 'Cantidad', 'Unidad', 'Costo Unitario', 'Valor Total'],
        ];
    }

    public function map($inventory): array
    {
        $cost = $inventory->product->cost ?? 0;
        $totalValue = $inventory->quantity * $cost;

        return [
            $inventory->warehouse->name,
            $inventory->product->sku,
            $inventory->product->name,
            $inventory->quantity,
            $inventory->product->unitOfMeasure?->abbreviation ?? 'UND',
            number_format($cost, 2),
            number_format($totalValue, 2),
        ];
    }

    public function title(): string
    {
        return 'Valor de Inventarios';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
            ],
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'],
                ],
            ],
        ];
    }
}
