@extends('layouts.dashboard')

@section('title', 'Indicadores de logística')
@section('page-title', 'Indicadores de logística')
@section('page-sub', $desde->format('d/m/Y').' al '.$hasta->format('d/m/Y'))

@section('content')
    <form method="GET" class="an-filtro">
        <label>Desde <input type="date" name="desde" value="{{ $desde->toDateString() }}"></label>
        <label>Hasta <input type="date" name="hasta" value="{{ $hasta->toDateString() }}"></label>
        <button class="btn" type="submit">Aplicar</button>
        <a href="{{ route('logistica.analytics') }}" class="btn btn--ghost">Últimos 30 días</a>
    </form>

    <div class="an-kpis">
        @php
            $tarjetas = [
                ['Entregas hoy', $kpis['hoy'], ''],
                ['Entregas exitosas', $kpis['entregas'], ''],
                ['No entregadas', $kpis['no_entregadas'], $kpis['no_entregadas'] ? 'mal' : ''],
                ['Tasa de éxito', $kpis['exito'] !== null ? $kpis['exito'].'%' : '—', ''],
                ['Puntualidad', $kpis['puntualidad'] !== null ? $kpis['puntualidad'].'%' : '—', ''],
                ['Km recorridos', number_format($kpis['km'], 1), ''],
                ['Tiempo promedio en cliente', $kpis['tiempo_cliente'] !== null ? $kpis['tiempo_cliente'].' min' : '—', ''],
                ['Desviaciones de ruta', $kpis['desviaciones'], $kpis['desviaciones'] ? 'warn' : ''],
                ['Incidencias', $kpis['incidencias'], $kpis['incidencias'] ? 'warn' : ''],
                ['Combustible', '$'.number_format($kpis['combustible'], 0), ''],
                ['Combustible por entrega', $kpis['por_entrega'] !== null ? '$'.number_format($kpis['por_entrega'], 2) : '—', ''],
                ['Rutas (finalizadas)', $kpis['rutas'].' ('.$kpis['finalizadas'].')', ''],
            ];
        @endphp
        @foreach ($tarjetas as [$k, $v, $tono])
            <div class="an-k {{ $tono }}"><div class="k">{{ $k }}</div><div class="v">{{ $v }}</div></div>
        @endforeach
    </div>

    <div class="card an-card">
        <div class="an-h">Entregas por día</div>
        <div class="an-barras">
            @foreach ($porDia as $dia => $x)
                @php $tot = $x['ok'] + $x['no']; @endphp
                <div class="an-col" title="{{ \Illuminate\Support\Carbon::parse($dia)->format('d/m') }}: {{ $x['ok'] }} entregadas, {{ $x['no'] }} no entregadas">
                    <div class="an-pila" style="height:{{ $tot ? max(4, round($tot * 100 / $maxDia)) : 0 }}%">
                        @if ($x['no'])<span class="no" style="flex:{{ $x['no'] }}"></span>@endif
                        @if ($x['ok'])<span class="ok" style="flex:{{ $x['ok'] }}"></span>@endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="an-eje"><span>{{ $desde->format('d/m') }}</span><span><i class="ok"></i> Entregadas <i class="no"></i> No entregadas</span><span>{{ $hasta->format('d/m') }}</span></div>
    </div>

    <div class="an-dos">
        <div class="card" style="padding:0; overflow-x:auto;">
            <div class="an-h" style="padding:13px 16px;">Por vehículo</div>
            <table class="an-t">
                <thead><tr><th>Vehículo</th><th>Rutas</th><th>Km</th><th>Entregas</th><th>Litros</th><th>Gasto</th></tr></thead>
                <tbody>
                    @forelse ($porVehiculo as $v)
                        <tr><td>{{ $v['nombre'] }}</td><td>{{ $v['rutas'] }}</td><td>{{ number_format($v['km'], 1) }}</td><td>{{ $v['entregas'] }}</td><td>{{ number_format($v['litros'], 1) }}</td><td>${{ number_format($v['gasto'], 0) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="an-vacio">Sin rutas en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card" style="padding:0; overflow-x:auto;">
            <div class="an-h" style="padding:13px 16px;">Por chofer</div>
            <table class="an-t">
                <thead><tr><th>Chofer</th><th>Rutas</th><th>Entregas</th><th>Fallidas</th><th>Puntualidad</th><th>Km</th></tr></thead>
                <tbody>
                    @forelse ($porChofer as $c)
                        <tr><td>{{ $c['nombre'] }}</td><td>{{ $c['rutas'] }}</td><td>{{ $c['entregas'] }}</td><td>{{ $c['fallidas'] }}</td><td>{{ $c['puntualidad'] !== null ? $c['puntualidad'].'%' : '—' }}</td><td>{{ number_format($c['km'], 1) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="an-vacio">Sin rutas en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="an-nota">Puntualidad: entregas cerradas antes del fin de su ventana horaria (solo cuentan las paradas con ventana). Tiempo en cliente: de la llegada al cierre de la entrega. Los km salen del GPS del teléfono.</p>

    <style>
        .an-filtro { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; margin-bottom:16px; }
        .an-filtro label { font-size:12px; font-weight:600; color:var(--muted); display:block; }
        .an-filtro input { display:block; margin-top:4px; }
        .an-kpis { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px,1fr)); gap:12px; margin-bottom:18px; }
        .an-k { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
        .an-k .k { font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; font-weight:600; }
        .an-k .v { margin-top:4px; font-size:22px; font-weight:700; }
        .an-k.mal .v { color:var(--danger); }
        .an-k.warn .v { color:var(--accent); }
        .an-card { padding:16px; margin-bottom:18px; }
        .an-h { font-weight:700; }
        .an-barras { display:flex; align-items:flex-end; gap:3px; height:150px; margin-top:14px; border-bottom:1px solid var(--border); }
        .an-col { flex:1; height:100%; display:flex; align-items:flex-end; min-width:0; }
        .an-pila { width:100%; display:flex; flex-direction:column; border-radius:3px 3px 0 0; overflow:hidden; }
        .an-pila .ok, .an-eje i.ok { background:var(--green); }
        .an-pila .no, .an-eje i.no { background:var(--danger); }
        .an-eje { display:flex; justify-content:space-between; margin-top:6px; font-size:12px; color:var(--muted); }
        .an-eje i { display:inline-block; width:10px; height:10px; border-radius:2px; margin:0 4px 0 10px; vertical-align:middle; }
        .an-dos { display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start; }
        .an-t { width:100%; border-collapse:collapse; font-size:13.5px; }
        .an-t th { padding:9px 14px; background:var(--surface-2); color:var(--muted); font-size:11.5px; font-weight:600; text-transform:uppercase; text-align:left; }
        .an-t td { padding:10px 14px; border-top:1px solid var(--border); }
        .an-vacio { color:var(--muted); text-align:center; }
        .an-nota { font-size:12px; color:var(--muted); margin-top:14px; }
        @media (max-width: 960px) { .an-dos { grid-template-columns:1fr; } }
    </style>
@endsection
