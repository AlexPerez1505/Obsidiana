@extends('layouts.dashboard')

@section('title', 'Incidencias')
@section('page-title', 'Incidencias en ruta')
@section('page-sub', 'Lo que reportan los choferes: retrasos, fallas, clientes ausentes…')

@section('content')
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="in-tabs">
        <a href="{{ route('logistica.incidencias.index') }}" class="in-tab {{ $estado === 'abiertas' ? 'is-on' : '' }}">Abiertas <span class="in-num">{{ $abiertas }}</span></a>
        <a href="{{ route('logistica.incidencias.index', ['estado' => 'resueltas']) }}" class="in-tab {{ $estado === 'resueltas' ? 'is-on' : '' }}">Resueltas</a>
    </div>

    <div class="in-lista">
        @forelse ($incidencias as $i)
            <div class="card in-item">
                <div class="in-cab">
                    <div>
                        <span class="badge {{ in_array($i->tipo, ['accidente', 'falla_mecanica']) ? 'badge--danger' : 'badge--warn' }}">{{ $i->tipoLabel() }}</span>
                        <a href="{{ route('logistica.rutas.show', $i->ruta) }}" class="in-ruta">{{ $i->ruta->folio }}</a>
                        @if ($i->parada)<span class="in-s"> · {{ $i->parada->nombre }}</span>@endif
                    </div>
                    <span class="in-s">{{ $i->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="in-s" style="margin-top:6px;">
                    {{ $i->ruta->vehicle?->plate_number ?? 'Sin vehículo' }} · {{ $i->usuario?->name ?? 'Sin usuario' }}
                    @if ($i->lat) · <a href="https://www.google.com/maps?q={{ $i->lat }},{{ $i->lng }}" target="_blank" rel="noopener">ver ubicación</a>@endif
                </div>
                @if ($i->descripcion)<p class="in-desc">{{ $i->descripcion }}</p>@endif
                @if ($i->fotoUrl())<a href="{{ $i->fotoUrl() }}" target="_blank"><img src="{{ $i->fotoUrl() }}" class="in-foto" alt="Foto de la incidencia" loading="lazy"></a>@endif

                @if ($i->resuelta_en)
                    <div class="in-ok"><x-ui.icon name="check" :size="15" /> Resuelta {{ $i->resuelta_en->format('d/m/Y H:i') }}@if ($i->resolucion) — {{ $i->resolucion }}@endif</div>
                @else
                    <form method="POST" action="{{ route('logistica.incidencias.resolver', $i) }}" class="in-form">@csrf
                        <input type="text" name="resolucion" placeholder="Qué se hizo (opcional)">
                        <button class="btn" type="submit">Marcar resuelta</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="card"><div class="empty-state"><h3>{{ $estado === 'abiertas' ? 'Sin incidencias abiertas' : 'Nada resuelto todavía' }}</h3><p>Los reportes del chofer aparecen aquí al instante.</p></div></div>
        @endforelse
    </div>

    <div style="margin-top:14px;">{{ $incidencias->links() }}</div>

    <style>
        .in-tabs { display:inline-flex; border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:16px; background:var(--surface); }
        .in-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; }
        .in-tab + .in-tab { border-left:1px solid var(--border); }
        .in-tab.is-on { background:var(--primary); color:#fff; }
        .in-num { padding:0 6px; border-radius:9px; background:rgba(255,255,255,.25); font-size:11px; }
        .in-lista { display:flex; flex-direction:column; gap:12px; }
        .in-item { padding:16px; }
        .in-cab { display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap; }
        .in-ruta { margin-left:8px; font-weight:700; }
        .in-s { font-size:12.5px; color:var(--muted); }
        .in-desc { margin:10px 0 0; font-size:14px; }
        .in-foto { margin-top:10px; max-height:160px; border-radius:8px; border:1px solid var(--border); }
        .in-ok { margin-top:12px; color:var(--green); font-weight:600; font-size:13px; }
        .in-form { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
        .in-form input { flex:1; min-width:200px; }
    </style>
@endsection
