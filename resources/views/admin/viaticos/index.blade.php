@extends('layouts.dashboard')
@section('title', 'Viaticos')
@section('page-title', 'Viaticos')
@section('page-sub', 'Gestion Administrativa > Viaticos')

@php
    $requests = [
        ['folio' => 'VT-0307', 'client' => 'Ricardo', 'destination' => 'Puebla', 'date' => '05 Ago 2026', 'amount' => '$3,850.00', 'status' => 'sent', 'reason' => 'Visita con cliente para entrega y capacitacion de equipo.'],
        ['folio' => 'VT-0304', 'client' => 'Marina Sherlyn', 'destination' => 'CDMX', 'date' => '02 Ago 2026', 'amount' => '$2,200.00', 'status' => 'approved', 'reason' => 'Reunion comercial y seguimiento de propuesta.'],
        ['folio' => 'VT-0299', 'client' => 'Jose Alex', 'destination' => 'Toluca', 'date' => '28 Jul 2026', 'amount' => '$1,480.00', 'status' => 'review', 'reason' => 'Servicio tecnico fuera de oficina con observacion de comprobantes.'],
    ];

    $statusMeta = [
        'draft' => ['label' => 'Borrador', 'class' => 'draft'],
        'sent' => ['label' => 'En revision', 'class' => 'sent'],
        'review' => ['label' => 'Observacion', 'class' => 'review'],
        'approved' => ['label' => 'Aprobado', 'class' => 'approved'],
    ];
@endphp

