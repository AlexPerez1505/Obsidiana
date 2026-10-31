@extends('structure.gestion_servicios.layout')

@php
    $services = $services ?? collect([]);
@endphp

@section('title', 'Área General')

@push('head')
<style>
.area-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:8px; }
.area-title { font-size:22px; margin:0; }
.area-subtitle { font-size:13px; color:var(--muted); margin:4px 0 0; }

.services-table { width:100%; border-collapse:collapse; font-size:14px; }
.services-table th, .services-table td { padding:12px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
.services-table th { font-weight:700; color:var(--muted); font-size:13px; }
.services-table tr:hover td { background:var(--primary-soft); }
.empty-state { text-align:center; padding:32px; color:var(--muted); font-size:13px; }
.badge { padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.badge.ok { background:var(--green-soft); color:var(--green); }
.badge.warn { background:var(--accent-soft); color:var(--accent); }
.badge.danger { background:var(--danger-soft); color:var(--danger); }
.muted-text { color:var(--muted); font-size:13px; }
</style>
@endpush

@section('service_content')
<x-ui.card>
    <div class="area-header">
        <div>
            <h1 class="area-title">Área General</h1>
            <p class="area-subtitle">Servicios registrados con equipo de otro tipo</p>
        </div>
        <a href="{{ route('gestion.servicios.registro') }}" class="btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700; text-decoration:none; background:var(--primary); color:#fff; border:1px solid var(--primary);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo servicio
        </a>
    </div>

    @if ($services->isNotEmpty())
        <table class="services-table">
            <thead>
                <tr>
                    <th># Servicio</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Marca / Modelo / Serie</th>
                    <th>Técnico</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    @php
                        $equipment = $service->serviceEquipment;
                        $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
                        $technician = $service->service_type === 'externo'
                            ? ($service->externalTechnician?->name ?? '—')
                            : ($service->internalTechnician?->name ?? '—');
                    @endphp
                    <tr>
                        <td><strong>{{ $service->service_number ?? ('OS-' . $service->id) }}</strong></td>
                        <td>{{ $customerName }}</td>
                        <td>{{ $equipment?->type_text ?? '—' }}</td>
                        <td>
                            <div>{{ $equipment?->brand_text ?? '—' }}</div>
                            <div class="muted-text">{{ $equipment?->model_text ?? '—' }}</div>
                            <div class="muted-text">{{ $equipment?->serial_number ?? '—' }}</div>
                        </td>
                        <td>{{ $technician }}</td>
                        <td>{{ $service->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <a href="{{ route('gestion.servicios.area_endoscopia.resumen', $service) }}" title="Ver resumen" style="color:var(--muted);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ route('gestion.servicios.area_endoscopia.edit', $service) }}" title="Editar" style="color:var(--muted);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form action="{{ route('gestion.servicios.area_endoscopia.destroy', $service) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este servicio?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Eliminar" style="background:none; border:none; padding:0; color:var(--danger); cursor:pointer;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                                <a href="{{ route('gestion.servicios.historial.aprobaciones.show', $service) }}" title="Mandar a aprobación" style="color:var(--green);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
                                </a>
                                <a href="{{ route('gestion.servicios.mantenimiento.reporte.raw', $service) }}" title="Hoja de reporte" style="color:var(--primary);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                </a>
                                <a href="{{ route('gestion.servicios.area_endoscopia.cotizacion', $service) }}" title="Cotización" style="color:var(--accent);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty-state">No hay servicios registrados con otro tipo de equipo.</p>
    @endif
</x-ui.card>
@endsection
