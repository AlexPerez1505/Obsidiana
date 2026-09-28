@extends('structure.commercial_management.erp')

@section('title', 'Orden de servicio · ' . ($service->service_number ?? 'OS-' . $service->id))

@section('erp_content')
    @php
        $equipment = $service->serviceEquipment;
        $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
        $technician = $service->internalTechnician?->name ?? '—';
        $inputStyle = 'width:100%; padding:11px 13px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px;';
        $readonlyStyle = $inputStyle . ' background:var(--surface-2, var(--surface)); color:var(--muted);';
        $labelStyle = 'display:block; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px;';
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <a href="{{ route('gestion.servicios.servicio_externo.ver', $service) }}" class="erp-back" title="Volver">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <h1 class="erp-h1">Orden de servicio · {{ $service->service_number ?? ('OS-' . $service->id) }}</h1>
            <span class="erp-badge info"><span class="dot"></span>Externo interno</span>
        </div>
    </div>

    <div style="margin-bottom:14px; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em;">Paso 1 de 4 · Datos y fechas</div>

    <form method="POST" action="{{ route('gestion.servicios.servicio_externo.orden_servicio.paso2', $service) }}">
        @csrf

        <div class="erp-card pad" style="max-width:720px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
                <div>
                    <label style="{{ $labelStyle }}">Cliente</label>
                    <div style="{{ $readonlyStyle }}">{{ $customerName }}</div>
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Técnico responsable</label>
                    <div style="{{ $readonlyStyle }}">{{ $technician }}</div>
                </div>
                <div>
                    <label for="fecha_ingreso" style="{{ $labelStyle }}">Fecha de ingreso</label>
                    <input type="date" id="fecha_ingreso" name="fecha_ingreso" required
                           value="{{ old('fecha_ingreso', $service->created_at?->format('Y-m-d')) }}" style="{{ $inputStyle }}">
                    @error('fecha_ingreso') <div style="color:var(--danger); font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="fecha_mantenimiento" style="{{ $labelStyle }}">Fecha de mantenimiento</label>
                    <input type="date" id="fecha_mantenimiento" name="fecha_mantenimiento" required
                           value="{{ old('fecha_mantenimiento', now()->format('Y-m-d')) }}" style="{{ $inputStyle }}">
                    @error('fecha_mantenimiento') <div style="color:var(--danger); font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div style="margin-top:18px; display:flex; gap:10px; align-items:center;">
            <button type="submit" class="erp-btn">Siguiente →</button>
            <a href="{{ route('gestion.servicios.servicio_externo.ver', $service) }}" class="erp-btn ghost">Cancelar</a>
        </div>
    </form>
@endsection
