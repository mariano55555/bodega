<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex de Inventario - {{ $product->name }}</title>
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
        }

        .header {
            position: fixed;
            top: 10mm;
            left: 5mm;
            right: 5mm;
            height: 30mm;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 5px;
        }

        .footer {
            position: fixed;
            bottom: -20mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
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

        .info-section {
            margin-bottom: 15px;
            background-color: #f5f5f5;
            padding: 10px;
            margin-top: 45mm;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 5px;
            font-size: 9px;
        }

        .info-table .label {
            font-weight: bold;
            color: #555;
            width: 20%;
        }

        .info-table .value {
            color: #333;
            width: 80%;
        }

        table.data-table {
            width: 95%;
            border-collapse: collapse;
            margin-bottom: 15px;
            margin-left: 5mm;
            margin-right: 5mm;
        }

        table.data-table thead {
            background-color: #e8e8e8;
        }

        table.data-table th {
            padding: 6px 4px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            color: #333;
            text-transform: uppercase;
        }

        table.data-table th.right {
            text-align: right;
        }

        table.data-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #eee;
            font-size: 8px;
        }

        table.data-table td.right {
            text-align: right;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .quantity-in {
            color: #059669;
            font-weight: bold;
        }

        .quantity-out {
            color: #2563eb;
            font-weight: bold;
        }

        .balance {
            font-weight: bold;
            color: #1a1a1a;
            margin-left: 5mm;
            margin-right: 5mm;
        }

        .balance-negative {
            color: #dc2626;
        }

        .cost {
            color: #555;
        }

        .value-col {
            font-weight: bold;
            color: #1a1a1a;
        }

        .summary {
            margin-top: 15px;
            background-color: #1e3a5f;
            color: white;
            padding: 12px 15px;
            margin-right: 5mm;
            margin-left: 5mm;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 10px;
            font-size: 10px;
        }

        .summary-table .label {
            font-weight: bold;
            text-align: right;
            width: 70%;
        }

        .summary-table .value {
            text-align: right;
            width: 30%;
            font-weight: bold;
        }

        .summary-table .separator td {
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            padding-top: 8px;
            margin-top: 4px;
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

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
            font-size: 11px;
        }

        .document-info {
            font-size: 7px;
            color: #666;
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
                    <div class="report-title">KARDEX DE INVENTARIO</div>
                </td>
                <td class="info-cell">
                    <div>Fecha de reporte:</div>
                    <div>{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Producto:</td>
                <td class="value">{{ $product->name }} (SKU: {{ $product->sku }})</td>
            </tr>
            <tr>
                <td class="label">Almacén:</td>
                <td class="value">{{ $warehouse->name }}</td>
            </tr>
            <tr>
                <td class="label">Período:</td>
                <td class="value">
                    @if ($dateFrom && $dateTo)
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{
                    \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                    @elseif ($dateFrom)
                    Desde {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                    @elseif ($dateTo)
                    Hasta {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                    @else
                    Todos los registros
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if ($movements->isEmpty())
    <div class="no-data">
        No se encontraron movimientos para el período seleccionado
    </div>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 9%;">Fecha</th>
                <th style="width: 12%;">Documento</th>
                <th style="width: 17%;">Transacción</th>
                <th class="right" style="width: 8%;">Saldo Ini.</th>
                <th class="right" style="width: 8%;">Entrada</th>
                <th class="right" style="width: 8%;">Salida</th>
                <th class="right" style="width: 9%;">Saldo Final</th>
                <th class="right" style="width: 9%;">Costo Unit.</th>
                <th class="right" style="width: 10%;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($movements as $movement)
            @php
            $initialBalance = $movement->balance_quantity - $movement->quantity_in + $movement->quantity_out;
            $totalValue = $movement->balance_quantity * ($movement->unit_cost ?? 0);
            @endphp
            <tr>
                <td>
                    {{ $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y') }}
                </td>
                <td>
                    @if ($movement->document_number)
                    <strong>{{ $movement->document_number }}</strong>
                    @endif
                    @if ($movement->reference_number)
                    <br><span class="document-info">Ref: {{ $movement->reference_number }}</span>
                    @endif
                    @if (! $movement->document_number && ! $movement->reference_number)
                    <span class="document-info">Sin documento</span>
                    @endif
                </td>
                <td>
                    @if ($movement->movementReason)
                    <strong>{{ $movement->movementReason->legacy_code ?? $movement->movementReason->code }}</strong><br>
                    <span style="font-size: 7px; color: #666;">{{ $movement->movementReason->legacy_name ??
                        $movement->movementReason->name }}</span>
                    @else
                    {{ $movement->movement_type_spanish }}
                    @endif
                </td>
                <td class="right">
                    <span class="{{ $initialBalance < 0 ? 'balance-negative' : '' }}">
                        {{ number_format($initialBalance, 2) }}
                    </span>
                </td>
                <td class="right">
                    @if ($movement->quantity_in > 0)
                    <span class="quantity-in">{{ number_format($movement->quantity_in, 2) }}</span>
                    @else
                    -
                    @endif
                </td>
                <td class="right">
                    @if ($movement->quantity_out > 0)
                    <span class="quantity-out">{{ number_format($movement->quantity_out, 2) }}</span>
                    @else
                    -
                    @endif
                </td>
                <td class="right">
                    <span class="balance {{ $movement->balance_quantity < 0 ? 'balance-negative' : '' }}">
                        {{ number_format($movement->balance_quantity, 2) }}
                    </span>
                </td>
                <td class="right cost">
                    @if ($movement->unit_cost)
                    ${{ number_format($movement->unit_cost, 2) }}
                    @else
                    -
                    @endif
                </td>
                <td class="right value-col">
                    ${{ number_format($totalValue, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
    $firstMovement = $movements->first();
    $lastMovement = $movements->last();
    $initialBalance = $firstMovement->balance_quantity - $firstMovement->quantity_in + $firstMovement->quantity_out;
    $finalValue = $lastMovement->balance_quantity * ($lastMovement->unit_cost ?? 0);
    $totalIn = $movements->sum('quantity_in');
    $totalOut = $movements->sum('quantity_out');
    @endphp
    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="label">Total Entradas:</td>
                <td class="value">{{ number_format($totalIn, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Salidas:</td>
                <td class="value">{{ number_format($totalOut, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Valor en Inventario:</td>
                <td class="value">${{ number_format($finalValue, 2) }}</td>
            </tr>
            <tr class="separator">
                <td class="label">Total Movimientos:</td>
                <td class="value">{{ $movements->count() }}</td>
            </tr>
            <tr>
                <td class="label">Existencia Actual:</td>
                <td class="value">{{ number_format($lastMovement->balance_quantity, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Costo Unitario Actual:</td>
                <td class="value">${{ number_format($lastMovement->unit_cost ?? 0, 2) }}</td>
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
