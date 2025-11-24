<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $typeLabel }} - {{ $priorityLabel }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: {{ $priorityColor }};
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .alert-box {
            background-color: #f9fafb;
            border-left: 4px solid {{ $priorityColor }};
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .alert-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
        }
        .alert-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }
        .detail-item {
            font-size: 13px;
        }
        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }
        .detail-value {
            color: #6b7280;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-critical {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-high {
            background-color: #fed7aa;
            color: #9a3412;
        }
        .badge-medium {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-low {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .additional-alerts {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .additional-alerts h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .alert-item {
            background-color: #f9fafb;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: {{ $priorityColor }};
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 16px;
        }
        @media only screen and (max-width: 600px) {
            .alert-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $typeLabel }}</h1>
            <p>Prioridad: <span class="badge badge-{{ $alert->priority }}">{{ $priorityLabel }}</span></p>
        </div>

        <div class="content">
            <div class="alert-box">
                <div class="alert-title">{{ $alert->message }}</div>

                <div class="alert-details">
                    <div class="detail-item">
                        <div class="detail-label">Producto:</div>
                        <div class="detail-value">{{ $alert->product->name ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">SKU:</div>
                        <div class="detail-value">{{ $alert->product->sku ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Bodega:</div>
                        <div class="detail-value">{{ $alert->warehouse->name ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Fecha:</div>
                        <div class="detail-value">{{ $alert->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($alert->threshold_value)
                    <div class="detail-item">
                        <div class="detail-label">Umbral:</div>
                        <div class="detail-value">{{ number_format($alert->threshold_value, 2) }}</div>
                    </div>
                    @endif
                    @if($alert->current_value)
                    <div class="detail-item">
                        <div class="detail-label">Valor Actual:</div>
                        <div class="detail-value">{{ number_format($alert->current_value, 2) }}</div>
                    </div>
                    @endif
                </div>

                @if($alert->metadata && isset($alert->metadata['lot_number']))
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                    <div class="detail-label">Lote: {{ $alert->metadata['lot_number'] }}</div>
                    @if(isset($alert->metadata['expiration_date']))
                    <div class="detail-value">Fecha de vencimiento: {{ \Carbon\Carbon::parse($alert->metadata['expiration_date'])->format('d/m/Y') }}</div>
                    @endif
                </div>
                @endif
            </div>

            @if(count($additionalAlerts) > 0)
            <div class="additional-alerts">
                <h3>Otras Alertas Recientes ({{ count($additionalAlerts) }})</h3>
                @foreach($additionalAlerts as $additionalAlert)
                <div class="alert-item">
                    <strong>{{ $additionalAlert->product->name ?? 'N/A' }}</strong> - {{ $additionalAlert->message }}
                </div>
                @endforeach
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/inventory/alerts" class="button">Ver Todas las Alertas</a>
            </div>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático del Sistema de Gestión de Bodega.</p>
            <p>Por favor no responda a este correo.</p>
        </div>
    </div>
</body>
</html>
