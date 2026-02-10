<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual de Salidas de Bodega</title>
    <style>
        @page {
            margin: 15mm 10mm 20mm 10mm;
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
            line-height: 1.3;
        }

        .header {
            width: 100%;
            margin-bottom: 15px;
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
            width: 20%;
        }

        .logo {
            max-width: 150px;
            max-height: 50px;
        }

        .title-cell {
            width: 60%;
            text-align: center;
        }

        .info-cell {
            width: 20%;
            text-align: right;
            font-size: 8px;
        }

        .institution-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 2px;
        }

        .department-name {
            font-size: 9px;
            color: #555;
            margin-bottom: 4px;
        }

        .report-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 2px;
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
            background-color: #f5f5f5;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: bold;
            color: #1e3a5f;
            border-left: 4px solid #d97706;
            margin-bottom: 0;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        table.data-table thead {
            background-color: #1e3a5f;
            color: white;
        }

        table.data-table th {
            padding: 5px 3px;
            text-align: left;
            font-size: 7px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
        }

        table.data-table th.right {
            text-align: right;
        }

        table.data-table td {
            padding: 4px 3px;
            border-bottom: 1px solid #eee;
            font-size: 7px;
            vertical-align: top;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .subtotal-section {
            margin-top: 5px;
            padding: 8px 10px;
            background-color: #fff7ed;
            border-top: 1px solid #fed7aa;
        }

        .subtotal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .subtotal-table td {
            padding: 2px 0;
            font-size: 8px;
        }

        .subtotal-table .label {
            text-align: right;
            padding-right: 15px;
            width: 80%;
        }

        .subtotal-table .value {
            text-align: right;
            font-weight: bold;
            width: 20%;
        }

        .grand-total {
            background-color: #1e3a5f;
            color: white;
            padding: 10px;
            margin-top: 15px;
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
            text-align: right;
            padding-right: 20px;
        }

        .grand-total-table .value {
            text-align: right;
            width: 100px;
            font-weight: bold;
        }

        .signatures {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            text-align: center;
            padding: 0 20px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 40px;
            font-size: 9px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 5mm;
            left: 10mm;
            right: 10mm;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
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
                    <div class="report-title">REPORTE MENSUAL DE SALIDAS DE BODEGA</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div>Página #</div>
                    <div>Fecha de reporte:</div>
                    <div>{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if ($groupedByWarehouse->isEmpty())
        <div class="no-data">
            No se encontraron salidas en el período seleccionado
        </div>
    @else
        @foreach ($groupedByWarehouse as $warehouseName => $group)
            <div class="section">
                <div class="section-header">
                    {{ $group->warehouse_name }}
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
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
                    </tbody>
                </table>

                <div class="subtotal-section">
                    <table class="subtotal-table">
                        <tr>
                            <td class="label">Cantidad Mensual</td>
                            <td class="value">{{ number_format($group->total_quantity, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Valor Mensual</td>
                            <td class="value">$ {{ number_format($group->total_value, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="grand-total">
            <table class="grand-total-table">
                <tr>
                    <td class="label">Total Mensual</td>
                    <td class="value">CANTIDAD</td>
                    <td class="value">VALOR</td>
                </tr>
                <tr>
                    <td class="label"></td>
                    <td class="value">{{ number_format($totals['total_quantity'], 2) }}</td>
                    <td class="value">${{ number_format($totals['total_value'], 2) }}</td>
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
</body>
</html>
