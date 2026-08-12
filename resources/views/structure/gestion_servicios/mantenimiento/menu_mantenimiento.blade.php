@extends('structure.gestion_servicios.layout')

@section('title', 'Órdenes de Servicio')

@section('service_content')
    <style>
        .service-page-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 24px;
        }
        .service-page-header .title-group h2 {
            margin: 0; font-size: 28px; color: #fff;
        }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p {
            margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 14px;
        }
        :root[data-theme="light"] .service-page-header .title-group p { color: var(--muted); }
    </style>

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Órdenes de Servicio aprobadas</h2>
                <p>Servicios que ya fueron aprobados y están listos para mantenimiento.</p>
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
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
                            $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
                            $equipment = $service->serviceEquipment;
                            $osNumber = preg_replace('/^NS-/', 'OS-', $service->service_number ?? '');
                        @endphp
                        <tr>
                            <td>{{ $osNumber ?: ('OS-' . $service->id) }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }}</td>
                            <td>
                                <span class="service-badge active">
                                    Aprobado
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="service-table-empty">No hay órdenes de servicio aprobadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
