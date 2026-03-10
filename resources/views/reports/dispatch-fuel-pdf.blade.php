<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Despacho de Combustibles y Lubricantes - {{ $dispatch->dispatch_number }}</title>
    <style>
        @page {
            margin-top: 15mm;
            margin-right: 12mm;
            margin-bottom: 20mm;
            margin-left: 12mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; line-height: 1.4; }

        .main-table { width: 100%; border-collapse: collapse; }
        .main-table td, .main-table th { border: 1px solid #333; padding: 4px 6px; }

        /* Header */
        .header-row td { border: none !important; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .header-table td { border: none !important; vertical-align: middle; padding: 5px; }
        .logo { max-width: 120px; max-height: 45px; }
        .institution-name { font-size: 10px; font-weight: bold; color: #1e3a5f; }
        .department-name { font-size: 9px; color: #333; font-weight: bold; }
        .report-title { font-size: 11px; font-weight: bold; color: #1e3a5f; text-align: center; margin: 8px 0 4px 0; }
        .doc-number { font-size: 10px; font-weight: bold; text-align: right; color: #c00; }

        /* Info fields */
        .info-row td { font-size: 9px; padding: 3px 6px; }
        .info-label { font-weight: bold; white-space: nowrap; width: 1%; }
        .info-value { border-bottom: 1px solid #999 !important; border-top: none !important; border-left: none !important; border-right: none !important; min-width: 80px; }

        /* Section headers */
        .section-header { background-color: #f5c500; font-weight: bold; font-size: 9px; padding: 4px 8px !important; text-align: left; }

        /* Products table */
        .products-header th { background-color: #e8e8e8; font-size: 8px; font-weight: bold; text-align: center; padding: 4px 3px; border: 1px solid #333; }
        .products-row td { font-size: 8px; text-align: center; padding: 3px 4px; border: 1px solid #333; min-height: 18px; }
        .products-row td.left { text-align: left; }
        .products-row td.right { text-align: right; }

        /* Totals */
        .total-row td { font-weight: bold; font-size: 9px; border: 1px solid #333; padding: 4px 6px; }

        /* Signatures */
        .signatures-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .signatures-table td { width: 25%; text-align: center; padding: 0 10px; vertical-align: bottom; border: none !important; }
        .signature-line { border-top: 1px solid #333; padding-top: 4px; margin-top: 50px; font-size: 8px; font-weight: bold; }
        .signature-role { font-size: 7px; color: #555; margin-top: 2px; }

        /* Footer */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .footer-table td { font-size: 7px; color: #666; border: none !important; padding: 2px 5px; }
    </style>
</head>
<body>
    @php
        $fuel = $dispatch->fuelDetail;
    @endphp

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 15%;">
                <img src="{{ public_path('images/LOGO-ENA_gris.png') }}" alt="Logo" class="logo">
            </td>
            <td style="width: 55%; text-align: center;">
                <div class="institution-name">MINISTERIO DE AGRICULTURA Y GANADERÍA</div>
                <div class="institution-name">ESCUELA NACIONAL DE AGRICULTURA "ROBERTO QUIÑÓNEZ"</div>
                <div class="department-name">DESPACHO DE COMBUSTIBLES Y LUBRICANTES DE BODEGA</div>
            </td>
            <td style="width: 15%; text-align: right;">
                <img src="{{ public_path('images/ena-logo.png') }}" alt="ENA" class="logo">
            </td>
            <td style="width: 15%; text-align: right;">
                <div class="doc-number">N° {{ $dispatch->physical_document_number }}</div>
            </td>
        </tr>
    </table>

    {{-- Request Info --}}
    <table class="main-table" style="margin-top: 8px;">
        <tr class="info-row">
            <td class="info-label" style="width: 20%;">UNIDAD SOLICITANTE:</td>
            <td style="width: 40%;">{{ $dispatch->area?->name ?? '-' }}</td>
            <td class="info-label" style="width: 10%;">FECHA:</td>
            <td style="width: 30%;">{{ $dispatch->document_date?->format('d-m-Y') ?? $dispatch->created_at->format('d-m-Y') }}</td>
        </tr>
        <tr class="info-row">
            <td class="info-label">DEPARTAMENTO:</td>
            <td colspan="3">{{ $dispatch->area?->name ?? '-' }}</td>
        </tr>
    </table>

    {{-- Vehicle Section --}}
    <table class="main-table" style="margin-top: 6px;">
        <tr>
            <td colspan="8" class="section-header">DESCRIPCIÓN DEL EQUIPO O VEHÍCULO EN QUE SE USARÁ</td>
        </tr>
        <tr class="info-row">
            <td class="info-label">CLASE:</td>
            <td>{{ $fuel?->vehicle_class ?? '' }}</td>
            <td class="info-label">MARCA:</td>
            <td>{{ $fuel?->vehicle_brand ?? '' }}</td>
            <td class="info-label">MODELO:</td>
            <td>{{ $fuel?->vehicle_model ?? '' }}</td>
            <td class="info-label">PLACA:</td>
            <td>{{ $fuel?->vehicle_plate ?? '' }}</td>
        </tr>
        <tr class="info-row">
            <td class="info-label" colspan="2">LECTURA DEL ODÓMETRO DEL VEHÍCULO:</td>
            <td colspan="2">{{ $fuel?->odometer_reading ? number_format($fuel->odometer_reading, 2) . ' Km' : '' }}</td>
            <td class="info-label" colspan="2">LECTURA DEL HORÓMETRO DEL TRACTOR:</td>
            <td colspan="2">{{ $fuel?->horometer_reading ? number_format($fuel->horometer_reading, 2) . ' Hr' : '' }}</td>
        </tr>
    </table>

    {{-- Justification Section --}}
    <table class="main-table" style="margin-top: 6px;">
        <tr>
            <td colspan="4" class="section-header">DESCRIPCIÓN DE LA JUSTIFICACIÓN</td>
        </tr>
        <tr class="info-row">
            <td class="info-label" style="width: 18%;">LUGAR A VISITAR:</td>
            <td style="width: 32%;">{{ $fuel?->place_to_visit ?? '' }}</td>
            <td class="info-label" style="width: 22%;">RESPONSABLE DE LA MISIÓN:</td>
            <td style="width: 28%;">{{ $dispatch->employee?->name ?? $dispatch->recipient_name ?? '' }}</td>
        </tr>
        <tr class="info-row">
            <td class="info-label">MISIÓN A REALIZAR:</td>
            <td>{{ $fuel?->mission_description ?? '' }}</td>
            <td class="info-label">KILÓMETROS A RECORRER:</td>
            <td>{{ $fuel?->kilometers_to_travel ? number_format($fuel->kilometers_to_travel, 2) : '' }}</td>
        </tr>
    </table>

    {{-- Products Section --}}
    <table class="main-table" style="margin-top: 6px;">
        <tr>
            <td colspan="6" class="section-header">DESCRIPCIÓN DEL COMBUSTIBLE Y/O LUBRICANTE SOLICITADO</td>
        </tr>
        <tr class="products-header">
            <th style="width: 10%;">CÓD.</th>
            <th style="width: 10%;">CANT.</th>
            <th style="width: 10%;">UNID.</th>
            <th style="width: 40%;">DESCRIPCIÓN DEL PRODUCTO</th>
            <th style="width: 15%;">VALOR UNIT. ($)</th>
            <th style="width: 15%;">VALOR TOTAL ($)</th>
        </tr>

        @foreach ($dispatch->details as $detail)
            <tr class="products-row">
                <td>{{ $detail->product?->sku ?? $detail->product_id }}</td>
                <td>{{ number_format($detail->quantity, 2) }}</td>
                <td>{{ $detail->unitOfMeasure?->abbreviation ?? '' }}</td>
                <td class="left">{{ $detail->product?->name ?? '' }}</td>
                <td class="right">${{ number_format($detail->unit_price, 2) }}</td>
                <td class="right">${{ number_format($detail->subtotal, 2) }}</td>
            </tr>
        @endforeach

        {{-- Empty rows to fill space --}}
        @for ($i = $dispatch->details->count(); $i < 5; $i++)
            <tr class="products-row">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor

        <tr class="total-row">
            <td colspan="5" style="text-align: right;">TOTAL ($):</td>
            <td style="text-align: right;">${{ number_format($dispatch->total, 2) }}</td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">SOLICITANTE</div>
                <div class="signature-role">NOMBRE Y FIRMA</div>
            </td>
            <td>
                <div class="signature-line">ENCARGADO DE BODEGA</div>
                <div class="signature-role">NOMBRE, FIRMA Y SELLO</div>
            </td>
            <td>
                <div class="signature-line">AUTORIZACIÓN</div>
                <div class="signature-role">GERENTE ADMINISTRATIVO</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <table class="footer-table">
        <tr>
            <td>BLANCO ORIGINAL - BODEGA</td>
            <td style="text-align: center;">AMARILLO DUPLICADO - ENCARGADO DE COMBUSTIBLE</td>
            <td style="text-align: right;">VERDE TRIPLICADO - SOLICITANTE</td>
        </tr>
    </table>

    {{-- Page number --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $size = 7;
            $y = $pdf->get_height() - 24;
            $x = $pdf->get_width() / 2 - 20;
            $pdf->page_text($x, $y, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>
</body>
</html>
