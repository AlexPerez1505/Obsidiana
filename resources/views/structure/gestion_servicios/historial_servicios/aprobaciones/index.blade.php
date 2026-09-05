@extends('structure.commercial_management.erp')

@section('title', 'Aprobaciones')

@section('erp_content')
    @php
        $pendientes = $services->count();
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Aprobaciones</h1>
            <span class="erp-count">{{ $pendientes }} {{ $pendientes === 1 ? 'pendiente' : 'pendientes' }}</span>
        </div>
        <a href="{{ route('gestion.servicios.historial') }}" class="erp-btn ghost">Volver al historial</a>
    </div>

    <div class="erp-card">
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Paso actual</th>
                        <th>Fecha</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td class="erp-strong">{{ $service->service_number }}</td>
                            <td>{{ $service->customer?->nombre }} {{ $service->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $service->service_type }}</td>
                            <td>{{ $service->currentStep?->name ?? '—' }}</td>
                            <td style="color:var(--muted);">{{ $service->created_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;">
                                <a href="{{ route('gestion.servicios.historial.aprobaciones.show', $service) }}" class="tbl-link">Ver</a>
                                <form action="{{ route('gestion.servicios.historial.approve', $service) }}" method="POST" style="display:inline; margin-left:10px;">
                                    @csrf
                                    <button type="submit" class="tbl-link" style="border:none; background:none; color:var(--green); cursor:pointer;">Aprobar</button>
                                </form>
                                <form action="{{ route('gestion.servicios.historial.deny', $service) }}" method="POST" style="display:inline; margin-left:10px;" onsubmit="return confirm('¿Denegar esta orden?')">
                                    @csrf
                                    <button type="submit" class="tbl-link" style="border:none; background:none; color:var(--danger); cursor:pointer;">Denegar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="erp-empty">No hay órdenes pendientes de aprobación.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
