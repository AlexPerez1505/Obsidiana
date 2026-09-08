@extends('structure.Configuracion.layout')

@section('title', 'Tipos de Equipo')
@section('page-title', 'Tipos de Equipo')

@section('configuracion_content')
    {{-- Acciones de la pantalla: el titulo lo pinta la barra superior del layout --}}
    <div class="content-actions">
        <a href="{{ route('configuracion.tipos_equipo.create') }}" class="btn">
            <x-gravityui-plus width="16" height="16" />
            Agregar tipo de equipo
        </a>
    </div>

    <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:22px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,168,255,0.12); color:#00A8FF;">
                <x-gravityui-box width="22" height="22" />
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalTypes) }}</div>
                <div class="stat-label">Tipos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(124,58,237,0.12); color:#A855F7;">
                <x-gravityui-bars width="22" height="22" />
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalSubtypes) }}</div>
                <div class="stat-label">Subtipos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,0.12); color:#22C55E;">
                <x-gravityui-suitcase width="22" height="22" />
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalBrands) }}</div>
                <div class="stat-label">Marcas registradas</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,0.12); color:#F97316;">
                <x-gravityui-star width="22" height="22" />
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalModels) }}</div>
                <div class="stat-label">Modelos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
    </div>

    <div class="catalog-card">
        <div class="toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px;">
            <div class="search-box" style="flex:1; min-width:260px;">
                <x-gravityui-magnifier class="search-icon" width="17" height="17" />
                <input type="text" placeholder="Buscar tipo de equipo..." readonly>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <button type="button" class="toolbar-btn" disabled>
                    <x-gravityui-arrow-down-to-line width="16" height="16" />
                    Exportar
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>TIPO DE EQUIPO</th>
                        <th>SUBTIPOS</th>
                        <th>MARCAS</th>
                        <th>MODELOS</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentTypes as $type)
                        <tr>
                            <td>
                                <div class="type-cell">
                                    <div class="type-icon">
                                        <x-gravityui-box width="22" height="22" />
                                    </div>
                                    <div>
                                        <div class="type-name">{{ $type->name }}</div>
                                        <div class="type-desc">{{ $type->description ?? 'Sin descripción' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="name-list">
                                    @forelse ($type->subtypes_names as $name)
                                        <span class="name-pill">{{ $name }}</span>
                                    @empty
                                        <span class="name-empty">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="name-list">
                                    @forelse ($type->brands_names as $name)
                                        <span class="name-pill">{{ $name }}</span>
                                    @empty
                                        <span class="name-empty">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="name-list">
                                    @forelse ($type->models_names as $name)
                                        <span class="name-pill">{{ $name }}</span>
                                    @empty
                                        <span class="name-empty">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="congress-menu">
                                    <button type="button" class="congress-menu-trigger" aria-label="Acciones del tipo de equipo" aria-expanded="false">
                                        <x-gravityui-ellipsis-vertical />
                                    </button>
                                    <div class="congress-menu-dropdown">
                                        <a href="#" class="congress-menu-item" title="Ver" aria-label="Ver tipo de equipo" style="text-decoration:none;">
                                            <x-gravityui-eye />
                                            <span>Ver</span>
                                        </a>
                                        <a href="#" class="congress-menu-item" title="Editar" aria-label="Editar tipo de equipo" style="text-decoration:none;">
                                            <x-gravityui-pencil />
                                            <span>Editar</span>
                                        </a>
                                        <a href="#" class="congress-menu-item danger" title="Eliminar" aria-label="Eliminar tipo de equipo" style="text-decoration:none;">
                                            <x-gravityui-trash-bin />
                                            <span>Eliminar</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell">No hay tipos de equipo registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipmentTypes->hasPages())
            <div style="margin-top:18px; display:flex; justify-content:flex-end;">
                {{ $equipmentTypes->links('vendor.pagination.default') }}
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    (function () {
        var menus = document.querySelectorAll('.congress-menu');
        menus.forEach(function (menu) {
            menu._dd = menu.querySelector('.congress-menu-dropdown');
        });

        function closeMenu(menu) {
            var trigger = menu.querySelector('.congress-menu-trigger');
            var dd = menu._dd;
            if (dd && dd.parentNode !== menu) menu.appendChild(dd);
            if (dd) dd.removeAttribute('style');
            menu.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function openMenu(menu, trigger) {
            var dd = menu._dd;
            menus.forEach(function (m) {
                if (m !== menu && m.classList.contains('open')) closeMenu(m);
            });

            document.body.appendChild(dd);
            var rect = trigger.getBoundingClientRect();
            var w = 140;
            var left = rect.left;
            if (left + w > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - w - 8);
            }
            dd.style.display = 'flex';
            dd.style.position = 'fixed';
            dd.style.zIndex = '9999';
            dd.style.top = (rect.bottom + 4) + 'px';
            dd.style.left = left + 'px';
            dd.style.right = 'auto';

            menu.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        menus.forEach(function (menu) {
            var trigger = menu.querySelector('.congress-menu-trigger');
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                if (menu.classList.contains('open')) closeMenu(menu);
                else openMenu(menu, trigger);
            });
        });

        document.addEventListener('click', function () {
            menus.forEach(function (m) {
                if (m.classList.contains('open')) closeMenu(m);
            });
        });

        document.querySelectorAll('.congress-menu-dropdown').forEach(function (dd) {
            dd.addEventListener('click', function (e) { e.stopPropagation(); });
        });
    })();
</script>
@endpush
