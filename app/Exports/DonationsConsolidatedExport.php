<?php

namespace App\Exports;

use App\Http\Controllers\DonationReportController;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class DonationsConsolidatedExport implements WithDrawings, WithEvents, WithTitle
{
    public function __construct(
        protected int $companyId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        protected ?int $donorId = null,
        protected ?int $warehouseId = null,
        protected ?int $categoryId = null,
    ) {
        $this->startDate = $startDate ?? now()->startOfYear()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfYear()->format('Y-m-d');
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
        return 'Donaciones Consolidado';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $data = DonationReportController::getConsolidatedData(
                    $this->companyId,
                    $this->startDate,
                    $this->endDate,
                    $this->donorId,
                    $this->warehouseId,
                    $this->categoryId,
                );

                $donorData = $data['donorData'];
                $categoryData = $data['categoryData'];
                $monthlyTrend = $data['monthlyTrend'];
                $totals = $data['totals'];

                // Column widths
                $widths = ['A' => 6, 'B' => 40, 'C' => 18, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 18, 'H' => 14];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Header
                $sheet->setCellValue('A2', 'ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"');
                $sheet->setCellValue('A3', 'GERENCIA ADMINISTRATIVA');
                $sheet->setCellValue('A4', 'REPORTE CONSOLIDADO DE DONACIONES');
                $sheet->setCellValue('A5', 'PERIODO: DEL '.\Carbon\Carbon::parse($this->startDate)->format('d/m/Y').' AL '.\Carbon\Carbon::parse($this->endDate)->format('d/m/Y'));
                $sheet->setCellValue('A7', 'Generado: '.now()->format('d/m/Y H:i'));

                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');
                $sheet->mergeCells('A4:H4');
                $sheet->mergeCells('A5:H5');
                $sheet->mergeCells('A7:H7');

                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1e3a5f');
                $sheet->getStyle('A2:H5')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A7')->getAlignment()->setHorizontal('center');

                $moneyFormat = '$#,##0.00';
                $row = 9;

                // Summary
                $sheet->setCellValue("A{$row}", 'RESUMEN');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                    'alignment' => ['horizontal' => 'center'],
                ]);
                $row++;

                $sheet->setCellValue("A{$row}", 'Total Donaciones');
                $sheet->setCellValue("B{$row}", $totals['total_donations']);
                $sheet->setCellValue("C{$row}", 'Total Donantes');
                $sheet->setCellValue("D{$row}", $totals['total_donors']);
                $sheet->setCellValue("E{$row}", 'Valor Total');
                $sheet->setCellValue("F{$row}", $totals['total_value']);
                $sheet->setCellValue("G{$row}", 'Promedio');
                $sheet->setCellValue("H{$row}", $totals['average_donation']);
                $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                $row += 2;

                // Donations by Donor
                if ($donorData->isNotEmpty()) {
                    $sheet->setCellValue("A{$row}", 'DONACIONES POR DONANTE');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                    $row++;

                    $headers = ['#', 'Donante', 'Documento', 'Donaciones', 'Categorías', 'Productos', 'Valor Estimado', '% del Total'];
                    foreach ($headers as $i => $h) {
                        $sheet->setCellValue(chr(65 + $i)."{$row}", $h);
                    }
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("G{$row}:H{$row}")->getAlignment()->setHorizontal('right');
                    $row++;

                    $rank = 1;
                    foreach ($donorData as $data) {
                        $sheet->setCellValue("A{$row}", $rank);
                        $sheet->setCellValue("B{$row}", $data['donor_name']);
                        $sheet->setCellValue("C{$row}", $data['donor']->tax_id ?? '-');
                        $sheet->setCellValue("D{$row}", $data['donation_count']);
                        $sheet->setCellValue("E{$row}", $data['categories']->unique()->count());
                        $sheet->setCellValue("F{$row}", $data['products']->count());
                        $sheet->setCellValue("G{$row}", $data['total_value']);
                        $sheet->setCellValue("H{$row}", $totals['total_value'] > 0 ? ($data['total_value'] / $totals['total_value']) : 0);

                        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('1e3a5f');
                        $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal('center');
                        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal('right');
                        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('0.0%');
                        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal('right');

                        if ($rank % 2 === 0) {
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f5f5f5']],
                            ]);
                        }
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'dddddd']]],
                        ]);

                        $rank++;
                        $row++;
                    }

                    // Total
                    $sheet->setCellValue("B{$row}", 'TOTAL');
                    $sheet->setCellValue("D{$row}", $donorData->sum('donation_count'));
                    $sheet->setCellValue("G{$row}", $totals['total_value']);
                    $sheet->setCellValue("H{$row}", 1);
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE]],
                    ]);
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                    $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal('right');
                    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('0.0%');
                    $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal('right');
                    $row += 2;
                }

                // Donations by Category
                if ($categoryData->isNotEmpty()) {
                    $sheet->setCellValue("A{$row}", 'DONACIONES POR CATEGORÍA');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                    $row++;

                    $headers = ['#', 'Categoría', 'Cantidad Total', 'Valor Estimado', '% del Total'];
                    foreach ($headers as $i => $h) {
                        $sheet->setCellValue(chr(65 + $i)."{$row}", $h);
                    }
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal('right');
                    $row++;

                    $rank = 1;
                    foreach ($categoryData as $data) {
                        $sheet->setCellValue("A{$row}", $rank);
                        $sheet->setCellValue("B{$row}", $data['name']);
                        $sheet->setCellValue("C{$row}", $data['total_quantity']);
                        $sheet->setCellValue("D{$row}", $data['total_value']);
                        $sheet->setCellValue("E{$row}", $totals['total_value'] > 0 ? ($data['total_value'] / $totals['total_value']) : 0);

                        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('1e3a5f');
                        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.0%');
                        $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal('right');

                        if ($rank % 2 === 0) {
                            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f5f5f5']],
                            ]);
                        }
                        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'dddddd']]],
                        ]);

                        $rank++;
                        $row++;
                    }

                    // Total
                    $sheet->setCellValue("B{$row}", 'TOTAL');
                    $sheet->setCellValue("C{$row}", $categoryData->sum('total_quantity'));
                    $sheet->setCellValue("D{$row}", $categoryData->sum('total_value'));
                    $sheet->setCellValue("E{$row}", 1);
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE]],
                    ]);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.0%');
                    $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal('right');
                    $row += 2;
                }

                // Monthly trend
                if ($monthlyTrend->isNotEmpty()) {
                    $sheet->setCellValue("A{$row}", 'TENDENCIA MENSUAL');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                    $row++;

                    $headers = ['Mes', 'Donaciones', 'Valor Total'];
                    foreach ($headers as $i => $h) {
                        $sheet->setCellValue(chr(65 + $i)."{$row}", $h);
                    }
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal('right');
                    $row++;

                    foreach ($monthlyTrend as $i => $month) {
                        $monthLabel = ucfirst(\Carbon\Carbon::parse($month->month.'-01')->locale('es')->isoFormat('MMMM YYYY'));
                        $sheet->setCellValue("A{$row}", $monthLabel);
                        $sheet->setCellValue("B{$row}", $month->donation_count);
                        $sheet->setCellValue("C{$row}", $month->total_value);

                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal('center');
                        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal('right');
                        $sheet->getStyle("C{$row}")->getFont()->setBold(true);

                        if ($i % 2 === 1) {
                            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f5f5f5']],
                            ]);
                        }
                        $row++;
                    }
                    $row += 2;
                }

                // Signatures
                $signatureRow = $row + 5;
                $sheet->setCellValue("B{$signatureRow}", '________________________');
                $sheet->setCellValue("D{$signatureRow}", '________________________');
                $sheet->setCellValue("G{$signatureRow}", '________________________');

                $labelRow = $signatureRow + 1;
                $sheet->setCellValue("B{$labelRow}", 'Elaborado');
                $sheet->setCellValue("D{$labelRow}", 'Revisado');
                $sheet->setCellValue("G{$labelRow}", 'Autorizado');

                foreach (['B', 'D', 'G'] as $col) {
                    $sheet->getStyle("{$col}{$labelRow}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$signatureRow}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("{$col}{$labelRow}")->getAlignment()->setHorizontal('center');
                }
            },
        ];
    }
}
