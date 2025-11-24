<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryRotationExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $warehouseId = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?int $companyId = null
    ) {}

    public function collection()
    {
        $companyId = $this->companyId ?? auth()->user()->company_id;

        if (! $companyId) {
            return collect();
        }

        $dateFrom = $this->dateFrom ?? now()->subMonths(3)->startOfMonth();
        $dateTo = $this->dateTo ?? now()->endOfMonth();

        $products = Product::where('company_id', $companyId)
            ->with(['inventory' => function ($q) {
                if ($this->warehouseId) {
                    $q->where('warehouse_id', $this->warehouseId);
                }
            }])
            ->get()
            ->map(function ($product) use ($dateFrom, $dateTo, $companyId) {
                $avgInventory = $product->inventory->avg('quantity') ?? 0;

                $totalMovements = InventoryMovement::where('company_id', $companyId)
                    ->where('product_id', $product->id)
                    ->when($this->warehouseId, function ($q) {
                        $q->where('warehouse_id', $this->warehouseId);
                    })
                    ->whereBetween('movement_date', [$dateFrom, $dateTo])
                    ->where('quantity_out', '>', 0)
                    ->sum('quantity_out');

                $rotationRate = $avgInventory > 0 ? ($totalMovements / $avgInventory) : 0;

                return (object) [
                    'product' => $product,
                    'avg_inventory' => $avgInventory,
                    'total_out' => $totalMovements,
                    'rotation_rate' => $rotationRate,
                ];
            })
            ->sortByDesc('rotation_rate');

        return new Collection($products->values());
    }

    public function headings(): array
    {
        return [
            ['ROTACIÓN DE INVENTARIOS'],
            ['Período: '.($this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d/m/Y') : 'Inicio').' - '.($this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d/m/Y') : 'Fin')],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['SKU', 'Producto', 'Inventario Promedio', 'Total Salidas', 'Tasa de Rotación', 'Clasificación'],
        ];
    }

    public function map($item): array
    {
        $classification = match (true) {
            $item->rotation_rate >= 4 => 'Alta rotación',
            $item->rotation_rate >= 2 => 'Media rotación',
            $item->rotation_rate >= 1 => 'Baja rotación',
            default => 'Muy baja rotación',
        };

        return [
            $item->product->sku,
            $item->product->name,
            number_format($item->avg_inventory, 2),
            number_format($item->total_out, 2),
            number_format($item->rotation_rate, 2),
            $classification,
        ];
    }

    public function title(): string
    {
        return 'Rotación de Inventarios';
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
