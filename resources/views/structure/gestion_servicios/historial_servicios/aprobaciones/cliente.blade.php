@extends('layouts.public')

@section('title', 'Resumen del servicio')

@push('head')
<style>
    .area-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .area-title { font-size:22px; margin:0; }
    .area-subtitle { font-size:13px; color:var(--muted); margin:4px 0 0; }
    .resumen-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:18px; }
    .resumen-field label { font-size:12px; color:var(--muted); font-weight:700; margin-bottom:4px; display:block; text-transform:uppercase; letter-spacing:.03em; }
    .resumen-field .value { font-size:15px; color:var(--text); }
    .badge { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
    .badge.ok { background:var(--green-soft); color:var(--green); }
    .badge.warn { background:var(--accent-soft); color:var(--accent); }
    .badge.danger { background:var(--danger-soft); color:var(--danger); }
    .cotizacion-table { width:100%; border-collapse:collapse; font-size:14px; margin-top:16px; }
    .cotizacion-table th, .cotizacion-table td { padding:14px 16px; border-bottom:1px solid var(--border); text-align:left; vertical-align:middle; }
    .cotizacion-table th { font-weight:700; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.03em; }
    .cotizacion-table td { vertical-align:middle; }
    .cotizacion-table img { width:48px; height:48px; object-fit:cover; border-radius:8px; }
    .cotizacion-table tr:last-child td { border-bottom:none; }
    .cotizacion-total { margin-top:18px; display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:flex-end; gap:28px; }
    .cotizacion-total > div { text-align:right; }
    .cotizacion-total .big { font-size:22px; font-weight:800; color:var(--primary); }
    .cotizacion-total .muted { font-size:13px; color:var(--muted); display:block; margin-bottom:4px; }
    .decision-wrap { display:flex; align-items:center; justify-content:flex-end; gap:12px; margin-top:24px; }
    .decision-msg { text-align:right; color:var(--muted); font-size:14px; margin-top:24px; }
    .alert { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:600; }
    .alert.ok { background:var(--green-soft); color:var(--green); }
    .alert.err { background:var(--danger-soft); color:var(--danger); }
    .section-title { font-size:16px; margin:0 0 6px; }
</style>
@endpush

@php
    $equipment = $service->serviceEquipment;
    $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
    $technician = $service->service_type === 'externo'
        ? ($service->externalTechnician?->name ?? '—')
        : ($service->internalTechnician?->name ?? '—');
    $statusClass = match ($service->status) {
        'aprobado', 'en_progreso', 'completado' => 'ok',
        'registrado', 'pendiente' => 'warn',
        'cancelado', 'rechazado' => 'danger',
        default => 'warn',
    };
    $hasCotizacion = $service->spareParts->isNotEmpty() || ($service->mano_obra ?? 0) > 0;
    $totalRefacciones = $service->spareParts->sum('subtotal');
    $totalCotizacion = $totalRefacciones + ($service->mano_obra ?? 0);
@endphp

@section('content')
<div class="card">
    <div class="area-header">
        <div>
            <h1 class="area-title">Resumen del servicio</h1>
            <p class="area-subtitle">Folio {{ $service->service_number ?? ('OS-' . $service->id) }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert ok">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert err">{{ session('error') }}</div>
    @endif

    <div class="resumen-grid">
        <div class="resumen-field">
            <label># Servicio</label>
            <div class="value">{{ $service->service_number ?? ('OS-' . $service->id) }}</div>
        </div>
        <div class="resumen-field">
            <label>Cliente</label>
            <div class="value">{{ $customerName }}</div>
        </div>
        <div class="resumen-field">
            <label>Equipo</label>
            <div class="value">{{ $equipment?->type_text ?? '—' }}</div>
        </div>
        <div class="resumen-field">
            <label>Marca / Modelo / Serie</label>
            <div class="value">
                {{ $equipment?->brand_text ?? '—' }} /
                {{ $equipment?->model_text ?? '—' }} /
                {{ $equipment?->serial_number ?? '—' }}
            </div>
        </div>
        <div class="resumen-field">
            <label>Técnico</label>
            <div class="value">{{ $technician }}</div>
        </div>
        <div class="resumen-field">
            <label>Tipo de servicio</label>
            <div class="value" style="text-transform:capitalize;">{{ $service->service_type ?? '—' }}</div>
        </div>
        <div class="resumen-field">
            <label>Estado</label>
            <div class="value"><span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $service->status ?? 'registrado')) }}</span></div>
        </div>
        <div class="resumen-field">
            <label>Fecha de registro</label>
            <div class="value">{{ $service->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
        @if ($equipment?->description)
            <div class="resumen-field" style="grid-column:1/-1;">
                <label>Descripción del equipo</label>
                <div class="value">{{ $equipment->description }}</div>
            </div>
        @endif
    </div>

    <div style="margin-top:24px; border-top:1px solid var(--border); padding-top:20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
                <h2 class="section-title">Reporte de mantenimiento</h2>
                <p style="margin:0; font-size:14px; color:var(--muted);">
                    @if ($service->reporte_data)
                        <span style="color:var(--green); font-weight:700;">● Reporte guardado</span>
                    @else
                        <span style="color:var(--accent); font-weight:700;">● Reporte pendiente</span>
                    @endif
                </p>
            </div>
            <div>
                @if ($service->reporte_data)
                    <a href="{{ route('gestion.servicios.mantenimiento.reporte.raw', $service) }}?descargar=1" target="_blank" class="btn btn--ghost">
                        Descargar PDF reporte
                    </a>
                @else
                    <span style="color:var(--muted); font-size:13px;">No disponible</span>
                @endif
            </div>
        </div>
    </div>

    <div style="margin-top:24px; border-top:1px solid var(--border); padding-top:20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:12px;">
            <div>
                <h2 class="section-title">Cotización</h2>
                <p style="margin:0; font-size:14px; color:var(--muted);">
                    @if ($hasCotizacion)
                        <span style="color:var(--green); font-weight:700;">● Cotización guardada</span>
                    @else
                        <span style="color:var(--accent); font-weight:700;">● Cotización pendiente</span>
                    @endif
                </p>
            </div>
            <div>
                @if ($hasCotizacion)
                    <a href="{{ route('gestion.servicios.area_endoscopia.cotizacion.pdf', $service) }}" class="btn btn--ghost">
                        Descargar PDF remisión
                    </a>
                @else
                    <span style="color:var(--muted); font-size:13px;">No disponible</span>
                @endif
            </div>
        </div>

        @if ($hasCotizacion)
            <table class="cotizacion-table">
                <thead>
                    <tr>
                        <th style="text-align:center;">FOTO</th>
                        <th>REFACCIÓN</th>
                        <th style="text-align:center;">CANTIDAD</th>
                        <th style="text-align:right;">P. UNIT.</th>
                        <th style="text-align:right;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($service->spareParts as $part)
                        <tr>
                            <td style="text-align:center;">
                                @if ($part->refaccion?->photo_path)
                                    <img src="{{ asset('storage/' . $part->refaccion->photo_path) }}" alt="{{ $part->nombre }}">
                                @else
                                    <span style="color:var(--muted); font-size:12px;">Sin foto</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ $part->nombre }}</div>
                                <div style="font-size:12px; color:var(--muted);">{{ $part->refaccion?->subtype ?? '—' }}</div>
                            </td>
                            <td style="text-align:center;">{{ $part->cantidad }}</td>
                            <td style="text-align:right;">${{ number_format($part->precio_unitario, 2) }}</td>
                            <td style="text-align:right;">${{ number_format($part->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="cotizacion-total">
                <div>
                    <span class="muted">Refacciones</span>
                    <div class="big" style="font-size:18px; color:var(--text);">${{ number_format($totalRefacciones, 2) }}</div>
                </div>
                <div>
                    <span class="muted">Mano de obra</span>
                    <div class="big" style="font-size:18px; color:var(--text);">${{ number_format($service->mano_obra ?? 0, 2) }}</div>
                </div>
                <div>
                    <span class="muted">Total cotización</span>
                    <div class="big">${{ number_format($totalCotizacion, 2) }}</div>
                </div>
            </div>
        @endif
    </div>

    @if ($service->customer_decision === null)
        <div style="margin-top:24px; border-top:1px solid var(--border); padding-top:20px;">
            <h2 class="section-title">¿Deseas aprobar el servicio?</h2>
            <p style="margin:0 0 16px; color:var(--muted); font-size:14px;">Selecciona una opción. El administrador revisará tu respuesta para continuar.</p>
            <div class="decision-wrap">
                <form action="{{ $decisionUrl }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="decision" value="rechazado">
                    <button type="submit" class="btn btn--danger" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Rechazar
                    </button>
                </form>
                <form action="{{ $decisionUrl }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="decision" value="aprobado">
                    <button type="submit" class="btn btn--green" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="20 6 9 17 4 12"/></svg>
                        Aprobar
                    </button>
                </form>
            </div>
        </div>
    @else
        <div style="margin-top:24px; border-top:1px solid var(--border); padding-top:20px;">
            <div class="decision-msg">
                Ya respondiste <strong>{{ ucfirst($service->customer_decision) }}</strong> el {{ $service->customer_decision_at?->format('d/m/Y H:i') ?? '—' }}.
                El administrador revisará tu respuesta.
            </div>
        </div>
    @endif
</div>
@endsection
