@extends('structure.commercial_management.erp')

@section('title', 'Marketing y promociones')
@section('page-title', 'Marketing y promociones')

@php
    $p = $periodo !== '30d' ? ['periodo' => $periodo] : [];
    $hayDatos = $k['usados']['valor'] || $k['ventas']['valor'] || $k['enviados']['valor'] || $k['activas'];
@endphp

@section('erp_content')
<div class="mk" data-no-mayus>
    @include('structure.promociones._shell', ['tab' => 'resumen'])

    <div class="mk-kpis">
        @include('structure.promociones._kpi', ['titulo' => 'Campañas activas', 'icono' => 'megafono', 'valor' => number_format($k['activas']),
            'href' => route('promociones.campanas.index', $p + ['estado' => 'activas']), 'k' => null, 'contexto' => 'programadas o enviándose'])
        @include('structure.promociones._kpi', ['titulo' => 'Cupones usados', 'icono' => 'tag', 'valor' => number_format($k['usados']['valor']),
            'href' => route('promociones.cupones.index', $p + ['con_usos' => 1]), 'k' => $k['usados'], 'contexto' => 'en ventas del periodo'])
        @include('structure.promociones._kpi', ['titulo' => 'Ventas asociadas', 'icono' => 'ventas', 'valor' => '$'.number_format($k['ventas']['valor'], 0),
            'href' => route('promociones.campanas.index', $p + ['con_ventas' => 1]), 'k' => $k['ventas'], 'contexto' => $k['ventas_n'].' venta(s) atribuidas'])
        @include('structure.promociones._kpi', ['titulo' => 'Total descontado', 'icono' => 'porcentaje', 'valor' => '$'.number_format($k['descontado']['valor'], 0),
            'href' => route('promociones.cupones.index', $p + ['con_usos' => 1, 'orden' => 'usos']), 'k' => $k['descontado'], 'contexto' => 'con cupones'])
    </div>

    @unless ($hayDatos)
        <div class="mk-card" style="margin-bottom:16px;">
            <div class="mk-empty">
                <div class="ic">@include('structure.promociones._ic', ['n' => 'megafono'])</div>
                <h3>Aún no hay movimientos en este periodo</h3>
                <p>Crea un cupón y una campaña; aquí verás envíos, cupones usados y ventas.</p>
                <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                    @can('cupones.gestionar')<a class="mk-btn" href="{{ route('promociones.cupones.index', ['nuevo' => 1]) }}">@include('structure.promociones._ic', ['n' => 'tag'])Crear cupón</a>@endcan
                    @can('promociones.gestionar')<a class="mk-btn sec" href="{{ route('promociones.campanas.create') }}">@include('structure.promociones._ic', ['n' => 'megafono'])Crear campaña</a>@endcan
                </div>
            </div>
        </div>
    @endunless

    <div class="mk-grid2">
        <div style="display:flex; flex-direction:column; gap:16px; min-width:0;">
            <section class="mk-card">
                <div class="mk-card-h"><h3>@include('structure.promociones._ic', ['n' => 'reloj']) Próximos envíos</h3>
                    <a class="mk-btn ghost sm" href="{{ route('promociones.campanas.index', $p + ['vista' => 'calendario']) }}">@include('structure.promociones._ic', ['n' => 'calendario'])Calendario</a></div>
                <div class="mk-card-b">
                    @forelse ($proximas as $c)
                        <a href="{{ route('promociones.campanas.index', $p + ['ver' => $c->id]) }}" style="display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); color:inherit; text-decoration:none;">
                            <span style="width:42px; text-align:center; flex:0 0 auto;"><b style="display:block; font-size:16px; line-height:1;">{{ $c->programada_para->format('d') }}</b><small class="mk-muted" style="font-size:11px; text-transform:uppercase;">{{ $c->programada_para->locale('es')->isoFormat('MMM') }}</small></span>
                            <span style="flex:1; min-width:0;"><b style="font-size:13.5px;">{{ $c->nombre }}</b><br><small class="mk-muted">{{ $c->programada_para->format('H:i') }} h · {{ \App\Services\Promociones\Audiencia::describir($c->filtros ?? []) }}</small></span>
                            <span class="mk-badge info">Programada</span>
                        </a>
                    @empty
                        <p class="mk-hint" style="padding:6px 0;">Nada programado.</p>
                    @endforelse
                    @foreach ($enCurso as $c)
                        <a href="{{ route('promociones.campanas.index', $p + ['ver' => $c->id]) }}" style="display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); color:inherit; text-decoration:none;">
                            <span style="flex:1; min-width:0;"><b style="font-size:13.5px;">{{ $c->nombre }}</b><br><small class="mk-muted">Inició {{ $c->iniciada_en?->format('d/m H:i') }}</small></span>
                            <span class="mk-badge {{ $c->tonoEstado() }}">{{ $c->estadoLabel() }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="mk-card">
                <div class="mk-card-h"><h3>@include('structure.promociones._ic', ['n' => 'tag']) Cupones más usados</h3></div>
                <div class="mk-card-b">
                    @forelse ($top as $t)
                        <a href="{{ route('promociones.cupones.index', $p + ['ver' => $t['id']]) }}" style="display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); color:inherit; text-decoration:none;">
                            <span class="mk-code" style="flex:0 0 auto;">{{ $t['codigo'] }}</span>
                            <span class="mk-muted" style="flex:1; min-width:0; font-size:12.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $t['nombre'] }}</span>
                            <span class="mk-num" style="font-size:13px;"><b>{{ $t['usos'] }}</b> usos · -${{ number_format($t['descontado'], 0) }}</span>
                        </a>
                    @empty
                        <p class="mk-hint" style="padding:6px 0;">Ningún cupón usado en el periodo.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div style="display:flex; flex-direction:column; gap:16px; min-width:0;">
            <section class="mk-card">
                <div class="mk-card-h"><h3>@include('structure.promociones._ic', ['n' => 'enviar']) Mensajes del periodo</h3></div>
                <div class="mk-card-b">
                    <ul class="mk-rows">
                        <li><span class="k">Enviados por WhatsApp</span><span class="v mk-num">{{ number_format($k['enviados']['valor']) }}</span></li>
                        <li><span class="k">Integración</span><span class="v">@if($whatsapp->listoParaEnviar())<span class="mk-badge ok">Conectada</span>@else<a href="{{ route('promociones.configuracion') }}" class="mk-badge warn" style="text-decoration:none;">Modo de pruebas</a>@endif</span></li>
                        <li><span class="k">Periodo</span><span class="v">{{ $k['desde']->format('d/m') }} – {{ $k['hasta']->format('d/m/Y') }}</span></li>
                    </ul>
                </div>
            </section>
            <section class="mk-card">
                <div class="mk-card-h"><h3>@include('structure.promociones._ic', ['n' => 'actividad']) Actividad reciente</h3></div>
                <div class="mk-card-b">
                    @if ($actividad->isEmpty())
                        <p class="mk-hint">Sin actividad.</p>
                    @else
                        <ul class="mk-tl">
                            @foreach ($actividad as $e)
                                <li>{{ $e->detalle }}@if($e->campana)<span class="mk-muted"> · {{ $e->campana->nombre }}</span>@endif
                                    <small>{{ $e->created_at->diffForHumans() }} · {{ $e->usuario?->name ?? 'Sistema / cliente' }}</small></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
