@extends('structure.commercial_management.erp')

@section('title', 'Mantenimiento')

@section('erp_content')
    @php
        $total = $services->count();
        $internos = $services->where('service_type', 'interno')->count();
        $externos = $services->where('service_type', 'externo')->count();
        $entregados = $services->where('status', 'entregado')->count();
    @endphp

    <div class="content-actions">
        <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" class="erp-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva orden
        </a>
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><div class="n">{{ $total }}</div><div class="l">Mantenimientos</div></div></div>
        <div class="erp-stat"><span class="ic green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="20 6 9 17 4 12"/></svg></span><div><div class="n">{{ $entregados }}</div><div class="l">Entregados</div></div></div>
        <div class="erp-stat"><span class="ic amber"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></span><div><div class="n">{{ $internos }}</div><div class="l">Internos</div></div></div>
        <div class="erp-stat"><span class="ic slate"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12 20.73 6.96"/></svg></span><div><div class="n">{{ $externos }}</div><div class="l">Externos</div></div></div>
    </div>

    <div class="erp-card">
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>OS</th><th>Cliente</th><th>Tipo</th><th>Estatus</th><th>Paso actual</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $badge = match($service->status) {
                                'entregado' => 'ok',
                                'en_progreso' => 'info',
                                'cancelado' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="erp-strong">{{ $service->service_number }}</td>
                            <td>{{ $service->customer?->nombre }} {{ $service->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $service->service_type }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $service->status }}</span></td>
                            <td>{{ $service->currentStep?->name ?? '—' }}</td>
                            <td style="color:var(--muted);">{{ $service->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <a href="{{ route('gestion.servicios.historial.show', $service) }}" class="tbl-link">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <h3>Aún no hay mantenimientos</h3>
                                    <p>Crea la primera orden de servicio y aparecerá aquí.</p>
                                    <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" class="erp-btn">Nueva orden</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
