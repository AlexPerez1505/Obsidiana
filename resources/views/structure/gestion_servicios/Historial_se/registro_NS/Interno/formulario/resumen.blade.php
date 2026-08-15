@extends('structure.gestion_servicios.layout')

@section('title', 'Resumen de la orden')

@section('service_content')
    <style>
        .ns-page { max-width: 1000px; margin: 0 auto; }

        .ns-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 22px;
        }
        .ns-header-title { display: flex; align-items: center; gap: 14px; }
        .ns-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(0,122,255,0.12); color: #007AFF;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .ns-icon svg { width: 24px; height: 24px; }
        .ns-header-title h2 { margin: 0; font-size: 22px; color: #fff; }
        :root[data-theme="light"] .ns-header-title h2 { color: var(--text); }
        .ns-header-title p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 13px; }
        :root[data-theme="light"] .ns-header-title p { color: var(--muted); }
        .ns-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ns-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: all .16s ease; border: none;
        }
        .ns-btn svg { width: 16px; height: 16px; }
        .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
        :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-btn--primary { background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
        .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }

        .ns-stepper {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 24px; padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-stepper { border-color: rgba(15,23,42,0.08); }
        .ns-step { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.45); }
        :root[data-theme="light"] .ns-step { color: var(--muted); }
        .ns-step-number {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800;
            background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);
        }
        :root[data-theme="light"] .ns-step-number { background: rgba(15,23,42,0.08); color: var(--muted); }
        .ns-step.completed { color: #22C55E; }
        :root[data-theme="light"] .ns-step.completed { color: #16A34A; }
        .ns-step.completed .ns-step-number { background: #22C55E; color: #fff; }

        .ns-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;
        }
        @media (max-width: 860px) { .ns-grid { grid-template-columns: 1fr; } }

        .ns-card {
            background: rgba(8,18,40,0.45);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 22px;
        }
        :root[data-theme="light"] .ns-card { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); }
        .ns-card-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 18px;
        }
        :root[data-theme="light"] .ns-card-title { color: var(--text); }
        .ns-card-title svg { width: 18px; height: 18px; color: #007AFF; }

        .ns-specs { display: flex; flex-direction: column; gap: 2px; }
        .ns-spec-row {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        :root[data-theme="light"] .ns-spec-row { border-color: rgba(15,23,42,0.08); }
        .ns-spec-row:last-child { border-bottom: none; }
        .ns-spec-row span:first-child {
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.6); letter-spacing: 0.5px;
        }
        :root[data-theme="light"] .ns-spec-row span:first-child { color: var(--muted); }
        .ns-spec-row span:last-child {
            font-size: 13px; color: #fff; text-align: right;
        }
        :root[data-theme="light"] .ns-spec-row span:last-child { color: var(--text); }
        .ns-status-pending {
            display: inline-flex; align-items: center; gap: 6px;
            color: #F59E0B !important; font-weight: 700;
        }
        .ns-status-pending svg { width: 14px; height: 14px; }

        .ns-quote-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .ns-quote-table th {
            text-align: right; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.55);
            padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .ns-quote-table th:first-child { text-align: left; }
        :root[data-theme="light"] .ns-quote-table th { color: var(--muted); }
        .ns-quote-table td {
            padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px; color: #fff;
        }
        :root[data-theme="light"] .ns-quote-table td { color: var(--text); }
        .ns-quote-table td:first-child { text-align: left; }
        .ns-quote-table td:nth-child(2),
        .ns-quote-table td:nth-child(3),
        .ns-quote-table td:nth-child(4) { text-align: right; }
        .ns-quote-table tr:last-child td { border-bottom: none; }
        .ns-quote-table .ns-empty td {
            text-align: center; color: rgba(255,255,255,0.5); padding: 20px 0;
        }
        :root[data-theme="light"] .ns-quote-table .ns-empty td { color: var(--muted); }

        .ns-totals { display: flex; flex-direction: column; gap: 10px; margin-top: 18px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.08); }
        :root[data-theme="light"] .ns-totals { border-color: rgba(15,23,42,0.08); }
        .ns-totals-row {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 14px; color: rgba(255,255,255,0.75);
        }
        :root[data-theme="light"] .ns-totals-row { color: var(--text); }
        .ns-totals-row.total { font-size: 18px; font-weight: 800; color: #fff; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); }
        :root[data-theme="light"] .ns-totals-row.total { color: var(--text); border-color: rgba(15,23,42,0.1); }
        .ns-total-value { color: #007AFF; font-size: 22px; }

        .ns-notify {
            display: flex; align-items: center; gap: 10px; margin-top: 22px;
            font-size: 14px; color: rgba(255,255,255,0.75); cursor: pointer;
        }
        :root[data-theme="light"] .ns-notify { color: var(--text); }
        .ns-notify input { width: 18px; height: 18px; accent-color: #007AFF; cursor: pointer; }

        .ns-actions { display: flex; gap: 10px; margin-top: 24px; }
        .ns-actions .ns-btn { flex: 1; }
    </style>

    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $tech = $service->internalTechnician;
        $techName = trim(($tech->name ?? '') . ' ' . ($tech->last_name ?? ''));
        $maintenance = $service->maintenance;
        $refacciones = $maintenance?->refacciones ?? [];
        $subtotal = (float) ($maintenance?->subtotal ?? 0);
        $envio = (float) ($maintenance?->envio ?? 0);
        $descuento = (float) ($maintenance?->descuento ?? 0);
        $iva = (float) ($maintenance?->requiere_iva ? (($subtotal + $envio - $descuento) > 0 ? ($subtotal + $envio - $descuento) * 0.16 : 0) : 0);
        $total = (float) ($maintenance?->total ?? 0);
    @endphp

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                    <h2>Resumen de la orden</h2>
                    <p>Revisa la informacion registrada antes de salir</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Salir
                </a>
            </div>
        </div>

        <div class="ns-stepper">
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Cliente</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Tecnico</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Cotizacion</span>
            </div>
        </div>

        <div class="ns-grid">
            <div class="ns-card">
                <div class="ns-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Cliente
                </div>
                <div class="ns-specs">
                    <div class="ns-spec-row"><span>NOMBRE</span><span>{{ $customerName ?: 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>TELEFONO</span><span>{{ $service->customer->telefono ?: 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>CORREO</span><span>{{ $service->customer->gmail ?: 'N/A' }}</span></div>
                </div>
            </div>

            <div class="ns-card">
                <div class="ns-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Equipo
                </div>
                <div class="ns-specs">
                    <div class="ns-spec-row"><span>TIPO</span><span>{{ $service->serviceEquipment->type_text ?? 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>SUBTIPO</span><span>{{ $service->serviceEquipment->subtype_text ?? 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>MARCA / MODELO</span><span>{{ trim(($service->serviceEquipment->brand_text ?? '') . ' ' . ($service->serviceEquipment->model_text ?? '')) ?: 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>NO. DE SERIE</span><span>{{ $service->serviceEquipment->serial_number ?? 'N/A' }}</span></div>
                </div>
            </div>

            <div class="ns-card">
                <div class="ns-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Tecnico responsable
                </div>
                <div class="ns-specs">
                    <div class="ns-spec-row"><span>NOMBRE</span><span>{{ $techName ?: 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>CARGO</span><span>{{ $tech->cargo ?? $tech->position ?? 'N/A' }}</span></div>
                    <div class="ns-spec-row"><span>TELEFONO</span><span>{{ $tech->phone ?? $tech->telefono ?? 'N/A' }}</span></div>
                </div>
            </div>

            <div class="ns-card" style="grid-column: 1 / -1;">
                <div class="ns-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Cotizacion
                </div>

                <table class="ns-quote-table">
                    <thead>
                        <tr>
                            <th>Refaccion</th>
                            <th>Cantidad</th>
                            <th>P. Unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($refacciones as $r)
                            <tr>
                                <td>{{ $r['concepto'] ?? 'N/A' }}</td>
                                <td>{{ $r['cantidad'] ?? 0 }}</td>
                                <td>${{ number_format((float)($r['precio'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float)($r['total'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr class="ns-empty"><td colspan="4">No se agregaron refacciones.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="ns-totals">
                    <div class="ns-totals-row"><span>Subtotal</span><span>${{ number_format($subtotal, 2) }}</span></div>
                    <div class="ns-totals-row"><span>Costo de envio</span><span>${{ number_format($envio, 2) }}</span></div>
                    <div class="ns-totals-row"><span>Descuento</span><span>-${{ number_format($descuento, 2) }}</span></div>
                    <div class="ns-totals-row"><span>IVA (16%)</span><span>${{ number_format($iva, 2) }}</span></div>
                    <div class="ns-totals-row total"><span>Total</span><span class="ns-total-value">${{ number_format($total, 2) }}</span></div>
                </div>
            </div>
        </div>

        <div class="ns-notify">
            <input type="checkbox" id="notifyTechnician" checked>
            <label for="notifyTechnician">Notificar al tecnico cuando los administradores aprueben la solicitud</label>
        </div>

        <div class="ns-actions">
            <button type="button" class="ns-btn ns-btn--ghost" onclick="window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Imprimir
            </button>
            <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Finalizar
            </a>
        </div>
    </div>
@endsection
