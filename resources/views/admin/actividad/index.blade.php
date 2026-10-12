@extends('layouts.dashboard')

@section('title', 'Actividad')
@section('page-title', 'Línea de tiempo')
@section('page-sub', 'Qué hizo cada quien, día por día')

@section('content')
    @php
        $iconos = [
            'clientes' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
            'cotizaciones' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/>',
            'ventas' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
            'cobranza' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
            'inventario' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
            'salidas' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'seguimientos' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'comisiones' => '<path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/>',
            'tareas' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        ];
        $tonos = ['clientes' => 'azul', 'cotizaciones' => 'ambar', 'ventas' => 'verde', 'cobranza' => 'verde', 'inventario' => 'azul', 'salidas' => 'rojo', 'seguimientos' => 'ambar', 'comisiones' => 'verde', 'tareas' => 'gris'];
        $iniciales = fn ($nombre) => mb_strtoupper(collect(explode(' ', trim($nombre)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: 'S';
    @endphp

    {{-- ===================== Filtros ===================== --}}
    <form method="GET" class="lt-filtros">
        <div class="lt-rango">
            <input type="date" name="desde" value="{{ $desde->toDateString() }}" aria-label="Desde">
            <span>a</span>
            <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" aria-label="Hasta">
        </div>
        <select name="usuario" aria-label="Usuario">
            <option value="">Todos los usuarios</option>
            @foreach ($usuarios as $u)
                <option value="{{ $u->id }}" @selected($usuarioId === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <select name="modulo" aria-label="Módulo">
            <option value="">Todo</option>
            @foreach ($modulos as $valor => $texto)
                <option value="{{ $valor }}" @selected($modulo === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn">Ver</button>
        <div class="lt-atajos">
            <a href="{{ route('admin.actividad.index', ['desde' => now()->toDateString(), 'hasta' => now()->toDateString()]) }}">Hoy</a>
            <a href="{{ route('admin.actividad.index', ['desde' => now()->subDays(6)->toDateString(), 'hasta' => now()->toDateString()]) }}">7 días</a>
            <a href="{{ route('admin.actividad.index', ['desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->toDateString()]) }}">Este mes</a>
        </div>
    </form>

    {{-- ===================== Resumen ===================== --}}
    <div class="lt-resumen">
        <div class="card lt-kpi">
            <b>{{ $total }}</b>
            <span>acciones del {{ $desde->format('d/m') }} al {{ $hasta->format('d/m') }}</span>
        </div>
        <div class="card lt-kpi lt-kpi--lista">
            <span class="k">Por persona</span>
            @forelse ($porUsuario->take(6) as $nombre => $n)
                <div class="lt-mini"><span>{{ $nombre }}</span><b>{{ $n }}</b></div>
            @empty
                <div class="lt-mini"><span class="muted">Sin actividad</span></div>
            @endforelse
        </div>
        <div class="card lt-kpi lt-kpi--lista">
            <span class="k">Por módulo</span>
            @forelse ($porModulo->sortDesc()->take(6) as $mod => $n)
                <div class="lt-mini"><span>{{ $modulos[$mod] ?? $mod }}</span><b>{{ $n }}</b></div>
            @empty
                <div class="lt-mini"><span class="muted">—</span></div>
            @endforelse
        </div>
    </div>

    {{-- ===================== Línea de tiempo ===================== --}}
    @forelse ($porDia as $dia => $eventos)
        @php $fecha = \Carbon\Carbon::parse($dia); @endphp
        <div class="lt-dia">
            <div class="lt-dia-head">
                <b>{{ $fecha->isToday() ? 'Hoy' : ($fecha->isYesterday() ? 'Ayer' : ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM'))) }}</b>
                <span>{{ $fecha->format('d/m/Y') }} · {{ $eventos->count() }} acción(es)</span>
            </div>
            <div class="card lt-lista">
                @foreach ($eventos as $e)
                    <div class="lt-ev">
                        <span class="lt-hora">{{ $e['cuando']->format('H:i') }}</span>
                        <span class="lt-ico {{ $tonos[$e['modulo']] ?? 'gris' }}" title="{{ $e['modulo_label'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $iconos[$e['modulo']] ?? '' !!}</svg>
                        </span>
                        <span class="lt-avatar" title="{{ $e['usuario'] }}">{{ $iniciales($e['usuario']) }}</span>
                        <div class="lt-txt">
                            <div class="lt-t">
                                <b>{{ $e['usuario'] }}</b>
                                @if ($e['url'])
                                    <a href="{{ $e['url'] }}">{{ $e['titulo'] }}</a>
                                @else
                                    {{ $e['titulo'] }}
                                @endif
                            </div>
                            @if ($e['detalle'])
                                <div class="lt-s">{{ $e['detalle'] }}</div>
                            @endif
                        </div>
                        <span class="lt-mod">{{ $e['modulo_label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <h3>Sin actividad en este rango</h3>
                <p>Prueba con otras fechas, otro usuario u otro módulo.</p>
            </div>
        </div>
    @endforelse

    <style>
        .lt-filtros { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
        .lt-rango { display:inline-flex; align-items:center; gap:8px; color:var(--muted); font-size:13px; }
        .lt-filtros input, .lt-filtros select { padding:9px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-family:inherit; font-size:13.5px; }
        .lt-atajos { display:flex; gap:12px; margin-left:auto; }
        .lt-atajos a { color:var(--primary); font-size:13px; font-weight:600; text-decoration:none; }
        .lt-atajos a:hover { text-decoration:underline; }

        .lt-resumen { display:grid; grid-template-columns:minmax(180px, .8fr) 1fr 1fr; gap:12px; margin-bottom:20px; }
        @media (max-width:900px) { .lt-resumen { grid-template-columns:1fr; } }
        .lt-kpi { padding:16px 18px; }
        .lt-kpi > b { display:block; font-size:30px; font-weight:800; letter-spacing:-.02em; }
        .lt-kpi > span { color:var(--muted); font-size:12.5px; }
        .lt-kpi .k { display:block; color:var(--muted); font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
        .lt-mini { display:flex; justify-content:space-between; gap:10px; font-size:13px; padding:3px 0; }
        .lt-mini b { font-weight:700; }

        .lt-dia { margin-bottom:18px; }
        .lt-dia-head { display:flex; align-items:baseline; gap:10px; margin:0 0 8px 4px; }
        .lt-dia-head b { font-size:15px; }
        .lt-dia-head span { color:var(--muted); font-size:12.5px; }
        .lt-lista { padding:4px 0; }
        .lt-ev { display:grid; grid-template-columns:48px 34px 32px minmax(0,1fr) auto; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid var(--border); }
        .lt-ev:last-child { border-bottom:0; }
        .lt-hora { color:var(--muted); font-size:12.5px; font-variant-numeric:tabular-nums; }
        .lt-ico { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; }
        .lt-ico svg { width:16px; height:16px; }
        .lt-ico.azul { background:var(--primary-soft); color:var(--primary); }
        .lt-ico.verde { background:var(--green-soft, rgba(34,197,94,.14)); color:var(--green); }
        .lt-ico.ambar { background:var(--accent-soft, rgba(245,158,11,.15)); color:var(--accent, #b45309); }
        .lt-ico.rojo { background:var(--danger-soft, rgba(239,68,68,.12)); color:var(--danger); }
        .lt-ico.gris { background:var(--surface-2); color:var(--muted); }
        .lt-avatar { width:30px; height:30px; border-radius:50%; background:var(--surface-2); color:var(--muted); font-size:11.5px; font-weight:800; display:flex; align-items:center; justify-content:center; }
        .lt-t { font-size:14px; }
        .lt-t b { font-weight:700; margin-right:4px; }
        .lt-t a { color:var(--text); text-decoration:none; }
        .lt-t a:hover { color:var(--primary); text-decoration:underline; }
        .lt-s { color:var(--muted); font-size:12.5px; margin-top:2px; overflow-wrap:anywhere; }
        .lt-mod { color:var(--muted); font-size:11.5px; white-space:nowrap; }
        @media (max-width:700px) { .lt-ev { grid-template-columns:44px 30px minmax(0,1fr); } .lt-avatar, .lt-mod { display:none; } }
    </style>
@endsection
