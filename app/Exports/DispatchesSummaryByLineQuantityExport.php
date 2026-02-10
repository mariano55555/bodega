<?php

namespace App\Exports;

use App\Models\DispatchDetail;
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

class DispatchesSummaryByLineQuantityExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithDrawings, WithEvents, WithStyles, WithTitle
{
    protected Collection $groupedData;

    protected float $grandTotal = 0;

    public function __construct(
        protected int $companyId,
        protected ?string $startDate = null,
        protected ?string $endDate = null
    ) {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');
    }

    public function collection(): Collection
    {
        $query = DispatchDetail::query()
            ->select([
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
                DB::raw('SUM(dispatch_details.quantity) as total_quantity'),
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $this->companyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado'])
            ->where('dispatches.document_date', '>=', $this->startDate)
            ->where('dispatches.document_date', '<=', $this->endDate)
            ->groupBy([
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'product_categories.parent_id',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->orderBy('parent_categories.legacy_code')
            ->orderBy('product_categories.legacy_code')
            ->get();

        $this->groupedData = $query->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();

            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'lines' => $items,
                'subtotal' => $items->sum('total_quantity'),
            ];
        });

        $this->grandTotal = $query->sum('total_quantity');

        return collect();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 20,
            'C' => 20,
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
        return 'Resumen Salidas por Línea (Cant)';
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
                $sheet->setCellValue('A4', 'REPORTE: RESUMEN SALIDAS POR LINEA (CANTIDAD)');
                $sheet->setCellValue('A5', 'PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y'));
                $sheet->setCellValue('A7', 'Generado: '.now()->format('d/m/Y H:i'));

                // Merge header cells
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('A4:C4');
                $sheet->mergeCells('A5:C5');
                $sheet->mergeCells('A7:C7');

                // Apply header styles
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1e3a5f');
                $sheet->getStyle('A2:C5')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

                // Data starting from row 9
                $currentRow = 9;

                foreach ($this->groupedData as $group) {
                    // Category header
                    $sheet->setCellValue("A{$currentRow}", "Categoría: {$group->parent_name} - {$group->parent_code}");
                    $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
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
                    $sheet->setCellValue("A{$currentRow}", 'Línea Presupuestaria');
                    $sheet->setCellValue("B{$currentRow}", 'Código de Línea');
                    $sheet->setCellValue("C{$currentRow}", 'Cantidad Mensual');
                    $sheet->getStyle("A{$currentRow}:C{$currentRow}")->applyFromArray([
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
                    foreach ($group->lines as $line) {
                        $sheet->setCellValue("A{$currentRow}", $line->category_name ?? 'Sin Línea');
                        $sheet->setCellValue("B{$currentRow}", $line->category_code ?? '-');
                        $sheet->setCellValue("C{$currentRow}", $line->total_quantity);
                        $sheet->getStyle("C{$currentRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                        $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal('right');
                        $currentRow++;
                    }

                    // Subtotal row
                    $sheet->setCellValue("A{$currentRow}", '');
                    $sheet->setCellValue("B{$currentRow}", "Subtotal {$group->parent_name}");
                    $sheet->setCellValue("C{$currentRow}", $group->subtotal);
                    $sheet->getStyle("A{$currentRow}:C{$currentRow}")->applyFromArray([
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
                    $sheet->getStyle("C{$currentRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal('right');
                    $currentRow += 2;
                }

                // Grand total
                $sheet->setCellValue("B{$currentRow}", 'Cantidad Total Mensual');
                $sheet->setCellValue("C{$currentRow}", $this->grandTotal);
                $sheet->getStyle("A{$currentRow}:C{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1e3a5f'],
                    ],
                ]);
                $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal('right');
                $sheet->getStyle("C{$currentRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal('right');

                // Signatures
                $signatureRow = $currentRow + 5;
                $sheet->setCellValue("A{$signatureRow}", '________________________');
                $sheet->setCellValue("B{$signatureRow}", '________________________');
                $sheet->setCellValue("C{$signatureRow}", '________________________');

                $labelRow = $signatureRow + 1;
                $sheet->setCellValue("A{$labelRow}", 'Elaborado');
                $sheet->setCellValue("B{$labelRow}", 'Revisado');
                $sheet->setCellValue("C{$labelRow}", 'Autorizado');

                $sheet->getStyle("A{$labelRow}:C{$labelRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$signatureRow}:C{$signatureRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$labelRow}:C{$labelRow}")->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
