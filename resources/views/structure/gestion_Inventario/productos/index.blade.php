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

        .galeria-overlay { position:fixed; inset:0; background:rgba(0,0,0,.82); display:none;
                           align-items:center; justify-content:center; z-index:1100; padding:20px; }
        .galeria-caja { position:relative; max-width:900px; width:100%; text-align:center; }
        .galeria-medio img, .galeria-medio video { max-width:100%; max-height:74vh; border-radius:10px; }
        .galeria-cerrar { position:absolute; top:-38px; right:0; border:none; background:transparent;
                          color:#fff; font-size:28px; line-height:1; cursor:pointer; }
        .galeria-nav { display:flex; align-items:center; justify-content:center; gap:18px; margin-top:14px; }
        .galeria-nav button { width:38px; height:38px; border-radius:50%; border:1px solid rgba(255,255,255,.35);
                              background:rgba(255,255,255,.1); color:#fff; font-size:20px; cursor:pointer; }
        .galeria-nav button:hover { background:rgba(255,255,255,.22); }
        .galeria-nav span { color:#fff; font-size:13px; }
    </style>
@endpush

@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <form method="GET" style="flex:1; min-width:220px; max-width:380px;">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por tipo, marca, modelo o serie..."
                   style="width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:9px; font-size:14.5px; background:var(--surface); color:var(--text);">
            @if (($filters['ubicacion'] ?? '') === 'congreso')
                <input type="hidden" name="ubicacion" value="congreso">
            @endif
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
                    ? route('inventory.productos.index', array_filter(['search' => $filters['search'] ?? null]))
                    : route('inventory.productos.index', array_filter(['search' => $filters['search'] ?? null, 'ubicacion' => 'congreso'])) }}"
               class="btn {{ ($filters['ubicacion'] ?? '') === 'congreso' ? '' : 'btn--ghost' }}"
               style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;"
               title="{{ ($filters['ubicacion'] ?? '') === 'congreso' ? 'Quitar el filtro' : 'Ver solo lo que está en congresos' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                En congreso ({{ $enCongreso }})
            </a>
        @endif

        <div style="flex:1;"></div>
        <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"/></svg>
            Paquetes
        </a>
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

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Imagen</th>
                        <th>Equipo</th>
                        <th>Marca / Modelo</th>
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
                    @forelse($productos as $producto)
                        <tr class="producto-row" title="Ver unidades disponibles"
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
                            <td>
                                <div style="font-weight:700;">{{ $producto->tipo_equipo }}</div>
                                @if($producto->subtipo)
                                    <div class="muted" style="font-size:12.5px;">{{ $producto->subtipo }}</div>
                                @endif
                            </td>
                            <td>{{ $producto->marca ?: '—' }} {{ $producto->modelo }}</td>
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
                                        <a href="{{ route('inventory.productos.edit', $producto) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}"
                                              data-confirm="¿Eliminar este producto?" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="es-danger" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
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
        @include('partials._paginacion', ['paginator' => $productos])
    </x-ui.card>

    {{-- Modales de unidades disponibles, fuera de la tabla para que el HTML sea válido --}}
    @foreach ($productos as $producto)
        <div id="unidades-modal-{{ $producto->id }}" class="unidades-modal-overlay">
            <div class="unidades-modal">
                <div class="unidades-modal-head">
                    <div>
                        <strong>{{ trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo }}</strong>
                        <div class="muted" style="font-size:12.5px;">Unidades disponibles ({{ $producto->serialesDisponibles->count() }})</div>
                    </div>
                    <button type="button" class="btn btn--ghost" style="padding:5px 10px;" onclick="document.getElementById('unidades-modal-{{ $producto->id }}').style.display='none'">Cerrar</button>
                </div>

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
                        $modelo = trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo;
                    @endphp
                    <div class="unidad-card" @if ($medios->isNotEmpty()) data-medios="{{ $medios->toJson() }}" @endif>
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
            </div>
        </div>
    @endforeach

    {{-- Visor de la evidencia de una pieza: sus fotos y su video, uno por uno --}}
    <div id="galeria-overlay" class="galeria-overlay" onclick="cerrarGaleria(event)">
        <div class="galeria-caja">
            <button type="button" class="galeria-cerrar" onclick="cerrarGaleria(event)" aria-label="Cerrar">&times;</button>
            <div class="galeria-medio" id="galeria-medio"></div>
            <div class="galeria-nav">
                <button type="button" onclick="moverGaleria(-1)" aria-label="Anterior">&lsaquo;</button>
                <span id="galeria-cuenta"></span>
                <button type="button" onclick="moverGaleria(1)" aria-label="Siguiente">&rsaquo;</button>
            </div>
        </div>
    </div>

    <script>
        /* ===================== Galería de evidencia por pieza =====================
           Los medios viajan en data-medios del renglón de la unidad: cada uno
           con su tipo (foto o video), así el visor sabe qué etiqueta pintar. */
        let galeriaMedios = [];
        let galeriaIndice = 0;

        function abrirGaleria(el) {
            const fuente = el.closest('[data-medios]');
            if (!fuente) return;

            try {
                galeriaMedios = JSON.parse(fuente.dataset.medios || '[]');
            } catch (e) {
                galeriaMedios = [];
            }

            if (!galeriaMedios.length) return;

            // Abre en el medio que se tocó, no siempre en el primero.
            const pedido = Number(el.dataset.indice);
            galeriaIndice = Number.isInteger(pedido) && pedido >= 0 && pedido < galeriaMedios.length
                ? pedido
                : 0;

            document.getElementById('galeria-overlay').style.display = 'flex';
            pintarGaleria();
        }

        function pintarGaleria() {
            const medio = galeriaMedios[galeriaIndice];
            const caja = document.getElementById('galeria-medio');

            caja.innerHTML = medio.tipo === 'video'
                ? '<video src="' + medio.url + '" controls autoplay playsinline></video>'
                : '<img src="' + medio.url + '" alt="Evidencia de la unidad">';

            document.getElementById('galeria-cuenta').textContent =
                (galeriaIndice + 1) + ' de ' + galeriaMedios.length + (medio.tipo === 'video' ? ' · video' : '');
        }

        function moverGaleria(paso) {
            if (!galeriaMedios.length) return;
            galeriaIndice = (galeriaIndice + paso + galeriaMedios.length) % galeriaMedios.length;
            pintarGaleria();
        }

        function cerrarGaleria(evento) {
            // Solo cierra al tocar el fondo o la ×, no al tocar la foto.
            if (evento && evento.target.closest('.galeria-medio')) return;

            document.getElementById('galeria-overlay').style.display = 'none';
            document.getElementById('galeria-medio').innerHTML = '';
        }

        document.addEventListener('keydown', function (e) {
            if (document.getElementById('galeria-overlay').style.display !== 'flex') return;

            if (e.key === 'Escape') cerrarGaleria();
            if (e.key === 'ArrowLeft') moverGaleria(-1);
            if (e.key === 'ArrowRight') moverGaleria(1);
        });
        function actualizarSeleccionPaquete() {
            const marcados = document.querySelectorAll('.producto-checkbox:checked');
            const boton = document.getElementById('btn-agrupar-paquete');

            document.getElementById('contador-seleccionados').textContent = marcados.length;

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

        function agruparEnPaquete() {
            const marcados = Array.from(document.querySelectorAll('.producto-checkbox:checked')).map(c => c.value);

            if (marcados.length === 0) return;

            const params = marcados.map(id => 'productos[]=' + encodeURIComponent(id)).join('&');
            window.location.href = @json(route('inventory.paquetes.create')) + '?' + params;
        }
    </script>

    @include('partials.row-menu')
@endsection
