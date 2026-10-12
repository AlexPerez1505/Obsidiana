@extends('layouts.dashboard')

@section('title', 'Asistencia')
@section('page-title', 'Asistencia')
@section('page-sub', $dia->translatedFormat('l d \d\e F \d\e Y'))

@section('content')
    @include('asistencia._tabs')

    @php $r = $datos['resumen']; @endphp

    @if ($sinHorarios)
        <x-ui.alert type="default" style="margin-bottom:14px;">
            {{ $sinHorarios }} persona(s) aprobadas todavía no tienen horario y no se les toma asistencia.
            @can('asistencia.gestionar')<a href="{{ route('asistencia.horarios') }}">Asignar horarios</a>@endcan
        </x-ui.alert>
    @endif

    {{-- ============ Fecha y acciones ============ --}}
    <div class="ap-barra rh-in" style="--i:1">
        <form method="GET" class="ap-fecha">
            <a class="btn btn--ghost" href="{{ route('asistencia.panel', ['fecha' => $dia->copy()->subDay()->toDateString()]) }}" aria-label="Día anterior"><x-ui.icon name="chevron-left" /></a>
            <input type="date" name="fecha" value="{{ $dia->toDateString() }}" onchange="this.form.submit()" aria-label="Fecha">
            <a class="btn btn--ghost" href="{{ route('asistencia.panel', ['fecha' => $dia->copy()->addDay()->toDateString()]) }}" aria-label="Día siguiente"><x-ui.icon name="chevron-right" /></a>
            @unless ($esHoy)<a class="btn btn--ghost" href="{{ route('asistencia.panel') }}">Hoy</a>@endunless
        </form>
        @if ($esHoy)<span class="ap-vivo"><i></i> En vivo</span>@endif
        @if ($puedeGestionar && $dia->lt(today()))
            <form method="POST" action="{{ route('asistencia.cerrar') }}" data-confirm="Se recalcula el día y se generan las faltas de quien no checó ni tiene justificante." data-confirm-title="Cerrar día" data-confirm-ok="Cerrar día" style="margin-left:auto;">
                @csrf <input type="hidden" name="fecha" value="{{ $dia->toDateString() }}">
                <button class="btn btn--ghost" type="submit">Recalcular y cerrar este día</button>
            </form>
        @endif
    </div>

    <div id="ap-live">
    {{-- ============ Resumen del día ============ --}}
    <div class="rh-kpis" style="margin-bottom:16px;">
        @php
            $tarjetas = [
                ['Deben checar', $r['esperados'], ''],
                ['Presentes', $r['presentes'], 'ok'],
                ['Retardos', $r['retardos'], 'warn'],
                ['Comisión / evento', $r['comision'], 'info'],
                ['Home office / campo', $r['home_office'] + $r['campo'], 'info'],
                ['Vacaciones / permiso', $r['ausencia_justificada'], ''],
            ];
            if ($esHoy) { $tarjetas[] = ['Aún sin checar', $r['sin_checar'], $r['sin_checar'] ? 'warn' : '']; }
            $tarjetas[] = ['Faltas', $r['faltas'], $r['faltas'] ? 'mal' : ''];
        @endphp
        @foreach ($tarjetas as $n => [$k, $v, $tono])
            <div class="rh-kpi {{ $tono }} rh-in" style="--i:{{ $n + 2 }}"><div class="rh-kpi-v" data-count="{{ $v }}">{{ $v }}</div><div class="rh-kpi-k">{{ $k }}</div></div>
        @endforeach
    </div>

    <div class="ap-grid">
        {{-- ============ Empleados del día ============ --}}
        <div class="rh-card rh-in" style="--i:6; overflow:hidden;">
            <div class="rh-pad" style="padding-bottom:10px;"><b class="rh-h">Empleados · {{ $dia->format('d/m/Y') }}</b></div>
            <table class="rh-t">
                <thead><tr><th>Empleado</th><th>Entrada</th><th>Salida</th><th>Trabajado</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($datos['empleados'] as $e)
                        @php $f = $e['fila']; @endphp
                        <tr>
                            <td data-label="Empleado"><a href="{{ route('asistencia.empleado', [$e['user'], 'mes' => $dia->format('Y-m')]) }}"><b>{{ $e['user']->name }}</b></a>
                                <div class="rh-sub">{{ $e['user']->checador_id ?: '—' }}</div></td>
                            <td data-label="Entrada">{{ $f && $f->check_in ? substr($f->check_in, 0, 5) : '—' }}</td>
                            <td data-label="Salida">@if ($f && $f->check_out){{ substr($f->check_out, 0, 5) }}@elseif ($f && $f->incomplete)<span class="rh-sub">abierta</span>@else —@endif</td>
                            <td data-label="Trabajado">{{ $f && $f->worked_minutes ? intdiv($f->worked_minutes, 60).' h '.str_pad($f->worked_minutes % 60, 2, '0', STR_PAD_LEFT).' min' : '—' }}</td>
                            <td data-label="Estado">
                                @switch($e['estado'])
                                    @case('puntual')<span class="rh-chip ok">Puntual</span>@break
                                    @case('retardo')<span class="rh-chip warn">Retardo +{{ $f->late_minutes }} min</span>@break
                                    @case('falta')<span class="rh-chip bad">Falta</span>@break
                                    @case('sin_checar')<span class="rh-chip">Sin checar</span>@break
                                    @default<span class="rh-chip info">{{ $e['modalidad']['label'] ?? $e['estado'] }}</span>
                                @endswitch
                                @if ($e['modalidad'] && in_array($e['estado'], ['puntual', 'retardo']))<span class="rh-sub"> · {{ $e['modalidad']['label'] }}</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="rh-empty"><b>Nadie debía checar este día</b>Es día de descanso, festivo o aún no hay horarios asignados.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ============ Últimas checadas ============ --}}
        <div class="rh-card rh-in" style="--i:7; overflow:hidden;">
            <div class="rh-pad" style="padding-bottom:10px;"><b class="rh-h">Últimas checadas de hoy</b></div>
            @forelse ($ultimas as $p)
                <div class="ap-ult">
                    <span class="ap-hora">{{ $p->horaLocal()->format('H:i') }}</span>
                    <span><b>{{ $p->user->name }}</b> <span class="rh-sub">· {{ $p->kindLabel() }}{{ $p->method === 'manual' ? ' (manual)' : '' }}</span></span>
                </div>
            @empty
                <div class="rh-empty"><b>Sin checadas todavía</b>Aparecerán aquí en cuanto alguien ponga su huella.</div>
            @endforelse
        </div>
    </div>

    </div>{{-- /ap-live --}}

    {{-- ============ Indicadores del mes (desplegable) ============ --}}
    <div class="rh-acc rh-in open" id="ind-mes" style="--i:8; margin-top:18px;">
        <div class="rh-acc-h">
            <div class="rh-acc-main"><div class="rh-acc-t">Indicadores de {{ $dia->translatedFormat('F Y') }}</div><div class="rh-acc-s">Puntualidad, promedio de llegada y quién destaca</div></div>
            <div class="rh-acc-side"><span class="rh-chip {{ ($kpis['puntualidad'] ?? 0) >= 90 ? 'ok' : 'warn' }}">{{ $kpis['puntualidad'] !== null ? $kpis['puntualidad'].'% puntual' : 'Sin datos' }}</span></div>
        </div>
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            <div class="rh-kpis">
                <div class="rh-kpi"><div class="rh-kpi-v" @if ($kpis['puntualidad'] !== null) data-count="{{ $kpis['puntualidad'] }}" data-dec="1" data-suffix="%" @endif>{{ $kpis['puntualidad'] !== null ? $kpis['puntualidad'].'%' : '—' }}</div><div class="rh-kpi-k">Puntualidad del mes</div></div>
                <div class="rh-kpi"><div class="rh-kpi-v">{{ $kpis['promedio_llegada'] ?? '—' }}</div><div class="rh-kpi-k">Promedio de llegada</div></div>
                <div class="rh-kpi ok"><div class="rh-kpi-v sm">{{ $kpis['mas_puntual']['nombre'] ?? '—' }}</div><div class="rh-kpi-k">Más puntual{{ $kpis['mas_puntual'] ? ' · prom. '.$kpis['mas_puntual']['promedio'] : '' }}</div></div>
                <div class="rh-kpi warn"><div class="rh-kpi-v sm">{{ $kpis['mas_retardos']['nombre'] ?? '—' }}</div><div class="rh-kpi-k">Más retardos{{ $kpis['mas_retardos'] ? ' · '.$kpis['mas_retardos']['n'] : '' }}</div></div>
                <div class="rh-kpi mal"><div class="rh-kpi-v sm">{{ $kpis['mas_faltas']['nombre'] ?? '—' }}</div><div class="rh-kpi-k">Más faltas{{ $kpis['mas_faltas'] ? ' · '.$kpis['mas_faltas']['n'] : '' }}</div></div>
                <div class="rh-kpi info"><div class="rh-kpi-v">{{ $kpis['oficina'] }} / {{ $kpis['home_office'] }}</div><div class="rh-kpi-k">Oficina / home office</div></div>
                <div class="rh-kpi info"><div class="rh-kpi-v" data-count="{{ $kpis['comisiones'] }}">{{ $kpis['comisiones'] }}</div><div class="rh-kpi-k">Días en comisión</div></div>
            </div>
        </div></div></div>
    </div>

    {{-- ============ Gráficas (desplegables) ============ --}}
    <div class="rh-acc rh-in" id="graficas" style="--i:9; margin-top:12px;">
        <div class="rh-acc-h">
            <div class="rh-acc-main"><div class="rh-acc-t">Tendencias</div><div class="rh-acc-s">Últimos 6 meses, hora de entrada y asistencia por puesto</div></div>
        </div>
        <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
            <div class="ap-graf">
                <div class="ap-g">
                    <div class="rh-h" style="margin-bottom:10px;">Puntualidad mensual (%)</div>
                    <div class="ap-barras">
                        @foreach ($serie as $s)
                            <div class="b" title="{{ $s['mes'] }}: {{ $s['puntualidad'] ?? '—' }}%"><div class="col"><span style="height:{{ $s['puntualidad'] ?? 0 }}%; background:var(--green)"></span></div><div class="l">{{ $s['mes'] }}</div><div class="n">{{ $s['puntualidad'] ?? '—' }}</div></div>
                        @endforeach
                    </div>
                </div>
                @php $maxRF = max(1, collect($serie)->map(fn ($s) => max($s['retardos'], $s['faltas']))->max()); @endphp
                <div class="ap-g">
                    <div class="rh-h" style="margin-bottom:10px;">Retardos y faltas por mes</div>
                    <div class="ap-barras">
                        @foreach ($serie as $s)
                            <div class="b" title="{{ $s['mes'] }}: {{ $s['retardos'] }} retardos, {{ $s['faltas'] }} faltas">
                                <div class="col dual"><span style="height:{{ round($s['retardos'] * 100 / $maxRF) }}%; background:var(--accent)"></span><span style="height:{{ round($s['faltas'] * 100 / $maxRF) }}%; background:var(--danger)"></span></div>
                                <div class="l">{{ $s['mes'] }}</div><div class="n">{{ $s['retardos'] }} / {{ $s['faltas'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="rh-sub" style="margin-top:6px;"><i style="background:var(--accent)" class="lg"></i>Retardos <i style="background:var(--danger)" class="lg"></i>Faltas</div>
                </div>
                @if (count($horas))
                    @php $minH = collect($horas)->min('minutos') - 10; $maxH = collect($horas)->max('minutos') + 10; @endphp
                    <div class="ap-g">
                        <div class="rh-h" style="margin-bottom:10px;">Hora promedio de entrada (14 días)</div>
                        <div class="ap-barras">
                            @foreach ($horas as $h)
                                <div class="b" title="{{ $h['fecha'] }}: {{ $h['hhmm'] }}"><div class="col"><span style="height:{{ max(6, round(($h['minutos'] - $minH) * 100 / max(1, $maxH - $minH))) }}%; background:var(--primary)"></span></div><div class="l">{{ $h['fecha'] }}</div><div class="n">{{ $h['hhmm'] }}</div></div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if (count($puestos))
                    <div class="ap-g">
                        <div class="rh-h" style="margin-bottom:10px;">Asistencia por puesto (mes)</div>
                        @foreach ($puestos as $p)
                            <div class="ap-pu"><span>{{ $p['puesto'] }}</span><span class="rh-bar"><i data-w="{{ $p['puntualidad'] ?? 0 }}"></i></span><span class="rh-sub">{{ $p['puntualidad'] !== null ? $p['puntualidad'].'%' : '—' }} · {{ $p['faltas'] }} faltas</span></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div></div></div>
    </div>

    <style>
        .ap-barra { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-bottom:16px; }
        .ap-fecha { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .ap-vivo { display:inline-flex; align-items:center; gap:7px; font-size:12.5px; font-weight:600; color:var(--green); padding:4px 10px; border-radius:99px; background:var(--green-soft); }
        .ap-vivo i { width:8px; height:8px; border-radius:50%; background:var(--green); animation:ap-p 1.6s infinite; }
        @keyframes ap-p { 50% { opacity:.25; transform:scale(.7); } }
        .ap-grid { display:grid; grid-template-columns:minmax(0,2fr) minmax(280px,1fr); gap:16px; align-items:start; }
        .ap-ult { display:flex; gap:12px; padding:11px 18px; border-top:1px solid var(--border); font-size:13.5px; transition:background .15s; }
        .ap-ult:hover { background:var(--surface-2); }
        .ap-hora { flex:0 0 46px; color:var(--muted); font-variant-numeric:tabular-nums; font-weight:600; }
        .ap-graf { display:grid; grid-template-columns:repeat(auto-fit, minmax(290px,1fr)); gap:22px; }
        .ap-barras { display:flex; align-items:flex-end; gap:8px; height:170px; }
        .ap-barras .b { flex:1; min-width:0; display:flex; flex-direction:column; height:100%; text-align:center; }
        .ap-barras .col { flex:1; display:flex; align-items:flex-end; justify-content:center; gap:3px; border-bottom:1px solid var(--border); }
        .ap-barras .col span { width:70%; max-width:34px; border-radius:4px 4px 0 0; min-height:2px; transform-origin:bottom; animation:ap-crece .8s cubic-bezier(.2,.7,.2,1) both; }
        .ap-barras .b:nth-child(2) span { animation-delay:.05s; } .ap-barras .b:nth-child(3) span { animation-delay:.1s; } .ap-barras .b:nth-child(4) span { animation-delay:.15s; } .ap-barras .b:nth-child(5) span { animation-delay:.2s; } .ap-barras .b:nth-child(6) span { animation-delay:.25s; }
        @keyframes ap-crece { from { transform:scaleY(0); } to { transform:scaleY(1); } }
        .ap-barras .col.dual span { width:38%; }
        .ap-barras .l { font-size:10.5px; color:var(--muted); margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ap-barras .n { font-size:11px; font-weight:700; }
        .lg { display:inline-block; width:10px; height:10px; border-radius:2px; margin:0 4px 0 8px; vertical-align:middle; }
        .ap-pu { display:grid; grid-template-columns:110px 1fr 130px; gap:10px; align-items:center; padding:6px 0; font-size:13px; }
        @media (max-width: 960px) { .ap-grid { grid-template-columns:1fr; } }
        @media (max-width: 520px) { .ap-pu { grid-template-columns:1fr; gap:4px; } }
    </style>
@endsection

@if ($esHoy)
@push('scripts')
<script>
    // Panel en vivo: cada 20 s refresca solo el resumen del día (las secciones abiertas no se tocan)
    setInterval(async function () {
        if (document.hidden) return;
        try {
            var r = await fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
            if (!r.ok) return;
            var doc = new DOMParser().parseFromString(await r.text(), 'text/html');
            var nuevo = doc.getElementById('ap-live'), actual = document.getElementById('ap-live');
            if (nuevo && actual && nuevo.innerHTML !== actual.innerHTML) {
                actual.innerHTML = nuevo.innerHTML;
                actual.querySelectorAll('.rh-in').forEach(function (e) { e.style.animation = 'none'; });   // sin repetir la animación de entrada
                actual.querySelectorAll('[data-count]').forEach(function (e) { e.textContent = e.dataset.count; });
            }
        } catch (e) {}
    }, 20000);
</script>
@endpush
@endif
