<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsumptionByLineExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $warehouseId = null,
        protected ?int $categoryId = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null
    ) {}

    public function collection()
    {
        $companyId = auth()->user()->company_id;
        $dateFrom = $this->dateFrom ?? now()->startOfMonth();
        $dateTo = $this->dateTo ?? now()->endOfMonth();

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->where('quantity_out', '>', 0)
            ->with(['product.category', 'warehouse']);

        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        if ($this->categoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->categoryId);
            });
        }

        $movements = $query->get();

        $byCategory = $movements->groupBy('product.category.name')->map(function ($items, $categoryName) {
            return (object) [
                'category_name' => $categoryName ?: 'Sin categoría',
                'total_quantity' => $items->sum('quantity_out'),
                'total_value' => $items->sum(function ($item) {
                    return $item->quantity_out * ($item->product->cost ?? 0);
                }),
                'products_count' => $items->pluck('product_id')->unique()->count(),
            ];
        })->sortByDesc('total_quantity');

        return new Collection($byCategory->values());
    }

    public function headings(): array
    {
        return [
            ['CONSUMO MENSUAL POR LÍNEA DE PRODUCTOS'],
            ['Período: '.($this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d/m/Y') : 'Inicio').' - '.($this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d/m/Y') : 'Fin')],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['Línea de Producto', 'Cantidad Total Consumida', 'Valor Total', 'Cantidad de Productos'],
        ];
    }

    public function map($item): array
    {
        return [
            $item->category_name,
            number_format($item->total_quantity, 2),
            '$'.number_format($item->total_value, 2),
            $item->products_count,
        ];
    }

    public function title(): string
    {
        return 'Consumo por Línea';
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
            5 => [
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
