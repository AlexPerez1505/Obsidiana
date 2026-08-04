@extends('layouts.dashboard')
@section('title', 'Reportes')
@section('page-title', 'Reportes')
@section('page-sub', 'Gestion Administrativa > Reporte')

@php
    $employees = [
        ['name' => 'Ricardo', 'initials' => 'R', 'area' => 'Gestion Administrativa', 'attendance' => '94%', 'pending' => '0', 'status' => 'Al dia'],
        ['name' => 'Marina Sherlyn', 'initials' => 'MS', 'area' => 'Marketing', 'attendance' => '96%', 'pending' => '1', 'status' => 'Vacaciones'],
        ['name' => 'Jose Alex', 'initials' => 'JA', 'area' => 'Inventario', 'attendance' => '91%', 'pending' => '1', 'status' => 'Permiso'],
        ['name' => 'Andrea Ramirez', 'initials' => 'AR', 'area' => 'Servicios', 'attendance' => '88%', 'pending' => '1', 'status' => 'Justificar'],
        ['name' => 'Dylan Santiago', 'initials' => 'DS', 'area' => 'Comercial', 'attendance' => '90%', 'pending' => '1', 'status' => 'Revision'],
        ['name' => 'Fernanda Lopez', 'initials' => 'FL', 'area' => 'Administracion', 'attendance' => '97%', 'pending' => '0', 'status' => 'Al dia'],
    ];

    $metrics = [
        ['label' => 'Colaboradores', 'value' => count($employees), 'trend' => 'En seguimiento', 'type' => 'employee'],
        ['label' => 'Asistencias', 'value' => '94%', 'trend' => '+3%', 'type' => 'attendance'],
        ['label' => 'Faltas', 'value' => '7', 'trend' => '-2', 'type' => 'absence'],
        ['label' => 'Vacaciones', 'value' => '4', 'trend' => 'Activas', 'type' => 'vacation'],
        ['label' => 'Permisos', 'value' => '6', 'trend' => '2 pendientes', 'type' => 'permission'],
        ['label' => 'Incidencias', 'value' => '3', 'trend' => 'Revision', 'type' => 'incident'],
    ];

    $records = [
        ['employee' => 'Ricardo', 'area' => 'Gestion Administrativa', 'type' => 'attendance', 'label' => 'Asistencia', 'date' => '03 Ago 2026', 'detail' => 'Entrada 08:02 - salida 17:01', 'status' => 'Validada'],
        ['employee' => 'Marina Sherlyn', 'area' => 'Marketing', 'type' => 'vacation', 'label' => 'Vacaciones', 'date' => '05-09 Ago 2026', 'detail' => 'Periodo solicitado por descanso anual', 'status' => 'Aprobada'],
        ['employee' => 'Jose Alex', 'area' => 'Inventario', 'type' => 'permission', 'label' => 'Permiso', 'date' => '04 Ago 2026', 'detail' => 'Salida personal de 12:00 a 14:00', 'status' => 'Pendiente'],
        ['employee' => 'Andrea Ramirez', 'area' => 'Servicios', 'type' => 'absence', 'label' => 'Falta', 'date' => '31 Jul 2026', 'detail' => 'Sin registro de entrada', 'status' => 'Justificar'],
        ['employee' => 'Dylan Santiago', 'area' => 'Comercial', 'type' => 'incident', 'label' => 'Incidencia', 'date' => '30 Jul 2026', 'detail' => 'Retardo mayor a 20 minutos', 'status' => 'Revision'],
        ['employee' => 'Fernanda Lopez', 'area' => 'Administracion', 'type' => 'attendance', 'label' => 'Asistencia', 'date' => '29 Jul 2026', 'detail' => 'Jornada completa validada', 'status' => 'Validada'],
    ];

    $typeMeta = [
        'attendance' => ['label' => 'Asistencia', 'class' => 'attendance'],
        'absence' => ['label' => 'Falta', 'class' => 'absence'],
        'vacation' => ['label' => 'Vacaciones', 'class' => 'vacation'],
        'permission' => ['label' => 'Permiso', 'class' => 'permission'],
        'incident' => ['label' => 'Incidencia', 'class' => 'incident'],
    ];

