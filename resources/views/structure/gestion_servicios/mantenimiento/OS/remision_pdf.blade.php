<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Remisión {{ $service->service_number ?? $service->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .header-left { width: auto; }
        .logo img { max-height: 60px; width: auto; display: block; margin-bottom: 6px; }
        .logo-title { font-size: 20px; font-weight: 800; color: #2563eb; margin-bottom: 4px; }
        .logo span { display: block; font-size: 10px; color: #6b7280; font-weight: 400; }
        .header-right { width: auto; margin-left: auto; text-align: right; }
        .header-right h1 { margin: 0; font-size: 20px; color: #374151; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; text-align: right; }
        .header-right .number { color: #dc2626; font-size: 22px; font-weight: 900; margin-top: 4px; text-align: right; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .info-table .right { text-align: right; }
        .info-label { font-weight: 700; color: #111827; text-transform: uppercase; font-size: 11px; }
        .info-value { color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #3b82f6; color: #fff; padding: 10px 8px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .totals { width: 260px; margin-left: auto; margin-top: 16px; text-align: right; }
        .totals .row { margin-bottom: 8px; }
        .totals .label { font-weight: 700; color: #111827; }
        .totals .value { font-weight: 700; color: #111827; }
        .total-box { display: inline-block; background: #2563eb; color: #fff; padding: 10px 18px; border-radius: 4px; margin-top: 8px; font-weight: 800; }
        .note { margin-top: 24px; font-size: 12px; color: #374151; }
        .note strong { color: #111827; }
    </style>
</head>
<body>
    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $customerPhone = $service->customer->telefono ?? $service->customer->phone ?? $service->customer->celular ?? 'N/A';
        $customerAddress = $service->customer->direccion ?? $service->customer->address ?? $service->customer->calle ?? 'N/A';
        $equipment = $service->serviceEquipment;
        $partidas = $maintenance->partidas_remision ?? [];
        $deliveryDate = $service->finished_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $remisionNumber = $service->service_number ? str_replace('NS-', 'REM-', $service->service_number) : 'REM-' . $service->id;
        $subtotal = collect($partidas)->sum(fn($p) => (float)($p['cantidad'] ?? 0) * (float)($p['precio_unitario'] ?? 0));
        $total = $subtotal;
        $pagar = $total;
    @endphp

    <div class="header">
        <div class="header-left">
            <div class="logo">
                <img src="{{ public_path('images/logo.png') }}" alt="Grupo MediBuy">
                <div class="logo-title">Grupo MediBuy</div>
                <span>Venta de Equipo Médico</span>
            </div>
        </div>
        <div class="header-right">
            <h1>Remisión Mantenimiento</h1>
            <div class="number">No.{{ $remisionNumber }}</div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%"><span class="info-label">Cliente:</span> <span class="info-value">{{ strtoupper($customerName) }}</span></td>
            <td width="50%" class="right"><span class="info-label">Fecha:</span> <span class="info-value">{{ $deliveryDate }}</span></td>
        </tr>
        <tr>
            <td><span class="info-label">Teléfono:</span> <span class="info-value">{{ $customerPhone }}</span></td>
            <td class="right"><span class="info-label">Vigencia:</span> <span class="info-value">10 DÍAS</span></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Unidad</th>
                <th>Cantidad</th>
                <th>Descripción</th>
                <th class="right">P. Unitario</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($partidas as $partida)
                @php $importe = (float)($partida['cantidad'] ?? 0) * (float)($partida['precio_unitario'] ?? 0); @endphp
                <tr>
                    <td>{{ $partida['unidad'] ?? '' }}</td>
                    <td>{{ number_format($partida['cantidad'] ?? 0, 0) }}</td>
                    <td>{{ $partida['descripcion'] ?? '' }}</td>
                    <td class="right">${{ number_format($partida['precio_unitario'] ?? 0, 2) }}</td>
                    <td class="right">${{ number_format($importe, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span class="label">Subtotal:</span> <span class="value">${{ number_format($subtotal, 2) }}</span></div>
        <div class="row"><span class="label">Total:</span> <span class="value">${{ number_format($total, 2) }}</span></div>
        <div class="total-box">Total a pagar: ${{ number_format($pagar, 2) }}</div>
    </div>

    <div class="note">
        <strong>Descripción:</strong> {{ $maintenance->descripcion_general ?? '' }}
    </div>
</body>
</html>
