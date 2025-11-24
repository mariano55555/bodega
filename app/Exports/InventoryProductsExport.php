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

class InventoryProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $companyId = null,
        protected ?string $search = null,
        protected ?int $warehouseId = null,
        protected ?int $categoryId = null,
        protected ?string $stockLevel = null,
        protected bool $showLowStock = false,
        protected bool $showExpiring = false
    ) {}

    public function collection()
    {
        $query = Inventory::query()
            ->with([
                'product' => function ($query) {
                    $query->with(['category', 'unitOfMeasure']);
                },
                'warehouse',
                'storageLocation',
            ])
            ->active()
            ->whereHas('product', function ($q) {
                $q->where('track_inventory', true);
            });

        // Company filter - filter through warehouse relationship
        if ($this->companyId) {
            $query->whereHas('warehouse', function ($q) {
                $q->where('company_id', $this->companyId);
            });
        }

        // Search filter
        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            })->orWhere('lot_number', 'like', "%{$this->search}%")
                ->orWhere('location', 'like', "%{$this->search}%");
        }

        // Warehouse filter
        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        // Category filter
        if ($this->categoryId) {
            $query->whereHas('product.category', function ($q) {
                $q->where('id', $this->categoryId);
            });
        }

        // Stock level filters
        if ($this->showLowStock) {
            $query->whereHas('product', function ($q) {
                $q->whereRaw('inventory.available_quantity <= products.minimum_stock');
            });
        }

        if ($this->showExpiring) {
            $query->expiringSoon(30);
        }

        // Stock level filter
        if ($this->stockLevel === 'available') {
            $query->available();
        } elseif ($this->stockLevel === 'reserved') {
            $query->where('reserved_quantity', '>', 0);
        } elseif ($this->stockLevel === 'zero') {
            $query->where('available_quantity', '<=', 0);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            ['INVENTARIO DE PRODUCTOS'],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            [
                'Bodega',
                'SKU',
                'Producto',
                'Categoría',
                'Ubicación',
                'Lote',
                'Cantidad Total',
                'Disponible',
                'Reservado',
                'Unidad',
                'Costo Unit.',
                'Valor Total',
                'Fecha Exp.',
            ],
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
            $inventory->product->category?->name ?? 'Sin categoría',
            $inventory->storageLocation?->name ?? 'Sin ubicación',
            $inventory->lot_number ?? 'N/A',
            number_format($inventory->quantity, 4),
            number_format($inventory->available_quantity, 4),
            number_format($inventory->reserved_quantity, 4),
            $inventory->product->unitOfMeasure?->abbreviation ?? 'UND',
            number_format($cost, 2),
            number_format($totalValue, 2),
            $inventory->expiration_date?->format('d/m/Y') ?? 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Inventario';
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
            2 => [
                'font' => [
                    'size' => 11,
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
