<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras por Proveedor</title>
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
    </style>
</head>
<body>
    @if ($supplierData->isEmpty())
        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">REPORTE DE COMPRAS POR PROVEEDOR</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha de reporte:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
        <div class="no-data">No se encontraron compras en el período seleccionado</div>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <td colspan="8" class="header-cell">
                        <table class="header-table">
                            <tr>
                                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                                <td class="title-cell">
                                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                                    <div class="report-title">REPORTE DE COMPRAS POR PROVEEDOR</div>
                                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                                </td>
                                <td class="info-cell">
                                    <div class="page-info">Fecha de reporte:</div>
                                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                                </td>
                            </tr>
                        </table>
                        <div class="header-separator"></div>
                    </td>
                </tr>
                <tr class="columns-row">
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Proveedor</th>
                    <th style="width: 12%;">Documento</th>
                    <th class="center" style="width: 8%;">Facturas</th>
                    <th class="right" style="width: 13%;">Subtotal</th>
                    <th class="right" style="width: 10%;">IVA</th>
                    <th class="right" style="width: 10%;">Descuento</th>
                    <th class="right" style="width: 12%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="summary-row">
                    <td colspan="3" style="width: 33.33%;">
                        <div class="summary-label">Total Proveedores</div>
                        <div class="summary-value">{{ number_format($totals['total_suppliers']) }}</div>
                    </td>
                    <td colspan="2" style="width: 33.33%;">
                        <div class="summary-label">Total Facturas</div>
                        <div class="summary-value">{{ number_format($totals['total_invoices']) }}</div>
                    </td>
                    <td colspan="3" style="width: 33.33%;">
                        <div class="summary-label">Monto Total</div>
                        <div class="summary-value currency">${{ number_format($totals['total_amount'], 2) }}</div>
                    </td>
                </tr>
                @php $maxTotal = $supplierData->max('total_amount'); @endphp
                @foreach ($supplierData as $index => $data)
                    <tr>
                        <td class="rank">{{ $index + 1 }}</td>
                        <td>
                            {{ $data->supplier->name }}
                            @if ($maxTotal > 0)
                                <div class="percentage-bar">
                                    <div class="percentage-fill" style="width: {{ ($data->total_amount / $maxTotal) * 100 }}%"></div>
                                </div>
                            @endif
                        </td>
                        <td class="tax-id">{{ $data->supplier->tax_id ?? '-' }}</td>
                        <td class="center">{{ $data->invoice_count }}</td>
                        <td class="right currency">$ {{ number_format($data->subtotal_amount, 2) }}</td>
                        <td class="right currency">$ {{ number_format($data->tax_amount, 2) }}</td>
                        <td class="right currency">$ {{ number_format($data->discount_amount, 2) }}</td>
                        <td class="right currency" style="font-weight: bold;">$ {{ number_format($data->total_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">TOTAL</td>
                    <td class="center">{{ $totals['total_invoices'] }}</td>
                    <td class="right currency">$ {{ number_format($totals['subtotal_amount'], 2) }}</td>
                    <td class="right currency">$ {{ number_format($totals['tax_amount'], 2) }}</td>
                    <td class="right currency">$ {{ number_format($totals['discount_amount'], 2) }}</td>
                    <td class="right currency">$ {{ number_format($totals['total_amount'], 2) }}</td>
                </tr>
            </tbody>
        </table>

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
