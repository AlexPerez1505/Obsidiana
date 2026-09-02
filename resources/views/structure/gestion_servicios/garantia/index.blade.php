@extends('structure.commercial_management.erp')

@section('title', 'Cartas de Garantía')

@section('erp_content')
    @php
        $total = $ventas->count();
        $vigentes = $ventas->filter->garantiaVigente()->count();
        $vencidas = $total - $vigentes;
    @endphp

    <div class="content-actions">
        <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" class="erp-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva orden
        </a>
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><div class="n">{{ $total }}</div><div class="l">Garantías</div></div></div>
        <div class="erp-stat"><span class="ic green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="20 6 9 17 4 12"/></svg></span><div><div class="n">{{ $vigentes }}</div><div class="l">Vigentes</div></div></div>
        <div class="erp-stat"><span class="ic amber"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span><div><div class="n">{{ $vencidas }}</div><div class="l">Vencidas</div></div></div>
    </div>

    <div class="erp-card">
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Meses</th><th>Vigencia</th><th>Estado</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                        @php
                            $vigente = $venta->garantiaVigente();
                        @endphp
                        <tr>
                            <td class="erp-strong">{{ $venta->folio }}</td>
                            <td>{{ $venta->customer?->nombre }} {{ $venta->customer?->apellido }}</td>
                            <td>{{ $venta->garantia_meses }} meses</td>
                            <td>{{ $venta->garantiaHasta()?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="erp-badge {{ $vigente ? 'ok' : 'warn' }}"><span class="dot"></span>{{ $vigente ? 'Vigente' : 'Vencida' }}</span></td>
                            <td style="text-align:right;">
                                <a href="{{ route('commercial.ventas.garantia', $venta) }}" target="_blank" class="tbl-link">Ver carta</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <span class="ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <h3>Aún no hay cartas de garantía</h3>
                                    <p>Registra ventas con garantía y aparecerán aquí.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
