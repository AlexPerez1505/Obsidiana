<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de viáticos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:10px; }
        h1 { color:#2563eb; margin:0 0 4px; } p { margin:0 0 14px; color:#64748b; }
        table { width:100%; border-collapse:collapse; } th { background:#eaf2ff; text-align:left; }
        th, td { padding:7px; border:1px solid #cbd5e1; } .money { text-align:right; }
        tfoot td { font-weight:bold; background:#f8fafc; }
    </style>
</head>
<body>
    <h1>Reporte de viáticos</h1>
    <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead><tr><th>Fecha</th><th>Lugar</th><th>Camioneta</th><th>Usuario</th><th>Descripción</th><th class="money">Casetas</th><th class="money">Gasolina</th><th class="money">Viáticos</th><th class="money">Adicional</th><th class="money">Total</th></tr></thead>
        <tbody>
        @foreach ($viatics as $v)
            <tr>
                <td>{{ optional($v->expense_date)->format('d/m/Y') ?: $v->created_at->format('d/m/Y') }}</td>
                <td>{{ $v->place ?: 'Sin lugar' }}</td>
                <td>{{ $v->vehicle_name ?: 'Sin camioneta' }}</td>
                <td>{{ $v->user?->name }}</td>
                <td>{{ $v->description }}</td>
                <td class="money">${{ number_format($v->tolls, 2) }}</td>
                <td class="money">${{ number_format($v->fuel, 2) }}</td>
                <td class="money">${{ number_format($v->meals + $v->lodging, 2) }}</td>
                <td class="money">${{ number_format($v->additional, 2) }}</td>
                <td class="money">${{ number_format($v->total_computed, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><td colspan="9">Total general</td><td class="money">${{ number_format($total, 2) }}</td></tr></tfoot>
    </table>
</body>
</html>
