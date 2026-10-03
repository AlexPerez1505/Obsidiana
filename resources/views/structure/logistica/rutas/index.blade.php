@extends('layouts.dashboard')

@section('title', 'Rutas')
@section('page-title', 'Rutas de reparto')
@section('page-sub', 'Planea el recorrido, asigna chofer y vehículo y sigue cada entrega')

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="rt-barra">
        <div class="rt-tabs">
            @foreach (['abiertas' => 'Abiertas', 'en_ruta' => 'En ruta', 'finalizada' => 'Finalizadas'] as $valor => $texto)
                <a href="{{ route('logistica.rutas.index', ['estado' => $valor]) }}" class="rt-tab {{ $estado === $valor ? 'is-on' : '' }}">
                    {{ $texto }} <span class="rt-num">{{ $conteos[$valor] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" class="rt-buscar">
            <input type="hidden" name="estado" value="{{ $estado }}">
            <input type="search" name="q" value="{{ $busqueda }}" placeholder="Folio o destino…">
        </form>

        @can('rutas.crear')
            <a href="{{ route('logistica.rutas.create', ['listas' => 1]) }}" class="btn btn--ghost" title="Una parada por cada orden de salida lista que aún no va en una ruta">Armar con órdenes listas</a>
            <a href="{{ route('logistica.rutas.create') }}" class="btn">+ Nueva ruta</a>
        @endcan
    </div>

    <div class="card" style="padding:0; overflow-x:auto;">
        <table class="rt-tabla">
            <thead>
                <tr>
                    <th>Ruta</th>
                    <th>Vehículo / chofer</th>
                    <th>Paradas</th>
                    <th>Recorrido</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rutas as $r)
                    @php $av = $r->avance(); @endphp
                    <tr>
                        <td>
                            <div class="rt-id">
                                <div class="t">{{ $r->folio }}</div>
                                <div class="s">{{ $r->fecha_programada?->format('d/m/Y') ?? 'Sin fecha' }}@if ($r->hora_salida) · {{ substr($r->hora_salida, 0, 5) }}@endif</div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $r->vehicle ? $r->vehicle->plate_number.' · '.trim($r->vehicle->brand.' '.$r->vehicle->model) : 'Sin vehículo' }}</div>
                            <div class="rt-sub">{{ $r->chofer?->name ?? 'Sin chofer' }}</div>
                        </td>
                        <td style="min-width:150px;">
                            <div class="rt-riel"><span style="width:{{ $av['pct'] }}%"></span></div>
                            <div class="rt-riel-txt">{{ $av['hechas'] }}/{{ $av['total'] }} paradas</div>
                        </td>
                        <td class="rt-sub">
                            @if ($r->distancia_m !== null)
                                {{ $r->distanciaKm() }} km · {{ $r->duracionTexto() }}
                            @else
                                Sin optimizar
                            @endif
                        </td>
                        <td><span class="badge {{ $r->estadoTono() }}">{{ $r->estadoLabel() }}</span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="{{ route('logistica.rutas.show', $r) }}" class="btn btn--ghost" style="padding:6px 12px;">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                </span>
                                <h3>Sin rutas</h3>
                                <p>Crea una ruta con varias paradas y el sistema calcula en qué orden conviene visitarlas.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $rutas->links() }}</div>

    <style>
        .rt-barra { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-bottom:16px; }
        .rt-buscar { margin-left:auto; }
        .rt-buscar input { min-width:220px; }
        .rt-tabs { display:inline-flex; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--surface); }
        .rt-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; }
        .rt-tab + .rt-tab { border-left:1px solid var(--border); }
        .rt-tab:hover { background:var(--surface-2); color:var(--text); }
        .rt-tab.is-on { background:var(--primary); color:#fff; }
        .rt-num { padding:0 6px; border-radius:9px; background:rgba(0,0,0,.08); font-size:11px; }
        .rt-tab.is-on .rt-num { background:rgba(255,255,255,.25); }
        .rt-tabla { width:100%; border-collapse:collapse; font-size:13.5px; }
        .rt-tabla th { padding:11px 14px; background:var(--surface-2); color:var(--muted); font-size:11.5px; font-weight:600; letter-spacing:.02em; text-transform:uppercase; text-align:left; }
        .rt-tabla td { padding:12px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
        .rt-tabla tr:last-child td { border-bottom:0; }
        .rt-id .t { font-weight:600; }
        .rt-id .s, .rt-sub { margin-top:1px; color:var(--muted); font-size:12.5px; }
        .rt-riel { height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
        .rt-riel span { display:block; height:100%; background:var(--green); }
        .rt-riel-txt { margin-top:3px; color:var(--muted); font-size:11.5px; }
    </style>
@endsection
