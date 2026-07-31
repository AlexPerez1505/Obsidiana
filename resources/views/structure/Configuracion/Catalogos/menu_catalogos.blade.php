@extends('structure.Configuracion.layout')

@section('title', 'Catálogos')

@section('configuracion_content')
    @php
        $categoryColors = [
            ['background' => 'rgba(236, 72, 153, .13)', 'color' => '#ec4899'],
            ['background' => 'rgba(99, 102, 241, .13)', 'color' => '#6366f1'],
            ['background' => 'rgba(34, 197, 94, .13)', 'color' => '#22c55e'],
            ['background' => 'rgba(245, 158, 11, .14)', 'color' => '#f59e0b'],
            ['background' => 'rgba(6, 182, 212, .13)', 'color' => '#06b6d4'],
        ];
    @endphp

    <div class="catalog-grid">
    <div class="card catalog-card">
        <div class="catalog-header">
            <h2 class="page-title">Categorías</h2>
            <div class="catalog-header-actions">
                <span class="catalog-count">{{ $categories->count() }} {{ $categories->count() === 1 ? 'categoría' : 'categorías' }}</span>
                <button type="button" class="catalog-search-btn" id="catalogSearchBtn" aria-label="Buscar categorías" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="catalog-search-wrap" id="catalogSearchWrap">
            <div class="catalog-search">
                <span class="search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" id="catalogSearchInput" placeholder="Buscar categoría por nombre o ID..." autocomplete="off">
            </div>
        </div>
        <p class="page-sub">Consulta las categorías registradas en el sistema.</p>

        @if (session('status'))
            <div class="alert alert--ok" style="margin:16px 0 0;">{{ session('status') }}</div>
        @endif

        @if ($categories->isEmpty())
            <div class="catalog-empty">Todavía no hay categorías registradas.</div>
        @else
            <div class="category-list" id="categoryList">
                @foreach ($categories as $category)
                    @php $color = $categoryColors[$loop->index % count($categoryColors)]; @endphp
                    <div class="category-item" data-search="{{ strtolower($category->name . ' ' . $category->id) }}">
                        <div class="category-icon" style="background:{{ $color['background'] }}; color:{{ $color['color'] }};">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/>
                                <path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>
                            </svg>
                        </div>
                        <div class="category-info">
                            <span class="category-name">{{ $category->name }}</span>
                            <span class="category-meta">Categoría registrada · ID {{ $category->id }}</span>
                        </div>
                        <span class="category-arrow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="catalog-search-noresult" id="catalogNoResult">No se encontraron categorías.</div>
        @endif

        <a href="{{ route('configuracion.categorias.create') }}" class="catalog-create">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Crear nueva categoría
        </a>
    </div>

    {{-- ===== Tabla de congresos ===== --}}
    <div class="card catalog-card congress-section">
        <div class="catalog-header">
            <h2 class="page-title">Congresos</h2>
            <div class="catalog-header-actions">
                <span class="catalog-count">{{ $congresses->count() }} {{ $congresses->count() === 1 ? 'congreso' : 'congresos' }}</span>
                <button type="button" class="catalog-search-btn" id="congressSearchBtn" aria-label="Buscar congresos" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="catalog-search-wrap" id="congressSearchWrap">
            <div class="catalog-search">
                <span class="search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" id="congressSearchInput" placeholder="Buscar congreso por nombre, lugar o estado..." autocomplete="off">
            </div>
        </div>
        <p class="page-sub">Gestiona los congresos registrados en el sistema.</p>

        @if ($congresses->isEmpty())
            <div class="catalog-empty">Todavía no hay congresos registrados.</div>
        @else
            <div class="congress-table-wrap" style="margin-top:18px;">
                <table class="congress-table" id="congressTable">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Fechas</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($congresses as $congress)
                            @php
                                $today = now()->startOfDay();
                                $start = $congress->start_date->startOfDay();
                                $end = $congress->end_date->startOfDay();
                                if ($today < $start) { $estado = 'upcoming'; $estadoLabel = 'Programado'; }
                                elseif ($today > $end) { $estado = 'finished'; $estadoLabel = 'Finalizado'; }
                                else { $estado = 'active'; $estadoLabel = 'En curso'; }
                            @endphp
                            <tr data-search="{{ strtolower($congress->name . ' ' . ($congress->category?->name ?? '') . ' ' . $estadoLabel) }}">
                                <td>
                                    @if ($congress->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($congress->image_path))
                                        <img src="{{ asset('storage/' . $congress->image_path) }}" alt="{{ $congress->name }}" class="congress-thumb">
                                    @else
                                        <div class="congress-thumb-placeholder" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                        </div>
                                    @endif
                                </td>
                                <td><span class="congress-name">{{ $congress->name }}</span></td>
                                <td>
                                    <span class="congress-dates">
                                        {{ $congress->start_date->format('d/m/Y') }} — {{ $congress->end_date->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td><span class="congress-place">{{ $congress->category?->name ?? '—' }}</span></td>
                                <td><span class="congress-badge {{ $estado }}">{{ $estadoLabel }}</span></td>
                                <td>
                                    <div class="congress-actions" style="justify-content:flex-end;">
                                        <button type="button" class="congress-action-btn" title="Ver" aria-label="Ver congreso">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                        <button type="button" class="congress-action-btn" title="Editar" aria-label="Editar congreso">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                        <button type="button" class="congress-action-btn danger" title="Eliminar" aria-label="Eliminar congreso">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="catalog-search-noresult" id="congressNoResult">No se encontraron congresos.</div>
        @endif

        <a href="{{ route('configuracion.congresos.create') }}" class="catalog-create">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Crear nuevo congreso
        </a>
    </div>
    </div>

    <script>
        (function () {
            // ---- Buscador de categorías ----
            var catBtn = document.getElementById('catalogSearchBtn');
            var catWrap = document.getElementById('catalogSearchWrap');
            var catInput = document.getElementById('catalogSearchInput');
            var catList = document.getElementById('categoryList');
            var catNoResult = document.getElementById('catalogNoResult');
            if (catBtn && catWrap && catInput) {
                var catItems = catList ? catList.querySelectorAll('.category-item') : [];
                catBtn.addEventListener('click', function () {
                    var open = catWrap.classList.toggle('open');
                    catBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) { catInput.focus(); } else { catInput.value = ''; filterCats(''); }
                });
                function filterCats(q) {
                    q = q.trim().toLowerCase();
                    var visible = 0;
                    catItems.forEach(function (it) {
                        var match = it.getAttribute('data-search').indexOf(q) !== -1;
                        it.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    if (catNoResult) catNoResult.style.display = visible === 0 ? 'block' : 'none';
                }
                catInput.addEventListener('input', function () { filterCats(this.value); });
            }

            // ---- Buscador de congresos ----
            var conBtn = document.getElementById('congressSearchBtn');
            var conWrap = document.getElementById('congressSearchWrap');
            var conInput = document.getElementById('congressSearchInput');
            var conTable = document.getElementById('congressTable');
            var conNoResult = document.getElementById('congressNoResult');
            if (conBtn && conWrap && conInput) {
                var conRows = conTable ? conTable.querySelectorAll('tbody tr') : [];
                conBtn.addEventListener('click', function () {
                    var open = conWrap.classList.toggle('open');
                    conBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) { conInput.focus(); } else { conInput.value = ''; filterCons(''); }
                });
                function filterCons(q) {
                    q = q.trim().toLowerCase();
                    var visible = 0;
                    conRows.forEach(function (row) {
                        var match = row.getAttribute('data-search').indexOf(q) !== -1;
                        row.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    if (conNoResult) conNoResult.style.display = visible === 0 ? 'block' : 'none';
                }
                conInput.addEventListener('input', function () { filterCons(this.value); });
            }
        })();
    </script>
@endsection
