@extends('structure.gestion_servicios.layout')

@section('title', 'Ruta de Trabajo - ' . ($service->service_number ?? $service->id))

@section('service_content')
    <style>
        .route-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .route-page-header .title-group h2 {
            margin: 0;
            font-size: 28px;
            color: #fff;
        }
        :root[data-theme="light"] .route-page-header .title-group h2 { color: var(--text); }
        .route-page-header .title-group p {
            margin: 4px 0 0;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .route-page-header .title-group p { color: var(--muted); }
        .route-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .route-info-card {
            background: rgba(8,18,40,0.82);
            border: 1px solid rgba(34,197,94,0.55);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(34,197,94,0.2);
        }
        .route-info-card label {
            display: block;
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }
        .route-info-card span {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
        }
        :root[data-theme="light"] .route-info-card { background: rgba(15,23,42,0.06); border-color: rgba(15,23,42,0.14); box-shadow: 0 8px 28px rgba(15,23,42,0.08); }
        :root[data-theme="light"] .route-info-card label { color: var(--muted); }
        :root[data-theme="light"] .route-info-card span { color: var(--text); }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: rgba(34,197,94,0.35);
            border-radius: 2px;
        }
        .timeline-step {
            position: relative;
            margin-bottom: 22px;
            padding: 16px 18px;
            background: rgba(8,18,40,0.82);
            border: 1px solid rgba(34,197,94,0.35);
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35);
        }
        .timeline-step::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(34,197,94,0.55);
            z-index: 2;
        }
        .timeline-step.completed {
            border-color: rgba(34,197,94,0.75);
            background: rgba(34,197,94,0.08);
        }
        .timeline-step.completed::before {
            background: #22C55E;
            border-color: #22C55E;
            box-shadow: 0 0 10px rgba(34,197,94,0.65);
        }
        .timeline-step.current {
            border-color: #22C55E;
            background: rgba(34,197,94,0.12);
            box-shadow: 0 0 18px rgba(34,197,94,0.35);
        }
        .timeline-step.current::before {
            background: #22C55E;
            border-color: #fff;
            box-shadow: 0 0 16px rgba(34,197,94,0.85);
            animation: route-pulse 1.6s infinite;
        }
        .timeline-step.rejected {
            border-color: rgba(239,68,68,0.75);
            background: rgba(239,68,68,0.08);
        }
        .timeline-step.rejected::before {
            background: #ef4444;
            border-color: #ef4444;
            box-shadow: 0 0 10px rgba(239,68,68,0.65);
        }
        :root[data-theme="light"] .timeline-step { background: #fff; border-color: rgba(15,23,42,0.14); box-shadow: 0 8px 28px rgba(15,23,42,0.08); }
        :root[data-theme="light"] .timeline::before { background: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .timeline-step::before { background: rgba(15,23,42,0.12); border-color: rgba(15,23,42,0.25); }
        :root[data-theme="light"] .timeline-step.current { background: rgba(34,197,94,0.08); }
        @keyframes route-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        .step-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .step-order {
            font-size: 13px;
            font-weight: 800;
            color: #22C55E;
        }
        .step-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            flex: 1;
        }
        :root[data-theme="light"] .step-title { color: var(--text); }
        .step-meta {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            line-height: 1.5;
        }
        :root[data-theme="light"] .step-meta { color: var(--muted); }
        .step-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>

    <div class="catalog-card service-section">
        <div class="route-page-header">
            <div class="title-group">
                <h2>Ruta de Trabajo</h2>
                <p>{{ $service->service_number ?? 'NS-' . $service->id }} &mdash; {{ $service->service_type === 'interno' ? 'Servicio interno' : 'Servicio externo' }}</p>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn--ghost">Volver</a>
        </div>

        <div class="route-info">
            <div class="route-info-card">
                <label>Cliente</label>
                <span>{{ trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? '')) ?: 'N/A' }}</span>
            </div>
            <div class="route-info-card">
                <label>Técnico</label>
                <span>
                    @if ($service->service_type === 'interno')
                        {{ trim(($service->internalTechnician->nombre ?? '') . ' ' . ($service->internalTechnician->apellidos ?? '')) ?: 'N/A' }}
                    @else
                        {{ trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? '')) ?: 'N/A' }}
                    @endif
                </span>
            </div>
            <div class="route-info-card">
                <label>Equipo</label>
                <span>{{ $service->serviceEquipment->type_text ?? 'N/A' }} {{ $service->serviceEquipment->brand_text ?? '' }}</span>
            </div>
            <div class="route-info-card">
                <label>Estado general</label>
                <span style="text-transform: capitalize;">{{ $service->status }}</span>
            </div>
            <div class="route-info-card">
                <label>Paso actual</label>
                <span>{{ $service->currentStep?->name ?? 'Sin paso asignado' }}</span>
            </div>
        </div>
    </div>

    <div class="catalog-card service-section">
        <h3 style="margin:0 0 20px; color:#fff;">Progreso de pasos</h3>

        <div class="timeline">
            @foreach ($steps as $step)
                @php
                    $tracking = $trackingsByStep[$step->id] ?? null;
                    $isCompleted = $tracking && $tracking->status === 'completado';
                    $isRejected = $tracking && $tracking->status === 'rechazado';
                    $isCurrent = $service->current_step_id === $step->id;
                    $cssClass = $isCompleted ? 'completed' : ($isRejected ? 'rejected' : ($isCurrent ? 'current' : ''));
                    $badgeClass = $isCompleted ? 'finished' : ($isRejected ? 'upcoming' : ($isCurrent ? 'active' : 'upcoming'));
                    $badgeText = $isCompleted ? 'Completado' : ($isRejected ? 'Rechazado' : ($isCurrent ? 'En curso' : 'Pendiente'));
                @endphp
                <div class="timeline-step {{ $cssClass }}">
                    <div class="step-header">
                        <span class="step-order">#{{ $step->order }}</span>
                        <h4 class="step-title">{{ $step->name }}</h4>
                        <span class="service-badge {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>
                    <p class="step-meta">{{ $step->description }}</p>
                    @if ($tracking)
                        <p class="step-meta">
                            @if ($isCompleted && $tracking->finished_at)
                                Completado el {{ $tracking->finished_at->format('d/m/Y H:i') }}
                            @elseif ($tracking->started_at)
                                Iniciado el {{ $tracking->started_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    @endif
                    <div class="step-actions">
                        @if ($isCurrent && $step->requires_qr && $tracking?->qr_token)
                            <a href="{{ url('/qr/' . $tracking->qr_token) }}" target="_blank" class="service-action-btn" title="Ver QR">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                Ver QR
                            </a>
                        @endif
                        @if ($isCurrent && $step->requires_approval)
                            <span class="service-badge upcoming">Requiere aprobación</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        setInterval(function () {
            if (document.hidden) return;
            window.location.reload();
        }, 30000);
    </script>
@endsection
