@extends('structure.commercial_management.erp')

@section('title', 'Servicio Externo Interno')

@section('erp_content')
    @php
        $total = $services->count();
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Servicio externo interno</h1>
            <span class="erp-count">{{ $total }} {{ $total === 1 ? 'servicio' : 'servicios' }}</span>
        </div>
        <a href="{{ route('gestion.servicios.historial') }}" class="erp-btn ghost">Volver al historial</a>
    </div>

    <div class="erp-card">
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>Cliente</th>
                        <th>Equipo</th>
                        <th>Marca / Modelo / Serie</th>
                        <th>Técnico interno</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $equipment = $service->serviceEquipment;
                            $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
                            $badge = match($service->status) {
                                'entregado', 'completado' => 'ok',
                                'en_progreso' => 'info',
                                'aprobado' => 'warn',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="erp-strong">{{ $service->service_number ?? ('OS-' . $service->id) }}</td>
                            <td>{{ $customerName }}</td>
                            <td>{{ $equipment?->type_text ?? '—' }}</td>
                            <td>
                                <div>{{ $equipment?->brand_text ?? '—' }}</div>
                                <div style="color:var(--muted); font-size:12px;">{{ $equipment?->model_text ?? '—' }} · {{ $equipment?->serial_number ?? '—' }}</div>
                            </td>
                            <td>{{ $service->internalTechnician?->name ?? '—' }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ ucfirst(str_replace('_', ' ', $service->status ?? '—')) }}</span></td>
                            <td style="color:var(--muted);">{{ $service->created_at?->format('d/m/Y') ?? '—' }}</td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a href="{{ route('gestion.servicios.area_endoscopia.resumen', $service) }}" class="tbl-link" title="Ver resumen" style="display:inline-flex; align-items:center; gap:4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Ver
                                </a>
                                <a href="{{ route('gestion.servicios.mantenimiento.reporte.raw', $service) }}" class="tbl-link" title="Hoja de reporte" style="display:inline-flex; align-items:center; gap:4px; margin-left:10px; color:var(--primary);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    Reporte
                                </a>
                                <a href="{{ route('gestion.servicios.area_endoscopia.cotizacion', $service) }}" class="tbl-link" title="Cotización" style="display:inline-flex; align-items:center; gap:4px; margin-left:10px; color:var(--accent);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    Cotización
                                </a>
                                <a href="{{ route('gestion.servicios.area_endoscopia.cotizacion.pdf', $service) }}" class="tbl-link" title="Descargar cotización PDF" style="display:inline-flex; align-items:center; gap:4px; margin-left:10px; color:var(--muted);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="erp-empty">No hay servicios externos con mantenimiento interno.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
