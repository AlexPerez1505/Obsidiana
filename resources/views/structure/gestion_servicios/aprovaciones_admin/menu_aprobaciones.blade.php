@extends('structure.gestion_servicios.layout')

@section('title', 'Aprobaciones de Servicios')

@section('service_content')
    <style>
        .service-page-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 0;
        }
        .service-page-header .title-group h2 {
            margin: 0; font-size: 28px; color: #fff;
        }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p {
            margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 14px;
        }
        :root[data-theme="light"] .service-page-header .title-group p { color: var(--muted); }

        .service-page-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .service-search-wrap {
            display: flex; align-items: center; gap: 10px;
        }
        .service-search-toggle {
            width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid rgba(22,119,255,0.55); background: rgba(8,18,40,0.55); color: #fff;
            cursor: pointer; transition: all .16s ease;
            box-shadow: 0 0 14px rgba(22,119,255,0.2), inset 0 1px 0 rgba(255,255,255,0.05);
        }
        .service-search-toggle:hover {
            background: rgba(22,119,255,0.2);
            box-shadow: 0 0 20px rgba(0,170,255,0.35);
            color: #fff;
        }
        .service-search-toggle svg { width: 18px; height: 18px; }
        .service-search-form {
            display: none; align-items: center; gap: 8px;
        }
        .service-search-form.open { display: flex; }
        .service-search-form input {
            width: 260px; padding: 10px 14px; border-radius: 12px;
            border: 1px solid rgba(22,119,255,0.55); background: rgba(8,18,40,0.55); color: #fff; font-size: 13px;
            box-shadow: 0 0 14px rgba(22,119,255,0.15), inset 0 1px 0 rgba(255,255,255,0.05);
        }
        .service-search-form input::placeholder { color: rgba(255,255,255,0.45); }
        .service-search-form input:focus { outline: none; border-color: #007AFF; box-shadow: 0 0 20px rgba(0,122,255,0.45); }
        :root[data-theme="light"] .service-search-toggle { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .service-search-toggle:hover { background: rgba(0,122,255,0.08); }
        :root[data-theme="light"] .service-search-form input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .service-search-form input::placeholder { color: var(--muted); }

        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.7); align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #0b1a35; border: 1px solid rgba(34,197,94,0.55); border-radius: 16px;
            padding: 28px; width: 90%; max-width: 360px; text-align: center;
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
        }
        :root[data-theme="light"] .modal-box { background: #fff; border-color: rgba(15,23,42,0.14); }
        .modal-box h3 { margin: 0 0 10px; color: #fff; font-size: 18px; }
        :root[data-theme="light"] .modal-box h3 { color: var(--text); }
        .modal-box p { margin: 0 0 18px; color: rgba(255,255,255,0.7); font-size: 13px; }
        :root[data-theme="light"] .modal-box p { color: var(--muted); }
        .modal-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 42px; font-weight: 800; color: #22C55E; letter-spacing: 6px;
            margin-bottom: 22px;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .modal-btn {
            padding: 10px 16px; border-radius: 10px; border: none; font-size: 13px; font-weight: 700;
            cursor: pointer; text-decoration: none;
        }
        .modal-btn--primary { background: #22C55E; color: #fff; }
        .modal-btn--ghost { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff; }
        :root[data-theme="light"] .modal-btn--ghost { border-color: rgba(15,23,42,0.2); color: var(--text); }

        .service-tabs {
            display: flex;
            gap: 6px;
            margin-top: 12px;
            background: rgba(5, 11, 24, 0.55);
            border: 1px solid rgba(22, 119, 255, 0.47);
            border-radius: 14px;
            padding: 5px;
            box-shadow:
                inset 0 1px 3px rgba(0, 0, 0, 0.35),
                0 6px 16px rgba(0, 0, 0, 0.25);
        }
        .service-tab {
            flex: 1;
            justify-content: center;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border: none;
            border-bottom: none;
            border-radius: 10px;
            background: transparent;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all .16s ease;
            box-shadow: none;
        }
        .service-tab:hover {
            background: rgba(22, 119, 255, 0.43);
            color: #fff;
        }
        .service-tab.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(22, 119, 255, 0.57), rgba(99, 91, 255, 0.51));
            border: 1px solid rgba(30, 125, 255, 0.9);
            box-shadow:
                0 0 20px rgba(30, 125, 255, 0.47),
                0 10px 35px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }
        .service-tab svg { width: 18px; height: 18px; }
        :root[data-theme="light"] .service-tabs {
            background: rgba(255, 255, 255, 0.78);
            border-color: rgba(15, 23, 42, 0.12);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        }
        :root[data-theme="light"] .service-tab { color: var(--muted); }
        :root[data-theme="light"] .service-tab:hover {
            background: rgba(0, 122, 255, 0.08);
            color: var(--primary);
        }
        :root[data-theme="light"] .service-tab.active {
            color: #fff;
            background: linear-gradient(135deg, #1677ff, #635bff);
            border-color: rgba(22, 119, 255, 0.7);
            box-shadow: 0 4px 14px rgba(22, 119, 255, 0.22);
        }

        .actions-menu { position: relative; display: inline-block; }
        .actions-menu-trigger {
            width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(34,197,94,0.35);
            background: rgba(34,197,94,0.08); color: #22C55E; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .actions-menu-trigger svg { width: 18px; height: 18px; }
        .actions-menu-dropdown {
            position: absolute; right: 0; top: calc(100% + 4px); z-index: 50;
            min-width: 220px; border-radius: 12px; overflow: hidden;
            background: #0b1a35; border: 1px solid rgba(34,197,94,0.35);
            box-shadow: 0 8px 24px rgba(0,0,0,0.5); padding: 4px;
            display: none; flex-direction: column; gap: 2px;
        }
        :root[data-theme="light"] .actions-menu-dropdown {
            background: #fff; border-color: rgba(15,23,42,0.14);
            box-shadow: 0 8px 24px rgba(15,23,42,0.18);
        }
        .actions-menu.open .actions-menu-dropdown { display: flex; }
        .actions-menu-item {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px;
            border: none; border-radius: 8px; background: transparent; color: #fff;
            font-size: 13px; cursor: pointer; text-align: left; transition: background .12s ease;
            text-decoration: none; box-sizing: border-box;
        }
        :root[data-theme="light"] .actions-menu-item { color: #0f172a; }
        .actions-menu-item:hover { background: rgba(34,197,94,0.14); }
        :root[data-theme="light"] .actions-menu-item:hover { background: rgba(15,23,42,0.06); }
        .actions-menu-item svg { width: 16px; height: 16px; flex: 0 0 auto; }
        .actions-menu-item.danger { color: #ff4a4a; }
        .actions-menu-item.danger:hover { background: rgba(255,74,74,0.14); }

        .service-table-empty {
            padding: 36px 14px;
            text-align: center;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .service-table-empty { color: var(--muted); }
        .catalog-card.service-section { overflow: visible; }
        .service-table-wrap {
            max-height: 360px;
            overflow-y: auto;
            border-radius: 14px;
        }
        .service-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: rgba(8, 18, 40, 0.96);
        }
        :root[data-theme="light"] .service-table thead th {
            background: rgba(255, 255, 255, 0.96);
        }

        .service-pagination {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            margin-top: 20px; flex-wrap: wrap;
        }
        .custom-select { position: relative; }
        .custom-select-trigger {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            min-width: 70px; padding: 10px 14px; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer;
            border: 1px solid rgba(22,119,255,0.55); color: #fff;
            background: linear-gradient(135deg, rgba(8,18,40,0.65), rgba(0,122,255,0.12));
            box-shadow: 0 0 14px rgba(22,119,255,0.2), inset 0 1px 0 rgba(255,255,255,0.05);
            transition: all .16s ease;
        }
        .custom-select-trigger:hover {
            border-color: rgba(0,170,255,0.8);
            box-shadow: 0 0 20px rgba(0,170,255,0.35), inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .custom-select-trigger svg { width: 14px; height: 14px; }
        .custom-options {
            position: absolute; bottom: calc(100% + 8px); right: 0; z-index: 20;
            min-width: 100%; border-radius: 12px; overflow: hidden;
            background: #0b1220; border: 1px solid rgba(22,119,255,0.55);
            box-shadow: 0 8px 28px rgba(0,0,0,0.45), 0 0 18px rgba(22,119,255,0.25);
            display: none; flex-direction: column;
        }
        .custom-options.open { display: flex; }
        .custom-option {
            padding: 10px 16px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.75);
            cursor: pointer; transition: all .12s ease; text-align: center;
        }
        .custom-option:hover {
            background: rgba(22,119,255,0.18); color: #fff;
        }
        .custom-option.selected {
            background: rgba(22,119,255,0.45); color: #fff;
            box-shadow: inset 3px 0 0 #1677ff;
        }
        :root[data-theme="light"] .custom-select-trigger {
            background: linear-gradient(135deg, #fff, rgba(0,122,255,0.08)); color: var(--text); border-color: rgba(15,23,42,0.2);
        }
        :root[data-theme="light"] .custom-options { background: #fff; border-color: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .custom-option { color: var(--text); }
        :root[data-theme="light"] .custom-option:hover { background: rgba(0,122,255,0.08); }
        :root[data-theme="light"] .custom-option.selected { background: rgba(0,122,255,0.12); box-shadow: inset 3px 0 0 var(--primary); }

        .pagination-simple { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .page-link {
            display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px;
            padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 700;
            text-decoration: none; transition: all .16s ease;
            background: rgba(8,18,40,0.55); color: rgba(255,255,255,0.75);
            border: 1px solid rgba(22,119,255,0.35);
        }
        .page-link:hover {
            background: rgba(22,119,255,0.2); color: #fff;
            box-shadow: 0 0 12px rgba(22,119,255,0.3);
        }
        .page-link.active {
            background: rgba(22,119,255,0.55); color: #fff;
            border-color: rgba(22,119,255,0.8);
            box-shadow: 0 0 16px rgba(22,119,255,0.45);
        }
        .page-link.disabled {
            opacity: 0.45; cursor: not-allowed;
            box-shadow: none;
        }
        :root[data-theme="light"] .page-link {
            background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14);
        }
        :root[data-theme="light"] .page-link:hover { background: rgba(0,122,255,0.08); color: var(--primary); box-shadow: 0 0 8px rgba(0,122,255,0.15); }
        :root[data-theme="light"] .page-link.active { background: rgba(0,122,255,0.12); color: var(--primary); border-color: var(--primary); }
    </style>

    @if (session('success'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #22C55E;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #ff4a4a;">
            {{ session('error') }}
        </div>
    @endif

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Aprobaciones</h2>
                <p>Servicios pendientes y aprobados por autoridades.</p>
            </div>
            <div class="service-page-actions">
                <div class="service-search-wrap">
                    <button type="button" class="service-search-toggle" onclick="toggleServiceSearch()" title="Buscar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>
                    <form method="GET" action="{{ route('gestion.servicios.aprobaciones') }}" class="service-search-form" id="serviceSearchForm">
                        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                        <input type="text" name="search" id="serviceSearchInput" value="{{ request('search') }}" placeholder="Buscar servicio, cliente o tecnico..." onkeydown="if (event.key === 'Enter') this.form.submit()">
                    </form>
                </div>
                <a href="{{ route('gestion.servicios.validaciones.os') }}" class="catalog-create" style="width:auto; padding: 9px 16px; margin:0;">
                    Validaciones OS
                </a>
            </div>
        </div>

        <div class="service-tabs">
            <button type="button" class="service-tab active" data-type="all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3zM3 9h18M9 21V9"/></svg>
                Todos
            </button>
            <button type="button" class="service-tab" data-type="interno">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Servicio Interno
            </button>
            <button type="button" class="service-tab" data-type="externo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Servicio Externo
            </button>
        </div>

        <div class="service-table-wrap">
            <table class="service-table">
                <thead>
                    <tr>
                        <th>SERVICIO</th>
                        <th>PASO</th>
                        <th>CLIENTE</th>
                        <th>TÉCNICO</th>
                        <th>EQUIPO</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trackings as $tracking)
                        @php
                            $service = $tracking->service;
                            $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
                            $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
                            if (empty($techName) && $service->internalTechnician) {
                                $techName = $service->internalTechnician->name;
                            }
                            $equipment = $service->serviceEquipment;
                            $codeTracking = $service->serviceTrackings->firstWhere('serviceStep.slug', 'notificacion-llegada-tecnico');
                            $verificationCode = $codeTracking?->verification_code;
                            $menuId = 'actions-menu-' . $tracking->id;
                        @endphp
                        <tr data-type="{{ $service->service_type }}">
                            <td>{{ $service->service_number ?? 'N/A' }}</td>
                            <td>{{ $tracking->serviceStep->name ?? 'N/A' }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }}</td>
                            <td>
                                @if ($tracking->status === 'completado')
                                    <span class="service-badge active">Aprobado</span>
                                @else
                                    <span class="service-badge upcoming">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions-menu" id="{{ $menuId }}">
                                    <button type="button" class="actions-menu-trigger" onclick="toggleMenu('{{ $menuId }}')" title="Opciones">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="6" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="18" r="1.5" fill="currentColor"/></svg>
                                    </button>
                                    <div class="actions-menu-dropdown">
                                        @if ($tracking->status === 'pendiente')
                                            <form action="{{ route('gestion.servicios.tracking.aprobar', $tracking) }}" method="POST" style="display:contents;">
                                                @csrf
                                                <button type="submit" class="actions-menu-item">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Aprobar
                                                </button>
                                            </form>
                                        @endif
                                        @if ($verificationCode)
                                            <button type="button" class="actions-menu-item" onclick="showCode('{{ $verificationCode }}')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                Ver código
                                            </button>
                                        @endif
                                        <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.nuevo.interno.resumen', $service) : route('gestion.servicios.nuevo.externo.resumen', $service) }}" class="actions-menu-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver resumen
                                        </a>
                                        <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.ruta.interno', $service) : route('gestion.servicios.ruta', $service) }}" class="actions-menu-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                            Ver ruta de trabajo
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="service-table-empty">No hay servicios pendientes de aprobación.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($trackings instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $trackings->total() > 0)
        <div class="service-pagination">
            <form method="GET" action="{{ route('gestion.servicios.aprobaciones') }}" class="per-page-form" id="perPageForm">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" id="perPageInput" value="{{ request('per_page', 10) }}">
                <div class="custom-select">
                    <div class="custom-select-trigger" onclick="togglePerPageOptions()">
                        <span id="perPageLabel">{{ request('per_page', 10) }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="custom-options" id="perPageOptions">
                        <div class="custom-option {{ request('per_page') == '10' ? 'selected' : '' }}" data-value="10" onclick="selectPerPage(10)">10</div>
                        <div class="custom-option {{ request('per_page') == '20' ? 'selected' : '' }}" data-value="20" onclick="selectPerPage(20)">20</div>
                        <div class="custom-option {{ request('per_page') == '50' ? 'selected' : '' }}" data-value="50" onclick="selectPerPage(50)">50</div>
                    </div>
                </div>
            </form>
            <div class="pagination-simple">
                @if ($trackings->onFirstPage())
                    <span class="page-link disabled">&laquo; Anterior</span>
                @else
                    <a href="{{ $trackings->previousPageUrl() }}" class="page-link">&laquo; Anterior</a>
                @endif

                @for ($i = 1; $i <= $trackings->lastPage(); $i++)
                    @if ($i == $trackings->currentPage())
                        <span class="page-link active">{{ $i }}</span>
                    @else
                        <a href="{{ $trackings->url($i) }}" class="page-link">{{ $i }}</a>
                    @endif
                @endfor

                @if ($trackings->hasMorePages())
                    <a href="{{ $trackings->nextPageUrl() }}" class="page-link">Siguiente &raquo;</a>
                @else
                    <span class="page-link disabled">Siguiente &raquo;</span>
                @endif
            </div>
        </div>
    @endif

    <div id="codeModal" class="modal-overlay" onclick="closeCode(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <h3>Código de verificación</h3>
            <p>Comparte este código con el técnico externo para el paso de llegada.</p>
            <div class="modal-code" id="modalCode">----</div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn--primary" onclick="copyCode()">Copiar</button>
                <button type="button" class="modal-btn modal-btn--ghost" onclick="closeCode()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function showCode(code) {
            const modal = document.getElementById('codeModal');
            document.getElementById('modalCode').textContent = code;
            modal.classList.add('open');
        }

        function closeCode(event) {
            if (event && event.target !== document.getElementById('codeModal')) return;
            document.getElementById('codeModal').classList.remove('open');
        }

        function copyCode() {
            const code = document.getElementById('modalCode').textContent.trim();
            navigator.clipboard.writeText(code).catch(() => {});
        }

        function toggleMenu(menuId) {
            const menu = document.getElementById(menuId);
            const wasOpen = menu.classList.contains('open');
            document.querySelectorAll('.actions-menu').forEach(m => m.classList.remove('open'));
            if (! wasOpen) menu.classList.add('open');
        }

        function toggleServiceSearch() {
            const form = document.getElementById('serviceSearchForm');
            form.classList.toggle('open');
            if (form.classList.contains('open')) {
                document.getElementById('serviceSearchInput').focus();
            }
        }

        function togglePerPageOptions() {
            document.getElementById('perPageOptions').classList.toggle('open');
        }

        function selectPerPage(value) {
            var input = document.getElementById('perPageInput');
            if (input) input.value = value;
            document.getElementById('perPageLabel').textContent = value;
            document.getElementById('perPageForm').submit();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.service-tab');
            const rows = document.querySelectorAll('.service-table tbody tr[data-type]');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');

                    const type = tab.dataset.type;
                    rows.forEach(function (row) {
                        row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
                    });
                });
            });
        });

        document.addEventListener('click', function (event) {
            if (! event.target.closest('.actions-menu')) {
                document.querySelectorAll('.actions-menu').forEach(m => m.classList.remove('open'));
            }
            if (! event.target.closest('.custom-select')) {
                document.getElementById('perPageOptions').classList.remove('open');
            }
        });
    </script>
@endsection
