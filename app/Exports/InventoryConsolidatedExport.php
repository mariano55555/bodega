<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryConsolidatedExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithDrawings, WithEvents, WithStyles, WithTitle
{
    protected Collection $groupedData;

    protected array $grandTotals = [];

    protected string $warehouseName = '';

    protected bool $isAllWarehouses = false;

    public function __construct(
        protected int $companyId,
        protected ?int $warehouseId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        ?string $warehouseName = null
    ) {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');
        $this->isAllWarehouses = is_null($this->warehouseId);

        if ($warehouseName) {
            $this->warehouseName = $warehouseName;
        } elseif ($this->isAllWarehouses) {
            $this->warehouseName = 'TODAS LAS BODEGAS';
        } else {
            $warehouse = Warehouse::find($this->warehouseId);
            $this->warehouseName = $warehouse?->name ?? 'N/A';
        }
    }

    public function collection(): Collection
    {
        $query = DB::table('inventory_movements as im')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.cost as product_cost',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
            ])
            ->join('products', 'im.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'products.unit_of_measure_id', '=', 'units_of_measure.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('im.company_id', $this->companyId)
            ->when(! $this->isAllWarehouses, fn ($q) => $q->where('im.warehouse_id', $this->warehouseId))
            ->whereNotNull('im.balance_quantity')
            ->where(function ($q) {
                $q->whereBetween('im.movement_date', [$this->startDate, $this->endDate])
                    ->orWhere('im.movement_date', '<', $this->startDate);
            })
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
                'products.cost',
                'units_of_measure.abbreviation',
                'units_of_measure.name',
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->get();

        $warehouseIds = $this->isAllWarehouses
            ? Warehouse::where('company_id', $this->companyId)->where('is_active', true)->pluck('id')
            : null;

        $results = $query->map(function ($product) use ($warehouseIds) {
            if ($this->isAllWarehouses) {
                // Sum initial stock across all warehouses
                $initialStock = 0;
                foreach ($warehouseIds as $whId) {
                    $movement = InventoryMovement::where('company_id', $this->companyId)
                        ->where('product_id', $product->product_id)
                        ->where('warehouse_id', $whId)
                        ->where('movement_date', '<', $this->startDate)
                        ->whereNotNull('balance_quantity')
                        ->orderByDesc('movement_date')
                        ->orderByDesc('id')
                        ->first();
                    $initialStock += (float) ($movement?->balance_quantity ?? 0);
                }
            } else {
                $initialMovement = InventoryMovement::where('company_id', $this->companyId)
                    ->where('product_id', $product->product_id)
                    ->where('warehouse_id', $this->warehouseId)
                    ->where('movement_date', '<', $this->startDate)
                    ->whereNotNull('balance_quantity')
                    ->orderByDesc('movement_date')
                    ->orderByDesc('id')
                    ->first();
                $initialStock = $initialMovement?->balance_quantity ?? 0;
            }

            $entries = InventoryMovement::where('company_id', $this->companyId)
                ->where('product_id', $product->product_id)
                ->when(! $this->isAllWarehouses, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                ->whereBetween('movement_date', [$this->startDate, $this->endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_in');

            $exits = InventoryMovement::where('company_id', $this->companyId)
                ->where('product_id', $product->product_id)
                ->when(! $this->isAllWarehouses, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                ->whereBetween('movement_date', [$this->startDate, $this->endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_out');

            $currentStock = (float) $initialStock + (float) $entries - (float) $exits;

            // Get unit cost from the most recent movement
            $lastCostMovement = InventoryMovement::where('company_id', $this->companyId)
                ->where('product_id', $product->product_id)
                ->when(! $this->isAllWarehouses, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                ->where('movement_date', '<=', $this->endDate)
                ->whereNotNull('balance_quantity')
                ->where('unit_cost', '>', 0)
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $unitCost = (float) ($lastCostMovement?->unit_cost ?? $product->product_cost ?? 0);
            $totalCost = $currentStock * $unitCost;

            return (object) [
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'unit' => $product->unit_abbreviation ?? $product->unit_name ?? '-',
                'category_name' => $product->category_name,
                'category_code' => $product->category_code,
                'parent_name' => $product->parent_name,
                'parent_code' => $product->parent_code,
                'initial_stock' => (float) $initialStock,
                'entries' => (float) $entries,
                'exits' => (float) $exits,
                'current_stock' => $currentStock,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ];
        })->filter(fn ($item) => $item->current_stock != 0);

        $this->groupedData = $results->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            $subcategories = $items->groupBy('category_name')->map(function ($subItems, $categoryName) {
                $first = $subItems->first();

                return (object) [
                    'category_name' => $categoryName ?: 'Sin Subcategoría',
                    'category_code' => $first->category_code ?? '',
                    'items' => $subItems->sortBy('sku')->values(),
                    'subtotals' => (object) [
                        'initial_stock' => $subItems->sum('initial_stock'),
                        'entries' => $subItems->sum('entries'),
                        'exits' => $subItems->sum('exits'),
                        'current_stock' => $subItems->sum('current_stock'),
                        'total_cost' => $subItems->sum('total_cost'),
                    ],
                ];
            })->sortBy('category_code')->values();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'subcategories' => $subcategories,
                'parent_subtotals' => (object) [
                    'initial_stock' => $items->sum('initial_stock'),
                    'entries' => $items->sum('entries'),
                    'exits' => $items->sum('exits'),
                    'current_stock' => $items->sum('current_stock'),
                    'total_cost' => $items->sum('total_cost'),
                ],
            ];
        })->sortBy(fn ($group) => $group->parent_code)->values();

        $this->grandTotals = [
            'initial_stock' => $results->sum('initial_stock'),
            'entries' => $results->sum('entries'),
            'exits' => $results->sum('exits'),
            'current_stock' => $results->sum('current_stock'),
            'total_cost' => $results->sum('total_cost'),
        ];

        return collect();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 12,
            'C' => 15,
            'D' => 13,
            'E' => 13,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }

    public function drawings(): Drawing
    {
        $drawing = new Drawing;
        $drawing->setName('Logo');
        $drawing->setDescription('Logo ENA');
        $drawing->setPath(public_path('images/LOGO-ENA_gris.png'));
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function title(): string
    {
        return 'Inventario Consolidado';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            2 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1e3a5f']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Header
                $sheet->setCellValue('A2', 'ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"');
                $sheet->setCellValue('A3', 'GERENCIA ADMINISTRATIVA');
                $sheet->setCellValue('A4', 'REPORTE INVENTARIO CONSOLIDADO');
                $sheet->setCellValue('A5', $this->warehouseName);
                $sheet->setCellValue('A6', 'PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y'));
                $sheet->setCellValue('A8', 'Generado: '.now()->format('d/m/Y H:i'));

                // Merge header cells
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');
                $sheet->mergeCells('A4:H4');
                $sheet->mergeCells('A5:H5');
                $sheet->mergeCells('A6:H6');
                $sheet->mergeCells('A8:H8');

                // Apply header styles
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1e3a5f');
                $sheet->getStyle('A2:H6')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A5')->getFont()->setBold(true)->getColor()->setRGB('92400e');

                $currentRow = 10;

                foreach ($this->groupedData as $group) {
                    // Parent category header
                    $sheet->setCellValue("A{$currentRow}", "{$group->parent_name} — Código {$group->parent_code}");
                    $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                    $sheet->getStyle("A{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '1e3a5f'],
                        ],
                    ]);
                    $currentRow++;

                    foreach ($group->subcategories as $subcategory) {
                        // Subcategory / Línea Presupuestaria header
                        $sheet->setCellValue("A{$currentRow}", "Línea Presupuestaria: {$subcategory->category_name} — Específico {$subcategory->category_code}");
                        $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                        $sheet->getStyle("A{$currentRow}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '3b6998'],
                            ],
                        ]);
                        $currentRow++;

                        // Column headers
                        $headers = ['Descripción del Producto', 'Unidad de Medida', 'Existencia Inicial', 'Entradas', 'Salidas', 'Existencia Actual', 'Precio Unitario', 'Costo Total'];
                        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                        foreach ($headers as $i => $header) {
                            $sheet->setCellValue("{$columns[$i]}{$currentRow}", $header);
                        }
                        $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '2d4a6f'],
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                ],
                            ],
                        ]);
                        $currentRow++;

                        // Data rows
                        foreach ($subcategory->items as $item) {
                            $sheet->setCellValue("A{$currentRow}", $item->product_name."\n".$item->sku);
                            $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);
                            $sheet->setCellValue("B{$currentRow}", $item->unit);
                            $sheet->setCellValue("C{$currentRow}", $item->initial_stock);
                            $sheet->setCellValue("D{$currentRow}", $item->entries);
                            $sheet->setCellValue("E{$currentRow}", $item->exits);
                            $sheet->setCellValue("F{$currentRow}", $item->current_stock);
                            $sheet->setCellValue("G{$currentRow}", $item->unit_cost);
                            $sheet->setCellValue("H{$currentRow}", $item->total_cost);

                            $sheet->getStyle("C{$currentRow}:H{$currentRow}")
                                ->getNumberFormat()
                                ->setFormatCode('#,##0.00');
                            $sheet->getStyle("C{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal('right');
                            $sheet->getStyle("G{$currentRow}")
                                ->getNumberFormat()
                                ->setFormatCode('$#,##0.00000');
                            $sheet->getStyle("H{$currentRow}")
                                ->getNumberFormat()
                                ->setFormatCode('$#,##0.00');

                            $sheet->getStyle("D{$currentRow}")->getFont()->getColor()->setRGB('16a34a');
                            $sheet->getStyle("E{$currentRow}")->getFont()->getColor()->setRGB('dc2626');
                            $sheet->getStyle("F{$currentRow}")->getFont()->setBold(true);
                            $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true);

                            $currentRow++;
                        }

                        // Subcategory subtotal row
                        $sheet->setCellValue("A{$currentRow}", '');
                        $sheet->setCellValue("B{$currentRow}", "Total Línea {$subcategory->category_code}");
                        $sheet->setCellValue("C{$currentRow}", $subcategory->subtotals->initial_stock);
                        $sheet->setCellValue("D{$currentRow}", $subcategory->subtotals->entries);
                        $sheet->setCellValue("E{$currentRow}", $subcategory->subtotals->exits);
                        $sheet->setCellValue("F{$currentRow}", $subcategory->subtotals->current_stock);
                        $sheet->setCellValue("G{$currentRow}", '');
                        $sheet->setCellValue("H{$currentRow}", $subcategory->subtotals->total_cost);

                        $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'e8e8e8'],
                            ],
                            'borders' => [
                                'top' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                ],
                            ],
                        ]);
                        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal('right');
                        $sheet->getStyle("C{$currentRow}:F{$currentRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                        $sheet->getStyle("H{$currentRow}")
                            ->getNumberFormat()
                            ->setFormatCode('$#,##0.00');
                        $sheet->getStyle("C{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal('right');
                        $currentRow++;
                    }

                    // Parent category subtotal row
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", "Total {$group->parent_name}");
                    $sheet->setCellValue("C{$currentRow}", $group->parent_subtotals->initial_stock);
                    $sheet->setCellValue("D{$currentRow}", $group->parent_subtotals->entries);
                    $sheet->setCellValue("E{$currentRow}", $group->parent_subtotals->exits);
                    $sheet->setCellValue("F{$currentRow}", $group->parent_subtotals->current_stock);
                    $sheet->setCellValue("G{$currentRow}", '');
                    $sheet->setCellValue("H{$currentRow}", $group->parent_subtotals->total_cost);

                    $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'd0d0d0'],
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                            ],
                        ],
                    ]);
                    $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal('right');
                    $sheet->getStyle("C{$currentRow}:F{$currentRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                    $sheet->getStyle("H{$currentRow}")
                        ->getNumberFormat()
                        ->setFormatCode('$#,##0.00');
                    $sheet->getStyle("C{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal('right');
                    $currentRow += 2;
                }

                // Grand total
                $sheet->setCellValue("B{$currentRow}", 'TOTALES DEL PERÍODO');
                $sheet->setCellValue("C{$currentRow}", $this->grandTotals['initial_stock']);
                $sheet->setCellValue("D{$currentRow}", $this->grandTotals['entries']);
                $sheet->setCellValue("E{$currentRow}", $this->grandTotals['exits']);
                $sheet->setCellValue("F{$currentRow}", $this->grandTotals['current_stock']);
                $sheet->setCellValue("H{$currentRow}", $this->grandTotals['total_cost']);

                $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1e3a5f'],
                    ],
                ]);
                $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal('right');
                $sheet->getStyle("C{$currentRow}:F{$currentRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle("H{$currentRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');
                $sheet->getStyle("C{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal('right');

                // Signatures
                $signatureRow = $currentRow + 5;
                $sheet->setCellValue("A{$signatureRow}", '________________________');
                $sheet->setCellValue("D{$signatureRow}", '________________________');
                $sheet->setCellValue("G{$signatureRow}", '________________________');

                $labelRow = $signatureRow + 1;
                $sheet->setCellValue("A{$labelRow}", 'Elaborado');
                $sheet->setCellValue("D{$labelRow}", 'Revisado');
                $sheet->setCellValue("G{$labelRow}", 'Autorizado');

                $sheet->getStyle("A{$labelRow}:H{$labelRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signatureRow}:H{$signatureRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$labelRow}:H{$labelRow}")->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
