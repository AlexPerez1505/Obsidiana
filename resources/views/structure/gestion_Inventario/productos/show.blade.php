@extends('layouts.dashboard')

@php
    // Primero el nombre del producto (subtipo) y luego el tipo; primero el modelo y luego la marca.
    $nombre = $producto->nombreVisible() ?: 'Producto';
    $marcaModelo = trim(collect([$producto->modelo, $producto->marca])->filter()->implode(' · '));
    $imagen = $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null;
    $tipoTitulo = $producto->tipo_equipo ? \Illuminate\Support\Str::title(mb_strtolower($producto->tipo_equipo)) : null;

    // Galería: la foto del producto y la evidencia de sus piezas (una por pieza).
    $medios = collect();
    if ($imagen) { $medios->push(['tipo' => 'foto', 'url' => $imagen]); }
    foreach ($piezas->where('vendido', false) as $pz) {
        $f = $pz->evidenceUrls()[0] ?? null;
        if ($f && ! $medios->contains('url', $f)) { $medios->push(['tipo' => 'foto', 'url' => $f]); }
        if ($medios->count() >= 12) break;
    }

    $puedeEditar = \App\Support\EdicionPropia::puedeEditarProducto(auth()->user(), $producto)
        || $piezas->contains(fn ($p) => \App\Support\EdicionPropia::puedeEditarPieza(auth()->user(), $p));
    $verPrecio = \App\Support\PrecioVisible::para();
@endphp

@section('title', $nombre)
@section('page-title', 'Ficha del equipo')
@section('page-sub', $tipoTitulo ?: 'Inventario')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/producto-ficha.css') }}?v={{ filemtime(public_path('css/producto-ficha.css')) }}">
@endpush

