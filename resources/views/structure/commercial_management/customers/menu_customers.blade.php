@extends('structure.commercial_management.layout')

@section('title', 'Clientes')

@section('commercial_content')
    {{-- Header --}}
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:16px;">
                <div class="header-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" style="color:#fff;">
                        <circle cx="9" cy="8" r="4"/>
                        <circle cx="17" cy="8" r="3" opacity="0.8"/>
                        <path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/>
                        <path d="M13 14.5c3.5 0 6 2 6 5.5" opacity="0.8"/>
                    </svg>
                </div>
                <div>
                    <h2 class="header-title" style="display:flex;align-items:center;">
                        Clientes
                        <span class="total-badge">Total: {{ $customers->count() }}</span>
                    </h2>
                    <p class="header-subtitle">Administra y consulta información de tus clientes</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn-glass">
                    Más acciones
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <a href="{{ route('commercial.clientes.create') }}" class="btn-gradient">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Registrar cliente
                </a>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div class="search-box" style="flex:1; min-width:240px;">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" class="search-input" placeholder="Buscar cliente por nombre, empresa o contacto..." />
            </div>
            <div class="filter-select-wrapper" style="min-width:170px;">
                <svg class="select-left-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <select class="filter-select">
                    <option>Todos los asesores</option>
                </select>
                <svg class="select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="filter-select-wrapper" style="min-width:150px;">
                <svg class="select-left-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <select class="filter-select">
                    <option>Con promoción</option>
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
                <svg class="select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>
    </div>

    {{-- Estadísticas (sin gráficas) --}}
    @php
        $total = $customers->count();
        $newCustomers = $customers
            ->filter(fn ($customer) => $customer->created_at?->greaterThanOrEqualTo(now()->startOfMonth()))
            ->count();
        $inactiveCustomers = $customers->where('activo', false)->count();
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px; margin-bottom:18px;">
        <div class="stat-card cyan">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div style="position:relative; z-index:1;">
                <div class="stat-label">Total clientes</div>
                <div class="stat-value">{{ $total }}</div>
                <div class="stat-sublabel">Registrados en el sistema</div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
            </div>
            <div style="position:relative; z-index:1;">
                <div class="stat-label">Clientes nuevos</div>
                <div class="stat-value">{{ $newCustomers }}</div>
                <div class="stat-sublabel">Registrados este mes</div>
            </div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div style="position:relative; z-index:1;">
                <div class="stat-label">Clientes inactivos</div>
                <div class="stat-value">{{ $inactiveCustomers }}</div>
                <div class="stat-sublabel">{{ $total > 0 ? round(($inactiveCustomers / $total) * 100) : 0 }}% del total</div>
            </div>
        </div>
    </div>

    {{-- Tabla de clientes --}}
    <div class="dashboard-card" style="overflow-x:auto;">
        <table class="customers-table">
            <thead>
                <tr>
                    <th>
                        CLIENTE
                    </th>
                    <th>
                        TELÉFONO
                    </th>
                    <th>
                        CORREO
                    </th>
                    <th>
                        ASESOR
                    </th>
                    <th>
                        PROMOCIÓN
                    </th>
                    <th style="display:none;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    @php
                        $initials = '';
                        $names = explode(' ', trim($customer->nombre . ' ' . $customer->apellido));
                        if (count($names) >= 2) {
                            $initials = strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
                        } elseif (count($names) === 1) {
                            $initials = strtoupper(substr($names[0], 0, 2));
                        } else {
                            $initials = 'CL';
                        }
                        $avatarColor = ['#7c3aed','#3b82f6','#10b981','#f59e0b','#ec4899'][crc32($initials) % 5];
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center;">
                                <span class="customer-avatar" style="background:{{ $avatarColor }}; box-shadow: 0 0 12px {{ $avatarColor }}, 0 0 0 1px rgba(255,255,255,0.15);">{{ $initials }}</span>
                                <div>
                                    <div class="customer-name">{{ $customer->nombre }} {{ $customer->apellido }}</div>
                                    <div class="customer-meta">{{ $customer->category?->nombre ?? 'Sin categoría' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <svg class="contact-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            {{ $customer->telefono }}
                        </td>
                        <td>
                            <svg class="contact-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            {{ $customer->gmail ?? '-' }}
                        </td>
                        <td>
                            <svg class="contact-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            {{ $customer->asesor?->name ?? 'Sin asesor' }}
                        </td>
                        <td>
                            <span class="promotion-badge {{ $customer->recibe_promocion ? '' : 'inactive' }}">
                                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                                {{ $customer->recibe_promocion ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td style="display:none;">
                            <div class="action-menu-wrapper" style="position:relative; display:inline-block;">
                                <button type="button" class="action-btn action-menu-toggle" title="Más opciones" data-menu="customer-menu-{{ $customer->id }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                </button>
                                <div id="customer-menu-{{ $customer->id }}" class="customer-action-menu" style="display:none;">
                                    <div class="action-menu-title">Acciones Rápida</div>
                                    <a href="{{ route('commercial.cotizaciones.create', ['cliente' => $customer->id]) }}" class="quick-action" title="Nueva Cotización">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        <span>Nueva Cotización</span>
                                    </a>
                                    <a href="{{ route('commercial.clientes.edit', $customer) }}" class="quick-action" title="Editar Cliente">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        <span>Editar Cliente</span>
                                    </a>
                                    <a href="{{ route('commercial.ventas.create', ['cliente' => $customer->id]) }}" class="quick-action" title="Nueva Venta">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                        <span>Nueva Venta</span>
                                    </a>
                                    <a href="{{ route('commercial.clientes.show', $customer) }}" class="quick-action" title="Ver Cliente">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>Ver Cliente</span>
                                    </a>
                                    <a href="#" class="quick-action" title="Nuevo Servicio">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                        <span>Nuevo Servicio</span>
                                    </a>
                                    <form action="#" method="POST" class="quick-action-form" style="display:flex; width:100%;" onsubmit="return confirm('¿Eliminar este cliente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="quick-action quick-action-danger" title="Eliminar Cliente" style="width:100%; border:none; background:none; cursor:pointer;">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            <span>Eliminar Cliente</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-message" style="padding:32px; text-align:center;">
                            No hay clientes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Footer resumen --}}
        @if($customers->count())
            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:18px; flex-wrap:wrap; gap:12px;">
                <div class="table-footer-info">
                    Mostrando {{ $customers->count() }} clientes en total
                </div>
                <div style="display:flex; align-items:center; gap:14px;">
                    <select class="page-size-select">
                        <option>10 por página</option>
                        <option>20 por página</option>
                        <option>50 por página</option>
                    </select>
                </div>
            </div>
        @endif
    </div>

    <style>
        .customer-action-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            min-width: 180px;
            background: #1f2937;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.35);
            z-index: 1000;
            padding: 10px;
            overflow: hidden;
            animation: menuPop 0.18s ease;
        }
        @keyframes menuPop {
            from { opacity: 0; transform: translateY(-6px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .action-menu-title {
            padding: 8px 10px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-weight: 600;
            color: #fff;
            font-size: 14px;
        }
        .quick-action {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .quick-action:hover {
            background: rgba(59, 130, 246, 0.18);
            color: #fff;
        }
        .quick-action svg {
            color: #38bdf8;
            flex-shrink: 0;
        }
        .quick-action-form .quick-action svg {
            color: #f87171;
        }
        .quick-action-form .quick-action:hover {
            background: rgba(239, 68, 68, 0.18);
            color: #fff;
        }

        html[data-theme="light"] .customer-action-menu {
            background: #fff;
            border-color: rgba(0,0,0,0.08);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        html[data-theme="light"] .action-menu-title {
            color: #111827;
            border-bottom-color: rgba(0,0,0,0.08);
        }
        html[data-theme="light"] .quick-action {
            color: #374151;
        }
        html[data-theme="light"] .quick-action:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #111827;
        }
        html[data-theme="light"] .quick-action svg {
            color: #0ea5e9;
        }
        html[data-theme="light"] .quick-action-form .quick-action svg {
            color: #ef4444;
        }
        html[data-theme="light"] .quick-action-form .quick-action:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #111827;
        }

        .empty-message {
            color: rgba(255,255,255,0.5);
        }
        .table-footer-info {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
        }
        html[data-theme="light"] .empty-message { color: #6b7280; }
        html[data-theme="light"] .table-footer-info { color: #6b7280; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.action-menu-toggle');

            function positionMenu(menu, btn) {
                const rect = btn.getBoundingClientRect();
                const mw = menu.offsetWidth;
                const mh = menu.offsetHeight;

                let left = rect.right - mw;
                if (left < 0) left = 0;
                if (left + mw > window.innerWidth) left = window.innerWidth - mw;

                let top = rect.bottom + 8;
                if (top + mh > window.innerHeight) top = rect.top - mh - 8;

                menu.style.left = left + 'px';
                menu.style.top = top + 'px';
            }

            function closeAllMenus() {
                document.querySelectorAll('.customer-action-menu').forEach(function(m) {
                    m.style.display = 'none';
                });
            }

            toggles.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const menuId = this.getAttribute('data-menu');
                    const menu = document.getElementById(menuId);

                    document.querySelectorAll('.customer-action-menu').forEach(function(m) {
                        if (m !== menu) m.style.display = 'none';
                    });

                    if (menu.style.display === 'block') {
                        menu.style.display = 'none';
                    } else {
                        positionMenu(menu, this);
                        menu.style.display = 'block';
                    }
                });
            });

            document.addEventListener('click', closeAllMenus);
            window.addEventListener('scroll', closeAllMenus, true);

            document.querySelectorAll('.customer-action-menu').forEach(function(menu) {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>
@endsection
