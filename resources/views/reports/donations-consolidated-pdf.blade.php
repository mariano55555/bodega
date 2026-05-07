<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado de Donaciones</title>
    <style>
        @page {
            margin-top: 20mm;
            margin-right: 15mm;
            margin-bottom: 20mm;
            margin-left: 15mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; line-height: 1.3; margin: 0; padding: 0; }
        .footer { position: fixed; bottom: -20mm; left: 0px; right: 0px; text-align: center; font-size: 7px; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
        table.report-table { width: 95%; border-collapse: collapse; margin-left: auto; margin-right: auto; }
        .header-cell { padding: 10px 0 5px 0; border: none !important; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; border: none !important; }
        .logo-cell { width: 25%; }
        .logo { max-width: 180px; max-height: 60px; }
        .title-cell { width: 50%; text-align: center; }
        .info-cell { width: 25%; text-align: right; font-size: 8px; }
        .institution-name { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .department-name { font-size: 9px; color: #555; margin-bottom: 4px; }
        .report-title { font-size: 12px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .period { font-size: 9px; color: #333; }
        .page-info { font-size: 8px; color: #666; }
        .header-separator { border-bottom: 2px solid #1e3a5f; margin-top: 5px; }
        .columns-row { background-color: #1e3a5f; }
        .columns-row th { padding: 6px 10px; text-align: left; font-size: 8px; font-weight: bold; border-bottom: 1px solid #ccc; color: white; }
        .columns-row th.right { text-align: right; }
        .columns-row th.center { text-align: center; }
        table.report-table td { padding: 5px 10px; border-bottom: 1px solid #eee; font-size: 8px; vertical-align: top; }
        table.report-table td.right { text-align: right; }
        table.report-table td.center { text-align: center; }
        table.report-table tbody tr:nth-child(even) { background-color: #fafafa; }
        .summary-row td { border: 1px solid #ddd; padding: 8px 12px; }
        .summary-label { font-size: 8px; color: #666; }
        .summary-value { font-size: 12px; font-weight: bold; color: #1e3a5f; }
        .total-row td { background-color: #1e3a5f; color: white; font-weight: bold; padding: 8px 10px; border-top: 2px solid #1e3a5f; font-size: 10px; border-bottom: none; }
        .tax-id { font-size: 8px; color: #666; }
        .rank { font-weight: bold; color: #1e3a5f; }
        .percentage-bar { width: 100%; height: 5px; background-color: #e0e0e0; margin-top: 3px; }
        .percentage-fill { height: 5px; background-color: #1e3a5f; }
        .signatures { margin-top: 50px; page-break-inside: avoid; }
        .signatures-table { width: 100%; border-collapse: collapse; }
        .signatures-table td { width: 33.33%; text-align: center; padding: 0 30px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 8px; margin-top: 50px; font-size: 10px; font-weight: bold; }
        .no-data { text-align: center; padding: 40px; color: #999; font-style: italic; font-size: 11px; }
        .currency { font-family: 'DejaVu Sans', sans-serif; }
        .section-title { font-size: 11px; font-weight: bold; color: #1e3a5f; margin: 20px 0 8px 0; padding: 5px 12px; background-color: #f0f4f8; border-left: 3px solid #1e3a5f; width: 95%; margin-left: auto; margin-right: auto; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $hasData = $donorData->isNotEmpty() || $categoryData->isNotEmpty();
    @endphp

    {{-- Page Header --}}
    <table class="header-table" style="width: 95%; margin-left: auto; margin-right: auto;">
        <tr>
            <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
            <td class="title-cell">
                <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                <div class="report-title">REPORTE CONSOLIDADO DE DONACIONES</div>
                <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
            </td>
            <td class="info-cell">
                <div class="page-info">Fecha de reporte:</div>
                <div class="page-info">{{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
    <div class="header-separator" style="width: 95%; margin-left: auto; margin-right: auto;"></div>

    @if (! $hasData)
        <div class="no-data">No se encontraron donaciones en el período seleccionado</div>
    @else
        {{-- Summary --}}
        <table class="report-table" style="margin-top: 12px;">
            <tbody>
                <tr class="summary-row">
                    <td style="width: 25%;">
                        <div class="summary-label">Total Donaciones</div>
                        <div class="summary-value">{{ number_format($totals['total_donations']) }}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="summary-label">Total Donantes</div>
                        <div class="summary-value">{{ number_format($totals['total_donors']) }}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="summary-label">Valor Total Estimado</div>
                        <div class="summary-value currency">${{ number_format($totals['total_value'], 2) }}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="summary-label">Promedio por Donación</div>
                        <div class="summary-value currency">${{ number_format($totals['average_donation'], 2) }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Donations by Donor --}}
        @if ($donorData->isNotEmpty())
            <div class="section-title">DONACIONES POR DONANTE</div>
            <table class="report-table">
                <thead>
                    <tr class="columns-row">
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">Donante</th>
                        <th style="width: 12%;">Documento</th>
                        <th class="center" style="width: 10%;">Donaciones</th>
                        <th class="center" style="width: 11%;">Categorías</th>
                        <th class="center" style="width: 11%;">Productos</th>
                        <th class="right" style="width: 11%;">Valor Estimado</th>
                        <th class="right" style="width: 10%;">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxValue = $donorData->max('total_value'); @endphp
                    @foreach ($donorData as $index => $data)
                        <tr>
                            <td class="rank">{{ $loop->iteration }}</td>
                            <td>
                                {{ $data['donor_name'] }}
                                @if ($maxValue > 0)
                                    <div class="percentage-bar">
                                        <div class="percentage-fill" style="width: {{ ($data['total_value'] / $maxValue) * 100 }}%"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="tax-id">{{ $data['donor']->tax_id ?? '-' }}</td>
                            <td class="center">{{ $data['donation_count'] }}</td>
                            <td class="center">{{ $data['categories']->unique()->count() }}</td>
                            <td class="center">{{ $data['products']->count() }}</td>
                            <td class="right currency" style="font-weight: bold;">$ {{ number_format($data['total_value'], 2) }}</td>
                            <td class="right">
                                {{ $totals['total_value'] > 0 ? number_format(($data['total_value'] / $totals['total_value']) * 100, 1) : '0.0' }}%
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3">TOTAL</td>
                        <td class="center">{{ $donorData->sum('donation_count') }}</td>
                        <td colspan="2"></td>
                        <td class="right currency">$ {{ number_format($totals['total_value'], 2) }}</td>
                        <td class="right">100.0%</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Donations by Category --}}
        @if ($categoryData->isNotEmpty())
            <div class="section-title" style="margin-top: 25px;">DONACIONES POR CATEGORÍA</div>
            <table class="report-table">
                <thead>
                    <tr class="columns-row">
                        <th style="width: 6%;">#</th>
                        <th style="width: 50%;">Categoría</th>
                        <th class="right" style="width: 14%;">Cantidad Total</th>
                        <th class="right" style="width: 15%;">Valor Estimado</th>
                        <th class="right" style="width: 15%;">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categoryData as $data)
                        <tr>
                            <td class="rank">{{ $loop->iteration }}</td>
                            <td>{{ $data['name'] }}</td>
                            <td class="right">{{ number_format($data['total_quantity'], 2) }}</td>
                            <td class="right currency" style="font-weight: bold;">$ {{ number_format($data['total_value'], 2) }}</td>
                            <td class="right">
                                {{ $totals['total_value'] > 0 ? number_format(($data['total_value'] / $totals['total_value']) * 100, 1) : '0.0' }}%
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td class="right">{{ number_format($categoryData->sum('total_quantity'), 2) }}</td>
                        <td class="right currency">$ {{ number_format($categoryData->sum('total_value'), 2) }}</td>
                        <td class="right">100.0%</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Monthly Trend --}}
        @if ($monthlyTrend->isNotEmpty())
            <div class="section-title" style="margin-top: 25px;">TENDENCIA MENSUAL</div>
            <table class="report-table">
                <thead>
                    <tr class="columns-row">
                        <th style="width: 30%;">Mes</th>
                        <th class="center" style="width: 20%;">Donaciones</th>
                        <th class="right" style="width: 25%;">Valor Total</th>
                        <th style="width: 25%;">Distribución</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxMonthValue = $monthlyTrend->max('total_value'); @endphp
                    @foreach ($monthlyTrend as $month)
                        <tr>
                            <td>{{ ucfirst(\Carbon\Carbon::parse($month->month.'-01')->locale('es')->isoFormat('MMMM YYYY')) }}</td>
                            <td class="center">{{ $month->donation_count }}</td>
                            <td class="right currency" style="font-weight: bold;">$ {{ number_format($month->total_value, 2) }}</td>
                            <td>
                                @if ($maxMonthValue > 0)
                                    <div class="percentage-bar" style="margin-top: 5px;">
                                        <div class="percentage-fill" style="width: {{ ($month->total_value / $maxMonthValue) * 100 }}%"></div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="signatures">
            <table class="signatures-table">
                <tr>
                    <td><div class="signature-line">Elaborado</div></td>
                    <td><div class="signature-line">Revisado</div></td>
                    <td><div class="signature-line">Autorizado</div></td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">Generado el {{ now()->format('d/m/Y H:i') }} | {{ auth()->user()->name }} | Sistema de Bodega</div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans");
            $size = 7;
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 24;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>
</body>
</html>
