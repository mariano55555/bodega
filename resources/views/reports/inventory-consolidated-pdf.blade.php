<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventario Consolidado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #1f2937;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #1f2937;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            text-align: center;
            color: #6b7280;
        }
        .summary {
            margin-top: 15px;
            padding: 10px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVENTARIO CONSOLIDADO</h1>
        <div class="info">
            <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
            @if (isset($filters['warehouse_id']))
                <br><strong>Bodega Filtrada</strong>
            @endif
            @if (isset($filters['type']))
                <br><strong>Tipo:</strong> {{ ucfirst($filters['type']) }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Bodega</th>
                <th>SKU</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Ubicación</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Unidad</th>
                <th class="text-right">Costo Unit.</th>
                <th class="text-right">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventories as $inventory)
                <tr>
                    <td>{{ $inventory->warehouse->name }}</td>
                    <td>{{ $inventory->product->sku }}</td>
                    <td>{{ $inventory->product->name }}</td>
                    <td>{{ $inventory->product->category?->name ?? 'Sin categoría' }}</td>
                    <td>{{ $inventory->storageLocation?->name ?? 'Sin ubicación' }}</td>
                    <td class="text-right">{{ number_format($inventory->quantity, 2) }}</td>
                    <td class="text-right">{{ $inventory->product->unitOfMeasure?->abbreviation ?? 'UND' }}</td>
                    <td class="text-right">${{ number_format($inventory->product->cost ?? 0, 2) }}</td>
                    <td class="text-right font-bold">
                        ${{ number_format($inventory->quantity * ($inventory->product->cost ?? 0), 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <strong>Total de Productos:</strong> {{ $inventories->unique('product_id')->count() }}
        <br>
        <strong>Cantidad Total:</strong> {{ number_format($inventories->sum('quantity'), 2) }}
        <br>
        <strong>Valor Total:</strong> ${{ number_format($inventories->sum(function ($inv) { return $inv->quantity * ($inv->product->cost ?? 0); }), 2) }}
    </div>

    <div class="footer">
        Sistema de Gestión de Bodegas - Generado por {{ auth()->user()->name }}
    </div>
</body>
</html>
