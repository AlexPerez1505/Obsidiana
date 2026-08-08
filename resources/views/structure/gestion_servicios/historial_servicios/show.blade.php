@extends('structure.gestion_servicios.layout')

@section('title', 'Servicio ' . $service->service_number)

@section('service_content')
<style>
.resumen-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; align-items: start; }
.resumen-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 18px; box-shadow: var(--shadow); }
.resumen-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; margin: 0 0 16px; color: var(--text); }
.resumen-detail { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
.resumen-detail:last-child { border-bottom: none; padding-bottom: 0; }
.resumen-label { color: var(--muted); font-weight: 600; }
.resumen-value { color: var(--text); text-align: right; }
.resumen-step { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--border); border-radius: 14px; margin-bottom: 10px; background: var(--surface); }
.resumen-step--active { border-color: var(--primary); background: var(--primary-soft); }
.resumen-step--done { border-color: var(--green); background: var(--green-soft); }
.resumen-step-name { font-size: 14px; font-weight: 700; color: var(--text); }
.resumen-step-status { font-size: 12px; font-weight: 700; margin-top: 2px; }
.qr-code { font-family: monospace; word-break: break-all; background: var(--surface-2); padding: 10px; border-radius: 8px; font-size: 13px; }
@media (max-width: 900px) { .resumen-grid { grid-template-columns: 1fr; } }
</style>

<div class="card">
    <div class="wizard-header" style="margin-bottom:18px;">
        <div>
            <h1 class="section-title" style="font-size:22px; margin:0;">Servicio {{ $service->service_number }}</h1>
            <p class="muted" style="margin:6px 0 0;">{{ $service->service_type === 'externo' ? 'Mantenimiento externo' : 'Mantenimiento interno' }}</p>
        </div>
    </div>

    <div class="resumen-grid">
        <!-- Acción / QR -->
        <div class="resumen-card">
            <h3 class="resumen-title">Código del producto</h3>
            <div class="resumen-detail">
                <span class="resumen-label">Product code</span>
                <span class="resumen-value" style="font-weight:700;">{{ $service->serviceEquipment->product_code ?? 'N/A' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">Paso actual</span>
                <span class="resumen-value" style="font-weight:700;">{{ $service->currentStep?->name ?? 'Completado' }}</span>
            </div>
            @if($service->qr_token)
            <div style="margin-top:14px;">
                <p class="muted" style="font-size:13px; margin:0 0 8px;">QR activo:</p>
                <div class="qr-code">{{ $service->qr_token }}</div>
                <a href="{{ route('qr.show', $service->qr_token) }}" target="_blank" class="btn" style="margin-top:12px; display:inline-flex;">Abrir enlace QR</a>
            </div>
            @endif
        </div>

        <!-- Ficha Técnica -->
        <div class="resumen-card">
            <h3 class="resumen-title">Ficha Técnica</h3>
            <div class="resumen-detail">
                <span class="resumen-label">Identificación</span>
                <span class="resumen-value">{{ $service->serviceEquipment->type_text ?? '—' }} | {{ $service->serviceEquipment->subtype_text ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">No. de serie</span>
                <span class="resumen-value">{{ $service->serviceEquipment->serial_number ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">Marca / Modelo</span>
                <span class="resumen-value">{{ $service->serviceEquipment->brand_text ?? '—' }} {{ $service->serviceEquipment->model_text ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">Médico / Titular</span>
                <span class="resumen-value">{{ $service->customer?->nombre ?? '—' }} {{ $service->customer?->apellido ?? '' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">Responsable</span>
                <span class="resumen-value" style="font-weight:700;">
                    @if($service->service_type === 'interno')
                        {{ $service->internalTechnician?->name ?? '—' }}
                    @else
                        {{ $service->externalTechnician?->name ?? '—' }}
                    @endif
                </span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">Validación OS</span>
                <span class="resumen-value" style="color:var(--accent); font-weight:700;">{{ ucfirst($service->status) }}</span>
            </div>
        </div>

        <!-- Ruta de Trabajo -->
        <div class="resumen-card" style="grid-column:1/-1;">
            <h3 class="resumen-title">Ruta de Trabajo</h3>
            @foreach($service->serviceTrackings as $track)
                @php
                    $stateClass = match($track->status) {
                        'completado' => 'resumen-step--done',
                        'pendiente' => $track->id === $service->serviceTrackings->last()->id ? 'resumen-step--active' : '',
                        default => '',
                    };
                    $statusColor = match($track->status) {
                        'completado' => 'var(--green)',
                        'pendiente' => 'var(--primary)',
                        default => 'var(--muted)',
                    };
                    $statusText = match($track->status) {
                        'completado' => 'COMPLETADO',
                        'pendiente' => 'PENDIENTE',
                        default => strtoupper($track->status),
                    };
                @endphp
                <div class="resumen-step {{ $stateClass }}">
                    <div style="flex:1;">
                        <div class="resumen-step-name">{{ $track->serviceStep?->name ?? 'Paso' }}</div>
                        <div class="resumen-step-status" style="color:{{ $statusColor }};">{{ $statusText }}</div>
                    </div>
                    @if($track->qr_token)
                        <a href="{{ route('qr.show', $track->qr_token) }}" target="_blank" class="btn btn--ghost" style="padding:6px 12px; font-size:12px;">QR</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
