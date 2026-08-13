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
            margin-bottom: 24px;
        }
        .service-page-header .title-group h2 {
            margin: 0;
            font-size: 28px;
            color: #fff;
        }
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
            background: #22C55E;
            color: #fff;
            box-shadow: 0 0 16px rgba(34,197,94,0.35);
        }
        .service-btn--green:hover { background: #16A34A; box-shadow: 0 0 22px rgba(34,197,94,0.5); }
        .service-btn--blue {
            background: linear-gradient(135deg, #007AFF, #6366F1);
            color: #fff;
            box-shadow: 0 0 16px rgba(0,122,255,0.35);
        }
        .service-btn--blue:hover { background: linear-gradient(135deg, #005FCC, #4F46E5); box-shadow: 0 0 22px rgba(0,122,255,0.5); }
        .service-btn svg { width: 18px; height: 18px; }
        .service-table-empty {
            padding: 36px 14px;
            text-align: center;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .service-table-empty { color: var(--muted); }
    </style>

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Historial de Servicios</h2>
                <p>Registro y seguimiento de Nuevo servicio.</p>
            </div>
            <div class="service-page-actions">
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
                        <tr>
                            <td>{{ $service->service_number ?? 'N/A' }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ ucfirst($service->status) }}</td>
                            <td>N/A</td>
                            <td>{{ $service->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ $service->service_type === 'interno' ? route('gestion.servicios.nuevo.interno.resumen', $service) : route('gestion.servicios.nuevo.externo.resumen', $service) }}" class="service-action-btn" title="Ver resumen">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ route('gestion.servicios.ruta', $service) }}" class="service-action-btn" title="Ver ruta de trabajo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                </a>
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
@endsection
