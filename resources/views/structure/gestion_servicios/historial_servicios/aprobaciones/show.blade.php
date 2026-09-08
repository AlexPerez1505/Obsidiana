@extends('structure.commercial_management.erp')

@section('title', 'Resumen ' . $service->service_number)

@section('erp_content')
    @php
        $totalRefacciones = $service->spareParts->sum('subtotal');
        $tech = $service->service_type === 'interno' ? $service->internalTechnician : $service->externalTechnician;
    @endphp

    <style>
        .resumen-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; align-items: start; }
        .resumen-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 18px; box-shadow: var(--shadow); }
        .resumen-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; margin: 0 0 16px; color: var(--text); }
        .resumen-title svg { color: var(--muted); flex-shrink: 0; }
        .resumen-detail { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
        .resumen-detail:last-child { border-bottom: none; padding-bottom: 0; }
        .resumen-detail--top { align-items: flex-start; }
        .resumen-label { color: var(--muted); font-weight: 600; letter-spacing: .03em; }
        .resumen-value { color: var(--text); text-align: right; max-width: 60%; }
        .resumen-value--wrap { white-space: pre-wrap; word-break: break-word; }
        .resumen-badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .resumen-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .resumen-badge.ok { background: var(--green-soft); color: var(--green); }
        .resumen-badge.warn { background: var(--accent-soft); color: var(--accent); }
        .resumen-badge.info { background: var(--primary-soft); color: var(--primary); }
        .resumen-badge.neutral { background: var(--surface-2); color: var(--muted); }
        .resumen-list { margin: 0; padding-left: 16px; font-size: 13px; color: var(--text); }
        .resumen-list li { margin-bottom: 6px; }
        .resumen-list li:last-child { margin-bottom: 0; }
        .resumen-empty { text-align: center; padding: 28px 10px; color: var(--muted); }
        .resumen-total-row { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; }
        .resumen-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        @media (max-width: 900px) { .resumen-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Resumen de Orden</h1>
            <span class="erp-count">{{ $service->service_number }}</span>
        </div>
        <a href="{{ route('gestion.servicios.historial.aprobaciones.index') }}" class="erp-btn ghost">Volver a aprobaciones</a>
    </div>

    <div class="resumen-grid">
        <!-- Resumen del servicio -->
        <div class="resumen-card">
            <h3 class="resumen-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Resumen del servicio
            </h3>

            <div class="resumen-detail">
                <span class="resumen-label">CLIENTE</span>
                <span class="resumen-value">{{ $service->customer?->nombre ?? '—' }} {{ $service->customer?->apellido ?? '' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TELÉFONO</span>
                <span class="resumen-value">{{ $service->customer?->phone ?? 'No registrado' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">CORREO</span>
                <span class="resumen-value">{{ $service->customer?->email ?? 'No registrado' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TIPO DE SERVICIO</span>
                <span class="resumen-value" style="text-transform:capitalize;">{{ $service->service_type === 'externo' ? 'Mantenimiento externo' : 'Mantenimiento interno' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">ESTATUS</span>
                <span class="resumen-value" style="text-transform:capitalize;">{{ $service->status }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">PASO ACTUAL</span>
                <span class="resumen-value">{{ $service->currentStep?->name ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">FECHA DE REGISTRO</span>
                <span class="resumen-value">{{ $service->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TIPO DE EQUIPO</span>
                <span class="resumen-value">{{ $service->serviceEquipment?->type_text ?? '—' }} | {{ $service->serviceEquipment?->subtype_text ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">MARCA / MODELO</span>
                <span class="resumen-value">{{ $service->serviceEquipment?->brand_text ?? '—' }} {{ $service->serviceEquipment?->model_text ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">NO. DE SERIE</span>
                <span class="resumen-value">{{ $service->serviceEquipment?->serial_number ?? '—' }}</span>
            </div>
            <div class="resumen-detail resumen-detail--top">
                <span class="resumen-label">DESCRIPCIÓN</span>
                <span class="resumen-value resumen-value--wrap">{{ $service->serviceEquipment?->description ?? '—' }}</span>
            </div>
            <div class="resumen-detail resumen-detail--top">
                <span class="resumen-label">OBSERVACIONES</span>
                <span class="resumen-value resumen-value--wrap">{{ $service->serviceEquipment?->observations ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TÉCNICO</span>
                <span class="resumen-value" style="font-weight:700;">{{ $tech?->name ?? '—' }}</span>
            </div>
            @if($service->service_type === 'externo' && $tech)
                <div class="resumen-detail">
                    <span class="resumen-label">ESPECIALIDAD</span>
                    <span class="resumen-value">{{ $tech->specialty ?? 'No registrada' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">EMPRESA</span>
                    <span class="resumen-value">{{ $tech->company ?? 'No registrada' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">UBICACIÓN</span>
                    <span class="resumen-value">{{ $tech->location ?? 'No registrada' }}</span>
                </div>
            @endif
            @if($tech)
                <div class="resumen-detail">
                    <span class="resumen-label">TELÉFONO TÉCNICO</span>
                    <span class="resumen-value">{{ $tech->phone ?? 'No registrado' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">CORREO TÉCNICO</span>
                    <span class="resumen-value">{{ $tech->email ?? 'No registrado' }}</span>
                </div>
            @endif
        </div>

        <!-- Cotización de refacciones -->
        <div class="resumen-card">
            <h3 class="resumen-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Cotización de refacciones
            </h3>

            @if($service->spareParts->isEmpty())
                <div class="resumen-empty">No se agregaron refacciones.</div>
            @else
                <ul class="resumen-list">
                    @foreach($service->spareParts as $part)
                        <li>{{ $part->nombre }} x{{ $part->cantidad }} — ${{ number_format($part->subtotal, 2) }}</li>
                    @endforeach
                </ul>
                <div class="resumen-detail resumen-total-row">
                    <span class="resumen-label">TOTAL REFACCIONES</span>
                    <span class="resumen-value" style="font-size:18px; font-weight:800; color:var(--primary);">${{ number_format($totalRefacciones, 2) }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="resumen-actions">
        <form action="{{ route('gestion.servicios.historial.deny', $service) }}" method="POST" onsubmit="return confirm('¿Denegar esta orden?')">
            @csrf
            <button type="submit" class="erp-btn danger">Denegar</button>
        </form>
        <form action="{{ route('gestion.servicios.historial.approve', $service) }}" method="POST">
            @csrf
            <button type="submit" class="erp-btn">Aprobar</button>
        </form>
    </div>
@endsection
