<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Servicio {{ $service->service_number ?? $service->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 24px; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 22px; color: #2563eb; }
        .header p { margin: 4px 0 0; color: #6b7280; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 10px; }
        .row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
        .label { color: #6b7280; font-weight: 700; }
        .value { color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .right { text-align: right; }
        .totals { width: 300px; margin-left: auto; margin-top: 16px; }
        .totals .row { border-bottom: none; }
        .total { font-weight: 800; font-size: 14px; }
        .anticipo { color: #dc2626; }
        .pagar { font-size: 16px; font-weight: 800; }
        .chip { display: inline-block; padding: 4px 8px; background: #f3f4f6; border-radius: 12px; margin: 0 4px 4px 0; }
        .description { background: #f9fafb; padding: 12px; border-radius: 8px; line-height: 1.5; }
    </style>
</head>
<body>
    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $equipment = $service->serviceEquipment;
        $partidas = $maintenance->partidas_remision ?? [];
        $checklist = $maintenance->checklist ?? [];
        $startDate = $service->started_at?->format('Y-m-d') ?? $service->created_at?->format('Y-m-d');
        $endDate = $maintenance->proximo_mantenimiento?->format('Y-m-d') ?? now()->addMonths(3)->format('Y-m-d');
        $subtotal = collect($partidas)->sum(fn($p) => (float)($p['cantidad'] ?? 0) * (float)($p['precio_unitario'] ?? 0)) + (float)($maintenance->envio ?? 0);
        $iva = ($maintenance->requiere_iva ?? false) ? $subtotal * 0.16 : 0;
        $total = $subtotal + $iva;
        $anticipo = (float)($maintenance->anticipo ?? 0);
        $pagar = $total - $anticipo;
    @endphp

    <div class="header">
        <h1>ORDEN DE SERVICIO</h1>
        <p>{{ $service->service_number ? str_replace('NS-', 'OS-', $service->service_number) : 'OS-' . $service->id }}</p>
    </div>

    <div class="section">
        <div class="section-title">Revisión Final</div>
        <div class="row"><span class="label">Cliente</span><span class="value">{{ strtoupper($customerName) }}</span></div>
        <div class="row"><span class="label">Equipo</span><span class="value">{{ $equipment->type_text ?? 'N/A' }}</span></div>
        <div class="row"><span class="label">Fechas</span><span class="value">{{ $startDate }} / {{ $endDate }}</span></div>
        <div class="row"><span class="label">Técnico</span><span class="value">{{ trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? '')) ?: ($service->internalTechnician->name ?? 'N/A') }}</span></div>
        <div class="row"><span class="label">Identificación</span><span class="value">{{ $equipment->brand_text ?? '' }} {{ $equipment->model_text ?? '' }} | SN: {{ $equipment->serial_number ?? 'N/A' }}</span></div>
        <div class="row"><span class="label">Próx. Mto.</span><span class="value">{{ $endDate }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">Checklist Procesado</div>
        <table>
            <thead>
                <tr><th>Item</th><th>Sección</th><th>Estatus</th></tr>
            </thead>
            <tbody>
                @foreach ($checklist as $item)
                    @php $done = is_array($item) ? ($item['done'] ?? false) : false; $text = is_array($item) ? ($item['text'] ?? '') : $item; @endphp
                    <tr>
                        <td>{{ $text }}</td>
                        <td>General</td>
                        <td>{{ $done ? 'Realizado' : 'Pendiente' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Partidas para remisión</div>
        <table>
            <thead>
                <tr><th>Item</th><th>Descripción</th><th>Unidad</th><th class="right">Cant.</th><th class="right">P. Unit.</th><th class="right">Importe</th></tr>
            </thead>
            <tbody>
                @foreach ($partidas as $partida)
                    @php $importe = (float)($partida['cantidad'] ?? 0) * (float)($partida['precio_unitario'] ?? 0); @endphp
                    <tr>
                        <td>{{ $partida['item'] ?? '' }}</td>
                        <td>{{ $partida['descripcion'] ?? '' }}</td>
                        <td>{{ $partida['unidad'] ?? '' }}</td>
                        <td class="right">{{ number_format($partida['cantidad'] ?? 0, 2) }}</td>
                        <td class="right">${{ number_format($partida['precio_unitario'] ?? 0, 2) }}</td>
                        <td class="right">${{ number_format($importe, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span class="label">Envío</span><span class="value">${{ number_format($maintenance->envio ?? 0, 2) }}</span></div>
            <div class="row"><span class="label">Subtotal</span><span class="value">${{ number_format($subtotal, 2) }}</span></div>
            <div class="row"><span class="label">IVA (16%)</span><span class="value">${{ number_format($iva, 2) }}</span></div>
            <div class="row total"><span class="label">Total</span><span class="value">${{ number_format($total, 2) }}</span></div>
            <div class="row anticipipo"><span class="label">Anticipo</span><span class="value">-${{ number_format($anticipo, 2) }}</span></div>
            <div class="row pagar"><span class="label">Pagar</span><span class="value">${{ number_format($pagar, 2) }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Descripción general remisión</div>
        <div class="description">{{ $maintenance->descripcion_general ?? '' }}</div>
    </div>
</body>
</html>
