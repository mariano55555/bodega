<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Existencias y Movimientos de Inventario</title>
    <style>
        @page {
            margin-top: 35mm;
            margin-right: 15mm;
            margin-bottom: 20mm;
            margin-left: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: 10mm;
            left: 5mm;
            right: 5mm;
            height: 30mm;
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

        .period {
            font-size: 9px;
            color: #333;
        }

        .warehouse-info {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            margin-top: 3px;
        }

        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .section-header {
            background-color: #fff7ed;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            border-left: 4px solid #d97706;
            margin-bottom: 0;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        table.data-table thead {
            background-color: #e8e8e8;
        }

        table.data-table th {
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            color: #333;
        }

        table.data-table th.right {
            text-align: right;
        }

        table.data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
            font-size: 8px;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table td.green {
            color: #16a34a;
        }

        table.data-table td.red {
            color: #dc2626;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .subtotal-row {
            background-color: #fff7ed;
        }

        .subtotal-row td {
            padding: 8px;
            border-top: 2px solid #fed7aa;
            font-weight: bold;
            font-size: 9px;
        }

        .grand-total {
            background-color: #1e3a5f;
            color: white;
            padding: 12px 15px;
            margin-top: 20px;
        }

        .grand-total-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grand-total-table td {
            padding: 3px 0;
            font-size: 10px;
        }

        .grand-total-table .label {
            text-align: left;
            font-weight: bold;
        }

        .grand-total-table .value {
            text-align: right;
            width: 100px;
            font-size: 11px;
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
            bottom: -20mm;
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
                    <div class="report-title">EXISTENCIAS Y MOVIMIENTOS DE INVENTARIO</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                    <div class="warehouse-info">BODEGA: {{ $warehouseName }}</div>
                </td>
                <td class="info-cell">
                    <div>Fecha de reporte:</div>
                    <div>{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 45mm;"></div>

    @if ($groupedByCategory->isEmpty())
        <div class="no-data">
            No se encontraron movimientos en el período seleccionado
        </div>
    @else
        @foreach ($groupedByCategory as $parentName => $group)
            <div class="section">
                <div class="section-header">
                    Categoría: {{ $group->parent_name }} - {{ $group->parent_code }}
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Descripción</th>
                            <th style="width: 10%;">Unidad</th>
                            <th class="right" style="width: 12%;">Exist. Inicial</th>
                            <th class="right" style="width: 12%;">Entradas</th>
                            <th class="right" style="width: 12%;">Salidas</th>
                            <th class="right" style="width: 14%;">Exist. Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->unit }}</td>
                                <td class="right">{{ number_format($item->initial_stock, 2) }}</td>
                                <td class="right green">{{ number_format($item->entries, 2) }}</td>
                                <td class="right red">{{ number_format($item->exits, 2) }}</td>
                                <td class="right" style="font-weight: bold;">{{ number_format($item->final_stock, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="data-table">
                    <tbody>
                        <tr class="subtotal-row">
                            <td style="width: 50%; text-align: right;">Subtotal {{ $group->parent_name }}</td>
                            <td class="right" style="width: 12%;">{{ number_format($group->subtotals->initial_stock, 2) }}</td>
                            <td class="right green" style="width: 12%;">{{ number_format($group->subtotals->entries, 2) }}</td>
                            <td class="right red" style="width: 12%;">{{ number_format($group->subtotals->exits, 2) }}</td>
                            <td class="right" style="width: 14%;">{{ number_format($group->subtotals->final_stock, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="grand-total">
            <table class="grand-total-table">
                <tr>
                    <td class="label">Totales del Período</td>
                    <td class="value">Inicial: {{ number_format($totals['initial_stock'], 2) }}</td>
                    <td class="value">Entradas: {{ number_format($totals['entries'], 2) }}</td>
                    <td class="value">Salidas: {{ number_format($totals['exits'], 2) }}</td>
                    <td class="value">Final: {{ number_format($totals['final_stock'], 2) }}</td>
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
            $x = ($pdf->get_width() - $width) / 2 + 60;
            $y = 14;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>
</body>
</html>
