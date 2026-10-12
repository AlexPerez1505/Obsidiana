@extends('structure.gestion_servicios.layout')

@section('title', 'Servicio ' . $service->service_number)

@section('service_content')
    @php
        $badgeClass = match ($service->status) {
            'en_progreso' => 'active',
            'registrado' => 'upcoming',
            default => 'finished',
        };
        $tecnico = $service->service_type === 'interno'
            ? $service->internalTechnician?->name
            : $service->externalTechnician?->name;
        $equipo = $service->serviceEquipment;
    @endphp

    <style>
        .service-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 18px;
        }
        .detail-card {
            padding: 20px;
        }
        .detail-card h2.page-title {
            margin-bottom: 4px;
        }
        .detail-sub {
            color: var(--muted, #8ba3c7);
            font-size: 13px;
            margin-bottom: 18px;
        }
        .detail-rows {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .detail-row {
            padding: 10px 12px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 9px;
        }
        .detail-row b {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--muted, #8ba3c7);
            margin-bottom: 3px;
        }
        .detail-actions {
            display: grid;
            gap: 10px;
            align-content: start;
        }
        .btn-reporte {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 9px;
            background: #22C55E;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
        .btn-reporte:hover {
            background: #16a34a;
        }
        .btn-volver {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,.14);
            color: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        @media (max-width: 860px) {
            .service-detail-grid,
            .detail-rows {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="service-detail-grid">
        <div class="card detail-card">
            <h2 class="page-title">{{ $service->service_number ?? 'OS-'.$service->id }}</h2>
            <p class="detail-sub">Detalle del servicio de mantenimiento</p>

            <div class="detail-rows">
                <div class="detail-row">
                    <b>Cliente</b>
                    {{ trim(($service->customer?->nombre ?? '').' '.($service->customer?->apellido ?? '')) ?: '—' }}
                </div>
                <div class="detail-row">
                    <b>Tipo de servicio</b>
                    <span style="text-transform:capitalize;">{{ $service->service_type }}</span>
                </div>
                <div class="detail-row">
                    <b>Estatus</b>
                    <span class="service-badge {{ $badgeClass }}">{{ $service->status }}</span>
                </div>
                <div class="detail-row">
                    <b>Paso actual</b>
                    {{ $service->currentStep?->name ?? '—' }}
                </div>
                <div class="detail-row">
                    <b>Técnico</b>
                    {{ $tecnico ?? 'Sin asignar' }}
                </div>
                <div class="detail-row">
                    <b>Fecha de registro</b>
                    {{ $service->created_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
                <div class="detail-row">
                    <b>Reporte</b>
                    {{ $service->reporte_data ? 'Generado' : 'Pendiente' }}
                </div>
                @if ($equipo)
                    <div class="detail-row">
                        <b>Equipo</b>
                        {{ collect([$equipo->type_text, $equipo->subtype_text, $equipo->brand_text, $equipo->model_text])->filter()->implode(' · ') ?: '—' }}
                    </div>
                    <div class="detail-row">
                        <b>Número de serie</b>
                        {{ $equipo->serial_number ?: '—' }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card detail-card detail-actions">
            <h2 class="page-title" style="font-size:16px;">Acciones</h2>
            <a class="btn-reporte" href="{{ route('gestion.servicios.mantenimiento.reporte', $service) }}">
                @if ($service->reporte_data)
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Ver reporte
                @else
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Empezar reporte
                @endif
            </a>
            <a class="btn-volver" href="{{ $service->service_type === 'externo'
                ? route('gestion.servicios.mantenimiento.index', ['tipo' => 'externo'])
                : route('gestion.servicios.mantenimiento.index', ['tecnico' => $service->internal_technician_id]) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Volver a mantenimiento
            </a>
        </div>
    </div>
@endsection
                                                                                                                                                                                                                                                                                                                                                                                                                               