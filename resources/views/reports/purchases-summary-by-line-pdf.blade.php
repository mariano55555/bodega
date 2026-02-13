<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Mensual por Línea Presupuestaria</title>
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
            font-size: 10px;
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
            font-size: 9px;
        }

        .institution-name {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 3px;
        }

        .department-name {
            font-size: 10px;
            color: #555;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 3px;
        }

        .period {
            font-size: 10px;
            color: #333;
        }

        .page-info {
            font-size: 9px;
            color: #666;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-header {
            background-color: #f0f0f0;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
            color: #1e3a5f;
            border-left: 4px solid #1e3a5f;
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
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            color: #333;
        }

        table.data-table th.right {
            text-align: right;
        }

        table.data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .subtotal-row {
            background-color: #f5f5f5;
        }

        .subtotal-row td {
            padding: 10px;
            border-top: 2px solid #ddd;
            font-weight: bold;
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
            font-size: 12px;
        }

        .grand-total-table .label {
            text-align: right;
            padding-right: 30px;
            font-weight: bold;
        }

        .grand-total-table .value {
            text-align: right;
            width: 150px;
            font-size: 14px;
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
            font-size: 10px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
            font-size: 11px;
        }

        .currency {
            font-family: 'DejaVu Sans', sans-serif;
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
                    <div class="report-title">REPORTE: RESUMEN MENSUAL POR LINEA PRESUPUESTARIA</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha de reporte:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if ($groupedByParent->isEmpty())
        <div class="no-data">
            No se encontraron compras en el período seleccionado
        </div>
    @else
        @foreach ($groupedByParent as $parentName => $group)
            <div class="section">
                <div class="section-header">
                    Categoría: {{ $group->parent_name }} - {{ $group->parent_code }}
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Línea Presupuestaria</th>
                            <th style="width: 20%;">Código de Línea</th>
                            <th class="right" style="width: 20%;">Total Mensual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group->lines as $line)
                            <tr>
                                <td>{{ $line->category_name ?? 'Sin Línea' }}</td>
                                <td>{{ $line->category_code ?? '-' }}</td>
                                <td class="right currency">$ {{ number_format($line->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="data-table">
                    <tbody>
                        <tr class="subtotal-row">
                            <td style="width: 80%; text-align: right;">Subtotal Adquisición de Bienes</td>
                            <td class="right currency" style="width: 20%;">$ {{ number_format($group->subtotal, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="grand-total">
            <table class="grand-total-table">
                <tr>
                    <td class="label">Total Mensual</td>
                    <td class="value currency">${{ number_format($totals['total_amount'], 2) }}</td>
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
