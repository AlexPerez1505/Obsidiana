@extends('layouts.dashboard')

@section('title', 'Serie '.$serie)
@section('page-title', 'Historial por número de serie')
@section('page-sub', $serie)

@section('content')
    @include('structure.garantias._estilos')

    @php
        $gasto = $reclamaciones->flatMap->costos->where('estado', 'aprobado');
        $actual = $garantias->where('estado', 'activa')->sortByDesc('vence')->first();
    @endphp

    <x-ui.page-header :title="'Serie '.$serie" :back="route('garantias.tablero')">
        @if ($actual)
            <span class="badge {{ $actual->semaforoBadge() }}">{{ $actual->semaforoTexto() }}</span>
        @endif
        @can('garantias.gestionar')
            <a class="btn" href="{{ route('garantias.reclamaciones.create', $actual ? ['garantia' => $actual->id] : []) }}">Registrar reclamación</a>
        @endcan
    </x-ui.page-header>

    @if ($garantias->isEmpty() && $reclamaciones->isEmpty())
        <div class="card"><div class="empty-state"><h3>No hay registros con esta serie</h3><p>Revisa que esté bien escrita o búscala por equipo o cliente.</p>
            <a class="btn" href="{{ route('garantias.index', ['q' => $serie]) }}">Buscar parecidas</a></div></div>
    @else
        <div class="gt-kpis">
            <div class="card stat"><div><div class="stat-num">{{ $garantias->count() }}</div><div class="stat-lbl">Garantías con esta serie</div></div></div>
            <div class="card stat"><div><div class="stat-num">{{ $reclamaciones->count() }}</div><div class="stat-lbl">Veces que ha fallado</div></div></div>
            <div class="card stat"><div><div class="stat-num">${{ number_format($gasto->sum('monto'), 0) }}</div><div class="stat-lbl">Gastado en reparaciones<small>{{ $gasto->sum('recuperado') > 0 ? '$'.number_format($gasto->sum('recuperado'), 0).' recuperado' : '' }}</small></div></div></div>
            <div class="card stat"><div>
                <div class="stat-num">{{ $actual ? $actual->vence->format('d/m/Y') : '—' }}</div>
                <div class="stat-lbl">{{ $actual && $actual->vigente() ? 'Cubierto hasta' : 'Sin cobertura vigente' }}</div>
            </div></div>
        </div>

        @if ($reclamaciones->count() >= 3)
            <div class="gt-aviso rojo"><div><b>Equipo reincidente</b>Ha fallado {{ $reclamaciones->count() }} veces. Revisa si conviene seguir reparándolo o sustituirlo.</div></div>
        @endif

        <div class="gt-2b">
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Ventas y garantías</h3></div>
                @foreach ($garantias as $g)
                    <a class="gt-fila" href="{{ route('garantias.show', $g) }}">
                        <div>
                            <div class="t">{{ $g->folio }} · {{ $g->equipoNombre() }}</div>
                            <div class="s">{{ $g->clienteNombre() }} · {{ $g->venta?->folio ?? 'alta manual' }} · {{ $g->inicio->format('d/m/Y') }} – {{ $g->vence->format('d/m/Y') }}</div>
                        </div>
                        <div class="der"><span class="badge {{ $g->semaforoBadge() }}">{{ $g->semaforoTexto() }}</span></div>
                    </a>
                @endforeach
                @foreach ($sustituciones as $s)
                    <div class="gt-fila">
                        <div>
                            <div class="t">{{ $s->tipoLabel() }}</div>
                            <div class="s">{{ $s->no_serie_anterior ?: '—' }} → {{ $s->no_serie_entregado ?: '—' }} · {{ $s->entregado_en->format('d/m/Y') }} · {{ $s->reclamacion?->folio }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Fallas y reparaciones</h3></div>
                @forelse ($reclamaciones as $r)
                    <a class="gt-fila" href="{{ route('garantias.reclamaciones.show', $r) }}">
                        <div>
                            <div class="t">{{ $r->folio }} · {{ $r->recibida_en->format('d/m/Y') }}</div>
                            <div class="s">{{ $r->causa ?: mb_strimwidth($r->sintomas, 0, 100, '…') }}</div>
                        </div>
                        <div class="der">
                            @if ($r->costoTotal() > 0)<span class="gt-s gt-num">${{ number_format($r->costoTotal(), 0) }}</span>@endif
                            <span class="badge {{ $r->dictamenBadge() }}">{{ $r->dictamenLabel() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="gt-vacio">Sin fallas registradas.</div>
                @endforelse
            </div>
        </div>
    @endif
@endsection
