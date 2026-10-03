@extends('layouts.dashboard')

@section('title', 'Mis rutas')
@section('page-title', 'Mis rutas')
@section('page-sub', 'Las rutas que tienes asignadas')

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="ch-lista">
        @forelse ($rutas as $r)
            @php $av = $r->avance(); @endphp
            <a href="{{ route('logistica.chofer.show', $r) }}" class="card ch-ruta {{ $r->activa() ? 'is-activa' : '' }}">
                <div class="ch-fila">
                    <div>
                        <div class="ch-t">{{ $r->folio }}</div>
                        <div class="ch-s">{{ $r->fecha_programada?->translatedFormat('l d \d\e F') ?? 'Sin fecha' }}@if ($r->hora_salida) · {{ substr($r->hora_salida, 0, 5) }}@endif</div>
                    </div>
                    <span class="badge {{ $r->estadoTono() }}">{{ $r->estadoLabel() }}</span>
                </div>
                <div class="ch-s" style="margin-top:8px;">
                    {{ $r->vehicle ? $r->vehicle->plate_number.' · '.trim($r->vehicle->brand.' '.$r->vehicle->model) : 'Sin vehículo' }}
                </div>
                <div class="ch-riel"><span style="width:{{ $av['pct'] }}%"></span></div>
                <div class="ch-s">{{ $av['hechas'] }} de {{ $av['total'] }} paradas</div>
                <div class="ch-cta">{{ $r->activa() ? 'Continuar ruta' : 'Ver ruta' }} <x-ui.icon name="arrow-right" :size="14" /></div>
            </a>
        @empty
            <div class="card">
                <div class="empty-state">
                    <h3>No tienes rutas asignadas</h3>
                    <p>Cuando la oficina te asigne una ruta programada aparecerá aquí.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($recientes->count())
        <h3 style="margin:26px 0 10px; font-size:14px; color:var(--muted);">Terminadas recientemente</h3>
        <div class="card" style="padding:0;">
            @foreach ($recientes as $r)
                <div class="ch-rec"><span>{{ $r->folio }}</span><span class="ch-s">{{ $r->finalizada_en?->format('d/m/Y H:i') }}</span></div>
            @endforeach
        </div>
    @endif

    <style>
        .ch-lista { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:14px; }
        .ch-ruta { display:block; padding:16px; color:inherit; text-decoration:none; }
        .ch-ruta.is-activa { border-color:var(--muted); }
        .ch-fila { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
        .ch-t { font-weight:700; font-size:16px; }
        .ch-s { font-size:12.5px; color:var(--muted); margin-top:2px; }
        .ch-riel { height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; margin-top:10px; }
        .ch-riel span { display:block; height:100%; background:var(--green); }
        .ch-cta { margin-top:12px; color:var(--primary); font-weight:700; font-size:14px; }
        .ch-rec { display:flex; justify-content:space-between; padding:11px 16px; border-bottom:1px solid var(--border); font-size:13.5px; }
        .ch-rec:last-child { border-bottom:0; }
    </style>
@endsection
