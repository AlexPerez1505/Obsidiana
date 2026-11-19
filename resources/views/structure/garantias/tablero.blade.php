@extends('layouts.dashboard')

@section('title', 'Garantías')
@section('page-title', 'Centro de garantías')
@section('page-sub', 'Cobertura de los equipos vendidos y atención de reclamaciones')

@section('content')
    @include('structure.garantias._estilos')

    <form method="GET" action="{{ route('garantias.buscar') }}" class="gt-buscar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Número de serie, etiqueta MB, folio de garantía o de reclamación" autocomplete="off" autofocus>
        </div>
        <button type="submit" class="btn">Consultar</button>
    </form>

    <div class="gt-rapidos">
        @can('garantias.gestionar')
            <a href="{{ route('garantias.reclamaciones.create') }}" class="btn">Registrar reclamación</a>
            <a href="{{ route('garantias.create') }}" class="btn btn--ghost">Alta manual de garantía</a>
        @endcan
        <a href="{{ route('garantias.index', ['semaforo' => 'por_vencer']) }}" class="btn btn--ghost">Próximas a vencer</a>
        <a href="{{ route('garantias.index', ['semaforo' => 'vencida']) }}" class="btn btn--ghost">Vencidas</a>
        <a href="{{ route('garantias.reportes') }}" class="btn btn--ghost">Reportes</a>
    </div>

    @if ($resumen['seguridad'])
        <div class="gt-aviso rojo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                <b>{{ $resumen['seguridad'] }} posible(s) incidente(s) de seguridad abiertos</b>
                Se evalúan por tecnovigilancia, aunque la garantía esté vencida.
                <a class="tbl-link" href="{{ route('garantias.reclamaciones.index', ['vista' => 'seguridad']) }}">Ver</a>
            </div>
        </div>
    @endif

    <div class="gt-kpis">
        <a class="gt-kpi-link" href="{{ route('garantias.index') }}">
            <div class="card card--accent is-green stat">
                <span class="stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg></span>
                <div><div class="stat-num">{{ number_format($resumen['activas']) }}</div><div class="stat-lbl">Garantías vigentes</div></div>
            </div>
        </a>
        <a class="gt-kpi-link" href="{{ route('garantias.reclamaciones.index') }}">
            <div class="card card--accent stat">
                <span class="stat-ico blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                <div>
                    <div class="stat-num">{{ $resumen['abiertas'] }}</div>
                    <div class="stat-lbl">Reclamaciones abiertas
                        @if ($resumen['atrasadas'])<small style="color:var(--danger);">{{ $resumen['atrasadas'] }} requieren atención</small>@endif
                    </div>
                </div>
            </div>
        </a>
        <a class="gt-kpi-link" href="{{ route('garantias.index', ['semaforo' => 'por_vencer']) }}">
            <div class="card card--accent is-amber stat">
                <span class="stat-ico orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></span>
                <div><div class="stat-num">{{ $resumen['por_vencer'] }}</div><div class="stat-lbl">Próximas a vencer<small>en los siguientes {{ config('garantias.dias_por_vencer') }} días</small></div></div>
            </div>
        </a>
        <a class="gt-kpi-link" href="{{ route('garantias.reportes') }}">
            <div class="card card--accent is-danger stat">
                <span class="stat-ico orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <div>
                    <div class="stat-num">${{ number_format($resumen['costo_mes'], 0) }}</div>
                    <div class="stat-lbl">Costo de garantías del mes
                        @if ($resumen['por_aprobar'])<small style="color:var(--accent);">{{ $resumen['por_aprobar'] }} gasto(s) por aprobar</small>@endif
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="gt-2">
        <div class="gt-col">
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Requieren atención</h3>
                    <span class="right"><a class="tbl-link" href="{{ route('garantias.reclamaciones.index') }}">Ver todas</a></span>
                </div>
                @forelse ($atencion as $r)
                    <a class="gt-fila" href="{{ route('garantias.reclamaciones.show', $r) }}">
                        <div>
                            <div class="t">{{ $r->folio }} · {{ $r->equipoNombre() }}</div>
                            <div class="s">{{ $r->clienteNombre() }} · {{ $r->etapaLabel() }} · {{ $r->recibida_en->diffForHumans() }}</div>
                        </div>
                        <div class="der">
                            @if ($r->es_seguridad)<span class="badge badge--danger">Seguridad</span>@endif
                            @if ($r->atrasada())<span class="badge badge--danger">Atrasada</span>@endif
                            <span class="badge {{ $r->prioridadBadge() }}">{{ $r->prioridadLabel() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="gt-vacio">No hay reclamaciones abiertas.</div>
                @endforelse
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Reclamaciones por mes</h3><span class="right gt-s">Últimos 6 meses</span></div>
                @php $max = max(1, $porMes->max('total')); @endphp
                <div class="gt-barras">
                    @foreach ($porMes as $m)
                        <div class="gt-barra" title="{{ $m['total'] }} en {{ $m['mes']->translatedFormat('F Y') }}">
                            <span class="v">{{ $m['total'] }}</span>
                            <span class="b" style="height:{{ max(2, round($m['total'] / $max * 100)) }}%;"></span>
                        </div>
                    @endforeach
                </div>
                <div class="gt-barras-mes">
                    @foreach ($porMes as $m)<span>{{ $m['mes']->translatedFormat('M') }}</span>@endforeach
                </div>
            </div>
        </div>

        <div class="gt-col">
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Próximas a vencer</h3>
                    <span class="right"><a class="tbl-link" href="{{ route('garantias.index', ['semaforo' => 'por_vencer']) }}">Ver todas</a></span>
                </div>
                @forelse ($proximas as $g)
                    <a class="gt-fila" href="{{ route('garantias.show', $g) }}">
                        <div>
                            <div class="t">{{ $g->equipoNombre() }}</div>
                            <div class="s">{{ $g->clienteNombre() }}{{ $g->no_serie ? ' · '.$g->no_serie : '' }}</div>
                        </div>
                        <div class="der"><span class="badge badge--warn">{{ $g->diasRestantes() }} días</span></div>
                    </a>
                @empty
                    <div class="gt-vacio">Ninguna vence en los próximos {{ config('garantias.dias_por_vencer') }} días.</div>
                @endforelse
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>En este momento</h3></div>
                <dl class="gt-kv">
                    <div><dt>Equipos en reparación</dt><dd>{{ $resumen['en_reparacion'] }}</dd></div>
                    <div><dt>Préstamos con el cliente</dt><dd>{{ $resumen['prestamos_fuera'] }}</dd></div>
                    <div><dt>Gastos por aprobar</dt><dd>{{ $resumen['por_aprobar'] }}</dd></div>
                    <div><dt>Reclamaciones atrasadas</dt><dd>{{ $resumen['atrasadas'] }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
@endsection
