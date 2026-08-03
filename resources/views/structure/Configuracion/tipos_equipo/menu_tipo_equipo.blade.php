@extends('structure.Configuracion.layout')

@section('title', 'Tipos de Equipo')

@section('configuracion_content')
    <div class="catalog-card" style="margin-bottom:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 class="page-title" style="margin:0; font-size:24px; font-weight:700;">Tipos de Equipo</h2>
                <p class="page-subtitle" style="margin:4px 0 0; font-size:14px;">Catálogo de tipos de equipo disponibles</p>
            </div>
            <a href="{{ route('configuracion.tipos_equipo.create') }}" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Agregar tipo de equipo
            </a>
        </div>
    </div>

    <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:22px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,168,255,0.12); color:#00A8FF;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalTypes) }}</div>
                <div class="stat-label">Tipos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(124,58,237,0.12); color:#A855F7;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalSubtypes) }}</div>
                <div class="stat-label">Subtipos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,0.12); color:#22C55E;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalBrands) }}</div>
                <div class="stat-label">Marcas registradas</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,0.12); color:#F97316;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
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
                <svg class="search-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" placeholder="Buscar tipo de equipo..." readonly>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <button type="button" class="toolbar-btn" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
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
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>
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
                                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                                    </button>
                                    <div class="congress-menu-dropdown">
                                        <a href="#" class="congress-menu-item" title="Ver" aria-label="Ver tipo de equipo" style="text-decoration:none;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <span>Ver</span>
                                        </a>
                                        <a href="#" class="congress-menu-item" title="Editar" aria-label="Editar tipo de equipo" style="text-decoration:none;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            <span>Editar</span>
                                        </a>
                                        <a href="#" class="congress-menu-item danger" title="Eliminar" aria-label="Eliminar tipo de equipo" style="text-decoration:none;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
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
