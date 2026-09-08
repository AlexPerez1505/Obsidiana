@extends('structure.commercial_management.erp')

@section('title', 'Historial de Servicios')

@section('erp_content')
    @php
        $total = $services->count();
        $registrados = $services->where('status', 'registrado')->count();
        $enProgreso = $services->where('status', 'en_progreso')->count();
        $entregados = $services->where('status', 'entregado')->count();
    @endphp

    <div class="content-actions">
        <x-ui.view-switch key="servicios" />
        <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" class="erp-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva orden
        </a>
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><div class="n">{{ $total }}</div><div class="l">Servicios</div></div></div>
        <div class="erp-stat"><span class="ic amber"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span><div><div class="n">{{ $registrados }}</div><div class="l">Registrados</div></div></div>
        <div class="erp-stat"><span class="ic green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="20 6 9 17 4 12"/></svg></span><div><div class="n">{{ $entregados }}</div><div class="l">Entregados</div></div></div>
        <div class="erp-stat"><span class="ic slate"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div><div class="n">{{ $enProgreso }}</div><div class="l">En progreso</div></div></div>
    </div>

    <div class="erp-card" style="margin-top:24px;">
        <a href="{{ route('gestion.servicios.historial.aprobaciones.index') }}" class="erp-btn" style="width:100%; justify-content:space-between; padding:16px 18px; min-height:52px;">
            <span style="display:flex; align-items:center; gap:12px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Aprobaciones
            </span>
            <span class="erp-count">{{ $registrados }} {{ $registrados === 1 ? 'pendiente' : 'pendientes' }}</span>
        </a>
    </div>

    <div class="erp-card" data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>OS</th><th>Cliente</th><th>Tipo</th><th>Estatus</th><th>Paso actual</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($services->where('status', '!=', 'registrado') as $service)
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <h3>Aún no hay servicios</h3>
                                    <p>Crea la primera y aparecerá en esta lista.</p>
                                    <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" class="erp-btn">Nueva orden</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($services->where('status', '!=', 'registrado') as $service)
            @php
                $badge = match($service->status) {
                    'entregado' => 'ok',
                    'en_progreso' => 'info',
                    'cancelado' => 'danger',
                    default => 'neutral',
                };
            @endphp
            <article class="data-card">
                <div class="data-card-top">
                    <div style="min-width:0;">
                        <div class="t">{{ $service->service_number }}</div>
                        <div class="s">{{ $service->customer?->nombre }} {{ $service->customer?->apellido }}</div>
                    </div>
                    <span class="right erp-badge {{ $badge }}"><span class="dot"></span>{{ $service->status }}</span>
                </div>

                <dl>
                    <div>
                        <dt>Tipo</dt>
                        <dd style="text-transform:capitalize;">{{ $service->service_type }}</dd>
                    </div>
                    <div>
                        <dt>Paso actual</dt>
                        <dd>{{ $service->currentStep?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $service->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">
                </div>
            </article>
        @empty
            <div class="erp-card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <h3>Aún no hay servicios</h3>
                    <p>Crea la primera y aparecerá aquí.</p>
                    <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" class="erp-btn">Nueva orden</a>
                </div>
            </div>
        @endforelse
    </div>
@endsection
