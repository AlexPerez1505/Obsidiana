@extends('layouts.dashboard')

@section('title', 'Reportes de garantías')
@section('page-title', 'Reportes de garantías')
@section('page-sub', 'Calidad, tiempos de atención, costos y recuperaciones')

@section('content')
    @include('structure.garantias._estilos')

    @php
        $k = $kpi;
        $o = fn ($v, $suf = '') => $v === null ? '—' : $v.$suf;
        $periodos = ['3' => '3 meses', '6' => '6 meses', '12' => '12 meses', 'todo' => 'Todo'];
    @endphp

    <div class="gt-tabs">
        @foreach ($periodos as $p => $t)
            <a class="gt-tab {{ (string) $periodo === (string) $p ? 'on' : '' }}" href="{{ route('garantias.reportes', ['periodo' => $p]) }}">{{ $t }}</a>
        @endforeach
    </div>

    <div class="gt-kpis">
        <div class="card stat"><div><div class="stat-num">{{ $k['tasa_fallas'] }}%</div><div class="stat-lbl">Tasa de fallas<small>{{ $k['reclamaciones'] }} reclamaciones · {{ $k['unidades'] }} unidades</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">{{ $k['reincidentes'] }}</div><div class="stat-lbl">Equipos reincidentes<small>2 o más reclamaciones</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">{{ $o($k['horas_respuesta'], ' h') }}</div><div class="stat-lbl">Primera respuesta<small>promedio</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">{{ $o($k['dias_resolucion'], ' d') }}</div><div class="stat-lbl">Tiempo de resolución<small>promedio, de recibida a cerrada</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">{{ $o($k['cumplimiento'], '%') }}</div><div class="stat-lbl">Cerradas a tiempo<small>dentro del plazo por prioridad</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">${{ number_format($k['costo_promedio'], 0) }}</div><div class="stat-lbl">Costo promedio por reclamación<small>total ${{ number_format($k['costo_total'], 0) }}</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">{{ $o($k['pct_recuperado'], '%') }}</div><div class="stat-lbl">Recuperado de fabricantes<small>${{ number_format($k['recuperado'], 0) }} de ${{ number_format($k['recuperable'], 0) }}</small></div></div></div>
        <div class="card stat"><div><div class="stat-num">{{ $o($k['satisfaccion'], ' / 5') }}</div><div class="stat-lbl">Satisfacción del cliente</div></div></div>
    </div>

    <div class="card gt-mb" style="padding:0;">
        <div class="card-head"><h3>Reclamaciones por mes</h3><span class="right gt-s">Últimos 12 meses</span></div>
        @php $max = max(1, $porMes->max('total')); @endphp
        <div class="gt-barras">
            @foreach ($porMes as $m)
                <div class="gt-barra" title="{{ $m['total'] }} en {{ $m['mes']->translatedFormat('F Y') }}">
                    <span class="v">{{ $m['total'] }}</span>
                    <span class="b" style="height:{{ max(2, round($m['total'] / $max * 100)) }}%;"></span>
                </div>
            @endforeach
        </div>
        <div class="gt-barras-mes">@foreach ($porMes as $m)<span>{{ $m['mes']->translatedFormat('M') }}</span>@endforeach</div>
    </div>

    <div class="gt-2b">
        @foreach (['Modelos con más incidencias' => $modelos, 'Proveedores' => $proveedores] as $titulo => $filas)
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>{{ $titulo }}</h3></div>
                @if ($filas->isEmpty())
                    <div class="gt-vacio">{{ $titulo === 'Proveedores' ? 'Captura el proveedor en cada garantía para compararlos.' : 'Aún no hay reclamaciones.' }}</div>
                @else
                    <div class="tbl-scroll">
                        <table class="gt-tabla">
                            <thead><tr><th>{{ $titulo === 'Proveedores' ? 'Proveedor' : 'Marca y modelo' }}</th><th>Unidades</th><th>Reclam.</th><th>Tasa</th><th>Gasto</th><th>Recup.</th></tr></thead>
                            <tbody>
                                @foreach ($filas as $f)
                                    <tr>
                                        <td><b>{{ $f['nombre'] }}</b></td>
                                        <td class="gt-num">{{ $f['unidades'] }}</td>
                                        <td class="gt-num">{{ $f['reclamaciones'] }}</td>
                                        <td class="gt-num"><span class="badge {{ $f['tasa'] >= 30 ? 'badge--danger' : ($f['tasa'] >= 15 ? 'badge--warn' : '') }}">{{ $f['tasa'] }}%</span></td>
                                        <td class="gt-num">${{ number_format($f['gasto'], 0) }}</td>
                                        <td class="gt-num">${{ number_format($f['recuperado'], 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <p class="gt-s" style="margin-top:14px;">
        {{ $k['no_procede'] }} reclamación(es) no procedieron y {{ $k['seguridad'] }} se marcaron como posible incidente de seguridad en el periodo.
    </p>
@endsection
