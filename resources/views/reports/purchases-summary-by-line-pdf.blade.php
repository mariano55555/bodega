<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Mensual por Línea Presupuestaria</title>
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
        .columns-row { background-color: #e8e8e8; }
        .columns-row th { padding: 6px 10px; text-align: left; font-size: 8px; font-weight: bold; border-bottom: 1px solid #ccc; color: #333; }
        .columns-row th.right { text-align: right; }
        table.report-table td { padding: 6px 10px; border-bottom: 1px solid #eee; font-size: 9px; vertical-align: top; }
        table.report-table td.right { text-align: right; }
        .section-header-row td { background-color: #f0f0f0; padding: 8px 12px; font-size: 10px; font-weight: bold; color: #1e3a5f; border-left: 4px solid #1e3a5f; border-bottom: none; }
        .subtotal-row { background-color: #f5f5f5; font-weight: bold; }
        .subtotal-row td { padding: 8px 10px; border-top: 2px solid #ddd; }
        .spacer-row td { padding: 8px 0; border-bottom: none; }
        .grand-total-row td { background-color: #1e3a5f; color: white; padding: 10px 10px; font-size: 11px; font-weight: bold; border-bottom: none; }
        .signatures { margin-top: 50px; page-break-inside: avoid; }
        .signatures-table { width: 100%; border-collapse: collapse; }
        .signatures-table td { width: 33.33%; text-align: center; padding: 0 30px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 8px; margin-top: 50px; font-size: 10px; font-weight: bold; }
        .no-data { text-align: center; padding: 40px; color: #999; font-style: italic; font-size: 11px; }
        .currency { font-family: 'DejaVu Sans', sans-serif; }
    </style>
</head>
<body>
    @if ($groupedByParent->isEmpty())
        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">REPORTE: RESUMEN MENSUAL POR LINEA PRESUPUESTARIA</div>
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
                    <td colspan="3" class="header-cell">
                        <table class="header-table">
                            <tr>
                                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                                <td class="title-cell">
                                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                                    <div class="report-title">REPORTE: RESUMEN MENSUAL POR LINEA PRESUPUESTARIA</div>
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
                    <th style="width: 50%;">Línea Presupuestaria</th>
                    <th style="width: 25%;">Código de Línea</th>
                    <th class="right" style="width: 25%;">Total Mensual</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedByParent as $parentName => $group)
                    <tr class="section-header-row">
                        <td colspan="3">Categoría: {{ $group->parent_name }} - {{ $group->parent_code }}</td>
                    </tr>
                    @foreach ($group->lines as $line)
                        <tr>
                            <td>{{ $line->category_name ?? 'Sin Línea' }}</td>
                            <td>{{ $line->category_code ?? '-' }}</td>
                            <td class="right currency">$ {{ number_format($line->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align: right;">Subtotal {{ $group->parent_name }}</td>
                        <td class="right currency">$ {{ number_format($group->subtotal, 2) }}</td>
                    </tr>
                    <tr class="spacer-row">
                        <td colspan="3"></td>
                    </tr>
                @endforeach
                <tr class="grand-total-row">
                    <td colspan="2" style="text-align: right;">Total Mensual</td>
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
