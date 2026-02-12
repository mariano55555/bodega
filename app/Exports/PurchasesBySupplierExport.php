<?php

namespace App\Exports;

use App\Models\Purchase;
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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchasesBySupplierExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithDrawings, WithEvents, WithStyles, WithTitle
{
    protected Collection $supplierData;

    protected array $totals;

    public function __construct(
        protected int $companyId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        protected ?int $supplierId = null,
        protected ?string $acquisitionType = null
    ) {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');
    }

    public function collection(): Collection
    {
        $query = Purchase::query()
            ->select([
                'supplier_id',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(subtotal) as subtotal_amount'),
                DB::raw('SUM(tax_amount) as tax_amount'),
                DB::raw('SUM(discount_amount) as discount_amount'),
            ])
            ->where('company_id', $this->companyId)
            ->whereIn('status', ['aprobado', 'recibido'])
            ->where('document_date', '>=', $this->startDate)
            ->where('document_date', '<=', $this->endDate)
            ->with(['supplier:id,name,tax_id'])
            ->groupBy('supplier_id');

        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }

        if ($this->acquisitionType) {
            $query->where('acquisition_type', $this->acquisitionType);
        }

        $this->supplierData = $query->orderByDesc('total_amount')->get();

        $this->totals = [
            'total_invoices' => $this->supplierData->sum('invoice_count'),
            'total_amount' => $this->supplierData->sum('total_amount'),
            'subtotal_amount' => $this->supplierData->sum('subtotal_amount'),
            'tax_amount' => $this->supplierData->sum('tax_amount'),
            'discount_amount' => $this->supplierData->sum('discount_amount'),
            'total_suppliers' => $this->supplierData->count(),
        ];

        return $this->supplierData;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 40,
            'C' => 18,
            'D' => 12,
            'E' => 18,
            'F' => 16,
            'G' => 16,
            'H' => 18,
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
        return 'Compras por Proveedor';
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
                $sheet->setCellValue('A4', 'REPORTE DE COMPRAS POR PROVEEDOR');
                $sheet->setCellValue('A5', 'PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y'));
                $sheet->setCellValue('A7', 'Generado: '.now()->format('d/m/Y H:i'));

                // Merge header cells
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');
                $sheet->mergeCells('A4:H4');
                $sheet->mergeCells('A5:H5');
                $sheet->mergeCells('A7:H7');

                // Header styles
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1e3a5f');
                $sheet->getStyle('A2:H5')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

                // Column headers at row 9
                $headerRow = 9;
                $headers = ['#', 'Proveedor', 'Documento', 'Facturas', 'Subtotal', 'IVA', 'Descuento', 'Total'];
                $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

                foreach ($headers as $i => $header) {
                    $sheet->setCellValue("{$columns[$i]}{$headerRow}", $header);
                }

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1e3a5f'],
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                $sheet->getStyle("D{$headerRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("E{$headerRow}:H{$headerRow}")->getAlignment()->setHorizontal('right');

                // Data rows
                $currentRow = $headerRow + 1;
                $moneyFormat = '$#,##0.00';

                foreach ($this->supplierData as $index => $data) {
                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $data->supplier->name);
                    $sheet->setCellValue("C{$currentRow}", $data->supplier->tax_id ?? '-');
                    $sheet->setCellValue("D{$currentRow}", $data->invoice_count);
                    $sheet->setCellValue("E{$currentRow}", $data->subtotal_amount);
                    $sheet->setCellValue("F{$currentRow}", $data->tax_amount);
                    $sheet->setCellValue("G{$currentRow}", $data->discount_amount);
                    $sheet->setCellValue("H{$currentRow}", $data->total_amount);

                    // Styles
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB('1e3a5f');
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal('center');

                    foreach (['E', 'F', 'G', 'H'] as $col) {
                        $sheet->getStyle("{$col}{$currentRow}")->getNumberFormat()->setFormatCode($moneyFormat);
                        $sheet->getStyle("{$col}{$currentRow}")->getAlignment()->setHorizontal('right');
                    }

                    $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true);

                    // Zebra striping
                    if ($index % 2 === 1) {
                        $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'f5f5f5'],
                            ],
                        ]);
                    }

                    // Light border
                    $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                        'borders' => [
                            'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'dddddd']],
                        ],
                    ]);

                    $currentRow++;
                }

                // Total row
                $sheet->setCellValue("A{$currentRow}", '');
                $sheet->setCellValue("B{$currentRow}", 'TOTAL');
                $sheet->setCellValue("C{$currentRow}", '');
                $sheet->setCellValue("D{$currentRow}", $this->totals['total_invoices']);
                $sheet->setCellValue("E{$currentRow}", $this->totals['subtotal_amount']);
                $sheet->setCellValue("F{$currentRow}", $this->totals['tax_amount']);
                $sheet->setCellValue("G{$currentRow}", $this->totals['discount_amount']);
                $sheet->setCellValue("H{$currentRow}", $this->totals['total_amount']);

                $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1e3a5f'],
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_DOUBLE],
                    ],
                ]);

                $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal('center');
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}{$currentRow}")->getNumberFormat()->setFormatCode($moneyFormat);
                    $sheet->getStyle("{$col}{$currentRow}")->getAlignment()->setHorizontal('right');
                }

                // Signatures
                $signatureRow = $currentRow + 5;
                $sheet->setCellValue("B{$signatureRow}", '________________________');
                $sheet->setCellValue("D{$signatureRow}", '________________________');
                $sheet->setCellValue("G{$signatureRow}", '________________________');

                $labelRow = $signatureRow + 1;
                $sheet->setCellValue("B{$labelRow}", 'Elaborado');
                $sheet->setCellValue("D{$labelRow}", 'Revisado');
                $sheet->setCellValue("G{$labelRow}", 'Autorizado');

                $sheet->getStyle("B{$labelRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$labelRow}")->getFont()->setBold(true);
                $sheet->getStyle("G{$labelRow}")->getFont()->setBold(true);

                foreach (['B', 'D', 'G'] as $col) {
                    $sheet->getStyle("{$col}{$signatureRow}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("{$col}{$labelRow}")->getAlignment()->setHorizontal('center');
                }
            },
        ];
    }
}