@section('content')
<div class="pf">
    {{-- ===================== Galería + datos ===================== --}}
    <section class="pf-top">
        <div class="pf-galeria" @if ($medios->isNotEmpty()) data-medios="{{ $medios->toJson() }}" @endif>
            @if ($medios->isNotEmpty())
                <button type="button" class="pf-foto pf-foto--grande" data-galeria data-indice="0" aria-label="Ver la foto de {{ $nombre }} en grande">
                    <img src="{{ $medios[0]['url'] }}" alt="{{ $nombre }}" onerror="this.remove()">
                </button>
                @if ($medios->count() > 1)
                    <div class="pf-miniaturas">
                        @foreach ($medios->slice(1, 2)->values() as $i => $m)
                            <button type="button" class="pf-foto" data-galeria data-indice="{{ $i + 1 }}" aria-label="Ver foto {{ $i + 2 }} de {{ $medios->count() }}">
                                <img src="{{ $m['url'] }}" alt="" loading="lazy" onerror="this.remove()">
                                @if ($i === 1 && $medios->count() > 3)
                                    <span class="pf-mas">+{{ $medios->count() - 3 }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <span class="pf-foto pf-foto--grande pf-foto--vacia" aria-label="Sin foto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                </span>
            @endif
        </div>

        <div class="pf-info">
            <nav class="pf-migas" aria-label="Ruta">
                <a href="{{ route('inventory.productos.index') }}">Productos</a>
                @if ($producto->tipo_equipo)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('inventory.productos.index', ['tipo' => $producto->tipo_equipo]) }}">{{ $tipoTitulo }}</a>
                @endif
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $nombre }}</span>
            </nav>

            <h1 class="pf-titulo">{{ $nombre }}</h1>
            @if ($marcaModelo)
                <p class="pf-sub">{{ $marcaModelo }}</p>
            @endif

            <div class="pf-linea">
                @if ($verPrecio)
                    <span class="pf-precio {{ $producto->precio === null ? 'is-sin' : '' }}">{{ \App\Support\PrecioVisible::texto($producto) }}</span>
                    <span class="pf-sep" aria-hidden="true"></span>
                @endif
                <span class="pf-stock {{ $conteos['disponibles'] > 0 ? '' : 'is-cero' }}">
                    @if ($conteos['disponibles'] > 0)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        {{ $conteos['disponibles'] }} {{ $conteos['disponibles'] === 1 ? 'disponible' : 'disponibles' }}
                    @else
                        Sin stock disponible
                    @endif
                </span>
                @if ($producto->en_tienda)
                    <span class="pf-chip pf-chip--web">En la web</span>
                @endif
                @if ($producto->es_serializado)
                    <span class="pf-chip">Pieza por pieza</span>
                @endif
            </div>

            {{-- Datos clave: lista sencilla, sin recuadros. --}}
            <div class="pf-opc">
                <span class="pf-opc-t">Datos del equipo</span>
                <dl class="pf-specs">
                    <div><dt>Categoría</dt><dd>{{ $tipoTitulo ?: '—' }}</dd></div>
                    <div><dt>Marca</dt><dd>{{ $producto->marca ?: '—' }}</dd></div>
                    <div><dt>Modelo</dt><dd>{{ $producto->modelo ?: '—' }}</dd></div>
                    <div><dt>Referencia</dt><dd>{{ $producto->referencia ?: '—' }}</dd></div>
                </dl>
            </div>

            {{-- Piezas: como los selectores de talla. --}}
            <div class="pf-opc">
                <div class="pf-opc-cab">
                    <span class="pf-opc-t">Piezas</span>
                    <a class="pf-opc-link" href="#piezas">Ver todas</a>
                </div>
                <div class="pf-tallas">
                    <a class="pf-talla is-sel" href="#piezas"><b>{{ $conteos['disponibles'] }}</b><span><i class="pf-punto pf-punto--ok"></i>Disponibles</span></a>
                    <a class="pf-talla" href="{{ $conteos['en_proceso'] ? route('inventory.procesos.index') : '#piezas' }}"><b>{{ $conteos['en_proceso'] }}</b><span><i class="pf-punto pf-punto--proceso"></i>En proceso</span></a>
                    @if ($enCongresoProducto)
                        <a class="pf-talla" href="#piezas"><b>{{ $enCongresoProducto }}</b><span><i class="pf-punto pf-punto--congreso"></i>En congreso</span></a>
                    @endif
                    <a class="pf-talla" href="#piezas"><b>{{ $conteos['vendidas'] }}</b><span><i class="pf-punto"></i>Vendidas</span></a>
                </div>
            </div>

            {{-- Acción principal y, al lado, las demás como iconos. --}}
            <div class="pf-acciones">
                @if ($puedeEditar)
                    <a class="pf-btn pf-btn--prim" href="{{ route('inventory.productos.edit', ['producto' => $producto, 'return_to' => route('inventory.productos.show', $producto, false)]) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Editar producto
                    </a>
                @endif
                @can('inventario.registrar')
                    <a class="pf-btn pf-btn--icono" href="{{ route('inventory.movimientos.create') }}" title="Registrar entrada" aria-label="Registrar entrada">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </a>
                @endcan
                <span class="pf-tienda">@include('structure.gestion_Inventario.productos._tienda', ['producto' => $producto, 'como' => 'boton'])</span>
                <a class="pf-btn pf-btn--icono" href="{{ $volverUrl }}" title="Regresar" aria-label="Regresar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
            </div>

            {{-- Secciones plegables. --}}
            <div class="pf-acordeon">
                <details open>
                    <summary>Descripción</summary>
                    <div class="pf-acordeon-c">
                        @if ($producto->descripcion)
                            <p>{{ $producto->descripcion }}</p>
                        @else
                            <p class="pf-muted">Este producto todavía no tiene descripción.</p>
                        @endif
                    </div>
                </details>
                <details open>
                    <summary>Especificaciones</summary>
                    <div class="pf-acordeon-c">
                        <dl class="pf-tabla">
                            <div><dt>Tipo</dt><dd>{{ $producto->tipo_equipo ?: '—' }}</dd></div>
                            @if ($producto->subtipo)<div><dt>Producto</dt><dd>{{ $producto->subtipo }}</dd></div>@endif
                            <div><dt>Marca</dt><dd>{{ $producto->marca ?: '—' }}</dd></div>
                            <div><dt>Modelo</dt><dd>{{ $producto->modelo ?: '—' }}</dd></div>
                            @if ($producto->referencia)<div><dt>Referencia</dt><dd>{{ $producto->referencia }}</dd></div>@endif
                            @if ($producto->proveedor)<div><dt>Proveedor</dt><dd>{{ $producto->proveedor }}</dd></div>@endif
                            <div><dt>Manejo</dt><dd>{{ $producto->es_serializado ? 'Pieza por pieza, cada una con su etiqueta' : 'Por cantidad' }}</dd></div>
                        </dl>
                    </div>
                </details>
                <details open>
                    <summary>Movimientos <span class="pf-n">{{ $movimientos->count() }}</span></summary>
                    <div class="pf-acordeon-c">
                        @if ($movimientos->isEmpty())
                            <p class="pf-muted">Todavía no registra entradas ni salidas.</p>
                        @else
                            <ul class="pf-movs">
                                @foreach ($movimientos as $mov)
                                    @php $entra = $mov->movement_type === 'entrada'; @endphp
                                    <li>
                                        <span class="pf-mov-tipo {{ $entra ? 'is-entra' : 'is-sale' }}">{{ $entra ? '+' : '−' }}{{ $mov->quantity }}</span>
                                        <span class="pf-mov-txt">
                                            <a href="{{ route('inventory.movimientos.show', ['movimiento' => $mov, 'return_to' => route('inventory.productos.show', $producto, false)]) }}">{{ ucfirst($mov->movement_type) }} {{ $mov->folio }}</a>
                                            <small>{{ $mov->movement_date?->format('d/m/Y') ?? '—' }} · {{ $mov->creator?->name ?: '—' }} · stock {{ $mov->stock_before ?? '—' }} → {{ $mov->stock_after ?? '—' }}{{ $mov->reference ? ' · Ref. '.$mov->reference : '' }}</small>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </section>

    {{-- ===================== Cuadros de color ===================== --}}
    <section class="pf-cuadros" aria-label="Resumen de piezas">
        <div class="pf-cuadro pf-cuadro--verde">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg>
            <b>{{ $conteos['disponibles'] }} para vender</b>
            <span>Listas en almacén o en congreso</span>
        </div>
        <div class="pf-cuadro pf-cuadro--ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/></svg>
            <b>{{ $conteos['en_proceso'] }} en proceso</b>
            <span>Revisión, hojalatería, mantenimiento o limpieza</span>
        </div>
        <div class="pf-cuadro pf-cuadro--azul">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg>
            <b>{{ $conteos['vendidas'] }} vendidas</b>
            <span>Ya salieron o están por entregarse</span>
        </div>
        <div class="pf-cuadro pf-cuadro--rosa">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            <b>{{ $conteos['total'] }} han entrado</b>
            <span>Piezas desde el inicio</span>
        </div>
    </section>

    {{-- ===================== Piezas ===================== --}}
    <section class="pf-sec" id="piezas" aria-labelledby="pf-piezas-t">
        <div class="pf-sec-cab">
            <h2 id="pf-piezas-t">Piezas <span>{{ $piezas->count() }}</span></h2>
            @if ($piezas->count() > 5)
                <label class="pf-buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <span class="pf-sr">Buscar pieza</span>
                    <input type="search" id="buscarPieza" placeholder="Serie, etiqueta o folio">
                </label>
                <span class="pf-muted" id="buscarPiezaCuenta"></span>
            @endif
        </div>
        <p class="pf-muted pf-sec-nota">Cada pieza física con su etiqueta. Toca la foto para ver su evidencia.</p>

        <div class="pf-piezas">
            @php $resumenComp = \App\Models\PiezaComponente::resumenPara($piezas->pluck('id')); @endphp
            @forelse ($piezas as $pieza)
                @php
                    $fotos = $pieza->evidenceUrls();
                    $mediosPz = collect($fotos)->map(fn ($url) => ['tipo' => 'foto', 'url' => $url]);
                    if ($pieza->videoUrl()) { $mediosPz->push(['tipo' => 'video', 'url' => $pieza->videoUrl()]); }
                @endphp
                <article class="pf-pieza" data-pieza
                         data-buscar="{{ mb_strtolower(implode(' ', array_filter([$pieza->no_serie, $pieza->codigo, $pieza->entrada?->folio, $pieza->estadoLabel()]))) }}"
                         @if ($mediosPz->isNotEmpty()) data-medios="{{ $mediosPz->toJson() }}" @endif>
                    @if ($mediosPz->isNotEmpty())
                        <button type="button" class="pf-pieza-foto" data-galeria data-indice="0"
                                aria-label="Ver la evidencia de {{ $pieza->codigo ?: 'esta pieza' }}">
                            @if (count($fotos))
                                <img src="{{ $fotos[0] }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                            <span class="pf-pieza-n">{{ count($fotos) }}{{ $pieza->videoUrl() ? ' ▶' : '' }}</span>
                        </button>
                    @else
                        <span class="pf-pieza-foto pf-pieza-foto--vacia" aria-hidden="true">—</span>
                    @endif

                    <div class="pf-pieza-txt">
                        <b class="pf-cod">{{ $pieza->codigo ?: '—' }}</b>
                        <span>Serie {{ $pieza->no_serie ?: '—' }} · {{ ucfirst($pieza->condicion) }}</span>
                        <span class="pf-muted">
                            Entró {{ $pieza->entrada?->movement_date?->format('d/m/Y') ?? '—' }}{{ $pieza->entrada ? ' · '.$pieza->entrada->folio : '' }}
                            @if ($pieza->vendido && $pieza->ventaItem?->venta) · Venta {{ $pieza->ventaItem->venta->folio }}@endif
                            @if ($falta = $pieza->faltaTexto()) · Falta: {{ $falta }}@endif
                        </span>
                    </div>

                    <span class="pf-pieza-estado {{ $pieza->vendible() ? 'is-ok' : ($pieza->vendido ? 'is-vendida' : 'is-proceso') }}">{{ $pieza->estadoLabel() }}</span>

                    <div class="pf-pieza-acc">
                        @include('structure.gestion_Inventario._componentes_boton', ['pieza' => $pieza, 'resumen' => $resumenComp])
                        @if ($pieza->urlPublica())
                            <a href="{{ $pieza->urlPublica() }}" target="_blank" rel="noopener" class="pf-link">Ficha pública</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="pf-vacio">
                    <b>Sin piezas</b>
                    <span>Registra una entrada para dar de alta las primeras.</span>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ===================== Relacionados ===================== --}}
    @if ($relacionados->isNotEmpty())
        <section class="pf-sec" aria-labelledby="pf-rel-t">
            <div class="pf-sec-cab">
                <h2 id="pf-rel-t">También en {{ $tipoTitulo ?: 'el inventario' }}</h2>
                <div class="pf-carrusel-nav">
                    <button type="button" class="pf-flecha" data-pf-carrusel="-1" aria-label="Anteriores"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg></button>
                    <button type="button" class="pf-flecha pf-flecha--sig" data-pf-carrusel="1" aria-label="Siguientes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                </div>
            </div>
            <div class="pf-carrusel" data-pf-carrusel-pista>
                @foreach ($relacionados as $rel)
                    {{-- Conserva a dónde regresar (la categoría de donde se vino). --}}
                    <a class="pf-rel" href="{{ route('inventory.productos.show', ['producto' => $rel, 'return_to' => request()->query('return_to')]) }}">
                        <span class="pf-rel-foto">
                            @if ($rel->imagen_path)
                                <img src="{{ asset('storage/'.$rel->imagen_path) }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                        </span>
                        <b>{{ $rel->nombreVisible() }}</b>
                        <small>{{ trim(($rel->modelo ?: '').($rel->marca ? ' · '.$rel->marca : ''), ' ·') ?: 'Sin modelo ni marca' }}</small>
                        <span class="pf-rel-pie">
                            @if ($verPrecio)
                                <span class="pf-precio pf-precio--chico {{ $rel->precio === null ? 'is-sin' : '' }}">{{ \App\Support\PrecioVisible::texto($rel) }}</span>
                            @endif
                            <span class="pf-rel-stock {{ $rel->stock > 0 ? '' : 'is-cero' }}">{{ $rel->stock > 0 ? $rel->stock.' disponibles' : 'Sin stock' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

@include('partials.galeria')
@include('structure.gestion_Inventario._componentes')

@push('scripts')
<script>
(function () {
    // Buscar dentro de las piezas.
    const campo = document.getElementById('buscarPieza');
    if (campo) {
        const filas = Array.from(document.querySelectorAll('.pf-pieza[data-pieza]'));
        const cuenta = document.getElementById('buscarPiezaCuenta');
        campo.addEventListener('input', function () {
            const q = campo.value.trim().toLowerCase();
            let n = 0;
            filas.forEach(function (f) { const ok = !q || f.dataset.buscar.includes(q); f.hidden = !ok; if (ok) n++; });
            cuenta.textContent = q ? n + ' de ' + filas.length : '';
        });
    }

    // Carrusel de relacionados.
    const pista = document.querySelector('[data-pf-carrusel-pista]');
    document.querySelectorAll('[data-pf-carrusel]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!pista) return;
            const paso = pista.clientWidth * 0.8 * Number(b.dataset.pfCarrusel);
            const destino = Math.max(0, Math.min(pista.scrollWidth - pista.clientWidth, pista.scrollLeft + paso));
            pista.scrollTo({ left: destino, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            // Si el desplazamiento suave no avanza (ventana sin pintar), se asegura.
            setTimeout(function () { if (Math.abs(pista.scrollLeft - destino) > 4) pista.scrollLeft = destino; }, 450);
        });
    });
})();
</script>
@endpush
@endsection
