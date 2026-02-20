<?php

namespace App\Exports;

use App\Models\DispatchDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DispatchesMonthlyExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $dataStartRow = 9;

    protected int $rowCount = 0;

    public function __construct(
        protected int $companyId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        protected ?int $warehouseId = null
    ) {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');
    }

    public function collection(): Collection
    {
        $query = DispatchDetail::query()
            ->select([
                'dispatch_details.*',
                'dispatches.document_date',
                'dispatches.dispatch_number',
                'dispatches.warehouse_id',
                'dispatches.area_id',
                'products.name as product_name',
                'products.sku',
                'products.category_id',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'warehouses.name as warehouse_name',
                'areas.name as area_name',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'parent_categories.name as parent_category_name',
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'dispatch_details.unit_of_measure_id', '=', 'units_of_measure.id')
            ->join('warehouses', 'dispatches.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('areas', 'dispatches.area_id', '=', 'areas.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $this->companyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado'])
            ->where('dispatches.document_date', '>=', $this->startDate)
            ->where('dispatches.document_date', '<=', $this->endDate);

        if ($this->warehouseId) {
            $query->where('dispatches.warehouse_id', $this->warehouseId);
        }

        $data = $query
            ->orderBy('warehouses.name')
            ->orderBy('dispatches.document_date')
            ->get();

        $this->rowCount = $data->count();

        return $data;
    }

    public function headings(): array
    {
        return [
            [''],
            ['ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"'],
            ['GERENCIA ADMINISTRATIVA'],
            ['REPORTE MENSUAL DE SALIDAS DE BODEGA'],
            ['PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y')],
            [''],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            [
                'Fecha Despacho',
                'N° Despacho',
                'Bodega',
                'Área Solicitante',
                'Línea Presupuestaria',
                'Específico',
                'Descripción del Producto',
                'Unidad de Medida',
                'Cantidad',
                'Valor',
            ],
        ];
    }

    public function map($row): array
    {
        return [
            \Carbon\Carbon::parse($row->document_date)->format('d/m/Y'),
            $row->dispatch_number,
            $row->warehouse_name,
            $row->area_name ?? '-',
            $row->parent_category_name ?? $row->category_name ?? '-',
            $row->category_code ?? '-',
            $row->product_name."\n".$row->sku,
            $row->unit_abbreviation ?? $row->unit_name ?? '-',
            number_format($row->quantity, 2),
            number_format($row->total, 2),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 15,
            'C' => 18,
            'D' => 20,
            'E' => 20,
            'F' => 10,
            'G' => 30,
            'H' => 10,
            'I' => 10,
            'J' => 12,
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
        return 'Salidas Mensuales';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            2 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1e3a5f']],
                'alignment' => ['horizontal' => 'center'],
            ],
            3 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => 'center'],
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => 'center'],
            ],
            5 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => 'center'],
            ],
            9 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e3a5f'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge header cells
                $sheet->mergeCells('A2:J2');
                $sheet->mergeCells('A3:J3');
                $sheet->mergeCells('A4:J4');
                $sheet->mergeCells('A5:J5');
                $sheet->mergeCells('A7:J7');

                // Align numbers to right
                $lastRow = $this->dataStartRow + $this->rowCount;
                $sheet->getStyle("I{$this->dataStartRow}:J{$lastRow}")->getAlignment()->setHorizontal('right');

                // Add borders to data
                $sheet->getStyle("A{$this->dataStartRow}:J{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Add total row
                $totalRow = $lastRow + 2;
                $sheet->setCellValue("H{$totalRow}", 'Total Mensual:');
                $sheet->setCellValue("I{$totalRow}", '=SUM(I'.$this->dataStartRow.':I'.$lastRow.')');
                $sheet->setCellValue("J{$totalRow}", '=SUM(J'.$this->dataStartRow.':J'.$lastRow.')');
                $sheet->getStyle("H{$totalRow}:J{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1e3a5f'],
                    ],
                ]);

                // Format currency column
                $sheet->getStyle("J{$this->dataStartRow}:J{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Signatures
                $signatureRow = $totalRow + 5;
                $sheet->setCellValue("B{$signatureRow}", '________________________');
                $sheet->setCellValue("E{$signatureRow}", '________________________');
                $sheet->setCellValue("H{$signatureRow}", '________________________');

                $labelRow = $signatureRow + 1;
                $sheet->setCellValue("B{$labelRow}", 'Elaborado');
                $sheet->setCellValue("E{$labelRow}", 'Revisado');
                $sheet->setCellValue("H{$labelRow}", 'Autorizado');

                $sheet->getStyle("B{$labelRow}:H{$labelRow}")->getFont()->setBold(true);
                $sheet->getStyle("B{$signatureRow}:H{$signatureRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("B{$labelRow}:H{$labelRow}")->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
