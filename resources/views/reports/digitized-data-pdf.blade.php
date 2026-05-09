<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Datos Digitados - {{ $typeLabel }}</title>
    <style>
        @page {
            margin-top: 22mm;
            margin-right: 12mm;
            margin-bottom: 18mm;
            margin-left: 12mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #333; line-height: 1.3; }
        .footer { position: fixed; bottom: -15mm; left: 0; right: 0; text-align: center; font-size: 7px; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }

        table.report-table { width: 95%; border-collapse: collapse; margin-left: auto; margin-right: auto; }
        .header-cell { padding: 6px 0 4px 0; border: none !important; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; border: none !important; }
        .logo-cell { width: 18%; }
        .logo { max-width: 110px; max-height: 45px; }
        .title-cell { width: 64%; text-align: center; }
        .info-cell { width: 18%; text-align: right; font-size: 8px; }
        .institution-name { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .department-name { font-size: 9px; color: #555; margin-bottom: 4px; }
        .report-title { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .period { font-size: 9px; color: #333; }
        .page-info { font-size: 8px; color: #666; }
        .header-separator { border-bottom: 2px solid #1e3a5f; margin-top: 5px; }

        .columns-row { background-color: #1e3a5f; }
        .columns-row th { padding: 5px 4px; text-align: left; font-size: 7px; font-weight: bold; color: #fff; border-bottom: 1px solid #ccc; }
        .columns-row th.right { text-align: right; }

        table.report-table td { padding: 3px 4px; font-size: 8px; vertical-align: top; }
        table.report-table td.right { text-align: right; }

        .voucher-row td { background-color: #f5f5f5; padding: 5px 8px; font-size: 9px; font-weight: bold; color: #1e3a5f; }
        .voucher-row td span.label { color: #1e3a5f; }
        .voucher-row td span.value { color: #333; font-weight: normal; margin-right: 14px; }

        .voucher-total-row td { background-color: #fff7ed; padding: 4px; font-size: 9px; font-weight: bold; }
        .voucher-total-row td.label { text-align: right; color: #1e3a5f; }
        .voucher-total-row td.value { text-align: right; color: #c2410c; }

        .spacer-row td { padding: 4px 0; }

        .grand-total-row td { background-color: #1e3a5f; color: #fff; padding: 8px 4px; font-size: 10px; font-weight: bold; }

        .signatures { margin-top: 32px; page-break-inside: avoid; }
        .signatures-table { width: 100%; border-collapse: collapse; }
        .signatures-table td { width: 33.33%; text-align: center; padding: 0 20px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; margin-top: 36px; font-size: 9px; font-weight: bold; }

        .no-data { text-align: center; padding: 30px; color: #999; font-style: italic; font-size: 10px; }
    </style>
</head>
<body>
    @if ($vouchers->isEmpty())
        <table class="header-table">
            <tr>
                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                <td class="title-cell">
                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                    <div class="report-title">DATOS DIGITADOS / {{ strtoupper($typeLabel) }}</div>
                    <div class="period">PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                </td>
                <td class="info-cell">
                    <div class="page-info">Fecha generación:</div>
                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
        <div class="no-data">No se encontraron registros en el período seleccionado</div>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <td colspan="6" class="header-cell">
                        <table class="header-table">
                            <tr>
                                <td class="logo-cell"><img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo"></td>
                                <td class="title-cell">
                                    <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                                    <div class="department-name">GERENCIA ADMINISTRATIVA</div>
                                    <div class="report-title">DATOS DIGITADOS / {{ strtoupper($typeLabel) }}</div>
                                    <div class="period">
                                        @if ($warehouseName)
                                            BODEGA: {{ $warehouseName }} —
                                        @endif
                                        PERIODO: DEL {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td class="info-cell">
                                    <div class="page-info">Fecha generación:</div>
                                    <div class="page-info">{{ now()->format('d/m/Y') }}</div>
                                </td>
                            </tr>
                        </table>
                        <div class="header-separator"></div>
                    </td>
                </tr>
                <tr class="columns-row">
                    <th style="width: 11%;">Código</th>
                    <th style="width: 43%;">Descripción</th>
                    <th style="width: 9%;">Unidad</th>
                    <th class="right" style="width: 11%;">Cantidad</th>
                    <th class="right" style="width: 12%;">Valor Unitario</th>
                    <th class="right" style="width: 14%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vouchers as $voucher)
                    <tr class="voucher-row">
                        <td colspan="6">
                            <span class="label">N° Doc:</span> <span class="value">{{ $voucher->document_number }}</span>
                            <span class="label">Fecha:</span> <span class="value">{{ \Carbon\Carbon::parse($voucher->document_date)->format('d/m/Y') }}</span>
                            <span class="label">{{ $isTransfers ? 'Bodega Destino' : 'Unidad Solicitante' }}:</span> <span class="value">{{ $voucher->requesting_unit }}</span>
                            @if (! $warehouseName)
                                <span class="label">{{ $isTransfers ? 'Bodega Origen' : 'Bodega' }}:</span> <span class="value">{{ $voucher->warehouse_name }}</span>
                            @endif
                        </td>
                    </tr>
                    @forelse ($voucher->items as $item)
                        <tr>
                            <td>{{ $item->sku }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="right">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="right">${{ number_format($item->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="no-data">Sin líneas de detalle</td>
                        </tr>
                    @endforelse
                    <tr class="voucher-total-row">
                        <td colspan="3" class="label">Cantidad Total</td>
                        <td class="right">{{ number_format($voucher->total_quantity, 2) }}</td>
                        <td class="label">TOTAL DE COMPROBANTE</td>
                        <td class="value">${{ number_format($voucher->total_amount, 2) }}</td>
                    </tr>
                    <tr class="spacer-row"><td colspan="6"></td></tr>
                @endforeach
                <tr class="grand-total-row">
                    <td colspan="3" class="right">Total General ({{ $totals['total_vouchers'] }} comprobantes — {{ $totals['total_items'] }} líneas)</td>
                    <td class="right">{{ number_format($totals['total_quantity'], 2) }}</td>
                    <td class="right">VALOR GENERAL</td>
                    <td class="right">${{ number_format($totals['total_amount'], 2) }}</td>
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
