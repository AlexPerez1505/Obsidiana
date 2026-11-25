@extends('structure.commercial_management.erp')

@section('title', 'Ventas')
@section('page-title', 'Ventas')

@push('head')
<style>
    .ventas-dashboard { --vd-blue:#2697ff; --vd-green:#42d4a3; --vd-amber:#ffbd4a; --vd-purple:#9d8cff; }
    .ventas-dashboard .content-actions { justify-content:flex-end; margin-bottom:18px; }
    .ventas-dashboard .erp-btn { border-radius:10px; font-weight:700; }
    .ventas-dashboard .erp-btn.ghost { background:var(--surface); }
    .vd-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .vd-stat { position:relative; overflow:hidden; min-height:86px; padding:16px 18px; border:1px solid var(--border);
               border-radius:15px; background:linear-gradient(135deg,var(--surface),var(--surface-2)); display:flex;
               align-items:center; gap:13px; transition:transform .2s ease, box-shadow .2s ease; }
    .vd-stat:hover { transform:translateY(-3px); box-shadow:0 12px 24px rgba(0,0,0,.12); }
    .vd-stat::after { content:""; position:absolute; width:145px; height:70px; right:-12px; bottom:-22px; border-top:2px solid currentColor;
                      border-radius:50%; opacity:.65; transform:rotate(-12deg); box-shadow:25px -10px 0 -1px transparent, 55px 2px 0 -1px currentColor; }
    .vd-stat.blue { color:var(--vd-blue); } .vd-stat.green { color:var(--vd-green); }
    .vd-stat.amber { color:var(--vd-amber); } .vd-stat.purple { color:var(--vd-purple); }
    .vd-stat.blue .ic { background:var(--vd-blue); } .vd-stat.green .ic { background:var(--vd-green); }
    .vd-stat.amber .ic { background:var(--vd-amber); } .vd-stat.purple .ic { background:var(--vd-purple); }
    .vd-stat .ic { width:42px; height:42px; flex:none; display:grid; place-items:center; border-radius:12px; background:currentColor; color:#fff; }
    .vd-stat .ic svg { width:22px; height:22px; } .vd-stat-copy { position:relative; z-index:1; color:var(--text); }
    .vd-stat .n { font-size:25px; font-weight:800; line-height:1; } .vd-stat .l { margin-top:5px; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.05em; }
    .vd-middle { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(230px,.9fr) 230px; gap:14px; margin-bottom:16px; }
    .vd-panel { border:1px solid var(--border); border-radius:15px; background:linear-gradient(145deg,var(--surface),var(--surface-2)); padding:14px; }
    .vd-panel-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; } .vd-panel-head h3 { margin:0; font-size:15px; }
    .vd-panel-head p { margin:2px 0 0; color:var(--muted); font-size:11px; }
    .vd-panel-icon { width:36px; height:36px; display:grid; place-items:center; border-radius:11px; background:var(--primary-soft); color:var(--primary); }
    .vd-panel-icon svg { width:20px; height:20px; }
    .vd-chart { height:116px; width:100%; display:block; } .vd-chart-grid { stroke:var(--border); stroke-dasharray:3 4; }
    .vd-chart-line { fill:none; stroke:var(--primary); stroke-width:3; stroke-linecap:round; stroke-linejoin:round; }
    .vd-chart-area { fill:var(--primary); opacity:.14; } .vd-chart-dot { fill:var(--surface); stroke:var(--primary); stroke-width:2; }
    .vd-chart-label { fill:var(--muted); font-size:10px; }
    .vd-chart-wrap { position:relative; }
    .vd-chart-tooltip { position:absolute; z-index:4; min-width:118px; padding:8px 10px; border:1px solid #3b5f88; border-radius:8px; background:#071426; color:#f8fbff; box-shadow:0 8px 20px rgba(0,0,0,.32); font-size:11px; line-height:1.35; pointer-events:none; opacity:0; transform:translate(-50%,-110%); transition:opacity .12s ease; }
    .vd-chart-tooltip strong { display:block; color:#fff; }
    .vd-chart-tooltip span { display:block; color:#b9cbe1; }
    [data-theme="light"] .vd-chart-tooltip { border-color:#b8cde2; background:#fff; color:#172b47; box-shadow:0 8px 20px rgba(30,64,100,.18); }
    [data-theme="light"] .vd-chart-tooltip strong { color:#172b47; }
    [data-theme="light"] .vd-chart-tooltip span { color:#607a98; }
    .vd-periods { display:flex; gap:5px; margin-left:auto; } .vd-periods span { padding:5px 10px; border-radius:99px; color:var(--muted); font-size:11px; }
    .vd-periods .active { color:#fff; background:var(--primary); }
    .vd-state { display:flex; align-items:center; gap:15px; min-height:116px; } .vd-donut { width:92px; height:92px; border-radius:50%; display:grid; place-items:center;
        background:conic-gradient(var(--vd-blue) 0 100%, var(--vd-green) 100% 100%, var(--vd-amber) 100% 100%); position:relative; flex:none; }
    .vd-donut::after { content:""; width:58px; height:58px; border-radius:50%; background:var(--surface); position:absolute; }
    .vd-donut strong,.vd-donut small { position:relative; z-index:1; color:var(--text); text-align:center; } .vd-donut strong { font-size:20px; line-height:1; }
    .vd-donut small { font-size:10px; display:block; margin-top:3px; } .vd-donut-label { display:flex; flex-direction:column; gap:8px; font-size:11px; color:var(--muted); }
    .vd-donut-label span { display:flex; align-items:center; gap:7px; white-space:nowrap; } .vd-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
    .vd-actions { display:flex; flex-direction:column; gap:9px; } .vd-action { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:12px; border-radius:11px;
        background:var(--surface-2); border:1px solid var(--border); color:var(--text); text-decoration:none; font-size:12px; font-weight:700; transition:transform .18s ease, border-color .18s ease; }
    .vd-action:hover { transform:translateX(3px); border-color:var(--primary); } .vd-action svg { width:18px; height:18px; color:var(--primary); }
    .vd-list-card { overflow:hidden; } .vd-list-head { display:flex; align-items:center; gap:12px; padding:14px 16px 10px; }
    .vd-list-title { display:flex; align-items:center; gap:10px; min-width:0; } .vd-list-title h3 { margin:0; font-size:15px; } .vd-list-title p { margin:2px 0 0; color:var(--muted); font-size:11px; }
    .vd-list-title .vd-panel-icon { width:34px; height:34px; } .vd-table-tools { margin-left:auto; display:flex; align-items:center; gap:8px; }
    .vd-table-search { position:relative; } .vd-table-search input { width:245px; padding:8px 10px 8px 31px; border:1px solid var(--border); border-radius:99px; background:var(--surface-2); color:var(--text); font:inherit; font-size:12px; }
    .vd-table-search svg { position:absolute; width:15px; left:10px; top:50%; transform:translateY(-50%); color:var(--muted); }
    .vd-list-card .erp-table-wrap { padding:0 8px 8px; } .vd-list-card .erp-table { border:1px solid var(--border); border-radius:12px; overflow:hidden; }
    .vd-list-card .erp-table th { padding:10px 12px; font-size:11px; } .vd-list-card .erp-table td { padding:9px 12px; font-size:12px; }
    .vd-list-card .erp-table tbody tr:hover td { background:var(--surface-2); }
    .vd-cell { display:flex; align-items:center; gap:8px; min-width:0; }
    .vd-cell-icon { display:inline-grid; place-items:center; flex:none; width:27px; height:27px; border-radius:8px; color:var(--primary); background:var(--primary-soft); }
    .vd-cell-icon svg { width:15px; height:15px; }
    .vd-cell-content { min-width:0; }
    @media (max-width:1050px) { .vd-middle { grid-template-columns:1fr 1fr; } .vd-actions { grid-column:1 / -1; flex-direction:row; } .vd-action { flex:1; } }
    @media (max-width:760px) { .vd-stats { grid-template-columns:repeat(2,1fr); } .vd-middle { grid-template-columns:1fr; } .vd-actions { grid-column:auto; flex-direction:column; } .vd-table-tools { width:100%; margin-left:0; } .vd-table-search,.vd-table-search input { width:100%; } .vd-list-head { flex-wrap:wrap; } }
    @media (prefers-reduced-motion:reduce) { .vd-stat,.vd-action { transition:none; } }
</style>
@endpush

@section('erp_content')
<div class="ventas-dashboard">
    <div class="content-actions">
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

    @php
        $registradasPct = $total ? round(($registradas / $total) * 100) : 0;
        $confirmadasPct = $total ? round(($confirmadas / $total) * 100) : 0;
        $facturadasPct = $total ? round(($facturadas / $total) * 100) : 0;
        $confirmadasInicio = $registradasPct;
        $facturadasInicio = $registradasPct + $confirmadasPct;
    @endphp
    <div class="vd-stats">
        <div class="vd-stat blue"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></span><div class="vd-stat-copy"><div class="n">{{ $total }}</div><div class="l">Ventas</div></div></div>
        <div class="vd-stat green"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="20 6 9 17 4 12"/></svg></span><div class="vd-stat-copy"><div class="n">{{ $confirmadas }}</div><div class="l">Confirmadas</div></div></div>
        <div class="vd-stat amber"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div class="vd-stat-copy"><div class="n">{{ $facturadas }}</div><div class="l">Facturadas</div></div></div>
        <div class="vd-stat purple"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div class="vd-stat-copy"><div class="n" style="font-size:20px;">${{ number_format($montoTotal, 2) }}</div><div class="l">Monto total</div></div></div>
    </div>

    @php
        $tendenciaInicial = $tendencias['semana'];
        $maxTendencia = max(1, (int) $tendenciaInicial->max('total'));
        $puntosTendencia = $tendenciaInicial->values()->map(function ($dia, $index) use ($maxTendencia, $tendenciaInicial) {
            return [
                'x' => round(20 + ($index * (580 / max(1, $tendenciaInicial->count() - 1))), 2),
                'y' => round(125 - (($dia['total'] / $maxTendencia) * 92), 2),
                'label' => $dia['label'],
                'detalle' => $dia['detalle'],
                'total' => $dia['total'],
            ];
        });
        $lineaTendencia = $puntosTendencia->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').' '.$p['x'].' '.$p['y'])->implode(' ');
        $areaTendencia = $lineaTendencia.' L 600 125 L 20 125 Z';
    @endphp
    <div class="vd-middle">
        <section class="vd-panel" data-ventas-trend><div class="vd-panel-head"><span class="vd-panel-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 17 6-6 4 4 8-9"/><path d="M14 6h7v7"/></svg></span><div><h3>Tendencia de ventas</h3><p data-trend-caption>Ventas registradas por día · semana actual</p></div><div class="vd-periods"><span class="active" data-period="semana">Semana</span><span data-period="mes">Mes</span><span data-period="anio">Año</span></div></div><div class="vd-chart-wrap"><div class="vd-chart-tooltip" data-trend-tooltip><strong></strong><span></span></div><svg class="vd-chart" data-trend-chart viewBox="0 0 620 150" preserveAspectRatio="none" aria-label="Tendencia real de ventas"><path class="vd-chart-grid" d="M20 20H600M20 55H600M20 90H600M20 125H600"/><path class="vd-chart-area" data-trend-area d="{{ $areaTendencia }}"/><path class="vd-chart-line" data-trend-line d="{{ $lineaTendencia }}"/><g data-trend-points>@foreach($puntosTendencia as $p)<circle class="vd-chart-dot" data-label="{{ $p['label'] }}" data-detail="{{ $p['detalle'] }}" data-total="{{ $p['total'] }}" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4"/><text class="vd-chart-label" x="{{ $p['x'] }}" y="145" text-anchor="middle">{{ $p['label'] }}</text>@endforeach</g></svg></div></section>
        <section class="vd-panel"><div class="vd-panel-head"><span class="vd-panel-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><h3>Estado de ventas</h3></div></div><div class="vd-state"><div class="vd-donut" style="background:conic-gradient(var(--vd-blue) 0 {{ $registradasPct }}%, var(--vd-green) {{ $confirmadasInicio }}% {{ $facturadasInicio }}%, var(--vd-amber) {{ $facturadasInicio }}% 100%);"><div><strong>{{ $total }}</strong><small>Ventas</small></div></div><div class="vd-donut-label"><span><i class="vd-dot" style="background:var(--vd-blue)"></i>Registrada {{ $registradas }} ({{ $registradasPct }}%)</span><span><i class="vd-dot" style="background:var(--vd-green)"></i>Confirmada {{ $confirmadas }} ({{ $confirmadasPct }}%)</span><span><i class="vd-dot" style="background:var(--vd-amber)"></i>Facturada {{ $facturadas }} ({{ $facturadasPct }}%)</span></div></div></section>
        <section class="vd-panel"><div class="vd-panel-head"><span class="vd-panel-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m13 2-9 12h7l-1 8 9-12h-7z"/></svg></span><div><h3>Acciones rápidas</h3></div></div><div class="vd-actions"><a class="vd-action" href="{{ route('commercial.ventas.rapida.index') }}"><span>Venta rápida</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></a><a class="vd-action" href="{{ route('commercial.ventas.create') }}"><span>Nueva venta</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></a></div></section>
    </div>

    <div class="erp-card vd-list-card">
        <div class="vd-list-head"><div class="vd-list-title"><span class="vd-panel-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span><div><h3>Listado de ventas</h3><p>Detalle de las ventas registradas</p></div></div><div class="vd-table-tools"><label class="vd-table-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input type="search" placeholder="Buscar venta..." data-ventas-search aria-label="Buscar venta"></label><x-ui.view-switch key="ventas" /></div></div>
        <div data-view-list>
        <div class="erp-table-wrap">
            <table class="erp-table" data-ventas-table>
                <thead>
                    <tr><th>Folio</th><th>Cliente</th><th>Asesor</th><th>Modalidad</th><th>Total</th><th>Estado</th><th>Fecha</th><th style="text-align:right;">Acciones</th></tr>
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
                            <td class="erp-strong"><div class="vd-cell"><span class="vd-cell-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></span><div class="vd-cell-content">{{ $v->folio }}
                                @if ($v->resumenProductos())
                                    <div style="font-weight:400; font-size:12px; color:var(--muted); margin-top:2px; max-width:240px; white-space:normal;">{{ $v->resumenProductos() }}</div>
                                @endif
                                </div></div></td>
                            <td><div class="vd-cell"><span class="vd-cell-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20c.8-3.3 3.1-5 7-5s6.2 1.7 7 5"/></svg></span><span class="vd-cell-content">{{ $v->customer?->nombre }} {{ $v->customer?->apellido }}</span></div></td>
                            {{-- Quién hizo la venta. --}}
                            <td>{{ $v->seller?->name ?: '—' }}</td>
                            <td style="text-transform:capitalize;">{{ $v->modalidad }}@if($v->modalidad === 'financiamiento') · {{ $v->num_meses }}m @endif</td>
                            <td class="erp-strong"><div class="vd-cell"><span class="vd-cell-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><span class="vd-cell-content">${{ number_format($v->total, 2) }}</span></div></td>
                            <td><span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $v->estadoLabel() }}</span></td>
                            <td style="color:var(--muted);"><div class="vd-cell"><span class="vd-cell-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span><span class="vd-cell-content">{{ $v->created_at?->format('d/m/Y') }}</span></div></td>
                            <td style="text-align:right;">
                                <x-erp.menu>
                                    <a class="erp-menu-item" href="{{ route('commercial.ventas.show', $v) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Ver detalle
                                    </a>
                                    @if ($v->estado !== 'cancelada')
                                        <a class="erp-menu-item" href="{{ route('commercial.ventas.edit', $v) }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar
                                        </a>
                                    @endif
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
                                    @can('ventas.eliminar')
                                        <div class="erp-menu-sep"></div>
                                        {{-- Sin NIP las primeras 72 horas; después lo pide. Si ya tiene
                                             cobros o el equipo salió, el servidor no la deja borrar. --}}
                                        <button type="button" class="erp-menu-item danger" data-eliminar-doc
                                                data-url="{{ route('commercial.ventas.destroy', $v) }}" data-folio="{{ $v->folio }}" data-que="la venta"
                                                data-nip="{{ \App\Support\EliminarConNip::requiere($v) ? 1 : 0 }}" data-horas="{{ \App\Support\EliminarConNip::horasRestantes($v) }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>Eliminar venta
                                        </button>
                                    @endcan
                                    @can('ventas.eliminar_definitivo')
                                        <form method="POST" action="{{ route('commercial.ventas.destroyDefinitivo', $v) }}"
                                              data-definitivo="{{ $v->folio }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="erp-menu-item danger" style="width:100%;text-align:left;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>Eliminar con historial
                                            </button>
                                        </form>
                                    @endcan
                                </x-erp.menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
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
                        <dt>Asesor</dt>
                        <dd>{{ $v->seller?->name ?: '—' }}</dd>
                    </div>
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
                    @if ($v->estado !== 'cancelada')
                        <a href="{{ route('commercial.ventas.edit', $v) }}" class="tbl-link">Editar</a>
                    @endif
                    <a href="{{ route('commercial.ventas.pdf', $v) }}" class="tbl-link" target="_blank">PDF</a>
                    @can('ventas.eliminar_definitivo')
                        <form method="POST" action="{{ route('commercial.ventas.destroyDefinitivo', $v) }}"
                              data-definitivo="{{ $v->folio }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="tbl-link" style="color:var(--danger,#f66);background:none;border:none;cursor:pointer;">Eliminar</button>
                        </form>
                    @endcan
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
    @include('structure.commercial_management._eliminar_doc')

    </div>

@push('scripts')
<script>
document.addEventListener('input', function (e) {
    if (!e.target.matches('[data-ventas-search]')) return;
    var q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('[data-ventas-table] tbody tr').forEach(function (row) {
        row.hidden = q !== '' && !row.textContent.toLowerCase().includes(q);
    });
});

// "Eliminar con historial" pide dos confirmaciones seguidas: la acción
// borra también cobros, pagos, facturas y garantías de la venta.
(function () {
    var panel = document.querySelector('[data-ventas-trend]');
    if (!panel) return;
    var datos = @json($tendencias);
    var svg = panel.querySelector('[data-trend-chart]');
    var area = panel.querySelector('[data-trend-area]');
    var line = panel.querySelector('[data-trend-line]');
    var puntos = panel.querySelector('[data-trend-points]');
    var tooltip = panel.querySelector('[data-trend-tooltip]');
    var caption = panel.querySelector('[data-trend-caption]');
    var nombres = { semana: 'Ventas registradas por día · semana actual', mes: 'Ventas registradas por semana · mes actual', anio: 'Ventas registradas por mes · año actual' };

    function pintar(periodo) {
        var filas = datos[periodo] || [];
        var maximo = Math.max(1, ...filas.map(function (fila) { return Number(fila.total) || 0; }));
        var divisor = Math.max(1, filas.length - 1);
        var coordenadas = filas.map(function (fila, i) {
            return { fila: fila, x: 20 + (i * (580 / divisor)), y: 125 - (((Number(fila.total) || 0) / maximo) * 92) };
        });
        var camino = coordenadas.map(function (p, i) { return (i ? 'L' : 'M') + ' ' + p.x.toFixed(2) + ' ' + p.y.toFixed(2); }).join(' ');
        area.setAttribute('d', camino + ' L 600 125 L 20 125 Z');
        line.setAttribute('d', camino);
        puntos.replaceChildren();
        coordenadas.forEach(function (p) {
            var punto = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            punto.setAttribute('class', 'vd-chart-dot'); punto.setAttribute('cx', p.x); punto.setAttribute('cy', p.y); punto.setAttribute('r', '4');
            punto.dataset.label = p.fila.label; punto.dataset.detail = p.fila.detalle; punto.dataset.total = p.fila.total;
            var etiqueta = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            etiqueta.setAttribute('class', 'vd-chart-label'); etiqueta.setAttribute('x', p.x); etiqueta.setAttribute('y', '145'); etiqueta.setAttribute('text-anchor', 'middle'); etiqueta.textContent = p.fila.label;
            puntos.appendChild(punto); puntos.appendChild(etiqueta);
        });
        caption.textContent = nombres[periodo];
        panel.querySelectorAll('[data-period]').forEach(function (boton) { boton.classList.toggle('active', boton.dataset.period === periodo); });
    }

    panel.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-period]');
        if (boton) pintar(boton.dataset.period);
    });
    panel.addEventListener('mousemove', function (e) {
        var punto = e.target.closest('[data-label]');
        if (!punto) { tooltip.style.opacity = '0'; return; }
        var rect = svg.getBoundingClientRect();
        var caja = svg.parentElement.getBoundingClientRect();
        var x = ((Number(punto.getAttribute('cx')) / 620) * rect.width) + (rect.left - caja.left);
        var y = ((Number(punto.getAttribute('cy')) / 150) * rect.height) + (rect.top - caja.top);
        tooltip.querySelector('strong').textContent = punto.dataset.label;
        tooltip.querySelector('span').textContent = punto.dataset.detail + ' · ' + punto.dataset.total + (punto.dataset.total === '1' ? ' venta' : ' ventas');
        tooltip.style.left = x + 'px'; tooltip.style.top = y + 'px'; tooltip.style.opacity = '1';
    });
    pintar('semana');
})();

document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-definitivo')) return;
    if (form.dataset.cmDone === '1') { form.dataset.cmDone = ''; return; }
    e.preventDefault();
    var folio = form.getAttribute('data-definitivo');
    window.confirmModal({
        title: 'Eliminar con historial',
        message: '¿Eliminar la venta ' + folio + ' junto con TODOS sus cobros, pagos, facturas y garantías? Esta acción no se puede deshacer.',
        confirmText: 'Continuar',
        danger: true
    }).then(function (ok) {
        if (!ok) return;
        // El segundo aviso solo puede abrirse cuando el primero ya se
        // ocultó por completo (~180 ms): si se abre antes, el hidden del
        // primero lo vuelve invisible.
        var espera = setInterval(function () {
            var ov = document.getElementById('cmOverlay');
            if (ov && !ov.hidden) return;
            clearInterval(espera);
            window.confirmModal({
                title: 'Confirmación final',
                message: 'Se borrará también su historial comercial completo. ¿Seguro que quieres eliminar definitivamente la venta ' + folio + '?',
                confirmText: 'Sí, eliminar todo',
                danger: true
            }).then(function (seguro) {
                if (!seguro) return;
                form.dataset.cmDone = '1';
                if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
            });
        }, 150);
    });
}, true);
</script>
@endpush
@endsection
