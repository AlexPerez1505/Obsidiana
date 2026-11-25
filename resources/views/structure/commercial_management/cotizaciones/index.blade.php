@extends('structure.commercial_management.erp')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')

@section('erp_content')
    @if (request()->query('from') === 'dashboard')
        <x-ui.page-header title="Cotizaciones" :back="route('dashboard')" />
    @endif

    <div class="content-actions">
        <x-ui.view-switch key="cotizaciones" />
        @can('cotizaciones.crear')
            <a href="{{ route('commercial.cotizaciones.create') }}" class="erp-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nueva cotización
            </a>
        @endcan
    </div>

    <div class="erp-stats cot-stats">
        <div class="erp-stat cot-stat cot-stat--blue"><span class="ic blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div class="cot-stat-copy"><div class="n">{{ $total }}</div><div class="l">Cotizaciones</div></div><span class="cot-stat-art" aria-hidden="true"><img src="{{ asset('images/cotizaciones/cotizaciones-total.png') }}" alt=""></span></div>
        <div class="erp-stat cot-stat cot-stat--amber"><span class="ic amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span><div class="cot-stat-copy"><div class="n">{{ $borradores }}</div><div class="l">Generadas</div></div><span class="cot-stat-art" aria-hidden="true"><img src="{{ asset('images/cotizaciones/cotizaciones-generadas.png') }}" alt=""></span></div>
        <div class="erp-stat cot-stat cot-stat--green"><span class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="20 6 9 17 4 12"/></svg></span><div class="cot-stat-copy"><div class="n">{{ $aceptadas }}</div><div class="l">Aceptadas</div></div><span class="cot-stat-art" aria-hidden="true"><img src="{{ asset('images/cotizaciones/cotizaciones-aceptadas.png') }}" alt=""></span></div>
        <div class="erp-stat cot-stat cot-stat--slate"><span class="ic slate"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div class="cot-stat-copy"><div class="n">${{ number_format($montoTotal, 2) }}</div><div class="l">Monto total</div></div><span class="cot-stat-art" aria-hidden="true"><img src="{{ asset('images/cotizaciones/cotizaciones-monto.png') }}" alt=""></span></div>
    </div>

    <div class="erp-card" data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table cot-table">
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Modalidad</th><th>Total</th><th>Estado</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($cotizaciones as $cot)
                        @php
                            $badge = match($cot->estado) {
                                'aceptada', 'convertida' => 'ok',
                                'enviada' => 'info',
                                'rechazada' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="erp-strong">
                                {{ $cot->folio }}
                                @if ($cot->resumenProductos())
                                    <div style="font-weight:400; font-size:12px; color:var(--muted); margin-top:2px; max-width:240px; white-space:normal;">{{ $cot->resumenProductos() }}</div>
                                @endif
                            </td>
                            <td>{{ $cot->customer?->nombre }} {{ $cot->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $cot->modalidad }}@if($cot->modalidad === 'financiamiento') · {{ $cot->num_meses }}m @endif</td>
                            <td class="erp-strong">${{ number_format($cot->total, 2) }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $cot->estadoLabel() }}</span></td>
                            <td style="color:var(--muted);">{{ $cot->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <x-erp.menu style="text-align:left;">
                                    <a class="erp-menu-item" href="{{ route('commercial.cotizaciones.show', $cot) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Ver detalle
                                    </a>
                                    @can('cotizaciones.editar')
                                    <a class="erp-menu-item" href="{{ route('commercial.cotizaciones.edit', $cot) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar
                                    </a>
                                    @endcan
                                    <a class="erp-menu-item" href="{{ route('commercial.cotizaciones.pdf', $cot) }}" target="_blank">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Descargar PDF
                                    </a>
                                    @can('ventas.crear')
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.create', ['cotizacion' => $cot->id]) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>Convertir a venta
                                    </a>
                                    @endcan
                                    @can('cotizaciones.eliminar')
                                    <div class="erp-menu-sep"></div>
                                    {{-- Sin NIP las primeras 72 horas; después lo pide. --}}
                                    <button type="button" class="erp-menu-item danger" data-eliminar-doc
                                            data-url="{{ route('commercial.cotizaciones.destroy', $cot) }}" data-folio="{{ $cot->folio }}" data-que="la cotización"
                                            data-nip="{{ \App\Support\EliminarConNip::requiere($cot) ? 1 : 0 }}" data-horas="{{ \App\Support\EliminarConNip::horasRestantes($cot) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>Eliminar
                                    </button>
                                    @endcan
                                </x-erp.menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <h3>Aún no hay cotizaciones</h3>
                                    <p>Crea la primera y aparecerá en esta lista.</p>
                                    @can('cotizaciones.crear')
                                        <a href="{{ route('commercial.cotizaciones.create') }}" class="erp-btn">Nueva cotización</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials._paginacion', ['paginator' => $cotizaciones])
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($cotizaciones as $cot)
            @php
                $badge = match($cot->estado) {
                    'aceptada', 'convertida' => 'ok',
                    'enviada' => 'info',
                    'rechazada' => 'danger',
                    default => 'neutral',
                };
            @endphp
            <article class="data-card">
                <div class="data-card-top">
                    <div style="min-width:0;">
                        <div class="t">{{ $cot->folio }}</div>
                        <div class="s">{{ $cot->customer?->nombre }} {{ $cot->customer?->apellido }}</div>
                        @if ($cot->resumenProductos())
                            <div class="s">{{ $cot->resumenProductos() }}</div>
                        @endif
                    </div>
                    <span class="right erp-badge {{ $badge }}"><span class="dot"></span>{{ $cot->estadoLabel() }}</span>
                </div>

                <dl>
                    <div>
                        <dt>Total</dt>
                        <dd>${{ number_format($cot->total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Modalidad</dt>
                        <dd style="text-transform:capitalize;">{{ $cot->modalidad }}@if($cot->modalidad === 'financiamiento') · {{ $cot->num_meses }}m @endif</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $cot->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">
                    <a href="{{ route('commercial.cotizaciones.show', $cot) }}" class="tbl-link">Ver</a>
                    @can('cotizaciones.editar')
                        <a href="{{ route('commercial.cotizaciones.edit', $cot) }}" class="tbl-link">Editar</a>
                    @endcan
                    <a href="{{ route('commercial.cotizaciones.pdf', $cot) }}" class="tbl-link" target="_blank">PDF</a>
                </div>
            </article>
        @empty
            <div class="erp-card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <h3>Aún no hay cotizaciones</h3>
                    <p>Crea la primera y aparecerá aquí.</p>
                    @can('cotizaciones.crear')
                        <a href="{{ route('commercial.cotizaciones.create') }}" class="erp-btn">Nueva cotización</a>
                    @endcan
                </div>
            </div>
        @endforelse
    </div>
    @include('partials._paginacion', ['paginator' => $cotizaciones])
    @include('structure.commercial_management._eliminar_doc')

    <style>
        .cot-stats { grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:16px; }
        .cot-stats .cot-stat { position:relative; min-height:82px; overflow:hidden; display:flex; align-items:center; gap:13px; padding:13px 15px; border:1px solid #20508a; border-top:3px solid #1683f7; border-radius:9px; background:linear-gradient(135deg,#111b2e 0%,#12345c 100%); box-shadow:0 8px 18px rgba(2,20,52,.18); transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .cot-stats .cot-stat:hover { transform:translateY(-3px); box-shadow:0 13px 24px rgba(2,20,52,.3), 0 0 12px color-mix(in srgb, var(--primary) 18%, transparent); }
        .cot-stats .cot-stat--amber { border-color:#76501e; border-top-color:#f59e0b; background:linear-gradient(135deg,#302a28 0%,#49321e 100%); }
        .cot-stats .cot-stat--green { border-color:#176a61; border-top-color:#22d67b; background:linear-gradient(135deg,#102d2f 0%,#10443f 100%); }
        .cot-stats .cot-stat--slate { border-color:#40536d; border-top-color:#8fa9c8; background:linear-gradient(135deg,#111b2e 0%,#243247 100%); }
        .cot-stats .cot-stat > * { position:relative; z-index:1; }
        .cot-stats .cot-stat .ic { width:42px; height:42px; flex:0 0 42px; border-radius:10px; }
        .cot-stats .cot-stat .ic svg { width:22px; height:22px; }
        .cot-stats .cot-stat-copy { min-width:0; padding-left:12px; border-left:1px solid rgba(151,194,235,.42); }
        .cot-stats .cot-stat .n { color:#f5f8ff; font-size:24px; line-height:1; font-weight:800; letter-spacing:0; }
        .cot-stats .cot-stat .l { margin-top:4px; color:#c2d7ef; font-size:11.5px; line-height:1.15; white-space:nowrap; text-transform:none; letter-spacing:0; }
        .cot-stats .cot-stat-art { position:absolute; right:4px; bottom:-8px; z-index:0; width:116px; height:84px; pointer-events:none; }
        .cot-stats .cot-stat-art img { display:block; width:100%; height:100%; object-fit:contain; mix-blend-mode:screen; opacity:.38; }
        .cot-stats .cot-stat--amber .cot-stat-art img { opacity:.34; }
        .cot-stats .cot-stat--green .cot-stat-art img { opacity:.34; }
        .cot-stats .cot-stat--slate .cot-stat-art { right:0; bottom:-10px; width:122px; height:90px; }
        .cot-stats .cot-stat--slate .cot-stat-art img { opacity:.32; }
        .cot-table tbody td:nth-child(1)::before,
        .cot-table tbody td:nth-child(2)::before,
        .cot-table tbody td:nth-child(3)::before,
        .cot-table tbody td:nth-child(4)::before,
        .cot-table tbody td:nth-child(5)::before,
        .cot-table tbody td:nth-child(6)::before { display:inline-block; width:17px; margin-right:6px; color:#8fb3d9; font-size:12px; line-height:1; text-align:center; vertical-align:-1px; }
        .cot-table tbody td:nth-child(1)::before { content:'▣'; color:#5aa9f7; }
        .cot-table tbody td:nth-child(2)::before { content:'♙'; }
        .cot-table tbody td:nth-child(3)::before { content:'◈'; color:#f3a43b; }
        .cot-table tbody td:nth-child(4)::before { content:'$'; color:#8bd6b0; font-weight:700; }
        .cot-table tbody td:nth-child(5)::before { content:'✓'; color:#4fd28a; font-weight:700; }
        .cot-table tbody td:nth-child(6)::before { content:'◷'; }
        .content-actions .erp-btn { transition:transform .2s ease, box-shadow .2s ease; }
        .content-actions .erp-btn:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(22,131,247,.28); }
        @media (max-width:1050px) { .cot-stats { grid-template-columns:repeat(2, minmax(0,1fr)); } }
        @media (max-width:560px) { .cot-stats { grid-template-columns:1fr; } }
        @media (prefers-reduced-motion:reduce) { .cot-stats .cot-stat { transition:none; } .cot-stats .cot-stat:hover { transform:none; } }
        :root[data-theme="light"] .cot-stats .cot-stat { border-color:#b8d2ec; border-top-color:#1683f7; background:linear-gradient(135deg,#f5faff 0%,#e5f2ff 100%); box-shadow:0 8px 18px rgba(35,86,132,.12); }
        :root[data-theme="light"] .cot-stats .cot-stat--amber { border-color:#e7c48a; border-top-color:#e89608; background:linear-gradient(135deg,#fffaf0 0%,#fff0d9 100%); }
        :root[data-theme="light"] .cot-stats .cot-stat--green { border-color:#9bd8c5; border-top-color:#18b968; background:linear-gradient(135deg,#f0fff8 0%,#dff7ee 100%); }
        :root[data-theme="light"] .cot-stats .cot-stat--slate { border-color:#bdccdc; border-top-color:#7189a5; background:linear-gradient(135deg,#f4f8fc 0%,#e4edf6 100%); }
        :root[data-theme="light"] .cot-stats .cot-stat-copy { border-left-color:rgba(56,111,160,.28); }
        :root[data-theme="light"] .cot-stats .cot-stat .n { color:#172b47; }
        :root[data-theme="light"] .cot-stats .cot-stat .l { color:#526f90; }
        :root[data-theme="light"] .cot-table tbody td:nth-child(1)::before,
        :root[data-theme="light"] .cot-table tbody td:nth-child(2)::before,
        :root[data-theme="light"] .cot-table tbody td:nth-child(3)::before,
        :root[data-theme="light"] .cot-table tbody td:nth-child(4)::before,
        :root[data-theme="light"] .cot-table tbody td:nth-child(5)::before,
        :root[data-theme="light"] .cot-table tbody td:nth-child(6)::before { color:#5d7d9f; }
    </style>
@endsection
