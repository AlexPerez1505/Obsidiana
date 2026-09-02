@extends('layouts.dashboard')
@section('title', 'Solicitud de materiales')
@section('page-title', 'Solicitar Material')
@section('page-sub', 'Gestion Administrativa > Solicitud de materiales')

@php
    $categories = $categories ?? [
        'Papelería',
        'Limpieza',
        'Herramientas',
        'Administración',
        'Ventas',
        'Logística y Envíos',
        'Almacén',
        'Mantenimiento de Equipo Médico',
        'Servicio Técnico',
        'Sistemas / TI',
        'Compras',
        'Marketing',
        'Seguridad e Higiene',
        'Mobiliario de Oficina',
        'Uniformes',
        'Publicidad',
        'Capacitación',
        'Combustible y Transporte',
        'Reparaciones Generales',
        'Hojalatería y Pintura',
        'Otros',
    ];
    $materialRequests = $materialRequests ?? [];
    $pendingCount = $pendingCount ?? collect($materialRequests)->where('status', 'pendiente')->count();
    $selectedUrgency = old('urgency', 'Normal');
@endphp

@push('head')
<style>
    .materials-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .materials-crumb {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .materials-crumb a {
        color: var(--primary);
        text-decoration: none;
    }

    .materials-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
    }

    .materials-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .materials-heading-icon,
    .field-icon,
    .summary-dot,
    .flow-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .materials-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: var(--primary-soft);
        color: var(--primary);
    }

    .materials-heading-icon svg,
    .field-icon svg,
    .materials-btn svg {
        width: 20px;
        height: 20px;
    }

    .materials-heading h2 {
        margin: 0;
        color: var(--text);
        font-size: 24px;
        line-height: 1.12;
    }

    .materials-heading p {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 14px;
        font-weight: 600;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        background: var(--accent-soft);
        color: var(--accent);
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .status-pill::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }

    .materials-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 18px;
        align-items: start;
    }

    .materials-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        min-width: 0;
    }

    .materials-form {
        display: grid;
        gap: 22px;
        padding: 24px;
    }

    .materials-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .materials-field {
        min-width: 0;
    }

    .materials-field label {
        display: block;
        margin: 0 0 8px;
        color: var(--text);
        font-size: 15px;
        line-height: 1.2;
        font-weight: 900;
    }

    .materials-control {
        display: flex;
        align-items: center;
        min-height: 56px;
        overflow: hidden;
        border: 1px solid var(--border);
        border-bottom: 3px solid var(--green);
        border-radius: 12px;
        background: var(--surface-2);
        transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }

    .materials-control:focus-within {
        border-color: var(--primary);
        background: var(--surface);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, .14);
    }

    .field-icon {
        align-self: stretch;
        width: 52px;
        background: color-mix(in srgb, var(--surface-2) 70%, var(--border));
        color: var(--muted);
        border-right: 1px solid var(--border);
    }

    .materials-control input,
    .materials-control select,
    .materials-control textarea {
        width: 100%;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: var(--text);
        font: inherit;
        font-size: 16px;
        font-weight: 700;
    }

    .materials-control input,
    .materials-control select {
        height: 53px;
        padding: 0 13px;
    }

    .materials-control select {
        cursor: pointer;
        background: var(--surface-2);
        color: var(--text);
    }

    .materials-control select option {
        background: #ffffff;
        color: #111827;
        font-weight: 700;
    }

    :root[data-theme="dark"] .materials-control select {
        background: var(--surface-2);
        color: var(--text);
        color-scheme: dark;
    }

    :root[data-theme="dark"] .materials-control select option {
        background: #0f1a30;
        color: #e8eef8;
    }

    :root[data-theme="dark"] .materials-control select option:checked {
        background: #1e40af;
        color: #ffffff;
    }

    .quantity-control {
        display: grid;
        grid-template-columns: 42px 1fr 42px;
        align-items: center;
        width: 100%;
        padding: 0 8px;
    }

    .quantity-control button {
        width: 34px;
        height: 34px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        color: var(--primary);
        font-size: 21px;
        font-weight: 900;
        cursor: pointer;
    }

    .quantity-control input {
        text-align: center;
        padding: 0;
    }

    .segmented {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .segment {
        min-height: 42px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--surface);
        color: var(--muted);
        font: inherit;
        font-size: 14px;
        font-weight: 900;
        cursor: pointer;
    }

    .segment.is-active {
        border-color: var(--primary);
        background: var(--primary-soft);
        color: var(--primary);
    }

    .textarea-control {
        align-items: stretch;
        min-height: 152px;
    }

    .textarea-control .field-icon {
        align-items: flex-start;
        padding-top: 16px;
    }

    .textarea-control textarea {
        min-height: 149px;
        padding: 14px;
        resize: vertical;
        line-height: 1.5;
        font-weight: 650;
    }

    .materials-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        padding-top: 4px;
    }

    .materials-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 0 18px;
        border-radius: 12px;
        font: inherit;
        font-weight: 900;
        cursor: pointer;
    }

    .materials-btn.ghost {
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--primary);
    }

    .materials-btn.ghost:hover {
        background: var(--primary-soft);
    }

    .materials-btn.primary {
        border: 0;
        background: var(--primary);
        color: #fff;
        box-shadow: 0 10px 22px rgba(0, 122, 255, .18);
    }

    .materials-btn.primary:hover {
        background: var(--primary-strong);
    }

    .side-panel {
        overflow: hidden;
    }

    .side-head {
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
    }

    .side-head h3 {
        margin: 0;
        color: var(--text);
        font-size: 17px;
    }

    .side-head p {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
    }

    .summary-list,
    .flow-list {
        display: grid;
        gap: 13px;
        padding: 16px 20px 18px;
    }

    .summary-item,
    .flow-step {
        display: grid;
        grid-template-columns: 30px 1fr;
        gap: 10px;
        align-items: start;
    }

    .summary-dot {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        background: var(--green-soft);
        color: var(--green);
        font-size: 13px;
        font-weight: 900;
    }

    .summary-item strong,
    .flow-step strong {
        display: block;
        color: var(--text);
        font-size: 14px;
    }

    .summary-item span,
    .flow-step span {
        color: var(--muted);
        font-size: 12.5px;
        line-height: 1.35;
        font-weight: 700;
    }

    .flow-list {
        padding-top: 0;
    }

    .flow-number {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        background: var(--surface-2);
        color: var(--muted);
        font-size: 13px;
        font-weight: 900;
    }

    .flow-step.is-current .flow-number {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .approvals-panel {
        overflow: hidden;
    }

    .approvals-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
    }

    .approvals-head h3 {
        margin: 0;
        color: var(--text);
        font-size: 18px;
    }

    .approvals-head p {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .approvals-count {
        min-height: 32px;
        padding: 0 12px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .approvals-table-wrap {
        overflow-x: auto;
    }

    .approvals-table {
        width: 100%;
        min-width: 860px;
        border-collapse: collapse;
        color: var(--text);
    }

    .approvals-table th {
        padding: 14px 16px;
        background: var(--surface-2);
        border-bottom: 1px solid var(--border);
        color: var(--muted);
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        text-transform: uppercase;
    }

    .approvals-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
        font-weight: 800;
        vertical-align: middle;
    }

    .approvals-table td small {
        display: block;
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
    }

    .approval-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .approval-status.pending {
        background: var(--accent-soft);
        color: var(--accent);
    }

    .approval-status.approved {
        background: var(--green-soft);
        color: var(--green);
    }

    .approval-status.rejected {
        background: var(--danger-soft);
        color: var(--danger);
    }

    .approval-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .approval-action {
        min-height: 32px;
        padding: 0 11px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .approval-action.approve {
        border-color: color-mix(in srgb, var(--green) 45%, var(--border));
        color: var(--green);
    }

    .approval-action.reject {
        border-color: color-mix(in srgb, var(--danger) 45%, var(--border));
        color: var(--danger);
    }

    .approval-action:hover {
        background: var(--surface-2);
    }

    .approval-action:disabled {
        cursor: not-allowed;
        opacity: .45;
    }

    @media (max-width: 1080px) {
        .materials-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 680px) {
        .materials-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .materials-row,
        .segmented {
            grid-template-columns: 1fr;
        }

        .materials-form {
            padding: 18px;
        }

        .materials-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .materials-btn {
            width: 100%;
        }
    }

    /* Ajustes de diseño para coincidir con el dashboard */
    .materials-field label {
        font-size: 13px;
        font-weight: 600;
        margin: 0 0 6px;
        color: var(--text);
    }

    .materials-control {
        min-height: auto;
        border: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        transition: border-color .15s, box-shadow .15s;
    }

    .materials-control:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, .15);
        background: var(--surface);
    }

    .materials-control .field-icon {
        width: 42px;
        background: transparent;
        border-right: none;
        color: var(--muted);
    }

    .materials-control input,
    .materials-control select,
    .materials-control textarea {
        height: auto;
        padding: 11px 12px;
        font-size: 15px;
        font-weight: 500;
    }

    .materials-control select {
        background: var(--surface);
    }

    .textarea-control {
        min-height: auto;
    }

    .textarea-control .field-icon {
        align-items: flex-start;
        padding-top: 14px;
    }

    .textarea-control textarea {
        min-height: 120px;
        padding: 14px;
    }

    .quantity-control input {
        padding: 0;
        text-align: center;
    }

    .materials-btn {
        min-height: auto;
        padding: 11px 16px;
        border-radius: 10px;
        font-size: 14.5px;
        font-weight: 600;
    }

    .materials-btn.primary {
        background: var(--primary);
        color: #fff;
        border: none;
        box-shadow: none;
    }

    .materials-btn.primary:hover {
        background: var(--primary-strong);
    }

    .materials-btn.ghost {
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--border);
    }

    .materials-btn.ghost:hover {
        background: var(--surface-2);
    }
