@extends('layouts.dashboard')

@section('title', 'Órdenes de salida')
@section('page-title', 'Órdenes de salida')
@section('page-sub', 'Lo que hay que preparar, emplayar y firmar para que salga cada venta')

@section('content')
    @if (session('status'))
        <x-ui.alert type="success" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Pestañas por estado --}}
    <div class="os-tabs">
        @foreach (['abiertas' => 'Por preparar', 'lista' => 'Listas para salir', 'entregada' => 'Entregadas'] as $valor => $texto)
            <a href="{{ route('inventory.salidas.index', ['estado' => $valor]) }}" class="os-tab {{ $estado === $valor ? 'is-on' : '' }}">
                {{ $texto }} <span class="os-num">{{ $conteos[$valor] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="card" style="padding:0; overflow-x:auto;">
        <table class="os-tabla">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Avance</th>
                    <th>Estado</th>
                    <th>Asesor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ordenes as $o)
                    @php $avance = $o->avance(); @endphp
                    <tr>
                        <td>
                            <div class="os-id">
                                <div class="t">{{ $o->folio }}</div>
                                <div class="s">Venta {{ $o->venta?->folio }} · {{ $o->created_at?->format('d/m/Y') }}</div>
                            </div>
                        </td>
                        <td>{{ trim(($o->venta?->customer?->nombre ?? '').' '.($o->venta?->customer?->apellido ?? '')) ?: 'Sin cliente' }}</td>
                        <td style="max-width:260px;">
                            @php $primero = $o->items->first(); @endphp
                            {{ $primero?->nombre ?? '—' }}
                            @if ($o->items->count() > 1)
                                <span class="os-mas">+{{ $o->items->count() - 1 }} más</span>
                            @endif
                        </td>
                        <td style="min-width:140px;">
                            <div class="os-riel" title="{{ $avance['hechos'] }} de {{ $avance['total'] }} pasos">
                                <span style="width:{{ $avance['pct'] }}%"></span>
                            </div>
                            <div class="os-riel-txt">{{ $avance['hechos'] }}/{{ $avance['total'] }} pasos</div>
                        </td>
                        <td><span class="badge {{ $o->estadoTono() }}">{{ $o->estadoLabel() }}</span></td>
                        <td style="color:var(--muted);">{{ $o->venta?->seller?->name ?? '—' }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="{{ route('inventory.salidas.pdf', $o) }}" target="_blank" class="btn btn--ghost" style="padding:6px 10px;" title="Hoja de salida en PDF">PDF</a>
                            <a href="{{ route('inventory.salidas.show', $o) }}" class="btn btn--ghost" style="padding:6px 12px;">
                                {{ $o->cerrada() ? 'Ver' : 'Preparar' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                </span>
                                <h3>Nada por aquí</h3>
                                <p>Cuando se registre una venta con equipo, su orden de salida aparece en esta lista.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $ordenes->links() }}</div>

    <style>
        .os-tabs { display:inline-flex; border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:16px; background:var(--surface); }
        .os-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; }
        .os-tab + .os-tab { border-left:1px solid var(--border); }
        .os-tab:hover { background:var(--surface-2); color:var(--text); }
        .os-tab.is-on { background:var(--primary); color:#fff; }
        .os-num { padding:0 6px; border-radius:9px; background:rgba(0,0,0,.08); font-size:11px; }
        .os-tab.is-on .os-num { background:rgba(255,255,255,.25); }

        .os-tabla { width:100%; border-collapse:collapse; font-size:13.5px; }
        .os-tabla th { padding:11px 14px; background:var(--surface-2); color:var(--muted); font-size:11.5px; font-weight:600; letter-spacing:.02em; text-transform:uppercase; text-align:left; }
        .os-tabla td { padding:12px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
        .os-tabla tr:last-child td { border-bottom:0; }
        .os-id .t { font-weight:600; }
        .os-id .s { margin-top:1px; color:var(--muted); font-size:12px; }
        .os-mas { margin-left:6px; color:var(--muted); font-size:12px; }
        .os-riel { height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
        .os-riel span { display:block; height:100%; background:var(--green); }
        .os-riel-txt { margin-top:3px; color:var(--muted); font-size:11.5px; }
    </style>
@endsection
