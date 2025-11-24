<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex de Inventario - {{ $product->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #1a1a1a;
        }

        .header h2 {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }

        .info-section {
            margin-bottom: 20px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            display: table-cell;
            width: 25%;
            font-weight: bold;
            color: #555;
        }

        .info-value {
            display: table-cell;
            width: 75%;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        thead {
            background-color: #333;
            color: white;
        }

        th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        th.right {
            text-align: right;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }

        td.right {
            text-align: right;
        }

        td.center {
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .quantity-in {
            color: #059669;
            font-weight: bold;
        }

        .quantity-out {
            color: #dc2626;
            font-weight: bold;
        }

        .balance {
            font-weight: bold;
            color: #1a1a1a;
        }

        .balance-negative {
            color: #dc2626;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .summary-label {
            display: table-cell;
            width: 70%;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 15px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .document-info {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kardex de Inventario</h1>
        <h2>Reporte de Movimientos</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Producto:</span>
            <span class="info-value">{{ $product->name }} (SKU: {{ $product->sku }})</span>
        </div>
        <div class="info-row">
            <span class="info-label">Almacén:</span>
            <span class="info-value">{{ $warehouse->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Período:</span>
            <span class="info-value">
                @if ($dateFrom && $dateTo)
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                @elseif ($dateFrom)
                    Desde {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                @elseif ($dateTo)
                    Hasta {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                @else
                    Todos los registros
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha de Reporte:</span>
            <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    @if ($movements->isEmpty())
        <div class="no-data">
            No se encontraron movimientos para el período seleccionado
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Fecha</th>
                    <th style="width: 20%;">Documento</th>
                    <th style="width: 28%;">Motivo</th>
                    <th class="right" style="width: 13%;">Entrada</th>
                    <th class="right" style="width: 13%;">Salida</th>
                    <th class="right" style="width: 14%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movements as $movement)
                    <tr>
                        <td>
                            {{ $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y') }}
                        </td>
                        <td>
                            @if ($movement->document_number)
                                <strong>{{ $movement->document_number }}</strong>
                            @endif
                            @if ($movement->reference_number)
                                <br><span class="document-info">{{ $movement->reference_number }}</span>
                            @endif
                            @if (! $movement->document_number && ! $movement->reference_number)
                                <span class="document-info">Sin documento</span>
                            @endif
                        </td>
                        <td>
                            {{ $movement->movementReason?->name ?? $movement->movement_type_spanish }}
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
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span class="summary-label">Total de Movimientos:</span>
                <span class="summary-value">{{ $movements->count() }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Entradas:</span>
                <span class="summary-value quantity-in">{{ number_format($movements->sum('quantity_in'), 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Salidas:</span>
                <span class="summary-value quantity-out">{{ number_format($movements->sum('quantity_out'), 2) }}</span>
            </div>
            <div class="summary-row" style="border-top: 2px solid #333; padding-top: 8px; margin-top: 8px;">
                <span class="summary-label">Saldo Final:</span>
                <span class="summary-value balance {{ $movements->last()->balance_quantity < 0 ? 'balance-negative' : '' }}">
                    {{ number_format($movements->last()->balance_quantity, 2) }}
                </span>
            </div>
        </div>
    @endif

    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} | {{ auth()->user()->name }} | Bodega System</p>
    </div>
</body>
</html>
