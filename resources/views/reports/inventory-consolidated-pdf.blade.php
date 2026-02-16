<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Inventario Consolidado</title>
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
        .logo-cell { width: 25%; }
        .logo { max-width: 180px; max-height: 60px; }
        .title-cell { width: 50%; text-align: center; }
        .info-cell { width: 25%; text-align: right; font-size: 8px; }
        .institution-name { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .department-name { font-size: 9px; color: #555; margin-bottom: 4px; }
        .report-title { font-size: 12px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .warehouse-info { font-size: 10px; font-weight: bold; color: #92400e; margin-top: 3px; }
        .period { font-size: 9px; color: #333; }
        .page-info { font-size: 8px; color: #666; }
        .header-separator { border-bottom: 2px solid #1e3a5f; margin-top: 5px; }
        .columns-row { background-color: #2d4a6f; }
        .columns-row th { padding: 5px 6px; text-align: left; font-size: 7px; font-weight: bold; border: 1px solid #1e3a5f; color: white; }
        .columns-row th.right { text-align: right; }
        .columns-row th.center { text-align: center; }
        table.report-table td { padding: 4px 6px; border: 1px solid #ddd; font-size: 7.5px; vertical-align: top; }
        table.report-table td.right { text-align: right; }
        table.report-table td.center { text-align: center; }
        table.report-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .section-header-row td { background-color: #1e3a5f; color: white; padding: 6px 10px; font-size: 9px; font-weight: bold; border: 1px solid #1e3a5f; }
        .subtotal-row { background-color: #e8e8e8; font-weight: bold; }
        .subtotal-row td { padding: 6px; border-top: 2px solid #999; font-size: 8px; }
        .spacer-row td { padding: 6px 0; border: none; }
        .grand-total-row td { background-color: #1e3a5f; color: white; padding: 8px 6px; font-size: 8px; font-weight: bold; border: 1px solid #1e3a5f; }
        .signatures { margin-top: 50px; page-break-inside: avoid; }
        .signatures-table { width: 100%; border-collapse: collapse; }
        .signatures-table td { width: 33.33%; text-align: center; padding: 0 30px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 8px; margin-top: 50px; font-size: 9px; font-weight: bold; }
        .no-data { text-align: center; padding: 40px; color: #999; font-style: italic; font-size: 10px; }
    </style>
</head>
<body>
    @if ($groupedByCategory->isEmpty())
        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">REPORTE INVENTARIO CONSOLIDADO</div>
                    <div class="warehouse-info">{{ $warehouseName }}</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha de reporte:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                    <div class="page-info" style="margin-top: 5px;">Hora: {{ now()->format('H:i') }}</div>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
        <div class="no-data">No se encontraron movimientos en el período seleccionado</div>
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
                                    <div class="report-title">REPORTE INVENTARIO CONSOLIDADO</div>
                                    <div class="warehouse-info">{{ $warehouseName }}</div>
                                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                                </td>
                                <td class="info-cell">
                                    <div class="page-info">Fecha de reporte:</div>
                                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                                    <div class="page-info" style="margin-top: 5px;">Hora: {{ now()->format('H:i') }}</div>
                                </td>
                            </tr>
                        </table>
                        <div class="header-separator"></div>
                    </td>
                </tr>
                <tr class="columns-row">
                    <th style="width: 25%;">Descripción del Producto</th>
                    <th class="center" style="width: 8%;">Unidad de Medida</th>
                    <th class="right" style="width: 10%;">Existencia Inicial</th>
                    <th class="right" style="width: 9%;">Entradas</th>
                    <th class="right" style="width: 9%;">Salidas</th>
                    <th class="right" style="width: 10%;">Existencia Actual</th>
                    <th class="right" style="width: 10%;">Precio Unitario</th>
                    <th class="right" style="width: 12%;">Costo Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedByCategory as $parentName => $group)
                    <tr class="section-header-row">
                        <td colspan="8">Línea Presupuestaria: {{ $group->parent_name }} - Específico {{ $group->parent_code }}</td>
                    </tr>
                    @foreach ($group->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="center">{{ $item->unit }}</td>
                            <td class="right">{{ number_format($item->initial_stock, 2) }}</td>
                            <td class="right">{{ number_format($item->entries, 2) }}</td>
                            <td class="right">{{ number_format($item->exits, 2) }}</td>
                            <td class="right" style="font-weight: bold;">{{ number_format($item->current_stock, 2) }}</td>
                            <td class="right">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="right" style="font-weight: bold;">${{ number_format($item->total_cost, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align: right;">Total Línea {{ $group->parent_code }}</td>
                        <td class="right">{{ number_format($group->subtotals->initial_stock, 2) }}</td>
                        <td class="right">{{ number_format($group->subtotals->entries, 2) }}</td>
                        <td class="right">{{ number_format($group->subtotals->exits, 2) }}</td>
                        <td class="right">{{ number_format($group->subtotals->current_stock, 2) }}</td>
                        <td></td>
                        <td class="right">${{ number_format($group->subtotals->total_cost, 2) }}</td>
                    </tr>
                    <tr class="spacer-row">
                        <td colspan="8"></td>
                    </tr>
                @endforeach
                <tr class="grand-total-row">
                    <td colspan="2" style="text-align: right;">TOTALES DEL PERÍODO</td>
                    <td class="right">{{ number_format($totals['initial_stock'], 2) }}</td>
                    <td class="right">{{ number_format($totals['entries'], 2) }}</td>
                    <td class="right">{{ number_format($totals['exits'], 2) }}</td>
                    <td class="right">{{ number_format($totals['current_stock'], 2) }}</td>
                    <td></td>
                    <td class="right">${{ number_format($totals['total_cost'], 2) }}</td>
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
