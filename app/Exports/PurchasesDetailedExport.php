<?php

namespace App\Exports;

use App\Models\PurchaseDetail;
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

class PurchasesDetailedExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $dataStartRow = 9;

    protected int $rowCount = 0;

    public function __construct(
        protected int $companyId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        protected ?int $categoryId = null,
        protected ?int $supplierId = null
    ) {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');
    }

    public function collection(): Collection
    {
        $query = PurchaseDetail::query()
            ->select([
                'purchase_details.*',
                'purchases.document_date',
                'purchases.document_number',
                'purchases.supplier_id',
                'products.name as product_name',
                'products.category_id',
                'units_of_measure.abbreviation as unit_abbreviation',
                'units_of_measure.name as unit_name',
                'suppliers.name as supplier_name',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.name as parent_category_name',
                'parent_categories.legacy_code as parent_category_code',
            ])
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->join('products', 'purchase_details.product_id', '=', 'products.id')
            ->leftJoin('units_of_measure', 'products.unit_of_measure_id', '=', 'units_of_measure.id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('purchases.company_id', $this->companyId)
            ->whereIn('purchases.status', ['aprobado', 'recibido'])
            ->where('purchases.document_date', '>=', $this->startDate)
            ->where('purchases.document_date', '<=', $this->endDate);

        if ($this->categoryId) {
            $query->where('products.category_id', $this->categoryId);
        }

        if ($this->supplierId) {
            $query->where('purchases.supplier_id', $this->supplierId);
        }

        $data = $query
            ->orderBy('category_name')
            ->orderBy('supplier_name')
            ->orderBy('purchases.document_date')
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
            ['REPORTE MENSUAL DE COMPRAS DETALLADAS'],
            ['PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y')],
            [''],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            [
                'Fecha de compra',
                'Línea Presupuestaria',
                'Código de Línea',
                'Proveedor',
                'No. Factura',
                'Descripción',
                'Unidad de Medida',
                'Cantidad',
                'P. Unitario',
                'Valor Neto',
            ],
        ];
    }

    public function map($row): array
    {
        return [
            \Carbon\Carbon::parse($row->document_date)->format('d/m/Y'),
            $row->category_name ?? 'Sin Línea',
            $row->category_code ?? '',
            $row->supplier_name,
            $row->document_number ?? '-',
            $row->product_name,
            $row->unit_abbreviation ?? $row->unit_name ?? '-',
            number_format($row->quantity, 2),
            number_format($row->unit_cost, 2),
            number_format($row->total, 2),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 30,
            'C' => 12,
            'D' => 25,
            'E' => 12,
            'F' => 35,
            'G' => 12,
            'H' => 10,
            'I' => 12,
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
        return 'Compras Detalladas';
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
                $sheet->getStyle("H{$this->dataStartRow}:J{$lastRow}")->getAlignment()->setHorizontal('right');

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
                $sheet->setCellValue("I{$totalRow}", 'Total de compras:');
                $sheet->setCellValue("J{$totalRow}", '=SUM(J'.$this->dataStartRow.':J'.$lastRow.')');
                $sheet->getStyle("I{$totalRow}:J{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1e3a5f'],
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                ]);

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

                // Format currency columns
                $sheet->getStyle("I{$this->dataStartRow}:J{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');
            },
        ];
    }
}
