@extends('layouts.dashboard')
@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Inventario de equipos y stock disponible')

@push('head')
    <style>
        .unidades-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:1000; padding:20px; }
        .unidades-modal { background:var(--surface); border-radius:14px; max-width:840px; width:100%; max-height:86vh; overflow-y:auto; padding:22px; }
        .unidades-modal-head { display:flex; align-items:center; justify-content:space-between; gap:12px;
                               margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid var(--border);
                               position:sticky; top:-22px; background:var(--surface); z-index:2; }
        .producto-row { cursor:pointer; }
        .producto-row:hover { background:var(--surface-2); }

        /* ===================== Una tarjeta por unidad =====================
           La evidencia de cómo llegó cada pieza se ve de entrada: sus fotos
           y su video, sin tener que abrir nada. */
        /* Vista en tarjetas */
        .prod-card { cursor:pointer; padding:0; overflow:hidden; display:flex; flex-direction:column; }
        .prod-card > :not(.prod-card-foto) { margin-left:16px; margin-right:16px; }
        .prod-card .data-card-foot { margin-bottom:14px; }
        .prod-card-foto { position:relative; aspect-ratio:4/3; background:var(--surface-2);
                          display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:13px;
                          border-bottom:1px solid var(--border); }
        .prod-card-foto img { width:100%; height:100%; object-fit:cover; display:block; }
        .prod-card-check { position:absolute; top:10px; left:10px; display:flex; padding:6px; border-radius:7px;
                           background:var(--surface); border:1px solid var(--border); cursor:pointer; }
        .prod-card-check input { width:16px; height:16px; margin:0; cursor:pointer; }

        .unidad-card { border:1px solid var(--border); border-radius:12px; padding:14px 16px; margin-bottom:12px; }
        .unidad-card:last-child { margin-bottom:0; }
        .unidad-card-head { display:flex; align-items:flex-start; gap:12px; margin-bottom:12px; }
        .unidad-serie { font-family:ui-monospace, Consolas, monospace; font-size:14.5px; font-weight:700; }
        .unidad-serie.sin { font-family:inherit; font-weight:600; color:var(--muted); }
        .unidad-meta { margin-top:3px; color:var(--muted); font-size:12.5px; line-height:1.5; }
        .unidad-meta .cod { font-family:ui-monospace, Consolas, monospace; }
        .unidad-card-head .der { margin-left:auto; display:flex; flex-direction:column; align-items:flex-end; gap:6px; flex:0 0 auto; }

        .unidad-medios { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:10px; }
        .medio { position:relative; padding:0; border:1px solid var(--border); border-radius:10px;
                 overflow:hidden; background:var(--surface-2); cursor:pointer; display:block; }
        .medio img, .medio video { width:100%; height:112px; object-fit:cover; display:block; background:#000; }
        .medio:hover { border-color:var(--primary); }
        .medio--video { cursor:default; }
        .medio--video video { object-fit:contain; }
        .medio-etiqueta { position:absolute; left:0; right:0; bottom:0; padding:3px 6px; font-size:10.5px;
                          font-weight:600; color:#fff; background:rgba(0,0,0,.6); text-align:center; }
        .unidad-sin-medios { margin:0; padding:10px 12px; border-radius:9px; background:var(--surface-2);
                             color:var(--muted); font-size:12.5px; }
        /* Está vendible, pero no en el anaquel. */
        .unidad-en-congreso { background:var(--warn-soft, rgba(217,119,6,.12)) !important;
                              color:var(--warn, #b45309) !important; border-color:transparent !important; }

        .ver-evidencia { border:none; background:transparent; padding:0;
                         color:var(--primary); font-size:11.5px; font-weight:600; cursor:pointer; white-space:nowrap; }
        .ver-evidencia:hover { text-decoration:underline; }

        /* ===================== Agrupado por tipo ===================== */
        .tipo-chips { display:flex; gap:6px; flex-wrap:wrap; margin:-6px 0 14px; }
        .tipo-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border:1px solid var(--border); border-radius:999px;
                     background:var(--surface); color:var(--text); font-size:12.5px; font-weight:600; text-decoration:none; }
        .tipo-chip span { color:var(--muted); font-weight:500; }
        .tipo-chip.on { background:var(--primary); border-color:var(--primary); color:#fff; }
        .tipo-chip.on span { color:#fff; opacity:.8; }
        .grupo-fila td { background:var(--surface-2); padding:9px 12px !important; cursor:pointer; user-select:none; }
        .grupo-cab { display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        .grupo-cab .n { font-weight:600; color:var(--muted); letter-spacing:0; text-transform:none; }
        .grupo-cab svg { width:14px; height:14px; transition:transform .18s ease; }
        .grupo-cerrado .grupo-cab svg { transform:rotate(-90deg); }
        .grupo-titulo-cards { grid-column:1 / -1; margin:8px 0 -2px; font-size:12.5px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--muted); }

        /* ===================== Paquetes con su contenido ===================== */
        .paq-sec > summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:10px; }
        .paq-sec > summary::-webkit-details-marker { display:none; }
        .paq-sec > summary h3 { margin:0; font-size:15px; font-weight:650; flex:1; }
        .paq-sec > summary .flecha { width:16px; height:16px; transition:transform .18s ease; color:var(--muted); }
        .paq-sec[open] > summary .flecha { transform:rotate(180deg); }
        .paq-grupo { margin-top:14px; }
        .paq-grupo-t { font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--muted); margin:0 0 8px; }
        .paq-lista { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:10px; }
        .paq-item { border:1px solid var(--border); border-radius:10px; background:var(--surface); }
        .paq-item > summary { list-style:none; cursor:pointer; padding:11px 13px; display:flex; align-items:center; gap:10px; }
        .paq-item > summary::-webkit-details-marker { display:none; }
        .paq-item > summary b { flex:1; min-width:0; font-size:13.5px; }
        .paq-item > summary small { color:var(--muted); font-size:12px; white-space:nowrap; }
        .paq-item ul { margin:0; padding:0 13px 12px 30px; font-size:12.5px; line-height:1.65; color:var(--muted); }
        .paq-item ul b { color:var(--text); }
        @media (prefers-reduced-motion: reduce) { .grupo-cab svg, .paq-sec > summary .flecha { transition:none; } }

    </style>
@endpush

@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <form method="GET" id="filtrosProductos" data-live="off" style="flex:1 1 100%;display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <label style="flex:1;min-width:220px;">Buscar producto
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por tipo, marca, modelo o serie..."
                       style="width:100%;padding:11px 14px;border:1px solid var(--border);border-radius:9px;background:var(--surface);color:var(--text);">
            </label>
            @include('partials.inventario-filtros-catalogo')
            @if (($filters['ubicacion'] ?? '') === 'congreso')
                <input type="hidden" name="ubicacion" value="congreso">
            @endif
            <button type="submit" class="btn">Aplicar filtros</button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost">Limpiar filtros</a>
        </form>

        {{-- Lo que está fuera del almacén, de un jalón: sigue siendo stock
             (allá se puede vender) pero no está en el anaquel. --}}
        {{--
            Sin la directiva php de una línea a propósito: se empareja con
            el cierre del bloque que hay más abajo en este mismo archivo y
            se traga todo el HTML de en medio.
        --}}
        @if ($enCongreso > 0)
            <a href="{{ ($filters['ubicacion'] ?? '') === 'congreso'
                    ? route('inventory.productos.index', array_filter(\Illuminate\Support\Arr::except($filters, ['ubicacion'])))
                    : route('inventory.productos.index', array_filter(array_merge($filters, ['ubicacion' => 'congreso']))) }}"
               class="btn {{ ($filters['ubicacion'] ?? '') === 'congreso' ? '' : 'btn--ghost' }}"
               style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;"
               title="{{ ($filters['ubicacion'] ?? '') === 'congreso' ? 'Quitar el filtro' : 'Ver solo lo que está en congresos' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                En congreso ({{ $enCongreso }})
            </a>
        @endif

        <div style="flex:1;"></div>
        <x-ui.view-switch key="productos" />
        <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"/></svg>
            Paquetes
        </a>
        {{-- Etiquetas de todas las piezas de los productos marcados, de un jalón. --}}
        <button type="button" id="btn-etiquetas" class="btn btn--ghost" disabled
                style="display:inline-flex; align-items:center; gap:7px; opacity:.5; cursor:not-allowed;"
                onclick="imprimirEtiquetas()" title="Marca productos con la casilla para imprimir sus etiquetas">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Imprimir etiquetas (<span id="contador-etiquetas">0</span>)
        </button>
        <button type="button" id="btn-agrupar-paquete" class="btn" disabled
                style="display:inline-flex; align-items:center; gap:7px; opacity:.5; cursor:not-allowed;"
                onclick="agruparEnPaquete()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agrupar en paquete (<span id="contador-seleccionados">0</span>)
        </button>
        @can('inventario.registrar')
        <a href="{{ route('inventory.movimientos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Registrar entrada
        </a>
        @endcan
    </div>

    @php
        $tipoSel = $filters['tipo'] ?? '';
        $base = array_filter(['search' => $filters['search'] ?? null, 'ubicacion' => $filters['ubicacion'] ?? null]);
    @endphp
    @if ($porTipo->count() > 1 || $tipoSel !== '')
        <nav class="tipo-chips" aria-label="Filtrar por tipo">
            <a href="{{ route('inventory.productos.index', $base) }}" class="tipo-chip {{ $tipoSel === '' ? 'on' : '' }}">Todos <span>{{ $porTipo->sum() }}</span></a>
            @foreach ($porTipo as $t => $n)
                <a href="{{ route('inventory.productos.index', $base + ['tipo' => $t]) }}" class="tipo-chip {{ $tipoSel === $t ? 'on' : '' }}">{{ $t }} <span>{{ $n }}</span></a>
            @endforeach
        </nav>
    @endif

    {{-- Paquetes agrupados por el tipo que más traen; cada uno se abre y
         dice qué contiene. --}}
    @if ($paquetesPorTipo->isNotEmpty())
        <x-ui.card style="margin-bottom:16px;">
            <details class="paq-sec" @if (! request()->has('page')) open @endif>
                <summary>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--muted);"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"/></svg>
                    <h3>Paquetes <span class="muted" style="font-weight:500; font-size:13px;">{{ $paquetesPorTipo->flatten()->count() }}</span></h3>
                    <a href="{{ route('inventory.paquetes.index') }}" class="tbl-link" onclick="event.stopPropagation();" style="font-size:13px;">Administrar</a>
                    <svg class="flecha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                @foreach ($paquetesPorTipo as $tipoPaq => $lista)
                    <div class="paq-grupo">
                        <p class="paq-grupo-t">{{ $tipoPaq }} · {{ $lista->count() }}</p>
                        <div class="paq-lista">
                            @foreach ($lista as $paq)
                                @php $contenido = $paq->contenido(); @endphp
                                <details class="paq-item">
                                    <summary>
                                        <b>{{ $paq->nombre }}</b>
                                        <small>{{ count($contenido) }} {{ count($contenido) === 1 ? 'artículo' : 'artículos' }}</small>
                                        @if (! $paq->activo)<span class="badge" style="font-size:11px;">Inactivo</span>@endif
                                    </summary>
                                    @if (empty($contenido))
                                        <p class="muted" style="margin:0; padding:0 13px 12px; font-size:12.5px;">Sin productos.</p>
                                    @else
                                        <ul>
                                            @foreach ($contenido as $l)
                                                <li><b>{{ $l['cantidad'] }}×</b> {{ $l['nombre'] }}{{ $l['marca'] ? ' · '.$l['marca'] : '' }}{{ $l['modelo'] ? ' · '.$l['modelo'] : '' }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </details>
        </x-ui.card>
    @endif

    <x-ui.card>
        <div style="overflow-x:auto;" data-view-list>
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="sel-todos" aria-label="Marcar todos los productos de la página" style="width:16px; height:16px; cursor:pointer;" onclick="event.stopPropagation()"></th>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Modelo / Marca</th>
                        {{-- El precio es dato de administración: la columna
                             no se dibuja para quien no puede verlo. --}}
                        @if (\App\Support\PrecioVisible::para())
                            <th>Precio de venta</th>
                        @endif
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php $tipoPrevio = null; @endphp
                    @forelse($productos as $producto)
                        @php $tipoFila = $producto->tipo_equipo ?: 'Sin tipo'; @endphp
                        @if ($tipoFila !== $tipoPrevio)
                            @php $tipoPrevio = $tipoFila; @endphp
                            <tr class="grupo-fila" data-grupo-toggle="{{ $tipoFila }}" tabindex="0" aria-expanded="true">
                                <td colspan="8">
                                    <span class="grupo-cab">
                                        <input type="checkbox" data-sel-grupo="{{ $tipoFila }}" aria-label="Marcar todo {{ $tipoFila }}"
                                               style="width:15px; height:15px; cursor:pointer; margin:0 2px 0 0;" onclick="event.stopPropagation()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="6 9 12 15 18 9"/></svg>
                                        {{ $tipoFila }} <span class="n">{{ $porTipo[$tipoFila] ?? '' }} {{ ($porTipo[$tipoFila] ?? 0) === 1 ? 'producto' : 'productos' }}</span>
                                    </span>
                                </td>
                            </tr>
                        @endif
                        <tr class="producto-row" data-grupo="{{ $tipoFila }}" title="Ver unidades disponibles"
                            onclick="if (!event.target.closest('.row-menu') && event.target.tagName !== 'INPUT') { document.getElementById('unidades-modal-{{ $producto->id }}').style.display='flex'; }">
                            <td onclick="event.stopPropagation();">
                                <input type="checkbox" class="producto-checkbox" value="{{ $producto->id }}" onchange="actualizarSeleccionPaquete()" style="width:16px; height:16px; cursor:pointer;">
                            </td>
                            <td>
                                @if($producto->imagen_path)
                                    <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->tipo_equipo }}" style="width:50px; height:50px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">
                                @else
                                    <div style="width:50px; height:50px; background:var(--surface); border:1px solid var(--border); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:20px;">—</div>
                                @endif
                            </td>
                            {{-- Primero el nombre del producto (su subtipo) y abajo el tipo;
                                 primero el modelo y abajo la marca. --}}
                            <td>
                                <div style="font-weight:700;">
                                    {{ $producto->nombreVisible() }}
                                    @include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'etiqueta'])
                                </div>
                                @if ($producto->subtipo && $producto->tipo_equipo)
                                    <div class="muted" style="font-size:12.5px;">{{ $producto->tipo_equipo }}</div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:600;">{{ $producto->modelo ?: '—' }}</div>
                                @if ($producto->marca)
                                    <div class="muted" style="font-size:12.5px;">{{ $producto->marca }}</div>
                                @endif
                                @if ($producto->referencia)
                                    <div class="muted" style="font-size:12.5px;">Ref. {{ $producto->referencia }}</div>
                                @endif
                            </td>
                            @if (\App\Support\PrecioVisible::para())
                                <td>{{ \App\Support\PrecioVisible::texto($producto) }}</td>
                            @endif
                            <td>
                                <span class="badge {{ $producto->stock > 0 ? 'badge--ok' : 'badge--danger' }}">
                                    {{ $producto->stock }} u.
                                </span>
                            </td>
                            <td>
                                {{-- Menú compartido: se posiciona en fijo, así que
                                     no lo recorta el overflow de la tarjeta. El
                                     de antes se cortaba cuando había una sola
                                     fila y no se alcanzaba a ver. --}}
                                <div class="row-menu" data-row-menu>
                                    <button type="button" class="row-menu-btn" data-row-menu-toggle
                                            aria-haspopup="true" aria-expanded="false"
                                            aria-label="Acciones de {{ $producto->tipo_equipo }}">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                    </button>

                                    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                        <a href="{{ route('inventory.productos.show', $producto) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver ficha
                                        </a>
                                        {{-- Editar: con permiso, o si es suyo y está dentro de
                                             sus primeras horas (el producto o alguna pieza). --}}
                                        @if (\App\Support\EdicionPropia::puedeEditarProducto(auth()->user(), $producto)
                                             || $producto->serialesDisponibles->contains(fn ($s) => \App\Support\EdicionPropia::puedeEditarPieza(auth()->user(), $s)))
                                            <a href="{{ route('inventory.productos.edit', $producto) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                Editar
                                            </a>
                                        @endif
                                        @include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'menu'])
                                        @if (\App\Support\EdicionPropia::puedeEliminarProducto(auth()->user(), $producto))
                                            <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}"
                                                  data-confirm="¿Eliminar este producto?" style="margin:0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="es-danger" role="menuitem">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay productos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===================== Vista en tarjetas =====================
             Misma información que la tabla. Tocar la tarjeta abre las
             unidades disponibles, igual que la fila. --}}
        <div class="data-cards" data-view-cards style="display:none;">
            @php $tipoPrevioC = null; @endphp
            @forelse ($productos as $producto)
                @php $tipoCard = $producto->tipo_equipo ?: 'Sin tipo'; @endphp
                @if ($tipoCard !== $tipoPrevioC)
                    @php $tipoPrevioC = $tipoCard; @endphp
                    <h3 class="grupo-titulo-cards">{{ $tipoCard }} · {{ $porTipo[$tipoCard] ?? '' }}</h3>
                @endif
                <article class="data-card prod-card" title="Ver unidades disponibles"
                         onclick="if (!event.target.closest('a, input, label')) { document.getElementById('unidades-modal-{{ $producto->id }}').style.display='flex'; }">
                    <div class="prod-card-foto">
                        @if ($producto->imagen_path)
                            <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->nombreVisible() }}" loading="lazy">
                        @else
                            <span>Sin foto</span>
                        @endif
                        <label class="prod-card-check" title="Agrupar en paquete" onclick="event.stopPropagation();">
                            <input type="checkbox" value="{{ $producto->id }}" onchange="espejoPaquete(this)"
                                   aria-label="Seleccionar {{ $producto->nombreVisible() }} para un paquete">
                        </label>
                    </div>

                    <div class="data-card-top" style="padding-top:12px;">
                        <div style="min-width:0; flex:1;">
                            <div class="t">
                                {{ $producto->nombreVisible() }}
                                @include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'etiqueta'])
                            </div>
                            @if ($producto->subtipo && $producto->tipo_equipo)
                                <div class="s">{{ $producto->tipo_equipo }}</div>
                            @endif
                        </div>
                        <span class="badge {{ $producto->stock > 0 ? 'badge--ok' : 'badge--danger' }}">{{ $producto->stock }} u.</span>
                    </div>

                    <dl>
                        <div><dt>Modelo</dt><dd>{{ $producto->modelo ?: '—' }}</dd></div>
                        <div><dt>Marca</dt><dd>{{ $producto->marca ?: '—' }}</dd></div>
                        @if ($producto->referencia)
                            <div><dt>Referencia</dt><dd>{{ $producto->referencia }}</dd></div>
                        @endif
                        @if (\App\Support\PrecioVisible::para())
                            <div><dt>Precio de venta</dt><dd>{{ \App\Support\PrecioVisible::texto($producto) }}</dd></div>
                        @endif
                    </dl>

                    <div class="data-card-foot" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        @include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'boton'])
                        <a class="tbl-link" href="{{ route('inventory.productos.show', $producto) }}" style="margin-left:auto;">Ver ficha</a>
                    </div>
                </article>
            @empty
                <p class="muted" style="text-align:center; padding:32px; grid-column:1/-1;">No hay productos registrados.</p>
            @endforelse
        </div>
        @include('partials._paginacion', ['paginator' => $productos])
    </x-ui.card>

    {{-- Modales de unidades disponibles, fuera de la tabla para que el HTML sea válido --}}
    @foreach ($productos as $producto)
        <div id="unidades-modal-{{ $producto->id }}" class="unidades-modal-overlay">
            <div class="unidades-modal">
                <div class="unidades-modal-head">
                    <div>
                        <strong>{{ $producto->nombreVisible() }}{{ $producto->modelo ? ' · '.trim($producto->modelo.' '.$producto->marca) : '' }}</strong>
                        <div class="muted" style="font-size:12.5px;">Unidades disponibles ({{ $producto->serialesDisponibles->count() }})</div>
                    </div>
                    <button type="button" class="btn btn--ghost" style="padding:5px 10px;" onclick="document.getElementById('unidades-modal-{{ $producto->id }}').style.display='none'">Cerrar</button>
                </div>

                {{-- Con muchas piezas del mismo equipo, buscar la que se
                     necesita en vez de recorrerlas todas. --}}
                @if ($producto->serialesDisponibles->count() > 4)
                    <div style="display:flex; gap:10px; align-items:center; margin:0 0 12px;">
                        <input type="search" data-unidades-buscar placeholder="Buscar por serie o etiqueta…"
                               aria-label="Buscar unidad" style="flex:1; min-width:0;">
                        <span class="muted" style="font-size:12.5px; white-space:nowrap;" data-unidades-conteo></span>
                    </div>
                @endif

                @forelse ($producto->serialesDisponibles as $serial)
                    @php
                        $evidencias = $serial->evidenceUrls();
                        $video = $serial->videoUrl();

                        // Todo lo que documenta cómo llegó esta pieza, en el
                        // orden en que se ve en la galería grande.
                        $medios = collect($evidencias)->map(fn ($url) => ['tipo' => 'foto', 'url' => $url]);
                        if ($video) {
                            $medios->push(['tipo' => 'video', 'url' => $video]);
                        }

                        $nFotos = count($evidencias);
                        $etiquetaMedios = $nFotos.' '.($nFotos === 1 ? 'foto' : 'fotos').($video ? ' + video' : '');
                        $modelo = trim($producto->modelo.' '.$producto->marca) ?: $producto->nombreVisible();
                    @endphp
                    <div class="unidad-card" data-buscar="{{ mb_strtolower(trim($serial->no_serie.' '.$serial->codigo)) }}"
                         @if ($medios->isNotEmpty()) data-medios="{{ $medios->toJson() }}" @endif>
                        <div class="unidad-card-head">
                            <div style="min-width:0;">
                                <div class="unidad-serie {{ $serial->no_serie ? '' : 'sin' }}">
                                    {{ $serial->no_serie ?: 'Sin número de serie del fabricante' }}
                                </div>
                                <div class="unidad-meta">
                                    {{ $modelo }}
                                    @if ($serial->codigo)
                                        · <span class="cod">{{ $serial->codigo }}</span>
                                    @endif
                                    @if ($serial->condicion)
                                        · {{ ucfirst($serial->condicion) }}
                                    @endif
                                    @if ($serial->entrada?->movement_date)
                                        · Llegó el {{ $serial->entrada->movement_date->format('d/m/Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="der">
                                {{-- Si se la llevaron a un congreso sigue
                                     contando como stock, pero no está en el
                                     almacén: eso hay que decirlo aquí. --}}
                                @if ($serial->enCongreso())
                                    <span class="badge unidad-en-congreso" style="font-size:11px;"
                                          title="Esta pieza no está en el almacén">
                                        En congreso: {{ $serial->congress?->nombre }}
                                    </span>
                                @else
                                    <span class="badge badge--ok" style="font-size:11px;">Disponible</span>
                                @endif
                                @if ($medios->isNotEmpty())
                                    <button type="button" class="ver-evidencia" onclick="abrirGaleria(this)"
                                            title="Abrir en grande">
                                        Ver evidencia ({{ $etiquetaMedios }})
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($medios->isEmpty())
                            <p class="unidad-sin-medios">Esta unidad se registró sin evidencia de entrada.</p>
                        @else
                            <div class="unidad-medios">
                                @foreach ($evidencias as $i => $url)
                                    <button type="button" class="medio" data-indice="{{ $i }}"
                                            onclick="abrirGaleria(this)" title="Ver en grande">
                                        <img src="{{ $url }}" loading="lazy" alt="Foto {{ $i + 1 }} de cómo llegó la unidad">
                                        <span class="medio-etiqueta">Foto {{ $i + 1 }} de {{ $nFotos }}</span>
                                    </button>
                                @endforeach

                                @if ($video)
                                    {{-- El video se reproduce aquí mismo; preload
                                         metadata para no descargarlo completo. --}}
                                    <div class="medio medio--video">
                                        <video src="{{ $video }}" controls preload="metadata" playsinline></video>
                                        <span class="medio-etiqueta">Video de entrada</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="muted" style="padding:16px 0; text-align:center;">No hay unidades disponibles.</p>
                @endforelse
                <p class="muted" data-unidades-vacio hidden style="padding:16px 0; text-align:center;">Ninguna unidad coincide con la búsqueda.</p>
            </div>
        </div>
    @endforeach

    {{-- Visor de la evidencia de una pieza: compartido con el detalle de Entrada / Salida. --}}
    @include('partials.galeria')

    <script>
        function espejoPaquete(casilla) {
            document.querySelectorAll('.producto-checkbox[value="' + casilla.value + '"]').forEach(c => { c.checked = casilla.checked; });
            actualizarSeleccionPaquete();
        }

        function actualizarSeleccionPaquete() {
            const marcados = document.querySelectorAll('.producto-checkbox:checked');
            const boton = document.getElementById('btn-agrupar-paquete');

            document.getElementById('contador-seleccionados').textContent = marcados.length;

            const etq = document.getElementById('btn-etiquetas');
            document.getElementById('contador-etiquetas').textContent = marcados.length;
            etq.disabled = marcados.length === 0;
            etq.style.opacity = marcados.length ? '1' : '.5';
            etq.style.cursor = marcados.length ? 'pointer' : 'not-allowed';

            if (marcados.length > 0) {
                boton.disabled = false;
                boton.style.opacity = '1';
                boton.style.cursor = 'pointer';
            } else {
                boton.disabled = true;
                boton.style.opacity = '.5';
                boton.style.cursor = 'not-allowed';
            }
        }

        function imprimirEtiquetas() {
            const ids = [...new Set(Array.from(document.querySelectorAll('.producto-checkbox:checked')).map(c => c.value))];
            if (!ids.length) return;
            window.open(@json(route('inventory.etiquetas.lote')) + '?' + ids.map(id => 'productos[]=' + encodeURIComponent(id)).join('&'), '_blank');
        }

        function agruparEnPaquete() {
            const marcados = Array.from(document.querySelectorAll('.producto-checkbox:checked')).map(c => c.value);

            if (marcados.length === 0) return;

            const params = marcados.map(id => 'productos[]=' + encodeURIComponent(id)).join('&');
            window.location.href = @json(route('inventory.paquetes.create')) + '?' + params;
        }

        /* Tocar el encabezado de un tipo lo pliega o despliega. */
        function alternarGrupo(cab) {
            const abierto = cab.getAttribute('aria-expanded') !== 'false';
            cab.setAttribute('aria-expanded', abierto ? 'false' : 'true');
            cab.classList.toggle('grupo-cerrado', abierto);
            document.querySelectorAll('tr[data-grupo]').forEach(function (tr) {
                if (tr.dataset.grupo === cab.dataset.grupoToggle) tr.hidden = abierto;
            });
        }
        document.addEventListener('click', function (e) {
            if (e.target.matches('input')) return;
            const cab = e.target.closest('[data-grupo-toggle]');
            if (cab) alternarGrupo(cab);
        });

        /* Marcar todos / todo un tipo (para etiquetas o para un paquete). */
        document.addEventListener('change', function (e) {
            let casillas = null;
            if (e.target.id === 'sel-todos') casillas = document.querySelectorAll('tr[data-grupo] .producto-checkbox');
            if (e.target.dataset.selGrupo) casillas = document.querySelectorAll('tr[data-grupo="' + CSS.escape(e.target.dataset.selGrupo) + '"] .producto-checkbox');
            if (!casillas) return;
            casillas.forEach(c => { c.checked = e.target.checked; });
            document.querySelectorAll('.prod-card-check input').forEach(c => {
                c.checked = !!document.querySelector('.producto-checkbox[value="' + c.value + '"]:checked');
            });
            actualizarSeleccionPaquete();
        });
        document.addEventListener('keydown', function (e) {
            const cab = e.target.closest && e.target.closest('[data-grupo-toggle]');
            if (cab && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); alternarGrupo(cab); }
        });

        /* ===================== Buscar dentro de las unidades =====================
           Cien piezas del mismo modelo salen todas en el modal: el buscador
           deja a la vista solo las que coinciden con la serie o la etiqueta. */
        document.addEventListener('input', function (e) {
            const campo = e.target.closest('[data-unidades-buscar]');
            if (!campo) return;

            const modal = campo.closest('.unidades-modal');
            const q = campo.value.trim().toLowerCase();
            const tarjetas = Array.from(modal.querySelectorAll('.unidad-card'));
            let visibles = 0;

            tarjetas.forEach(function (t) {
                const coincide = !q || (t.dataset.buscar || '').includes(q);
                t.hidden = !coincide;
                if (coincide) visibles++;
            });

            const conteo = modal.querySelector('[data-unidades-conteo]');
            if (conteo) conteo.textContent = q ? visibles + ' de ' + tarjetas.length : '';

            const vacio = modal.querySelector('[data-unidades-vacio]');
            if (vacio) vacio.hidden = visibles > 0;
        });
    </script>

    @include('partials.row-menu')
@endsection
