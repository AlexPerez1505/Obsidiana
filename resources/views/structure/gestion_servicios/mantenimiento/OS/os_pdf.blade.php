<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Servicio {{ $service->service_number ?? $service->id }}</title>
    <style>
        @page { margin: 8mm; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 10px; }
        .top-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
        .logo { text-align: center; }
        .logo img { max-height: 60px; width: auto; display: block; margin: 0 auto 6px; }
        .logo-title { font-size: 16px; font-weight: 800; color: #2563eb; }
        .logo span { display: block; font-size: 9px; color: #6b7280; font-weight: 400; }
        .title-center { text-align: center; flex: 1; }
        .title-center h1 { margin: 0; font-size: 20px; color: #111827; text-transform: uppercase; font-weight: 800; }
        .title-center p { margin: 4px 0 0; font-size: 10px; color: #6b7280; text-transform: uppercase; }
        .os-badge { background: #e0f2fe; color: #0369a1; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .section { margin-bottom: 8px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px; }
        .section-title { font-size: 12px; font-weight: 800; text-transform: uppercase; color: #374151; margin: 0 0 6px; }
        .info-table, .equipo-table { width: 100%; border-collapse: collapse; }
        .info-table td, .equipo-table td { padding: 5px 5px 5px 0; vertical-align: top; border-bottom: 1px solid #f3f4f6; }
        .info-table tr:last-child td, .equipo-table tr:last-child td { border-bottom: none; }
        .label { display: block; color: #6b7280; font-weight: 700; text-transform: uppercase; font-size: 9px; margin-bottom: 2px; }
        .value { color: #111827; font-weight: 600; }
        .two-col-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .two-col-table td { vertical-align: top; }
        .actions-cell-left { padding-right: 6px; }
        .actions-cell-right { padding-left: 6px; }
        .actions-box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px; }
        .actions-list { margin: 0; padding-left: 18px; }
        .actions-list li { margin-bottom: 2px; color: #374151; }
        .photo-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; table-layout: fixed; }
        .photo-cell { border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px; text-align: center; width: 33.33%; vertical-align: top; }
        .photo-cell img { width: 100%; max-height: 50px; object-fit: cover; border-radius: 6px; }
        .photo-placeholder { width: 100%; height: 50px; background: #f3f4f6; border-radius: 6px; margin-bottom: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; }
        .dictamen { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px; page-break-inside: avoid; }
        .dictamen-title { font-size: 12px; font-weight: 800; text-transform: uppercase; color: #374151; margin: 0 0 6px; }
        .dictamen-content { color: #374151; line-height: 1.25; }
    </style>
</head>
<body>
    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $customerPhone = $service->customer->telefono ?? $service->customer->phone ?? $service->customer->celular ?? 'N/A';
        $customerAddress = $service->customer->direccion ?? $service->customer->address ?? $service->customer->calle ?? 'N/A';
        $equipment = $service->serviceEquipment;
        $checklist = $maintenance->checklist ?? [];
        $techName = $service->externalTechnician
            ? trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''))
            : trim(($service->internalTechnician->name ?? '') . ' ' . ($service->internalTechnician->last_name ?? ''));
        $ingresoDate = $service->started_at?->format('d/m/Y') ?? $service->created_at?->format('d/m/Y') ?? 'N/A';
        $serviceDate = $service->finished_at?->format('d/m/Y') ?? $service->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $nextDate = $maintenance->proximo_mantenimiento?->format('d/m/Y') ?? now()->addMonths(3)->format('d/m/Y');
        $proxMonths = $maintenance->proximo_mantenimiento && $service->started_at
            ? ceil($service->started_at->diffInMonths($maintenance->proximo_mantenimiento))
            : 3;
        $clasificacion = ucfirst($maintenance->tipo_reparacion ?? 'Correctivo');
        $osNumber = $service->service_number ? str_replace('NS-', 'OS-', $service->service_number) : 'OS-' . $service->id;
        $acciones = [];
        foreach ($checklist as $c) {
            if (is_array($c) && ($c['done'] ?? false) && ($c['text'] ?? '')) {
                $acciones[] = $c['text'];
            }
        }
        if (empty($acciones) && $maintenance->descripcion) {
            $acciones = array_filter(array_map('trim', preg_split('/[,\.\s]+/', $maintenance->descripcion)));
        }
        $fotos = $maintenance->fotos ?? [];
    @endphp

    <div class="top-header">
        <div class="logo">
            <img src="{{ public_path('images/logo.png') }}" alt="Grupo MediBuy">
            <div class="logo-title">Grupo MediBuy</div>
            <span>Venta de Equipo Médico</span>
        </div>
        <div class="title-center">
            <h1>Orden de Servicio</h1>
            <p>Grupo Medibuy - Departamento Técnico</p>
        </div>
        <div class="os-badge">OS # {{ $service->id }}</div>
    </div>

    <div class="section">
        <div class="section-title">Información General</div>
        <table class="info-table">
            <tr>
                <td width="15%"><span class="label">Cliente</span></td>
                <td width="35%"><span class="value">{{ $customerName }}</span></td>
                <td width="15%"><span class="label">Fecha de Ingreso</span></td>
                <td width="35%"><span class="value">{{ $ingresoDate }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Representante</span></td>
                <td><span class="value">{{ $techName ?: 'N/A' }}</span></td>
                <td><span class="label">Fecha Servicio</span></td>
                <td><span class="value">{{ $serviceDate }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Teléfono</span></td>
                <td><span class="value">{{ $customerPhone }}</span></td>
                <td><span class="label">Próximo Serv.</span></td>
                <td><span class="value">{{ $nextDate }} ({{ $proxMonths }} meses)</span></td>
            </tr>
            <tr>
                <td><span class="label">Dirección</span></td>
                <td><span class="value">{{ $customerAddress }}</span></td>
                <td><span class="label">Clasificación</span></td>
                <td><span class="value">{{ $clasificacion }}</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Especificaciones del Equipo</div>
        <table class="equipo-table">
            <tr>
                <td width="15%"><span class="label">Equipo</span></td>
                <td width="35%"><span class="value">{{ $equipment->type_text ?? 'N/A' }}</span></td>
                <td width="15%"><span class="label">Marca</span></td>
                <td width="35%"><span class="value">{{ $equipment->brand_text ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Modelo</span></td>
                <td><span class="value">{{ $equipment->model_text ?? 'N/A' }}</span></td>
                <td><span class="label">No. de Serie</span></td>
                <td><span class="value">{{ $equipment->serial_number ?? 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <table class="two-col-table">
        <tr>
            <td width="30%" class="actions-cell-left">
                <div class="actions-box">
                    <div class="section-title">Acciones Realizadas</div>
                    @if (count($acciones))
                        <ul class="actions-list">
                            @foreach ($acciones as $accion)
                                <li>{{ $accion }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p style="color: #6b7280;">Sin acciones registradas</p>
                    @endif
                </div>
            </td>
            <td width="70%" class="actions-cell-right">
                <div class="actions-box">
                    <div class="section-title">Registro Fotográfico</div>
                    @if (count($fotos))
                        <table class="photo-table">
                            <tr>
                                @foreach ($fotos as $i => $foto)
                                    <td class="photo-cell">
                                        <img src="{{ $foto }}" alt="Perspectiva {{ $i + 1 }}">
                                        <div style="font-size: 10px; color: #6b7280;">Perspectiva {{ $i + 1 }}</div>
                                    </td>
                                @endforeach
                            </tr>
                        </table>
                    @else
                        <table class="photo-table">
                            <tr>
                                <td class="photo-cell">
                                    <div class="photo-placeholder">Sin imagen</div>
                                    <div style="font-size: 10px; color: #6b7280;">Perspectiva 1</div>
                                </td>
                                <td class="photo-cell">
                                    <div class="photo-placeholder">Sin imagen</div>
                                    <div style="font-size: 10px; color: #6b7280;">Perspectiva 2</div>
                                </td>
                                <td class="photo-cell">
                                    <div class="photo-placeholder">Sin imagen</div>
                                    <div style="font-size: 10px; color: #6b7280;">Perspectiva 3</div>
                                </td>
                            </tr>
                        </table>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="dictamen">
        <div class="dictamen-title">Dictamen / Observaciones</div>
        <div class="dictamen-content">{{ $maintenance->descripcion_general ?? $maintenance->descripcion ?? 'Sin observaciones' }}</div>
    </div>
</body>
</html>
