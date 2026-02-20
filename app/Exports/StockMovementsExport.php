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

class StockMovementsExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithDrawings, WithEvents, WithStyles, WithTitle
{
    protected Collection $groupedData;

    protected array $grandTotals = [];

    protected string $warehouseName = '';

    public function __construct(
        protected int $companyId,
        protected int $warehouseId,
        protected ?string $startDate = null,
        protected ?string $endDate = null
    ) {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');

        $warehouse = Warehouse::find($this->warehouseId);
        $this->warehouseName = $warehouse?->name ?? 'N/A';
    }

    public function collection(): Collection
    {
        // Get products with movements in the period
        $query = DB::table('inventory_movements as im')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
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
            ->where('im.warehouse_id', $this->warehouseId)
            ->whereNotNull('im.balance_quantity')
            ->where(function ($q) {
                $q->whereBetween('im.movement_date', [$this->startDate, $this->endDate])
                    ->orWhere('im.movement_date', '<', $this->startDate);
            })
            ->groupBy([
                'products.id',
                'products.name',
                'products.sku',
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

        // For each product, calculate initial stock, entries, exits
        $results = $query->map(function ($product) {
            // Get initial stock (balance just before start_date)
            $initialMovement = InventoryMovement::where('company_id', $this->companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $this->warehouseId)
                ->where('movement_date', '<', $this->startDate)
                ->whereNotNull('balance_quantity')
                ->orderByDesc('movement_date')
                ->orderByDesc('id')
                ->first();

            $initialStock = $initialMovement?->balance_quantity ?? 0;

            // Get entries during period
            $entries = InventoryMovement::where('company_id', $this->companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('movement_date', [$this->startDate, $this->endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_in');

            // Get exits during period
            $exits = InventoryMovement::where('company_id', $this->companyId)
                ->where('product_id', $product->product_id)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('movement_date', [$this->startDate, $this->endDate])
                ->whereNotNull('balance_quantity')
                ->sum('quantity_out');

            // Final stock
            $finalStock = (float) $initialStock + (float) $entries - (float) $exits;

            return (object) [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'unit' => $product->unit_abbreviation ?? $product->unit_name ?? '-',
                'category_id' => $product->category_id,
                'category_name' => $product->category_name,
                'category_code' => $product->category_code,
                'parent_id' => $product->parent_id,
                'parent_name' => $product->parent_name,
                'parent_code' => $product->parent_code,
                'initial_stock' => (float) $initialStock,
                'entries' => (float) $entries,
                'exits' => (float) $exits,
                'final_stock' => $finalStock,
            ];
        });

        $this->groupedData = $results->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'items' => $items,
                'subtotals' => (object) [
                    'initial_stock' => $items->sum('initial_stock'),
                    'entries' => $items->sum('entries'),
                    'exits' => $items->sum('exits'),
                    'final_stock' => $items->sum('final_stock'),
                ],
            ];
        });

        $this->grandTotals = [
            'initial_stock' => $results->sum('initial_stock'),
            'entries' => $results->sum('entries'),
            'exits' => $results->sum('exits'),
            'final_stock' => $results->sum('final_stock'),
        ];

        return collect();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 10,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
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
        return 'Existencias y Movimientos';
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
                $sheet->setCellValue('A4', 'EXISTENCIAS Y MOVIMIENTOS DE INVENTARIO');
                $sheet->setCellValue('A5', 'PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y'));
                $sheet->setCellValue('A6', 'BODEGA: '.$this->warehouseName);
                $sheet->setCellValue('A8', 'Generado: '.now()->format('d/m/Y H:i'));

                // Merge header cells
                $sheet->mergeCells('A2:F2');
                $sheet->mergeCells('A3:F3');
                $sheet->mergeCells('A4:F4');
                $sheet->mergeCells('A5:F5');
                $sheet->mergeCells('A6:F6');
                $sheet->mergeCells('A8:F8');

                // Apply header styles
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1e3a5f');
                $sheet->getStyle('A2:F6')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A6')->getFont()->setBold(true)->getColor()->setRGB('92400e');

                // Data starting from row 10
                $currentRow = 10;

                foreach ($this->groupedData as $group) {
                    // Category header
                    $sheet->setCellValue("A{$currentRow}", "Categoría: {$group->parent_name} - {$group->parent_code}");
                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
                    $sheet->getStyle("A{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '92400e']],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'fff7ed'],
                        ],
                        'borders' => [
                            'left' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                                'color' => ['rgb' => 'd97706'],
                            ],
                        ],
                    ]);
                    $currentRow++;

