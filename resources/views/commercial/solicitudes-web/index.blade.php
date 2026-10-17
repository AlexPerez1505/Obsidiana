@extends('layouts.dashboard')

@section('title', 'Solicitudes web')
@section('page-title', 'Solicitudes web')
@section('page-sub', 'Lo que piden los clientes desde la tienda en línea')

@section('content')
    @php
        $filas = $solicitudes->map(fn ($s) => [
            'modelo' => $s,
            'resumen' => $s->resumen(),
        ]);

        $datos = fn (array $fila) => [
            'data-buscar' => mb_strtolower(implode(' ', array_filter([
                $fila['modelo']->folio, $fila['modelo']->nombre, $fila['modelo']->empresa,
                $fila['modelo']->telefono, $fila['modelo']->correo, $fila['modelo']->ciudad, $fila['resumen'],
            ]))),
            'data-estado' => $fila['modelo']->estadoTexto(),
            'data-tipo' => \App\Models\TiendaSolicitud::TIPOS[$fila['modelo']->tipo] ?? '',
            'data-cliente' => $fila['modelo']->customer_id ? '1' : '0',
            'data-fecha' => $fila['modelo']->created_at?->format('Y-m-d') ?? '',
        ];
    @endphp

    {{-- ===================== Métricas ===================== --}}
    <div class="sw-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $solicitudes->count() }}</div>
                <div class="stat-lbl">Solicitudes recibidas</div>
            </div>
        </div>
        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $solicitudes->where('estado', 'nueva')->count() }}</div>
                <div class="stat-lbl">Sin atender</div>
            </div>
        </div>
        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $solicitudes->where('estado', 'cotizada')->count() }}</div>
                <div class="stat-lbl">Cotizadas</div>
            </div>
        </div>
    </div>

    {{-- ===================== Búsqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por folio, nombre, teléfono, ciudad o equipo" autocomplete="off">
        </div>

        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                <div class="flt-group">
                    <h4>Estado</h4>
                    @foreach (\App\Models\TiendaSolicitud::ESTADOS as $estado)
                        <label class="flt-opt">
                            <span class="flt-opt-txt">{{ $estado['texto'] }}</span>
                            <input type="checkbox" data-f="estado" value="{{ $estado['texto'] }}">
                        </label>
                    @endforeach
                </div>

                <div class="flt-group">
                    <h4>Tipo</h4>
                    @foreach (\App\Models\TiendaSolicitud::TIPOS as $tipo)
                        <label class="flt-opt">
                            <span class="flt-opt-txt">{{ $tipo }}</span>
                            <input type="checkbox" data-f="tipo" value="{{ $tipo }}">
                        </label>
                    @endforeach
                </div>

                <div class="flt-group">
                    <h4>Cliente</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Ya está en Clientes</span>
                        <input type="checkbox" data-f="pref" value="cliente">
                    </label>
                </div>

                <div class="flt-group">
                    <h4>Fecha</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Desde">
                        <input type="date" data-f="hasta" aria-label="Hasta">
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>

        <x-ui.view-switch key="solicitudes-web" />
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" data-view-list style="overflow-x:auto; padding:0;">
        <table class="sw-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Cliente</th>
                    <th>Pide</th>
                    <th>Ciudad</th>
                    <th>Recibida</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    @php $s = $fila['modelo']; @endphp
                    <tr class="f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td class="sw-mono">{{ $s->folio }}</td>
                        <td>
                            <div class="t">{{ $s->nombre }}</div>
                            <div class="s">{{ $s->empresa ?: $s->telefono }}</div>
                        </td>
                        <td class="sw-resumen" title="{{ $fila['resumen'] }}">{{ $fila['resumen'] }}</td>
                        <td>{{ $s->ciudad ?: '—' }}</td>
                        <td class="sw-mono">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge {{ $s->estadoBadge() }}">{{ $s->estadoTexto() }}</span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a class="tbl-link" href="{{ route('commercial.solicitudes-web.show', $s) }}">Ver solicitud</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <h3>Aún no llegan solicitudes</h3>
                                <p>Cuando un cliente pida cotización desde la tienda en línea, aparecerá aquí.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @foreach ($filas as $fila)
            @php $s = $fila['modelo']; @endphp
            <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                <div class="data-card-top">
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $s->nombre }}</div>
                        <div class="s">{{ $s->folio }} · {{ $s->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <span class="badge {{ $s->estadoBadge() }}">{{ $s->estadoTexto() }}</span>
                </div>
                <dl>
                    <div><dt>Teléfono</dt><dd>{{ $s->telefono }}</dd></div>
                    <div><dt>Ciudad</dt><dd>{{ $s->ciudad ?: '—' }}</dd></div>
                    <div><dt>Pide</dt><dd>{{ $fila['resumen'] }}</dd></div>
                </dl>
                <div class="data-card-foot">
                    <a class="tbl-link" href="{{ route('commercial.solicitudes-web.show', $s) }}">Ver solicitud</a>
                </div>
            </article>
        @endforeach
    </div>

    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <h3>Ninguna solicitud coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    <style>
        .sw-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }
        .sw-stats .stat-ico svg { width:20px; height:20px; }
        .sw-table { width:100%; border-collapse:collapse; }
        .sw-mono { font-variant-numeric:tabular-nums; font-size:13px; color:var(--muted); white-space:nowrap; }
        .sw-resumen { max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:13px; }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'solicitud',
        'plural' => 'solicitudes',
        'toggleInicial' => null,
        'etiquetas' => [
            'estado' => 'Estado',
            'tipo' => 'Tipo',
            'cliente' => 'Ya está en Clientes',
        ],
    ])
@endsection