</style>
@endpush

@section('content')
    <section class="materials-page">
        <nav class="materials-crumb" aria-label="Ruta">
            <a href="{{ route('dashboard') }}">Gestion Administrativa</a>
            <span>&gt;</span>
            <strong>Solicitud de materiales</strong>
        </nav>

        <div class="materials-header">
            <div class="materials-heading">
                <span class="materials-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4z"></path>
                        <path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"></path>
                    </svg>
                </span>
                <div>
                    <h2>Nueva solicitud</h2>
                    <p>Registra el insumo, cantidad y justificación para revisión.</p>
                </div>
            </div>
            <span class="status-pill">Borrador</span>
        </div>

        <div class="materials-layout">
            <section class="materials-panel" aria-label="Formulario para solicitar material">
                <form class="materials-form" id="materialsForm" method="POST" action="{{ route('admin.materials.store') }}">
                    @csrf

                    @if ($errors->any())
                        <div class="materials-panel" style="padding:12px 14px;border-color:rgba(220,38,38,.32);color:#dc2626;font-weight:800;">
                            Revisa los campos de la solicitud antes de enviarla.
                        </div>
                    @endif
                    <div class="materials-field">
                        <label for="category">Categoría</label>
                        <div class="materials-control">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16v16H4z"></path>
                                    <path d="M8 4v16M4 9h16M4 15h16"></path>
                                </svg>
                            </span>
                            <select id="category" name="category">
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="materials-row">
                        <div class="materials-field">
                            <label for="material">Material/Equipo/Etc</label>
                            <div class="materials-control">
                                <span class="field-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4z"></path>
                                        <path d="M3.27 6.96 12 12l8.73-5.04"></path>
                                    </svg>
                                </span>
                                <input id="material" name="material_name" type="text" value="{{ old('material_name') }}" autocomplete="off" placeholder="Ej. hojas carta, guantes, cable HDMI" required>
                            </div>
                        </div>

                        <div class="materials-field">
                            <label for="quantity">Cantidad</label>
                            <div class="materials-control">
                                <span class="field-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 7h8M8 12h8M8 17h8"></path>
                                        <path d="M4 7h.01M4 12h.01M4 17h.01"></path>
                                    </svg>
                                </span>
                                <div class="quantity-control">
                                    <button type="button" onclick="adjustQuantity(-1)" aria-label="Disminuir cantidad">-</button>
                                    <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" inputmode="numeric" required>
                                    <button type="button" onclick="adjustQuantity(1)" aria-label="Aumentar cantidad">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="materials-row">
                        <div class="materials-field">
                            <label for="unit">Unidad</label>
                            <div class="materials-control">
                                <span class="field-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 7h16M4 12h16M4 17h10"></path>
                                    </svg>
                                </span>
                                <select id="unit" name="unit">
                                    @foreach (['Pieza', 'Paquete', 'Caja', 'Kit', 'Servicio'] as $unitOption)
                                        <option value="{{ $unitOption }}" @selected(old('unit', 'Pieza') === $unitOption)>{{ $unitOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="materials-field">
                            <label for="required-date">Fecha requerida</label>
                            <div class="materials-control">
                                <span class="field-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    </svg>
                                </span>
                                <input id="required-date" name="required_date" type="date" value="{{ old('required_date', now()->addDay()->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>

                    <div class="materials-field">
                        <label>Urgencia</label>
                        <div class="segmented" role="group" aria-label="Urgencia">
                            <button class="segment {{ $selectedUrgency === 'Normal' ? 'is-active' : '' }}" type="button" data-urgency="Normal">Normal</button>
                            <button class="segment {{ $selectedUrgency === 'Urgente' ? 'is-active' : '' }}" type="button" data-urgency="Urgente">Urgente</button>
                            <button class="segment {{ $selectedUrgency === 'Programada' ? 'is-active' : '' }}" type="button" data-urgency="Programada">Programada</button>
                        </div>
                        <input type="hidden" id="urgency" name="urgency" value="{{ $selectedUrgency }}">
                    </div>

                    <div class="materials-field">
                        <label for="justification">Justificación</label>
                        <div class="materials-control textarea-control">
                            <span class="field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <path d="M14 2v6h6M8 13h8M8 17h5"></path>
                                </svg>
                            </span>
                            <textarea id="justification" name="justification" placeholder="Describe la necesidad del equipo o insumo solicitado, su uso y urgencia.">{{ old('justification') }}</textarea>
                        </div>
                    </div>

                    <div class="materials-actions">
                        <button class="materials-btn ghost" type="button" onclick="showMaterialToast('Borrador guardado localmente.')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <path d="M17 21v-8H7v8M7 3v5h8"></path>
                            </svg>
                            Guardar borrador
                        </button>
                        <button class="materials-btn primary" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 2 11 13"></path>
                                <path d="M22 2 15 22l-4-9-9-4 20-7z"></path>
                            </svg>
                            Enviar Solicitud
                        </button>
                    </div>
                </form>
            </section>

            <aside class="materials-panel side-panel" aria-label="Resumen de solicitud">
                <div class="side-head">
                    <h3>Resumen</h3>
                    <p>Estado actual de la solicitud</p>
                </div>

                <div class="summary-list">
                    <div class="summary-item">
                        <span class="summary-dot">1</span>
                        <span><strong id="summaryCategory">Papelería</strong>Categoría seleccionada</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-dot">2</span>
                        <span><strong id="summaryQuantity">1 Pieza</strong>Cantidad solicitada</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-dot">3</span>
                        <span><strong id="summaryUrgency">Normal</strong>Nivel de urgencia</span>
                    </div>
                </div>

                <div class="side-head">
                    <h3>Proceso</h3>
                    <p>Seguimiento administrativo</p>
                </div>

                <div class="flow-list">
                    <div class="flow-step is-current">
                        <span class="flow-number">1</span>
                        <span><strong>Solicitud</strong><span>El usuario captura y envía el requerimiento.</span></span>
                    </div>
                    <div class="flow-step">
                        <span class="flow-number">2</span>
                        <span><strong>Revisión</strong><span>Administración valida disponibilidad y prioridad.</span></span>
                    </div>
                    <div class="flow-step">
                        <span class="flow-number">3</span>
                        <span><strong>Entrega</strong><span>El encargado confirma la salida del material.</span></span>
                    </div>
                </div>
            </aside>
        </div>

        <section class="materials-panel approvals-panel" aria-label="Revision de solicitudes de material">
            <div class="approvals-head">
                <div>
                    <h3>Revision de solicitudes</h3>
                    <p>Aqui se aprueban o rechazan las solicitudes enviadas.</p>
                </div>
                <span class="approvals-count" id="approvalCount">{{ $pendingCount }} {{ $pendingCount === 1 ? 'pendiente' : 'pendientes' }}</span>
            </div>

            <div class="approvals-table-wrap">
                <table class="approvals-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Material</th>
                            <th>Cantidad</th>
                            <th>Fecha requerida</th>
                            <th>Urgencia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="materialsApprovalBody">
                        @forelse ($materialRequests as $requestRow)
                            <tr>
                                <td>{{ $requestRow['folio'] }}</td>
                                <td>{{ $requestRow['material_name'] }}<small>{{ $requestRow['category'] }}</small></td>
                                <td>{{ $requestRow['quantity'] }} {{ $requestRow['unit'] }}</td>
                                <td>{{ $requestRow['required_date'] }}</td>
                                <td>{{ $requestRow['urgency'] }}</td>
                                <td><span class="approval-status {{ $requestRow['status_class'] }}">{{ $requestRow['status_label'] }}</span></td>
                                <td>
                                    <div class="approval-actions">
                                        @if (auth()->user()?->isAdmin() && $requestRow['can_review'])
                                            <form method="POST" action="{{ route('admin.materials.review', $requestRow['id']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="approve">
                                                <button class="approval-action approve" type="submit">Aprobar</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.materials.review', $requestRow['id']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="reject">
                                                <button class="approval-action reject" type="submit">Rechazar</button>
                                            </form>
                                        @else
                                            <button class="approval-action" type="button" disabled>Sin acciones</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No hay solicitudes registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <script>
        const category = document.getElementById('category');
        const material = document.getElementById('material');
        const quantity = document.getElementById('quantity');
        const unit = document.getElementById('unit');
        const urgencyInput = document.getElementById('urgency');
        const requiredDate = document.getElementById('required-date');
        const materialsApprovalBody = document.getElementById('materialsApprovalBody');
        const approvalCount = document.getElementById('approvalCount');
        const summaryCategory = document.getElementById('summaryCategory');
        const summaryQuantity = document.getElementById('summaryQuantity');
        const summaryUrgency = document.getElementById('summaryUrgency');
        let materialRequestSequence = 9;

        function updateSummary() {
            summaryCategory.textContent = category.value;
            summaryQuantity.textContent = `${quantity.value || 1} ${unit.value}`;
        }

        function adjustQuantity(amount) {
            const current = parseInt(quantity.value || '1', 10);
            quantity.value = Math.max(1, current + amount);
            updateSummary();
        }

        function showMaterialToast(message) {
            if (typeof window.showToast === 'function') {
                window.showToast(message);
            }
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function currentUrgency() {
            return urgencyInput?.value || document.querySelector('.segment.is-active')?.dataset.urgency || 'Normal';
        }

        function updateApprovalCount() {
            const pending = materialsApprovalBody.querySelectorAll('.approval-status.pending').length;
            approvalCount.textContent = pending === 1 ? '1 pendiente' : `${pending} pendientes`;
        }

        function bindApprovalButtons(scope = document) {
            scope.querySelectorAll('[data-approval-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    const row = button.closest('tr');
                    const status = row.querySelector('.approval-status');
                    const action = button.dataset.approvalAction;
                    const approved = action === 'approve';

                    status.className = `approval-status ${approved ? 'approved' : 'rejected'}`;
                    status.textContent = approved ? 'Aprobada' : 'Rechazada';
                    row.querySelectorAll('[data-approval-action]').forEach((item) => item.disabled = true);
                    updateApprovalCount();
                    showMaterialToast(approved ? 'Solicitud aprobada.' : 'Solicitud rechazada.');
                });
            });
        }

        function submitMaterialRequest(event) {
            event.preventDefault();

            const materialName = material.value.trim() || 'Material sin nombre';
            const folio = `SOL-${String(materialRequestSequence).padStart(4, '0')}`;
            materialRequestSequence += 1;

            materialsApprovalBody.insertAdjacentHTML('afterbegin', `
                <tr>
                    <td>${folio}</td>
                    <td>${escapeHtml(materialName)}<small>${escapeHtml(category.value)}</small></td>
                    <td>${escapeHtml(quantity.value || 1)} ${escapeHtml(unit.value)}</td>
                    <td>${escapeHtml(requiredDate.value)}</td>
                    <td>${escapeHtml(currentUrgency())}</td>
                    <td><span class="approval-status pending">Pendiente</span></td>
                    <td>
                        <div class="approval-actions">
                            <button class="approval-action approve" type="button" data-approval-action="approve">Aprobar</button>
                            <button class="approval-action reject" type="button" data-approval-action="reject">Rechazar</button>
                        </div>
                    </td>
                </tr>
            `);

            bindApprovalButtons(materialsApprovalBody.firstElementChild);
            updateApprovalCount();
            showMaterialToast('Solicitud enviada a revision. Ahora aparece en Revision de solicitudes.');
        }

        category.addEventListener('change', updateSummary);
        quantity.addEventListener('input', updateSummary);
        unit.addEventListener('change', updateSummary);
        bindApprovalButtons();
        updateApprovalCount();

        document.querySelectorAll('.segment').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.segment').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                if (urgencyInput) {
                    urgencyInput.value = button.dataset.urgency;
                }
                summaryUrgency.textContent = button.dataset.urgency;
            });
        });
    </script>
@endsection
