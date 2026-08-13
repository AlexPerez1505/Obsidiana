@extends('structure.gestion_servicios.layout')

@section('title', 'Validación de Órdenes de Servicio')

@section('service_content')
    <style>
        .service-page-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 24px;
        }
        .service-page-header .title-group h2 { margin: 0; font-size: 28px; color: #fff; }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 14px; }
        :root[data-theme="light"] .service-page-header .title-group p { color: var(--muted); }
        .service-action-btn {
            background: rgba(34,197,94,0.14); border: 1px solid rgba(34,197,94,0.4);
            color: #22C55E; border-radius: 8px; padding: 7px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .service-action-btn svg { width: 18px; height: 18px; }
        .service-action-btn.approve { background: rgba(34,197,94,0.14); color: #22C55E; }
        .service-action-btn.view { background: rgba(59,130,246,0.14); border-color: rgba(59,130,246,0.4); color: #3b82f6; }
    </style>

    @if (session('success'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #22C55E;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #ff4a4a;">{{ session('error') }}</div>
    @endif

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Validación de OS</h2>
                <p>Órdenes de servicio generadas pendientes de validación por administrador.</p>
            </div>
        </div>

        <div class="service-table-wrap">
            <table class="service-table">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>CLIENTE</th>
                        <th>TÉCNICO</th>
                        <th>EQUIPO</th>
                        <th>TOTAL</th>
                        <th>A PAGAR</th>
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
                            $equipment = $service->serviceEquipment;
                            $maintenance = $service->maintenance;
                            $osNumber = str_replace('NS-', 'OS-', $service->service_number ?? ('OS-' . $service->id));
                            $partidas = $maintenance?->partidas_remision ?? [];
                            $subtotal = collect($partidas)->sum(fn($p) => (float)($p['cantidad'] ?? 0) * (float)($p['precio_unitario'] ?? 0)) + (float)($maintenance?->envio ?? 0);
                            $iva = ($maintenance?->requiere_iva ?? false) ? $subtotal * 0.16 : 0;
                            $total = $subtotal + $iva;
                            $anticipo = (float)($maintenance?->anticipo ?? 0);
                            $pagar = $total - $anticipo;
                        @endphp
                        <tr>
                            <td>{{ $osNumber }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }}</td>
                            <td>${{ number_format($total, 2) }}</td>
                            <td>${{ number_format($pagar, 2) }}</td>
                            <td>
                                @if ($tracking->status === 'completado')
                                    <span class="service-badge active">Aprobada</span>
                                @else
                                    <span class="service-badge upcoming">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('gestion.servicios.ruta', $service) }}" class="service-action-btn" title="Ver ruta de trabajo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                </a>

                                <a href="{{ route('gestion.servicios.os.form', $service) }}" target="_blank" class="service-action-btn view" title="Ver OS">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                </a>

                                @if ($tracking->status === 'pendiente')
                                    <form action="{{ route('gestion.servicios.tracking.aprobar', $tracking) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="service-action-btn approve" title="Validar OS">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="service-table-empty">No hay órdenes de servicio pendientes de validación.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