                    // Column headers
                    $sheet->setCellValue("A{$currentRow}", 'Descripción');
                    $sheet->setCellValue("B{$currentRow}", 'Unidad');
                    $sheet->setCellValue("C{$currentRow}", 'Exist. Inicial');
                    $sheet->setCellValue("D{$currentRow}", 'Entradas');
                    $sheet->setCellValue("E{$currentRow}", 'Salidas');
                    $sheet->setCellValue("F{$currentRow}", 'Exist. Final');
                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'e8e8e8'],
                        ],
                        'borders' => [
                            'bottom' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                    $currentRow++;

                    // Data rows
                    foreach ($group->items as $item) {
                        $sheet->setCellValue("A{$currentRow}", $item->product_name."\n".$item->sku);
                        $sheet->getStyle("A{$currentRow}")->getAlignment()->setWrapText(true);
                        $sheet->setCellValue("B{$currentRow}", $item->unit);
                        $sheet->setCellValue("C{$currentRow}", $item->initial_stock);
                        $sheet->setCellValue("D{$currentRow}", $item->entries);
                        $sheet->setCellValue("E{$currentRow}", $item->exits);
                        $sheet->setCellValue("F{$currentRow}", $item->final_stock);

                        $sheet->getStyle("C{$currentRow}:F{$currentRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                        $sheet->getStyle("C{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal('right');

                        // Color for entries (green) and exits (red)
                        $sheet->getStyle("D{$currentRow}")->getFont()->getColor()->setRGB('16a34a');
                        $sheet->getStyle("E{$currentRow}")->getFont()->getColor()->setRGB('dc2626');
                        $sheet->getStyle("F{$currentRow}")->getFont()->setBold(true);

                        $currentRow++;
                    }

                    // Subtotal row
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", "Subtotal {$group->parent_name}");
                    $sheet->setCellValue("C{$currentRow}", $group->subtotals->initial_stock);
                    $sheet->setCellValue("D{$currentRow}", $group->subtotals->entries);
                    $sheet->setCellValue("E{$currentRow}", $group->subtotals->exits);
                    $sheet->setCellValue("F{$currentRow}", $group->subtotals->final_stock);

                    $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'fff7ed'],
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                                'color' => ['rgb' => 'fed7aa'],
                            ],
                        ],
                    ]);
                    $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal('right');
                    $sheet->getStyle("C{$currentRow}:F{$currentRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                    $sheet->getStyle("C{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal('right');
                    $currentRow += 2;
                }

                // Grand total
                $sheet->setCellValue("B{$currentRow}", 'TOTALES DEL PERÍODO');
                $sheet->setCellValue("C{$currentRow}", $this->grandTotals['initial_stock']);
                $sheet->setCellValue("D{$currentRow}", $this->grandTotals['entries']);
                $sheet->setCellValue("E{$currentRow}", $this->grandTotals['exits']);
                $sheet->setCellValue("F{$currentRow}", $this->grandTotals['final_stock']);

                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
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
                $sheet->getStyle("C{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal('right');

                // Signatures
                $signatureRow = $currentRow + 5;
                $sheet->setCellValue("A{$signatureRow}", '________________________');
                $sheet->setCellValue("C{$signatureRow}", '________________________');
                $sheet->setCellValue("E{$signatureRow}", '________________________');

                $labelRow = $signatureRow + 1;
                $sheet->setCellValue("A{$labelRow}", 'Elaborado');
                $sheet->setCellValue("C{$labelRow}", 'Revisado');
                $sheet->setCellValue("E{$labelRow}", 'Autorizado');

                $sheet->getStyle("A{$labelRow}:F{$labelRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signatureRow}:F{$signatureRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$labelRow}:F{$labelRow}")->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
