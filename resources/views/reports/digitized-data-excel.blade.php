<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Datos Digitados - {{ $typeLabel }}</title>
    @php
        $headerStyle = 'background-color: #1e3a5f; color: #ffffff; font-weight: bold;';
        $totalStyle = 'background-color: #fff7ed; font-weight: bold;';
        $grandTotalStyle = 'background-color: #1e3a5f; color: #ffffff; font-weight: bold;';
    @endphp
</head>
<body>
    <table border="1" cellspacing="0" cellpadding="4" style="border-collapse: collapse;">
        <tr>
            <td colspan="6" style="text-align: center; font-weight: bold; font-size: 14px;">
                ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"
            </td>
        </tr>
        <tr>
            <td colspan="6" style="text-align: center; font-weight: bold;">
                DATOS DIGITADOS / {{ strtoupper($typeLabel) }}
            </td>
        </tr>
        <tr>
            <td colspan="3"><strong>BODEGA:</strong> {{ $warehouseName ?? 'TODAS' }}</td>
            <td colspan="2"><strong>PERIODO:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
            <td><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr><td colspan="6">&nbsp;</td></tr>

        @forelse ($vouchers as $voucher)
            <tr>
                <td><strong>N° Documento</strong></td>
                <td>{{ $voucher->document_number }}</td>
                <td><strong>Fecha</strong></td>
                <td>{{ \Carbon\Carbon::parse($voucher->document_date)->format('d/m/Y') }}</td>
                <td><strong>{{ $isTransfers ? 'Bodega Destino' : 'Unidad Solicitante' }}</strong></td>
                <td>{{ $voucher->requesting_unit }}</td>
            </tr>
            <tr>
                <td style="{{ $headerStyle }}">CÓDIGO</td>
                <td style="{{ $headerStyle }}">DESCRIPCIÓN</td>
                <td style="{{ $headerStyle }}">UNIDAD</td>
                <td style="{{ $headerStyle }}">CANTIDAD</td>
                <td style="{{ $headerStyle }}">VALOR UNITARIO</td>
                <td style="{{ $headerStyle }}">TOTAL</td>
            </tr>
            @foreach ($voucher->items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ number_format($item->quantity, 2, '.', '') }}</td>
                    <td>{{ number_format($item->unit_price, 2, '.', '') }}</td>
                    <td>{{ number_format($item->total, 2, '.', '') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" style="{{ $totalStyle }} text-align: right;">Cantidad Total</td>
                <td style="{{ $totalStyle }}">{{ number_format($voucher->total_quantity, 2, '.', '') }}</td>
                <td style="{{ $totalStyle }} text-align: right;">TOTAL DE COMPROBANTE</td>
                <td style="{{ $totalStyle }}">{{ number_format($voucher->total_amount, 2, '.', '') }}</td>
            </tr>
            <tr><td colspan="6">&nbsp;</td></tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; font-style: italic;">No se encontraron registros</td>
            </tr>
        @endforelse

        @if ($vouchers->count() > 0)
            <tr>
                <td colspan="3" style="{{ $grandTotalStyle }} text-align: right;">TOTAL GENERAL ({{ $totals['total_vouchers'] }} comprobantes / {{ $totals['total_items'] }} líneas)</td>
                <td style="{{ $grandTotalStyle }}">{{ number_format($totals['total_quantity'], 2, '.', '') }}</td>
                <td style="{{ $grandTotalStyle }} text-align: right;">VALOR GENERAL</td>
                <td style="{{ $grandTotalStyle }}">{{ number_format($totals['total_amount'], 2, '.', '') }}</td>
            </tr>
        @endif
    </table>
</body>
</html>
