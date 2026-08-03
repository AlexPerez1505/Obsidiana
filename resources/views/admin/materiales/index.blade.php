@extends('layouts.dashboard')
@section('title', 'Solicitud de materiales')
@section('page-title', 'Solicitud de materiales')
@section('page-sub', 'Gestion Administrativa > Solicitud de materiales')

@php
    $statusMeta = [
        'draft' => ['label' => 'Registrada', 'class' => 'draft'],
        'sent' => ['label' => 'Enviada', 'class' => 'sent'],
        'approved' => ['label' => 'Aprobada', 'class' => 'approved'],
        'delivered' => ['label' => 'Entregada', 'class' => 'delivered'],
    ];

    $requests = [
        [
            'folio' => 'SM-0261',
            'area' => 'Marketing',
            'reason' => 'Material para capacitacion interna del equipo comercial.',
            'requested_by' => 'Ricardo',
            'date' => '03 Ago 2026',
            'status' => 'sent',
            'items' => 'Plumones, hojas carta, cinta',
        ],
        [
            'folio' => 'SM-0258',
            'area' => 'Inventario',
            'reason' => 'Reposicion para kit de instalacion en campo.',
            'requested_by' => 'Jose Alex',
            'date' => '31 Jul 2026',
            'status' => 'approved',
            'items' => 'Cinchos, etiquetas, guantes',
        ],
        [
            'folio' => 'SM-0254',
            'area' => 'Servicios',
            'reason' => 'Entrega completada para mantenimiento preventivo.',
            'requested_by' => 'Marina',
            'date' => '29 Jul 2026',
            'status' => 'delivered',
            'items' => 'Alcohol, panos, tornilleria',
        ],
    ];
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
        align-items: center;
        gap: 16px;
        padding-bottom: 2px;
    }

    .materials-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .materials-heading svg {
        width: 34px;
        height: 34px;
        color: var(--primary);
        flex: 0 0 auto;
    }

    .materials-heading h2 {
        margin: 0;
        font-size: 24px;
        line-height: 1.12;
        color: var(--text);
    }

    .materials-heading p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 14px;
        font-weight: 600;
    }

    .materials-primary {
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
        font: inherit;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .22);
        white-space: nowrap;
    }

    .materials-primary:hover {
        background: #1d4ed8;
    }

    .materials-primary svg,
    .materials-light-btn svg,
    .materials-icon svg {
        width: 19px;
        height: 19px;
        flex: 0 0 auto;
    }

    .materials-flow {
        display: grid;
        grid-template-columns: repeat(4, minmax(135px, 1fr));
        gap: 12px;
    }

    .flow-step {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: center;
        min-height: 70px;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
    }

    .flow-step.is-current {
        border-color: rgba(37, 99, 235, .48);
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .28);
    }

    .flow-step strong {
        display: block;
        font-size: 13.5px;
        color: var(--text);
    }

    .flow-step span {
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
    }

    .materials-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-soft);
        color: var(--primary);
    }

    .materials-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr);
        gap: 18px;
        align-items: start;
    }

    .materials-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: var(--shadow);
    }

    .materials-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
    }

    .materials-panel-head h3 {
        margin: 0;
        color: var(--text);
        font-size: 17px;
    }

    .materials-panel-head p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
    }

    .materials-form {
        display: grid;
        gap: 14px;
        padding: 18px 20px 20px;
    }

    .materials-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .materials-field label {
        margin: 0 0 7px;
    }

    .materials-field input,
    .materials-field select,
    .materials-field textarea {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        outline: none;
    }

    .materials-field textarea {
        min-height: 94px;
        resize: vertical;
    }

    .materials-field input:focus,
    .materials-field select:focus,
    .materials-field textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, .12);
    }

    .materials-items {
        display: grid;
        gap: 9px;
    }

    .materials-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 96px 128px 38px;
        gap: 9px;
        align-items: end;
    }

    .materials-remove {
        width: 38px;
        height: 38px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--danger);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .materials-remove:hover {
        background: var(--danger-soft);
    }

    .materials-remove svg {
        width: 17px;
        height: 17px;
    }

    .materials-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding-top: 4px;
    }

    .materials-light-btn {
        min-height: 40px;
        padding: 0 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .materials-light-btn:hover {
        background: var(--primary-soft);
        border-color: rgba(37, 99, 235, .32);
    }

    .materials-side {
        display: grid;
        gap: 18px;
    }

    .delivery-box {
        padding: 18px 20px;
    }

    .delivery-status {
        display: grid;
        gap: 12px;
    }

    .delivery-row {
        display: grid;
        grid-template-columns: 34px 1fr;
        gap: 10px;
        align-items: start;
    }

    .delivery-dot {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--surface-2);
        color: var(--muted);
        border: 1px solid var(--border);
        font-weight: 900;
    }

    .delivery-row.is-ready .delivery-dot {
        background: var(--green-soft);
        color: var(--green);
    }

    .delivery-row strong {
        display: block;
        color: var(--text);
        font-size: 14px;
    }

    .delivery-row span {
        color: var(--muted);
        display: block;
        margin-top: 2px;
        font-size: 12.5px;
        font-weight: 600;
        line-height: 1.35;
    }

    .requests-list {
        display: grid;
        gap: 10px;
        padding: 12px;
    }

    .request-card {
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 13px;
        background: var(--surface);
    }

    .request-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .request-top strong {
        color: var(--text);
        font-size: 14.5px;
    }

    .request-top small {
        display: block;
        color: var(--muted);
        margin-top: 2px;
        font-weight: 700;
    }

    .request-card p {
        margin: 0 0 8px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.35;
    }

    .request-items {
        color: var(--text);
        font-size: 12.5px;
        font-weight: 800;
    }

    .status-pill {
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 900;
        white-space: nowrap;
    }

    .status-pill.draft {
        color: #6b4f00;
        background: #fff4c2;
    }

    .status-pill.sent {
        color: #075985;
        background: #dff3ff;
    }

    .status-pill.approved {
        color: #166534;
        background: #dcfce7;
    }

    .status-pill.delivered {
        color: #4338ca;
        background: #e0e7ff;
    }

    :root[data-theme="dark"] .status-pill.draft {
        background: rgba(245, 158, 11, .18);
        color: #facc15;
    }

    :root[data-theme="dark"] .status-pill.sent {
        background: rgba(14, 165, 233, .16);
        color: #7dd3fc;
    }

    :root[data-theme="dark"] .status-pill.approved {
        background: rgba(34, 197, 94, .15);
        color: #86efac;
    }

    :root[data-theme="dark"] .status-pill.delivered {
        background: rgba(99, 102, 241, .18);
        color: #c4b5fd;
    }

    @media (max-width: 1100px) {
        .materials-layout,
        .materials-flow {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 760px) {
        .materials-header,
        .materials-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .materials-primary,
        .materials-light-btn {
            width: 100%;
        }

        .materials-layout,
        .materials-flow,
        .materials-form-grid {
            grid-template-columns: 1fr;
        }

        .materials-item-row {
            grid-template-columns: 1fr;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
        }

        .materials-remove {
            width: 100%;
        }
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"></path>
                </svg>
                <div>
                    <h2>Solicitud de materiales</h2>
                    <p>Registra el motivo, envia a revision y controla la entrega.</p>
                </div>
            </div>
            <button class="materials-primary" type="button" onclick="document.getElementById('material-reason').focus();">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                Nueva solicitud
            </button>
        </div>

        <div class="materials-flow" aria-label="Flujo de solicitud">
            <div class="flow-step is-current">
                <span class="materials-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13h8M8 17h5"></path></svg>
                </span>
                <span><strong>Registro</strong><span>Captura motivo y materiales</span></span>
            </div>
            <div class="flow-step">
                <span class="materials-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"></path><path d="M22 2 15 22l-4-9-9-4 20-7z"></path></svg>
                </span>
                <span><strong>Envio</strong><span>La solicitud pasa al encargado</span></span>
            </div>
            <div class="flow-step">
                <span class="materials-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                </span>
                <span><strong>Aprobacion</strong><span>Revision de materiales</span></span>
            </div>
            <div class="flow-step">
                <span class="materials-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7 9 18l-5-5"></path><path d="M21 11.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </span>
                <span><strong>Entrega</strong><span>Encargado entrega material</span></span>
            </div>
        </div>

        <div class="materials-layout">
            <div class="materials-panel">
                <div class="materials-panel-head">
                    <div>
                        <h3>Registrar solicitud</h3>
                        <p>El motivo ayuda al encargado a aprobar y preparar la entrega.</p>
                    </div>
                    <span class="status-pill draft">Borrador</span>
                </div>

                <form class="materials-form" onsubmit="event.preventDefault(); showMaterialToast();">
                    <div class="materials-form-grid">
                        <div class="materials-field">
                            <label for="material-area">Area solicitante</label>
                            <select id="material-area">
                                <option>Gestion Administrativa</option>
                                <option>Marketing</option>
                                <option>Inventario</option>
                                <option>Servicios</option>
                                <option>Comercial</option>
                            </select>
                        </div>
                        <div class="materials-field">
                            <label for="material-priority">Prioridad</label>
                            <select id="material-priority">
                                <option>Normal</option>
                                <option>Urgente</option>
                                <option>Programada</option>
                            </select>
                        </div>
                    </div>

                    <div class="materials-form-grid">
                        <div class="materials-field">
                            <label for="material-date">Fecha requerida</label>
                            <input id="material-date" type="date" value="2026-08-05">
                        </div>
                        <div class="materials-field">
                            <label for="material-recipient">Recibe</label>
                            <input id="material-recipient" type="text" value="Ricardo">
                        </div>
                    </div>

                    <div class="materials-field">
                        <label for="material-reason">Motivo de la solicitud</label>
                        <textarea id="material-reason">Se requiere material para preparar una entrega operativa y dejar evidencia del uso.</textarea>
                    </div>

                    <div class="materials-items" id="materialsItems">
                        <div class="materials-item-row">
                            <div class="materials-field">
                                <label>Material</label>
                                <input type="text" value="Hojas carta">
                            </div>
                            <div class="materials-field">
                                <label>Cantidad</label>
                                <input type="text" value="2">
                            </div>
                            <div class="materials-field">
                                <label>Unidad</label>
                                <select>
                                    <option>Paquete</option>
                                    <option>Pieza</option>
                                    <option>Caja</option>
                                    <option>Kit</option>
                                </select>
                            </div>
                            <button class="materials-remove" type="button" aria-label="Quitar material" onclick="removeMaterialRow(this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="materials-item-row">
                            <div class="materials-field">
                                <label>Material</label>
                                <input type="text" value="Plumones">
                            </div>
                            <div class="materials-field">
                                <label>Cantidad</label>
                                <input type="text" value="6">
                            </div>
                            <div class="materials-field">
                                <label>Unidad</label>
                                <select>
                                    <option>Pieza</option>
                                    <option>Paquete</option>
                                    <option>Caja</option>
                                    <option>Kit</option>
                                </select>
                            </div>
                            <button class="materials-remove" type="button" aria-label="Quitar material" onclick="removeMaterialRow(this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="materials-actions">
                        <button class="materials-light-btn" type="button" onclick="addMaterialRow()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                            Agregar material
                        </button>

                        <button class="materials-primary" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"></path><path d="M22 2 15 22l-4-9-9-4 20-7z"></path></svg>
                            Enviar solicitud
                        </button>
                    </div>
                </form>
            </div>

            <aside class="materials-side">
                <div class="materials-panel">
                    <div class="materials-panel-head">
                        <div>
                            <h3>Aprobacion y entrega</h3>
                            <p>Vista del encargado de materiales.</p>
                        </div>
                    </div>
                    <div class="delivery-box">
                        <div class="delivery-status">
                            <div class="delivery-row is-ready">
                                <span class="delivery-dot">1</span>
                                <span><strong>Solicitud recibida</strong><span>Se revisa motivo, cantidades y disponibilidad.</span></span>
                            </div>
                            <div class="delivery-row is-ready">
                                <span class="delivery-dot">2</span>
                                <span><strong>Aprobacion del encargado</strong><span>Si procede, se reserva el material para entrega.</span></span>
                            </div>
                            <div class="delivery-row">
                                <span class="delivery-dot">3</span>
                                <span><strong>Entrega fisica</strong><span>El encargado confirma quien recibe y fecha de salida.</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="materials-panel">
                    <div class="materials-panel-head">
                        <div>
                            <h3>Solicitudes recientes</h3>
                            <p>Ejemplos visuales del flujo.</p>
                        </div>
                    </div>
                    <div class="requests-list">
                        @foreach ($requests as $request)
                            @php($meta = $statusMeta[$request['status']])
                            <article class="request-card">
                                <div class="request-top">
                                    <span>
                                        <strong>{{ $request['folio'] }}</strong>
                                        <small>{{ $request['area'] }} - {{ $request['date'] }}</small>
                                    </span>
                                    <span class="status-pill {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                </div>
                                <p>{{ $request['reason'] }}</p>
                                <div class="request-items">{{ $request['items'] }}</div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <script>
        function addMaterialRow() {
            const container = document.getElementById('materialsItems');
            const template = container.querySelector('.materials-item-row').cloneNode(true);

            template.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });

            template.querySelector('select').selectedIndex = 0;
            container.appendChild(template);
            template.querySelector('input').focus();
        }

        function removeMaterialRow(button) {
            const rows = document.querySelectorAll('.materials-item-row');
            if (rows.length === 1) {
                rows[0].querySelectorAll('input').forEach((input) => {
                    input.value = '';
                });
                return;
            }

            button.closest('.materials-item-row').remove();
        }

        function showMaterialToast() {
            if (typeof window.showToast === 'function') {
                window.showToast('Solicitud enviada a revision del encargado.');
            }
        }
    </script>
@endsection
