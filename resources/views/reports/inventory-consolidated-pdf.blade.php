<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Inventario Consolidado</title>
    <style>
        @page {
            margin-top: 5mm;
            margin-right: 20mm;
            margin-bottom: 20mm;
            margin-left: 20mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            padding-top: 110px;
        }

        .header {
            position: fixed;
            top: 0px;
            left: 0px;
            right: 0px;
            height: 100px;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 25%;
        }

        .logo {
            max-width: 180px;
            max-height: 60px;
        }

        .title-cell {
            width: 50%;
            text-align: center;
        }

        .info-cell {
            width: 25%;
            text-align: right;
            font-size: 8px;
        }

        .institution-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 3px;
        }

        .department-name {
            font-size: 9px;
            color: #555;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 3px;
        }

        .warehouse-info {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            margin-top: 3px;
        }

        .period {
            font-size: 9px;
            color: #333;
        }

        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .section-header {
            background-color: #1e3a5f;
            color: white;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 0;
        }

        .section-header-code {
            float: right;
            font-weight: bold;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        table.data-table thead {
            background-color: #2d4a6f;
        }

        table.data-table th {
            padding: 5px 6px;
            text-align: left;
            font-size: 7px;
            font-weight: bold;
            border: 1px solid #1e3a5f;
            color: white;
        }

        table.data-table th.right {
            text-align: right;
        }

        table.data-table th.center {
            text-align: center;
        }

        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 7.5px;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table td.center {
            text-align: center;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .subtotal-row {
            background-color: #e8e8e8 !important;
        }

        .subtotal-row td {
            padding: 6px;
            border-top: 2px solid #999;
            font-weight: bold;
            font-size: 8px;
        }

        .grand-total {
            background-color: #1e3a5f;
            color: white;
            padding: 10px 15px;
            margin-top: 20px;
        }

        .grand-total-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grand-total-table td {
            padding: 3px 0;
            font-size: 9px;
        }

        .grand-total-table .label {
            text-align: left;
            font-weight: bold;
        }

        .grand-total-table .value {
            text-align: right;
            width: 100px;
            font-size: 10px;
            font-weight: bold;
        }

        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            text-align: center;
            padding: 0 30px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 8px;
            margin-top: 50px;
            font-size: 9px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo">
                </td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">REPORTE INVENTARIO CONSOLIDADO</div>
                    <div class="warehouse-info">{{ $warehouseName }}</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div>Fecha de reporte:</div>
                    <div>{{ now()->format('d/m/Y') }}</div>
                    <div style="margin-top: 5px;">Hora: {{ now()->format('H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if ($groupedByCategory->isEmpty())
        <div class="no-data">
            No se encontraron movimientos en el período seleccionado
        </div>
    @else
        @foreach ($groupedByCategory as $parentName => $group)
            <div class="section">
                <div class="section-header">
                    Línea Presupuestaria: {{ $group->parent_name }}
                    <span class="section-header-code">Específico {{ $group->parent_code }}</span>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
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
                    </tbody>
                </table>

                <table class="data-table">
                    <tbody>
                        <tr class="subtotal-row">
                            <td style="width: 33%; text-align: right;">Total Línea {{ $group->parent_code }}</td>
                            <td class="right" style="width: 10%;">{{ number_format($group->subtotals->initial_stock, 2) }}</td>
                            <td class="right" style="width: 9%;">{{ number_format($group->subtotals->entries, 2) }}</td>
                            <td class="right" style="width: 9%;">{{ number_format($group->subtotals->exits, 2) }}</td>
                            <td class="right" style="width: 10%;">{{ number_format($group->subtotals->current_stock, 2) }}</td>
                            <td style="width: 10%;"></td>
                            <td class="right" style="width: 12%;">${{ number_format($group->subtotals->total_cost, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="grand-total">
            <table class="grand-total-table">
                <tr>
                    <td class="label">TOTALES DEL PERÍODO</td>
                    <td class="value">Inicial: {{ number_format($totals['initial_stock'], 2) }}</td>
                    <td class="value">Entradas: {{ number_format($totals['entries'], 2) }}</td>
                    <td class="value">Salidas: {{ number_format($totals['exits'], 2) }}</td>
                    <td class="value">Actual: {{ number_format($totals['current_stock'], 2) }}</td>
                    <td class="value">Costo: ${{ number_format($totals['total_cost'], 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="signatures">
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="signature-line">Elaborado</div>
                    </td>
                    <td>
                        <div class="signature-line">Revisado</div>
                    </td>
                    <td>
                        <div class="signature-line">Autorizado</div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} | {{ auth()->user()->name }} | Sistema de Bodega
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans");
            $size = 8;
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 57;
            $y = 14;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>
</body>
</html>
