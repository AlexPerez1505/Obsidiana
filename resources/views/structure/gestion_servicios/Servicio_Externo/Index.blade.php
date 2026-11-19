@extends('structure.commercial_management.erp')

@section('title', 'Servicio externo')

@section('erp_content')
    @php
        $total = $services->count();
        $groups = [
            [
                'title' => 'Externo interno',
                'badge' => 'info',
                'empty' => 'No hay servicios externos internos.',
                'items' => $services->where('service_type', 'interno'),
            ],
            [
                'title' => 'Externo externo',
                'badge' => 'warn',
                'empty' => 'No hay servicios externos externos.',
                'items' => $services->where('service_type', 'externo'),
            ],
        ];
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Servicio externo</h1>
            <span class="erp-count">{{ $total }} {{ $total === 1 ? 'servicio' : 'servicios' }}</span>
        </div>
        <a href="{{ route('gestion.servicios.historial') }}" class="erp-btn ghost">Volver al historial</a>
    </div>

    @foreach ($groups as $group)
        <div class="erp-card" @if (! $loop->first) style="margin-top:18px;" @endif>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; border-bottom:1px solid var(--border);">
                <h2 style="margin:0; font-size:15px; font-weight:700;">{{ $group['title'] }}</h2>
                <span class="erp-badge {{ $group['badge'] }}"><span class="dot"></span>{{ $group['items']->count() }} {{ $group['items']->count() === 1 ? 'servicio' : 'servicios' }}</span>
            </div>
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Equipo</th>
                            <th>Marca / Modelo / Serie</th>
                            <th>Técnico</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($group['items'] as $service)
                            @php
                                $equipment = $service->serviceEquipment;
                                $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
                                $technician = $service->service_type === 'externo'
                                    ? ($service->externalTechnician?->name ?? $service->internalTechnician?->name)
                                    : $service->internalTechnician?->name;
                                $badge = match($service->status) {
                                    'entregado', 'completado' => 'ok',
                                    'en_progreso' => 'info',
                                    'aprobado' => 'warn',
                                    default => 'neutral',
                                };
                                $typeBadge = $service->service_type === 'externo' ? 'warn' : 'info';
                            @endphp
                            <tr>
                                <td class="erp-strong">{{ $service->service_number ?? ('OS-' . $service->id) }}</td>
                                <td>{{ $customerName }}</td>
                                <td><span class="erp-badge {{ $typeBadge }}" style="text-transform:capitalize;"><span class="dot"></span>{{ $service->service_type ? 'externo - ' . $service->service_type : '—' }}</span></td>
                                <td>{{ $equipment?->type_text ?? '—' }}</td>
                                <td>
                                    <div>{{ $equipment?->brand_text ?? '—' }}</div>
                                    <div style="color:var(--muted); font-size:12px;">{{ $equipment?->model_text ?? '—' }} · {{ $equipment?->serial_number ?? '—' }}</div>
                                </td>
                                <td>
                                    <div>{{ $technician ?? '—' }}</div>
                                    @if ($service->service_type === 'externo' && $service->externalTechnician?->company)
                                        <div style="color:var(--muted); font-size:12px;">{{ $service->externalTechnician->company }}</div>
                                    @endif
                                </td>
                                <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ ucfirst(str_replace('_', ' ', $service->status ?? '—')) }}</span></td>
                                <td style="color:var(--muted);">{{ $service->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <a href="{{ route('gestion.servicios.servicio_externo.ver', $service) }}" class="tbl-link" title="Ver servicio" style="display:inline-flex; align-items:center; gap:4px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="erp-empty">{{ $group['empty'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
