<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex de Inventario - {{ $product->name }}</title>
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
        .page-info { font-size: 8px; color: #666; }
        .header-separator { border-bottom: 2px solid #1e3a5f; margin-top: 5px; }
        .info-section { background-color: #f5f5f5; padding: 5px 10px; margin-top: 3px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 5px; font-size: 8px; }
        .info-table .label { font-weight: bold; color: #555; width: 15%; }
        .info-table .value { color: #333; width: 85%; }
        .columns-row { background-color: #e8e8e8; }
        .columns-row th { padding: 5px 4px; text-align: left; font-size: 8px; font-weight: bold; border-bottom: 1px solid #ccc; color: #333; }
        .columns-row th.right { text-align: right; }
        table.report-table td { padding: 4px; border-bottom: 1px solid #eee; font-size: 8px; vertical-align: top; }
        table.report-table td.right { text-align: right; }
        table.report-table tbody tr:nth-child(even) { background-color: #fafafa; }
        .quantity-in { color: #059669; font-weight: bold; }
        .quantity-out { color: #2563eb; font-weight: bold; }
        .balance { font-weight: bold; color: #1a1a1a; }
        .balance-negative { color: #dc2626; }
        .cost { color: #555; }
        .value-col { font-weight: bold; color: #1a1a1a; }
        .document-info { font-size: 7px; color: #666; }
        .spacer-row td { padding: 4px 0; border-bottom: none; }
        .summary-header-row td { background-color: #1e3a5f; color: white; padding: 6px 4px; font-size: 9px; font-weight: bold; border-bottom: none; }
        .summary-row td { background-color: #2d4a6f; color: white; padding: 4px 4px; font-size: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); }
        .summary-row td.label-col { text-align: right; font-weight: bold; }
        .summary-separator td { background-color: #2d4a6f; border-top: 1px solid rgba(255, 255, 255, 0.3); padding: 0; }
        .signatures { margin-top: 40px; page-break-inside: avoid; }
        .signatures-table { width: 100%; border-collapse: collapse; }
        .signatures-table td { width: 33.33%; text-align: center; padding: 0 20px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; margin-top: 40px; font-size: 9px; font-weight: bold; }
        .no-data { text-align: center; padding: 30px; color: #999; font-style: italic; font-size: 10px; }
    </style>
</head>
<body>
    @if ($movements->isEmpty())
        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">KARDEX DE INVENTARIO</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha de reporte:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
        <div class="info-section">
            <table class="info-table">
                <tr><td class="label">Producto:</td><td class="value">{{ $product->name }} (SKU: {{ $product->sku }})</td></tr>
                <tr><td class="label">Almacén:</td><td class="value">{{ $warehouse->name }}</td></tr>
                <tr><td class="label">Período:</td><td class="value">@if ($dateFrom && $dateTo){{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}@elseif ($dateFrom)Desde {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}@elseif ($dateTo)Hasta {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}@else Todos los registros @endif</td></tr>
            </table>
        </div>
        <div class="no-data">No se encontraron movimientos para el período seleccionado</div>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <td colspan="9" class="header-cell">
                        <table class="header-table">
                            <tr>
                                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                                <td class="title-cell">
                                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                                    <div class="report-title">KARDEX DE INVENTARIO</div>
                                </td>
                                <td class="info-cell">
                                    <div class="page-info">Fecha de reporte:</div>
                                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                                </td>
                            </tr>
                        </table>
                        <div class="header-separator"></div>
                        <div class="info-section">
                            <table class="info-table">
                                <tr><td class="label">Producto:</td><td class="value">{{ $product->name }} (SKU: {{ $product->sku }})</td></tr>
                                <tr><td class="label">Almacén:</td><td class="value">{{ $warehouse->name }}</td></tr>
                                <tr><td class="label">Período:</td><td class="value">@if ($dateFrom && $dateTo){{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}@elseif ($dateFrom)Desde {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}@elseif ($dateTo)Hasta {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}@else Todos los registros @endif</td></tr>
                            </table>
                        </div>
                    </td>
                </tr>
                <tr class="columns-row">
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
                        <td>{{ $movement->movement_date?->format('d/m/Y') ?? $movement->created_at->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $physicalDocNumber = null;
                                if ($movement->dispatch) {
                                    $physicalDocNumber = $movement->dispatch->physical_document_number ?: $movement->dispatch->document_number;
                                } elseif ($movement->purchase) {
                                    $physicalDocNumber = $movement->purchase->document_number;
                                } elseif ($movement->donation) {
                                    $physicalDocNumber = $movement->donation->document_number;
                                } else {
                                    $physicalDocNumber = $movement->document_number;
                                }
                            @endphp
                            @if ($physicalDocNumber)
                                <strong>{{ $physicalDocNumber }}</strong>
                            @else
                                <span class="document-info">Sin documento</span>
                            @endif
                        </td>
                        <td>
                            @if ($movement->movementReason)
                                <strong>{{ $movement->movementReason->legacy_code ?? $movement->movementReason->code }}</strong><br>
                                <span style="font-size: 7px; color: #666;">{{ $movement->movementReason->legacy_name ?? $movement->movementReason->name }}</span>
                            @else
                                {{ $movement->movement_type_spanish }}
                            @endif
                        </td>
                        <td class="right"><span class="{{ $initialBalance < 0 ? 'balance-negative' : '' }}">{{ number_format($initialBalance, 2) }}</span></td>
                        <td class="right">@if ($movement->quantity_in > 0)<span class="quantity-in">{{ number_format($movement->quantity_in, 2) }}</span>@else - @endif</td>
                        <td class="right">@if ($movement->quantity_out > 0)<span class="quantity-out">{{ number_format($movement->quantity_out, 2) }}</span>@else - @endif</td>
                        <td class="right"><span class="balance {{ $movement->balance_quantity < 0 ? 'balance-negative' : '' }}">{{ number_format($movement->balance_quantity, 2) }}</span></td>
                        <td class="right cost">@if ($movement->unit_cost)${{ number_format($movement->unit_cost, 2) }}@else - @endif</td>
                        <td class="right value-col">${{ number_format($totalValue, 2) }}</td>
                    </tr>
                @endforeach

                @php
                    $firstMovement = $movements->first();
                    $lastMovement = $movements->last();
                    $finalValue = $lastMovement->balance_quantity * ($lastMovement->unit_cost ?? 0);
                    $totalIn = $movements->sum('quantity_in');
                    $totalOut = $movements->sum('quantity_out');
                @endphp

                <tr class="spacer-row">
                    <td colspan="9"></td>
                </tr>
                <tr class="summary-header-row">
                    <td colspan="9" style="text-align: center;">RESUMEN DE MOVIMIENTOS</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7" class="label-col">Total Entradas:</td>
                    <td colspan="2" class="right">{{ number_format($totalIn, 2) }}</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7" class="label-col">Total Salidas:</td>
                    <td colspan="2" class="right">{{ number_format($totalOut, 2) }}</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7" class="label-col">Valor en Inventario:</td>
                    <td colspan="2" class="right">${{ number_format($finalValue, 2) }}</td>
                </tr>
                <tr class="summary-separator">
                    <td colspan="9"></td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7" class="label-col">Total Movimientos:</td>
                    <td colspan="2" class="right">{{ $movements->count() }}</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7" class="label-col">Existencia Actual:</td>
                    <td colspan="2" class="right">{{ number_format($lastMovement->balance_quantity, 2) }}</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7" class="label-col">Costo Unitario Actual:</td>
                    <td colspan="2" class="right">${{ number_format($lastMovement->unit_cost ?? 0, 2) }}</td>
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