@push('head')
<style>
    .viatics-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .viatics-crumb {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .viatics-crumb a {
        color: var(--primary);
        text-decoration: none;
    }

    .viatics-header,
    .viatics-actions,
    .viatics-panel-head,
    .request-top,
    .request-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .viatics-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .viatics-heading svg {
        width: 34px;
        height: 34px;
        color: var(--primary);
        flex: 0 0 auto;
    }

    .viatics-heading h2 {
        margin: 0;
        font-size: 24px;
        line-height: 1.12;
        color: var(--text);
    }

    .viatics-heading p,
    .viatics-panel-head p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 13.5px;
        font-weight: 600;
    }

    .viatics-primary,
    .viatics-secondary,
    .viatics-action {
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .viatics-primary {
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

    .viatics-primary:hover {
        background: #1d4ed8;
    }

    .viatics-secondary {
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
    }

    .viatics-secondary:hover {
        background: var(--primary-soft);
        border-color: rgba(37, 99, 235, .35);
    }

    .viatics-primary svg,
    .viatics-secondary svg,
    .viatics-icon svg,
    .review-icon svg {
        width: 19px;
        height: 19px;
        flex: 0 0 auto;
    }

    .viatics-flow {
        display: grid;
        grid-template-columns: repeat(4, minmax(150px, 1fr));
        gap: 12px;
    }

    .viatics-step {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: center;
        min-height: 74px;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        box-shadow: var(--shadow);
    }

    .viatics-step.is-current {
        border-color: rgba(37, 99, 235, .48);
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .25), var(--shadow);
    }

    .viatics-step strong,
    .review-item strong,
    .request-top strong {
        display: block;
        color: var(--text);
    }

    .viatics-step strong {
        font-size: 13.5px;
    }

    .viatics-step span span,
    .review-item span,
    .request-top small {
        display: block;
        margin-top: 2px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
    }

    .viatics-icon,
    .review-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .viatics-icon {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .viatics-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, .7fr);
        gap: 18px;
        align-items: start;
    }

    .viatics-panel {
        min-width: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: var(--shadow);
    }

    .viatics-panel-head {
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
    }

    .viatics-panel-head h3 {
        margin: 0;
        color: var(--text);
        font-size: 17px;
    }

    .viatics-form {
        display: grid;
        gap: 14px;
        padding: 18px 20px 20px;
    }

    .viatics-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .viatics-field label {
        margin: 0 0 7px;
    }

    .viatics-field input,
    .viatics-field select,
    .viatics-field textarea {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        outline: none;
    }

    .viatics-field textarea {
        min-height: 92px;
        resize: vertical;
    }

    .viatics-field input:focus,
    .viatics-field select:focus,
    .viatics-field textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, .12);
    }

    .expense-list {
        display: grid;
        gap: 9px;
    }

    .expense-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 120px 38px;
        gap: 9px;
        align-items: end;
    }

    .expense-remove {
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

    .expense-remove:hover {
        background: var(--danger-soft);
    }

    .expense-remove svg {
        width: 17px;
        height: 17px;
    }

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

    .status-pill.draft { color: #6b4f00; background: #fff4c2; }
    .status-pill.sent { color: #075985; background: #dff3ff; }
    .status-pill.review { color: #b45309; background: #ffedd5; }
    .status-pill.approved { color: #166534; background: #dcfce7; }

    .viatics-side {
        display: grid;
        gap: 18px;
    }

    .admin-review {
        padding: 18px 20px;
        display: grid;
        gap: 14px;
    }

    .review-item {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 10px;
        align-items: start;
    }

    .review-icon {
        background: var(--surface-2);
        color: var(--muted);
        border: 1px solid var(--border);
    }

    .review-item.is-ready .review-icon {
        background: var(--green-soft);
        color: var(--green);
    }

    .decision-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding-top: 4px;
    }

    .viatics-action {
        min-height: 40px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .viatics-action.approve {
        color: var(--green);
        background: var(--green-soft);
    }

    .viatics-action.reject {
        color: var(--danger);
        background: var(--danger-soft);
    }

    .request-list {
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
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .request-card p {
        margin: 0 0 8px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.35;
    }

    .request-meta {
        color: var(--text);
        font-size: 12.5px;
        font-weight: 900;
    }

    :root[data-theme="dark"] .status-pill.draft {
        background: rgba(245, 158, 11, .18);
        color: #facc15;
    }

    :root[data-theme="dark"] .status-pill.sent {
        background: rgba(14, 165, 233, .16);
        color: #7dd3fc;
    }

    :root[data-theme="dark"] .status-pill.review {
        background: rgba(217, 119, 6, .18);
        color: #fdba74;
    }

    :root[data-theme="dark"] .status-pill.approved {
        background: rgba(34, 197, 94, .15);
        color: #86efac;
    }

    @media (max-width: 1100px) {
        .viatics-layout,
        .viatics-flow {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 760px) {
        .viatics-header,
        .viatics-actions,
        .viatics-panel-head {
            align-items: stretch;
            flex-direction: column;
        }

        .viatics-primary,
        .viatics-secondary {
            width: 100%;
        }

        .viatics-layout,
        .viatics-flow,
        .viatics-form-grid,
        .expense-row,
        .decision-box {
            grid-template-columns: 1fr;
        }

        .expense-row {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
        }

        .expense-remove {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    <section class="viatics-page">
        <nav class="viatics-crumb" aria-label="Ruta">
            <a href="{{ route('dashboard') }}">Gestion Administrativa</a>
            <span>&gt;</span>
            <strong>Viaticos</strong>
        </nav>

        <div class="viatics-header">
            <div class="viatics-heading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18"></path>
                    <path d="M3 12h18"></path>
                    <path d="M3 18h18"></path>
                    <path d="M7 3v18"></path>
                    <path d="M17 3v18"></path>
                </svg>
                <div>
                    <h2>Solicitud de viaticos</h2>
                    <p>El cliente envia la solicitud y el administrador la revisa para aprobarla.</p>
                </div>
            </div>

            <button class="viatics-primary" type="button" onclick="document.getElementById('viatics-reason').focus();">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                Nueva solicitud
            </button>
        </div>

        <div class="viatics-flow" aria-label="Flujo de viaticos">
            <div class="viatics-step is-current">
                <span class="viatics-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <span><strong>Cliente</strong><span>Captura motivo e importe</span></span>
            </div>
            <div class="viatics-step">
                <span class="viatics-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"></path><path d="M22 2 15 22l-4-9-9-4 20-7z"></path></svg>
                </span>
                <span><strong>Envio</strong><span>Llega al administrador</span></span>
            </div>
            <div class="viatics-step">
                <span class="viatics-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </span>
                <span><strong>Revision</strong><span>Admin valida datos</span></span>
            </div>
            <div class="viatics-step">
                <span class="viatics-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                </span>
                <span><strong>Aprobacion</strong><span>Se libera el viatico</span></span>
            </div>
        </div>

        <div class="viatics-layout">
            <div class="viatics-panel">
                <div class="viatics-panel-head">
                    <div>
                        <h3>Captura de solicitud</h3>
                        <p>Informacion que envia el cliente al administrador.</p>
                    </div>
                    <span class="status-pill draft">Borrador</span>
                </div>

                <form class="viatics-form" onsubmit="event.preventDefault(); sendViaticsRequest();">
                    <div class="viatics-form-grid">
                        <div class="viatics-field">
                            <label for="viatics-client">Cliente / solicitante</label>
                            <input id="viatics-client" type="text" value="Ricardo">
                        </div>
                        <div class="viatics-field">
                            <label for="viatics-area">Area</label>
                            <select id="viatics-area">
                                <option>Gestion Administrativa</option>
                                <option>Comercial</option>
                                <option>Servicios</option>
                                <option>Inventario</option>
                                <option>Marketing</option>
                            </select>
                        </div>
                    </div>

                    <div class="viatics-form-grid">
                        <div class="viatics-field">
                            <label for="viatics-destination">Destino</label>
                            <input id="viatics-destination" type="text" value="Puebla">
                        </div>
                        <div class="viatics-field">
                            <label for="viatics-priority">Prioridad</label>
                            <select id="viatics-priority">
                                <option>Normal</option>
                                <option>Urgente</option>
                                <option>Programada</option>
                            </select>
                        </div>
                    </div>

                    <div class="viatics-form-grid">
                        <div class="viatics-field">
                            <label for="viatics-start">Salida</label>
                            <input id="viatics-start" type="date" value="2026-08-05">
                        </div>
                        <div class="viatics-field">
                            <label for="viatics-end">Regreso</label>
                            <input id="viatics-end" type="date" value="2026-08-06">
                        </div>
                    </div>

                    <div class="viatics-field">
                        <label for="viatics-reason">Motivo del viatico</label>
                        <textarea id="viatics-reason">Visita con cliente para entrega de equipo, capacitacion y validacion del servicio contratado.</textarea>
                    </div>

                    <div class="expense-list" id="expenseList">
                        <div class="expense-row">
                            <div class="viatics-field">
                                <label>Concepto</label>
                                <select>
                                    <option>Transporte</option>
                                    <option>Alimentos</option>
                                    <option>Hospedaje</option>
                                    <option>Casetas</option>
                                    <option>Otro</option>
                                </select>
                            </div>
                            <div class="viatics-field">
                                <label>Monto</label>
                                <input type="text" value="$1,200.00">
                            </div>
                            <button class="expense-remove" type="button" aria-label="Quitar concepto" onclick="removeExpenseRow(this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="expense-row">
                            <div class="viatics-field">
                                <label>Concepto</label>
                                <select>
                                    <option>Alimentos</option>
                                    <option>Transporte</option>
                                    <option>Hospedaje</option>
                                    <option>Casetas</option>
                                    <option>Otro</option>
                                </select>
                            </div>
                            <div class="viatics-field">
                                <label>Monto</label>
                                <input type="text" value="$650.00">
                            </div>
                            <button class="expense-remove" type="button" aria-label="Quitar concepto" onclick="removeExpenseRow(this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="viatics-actions">
                        <button class="viatics-secondary" type="button" onclick="addExpenseRow()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                            Agregar concepto
                        </button>

                        <button class="viatics-primary" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"></path><path d="M22 2 15 22l-4-9-9-4 20-7z"></path></svg>
                            Enviar al administrador
                        </button>
                    </div>
                </form>
            </div>

            <aside class="viatics-side">
                <div class="viatics-panel">
                    <div class="viatics-panel-head">
                        <div>
                            <h3>Revision del administrador</h3>
                            <p>Panel visual para decidir la solicitud.</p>
                        </div>
                    </div>
                    <div class="admin-review">
                        <div class="review-item is-ready">
                            <span class="review-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                            </span>
                            <span><strong>Solicitud recibida</strong><span>El administrador revisa destino, motivo y fechas.</span></span>
                        </div>
                        <div class="review-item is-ready">
                            <span class="review-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3h18v18H3z"></path><path d="M8 12h8M8 16h5M8 8h8"></path></svg>
                            </span>
                            <span><strong>Validacion de presupuesto</strong><span>Se compara el monto estimado con el gasto permitido.</span></span>
                        </div>
                        <div class="review-item">
                            <span class="review-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                            </span>
                            <span><strong>Decision final</strong><span>El administrador aprueba o rechaza la solicitud.</span></span>
                        </div>

                        <div class="decision-box">
                            <button class="viatics-action approve" type="button" onclick="approveViatics()">Aprobar</button>
                            <button class="viatics-action reject" type="button" onclick="rejectViatics()">Rechazar</button>
                        </div>
                    </div>
                </div>

                <div class="viatics-panel">
                    <div class="viatics-panel-head">
                        <div>
                            <h3>Solicitudes recientes</h3>
                            <p>Seguimiento del flujo.</p>
                        </div>
                    </div>

                    <div class="request-list">
                        @foreach ($requests as $request)
                            @php($meta = $statusMeta[$request['status']])
                            <article class="request-card">
                                <div class="request-top">
                                    <span>
                                        <strong>{{ $request['folio'] }} - {{ $request['client'] }}</strong>
                                        <small>{{ $request['destination'] }} - {{ $request['date'] }}</small>
                                    </span>
                                    <span class="status-pill {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                </div>
                                <p>{{ $request['reason'] }}</p>
                                <div class="request-meta">
                                    <span>{{ $request['amount'] }}</span>
                                    <span>Admin</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <script>
        function addExpenseRow() {
            const container = document.getElementById('expenseList');
            const template = container.querySelector('.expense-row').cloneNode(true);

            template.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });

            template.querySelector('select').selectedIndex = 0;
            container.appendChild(template);
            template.querySelector('input').focus();
        }

        function removeExpenseRow(button) {
            const rows = document.querySelectorAll('.expense-row');
            if (rows.length === 1) {
                rows[0].querySelectorAll('input').forEach((input) => {
                    input.value = '';
                });
                return;
            }

            button.closest('.expense-row').remove();
        }

        function sendViaticsRequest() {
            if (typeof window.showToast === 'function') {
                window.showToast('Solicitud enviada al administrador para revision.');
            }
        }

        function approveViatics() {
            if (typeof window.showToast === 'function') {
                window.showToast('Solicitud de viaticos aprobada.');
            }
        }

        function rejectViatics() {
            if (typeof window.showToast === 'function') {
                window.showToast('Solicitud marcada para correccion.', 'warn');
            }
        }
    </script>
@endsection