@endphp

@push('head')
<style>
    .reports-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .reports-crumb {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .reports-crumb a {
        color: var(--primary);
        text-decoration: none;
    }

    .reports-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }

    .reports-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .reports-heading svg {
        width: 34px;
        height: 34px;
        color: var(--primary);
        flex: 0 0 auto;
    }

    .reports-heading h2 {
        margin: 0;
        font-size: 24px;
        line-height: 1.12;
        color: var(--text);
    }

    .reports-heading p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 14px;
        font-weight: 600;
    }

    .reports-primary,
    .reports-light,
    .reports-tab {
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .reports-primary {
        min-height: 44px;
        padding: 0 18px;
        border: 0;
        border-radius: 7px;
        background: #2563eb;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .22);
        white-space: nowrap;
    }

    .reports-primary:hover {
        background: #1d4ed8;
    }

    .reports-primary svg,
    .reports-light svg,
    .reports-icon svg {
        width: 19px;
        height: 19px;
        flex: 0 0 auto;
    }

    .reports-filters {
        display: grid;
        grid-template-columns: repeat(4, minmax(150px, 1fr)) auto;
        gap: 12px;
        align-items: end;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 16px;
        box-shadow: var(--shadow);
    }

    .reports-field label {
        margin: 0 0 7px;
    }

    .reports-field input,
    .reports-field select {
        width: 100%;
        height: 42px;
        padding: 0 11px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        outline: none;
    }

    .reports-field input:focus,
    .reports-field select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, .12);
    }

    .reports-light {
        min-height: 42px;
        padding: 0 14px;
        border: 1px solid rgba(37, 99, 235, .35);
        border-radius: 8px;
        background: var(--primary-soft);
        color: var(--primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .reports-metrics {
        display: grid;
        grid-template-columns: repeat(6, minmax(140px, 1fr));
        gap: 12px;
    }

    .metric-box {
        min-height: 104px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        box-shadow: var(--shadow);
        display: grid;
        gap: 10px;
    }

    .metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .reports-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--surface-2);
    }

    .metric-box.employee .reports-icon { color: #0891b2; background: #cffafe; }
    .metric-box.attendance .reports-icon { color: #2563eb; background: #dbeafe; }
    .metric-box.absence .reports-icon { color: #dc2626; background: #fee2e2; }
    .metric-box.vacation .reports-icon { color: #059669; background: #d1fae5; }
    .metric-box.permission .reports-icon { color: #d97706; background: #ffedd5; }
    .metric-box.incident .reports-icon { color: #7c3aed; background: #ede9fe; }

    .metric-label {
        color: var(--muted);
        font-size: 12.5px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .metric-value {
        margin: 0;
        color: var(--text);
        font-size: 30px;
        line-height: 1;
        font-weight: 900;
    }

    .metric-trend {
        color: var(--muted);
        font-size: 12px;
        font-weight: 800;
    }

    .reports-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(290px, .65fr);
        gap: 18px;
        align-items: start;
    }

    .reports-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: var(--shadow);
        min-width: 0;
    }

    .reports-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
    }

    .reports-panel-head h3 {
        margin: 0;
        color: var(--text);
        font-size: 17px;
    }

    .reports-panel-head p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
    }

    .reports-tabs {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 14px 16px 0;
    }

    .reports-tab {
        min-height: 34px;
        padding: 0 12px;
        border: 1px solid var(--border);
        border-radius: 999px;
        background: var(--surface);
        color: var(--muted);
    }

    .reports-tab.is-active {
        border-color: rgba(37, 99, 235, .45);
        background: var(--primary-soft);
        color: var(--primary);
    }

    .records-wrap {
        overflow-x: auto;
        padding: 14px 16px 16px;
    }

    .records-table {
        min-width: 760px;
    }

    .records-table th,
    .records-table td {
        padding: 12px 10px;
        vertical-align: top;
    }

    .employee-name {
        display: block;
        color: var(--text);
        font-weight: 800;
    }

    .employee-area {
        display: block;
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
    }

    .type-pill,
    .status-pill {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 0 9px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 900;
        white-space: nowrap;
    }

    .type-pill.attendance { color: #1d4ed8; background: #dbeafe; }
    .type-pill.absence { color: #b91c1c; background: #fee2e2; }
    .type-pill.vacation { color: #047857; background: #d1fae5; }
    .type-pill.permission { color: #b45309; background: #ffedd5; }
    .type-pill.incident { color: #6d28d9; background: #ede9fe; }

    .status-pill {
        color: var(--text);
        background: var(--surface-2);
        border: 1px solid var(--border);
    }

    .report-side {
        display: grid;
        gap: 18px;
    }

    .employee-summary-list {
        display: grid;
        gap: 10px;
        padding: 14px;
    }

    .employee-summary-item {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: start;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
    }

    .employee-summary-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 12px;
        font-weight: 900;
    }

    .employee-summary-main {
        min-width: 0;
    }

    .employee-summary-main strong,
    .employee-summary-area,
    .employee-summary-stats span {
        display: block;
    }

    .employee-summary-main strong {
        color: var(--text);
        font-size: 14px;
    }

    .employee-summary-area {
        margin-top: 2px;
        color: var(--muted);
        font-size: 12.5px;
        font-weight: 700;
    }

    .employee-summary-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .employee-summary-stats span {
        min-width: 0;
        padding: 7px 8px;
        border-radius: 8px;
        background: var(--surface-2);
        color: var(--muted);
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1.25;
    }

    .employee-summary-stats b {
        display: block;
        color: var(--text);
        font-size: 12px;
    }

    .pending-list {
        display: grid;
        gap: 10px;
        padding: 14px;
    }

    .pending-item {
        display: grid;
        grid-template-columns: 36px 1fr;
        gap: 10px;
        align-items: start;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
    }

    .pending-dot {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-soft);
        color: var(--accent);
        font-weight: 900;
    }

    .pending-item strong {
        display: block;
        color: var(--text);
        font-size: 14px;
    }

    .pending-item span {
        display: block;
        margin-top: 2px;
        color: var(--muted);
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.35;
    }

    :root[data-theme="dark"] .metric-box.employee .reports-icon { background: rgba(8, 145, 178, .18); color: #67e8f9; }
    :root[data-theme="dark"] .metric-box.attendance .reports-icon,
    :root[data-theme="dark"] .type-pill.attendance { background: rgba(37, 99, 235, .18); color: #93c5fd; }
    :root[data-theme="dark"] .metric-box.absence .reports-icon,
    :root[data-theme="dark"] .type-pill.absence { background: rgba(220, 38, 38, .18); color: #fca5a5; }
    :root[data-theme="dark"] .metric-box.vacation .reports-icon,
    :root[data-theme="dark"] .type-pill.vacation { background: rgba(16, 185, 129, .16); color: #86efac; }
    :root[data-theme="dark"] .metric-box.permission .reports-icon,
    :root[data-theme="dark"] .type-pill.permission { background: rgba(217, 119, 6, .18); color: #fdba74; }
    :root[data-theme="dark"] .metric-box.incident .reports-icon,
    :root[data-theme="dark"] .type-pill.incident { background: rgba(124, 58, 237, .2); color: #c4b5fd; }

    @media (max-width: 1180px) {
        .reports-metrics {
            grid-template-columns: repeat(3, minmax(140px, 1fr));
        }

        .reports-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 860px) {
        .reports-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .reports-header {
            align-items: stretch;
            flex-direction: column;
        }

        .reports-primary,
        .reports-light {
            width: 100%;
        }

        .reports-filters,
        .reports-metrics {
            grid-template-columns: 1fr;
        }

        .reports-panel-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
    <section class="reports-page">
        <nav class="reports-crumb" aria-label="Ruta">
            <a href="{{ route('dashboard') }}">Gestion Administrativa</a>
            <span>&gt;</span>
            <strong>Reporte</strong>
        </nav>

        <div class="reports-header">
            <div class="reports-heading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 3v18h18"></path>
                    <path d="M7 15l4-4 3 3 5-7"></path>
                    <path d="M18 7h1v1"></path>
                </svg>
                <div>
                    <h2>Reportes administrativos</h2>
                    <p>Control por colaborador de asistencias, faltas, vacaciones, permisos e incidencias.</p>
                </div>
            </div>

            <button class="reports-primary" type="button" onclick="exportReport()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
                Exportar reporte
            </button>
        </div>

        <form class="reports-filters" onsubmit="event.preventDefault(); filterReportRows();">
            <div class="reports-field">
                <label for="report-start">Desde</label>
                <input id="report-start" type="date" value="2026-08-01">
            </div>
            <div class="reports-field">
                <label for="report-end">Hasta</label>
                <input id="report-end" type="date" value="2026-08-31">
            </div>
            <div class="reports-field">
                <label for="report-area">Area</label>
                <select id="report-area">
                    <option value="all">Todas</option>
                    <option>Gestion Administrativa</option>
                    <option>Marketing</option>
                    <option>Inventario</option>
                    <option>Servicios</option>
                    <option>Comercial</option>
                    <option>Administracion</option>
                </select>
            </div>
            <div class="reports-field">
                <label for="report-person">Colaborador</label>
                <input id="report-person" type="text" placeholder="Buscar nombre" list="report-employees">
                <datalist id="report-employees">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee['name'] }}"></option>
                    @endforeach
                </datalist>
            </div>
            <button class="reports-light" type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.5V20l4-2v-5.5L22 3z"></path></svg>
                Filtrar
            </button>
        </form>

        <div class="reports-metrics">
            @foreach ($metrics as $metric)
                <div class="metric-box {{ $metric['type'] }}">
                    <div class="metric-top">
                        <span class="metric-label">{{ $metric['label'] }}</span>
                        <span class="reports-icon">
                            @if ($metric['type'] === 'employee')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            @elseif ($metric['type'] === 'attendance')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                            @elseif ($metric['type'] === 'absence')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                            @elseif ($metric['type'] === 'vacation')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16"></path><path d="M12 3v16"></path><path d="M7 8c2-3 8-3 10 0"></path></svg>
                            @elseif ($metric['type'] === 'permission')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path></svg>
                            @endif
                        </span>
                    </div>
                    <p class="metric-value">{{ $metric['value'] }}</p>
                    <span class="metric-trend">{{ $metric['trend'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="reports-layout">
            <div class="reports-panel">
                <div class="reports-panel-head">
                    <div>
                        <h3>Movimientos del periodo</h3>
                        <p>Registros recientes por colaborador y tipo.</p>
                    </div>
                    <span class="status-pill" id="reportCount">{{ count($records) }} registros</span>
                </div>

                <div class="reports-tabs" aria-label="Tipos de reporte">
                    <button class="reports-tab is-active" type="button" data-report-type="all">Todos</button>
                    <button class="reports-tab" type="button" data-report-type="attendance">Asistencias</button>
                    <button class="reports-tab" type="button" data-report-type="absence">Faltas</button>
                    <button class="reports-tab" type="button" data-report-type="vacation">Vacaciones</button>
                    <button class="reports-tab" type="button" data-report-type="permission">Permisos</button>
                    <button class="reports-tab" type="button" data-report-type="incident">Incidencias</button>
                </div>

                <div class="records-wrap">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Detalle</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="recordsBody">
                            @foreach ($records as $record)
                                @php($meta = $typeMeta[$record['type']])
                                <tr data-type="{{ $record['type'] }}" data-employee="{{ strtolower($record['employee']) }}" data-area="{{ $record['area'] }}">
                                    <td>
                                        <span class="employee-name">{{ $record['employee'] }}</span>
                                        <span class="employee-area">{{ $record['area'] }}</span>
                                    </td>
                                    <td><span class="type-pill {{ $meta['class'] }}">{{ $record['label'] }}</span></td>
                                    <td>{{ $record['date'] }}</td>
                                    <td>{{ $record['detail'] }}</td>
                                    <td><span class="status-pill">{{ $record['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="report-side">
                <div class="reports-panel">
                    <div class="reports-panel-head">
                        <div>
                            <h3>Colaboradores monitoreados</h3>
                            <p>Resumen individual del periodo.</p>
                        </div>
                        <span class="status-pill" id="employeeCount">{{ count($employees) }} empleados</span>
                    </div>
                    <div class="employee-summary-list" id="employeeSummaryList">
                        @foreach ($employees as $employee)
                            <div class="employee-summary-item" data-employee="{{ strtolower($employee['name']) }}" data-area="{{ $employee['area'] }}">
                                <span class="employee-summary-avatar">{{ $employee['initials'] }}</span>
                                <span class="employee-summary-main">
                                    <strong>{{ $employee['name'] }}</strong>
                                    <span class="employee-summary-area">{{ $employee['area'] }}</span>
                                    <span class="employee-summary-stats">
                                        <span><b>{{ $employee['attendance'] }}</b> Asistencia</span>
                                        <span><b>{{ $employee['pending'] }}</b> Pendientes</span>
                                        <span><b>{{ $employee['status'] }}</b> Estado</span>
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="reports-panel">
                    <div class="reports-panel-head">
                        <div>
                            <h3>Pendientes</h3>
                            <p>Casos que requieren seguimiento.</p>
                        </div>
                    </div>
                    <div class="pending-list">
                        <div class="pending-item">
                            <span class="pending-dot">2</span>
                            <span><strong>Permisos por aprobar</strong><span>Solicitudes de salida personal en espera.</span></span>
                        </div>
                        <div class="pending-item">
                            <span class="pending-dot">1</span>
                            <span><strong>Falta por justificar</strong><span>Registro sin evidencia cargada.</span></span>
                        </div>
                        <div class="pending-item">
                            <span class="pending-dot">3</span>
                            <span><strong>Incidencias abiertas</strong><span>Retardos y ajustes de asistencia.</span></span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <script>
        let activeReportType = 'all';

        document.querySelectorAll('.reports-tab').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.reports-tab').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                activeReportType = button.dataset.reportType;
                filterReportRows();
            });
        });

        function filterReportRows() {
            const person = document.getElementById('report-person').value.trim().toLowerCase();
            const area = document.getElementById('report-area').value;
            let visible = 0;
            let visibleEmployees = 0;

            document.querySelectorAll('#recordsBody tr').forEach((row) => {
                const matchesType = activeReportType === 'all' || row.dataset.type === activeReportType;
                const matchesPerson = !person || row.dataset.employee.includes(person);
                const matchesArea = area === 'all' || row.dataset.area === area;
                const show = matchesType && matchesPerson && matchesArea;

                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            document.querySelectorAll('#employeeSummaryList .employee-summary-item').forEach((item) => {
                const matchesPerson = !person || item.dataset.employee.includes(person);
                const matchesArea = area === 'all' || item.dataset.area === area;
                const show = matchesPerson && matchesArea;

                item.style.display = show ? '' : 'none';
                if (show) visibleEmployees += 1;
            });

            document.getElementById('reportCount').textContent = visible + (visible === 1 ? ' registro' : ' registros');
            document.getElementById('employeeCount').textContent = visibleEmployees + (visibleEmployees === 1 ? ' empleado' : ' empleados');
        }

        function exportReport() {
            if (typeof window.showToast === 'function') {
                window.showToast('Reporte preparado para exportacion.');
            }
        }
    </script>
@endsection
