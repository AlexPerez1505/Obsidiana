@extends('layouts.dashboard')

@php
    // Primero el nombre del producto (subtipo) y luego el tipo; primero el modelo y luego la marca.
    $nombre = trim(collect([$producto->subtipo, $producto->tipo_equipo])->filter()->unique()->implode(' · ')) ?: 'Producto';
    $marcaModelo = trim(collect([$producto->modelo, $producto->marca])->filter()->implode(' · '));
    $imagen = $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null;
@endphp

@section('title', $nombre)
@section('page-title', $nombre)
@section('page-sub', $marcaModelo ?: 'Ficha del producto')

@push('head')
    <style>
        /* Encabezado: identidad y acciones arriba, el stock desglosado abajo */
        .pr-cab { margin-bottom:18px; }
        .pr-cab-top { display:flex; align-items:center; justify-content:space-between; gap:12px 18px; flex-wrap:wrap; }
        .pr-cab-id { display:flex; align-items:center; gap:14px; min-width:0; flex:1 1 320px; }
        .pr-cab-txt { min-width:0; }
        .pr-eyebrow { display:block; color:var(--muted); font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .pr-cab h2 { margin:2px 0 2px; font-size:19px; font-weight:600; line-height:1.25; overflow-wrap:anywhere; }
        .pr-mm { margin:0; color:var(--muted); font-size:13.5px; }
        .pr-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .pr-acciones { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

        /* Portada del catálogo: una miniatura; en grande se ve en el visor. */
        .pr-portada { position:relative; flex:0 0 72px; width:72px; height:72px; padding:0; overflow:hidden;
                      border:1px solid var(--border); border-radius:12px; background:var(--surface-2); cursor:pointer;
                      display:flex; align-items:center; justify-content:center; color:var(--muted); }
        .pr-portada img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .25s ease; }
        .pr-portada:hover img { transform:scale(1.04); }
        .pr-portada:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        span.pr-portada { cursor:default; }

        /* El stock no es un número suelto: de dónde sale cada cifra. */
        .pr-stock { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:12px 20px;
                    margin:16px 0 0; padding-top:16px; border-top:1px solid var(--border); }
        .pr-stock > div { min-width:0; }
        .pr-stock dt { color:var(--muted); font-size:12.5px; }
        .pr-stock dd { margin:2px 0 0; }
        .pr-stock .v { display:block; font-size:22px; font-weight:700; font-variant-numeric:tabular-nums; line-height:1.15; }
        .pr-stock .v.ok { color:var(--green); }
        .pr-stock .v.proceso { color:var(--ambar, #b45309); }
        .pr-stock .d { display:block; color:var(--muted); font-size:12px; }
        .pr-desc { margin:14px 0 0; color:var(--muted); }

        /* Cuerpo: piezas (la lista larga) a la izquierda, movimientos al lado */
        .pr-cuerpo { display:grid; grid-template-columns:minmax(0, 1fr); gap:18px; align-items:start; }
        @media (min-width: 1100px) {
            .pr-cuerpo { grid-template-columns:minmax(0, 1fr) 300px; }
        }
        .pr-lateral { display:flex; flex-direction:column; gap:18px; min-width:0; }
        .pr-sec { padding:18px 18px 6px; }
        .pr-sec-cab { display:flex; align-items:baseline; justify-content:space-between; gap:10px; }
        .pr-cuenta { font-size:13px; white-space:nowrap; }
        .pr-sec-nota { margin:4px 0 0; font-size:12.5px; }

        /* Tabla de piezas */
        .kx { width:100%; border-collapse:collapse; }
        .kx th { text-align:left; padding:10px 12px; font-size:11px; font-weight:700;
                 letter-spacing:.06em; text-transform:uppercase; color:var(--muted);
                 border-bottom:1px solid var(--border); white-space:nowrap; }
        .kx td { padding:10px 12px; border-bottom:1px solid var(--border); font-size:13.5px; vertical-align:middle; }
        .kx tr:last-child td { border-bottom:none; }
        .kx .nota { display:block; margin-top:3px; color:var(--muted); font-size:12.5px; }
        .pz-cod { font-family:ui-monospace, Consolas, monospace; font-weight:700; font-size:12.5px; }
        .pr-pz-id { display:flex; align-items:center; gap:10px; white-space:nowrap; }
        .tbl-scroll { overflow-x:auto; }

        /* Evidencia de la pieza en la tabla: portada chiquita con su cuenta */
        .pr-pz-portada { position:relative; flex:0 0 40px; width:40px; height:40px; padding:0; overflow:hidden;
                         border:1px solid var(--border); border-radius:8px; background:var(--surface-2); cursor:pointer; }
        .pr-pz-portada img { width:100%; height:100%; object-fit:cover; display:block; }
        .pr-pz-portada:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .pr-pz-video { display:flex; align-items:center; justify-content:center; width:100%; height:100%;
                       background:#0f172a; color:#fff; }
        .pr-pz-video svg { width:16px; height:16px; }
        .pr-pz-cuenta { position:absolute; right:2px; bottom:2px; display:inline-flex; align-items:center; gap:2px;
                        padding:1px 4px; border-radius:999px; background:rgba(15,23,42,.75); color:#fff;
                        font-size:10px; font-weight:700; line-height:1.3; }
        .pr-pz-cuenta svg { width:8px; height:8px; }
        .pr-pz-vacia { flex:0 0 40px; width:40px; height:40px; border:1px dashed var(--border); border-radius:8px;
                       display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:11px; }

        /* Movimientos en el lateral: una tarjetita por movimiento */
        .pr-movs { list-style:none; margin:0; padding:0 0 6px; }
        .pr-mov { padding:11px 18px; border-top:1px solid var(--border); }
        .pr-mov-l1 { display:flex; align-items:center; gap:8px; }
        .pr-mov-folio { font-family:ui-monospace, Consolas, monospace; font-weight:700; font-size:12.5px; color:inherit; }
        .pr-mov-cant { margin-left:auto; font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .pr-mov-cant.entra { color:var(--green); }
        .pr-mov-cant.sale { color:var(--danger); }
        .pr-mov-l2 { display:flex; flex-wrap:wrap; gap:2px 10px; margin-top:4px; color:var(--muted); font-size:12.5px; }
        .pr-mov-l2 b { color:var(--text); }
        .pr-mov .nota { display:block; margin-top:3px; color:var(--muted); font-size:12.5px; overflow-wrap:anywhere; }
        .pr-mov-ver { margin-left:auto; }

        @media (max-width: 560px) {
            .pr-stock { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .pr-portada { flex-basis:60px; width:60px; height:60px; }
        }
    </style>
@endpush

@section('content')
    {{-- ===================== Encabezado + stock =====================
         Qué producto es, qué se puede hacer con él y cuánto hay, en un
         solo bloque. --}}
    <x-ui.card class="pr-cab">
        <div class="pr-cab-top">
            <div class="pr-cab-id" @if ($imagen) data-medios="{{ collect([['tipo' => 'foto', 'url' => $imagen]])->toJson() }}" @endif>
                @if ($imagen)
                    <button type="button" class="pr-portada" data-galeria data-indice="0" aria-label="Ver la imagen de {{ $nombre }}">
                        <img src="{{ $imagen }}" alt="{{ $nombre }}">
                    </button>
                @else
                    <span class="pr-portada">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="26" height="26"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                    </span>
                @endif

                <div class="pr-cab-txt">
                    <span class="pr-eyebrow">{{ $producto->id ? 'Ficha del producto' : 'Producto' }}</span>
                    <h2>{{ $nombre }}</h2>
                    @if ($marcaModelo || $producto->referencia)
                        <p class="pr-mm">
                            {{ $marcaModelo }}
                            @if ($marcaModelo && $producto->referencia) · @endif
                            @if ($producto->referencia)Ref. {{ $producto->referencia }}@endif
                        </p>
                    @endif

                    @if (\App\Support\PrecioVisible::para() || $producto->es_serializado || $producto->en_tienda)
                        <div class="pr-chips">
                            @if (\App\Support\PrecioVisible::para())
                                <span class="badge">{{ \App\Support\PrecioVisible::texto($producto) }}</span>
                            @endif
                            @if ($producto->es_serializado)
                                <span class="badge badge--info">Se maneja pieza por pieza</span>
                            @endif
                            @include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'etiqueta'])
                        </div>
                    @endif
                </div>
            </div>

            <div class="pr-acciones">
                @include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'boton'])
                <a href="{{ route('inventory.movimientos.create') }}" class="btn btn--ghost">Registrar entrada</a>
                @if (\App\Support\EdicionPropia::puedeEditarProducto(auth()->user(), $producto)
                     || $piezas->contains(fn ($p) => \App\Support\EdicionPropia::puedeEditarPieza(auth()->user(), $p)))
                    <a href="{{ route('inventory.productos.edit', $producto) }}" class="btn-icono" title="Editar producto" aria-label="Editar producto">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </a>
                @endif
                <a href="{{ route('inventory.productos.index') }}" class="btn-icono" title="Regresar" aria-label="Regresar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
            </div>
        </div>

        {{-- El stock no es un número suelto: se cuenta desde las piezas. --}}
        <dl class="pr-stock">
            <div>
                <dt>Disponible</dt>
                <dd><span class="v ok">{{ $conteos['disponibles'] }}</span><span class="d">Se puede vender hoy</span></dd>
            </div>
            <div>
                <dt>En proceso</dt>
                <dd>
                    <span class="v {{ $conteos['en_proceso'] ? 'proceso' : '' }}">{{ $conteos['en_proceso'] }}</span>
                    <span class="d">
                        @if ($conteos['en_proceso'])
                            <a href="{{ route('inventory.procesos.index') }}" class="link">Hojalatería o mantenimiento</a>
                        @else
                            Nada detenido
                        @endif
                    </span>
                </dd>
            </div>
            <div>
                <dt>Vendidas</dt>
                <dd><span class="v">{{ $conteos['vendidas'] }}</span><span class="d">Ya salieron</span></dd>
            </div>
            <div>
                <dt>Han entrado</dt>
                <dd><span class="v">{{ $conteos['total'] }}</span><span class="d">Piezas desde el inicio</span></dd>
            </div>
        </dl>

        @if ($producto->descripcion)
            <p class="pr-desc">{{ $producto->descripcion }}</p>
        @endif
    </x-ui.card>

    <div class="pr-cuerpo">
        {{-- ===================== Piezas =====================
             La lista larga va en la columna ancha. Cada pieza lleva su
             evidencia como una portada chiquita: todo lo demás se ve en el
             visor, sin un mosaico de miniaturas. --}}
        <x-ui.card style="padding:0;">
            <div class="pr-sec">
                <div class="pr-sec-cab">
                    <x-ui.section-title style="margin:0;">Piezas</x-ui.section-title>
                    <span class="muted pr-cuenta">{{ $piezas->count() }} {{ $piezas->count() === 1 ? 'pieza' : 'piezas' }}</span>
                </div>
                <p class="campo-nota pr-sec-nota">
                    Cada pieza física con su etiqueta. Escanea su QR para abrir la ficha pública.
                </p>

                @if ($piezas->count() > 5)
                    <div style="display:flex; gap:10px; align-items:center; margin-top:10px;">
                        <input type="search" id="buscarPieza" placeholder="Buscar por serie, etiqueta o folio…"
                               aria-label="Buscar pieza" style="flex:1; min-width:0;">
                        <span class="campo-nota" id="buscarPiezaCuenta" style="white-space:nowrap;"></span>
                    </div>
                @endif
            </div>

            <div class="tbl-scroll">
                <table class="kx">
                    <thead>
                        <tr>
                            <th>Etiqueta</th>
                            <th>No. de serie</th>
                            <th>Estado</th>
                            <th>Condición</th>
                            <th>Entró</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($piezas as $pieza)
                            @php
                                // Fotos y video de la pieza, en el orden del visor.
                                $fotos = $pieza->evidenceUrls();
                                $medios = collect($fotos)->map(fn ($url) => ['tipo' => 'foto', 'url' => $url]);
                                if ($pieza->videoUrl()) {
                                    $medios->push(['tipo' => 'video', 'url' => $pieza->videoUrl()]);
                                }
                            @endphp
                            <tr data-pieza data-buscar="{{ mb_strtolower(implode(' ', array_filter([$pieza->no_serie, $pieza->codigo, $pieza->entrada?->folio, $pieza->estadoLabel()]))) }}"
                                @if ($medios->isNotEmpty()) data-medios="{{ $medios->toJson() }}" @endif>
                                <td>
                                    <div class="pr-pz-id">
                                        @if ($medios->isNotEmpty())
                                            <button type="button" class="pr-pz-portada" data-galeria data-indice="0"
                                                    title="Ver evidencia: {{ count($fotos) }} {{ count($fotos) === 1 ? 'foto' : 'fotos' }}{{ $pieza->videoUrl() ? ' y video' : '' }}"
                                                    aria-label="Ver la evidencia de {{ $pieza->codigo ?: 'esta pieza' }}">
                                                @if (count($fotos))
                                                    <img src="{{ $fotos[0] }}" alt="" loading="lazy">
                                                @else
                                                    <span class="pr-pz-video" aria-hidden="true">
                                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                                    </span>
                                                @endif
                                                <span class="pr-pz-cuenta">
                                                    {{ count($fotos) }}
                                                    @if ($pieza->videoUrl())
                                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        @else
                                            <span class="pr-pz-vacia" title="Sin evidencia">—</span>
                                        @endif
                                        <span class="pz-cod">{{ $pieza->codigo ?: '—' }}</span>
                                    </div>
                                </td>
                                <td>{{ $pieza->no_serie ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $pieza->vendible() ? 'badge--ok' : ($pieza->vendido ? '' : 'badge--info') }}">
                                        {{ $pieza->estadoLabel() }}
                                    </span>
                                    @if ($falta = $pieza->faltaTexto())
                                        <span class="nota">Falta: {{ $falta }}</span>
                                    @endif
                                    @if ($pieza->vendido && $pieza->ventaItem?->venta)
                                        <span class="nota">Venta {{ $pieza->ventaItem->venta->folio }}</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($pieza->condicion) }}</td>
                                <td style="white-space:nowrap;">
                                    {{ $pieza->entrada?->movement_date?->format('d/m/Y') ?? '—' }}
                                    @if ($pieza->entrada)
                                        <span class="nota">{{ $pieza->entrada->folio }}</span>
                                    @endif
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    @if ($pieza->urlPublica())
                                        <a href="{{ $pieza->urlPublica() }}" target="_blank" class="tbl-link">Ficha</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <h3>Sin piezas</h3>
                                        <p>Registra una entrada para dar de alta las primeras.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- ===================== Lateral: kardex =====================
             Cada movimiento cabe en una tarjetita (folio, cantidad, fecha,
             quién y cómo quedó el stock), así que no necesita la tabla de
             siete columnas a todo lo ancho. --}}
        <div class="pr-lateral">
            <x-ui.card style="padding:0;">
                <div class="pr-sec" style="padding-bottom:12px;">
                    <div class="pr-sec-cab">
                        <x-ui.section-title style="margin:0;">Movimientos</x-ui.section-title>
                        <span class="muted pr-cuenta">{{ $movimientos->count() }}</span>
                    </div>
                    <p class="campo-nota pr-sec-nota">
                        Todo lo que entró y salió de este producto, con quién lo registró y cómo quedó el stock.
                    </p>
                </div>

                @if ($movimientos->isEmpty())
                    <div class="empty-state" style="border-top:1px solid var(--border);">
                        <h3>Sin movimientos</h3>
                        <p>Este producto todavía no registra entradas ni salidas.</p>
                    </div>
                @else
                    <ul class="pr-movs">
                        @foreach ($movimientos as $mov)
                            @php $entra = $mov->movement_type === 'entrada'; @endphp
                            <li class="pr-mov">
                                <div class="pr-mov-l1">
                                    <span class="badge {{ $entra ? 'badge--ok' : 'badge--danger' }}">{{ ucfirst($mov->movement_type) }}</span>
                                    <a href="{{ route('inventory.movimientos.show', $mov) }}" class="pr-mov-folio">{{ $mov->folio }}</a>
                                    <span class="pr-mov-cant {{ $entra ? 'entra' : 'sale' }}">{{ $entra ? '+' : '−' }}{{ $mov->quantity }}</span>
                                </div>
                                <div class="pr-mov-l2">
                                    <span>{{ $mov->movement_date?->format('d/m/Y') ?? '—' }}</span>
                                    <span>Stock {{ $mov->stock_before ?? '—' }} &rarr; <b>{{ $mov->stock_after ?? '—' }}</b></span>
                                    <span>{{ $mov->creator?->name ?: '—' }}</span>
                                    <a href="{{ route('inventory.movimientos.show', $mov) }}" class="tbl-link pr-mov-ver">Ver</a>
                                </div>
                                @if ($entra && $mov->condicion)
                                    <span class="nota">Equipo {{ $mov->condicion }}</span>
                                @endif
                                @if ($mov->reference)
                                    <span class="nota">Ref. {{ $mov->reference }}</span>
                                @endif
                                @if ($mov->notes)
                                    <span class="nota">{{ $mov->notes }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>

    @include('partials.galeria')

    @push('scripts')
        <script>
        (function () {
            const campo = document.getElementById('buscarPieza');
            if (!campo) return;

            const filas = Array.from(document.querySelectorAll('[data-pieza]'));
            const cuenta = document.getElementById('buscarPiezaCuenta');

            campo.addEventListener('input', function () {
                const q = campo.value.trim().toLowerCase();
                let n = 0;

                filas.forEach(function (tr) {
                    const ok = !q || tr.dataset.buscar.includes(q);
                    tr.hidden = !ok;
                    if (ok) n++;
                });

                cuenta.textContent = q ? n + ' de ' + filas.length : '';
            });
        })();
        </script>
    @endpush
@endsection
