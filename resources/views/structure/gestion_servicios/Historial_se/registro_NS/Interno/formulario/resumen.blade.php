@extends('structure.gestion_servicios.layout')

@section('title', 'Resumen de la orden')

@section('service_content')
    @include('structure.gestion_servicios.Historial_se.registro_ns.Interno.interno_estilos_base')
    <style>
        .ns-page { max-width: 100%; padding: 0 24px; }
        .ns-icon { width: 40px; height: 40px; }
        .ns-icon svg { width: 20px; height: 20px; }
        .ns-stepper { justify-content: space-between; gap: 8px; }
        .ns-step { flex: 0 0 auto; }
        .ns-step-number { flex-shrink: 0; }
        .ns-step-line { height: 2px; min-width: 12px; max-width: 60px; }
        .ns-notify { margin-top: 22px; }
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
            <div>
                <a href="{{ route('gestion.servicios.historial') }}" class="resumen-btn resumen-btn--primary">
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

        <div class="resumen-grid">
            <div class="resumen-card catalog-card catalog-card">
                <div class="resumen-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Cliente
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">NOMBRE</span>
                    <span class="resumen-value">{{ $customerName ?: 'N/A' }}</span>
                </div>
            </div>

            <div class="resumen-card catalog-card">
                <div class="resumen-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Equipo
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">TIPO</span>
                    <span class="resumen-value">{{ $service->serviceEquipment->type_text ?? 'N/A' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">SUBTIPO</span>
                    <span class="resumen-value">{{ $service->serviceEquipment->subtype_text ?? 'N/A' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">MARCA / MODELO</span>
                    <span class="resumen-value">{{ trim(($service->serviceEquipment->brand_text ?? '') . ' ' . ($service->serviceEquipment->model_text ?? '')) ?: 'N/A' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">NO. DE SERIE</span>
                    <span class="resumen-value">{{ $service->serviceEquipment->serial_number ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="resumen-card catalog-card">
                <div class="resumen-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Tecnico responsable
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">NOMBRE</span>
                    <span class="resumen-value">{{ $techName ?: 'N/A' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">CARGO</span>
                    <span class="resumen-value">{{ $tech->cargo ?? $tech->position ?? 'N/A' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">TELEFONO</span>
                    <span class="resumen-value">{{ $tech->phone ?? $tech->telefono ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="resumen-card catalog-card" style="grid-column: 1 / -1;">
                <div class="resumen-title resumen-title--between">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Cotizacion
                    </span>
                    <span class="resumen-count">{{ count($refacciones) }} refacciones</span>
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

                <div class="resumen-detail">
                    <span class="resumen-label">Subtotal</span>
                    <span class="resumen-value">${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">Costo de envio</span>
                    <span class="resumen-value">${{ number_format($envio, 2) }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">Descuento</span>
                    <span class="resumen-value">-${{ number_format($descuento, 2) }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">IVA (16%)</span>
                    <span class="resumen-value">${{ number_format($iva, 2) }}</span>
                </div>
                <div class="resumen-detail" style="border-bottom: none; font-weight: 800;">
                    <span class="resumen-label" style="color: var(--text); font-size: 15px;">Total</span>
                    <span class="resumen-value" style="color: var(--primary); font-size: 18px;">${{ number_format($total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="ns-notify">
            <input type="checkbox" id="notifyTechnician" checked>
            <label for="notifyTechnician">Notificar al tecnico cuando los administradores aprueben la solicitud</label>
        </div>

        <div class="resumen-actions">
            <a href="{{ route('gestion.servicios.historial') }}" class="resumen-btn resumen-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Finalizar
            </a>
        </div>
    </div>
@endsection
