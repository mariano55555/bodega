<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras por Proveedor</title>
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
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
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

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a5f;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        table.data-table thead {
            background-color: #1e3a5f;
        }

        table.data-table th {
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: white;
            border-bottom: 1px solid #ccc;
        }

        table.data-table th.right {
            text-align: right;
        }

        table.data-table th.center {
            text-align: center;
        }

        table.data-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table td.center {
            text-align: center;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .total-row {
            background-color: #1e3a5f !important;
        }

        .total-row td {
            color: white;
            font-weight: bold;
            padding: 10px;
            border-top: 2px solid #1e3a5f;
            font-size: 11px;
        }

        .tax-id {
            font-size: 9px;
            color: #666;
        }

        .rank {
            font-weight: bold;
            color: #1e3a5f;
        }

        .percentage-bar {
            width: 100%;
            height: 6px;
            background-color: #e0e0e0;
            margin-top: 3px;
        }

        .percentage-fill {
            height: 6px;
            background-color: #1e3a5f;
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
            bottom: 5mm;
            left: 10mm;
            right: 10mm;
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
                    <div class="report-title">REPORTE DE COMPRAS POR PROVEEDOR</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha de reporte:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Summary --}}
    <table class="summary-table">
        <tr>
            <td style="width: 33.33%;">
                <div class="summary-label">Total Proveedores</div>
                <div class="summary-value">{{ number_format($totals['total_suppliers']) }}</div>
            </td>
            <td style="width: 33.33%;">
                <div class="summary-label">Total Facturas</div>
                <div class="summary-value">{{ number_format($totals['total_invoices']) }}</div>
            </td>
            <td style="width: 33.33%;">
                <div class="summary-label">Monto Total</div>
                <div class="summary-value currency">${{ number_format($totals['total_amount'], 2) }}</div>
            </td>
        </tr>
    </table>

    @if ($supplierData->isEmpty())
        <div class="no-data">
            No se encontraron compras en el período seleccionado
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
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
