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

        @if (session('status_category'))
            <div class="alert alert--ok" style="margin:16px 0 0;">{{ session('status_category') }}</div>
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
                        <div class="congress-menu">
                            <button type="button" class="congress-menu-trigger" aria-label="Acciones de la categoría" aria-expanded="false">
                                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                            </button>
                            <div class="congress-menu-dropdown">
                                <a href="{{ route('configuracion.categorias.edit', $category) }}" class="congress-menu-item" title="Editar" aria-label="Editar categoría" style="text-decoration:none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    <span>Editar</span>
                                </a>
                                <a href="{{ route('configuracion.categorias.delete', $category) }}" class="congress-menu-item danger" title="Eliminar" aria-label="Eliminar categoría" style="text-decoration:none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span>Eliminar</span>
                                </a>
                            </div>
                        </div>
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

        @if (session('status_congress'))
            <div class="alert alert--ok" style="margin:16px 0 0;">{{ session('status_congress') }}</div>
        @endif

        @if ($congresses->isEmpty())
            <div class="catalog-empty">Todavía no hay congresos registrados.</div>
        @else
            <div class="congress-table-wrap" style="margin-top:18px;">
                <table class="congress-table" id="congressTable">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
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

                                // Determinar el primer archivo y su tipo
                                $firstFile = is_array($congress->image_path) ? ($congress->image_path[0] ?? null) : $congress->image_path;
                                $fileExists = $firstFile && \Illuminate\Support\Facades\Storage::disk('public')->exists($firstFile);
                                $fileExt = $firstFile ? strtolower(pathinfo($firstFile, PATHINFO_EXTENSION)) : '';
                                $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp']);
                                $fileCount = is_array($congress->image_path) ? count($congress->image_path) : 0;
                            @endphp
                            <tr data-search="{{ strtolower($congress->name . ' ' . ($congress->category?->name ?? '') . ' ' . ($congress->address ?? '') . ' ' . $estadoLabel) }}">
                                <td>
                                    @if ($fileExists && $isImage)
                                        <img src="{{ asset('storage/' . $firstFile) }}" alt="{{ $congress->name }}" class="congress-thumb">
                                    @elseif ($fileExists)
                                        <div class="congress-thumb-file" title="{{ basename($firstFile) }}">
                                            @if ($fileExt === 'pdf')
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg>
                                            @elseif (in_array($fileExt, ['doc', 'docx']))
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13l-2 4-2-4M10 17v-4M10 15h2.5"/></svg>
                                            @elseif (in_array($fileExt, ['xls', 'xlsx']))
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m8 17 2-4 2 4M8 13v4M14 13l-2 4 2 4"/></svg>
                                            @elseif (in_array($fileExt, ['ppt', 'pptx']))
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><rect x="8" y="13" width="8" height="4" rx="1"/><path d="M12 13v4"/></svg>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                                            @endif
                                            <span class="file-ext">{{ strtoupper($fileExt) }}</span>
                                        </div>
                                    @else
                                        <div class="congress-thumb-placeholder" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                        </div>
                                    @endif
                                    @if ($fileCount > 1)
                                        <span class="file-count-badge">+{{ $fileCount - 1 }}</span>
                                    @endif
                                </td>
                                <td><span class="congress-name">{{ $congress->name }}</span></td>
                                <td><span class="congress-category">{{ $congress->category?->name ?? '—' }}</span></td>
                                <td>
                                    <span class="congress-dates">
                                        {{ $congress->start_date->format('d/m/Y') }}<br>
                                        {{ $congress->end_date->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td><span class="congress-place">{{ $congress->address ?? '—' }}</span></td>
                                <td><span class="congress-badge {{ $estado }}">{{ $estadoLabel }}</span></td>
                                <td>
                                    <div class="congress-menu">
                                        <button type="button" class="congress-menu-trigger" aria-label="Acciones del congreso" aria-expanded="false">
                                            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                                        </button>
                                        <div class="congress-menu-dropdown">
                                            <a href="{{ route('configuracion.congresos.show', $congress) }}" class="congress-menu-item" title="Ver" aria-label="Ver congreso" style="text-decoration:none;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                <span>Ver</span>
                                            </a>
                                            <a href="{{ route('configuracion.congresos.edit', $congress) }}" class="congress-menu-item" title="Editar" aria-label="Editar congreso" style="text-decoration:none;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                <span>Editar</span>
                                            </a>
                                            <a href="{{ route('configuracion.congresos.delete', $congress) }}" class="congress-menu-item danger" title="Eliminar" aria-label="Eliminar congreso" style="text-decoration:none;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span>Eliminar</span>
                                            </a>
                                        </div>
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

            // ---- Menú de tres puntos (acciones de congreso) ----
            var menus = document.querySelectorAll('.congress-menu');
            menus.forEach(function (menu) {
                var trigger = menu.querySelector('.congress-menu-trigger');
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    // Cerrar otros menús abiertos
                    menus.forEach(function (m) {
                        if (m !== menu) {
                            m.classList.remove('open');
                            m.querySelector('.congress-menu-trigger').setAttribute('aria-expanded', 'false');
                        }
                    });
                    // Posición fija para menús dentro de la lista de categorías
                    if (menu.closest('.category-list')) {
                        var rect = trigger.getBoundingClientRect();
                        menu.style.setProperty('--menu-top', (rect.bottom + 4) + 'px');
                        menu.style.setProperty('--menu-right', (window.innerWidth - rect.right) + 'px');
                    }
                    var open = menu.classList.toggle('open');
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            });
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function () {
                menus.forEach(function (m) {
                    m.classList.remove('open');
                    m.querySelector('.congress-menu-trigger').setAttribute('aria-expanded', 'false');
                });
            });
            // Evitar que el clic dentro del menú lo cierre inmediatamente
            document.querySelectorAll('.congress-menu-dropdown').forEach(function (dd) {
                dd.addEventListener('click', function (e) { e.stopPropagation(); });
            });
        })();
    </script>
@endsection
