<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual de Salidas de Bodega</title>
    <style>
        @page {
            margin-top: 20mm;
            margin-right: 15mm;
            margin-bottom: 20mm;
            margin-left: 15mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #333; line-height: 1.3; margin: 0; padding: 0; }
        .footer { position: fixed; bottom: -20mm; left: 0px; right: 0px; text-align: center; font-size: 7px; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
        table.report-table { width: 95%; border-collapse: collapse; margin-left: auto; margin-right: auto; }
        .header-cell { padding: 10px 0 5px 0; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 20%; }
        .logo { max-width: 150px; max-height: 50px; }
        .title-cell { width: 60%; text-align: center; }
        .info-cell { width: 20%; text-align: right; font-size: 8px; }
        .institution-name { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .department-name { font-size: 9px; color: #555; margin-bottom: 4px; }
        .report-title { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .period { font-size: 9px; color: #333; }
        .page-info { font-size: 8px; color: #666; }
        .header-separator { border-bottom: 2px solid #1e3a5f; margin-top: 5px; }
        .columns-row { background-color: #1e3a5f; }
        .columns-row th { padding: 5px 3px; text-align: left; font-size: 7px; font-weight: bold; border-bottom: 1px solid #ccc; color: white; }
        .columns-row th.right { text-align: right; }
        table.report-table td { padding: 4px 3px; border-bottom: 1px solid #eee; font-size: 7px; vertical-align: top; }
        table.report-table td.right { text-align: right; }
        table.report-table tbody tr:nth-child(even) { background-color: #fafafa; }
        .section-header-row td { background-color: #f5f5f5; padding: 6px 10px; font-size: 10px; font-weight: bold; color: #1e3a5f; border-left: 4px solid #d97706; border-bottom: none; }
        .subtotal-row { background-color: #fff7ed; }
        .subtotal-row td { padding: 4px 3px; border-top: 1px solid #fed7aa; font-size: 8px; font-weight: bold; }
        .spacer-row td { padding: 8px 0; border-bottom: none; }
        .grand-total-row td { background-color: #1e3a5f; color: white; padding: 8px 3px; font-size: 9px; font-weight: bold; border-bottom: none; }
        .signatures { margin-top: 40px; page-break-inside: avoid; }
        .signatures-table { width: 100%; border-collapse: collapse; }
        .signatures-table td { width: 33.33%; text-align: center; padding: 0 20px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; margin-top: 40px; font-size: 9px; font-weight: bold; }
        .no-data { text-align: center; padding: 30px; color: #999; font-style: italic; font-size: 10px; }
    </style>
</head>
<body>
    @if ($groupedByWarehouse->isEmpty())
        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">REPORTE MENSUAL DE SALIDAS DE BODEGA</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha de reporte:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
        <div class="no-data">No se encontraron salidas en el período seleccionado</div>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <td colspan="10" class="header-cell">
                        <table class="header-table">
                            <tr>
                                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                                <td class="title-cell">
                                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                                    <div class="report-title">REPORTE MENSUAL DE SALIDAS DE BODEGA</div>
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
                    <th style="width: 8%;">Fecha Despacho</th>
                    <th style="width: 10%;">N° Despacho</th>
                    <th style="width: 8%;">Bodega</th>
                    <th style="width: 12%;">Área Solicitante</th>
                    <th style="width: 12%;">Línea Presupuestaria</th>
                    <th style="width: 8%;">Específico</th>
                    <th style="width: 18%;">Descripción del Producto</th>
                    <th style="width: 6%;">Unidad</th>
                    <th class="right" style="width: 8%;">Cantidad</th>
                    <th class="right" style="width: 10%;">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedByWarehouse as $warehouseName => $group)
                    <tr class="section-header-row">
                        <td colspan="10">{{ $group->warehouse_name }}</td>
                    </tr>
                    @foreach ($group->items as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->document_date)->format('d/m/Y') }}</td>
                            <td>{{ $item->dispatch_number }}</td>
                            <td>{{ $item->warehouse_name }}</td>
                            <td>{{ $item->area_name ?? '-' }}</td>
                            <td>{{ $item->parent_category_name ?? $item->category_name ?? '-' }}</td>
                            <td>{{ $item->category_code ?? '-' }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->unit_abbreviation ?? $item->unit_name ?? '-' }}</td>
                            <td class="right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="right">$ {{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="8" style="text-align: right;">Cantidad Mensual: {{ number_format($group->total_quantity, 2) }}</td>
                        <td colspan="2" class="right">Valor: $ {{ number_format($group->total_value, 2) }}</td>
                    </tr>
                    <tr class="spacer-row">
                        <td colspan="10"></td>
                    </tr>
                @endforeach
                <tr class="grand-total-row">
                    <td colspan="8" style="text-align: right;">Total Mensual</td>
                    <td class="right">{{ number_format($totals['total_quantity'], 2) }}</td>
                    <td class="right">${{ number_format($totals['total_value'], 2) }}</td>
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
