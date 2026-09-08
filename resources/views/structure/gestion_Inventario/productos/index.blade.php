@extends('layouts.dashboard')
@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Inventario de equipos y stock disponible')

<<<<<<< Updated upstream
=======
@push('head')
    <style>
        .unidades-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:1000; padding:20px; }
        .unidades-modal { background:var(--surface); border-radius:12px; max-width:560px; width:100%; max-height:80vh; overflow-y:auto; padding:20px; }
        .unidades-modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
        .unidad-item { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border); }
        .unidad-item:last-child { border-bottom:none; }
        .unidad-item img { width:54px; height:54px; object-fit:cover; border-radius:8px; border:1px solid var(--border); cursor:pointer; }
        .unidad-item .unidad-sin-foto { width:54px; height:54px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:11px; text-align:center; }
        .unidad-item .unidad-thumb-wrap { position:relative; flex:0 0 54px; cursor:pointer; }
        .unidad-item .unidad-thumb-wrap .unidad-video-badge { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.25); border-radius:8px; color:#fff; pointer-events:none; }
        .unidad-item .unidad-mas { font-size:11px; color:var(--muted); cursor:pointer; text-decoration:underline; margin-left:4px; }
        .producto-row { cursor:pointer; }
        .producto-row:hover { background:var(--surface-2); }

        /* ===================== Galería / lightbox de evidencia ===================== */
        .galeria-overlay { position:fixed; inset:0; background:rgba(0,0,0,.85); display:none; align-items:center; justify-content:center; z-index:1100; touch-action:none; }
        .galeria-contenido { max-width:90vw; max-height:80vh; display:flex; align-items:center; justify-content:center; }
        .galeria-contenido img, .galeria-contenido video { max-width:90vw; max-height:80vh; border-radius:10px; object-fit:contain; }
        .galeria-cerrar { position:absolute; top:16px; right:20px; width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.12); border:none; color:#fff; font-size:22px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .galeria-cerrar:hover { background:rgba(255,255,255,.22); }
        .galeria-nav { position:absolute; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.12); border:none; color:#fff; font-size:26px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .galeria-nav:hover { background:rgba(255,255,255,.22); }
        .galeria-nav.prev { left:16px; }
        .galeria-nav.next { right:16px; }
        .galeria-contador { position:absolute; bottom:18px; left:50%; transform:translateX(-50%); color:#fff; font-size:13px; background:rgba(255,255,255,.12); padding:4px 12px; border-radius:999px; }
        @media (max-width:640px) {
            .galeria-nav { width:38px; height:38px; font-size:22px; }
            .galeria-nav.prev { left:6px; }
            .galeria-nav.next { right:6px; }
        }
    </style>
@endpush

>>>>>>> Stashed changes
@section('content')
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
        <form method="GET" style="flex:1; min-width:220px; max-width:380px;">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por tipo, marca, modelo o serie..."
                   style="width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:9px; font-size:14.5px; background:var(--surface); color:var(--text);">
        </form>
        <div style="flex:1;"></div>
<<<<<<< Updated upstream
=======
        <a href="{{ route('inventory.paquetes.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <x-gravityui-box width="16" height="16" />
            Paquetes
        </a>
        <button type="button" id="btn-agrupar-paquete" class="btn" disabled
                style="display:inline-flex; align-items:center; gap:7px; opacity:.5; cursor:not-allowed;"
                onclick="agruparEnPaquete()">
            <x-gravityui-plus width="16" height="16" />
            Agrupar en paquete (<span id="contador-seleccionados">0</span>)
        </button>
>>>>>>> Stashed changes
        <a href="{{ route('inventory.movimientos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <x-gravityui-plus width="16" height="16" />
            Registrar entrada
        </a>
    </div>

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Equipo</th>
                        <th>Marca / Modelo</th>
                        <th>No. Serie</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr>
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
                            <td>{{ $producto->no_serie ?: '—' }}</td>
                            <td>${{ number_format($producto->precio, 2) }}</td>
                            <td>
                                <span class="badge {{ $producto->stock > 0 ? 'badge--ok' : 'badge--danger' }}">
                                    {{ $producto->stock }} u.
                                </span>
                            </td>
                            <td>{{ $producto->proveedor ?: '—' }}</td>
                            <td>
<<<<<<< Updated upstream
                                <details class="actions-dropdown" style="position:relative;">
                                    <summary style="list-style:none; cursor:pointer; display:inline-flex; padding:6px 8px; border-radius:6px; color:var(--text);">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="6" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="18" r="2"/></svg>
                                    </summary>
                                    <div style="position:absolute; right:0; top:100%; margin-top:6px; background:var(--surface); border:1px solid var(--border); border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,.12); min-width:130px; z-index:20; overflow:hidden;">
                                        <a href="{{ route('inventory.productos.edit', $producto) }}" style="display:flex; align-items:center; gap:8px; padding:10px 14px; font-size:13px; color:var(--text); text-decoration:none; white-space:nowrap;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
=======
                                {{-- Menú compartido: se posiciona en fijo, así que
                                     no lo recorta el overflow de la tarjeta. El
                                     de antes se cortaba cuando había una sola
                                     fila y no se alcanzaba a ver. --}}
                                <div class="row-menu" data-row-menu>
                                    <button type="button" class="row-menu-btn" data-row-menu-toggle
                                            aria-haspopup="true" aria-expanded="false"
                                            aria-label="Acciones de {{ $producto->tipo_equipo }}">
                                        <x-gravityui-ellipsis-vertical />
                                    </button>

                                    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                        <a href="{{ route('inventory.productos.show', $producto) }}" role="menuitem">
                                            <x-gravityui-eye />
                                            Ver ficha
                                        </a>
                                        <a href="{{ route('inventory.productos.edit', $producto) }}" role="menuitem">
                                            <x-gravityui-pencil />
>>>>>>> Stashed changes
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('inventory.productos.destroy', $producto) }}" onsubmit="return confirm('¿Eliminar este producto?');" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
<<<<<<< Updated upstream
                                            <button type="submit" style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; font-size:13px; color:var(--danger); background:transparent; border:none; cursor:pointer; text-align:left; white-space:nowrap;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
