<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KardexExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected int $productId,
        protected int $warehouseId,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?int $companyId = null
    ) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $companyId = $this->companyId ?? auth()->user()->company_id;

        if (! $companyId) {
            return collect();
        }

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->where('product_id', $this->productId)
            ->where('warehouse_id', $this->warehouseId)
            ->whereNotNull('balance_quantity')
            ->with(['product', 'warehouse', 'movementReason']);

        if ($this->dateFrom) {
            $query->whereDate('movement_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('movement_date', '<=', $this->dateTo);
        }

        return $query->orderBy('movement_date')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        $product = Product::find($this->productId);
        $warehouse = Warehouse::find($this->warehouseId);

        return [
            ['KARDEX DE INVENTARIO'],
            ['Producto: '.$product->name.' (SKU: '.$product->sku.')'],
            ['Almacén: '.$warehouse->name],
            ['Período: '.($this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d/m/Y') : 'Inicio').' - '.($this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d/m/Y') : 'Fin')],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['Fecha', 'Documento', 'Referencia', 'Transacción', 'Saldo Inicial', 'Entrada', 'Salida', 'Saldo Final', 'Costo Unitario', 'Valor Total'],
        ];
    }

    /**
     * @param  mixed  $movement
     */
    public function map($movement): array
    {
        $initialBalance = $movement->balance_quantity - $movement->quantity_in + $movement->quantity_out;
        $totalValue = $movement->balance_quantity * ($movement->unit_cost ?? 0);

        return [
            $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y'),
            $movement->document_number ?? '',
            $movement->reference_number ?? '',
            $movement->movementReason?->name ?? $movement->movement_type_spanish,
            $initialBalance,
            $movement->quantity_in > 0 ? $movement->quantity_in : '',
            $movement->quantity_out > 0 ? $movement->quantity_out : '',
            $movement->balance_quantity,
            $movement->unit_cost ?? 0,
            $totalValue,
        ];
    }

    public function title(): string
    {
        return 'Kardex';
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
            '2:5' => [
                'font' => [
                    'size' => 11,
                ],
            ],
            7 => [
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
