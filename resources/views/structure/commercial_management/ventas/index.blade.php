@extends('structure.commercial_management.erp')

@section('title', 'Ventas')
@section('page-title', 'Ventas')

@section('erp_content')
    <div class="content-actions">
        <x-ui.view-switch key="ventas" />
        @can('ventas.crear')
            <a href="{{ route('commercial.ventas.rapida.index') }}" class="erp-btn ghost" title="Escanear con el celular y cobrar al público en general">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v7h-7"/></svg>
                Venta rápida
            </a>
        @endcan
        @can('ventas.crear')
        <a href="{{ route('commercial.ventas.create') }}" class="erp-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva venta
        </a>
        @endcan
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></span><div><div class="n">{{ $total }}</div><div class="l">Ventas</div></div></div>
        <div class="erp-stat"><span class="ic green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="20 6 9 17 4 12"/></svg></span><div><div class="n">{{ $confirmadas }}</div><div class="l">Confirmadas</div></div></div>
        <div class="erp-stat"><span class="ic amber"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><div class="n">{{ $facturadas }}</div><div class="l">Facturadas</div></div></div>
        <div class="erp-stat"><span class="ic slate"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div><div class="n" style="font-size:19px;">${{ number_format($montoTotal, 2) }}</div><div class="l">Monto total</div></div></div>
    </div>

    <div class="erp-card" data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Modalidad</th><th>Total</th><th>Estado</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $v)
                        @php
                            $badge = match($v->estado) {
                                'confirmada' => 'ok', 'facturada' => 'info',
                                'cancelada' => 'danger', default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="erp-strong">
                                {{ $v->folio }}
                                @if ($v->resumenProductos())
                                    <div style="font-weight:400; font-size:12px; color:var(--muted); margin-top:2px; max-width:240px; white-space:normal;">{{ $v->resumenProductos() }}</div>
                                @endif
                            </td>
                            <td>{{ $v->customer?->nombre }} {{ $v->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $v->modalidad }}@if($v->modalidad === 'financiamiento') · {{ $v->num_meses }}m @endif</td>
                            <td class="erp-strong">${{ number_format($v->total, 2) }}</td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $v->estadoLabel() }}</span></td>
                            <td style="color:var(--muted);">{{ $v->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <x-erp.menu>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.show', $v) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Ver detalle
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.edit', $v) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.pdf', $v) }}" target="_blank">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Descargar PDF
                                    </a>
                                    <a class="erp-menu-item" href="{{ route('commercial.facturas.create', ['venta' => $v->id]) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Generar borrador de factura
                                    </a>
                                    @if ($v->estado !== 'cancelada')
                                        <div class="erp-menu-sep"></div>
                                        {{-- Cancelar conserva historial y cobros; se confirma con motivo y PIN en el detalle. --}}
                                        <a class="erp-menu-item danger" href="{{ route('commercial.ventas.show', $v) }}#cancelar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>Cancelar venta
                                        </a>
                                    @endif
                                </x-erp.menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    </span>
                                    <h3>Aún no hay ventas</h3>
                                    <p>Registra la primera y aparecerá en esta lista.</p>
                                    <a href="{{ route('commercial.ventas.create') }}" class="erp-btn">Nueva venta</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials._paginacion', ['paginator' => $ventas])
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($ventas as $v)
            @php
                $badge = match($v->estado) {
                    'confirmada' => 'ok', 'facturada' => 'info',
                    'cancelada' => 'danger', default => 'neutral',
                };
            @endphp
            <article class="data-card">
                <div class="data-card-top">
                    <div style="min-width:0;">
                        <div class="t">{{ $v->folio }}</div>
                        <div class="s">{{ $v->customer?->nombre }} {{ $v->customer?->apellido }}</div>
                        @if ($v->resumenProductos())
                            <div class="s">{{ $v->resumenProductos() }}</div>
                        @endif
                    </div>
                    <span class="right erp-badge {{ $badge }}"><span class="dot"></span>{{ $v->estadoLabel() }}</span>
                </div>

                <dl>
                    <div>
                        <dt>Total</dt>
                        <dd>${{ number_format($v->total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Modalidad</dt>
                        <dd style="text-transform:capitalize;">{{ $v->modalidad }}@if($v->modalidad === 'financiamiento') · {{ $v->num_meses }}m @endif</dd>
                    </div>
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $v->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="data-card-foot">
                    <a href="{{ route('commercial.ventas.show', $v) }}" class="tbl-link">Ver</a>
                    <a href="{{ route('commercial.ventas.edit', $v) }}" class="tbl-link">Editar</a>
                    <a href="{{ route('commercial.ventas.pdf', $v) }}" class="tbl-link" target="_blank">PDF</a>
                </div>
            </article>
        @empty
            <div class="erp-card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </span>
                    <h3>Aún no hay ventas</h3>
                    <p>Registra la primera y aparecerá aquí.</p>
                    <a href="{{ route('commercial.ventas.create') }}" class="erp-btn">Nueva venta</a>
                </div>
            </div>
        @endforelse
    </div>
    @include('partials._paginacion', ['paginator' => $ventas])
@endsection
