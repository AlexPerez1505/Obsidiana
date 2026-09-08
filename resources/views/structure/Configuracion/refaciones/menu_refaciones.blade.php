@extends('structure.Configuracion.layout')

@section('title', 'Refacciones')
@section('page-title', 'Refacciones')

@section('configuracion_content')
    {{-- Acciones de la pantalla: el titulo lo pinta la barra superior del layout --}}
    <div class="content-actions">
        <a href="{{ route('configuracion.refaciones.create') }}" class="btn">
            <x-gravityui-plus width="16" height="16" />
            Agregar refacción
        </a>
    </div>

    <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:22px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,168,255,0.12); color:#00A8FF;">
                <x-gravityui-box width="22" height="22" />
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalRefacciones) }}</div>
                <div class="stat-label">Refacciones registradas</div>
                <div class="stat-sublabel">Total activas</div>
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
                <div class="stat-number">{{ number_format($totalStock) }}</div>
                <div class="stat-label">Stock total</div>
                <div class="stat-sublabel">Unidades disponibles</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,0.12); color:#F97316;">
                <x-gravityui-star width="22" height="22" />
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalCompatible) }}</div>
                <div class="stat-label">Con compatibilidad</div>
                <div class="stat-sublabel">Datos registrados</div>
            </div>
        </div>
    </div>

    <div class="catalog-card">
        <div class="toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px;">
            <div class="search-box" style="flex:1; min-width:260px;">
                <x-gravityui-magnifier class="search-icon" width="17" height="17" />
                <input type="text" placeholder="Buscar refacción..." readonly>
            </div>
        </div>

        <div class="table-wrap">
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>SUBTIPO</th>
                        <th>NOMBRE</th>
                        <th>DESCRIPCIÓN</th>
                        <th>STOCK</th>
                        <th>COMPATIBLE CON</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refacciones as $refaccion)
                        <tr>
                            <td>
                                <div class="type-name">{{ $refaccion->subtype }}</div>
                            </td>
                            <td>
                                <div class="type-name">{{ $refaccion->name }}</div>
                            </td>
                            <td>
                                <div class="type-desc">{{ $refaccion->description ?? 'Sin descripción' }}</div>
                            </td>
                            <td>
                                <div class="type-name">{{ number_format($refaccion->stock) }}</div>
                            </td>
                            <td>
                                <div class="type-desc">{{ $refaccion->compatible_with ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="congress-menu">
                                    <button type="button" class="congress-menu-trigger" aria-label="Acciones de la refacción" aria-expanded="false">
                                        <x-gravityui-ellipsis-vertical />
                                    </button>
                                    <div class="congress-menu-dropdown">
                                        <a href="#" class="congress-menu-item" title="Ver" aria-label="Ver refacción" style="text-decoration:none;">
                                            <x-gravityui-eye />
                                            <span>Ver</span>
                                        </a>
                                        <a href="#" class="congress-menu-item" title="Editar" aria-label="Editar refacción" style="text-decoration:none;">
                                            <x-gravityui-pencil />
                                            <span>Editar</span>
                                        </a>
                                        <form method="POST" action="{{ route('configuracion.refaciones.destroy', $refaccion) }}" style="display:contents;" onsubmit="return confirm('¿Estás seguro de eliminar esta refacción?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="congress-menu-item danger" title="Eliminar" aria-label="Eliminar refacción" style="width:100%; text-align:left; background:transparent; border:none; cursor:pointer;">
                                                <x-gravityui-trash-bin />
                                                <span>Eliminar</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">No hay refacciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($refacciones->hasPages())
            <div style="margin-top:18px; display:flex; justify-content:flex-end;">
                {{ $refacciones->links('vendor.pagination.default') }}
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
