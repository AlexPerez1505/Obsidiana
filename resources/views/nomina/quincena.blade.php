@extends('layouts.dashboard')

@section('title', 'Nómina')
@section('page-title', 'Nómina')
@section('page-sub', 'Se calcula sola con la asistencia, los sueldos y las reglas que configuró RH')

@section('content')
    @include('nomina._tabs')

    @php
        $mon = fn ($n) => '$'.number_format($n, 2);
        $iniciales = fn ($n) => collect(explode(' ', trim($n)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    @endphp

    {{-- ============ Periodo y acciones ============ --}}
    <div class="rh-card rh-pad rh-in nq-cab" style="--i:1">
        <div class="nq-per">
            <a class="btn btn--ghost" href="{{ route('nomina.index', ['desde' => $anterior[0]->toDateString()]) }}" aria-label="Quincena anterior"><x-ui.icon name="chevron-left" /></a>
            <div class="nq-per-t">
                <b>{{ $desde->translatedFormat('d \d\e F') }} – {{ $hasta->translatedFormat('d \d\e F \d\e Y') }}</b>
                <span class="rh-sub">Quincena {{ $desde->day <= 15 ? '1' : '2' }} de {{ $desde->translatedFormat('F') }}</span>
            </div>
            <a class="btn btn--ghost" href="{{ route('nomina.index', ['desde' => $siguiente[0]->toDateString()]) }}" aria-label="Quincena siguiente"><x-ui.icon name="chevron-right" /></a>
            @unless ($enCurso)<a class="btn btn--ghost" href="{{ route('nomina.index') }}">Hoy</a>@endunless
        </div>

        <div class="nq-est">
            @if ($run)
                <span class="rh-chip {{ $run->status === 'pagada' ? 'ok' : 'info' }}">{{ $run->status === 'pagada' ? 'Pagada' : 'Cerrada' }}</span>
                <span class="rh-sub">{{ $run->closed_at?->setTimezone(config('asistencia.timezone'))->format('d/m/Y H:i') }}{{ $run->cerro ? ' · '.$run->cerro->name : '' }}</span>
            @elseif ($enCurso)
                <span class="rh-chip warn">En curso: los montos cambian cada día</span>
            @elseif ($concluida)
                <span class="rh-chip info">Lista para cerrar</span>
            @else
                <span class="rh-chip">Por venir</span>
            @endif
        </div>

        <div class="rh-actions nq-acc">
            <a class="btn btn--ghost" href="{{ route('nomina.exportar', ['desde' => $desde->toDateString()]) }}"><x-ui.icon name="download" /> Exportar CSV</a>
            @if ($run)
                <form method="POST" action="{{ route('nomina.pagada', ['desde' => $desde->toDateString()]) }}">@csrf
                    <button class="btn btn--ghost" type="submit">@if ($run->status !== 'pagada')<x-ui.icon name="check" />@endif {{ $run->status === 'pagada' ? 'Marcar pendiente de pago' : 'Marcar como pagada' }}</button>
                </form>
                <form method="POST" action="{{ route('nomina.reabrir', ['desde' => $desde->toDateString()]) }}" data-confirm="Los montos congelados se descartan y la quincena vuelve a calcularse con la asistencia y las reglas de hoy." data-confirm-title="Reabrir quincena" data-confirm-ok="Reabrir">@csrf
                    <button class="btn btn--ghost" type="submit">Reabrir</button>
                </form>
            @elseif ($concluida)
                <form method="POST" action="{{ route('nomina.cerrar', ['desde' => $desde->toDateString()]) }}"
                      data-confirm="Se congelan los montos de {{ $totales['empleados'] }} empleado(s) ({{ $mon($totales['neto']) }} netos){{ $pendientes ? '. Ojo: hay '.$pendientes.' día(s) sin cerrar' : '' }}{{ $sinSueldo ? ' y '.$sinSueldo.' persona(s) sin sueldo' : '' }}."
                      data-confirm-title="Cerrar quincena" data-confirm-ok="Cerrar quincena" data-confirm-danger="0">@csrf
                    <button class="btn" type="submit"><x-ui.icon name="lock" /> Cerrar quincena</button>
                </form>
            @endif
        </div>
    </div>

    @if (! $run && $sinSueldo)
        <x-ui.alert type="default" style="margin:14px 0 0;">
            <b>{{ $sinSueldo }} persona(s) sin sueldo.</b> Captúralo una sola vez en <a href="{{ route('nomina.empleados') }}">Empleados y sueldos</a> y la nómina se calcula sola cada quincena.
        </x-ui.alert>
    @endif

    {{-- ============ Totales ============ --}}
    <div class="rh-kpis" style="margin:16px 0;">
        <div class="rh-kpi rh-in" style="--i:2"><div class="rh-kpi-v" data-count="{{ $totales['sueldos'] }}" data-dec="2" data-prefix="$">{{ $mon($totales['sueldos']) }}</div><div class="rh-kpi-k">Sueldos base</div></div>
        <div class="rh-kpi ok rh-in" style="--i:3"><div class="rh-kpi-v" data-count="{{ $totales['bonos'] }}" data-dec="2" data-prefix="+$">+{{ $mon($totales['bonos']) }}</div><div class="rh-kpi-k">Bonos</div></div>
        <div class="rh-kpi mal rh-in" style="--i:4"><div class="rh-kpi-v" data-count="{{ $totales['descuentos'] }}" data-dec="2" data-prefix="−$">−{{ $mon($totales['descuentos']) }}</div><div class="rh-kpi-k">Descuentos</div></div>
        <div class="rh-kpi info rh-in" style="--i:5"><div class="rh-kpi-v" data-count="{{ $totales['neto'] }}" data-dec="2" data-prefix="$">{{ $mon($totales['neto']) }}</div><div class="rh-kpi-k">Neto a pagar · {{ $totales['empleados'] }} empleados</div></div>
    </div>

    {{-- ============ Por persona ============ --}}
    <div class="nq-barra rh-in" style="--i:6">
        <input type="search" id="nq-buscar" placeholder="Buscar persona…" aria-label="Buscar persona">
        <button type="button" class="btn btn--ghost" id="nq-abrir">Expandir todo</button>
        <button type="button" class="btn btn--ghost" id="nq-cerrar">Contraer todo</button>
    </div>

    <div class="rh-stack-gap" id="nq-lista">
        @forelse ($filas as $i => $f)
            @php $m = $f['metricas']; $abierta = session('abrir') === 'u'.$f['user_id']; @endphp
            <div class="rh-acc rh-in {{ $abierta ? 'open' : '' }}" id="u{{ $f['user_id'] }}" data-nombre="{{ mb_strtolower($f['nombre']) }}" style="--i:{{ min($i, 12) + 7 }}">
                <div class="rh-acc-h">
                    <span class="nq-av">{{ $iniciales($f['nombre']) }}</span>
                    <div class="rh-acc-main">
                        <div class="rh-acc-t">{{ $f['nombre'] }}</div>
                        <div class="rh-acc-s">
                            {{ $f['numero'] ?: 'Sin no. de checador' }} ·
                            {{ $m['dias_trabajados'] ?? 0 }}/{{ $m['laborales'] ?? 0 }} días
                            @if (($m['faltas'] ?? 0))<span class="rh-chip bad">{{ $m['faltas'] }} falta(s)</span>@endif
                            @if (($m['retardos'] ?? 0))<span class="rh-chip warn">{{ $m['retardos'] }} retardo(s)</span>@endif
                            @if ($f['sin_sueldo'])<span class="rh-chip bad">Sin sueldo</span>@endif
                        </div>
                    </div>
                    <div class="rh-acc-side">
                        @if ($f['bonos'])<span class="rh-chip ok">+{{ $mon($f['bonos']) }}</span>@endif
                        @if ($f['descuentos'])<span class="rh-chip bad">−{{ $mon($f['descuentos']) }}</span>@endif
                        <b class="nq-neto">{{ $mon($f['neto']) }}</b>
                    </div>
                </div>
                <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                    @foreach ($f['avisos'] as $a)<div class="nq-aviso"><x-ui.icon name="alert" :size="15" /> {{ $a }}</div>@endforeach

                    <div class="nq-mets">
                        <div><b>{{ $m['laborales'] ?? 0 }}</b><span>Días laborales</span></div>
                        <div><b>{{ $m['dias_trabajados'] ?? 0 }}</b><span>Trabajados</span></div>
                        <div><b>{{ $m['faltas'] ?? 0 }}</b><span>Faltas</span></div>
                        <div><b>{{ $m['retardos'] ?? 0 }}</b><span>Retardos</span></div>
                        <div><b>{{ $m['vacaciones'] ?? 0 }}</b><span>Vacaciones</span></div>
                        <div><b>{{ ($m['comisiones'] ?? 0) + ($m['campo'] ?? 0) + ($m['home_office'] ?? 0) + ($m['eventos'] ?? 0) }}</b><span>Fuera de oficina</span></div>
                        <div><b>{{ $m['horas_trabajadas'] ?? 0 }}</b><span>Horas</span></div>
                        <div><b>{{ isset($m['asistencia_pct']) && $m['asistencia_pct'] !== null ? $m['asistencia_pct'].'%' : '—' }}</b><span>Asistencia</span></div>
                    </div>

                    <div class="nq-des">
                        <div class="nq-r nq-r--base"><span>Sueldo de la quincena</span><span></span><b>{{ $mon($f['sueldo']) }}</b></div>
                        @forelse ($f['lineas'] as $l)
                            <div class="nq-r {{ $l['tipo'] === 'bono' ? 'ok' : 'bad' }}">
                                <span><b><x-ui.icon :name="$l['tipo'] === 'bono' ? 'plus' : 'minus'" :size="14" /> {{ $l['nombre'] }}</b><small>{{ $l['detalle'] }}</small></span>
                                <span class="rh-chip {{ ($l['origen'] ?? '') === 'concepto' ? 'info' : '' }}">{{ ($l['origen'] ?? '') === 'concepto' ? 'Concepto' : 'Regla' }}</span>
                                <b>{{ $l['tipo'] === 'bono' ? '+' : '−' }}{{ $mon($l['monto']) }}</b>
                            </div>
                        @empty
                            <div class="nq-r"><span class="rh-sub">Sin bonos ni descuentos esta quincena.</span><span></span><b></b></div>
                        @endforelse
                        @foreach ($f['en_juego'] as $j)
                            <div class="nq-r jue">
                                <span><b><x-ui.icon name="hourglass" :size="14" /> {{ $j['nombre'] }}</b><small>{{ $j['cumple_ahora'] ? 'Va cumpliendo' : 'Ya no cumple' }}{{ $j['monto'] ? ' (' . $mon($j['monto']) . ')' : '' }} · {{ $j['detalle'] }}</small></span>
                                <span class="rh-chip {{ $j['cumple_ahora'] ? 'ok' : 'bad' }}">{{ $j['cumple_ahora'] ? 'En juego' : 'Perdido' }}</span>
                                <b></b>
                            </div>
                        @endforeach
                        <div class="nq-r nq-r--tot"><span>Neto a pagar</span><span></span><b>{{ $mon($f['neto']) }}</b></div>
                    </div>

                    <div class="rh-actions" style="margin-top:14px;">
                        @if ($f['user_id'])<a class="btn btn--ghost" href="{{ route('asistencia.empleado', [$f['user_id'], 'mes' => $hasta->format('Y-m')]) }}">Ver su asistencia</a>@endif
                        @if (! $f['congelada'] && $f['user_id'])
                            <button type="button" class="btn btn--ghost" data-rh-toggle="cu{{ $f['user_id'] }}"><x-ui.icon name="plus" /> Bono o descuento solo esta quincena</button>
                        @endif
                    </div>

                    @if (! $f['congelada'] && $f['user_id'])
                        <div class="rh-acc plain" id="cu{{ $f['user_id'] }}" style="margin-top:12px;">
                            <div class="rh-acc-b"><div class="rh-acc-i"><div class="rh-acc-c">
                                <form method="POST" action="{{ route('nomina.concepto-unico', ['user' => $f['user_id'], 'desde' => $desde->toDateString()]) }}" class="rh-form">@csrf
                                    <div class="rh-grid">
                                        <label>Tipo <select name="kind"><option value="bono">Bono</option><option value="descuento">Descuento</option></select></label>
                                        <label>Concepto <input type="text" name="name" placeholder="Horas extra, anticipo…" required></label>
                                        <label>Monto $ <input type="number" step="0.01" min="0.01" name="amount" required></label>
                                        <label>Nota (opcional) <input type="text" name="note"></label>
                                    </div>
                                    <div class="rh-actions" style="margin-top:12px;"><button class="btn" type="submit">Agregar</button><span class="rh-sub">Aplica solo a esta quincena. Para algo fijo, ve a Empleados y sueldos.</span></div>
                                </form>
                            </div></div></div>
                        </div>
                    @endif
                </div></div></div>
            </div>
        @empty
            <div class="rh-card rh-empty"><b>Nadie toma asistencia todavía</b>Asigna horarios en Asistencia, sección Horarios y festivos, para que entren a la nómina.</div>
        @endforelse
    </div>

    <style>
        .nq-cab { display:flex; flex-wrap:wrap; align-items:center; gap:14px 22px; }
        .nq-per { display:flex; align-items:center; gap:10px; }
        .nq-per-t { display:flex; flex-direction:column; min-width:200px; text-align:center; }
        .nq-est { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .nq-acc { margin-left:auto; } .nq-acc form { margin:0; }
        .nq-barra { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px; } .nq-barra input { flex:1; min-width:200px; }
        .nq-av { flex:0 0 36px; height:36px; border-radius:50%; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
        .nq-neto { font-size:17px; font-variant-numeric:tabular-nums; }
        .nq-aviso { display:flex; align-items:center; gap:8px; margin-bottom:8px; padding:9px 12px; border-radius:8px; background:var(--accent-soft); color:var(--accent); font-size:13px; font-weight:500; }
        .nq-mets { display:grid; grid-template-columns:repeat(auto-fit, minmax(96px, 1fr)); gap:8px; margin:10px 0 14px; }
        .nq-mets div { padding:9px 10px; border-radius:8px; background:var(--surface-2); text-align:center; }
        .nq-mets b { display:block; font-size:18px; font-variant-numeric:tabular-nums; } .nq-mets span { font-size:11px; color:var(--muted); text-transform:uppercase; font-weight:600; }
        .nq-des { border:1px solid var(--border); border-radius:10px; overflow:hidden; }
        .nq-r b .ui-ico { margin-right:2px; }
        .nq-r { display:grid; grid-template-columns:1fr auto 110px; gap:10px; align-items:center; padding:10px 14px; border-top:1px solid var(--border); font-size:13.5px; }
        .nq-r:first-child { border-top:0; } .nq-r small { display:block; color:var(--muted); font-size:12px; margin-top:2px; font-weight:400; }
        .nq-r > b:last-child { text-align:right; font-variant-numeric:tabular-nums; }
        .nq-r.ok > b:last-child { color:var(--green); } .nq-r.bad > b:last-child { color:var(--danger); }
        .nq-r--base { background:var(--surface-2); } .nq-r--tot { background:var(--primary-soft); font-weight:700; } .nq-r.jue { background:var(--surface-2); }
        @media (max-width: 560px) { .nq-r { grid-template-columns:1fr auto; } .nq-r > span:nth-child(2) { display:none; } .nq-acc { margin-left:0; width:100%; } .nq-per-t { min-width:0; flex:1; } }
    </style>
@endsection

@push('scripts')
<script>
    (function () {
        var buscar = document.getElementById('nq-buscar'), lista = document.getElementById('nq-lista');
        if (!lista) return;
        var cards = lista.querySelectorAll('.rh-acc[data-nombre]');
        buscar.addEventListener('input', function () {
            var q = buscar.value.trim().toLowerCase();
            cards.forEach(function (c) { c.style.display = !q || c.dataset.nombre.indexOf(q) !== -1 ? '' : 'none'; });
        });
        document.getElementById('nq-abrir').onclick = function () { cards.forEach(function (c) { if (c.style.display !== 'none') { c.classList.add('open'); c.querySelector('.rh-acc-h').setAttribute('aria-expanded', 'true'); } }); };
        document.getElementById('nq-cerrar').onclick = function () { cards.forEach(function (c) { c.classList.remove('open'); c.querySelector('.rh-acc-h').setAttribute('aria-expanded', 'false'); }); };
    })();
</script>
@endpush
