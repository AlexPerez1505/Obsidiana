@extends('layouts.dashboard')
@section('title', 'Productos')
@section('page-title', 'Productos')
@section('page-sub', 'Inventario de equipos y stock disponible')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/productos-categorias.css') }}?v={{ filemtime(public_path('css/productos-categorias.css')) }}">
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

        /* ===================== Filtros del inventario ===================== */
        .inv-toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
        .inv-filter-wrap { flex:1 0 100%; min-width:0; order:2; }
        .inv-filter-toggle { width:100%; display:flex; align-items:center; gap:10px; padding:13px 16px; border:1px solid var(--primary); border-radius:14px; background:var(--surface); color:var(--text); cursor:pointer; text-align:left; }
        .inv-filter-toggle svg { width:18px; height:18px; color:var(--primary); flex:0 0 auto; }
        .inv-filter-toggle span { display:flex; flex-direction:column; gap:2px; }
        .inv-filter-toggle strong { font-size:15px; }
        .inv-filter-toggle small { color:var(--muted); font-size:12px; }
        .inv-filter-toggle .chev { margin-left:auto; font-size:18px; color:var(--muted); }
        .inv-filter-toggle[aria-expanded="true"] { border-radius:14px 14px 0 0; }
        .inv-filter-toggle[aria-expanded="true"] .chev { transform:rotate(180deg); }
        .inv-filter-panel { flex:1 1 100%; display:grid !important; grid-template-columns:minmax(250px,1.45fr) repeat(4,minmax(145px,1fr)) auto auto; gap:12px; align-items:end; padding:16px; border:1px solid var(--border); border-radius:16px; background:var(--surface); box-shadow:0 8px 24px rgba(15,23,42,.05); }
        .inv-filter-panel[hidden] { display:none !important; }
        .inv-filter-wrap .inv-filter-panel { border-top:0; border-radius:0 0 16px 16px; }
        .inv-filter-field { display:flex; flex-direction:column; gap:7px; min-width:0; color:var(--muted); font-size:11px; font-weight:750; letter-spacing:.06em; text-transform:uppercase; }
        .inv-filter-label { display:flex; align-items:center; gap:7px; color:var(--text); font-size:11px; font-weight:650; letter-spacing:.02em; text-transform:none; }
        .inv-filter-label svg { width:15px; height:15px; flex:0 0 auto; color:#9bb8dd; stroke-width:1.8; }
        .inv-filter-field input, .inv-filter-field select { width:100%; min-height:42px; box-sizing:border-box; padding:10px 12px; border:1px solid color-mix(in srgb, var(--primary) 16%, var(--border)); border-radius:10px; background:color-mix(in srgb, var(--surface-2) 88%, #071b38); color:var(--text); font:inherit; font-size:13px; letter-spacing:normal; text-transform:none; outline:none; transition:border-color .18s, box-shadow .18s, background .18s; }
        .inv-filter-field input::placeholder { color:var(--muted); opacity:.8; }
        .inv-filter-field input:focus, .inv-filter-field select:focus { border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent); background:var(--surface); }
        .inv-filter-search { position:relative; }
        .inv-filter-search input { padding-left:39px; }
        .inv-filter-search svg { position:absolute; left:12px; bottom:13px; width:17px; height:17px; color:var(--muted); pointer-events:none; }
        .inv-filter-actions { display:flex; align-items:center; gap:8px; height:42px; }
        .inv-filter-actions .btn { white-space:nowrap; height:42px; }
        .inv-filter-panel [data-catalogo-filtros] { display:contents !important; }
        .tipo-chips { gap:8px; margin:0 0 16px; padding:4px; }
        .tipo-chip { padding:8px 13px; border-radius:10px; background:transparent; font-size:12.5px; transition:background .18s, border-color .18s, color .18s, transform .18s; }
        .tipo-chip:hover { border-color:var(--primary); color:var(--primary); transform:translateY(-1px); }
        .tipo-chip.on { box-shadow:0 4px 12px color-mix(in srgb, var(--primary) 20%, transparent); }
        .tipo-chip span { padding:2px 6px; border-radius:999px; background:var(--surface-2); font-size:11px; }
        .tipo-chip.on span { background:rgba(255,255,255,.16); }
        .inv-actions { flex:1 0 100%; display:flex; align-items:center; justify-content:flex-end; gap:12px; flex-wrap:wrap; order:1; }
        .inv-actions .inv-filter-toggle { width:auto; padding:10px 14px; }
        .inv-actions .inv-filter-toggle small { display:none; }
        .inv-actions .inv-spacer { flex:1; }
        @media (max-width: 1100px) { .inv-filter-panel { grid-template-columns:repeat(2,minmax(180px,1fr)); } .inv-filter-search { grid-column:1 / -1; } .inv-filter-actions { justify-content:flex-start; } }
        @media (max-width: 600px) { .inv-filter-panel { grid-template-columns:1fr; padding:12px; } .inv-filter-search { grid-column:auto; } .inv-filter-actions { width:100%; } .inv-filter-actions .btn { flex:1; justify-content:center; } .inv-actions > * { flex:1 1 100%; justify-content:center; } .inv-actions .inv-spacer { display:none; } }

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
    @if (request()->query('from') === 'dashboard')
        <x-ui.page-header title="Inventario" :back="route('dashboard')" />
    @endif

    @php
        $tipoSel = $filters['tipo'] ?? '';
        $base = array_filter(['search' => $filters['search'] ?? null, 'ubicacion' => $filters['ubicacion'] ?? null, 'con_stock' => $filters['con_stock'] ?? null]);
    @endphp

    {{-- Dos vistas: al entrar, solo las categorías; al abrir una (o "Todos
         los productos", o al buscar/filtrar), la lista con su barra,
         paquetes y tabla. --}}
    @if ($vistaLista)
    @php
        // Icono por categoría (por palabra clave; si no, una caja).
        $iconoTipo = function (string $t): string {
            $t = mb_strtolower($t);
            $p = match (true) {
                str_contains($t, 'laparos') => '<path d="M4 20 14 10"/><path d="M14 10l3-3 3 3-3 3z"/><path d="M8 16l-2-2"/>',
                str_contains($t, 'artros') => '<path d="M17 4a3 3 0 0 0-3 3l-4 4a3 3 0 1 0 3 3l4-4a3 3 0 1 0 0-6z"/>',
                str_contains($t, 'endos') => '<path d="M4 4c6 0 6 8 12 8a4 4 0 0 1 0 8"/><circle cx="16" cy="20" r="1.5"/>',
                str_contains($t, 'hospital') => '<path d="M3 18V8M3 14h18v4M21 18v-4a3 3 0 0 0-3-3h-7v3"/><circle cx="7" cy="11" r="2"/>',
                str_contains($t, 'quirof') => '<path d="M14 4 4 14l3 3 10-10z"/><path d="M14 4l6 6-3 3"/><path d="M5 19l2-2"/>',
                str_contains($t, 'radiolog') => '<circle cx="12" cy="12" r="2"/><path d="M12 2a10 10 0 0 1 8.7 5L14 10.9M3.3 7A10 10 0 0 1 12 2M12 22a10 10 0 0 1-8.7-5L10 13.1M20.7 17A10 10 0 0 1 12 22"/>',
                str_contains($t, 'urolog') => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
                str_contains($t, 'otorrino') => '<path d="M7 9a5 5 0 1 1 10 0c0 3-3 4-3 7a3 3 0 0 1-6 0"/><path d="M10 9a2 2 0 1 1 4 0"/>',
                default => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22V12"/>',
            };
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';
        };
        $tituloVista = $tipoSel !== '' ? \Illuminate\Support\Str::title(mb_strtolower($tipoSel))
            : (($filters['search'] ?? '') !== '' ? 'Resultados de “'.$filters['search'].'”' : 'Todos los productos');
    @endphp

    {{-- Cambiar de categoría sin regresar al inicio, y buscar ahí mismo:
         la lupa convierte la barra en el buscador. --}}
    @php $buscando = ($filters['search'] ?? '') !== ''; @endphp
    <div class="pvb {{ $buscando ? 'is-abierto' : '' }}" data-pvb
         data-url-sug="{{ route('inventory.productos.sugerencias') }}"
         data-volver="{{ request()->getRequestUri() }}"
         data-buscando="{{ $buscando ? '1' : '0' }}"
         data-sin-busqueda="{{ route('inventory.productos.index', $tipoSel !== '' ? ['tipo' => $tipoSel] : ['ver' => 'todos']) }}">
    <nav class="pv-tipos" aria-label="Cambiar de categoría">
        <a class="pv-tipo" href="{{ route('inventory.productos.index') }}" title="Volver a las categorías">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            <span>Categorías</span>
        </a>
        <a class="pv-tipo {{ $tipoSel === '' ? 'is-on' : '' }}" href="{{ route('inventory.productos.index', $base + ['ver' => 'todos']) }}"
           @if ($tipoSel === '') aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Todos</span>
        </a>
        @foreach ($porTipo as $t => $n)
            <a class="pv-tipo {{ $tipoSel === $t ? 'is-on' : '' }}" href="{{ route('inventory.productos.index', $base + ['tipo' => $t]) }}"
               @if ($tipoSel === $t) aria-current="page" @endif title="{{ $n }} {{ $n === 1 ? 'producto' : 'productos' }}">
                {!! $iconoTipo((string) $t) !!}
                <span>{{ \Illuminate\Support\Str::title(mb_strtolower((string) $t)) }}</span>
            </a>
        @endforeach
    </nav>

    <button type="button" class="pvb-lupa" data-pvb-abrir aria-label="Buscar productos" title="Buscar productos" aria-expanded="{{ $buscando ? 'true' : 'false' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
    </button>

    {{-- El buscador se abre encima de la barra, sin cambiarle el tamaño. --}}
    <form class="pvb-form" method="GET" action="{{ route('inventory.productos.index') }}" role="search" data-pvb-form>
        <svg class="pvb-form-lupa" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <label class="pv-sr" for="pvb-q">Buscar productos</label>
        <input id="pvb-q" type="text" name="search" value="{{ $filters['search'] ?? '' }}" autocomplete="off" spellcheck="false"
               placeholder="Busca por nombre, modelo, marca, serie o etiqueta…" data-pvb-q enterkeyhint="search"
               role="combobox" aria-autocomplete="list" aria-controls="pvb-sug" aria-expanded="false">
        <kbd class="pvb-kbd" aria-hidden="true">Esc</kbd>
        <button type="button" class="pvb-x" data-pvb-cerrar aria-label="Cerrar el buscador" title="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </form>

    <div class="pvb-sug" id="pvb-sug" role="listbox" aria-label="Sugerencias" data-pvb-sug hidden></div>
    </div>

    <div class="pv-cab">
        <h2 class="pv-titulo">{{ $tituloVista }}. <span>{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}</span></h2>
        @if ($productos->hasPages())
            <div class="pv-nav">
                @if ($productos->onFirstPage())
                    <span class="pv-flecha is-off" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg></span>
                @else
                    <a class="pv-flecha" href="{{ $productos->previousPageUrl() }}" aria-label="Página anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg></a>
                @endif
                <span class="pv-pag">{{ $productos->currentPage() }} / {{ $productos->lastPage() }}</span>
                @if ($productos->hasMorePages())
                    <a class="pv-flecha pv-flecha--sig" href="{{ $productos->nextPageUrl() }}" aria-label="Página siguiente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                @else
                    <span class="pv-flecha pv-flecha--sig is-off" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                @endif
            </div>
        @endif
    </div>

    @endif

    {{-- Categorías (tipos de equipo) en tarjetas: la foto se sale de la
         tarjeta. Un clic abre la vista de esa categoría. --}}
    @if (! $vistaLista)
        <nav class="pcat" aria-label="Categorías de productos">
            <div class="pcat-top">
                <h2>Categorías</h2>
                <span class="pcat-n">{{ $tarjetasTipo->count() }}</span>
                @if ($tipoSel !== '')
                    <a class="pcat-todos" href="{{ route('inventory.productos.index', $base) }}">
                        Ver todas · {{ $porTipo->sum() }} productos
                    </a>
                @endif
            </div>
            <div class="pcat-grid">
                @foreach ($tarjetasTipo as $i => $cat)
                    @php $on = false; @endphp
                    <a class="pcat-card pcat-t{{ $i % 6 }} {{ $on ? 'is-on' : '' }}"
                       href="{{ route('inventory.productos.index', $on ? $base : $base + ['tipo' => $cat['tipo']]) }}"
                       @if ($on) aria-current="true" @endif
                       aria-label="{{ $cat['tipo'] }}: {{ $cat['productos'] }} {{ $cat['productos'] === 1 ? 'producto' : 'productos' }}, {{ $cat['disponibles'] }} {{ $cat['disponibles'] === 1 ? 'pieza disponible' : 'piezas disponibles' }}{{ $on ? '. Quitar filtro' : '' }}">
                        <span class="pcat-fondo" aria-hidden="true">
                            @switch($i % 6)
                                @case(0)
                                    <svg class="pcat-deco" viewBox="0 0 120 120"><g stroke-width="6" stroke-linecap="round"><path d="M30 40 90 22" stroke="#7aa7e8"/><path d="M24 58 84 40" stroke="#ee7b8f"/><path d="M34 74 94 56" stroke="#7fd1bd"/><path d="M20 92 80 74" stroke="#7aa7e8"/><path d="M28 108 88 90" stroke="#ee7b8f"/></g></svg>
                                    @break
                                @case(1)
                                    <svg class="pcat-deco" viewBox="0 0 120 120" fill="none" stroke-width="2"><path d="M10 100 30 70 22 64 42 34 34 28 54 0" stroke="#ee7b8f"/><path d="M30 110 50 80 42 74 62 44 54 38 74 10" stroke="#6cc8d9"/><path d="M50 118 70 88 62 82 82 52 74 46 94 16" stroke="#e8c45a"/></svg>
                                    @break
                                @case(2)
                                    <svg class="pcat-deco" viewBox="0 0 120 120" fill="none" stroke-width="2" stroke-dasharray="2 3"><circle cx="90" cy="18" r="9" stroke="#ee7b8f"/><circle cx="74" cy="40" r="9" stroke="#ee7b8f"/><circle cx="100" cy="46" r="9" stroke="#7fd1bd"/><circle cx="60" cy="66" r="9" stroke="#ee7b8f"/><circle cx="88" cy="74" r="9" stroke="#7fd1bd"/><circle cx="72" cy="98" r="9" stroke="#ee7b8f"/><circle cx="104" cy="102" r="9" stroke="#7fd1bd"/></svg>
                                    @break
                                @case(3)
                                    <svg class="pcat-deco" viewBox="0 0 120 120" fill="none" stroke="#7fd1a4" stroke-width="1.6"><path d="M20 120C30 60 70 40 120 30"/><path d="M34 120C44 70 80 54 120 48"/><path d="M50 120C58 84 88 70 120 66"/><path d="M8 120C18 52 60 22 120 12"/></svg>
                                    @break
                                @case(4)
                                    <svg class="pcat-deco" viewBox="0 0 120 120"><circle cx="40" cy="70" r="11" fill="#e8d36a"/><circle cx="84" cy="80" r="11" fill="#e8d36a"/><circle cx="36" cy="102" r="7" fill="#e8d36a"/><circle cx="66" cy="110" r="8" fill="#e8d36a"/><circle cx="104" cy="108" r="5" fill="#5fb87a"/><circle cx="70" cy="44" r="6" fill="none" stroke="#e8d36a" stroke-width="2"/></svg>
                                    @break
                                @default
                                    <svg class="pcat-deco" viewBox="0 0 120 120" stroke-linecap="round" stroke-width="7"><path d="M30 118 110 38" stroke="#5fb87a"/><path d="M54 118 118 54" stroke="#9cc3ee"/><path d="M6 110 70 46" stroke="#9cc3ee" opacity=".7"/><path d="M80 118 120 78" stroke="#5fb87a" opacity=".6"/></svg>
                            @endswitch
                        </span>
                        <span class="pcat-punto pcat-p1" aria-hidden="true"></span>
                        <span class="pcat-punto pcat-p2" aria-hidden="true"></span>

                        <span class="pcat-img" aria-hidden="true">
                            @if ($cat['imagen'])
                                <img src="{{ $cat['imagen'] }}" alt="" loading="lazy" decoding="async"
                                     onerror="this.parentNode.classList.add('sin');this.remove()">
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"/></svg>
                            @endif
                        </span>

                        <span class="pcat-flecha" aria-hidden="true">
                            @if ($on)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg>
                            @endif
                        </span>

                        <span class="pcat-txt">
                            <small>{{ $cat['disponibles'] }} {{ $cat['disponibles'] === 1 ? 'pieza disponible' : 'piezas disponibles' }}</small>
                            {{-- Palabras muy largas (Otorrinolaringología) con letra más chica: no se parten. --}}
                            @php $palabraLarga = collect(preg_split('/\s+/u', $cat['tipo']))->max(fn ($w) => mb_strlen($w)) > 16; @endphp
                            <strong class="{{ $palabraLarga ? 'pcat-largo' : '' }}">{{ \Illuminate\Support\Str::title(mb_strtolower($cat['tipo'])) }}</strong>
                            <em>{{ $cat['productos'] }} {{ $cat['productos'] === 1 ? 'producto' : 'productos' }}</em>
                        </span>
                    </a>
                @endforeach

                {{-- Todo el inventario, sin separar por categoría. --}}
                <a class="pcat-card pcat-card--todos" href="{{ route('inventory.productos.index', ['ver' => 'todos']) }}"
                   aria-label="Todos los productos: {{ $porTipo->sum() }}">
                    <span class="pcat-img pcat-img--todos" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    </span>
                    <span class="pcat-flecha" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                    <span class="pcat-txt">
                        <small>Sin separar por categoría</small>
                        <strong>Todos los productos</strong>
                        <em>{{ $porTipo->sum() }} {{ $porTipo->sum() === 1 ? 'producto' : 'productos' }}</em>
                    </span>
                </a>
            </div>
        </nav>
    @endif

    @if ($vistaLista)
    {{-- ===================== Paquetes en vitrina =====================
         Mismo diseño que los productos. La foto es un collage con las
         fotos de lo que trae el paquete; tocarla muestra el contenido. --}}
    @php $paquetesVista = $paquetesPorTipo->flatten(1)->values(); @endphp
    @if ($paquetesVista->isNotEmpty())
        <div class="pv-cab pv-cab--sec">
            <h2 class="pv-titulo pv-titulo--sec">Paquetes. <span>{{ $paquetesVista->count() }}</span></h2>
            <a class="pv-admin" href="{{ route('inventory.paquetes.index') }}">Administrar paquetes</a>
        </div>
        <div class="pv-grid pv-grid--paq">
            @foreach ($paquetesVista as $paq)
                @php
                    $fotosPaq = $paq->productos->map(fn ($p) => $p->imagen_path ? asset('storage/'.$p->imagen_path) : null)
                        ->concat($paq->equipos->map(fn ($e) => $e->imagen ? asset('storage/'.$e->imagen) : null))
                        ->filter()->values();
                    if ($paq->imagen) { $fotosPaq->prepend(asset('storage/'.$paq->imagen)); }
                    $fotosPaq = $fotosPaq->take(3);
                    $contenidoPaq = $paq->contenido();
                    $piezasPaq = collect($contenidoPaq)->sum('cantidad');
                    $precioPaq = (float) $paq->precio > 0 ? (float) $paq->precio : $paq->precioCalculado();
                @endphp
                <article class="pv-card pv-card--paq">
                    <div class="pv-media pv-media--paq pv-fotos-{{ max(1, $fotosPaq->count()) }}" role="button" tabindex="0"
                         title="Ver lo que trae" aria-label="Ver lo que trae el paquete {{ $paq->nombre }}"
                         onclick="const m = document.getElementById('paquete-modal-{{ $paq->id }}'); m.style.display='flex'; m.querySelector('.pvm-x').focus();"
                         onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }">
                        @forelse ($fotosPaq as $foto)
                            <span class="pv-foto"><img src="{{ $foto }}" alt="" loading="lazy" decoding="async"></span>
                        @empty
                            <span class="pv-sinfoto" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22V12"/></svg></span>
                        @endforelse

                        <span class="pv-etiquetas">
                            <span class="pv-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/></svg>Paquete</span>
                            @if (! $paq->activo)
                                <span class="pv-chip pv-chip--tipo">Inactivo</span>
                            @endif
                        </span>
                    </div>

                    <div class="pv-info">
                        <div class="pv-puntos" aria-hidden="true"></div>
                        <div class="pv-nombre">
                            <h3>{{ $paq->nombre }}</h3>
                            @canany(['inventario.editar', 'inventario.eliminar'])
                            <div class="row-menu" data-row-menu>
                                <button type="button" class="row-menu-btn" data-row-menu-toggle aria-haspopup="true" aria-expanded="false"
                                        aria-label="Acciones del paquete {{ $paq->nombre }}">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                    @can('inventario.editar')
                                        <a href="{{ route('inventory.paquetes.edit', $paq) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Editar paquete
                                        </a>
                                    @endcan
                                    @can('inventario.eliminar')
                                        <form method="POST" action="{{ route('inventory.paquetes.destroy', $paq) }}" data-confirm="¿Eliminar este paquete? Los productos no se borran." style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="es-danger" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                                Eliminar paquete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                            @endcanany
                        </div>
                        <p class="pv-sub pv-sub--paq">{{ collect($contenidoPaq)->map(fn ($l) => $l['cantidad'].'× '.$l['nombre'])->implode(' · ') ?: 'Sin productos' }}</p>

                        <div class="pv-pie">
                            @if (\App\Support\PrecioVisible::para())
                                <span class="pv-precio {{ $precioPaq > 0 ? '' : 'is-sin' }}" @if ((float) $paq->precio <= 0 && $precioPaq > 0) title="Suma de los precios de lo que trae" @endif>
                                    {{ $precioPaq > 0 ? '$'.number_format($precioPaq, 2) : 'Sin precio definido' }}
                                </span>
                            @endif
                            <span class="pv-stock pv-stock--paq">
                                {{ count($contenidoPaq) }} {{ count($contenidoPaq) === 1 ? 'artículo' : 'artículos' }}{{ $piezasPaq > count($contenidoPaq) ? ' · '.$piezasPaq.' pz' : '' }}
                            </span>
                        </div>
                    </div>
                </article>

                {{-- Lo que trae el paquete: encabezado con su collage y total,
                     y un renglón por artículo con foto, cantidad y existencias. --}}
                <div id="paquete-modal-{{ $paq->id }}" class="unidades-modal-overlay pvm-fondo" role="dialog" aria-modal="true" aria-labelledby="pvm-t-{{ $paq->id }}"
                     onclick="if (event.target === this) this.style.display='none'"
                     onkeydown="if (event.key === 'Escape') this.style.display='none'">
                    <div class="pvm">
                        <header class="pvm-cab">
                            <div class="pvm-mini pv-fotos-{{ max(1, $fotosPaq->count()) }}" aria-hidden="true">
                                @forelse ($fotosPaq as $foto)
                                    <span><img src="{{ $foto }}" alt="" loading="lazy" onerror="this.remove()"></span>
                                @empty
                                    <span></span>
                                @endforelse
                            </div>
                            <div class="pvm-cab-t">
                                <span class="pvm-etq">Paquete{{ $paq->activo ? '' : ' · Inactivo' }}</span>
                                <h3 id="pvm-t-{{ $paq->id }}">{{ $paq->nombre }}</h3>
                                <p>{{ count($contenidoPaq) }} {{ count($contenidoPaq) === 1 ? 'artículo' : 'artículos' }} · {{ $piezasPaq }} {{ $piezasPaq === 1 ? 'pieza' : 'piezas' }}
                                    @if (\App\Support\PrecioVisible::para())
                                        · <b>{{ $precioPaq > 0 ? '$'.number_format($precioPaq, 2) : 'Sin precio' }}</b>
                                    @endif
                                </p>
                            </div>
                            <button type="button" class="pvm-x" aria-label="Cerrar" onclick="document.getElementById('paquete-modal-{{ $paq->id }}').style.display='none'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </header>

                        <ul class="pvm-lista">
                            @foreach ($paq->productos as $p)
                                <li class="pvm-item">
                                    <span class="pvm-foto">
                                        @if ($p->imagen_path)
                                            <img src="{{ asset('storage/'.$p->imagen_path) }}" alt="" loading="lazy" onerror="this.remove()">
                                        @endif
                                    </span>
                                    <span class="pvm-cant">{{ (int) $p->pivot->cantidad }}×</span>
                                    <span class="pvm-txt">
                                        <b>{{ $p->nombreVisible() }}</b>
                                        <small>{{ trim(($p->modelo ?: '').($p->marca ? ' · '.$p->marca : ''), ' ·') ?: 'Sin modelo ni marca' }}</small>
                                    </span>
                                    <span class="pvm-stock {{ $p->stock >= (int) $p->pivot->cantidad ? '' : 'is-falta' }}"
                                          title="{{ $p->stock >= (int) $p->pivot->cantidad ? 'Alcanza para este paquete' : 'No alcanza: el paquete pide '.(int) $p->pivot->cantidad }}">
                                        {{ $p->stock }} en stock
                                    </span>
                                    <a class="pvm-ficha" href="{{ route('inventory.productos.show', $p) }}" aria-label="Ver ficha de {{ $p->nombreVisible() }}" title="Ver ficha">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg>
                                    </a>
                                </li>
                            @endforeach
                            @foreach ($paq->equipos as $e)
                                <li class="pvm-item">
                                    <span class="pvm-foto">
                                        @if ($e->imagen)
                                            <img src="{{ asset('storage/'.$e->imagen) }}" alt="" loading="lazy" onerror="this.remove()">
                                        @endif
                                    </span>
                                    <span class="pvm-cant">{{ (int) $e->pivot->cantidad }}×</span>
                                    <span class="pvm-txt">
                                        <b>{{ trim(($e->subtipo ?: $e->tipo) ?? '') ?: 'Equipo' }}</b>
                                        <small>{{ trim(($e->modelo ?: '').($e->marca ? ' · '.$e->marca : ''), ' ·') ?: 'Sin modelo ni marca' }}</small>
                                    </span>
                                    <span class="pvm-stock pvm-stock--eq">Equipo</span>
                                    <span></span>
                                </li>
                            @endforeach
                        </ul>

                        <footer class="pvm-pie">
                            @can('inventario.editar')
                                <a class="pvm-btn" href="{{ route('inventory.paquetes.edit', $paq) }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    Editar paquete
                                </a>
                            @endcan
                            <button type="button" class="pvm-btn pvm-btn--prim" onclick="document.getElementById('paquete-modal-{{ $paq->id }}').style.display='none'">Listo</button>
                        </footer>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pv-cab pv-cab--sec">
            <h2 class="pv-titulo pv-titulo--sec">Productos. <span>{{ $productos->total() }}</span></h2>
        </div>
    @endif

    {{-- ===================== Vitrina de productos =====================
         Foto grande, nombre, modelo y marca, precio (solo para quien puede
         verlo) y piezas disponibles. Tocar la foto abre sus unidades; la
         palomita lo marca para etiquetas o paquete; ⋮ tiene las acciones. --}}
    <div class="pv-sel">
        <label class="pv-sel-todos">
            <input type="checkbox" id="sel-todos" aria-label="Marcar todos los productos de la página">
            <span>Marcar todos</span>
        </label>
    </div>

    <div class="pv-grid">
        @forelse ($productos as $producto)
            @php
                $enCongresoP = $producto->serialesDisponibles->whereNotNull('congress_id')->count();
                $enAlmacenP = $producto->serialesDisponibles->count() - $enCongresoP;
                $precio = \App\Support\PrecioVisible::texto($producto);
            @endphp
            <article class="pv-card">
                @php $fichaUrl = route('inventory.productos.show', ['producto' => $producto, 'return_to' => request()->getRequestUri()]); @endphp
                <div class="pv-media" role="link" tabindex="0" title="Ver la ficha"
                     aria-label="Ver la ficha de {{ $producto->nombreVisible() }}"
                     onclick="location.href = {{ \Illuminate\Support\Js::from($fichaUrl) }}"
                     onkeydown="if (event.key === 'Enter') { event.preventDefault(); this.click(); }">
                    @if ($producto->imagen_path)
                        <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="{{ $producto->nombreVisible() }}" loading="lazy" decoding="async">
                    @else
                        <span class="pv-sinfoto" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 17-5-5-6 6"/></svg></span>
                    @endif

                    <span class="pv-etiquetas">
                        @if ($tipoSel === '' && $producto->tipo_equipo)
                            <span class="pv-chip pv-chip--tipo">{{ \Illuminate\Support\Str::title(mb_strtolower($producto->tipo_equipo)) }}</span>
                        @endif
                        @if ($producto->en_tienda)
                            <span class="pv-chip pv-chip--web"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>En la web</span>
                        @endif
                    </span>

                    <label class="pv-check" title="Marcar para etiquetas o paquete" onclick="event.stopPropagation()">
                        <input type="checkbox" class="producto-checkbox" value="{{ $producto->id }}" onchange="actualizarSeleccionPaquete()"
                               aria-label="Marcar {{ $producto->nombreVisible() }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                    </label>
                </div>

                <div class="pv-info">
                    {{-- Dónde están sus piezas: verde en almacén, azul en congreso.
                         El renglón siempre ocupa su lugar para que todo alinee. --}}
                    <div class="pv-puntos" @if ($enAlmacenP + $enCongresoP) aria-label="{{ $enAlmacenP }} en almacén{{ $enCongresoP ? ', '.$enCongresoP.' en congreso' : '' }}" @else aria-hidden="true" @endif>
                        @if ($enAlmacenP > 0)<span class="pv-punto pv-punto--almacen" title="{{ $enAlmacenP }} en almacén"></span>@endif
                        @if ($enCongresoP > 0)<span class="pv-punto pv-punto--congreso" title="{{ $enCongresoP }} en congreso"></span>@endif
                    </div>

                    <div class="pv-nombre">
                        <h3><a href="{{ $fichaUrl }}">{{ $producto->nombreVisible() }}</a></h3>
                        <div class="row-menu" data-row-menu>
                            <button type="button" class="row-menu-btn" data-row-menu-toggle aria-haspopup="true" aria-expanded="false"
                                    aria-label="Acciones de {{ $producto->nombreVisible() }}">
                                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                            </button>
                            <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                <a href="{{ route('inventory.productos.show', $producto) }}" role="menuitem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Ver ficha
                                </a>
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
                    </div>
                    <p class="pv-sub">{{ trim(($producto->modelo ?: '').($producto->marca ? ' · '.$producto->marca : ''), ' ·') ?: 'Sin modelo ni marca' }}</p>
                    @if ($producto->referencia)
                        <p class="pv-ref">Ref. {{ $producto->referencia }}</p>
                    @endif

                    <div class="pv-pie">
                        @if ($precio !== null)
                            <span class="pv-precio {{ $producto->precio === null ? 'is-sin' : '' }}">{{ $precio }}</span>
                        @endif
                        <span class="pv-stock {{ $producto->stock > 0 ? '' : 'is-cero' }}">
                            <span class="pv-stock-punto" aria-hidden="true"></span>
                            {{ $producto->stock > 0 ? $producto->stock.' '.($producto->stock === 1 ? 'disponible' : 'disponibles') : 'Sin stock' }}
                        </span>
                    </div>
                </div>
            </article>
        @empty
            <p class="pv-vacio">No hay productos en esta categoría.</p>
        @endforelse
    </div>
    @include('partials._paginacion', ['paginator' => $productos])

    {{-- Aparece solo cuando hay productos marcados con la palomita. --}}
    <div class="pv-barra" id="pv-barra" hidden role="region" aria-label="Productos marcados">
        <span class="pv-barra-n"><b id="contador-seleccionados">0</b> <span id="pv-barra-palabra">marcados</span></span>
        <button type="button" id="btn-etiquetas" class="pv-barra-btn" onclick="imprimirEtiquetas()" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            <span class="pv-l">Imprimir etiquetas</span><span class="pv-c">Etiquetas</span> <span class="pv-sr">(<span id="contador-etiquetas">0</span>)</span>
        </button>
        <button type="button" id="btn-agrupar-paquete" class="pv-barra-btn pv-barra-btn--prim" onclick="agruparEnPaquete()" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            <span class="pv-l">Agrupar en paquete</span><span class="pv-c">Paquete</span>
        </button>
        <button type="button" class="pv-barra-x" onclick="desmarcarTodo()" aria-label="Desmarcar todo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>

    @endif

    {{-- Visor de la evidencia de una pieza: compartido con el detalle de Entrada / Salida. --}}
    @include('partials.galeria')

    <script>
        (function () {
            const toggle = document.getElementById('toggleFiltrosProductos');
            const panel = document.getElementById('filtrosProductos');
            if (!toggle || !panel) return;
            toggle.addEventListener('click', function () {
                const abierto = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', abierto ? 'false' : 'true');
                panel.hidden = abierto;
            });
        })();

        function espejoPaquete(casilla) {
            document.querySelectorAll('.producto-checkbox[value="' + casilla.value + '"]').forEach(c => { c.checked = casilla.checked; });
            actualizarSeleccionPaquete();
        }

        function actualizarSeleccionPaquete() {
            const n = new Set(Array.from(document.querySelectorAll('.producto-checkbox:checked')).map(c => c.value)).size;
            const barra = document.getElementById('pv-barra');
            if (!barra) return;
            document.getElementById('contador-seleccionados').textContent = n;
            document.getElementById('pv-barra-palabra').textContent = n === 1 ? 'marcado' : 'marcados';
            document.getElementById('contador-etiquetas').textContent = n;
            document.getElementById('btn-etiquetas').disabled = n === 0;
            document.getElementById('btn-agrupar-paquete').disabled = n === 0;
            barra.hidden = n === 0;
            const todos = document.getElementById('sel-todos');
            const total = document.querySelectorAll('.pv-card .producto-checkbox').length;
            if (todos) { todos.checked = n > 0 && n === total; todos.indeterminate = n > 0 && n < total; }
        }

        function desmarcarTodo() {
            document.querySelectorAll('.producto-checkbox:checked').forEach(c => { c.checked = false; });
            actualizarSeleccionPaquete();
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
            if (e.target.id === 'sel-todos') casillas = document.querySelectorAll('.pv-card .producto-checkbox');
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

    <script src="{{ asset('js/productos-buscador.js') }}?v={{ filemtime(public_path('js/productos-buscador.js')) }}"></script>
    @include('partials.row-menu')
@endsection
