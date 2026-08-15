@extends('structure.gestion_servicios.layout')

@section('title', 'Historial de Servicios')

@section('service_content')
    <style>
        .service-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .service-page-header .title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .service-page-header .title-group h2 {
            margin: 0;
            font-size: 28px;
            color: #fff;
        }
        .service-title-icon {
            width: 52px; height: 52px; border-radius: 16px;
            background: rgba(0,122,255,0.18); color: #4aa3ff;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            border: 1px solid rgba(0,170,255,0.45);
            box-shadow:
                0 0 18px rgba(0,154,255,0.45),
                0 0 6px rgba(0,170,255,0.35),
                inset 0 1px 0 rgba(255,255,255,0.1);
            transition: all .2s ease;
        }
        .service-title-icon:hover {
            color: #7ecbff;
            box-shadow:
                0 0 28px rgba(0,170,255,0.65),
                0 0 12px rgba(0,200,255,0.45),
                inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .service-title-icon svg { width: 26px; height: 26px; }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p {
            margin: 4px 0 0;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
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
        .service-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .service-btn:hover { transform: translateY(-1px); }
        .service-btn--green {
            background: #1677ff;
            color: #fff;
            box-shadow: 0 0 16px rgba(22,119,255, 0.7);
        }
        .service-btn--green:hover { background: #0e5ce0; box-shadow: 0 0 22px rgba(22,119,255, 0.85); }
        .service-btn--blue {
            background: linear-gradient(135deg, #007AFF, #6366F1);
            color: #fff;
            box-shadow: 0 0 16px rgba(0,122,255,0.35);
        }
        .service-btn--blue:hover { background: linear-gradient(135deg, #005FCC, #4F46E5); box-shadow: 0 0 22px rgba(0,122,255,0.5); }
        .service-btn svg { width: 18px; height: 18px; }
        .service-tabs {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            border-bottom: 1px solid rgba(22, 119, 255, 0.6);
        }
        .service-tab, a.service-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: color .16s ease, border-color .16s ease;
        }
        .service-tab:hover { color: #fff; }
        .service-tab.active { color: #1677ff; border-bottom-color: #1677ff; }
        .service-tab svg { width: 18px; height: 18px; }
        :root[data-theme="light"] .service-tab { color: var(--muted); }
        :root[data-theme="light"] .service-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .service-table-empty {
            padding: 36px 14px;
            text-align: center;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .service-table-empty { color: var(--muted); }

        /* ===== Volumen visual: elevation y glow ===== */
        .service-page-header {
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(22, 119, 255, 0.45);
        }

        .service-btn {
            box-shadow:
                0 8px 22px rgba(31, 105, 255, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }
        .service-btn:hover {
            transform: translateY(-1px);
        }

        .service-btn--green, .service-btn--blue {
            transition: all 0.2s ease;
        }
        .service-btn--green {
            background: linear-gradient(135deg, #1677ff, #3b8cff);
            box-shadow:
                0 8px 22px rgba(31, 105, 255, 0.25),
                0 0 18px rgba(22, 119, 255, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.10);
        }
        .service-btn--green:hover {
            background: linear-gradient(135deg, #0e5ce0, #2a75ff);
            box-shadow:
                0 10px 28px rgba(31, 105, 255, 0.40),
                0 0 24px rgba(22, 119, 255, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }
        .service-btn--blue {
            background: linear-gradient(135deg, #1677ff, #635bff);
            box-shadow:
                0 8px 22px rgba(99, 91, 255, 0.6),
                0 0 18px rgba(99, 91, 255, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.10);
        }
        .service-btn--blue:hover {
            background: linear-gradient(135deg, #0e5ce0, #4f46e5);
            box-shadow:
                0 10px 28px rgba(99, 91, 255, 0.75),
                0 0 24px rgba(99, 91, 255, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .service-tabs {
            background: rgba(5, 11, 24, 0.55);
            border: 1px solid rgba(22, 119, 255, 0.47);
            border-radius: 14px;
            padding: 5px;
            gap: 6px;
            margin-top: 12px;
            box-shadow:
                inset 0 1px 3px rgba(0, 0, 0, 0.35),
                0 6px 16px rgba(0, 0, 0, 0.25);
        }
        .service-tab {
            flex: 1;
            justify-content: center;
            border-bottom: none;
            border-radius: 10px;
            padding: 12px 16px;
            background: transparent;
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

        .service-table-wrap { max-height: 520px; overflow: auto; border-radius: 14px; }
        .service-table { min-width: 900px; width: 100%; border-collapse: separate; }
        .service-table th:first-child, .service-table td:first-child { border-top-left-radius: 0; }
        .service-table th:last-child, .service-table td:last-child { border-top-right-radius: 0; }

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

        :root[data-theme="light"] .service-title-icon {
            background: rgba(0,122,255,0.1); color: var(--primary); border-color: rgba(0,122,255,0.35);
            box-shadow: 0 0 12px rgba(0,122,255,0.15);
        }
        :root[data-theme="light"] .service-title-icon:hover { color: #005FCC; box-shadow: 0 0 18px rgba(0,122,255,0.25); }
        :root[data-theme="light"] .service-page-header { border-bottom-color: rgba(15,23,42,0.12); }
        :root[data-theme="light"] .service-tabs {
            background: rgba(255, 255, 255, 0.78);
            border-color: rgba(15, 23, 42, 0.12);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        }
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
        :root[data-theme="light"] .service-table th,
        :root[data-theme="light"] .service-table td { color: var(--text); border-color: rgba(15,23,42,0.08); }
        :root[data-theme="light"] .service-table th { color: var(--muted); }
        :root[data-theme="light"] .service-table tr:hover td { background: rgba(0,122,255,0.05); }
        :root[data-theme="light"] .actions-menu-trigger { border-color: rgba(15,23,42,0.14); background: rgba(15,23,42,0.04); color: #22C55E; }

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

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <div class="service-title-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h2>Historial de Servicios</h2>
                    <p>Consulta y da seguimiento a los servicios realizados.</p>
                </div>
            </div>
            <div class="service-page-actions">
                <div class="service-search-wrap">
                    <button type="button" class="service-search-toggle" onclick="toggleServiceSearch()" title="Buscar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>
                    <form method="GET" action="{{ route('gestion.servicios.historial') }}" class="service-search-form" id="serviceSearchForm">
                        <input type="hidden" name="type" value="{{ request('type', 'all') }}">
                        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                        <input type="text" name="search" id="serviceSearchInput" value="{{ request('search') }}" placeholder="Buscar servicio, cliente o tecnico..." onkeydown="if (event.key === 'Enter') this.form.submit()">
                    </form>
                </div>
                <a href="{{ route('gestion.servicios.aprobaciones') }}" class="service-btn service-btn--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Aprobaciones
                </a>
                <a href="{{ route('gestion.servicios.nuevo') }}" class="service-btn service-btn--blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nuevo Servicio
                </a>
            </div>
        </div>

        <div class="service-tabs">
            <a href="{{ route('gestion.servicios.historial', ['type' => 'all', 'per_page' => request('per_page', 10)]) }}" class="service-tab {{ request('type', 'all') === 'all' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3zM3 9h18M9 21V9"/></svg>
                Todos
            </a>
            <a href="{{ route('gestion.servicios.historial', ['type' => 'interno', 'per_page' => request('per_page', 10)]) }}" class="service-tab {{ request('type') === 'interno' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Servicio Interno
            </a>
            <a href="{{ route('gestion.servicios.historial', ['type' => 'externo', 'per_page' => request('per_page', 10)]) }}" class="service-tab {{ request('type') === 'externo' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Servicio Externo
            </a>
        </div>
    </div>

    <div class="catalog-card service-section">
        <div class="service-table-wrap">
            <table class="service-table">
                <thead>
                    <tr>
                        <th>SERVICIO</th>
                        <th>CLIENTE</th>
                        <th>TIPO DE TÉCNICO</th>
                        <th>ESTADO</th>
                        <th>TOTAL</th>
                        <th>FECHA</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
                            $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
                        @endphp
                        <tr data-type="{{ $service->service_type }}">
                            <td>{{ $service->service_number ?? 'N/A' }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ ucfirst($service->status) }}</td>
                            <td>N/A</td>
                            <td>{{ $service->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @php
                                    $menuId = 'actions-menu-' . $service->id;
                                @endphp
                                <div class="actions-menu" id="{{ $menuId }}">
                                    <button type="button" class="actions-menu-trigger" onclick="toggleMenu('{{ $menuId }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="6" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="18" r="1.5" fill="currentColor"/></svg>
                                    </button>
                                    <div class="actions-menu-dropdown">
                                        <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.nuevo.interno.resumen', $service) : route('gestion.servicios.nuevo.externo.resumen', $service) }}" class="actions-menu-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver resumen
                                        </a>
                                        <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.ruta.interno', $service) : route('gestion.servicios.ruta', $service) }}" class="actions-menu-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                            Ver ruta de trabajo
                                        </a>
                                        @if ($service->service_type === 'externo')
                                            <a href="{{ route('gestion.servicios.edit.externo', $service) }}" class="actions-menu-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Editar
                                            </a>
                                        @else
                                            <a href="{{ route('gestion.servicios.edit.interno', $service) }}" class="actions-menu-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Editar
                                            </a>
                                        @endif
                                        <form action="{{ route('gestion.servicios.destroy', $service) }}" method="POST" onsubmit="return confirm('¿Eliminar esta orden de servicio?');" style="display:contents;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="actions-menu-item danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="service-table-empty">No hay servicios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="service-pagination">
        <form method="GET" action="{{ route('gestion.servicios.historial') }}" class="per-page-form" id="perPageForm">
            <input type="hidden" name="type" value="{{ request('type', 'all') }}">
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
            @if ($services->onFirstPage())
                <span class="page-link disabled">&laquo; Anterior</span>
            @else
                <a href="{{ $services->previousPageUrl() }}" class="page-link">&laquo; Anterior</a>
            @endif

            @for ($i = 1; $i <= $services->lastPage(); $i++)
                @if ($i == $services->currentPage())
                    <span class="page-link active">{{ $i }}</span>
                @else
                    <a href="{{ $services->url($i) }}" class="page-link">{{ $i }}</a>
                @endif
            @endfor

            @if ($services->hasMorePages())
                <a href="{{ $services->nextPageUrl() }}" class="page-link">Siguiente &raquo;</a>
            @else
                <span class="page-link disabled">Siguiente &raquo;</span>
            @endif
        </div>
    </div>

    <script>
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
            document.getElementById('perPageInput').value = value;
            document.getElementById('perPageLabel').textContent = value;
            document.getElementById('perPageForm').submit();
        }

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
