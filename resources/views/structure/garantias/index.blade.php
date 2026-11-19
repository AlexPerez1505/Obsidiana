@extends('layouts.dashboard')

@section('title', 'Garantías')
@section('page-title', 'Garantías')
@section('page-sub', 'Un expediente por equipo o componente vendido')

@section('content')
    @include('structure.garantias._estilos')

    @php
        $semaforo = $filtros['semaforo'] ?? '';
        $url = fn (array $cambio) => route('garantias.index', array_filter(array_merge($filtros, $cambio), fn ($v) => $v !== null && $v !== ''));
        $pestanas = [
            '' => ['Todas', null],
            'vigente' => ['Vigentes', null],
            'por_vencer' => ['Próximas a vencer', $conteos['por_vencer']],
            'vencida' => ['Vencidas', $conteos['vencidas']],
            'reclamacion' => ['Con reclamación', null],
            'en_reparacion' => ['En reparación', null],
            'anulada' => ['Anuladas', null],
        ];
    @endphp

    <div class="content-actions">
        @can('garantias.configurar')
            <form method="POST" action="{{ route('garantias.sincronizar') }}" data-confirm="Se revisan todas las ventas: se crean las garantías que falten y se actualizan fechas de entrega. No se borra nada." data-confirm-title="Actualizar desde ventas" data-confirm-ok="Actualizar">
                @csrf
                <button type="submit" class="btn btn--ghost">Actualizar desde ventas</button>
            </form>
        @endcan
        @can('garantias.gestionar')
            <a href="{{ route('garantias.create') }}" class="btn">Alta manual</a>
        @endcan
    </div>

    @if ($cliente)
        <div class="gt-aviso azul">
            <div>Garantías de <b style="display:inline;">{{ trim($cliente->nombre.' '.$cliente->apellido) }}</b> ·
                <a class="tbl-link" href="{{ $url(['cliente' => null]) }}">Ver de todos los clientes</a></div>
        </div>
    @endif

    <div class="gt-tabs">
        @foreach ($pestanas as $clave => [$texto, $n])
            <a href="{{ $url(['semaforo' => $clave, 'page' => null]) }}" class="gt-tab {{ $semaforo === $clave ? 'on' : '' }}">
                {{ $texto }} @if ($n)<span class="n">{{ $n }}</span>@endif
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('garantias.index') }}" class="f-toolbar">
        @foreach (['semaforo', 'cliente'] as $k)
            @if (! empty($filtros[$k]))<input type="hidden" name="{{ $k }}" value="{{ $filtros[$k] }}">@endif
        @endforeach
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Serie, folio, equipo, cliente o venta" autocomplete="off">
        </div>
        <select name="tipo" onchange="this.form.submit()" style="width:auto;">
            <option value="">Todos los tipos</option>
            @foreach (\App\Models\Garantia::TIPOS as $k => $t)
                <option value="{{ $k }}" @selected(($filtros['tipo'] ?? '') === $k)>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn--ghost">Buscar</button>
    </form>

    <div class="card" style="padding:0;">
        <div class="tbl-scroll">
            <table class="gt-tabla">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Serie</th>
                        <th>Cliente</th>
                        <th>Cobertura</th>
                        <th>Vence</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $grupoActual = null; @endphp
                    @forelse ($garantias as $g)
                        @php $claveGrupo = $g->grupo ? $g->venta_item_id.'|'.$g->grupo : null; @endphp
                        @if ($claveGrupo && $claveGrupo !== $grupoActual)
                            <tr class="gt-grupo"><td colspan="7">{{ $g->grupo }} · {{ $g->venta?->folio }}</td></tr>
                        @endif
                        @php $grupoActual = $claveGrupo; @endphp
                        <tr class="{{ $g->anulada() ? 'anulada' : '' }}">
                            <td>
                                <div class="t" style="font-weight:600;">{{ $g->equipoNombre() }}</div>
                                <div class="s">{{ $g->folio }}{{ $g->venta ? ' · '.$g->venta->folio : ' · alta manual' }} · {{ $g->condicionLabel() }}</div>
                            </td>
                            <td class="gt-mono">{{ $g->no_serie ?: ($g->codigo_interno ?: '—') }}</td>
                            <td>{{ $g->clienteNombre() }}</td>
                            <td>
                                <div>{{ $g->tipoLabel() }}</div>
                                <div class="s">{{ $g->meses }} meses{{ $g->fecha_entrega ? '' : ' · entrega por confirmar' }}</div>
                            </td>
                            <td class="gt-num" style="white-space:nowrap;">{{ $g->vence->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $g->semaforoBadge() }}">{{ $g->semaforoTexto() }}</span></td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a class="tbl-link" href="{{ route('garantias.show', $g) }}">Abrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                                    <h3>No hay garantías que mostrar</h3>
                                    <p>Las garantías se generan solas con cada venta que lleva meses de garantía.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:14px;">{{ $garantias->links() }}</div>
@endsection