=======
                                            <button type="submit" class="es-danger" role="menuitem">
                                                <x-gravityui-trash-bin />
>>>>>>> Stashed changes
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </details>
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
<<<<<<< Updated upstream
=======

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
                        $medios = collect($evidencias)->map(fn ($url) => ['tipo' => 'imagen', 'url' => $url])->values();
                        if ($serial->videoUrl()) {
                            $medios->push(['tipo' => 'video', 'url' => $serial->videoUrl()]);
                        }
                    @endphp
                    <div class="unidad-item">
                        @if (count($evidencias))
                            <div class="unidad-thumb-wrap" data-medios="{{ $medios->toJson() }}" onclick="abrirGaleria(this)">
                                <img src="{{ $evidencias[0] }}" alt="Foto de la unidad">
                            </div>
                        @elseif ($serial->videoUrl())
                            <div class="unidad-thumb-wrap" data-medios="{{ $medios->toJson() }}" onclick="abrirGaleria(this)">
                                <div class="unidad-sin-foto" style="position:relative;">
                                    <x-gravityui-video width="20" height="20" />
                                </div>
                            </div>
                        @else
                            <div class="unidad-sin-foto">Sin foto</div>
                        @endif
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:13.5px;">{{ $serial->no_serie ?: '— (sin serial capturado)' }}</div>
                            <span class="badge badge--ok" style="font-size:11px;">Disponible</span>
                            @if ($medios->isNotEmpty())
                                <span class="unidad-mas" data-medios="{{ $medios->toJson() }}" onclick="abrirGaleria(this)">
                                    Ver evidencia ({{ count($evidencias) }} foto{{ count($evidencias) !== 1 ? 's' : '' }}{{ $serial->videoUrl() ? ' + video' : '' }})
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="muted" style="padding:16px 0; text-align:center;">No hay unidades disponibles.</p>
                @endforelse
            </div>
        </div>
    @endforeach

    {{-- Galería de evidencia: un solo visor reutilizado por todas las
         unidades, para poder deslizarse entre sus 3 fotos y su video. --}}
    <div id="galeria-overlay" class="galeria-overlay">
        <button type="button" class="galeria-cerrar" onclick="cerrarGaleria()" aria-label="Cerrar">&times;</button>
        <button type="button" class="galeria-nav prev" onclick="galeriaMover(-1)" aria-label="Anterior">&lsaquo;</button>
        <div class="galeria-contenido" id="galeria-contenido"></div>
        <button type="button" class="galeria-nav next" onclick="galeriaMover(1)" aria-label="Siguiente">&rsaquo;</button>
        <div class="galeria-contador" id="galeria-contador"></div>
    </div>

    <script>
        let galeriaMedios = [];
        let galeriaIndice = 0;

        function abrirGaleria(el) {
            try {
                galeriaMedios = JSON.parse(el.dataset.medios || '[]');
            } catch (e) {
                galeriaMedios = [];
            }
            if (!galeriaMedios.length) return;

            galeriaIndice = 0;
            document.getElementById('galeria-overlay').style.display = 'flex';
            pintarGaleria();
        }

        function cerrarGaleria() {
            document.getElementById('galeria-overlay').style.display = 'none';
            document.getElementById('galeria-contenido').innerHTML = '';
        }

        function galeriaMover(delta) {
            if (!galeriaMedios.length) return;
            galeriaIndice = (galeriaIndice + delta + galeriaMedios.length) % galeriaMedios.length;
            pintarGaleria();
        }

        function pintarGaleria() {
            const medio = galeriaMedios[galeriaIndice];
            const contenido = document.getElementById('galeria-contenido');

            contenido.innerHTML = medio.tipo === 'video'
                ? '<video src="' + medio.url + '" controls autoplay></video>'
                : '<img src="' + medio.url + '" alt="Evidencia de la unidad">';

            document.getElementById('galeria-contador').textContent = (galeriaIndice + 1) + ' / ' + galeriaMedios.length;

            const mostrarNav = galeriaMedios.length > 1;
            document.querySelectorAll('.galeria-nav').forEach(function (b) { b.style.display = mostrarNav ? 'flex' : 'none'; });
        }

        (function () {
            const overlay = document.getElementById('galeria-overlay');
            if (!overlay) return;

            // Clic fuera del contenido cierra la galería.
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) cerrarGaleria();
            });

            // Flechas del teclado y Escape.
            document.addEventListener('keydown', function (e) {
                if (overlay.style.display !== 'flex') return;
                if (e.key === 'Escape') cerrarGaleria();
                if (e.key === 'ArrowLeft') galeriaMover(-1);
                if (e.key === 'ArrowRight') galeriaMover(1);
            });

            // Deslizar con el dedo (swipe) para pasar de foto/video.
            let inicioX = 0;
            overlay.addEventListener('touchstart', function (e) {
                inicioX = e.touches[0].clientX;
            }, { passive: true });
            overlay.addEventListener('touchend', function (e) {
                const diferencia = e.changedTouches[0].clientX - inicioX;
                if (Math.abs(diferencia) > 40) {
                    galeriaMover(diferencia > 0 ? -1 : 1);
                }
            }, { passive: true });
        })();

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
>>>>>>> Stashed changes
@endsection
