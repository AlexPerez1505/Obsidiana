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
                        <th>Fecha</th>
                        <th>Decisión del cliente</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td class="erp-strong">{{ $service->service_number }}</td>
                            <td>{{ $service->customer?->nombre }} {{ $service->customer?->apellido }}</td>
                            <td style="text-transform:capitalize;">{{ $service->service_type }}</td>
                            <td style="color:var(--muted);">{{ $service->created_at?->format('d/m/Y') }}</td>
                            <td>
                                @if ($service->customer_decision)
                                    <span class="erp-count" style="color:{{ $service->customer_decision === 'aprobado' ? 'var(--green)' : 'var(--danger)' }}; background:transparent; border:none; padding:0; font-weight:700; text-transform:capitalize;">{{ $service->customer_decision }}</span>
                                @else
                                    <span style="color:var(--muted); font-size:13px;">Pendiente</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a href="{{ route('gestion.servicios.historial.aprobaciones.show', $service) }}" class="tbl-link" style="display:inline-flex; align-items:center; gap:4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Ver
                                </a>
                                <form action="{{ route('gestion.servicios.historial.approve', $service) }}" method="POST" style="display:inline; margin-left:10px;">
                                    @csrf
                                    <button type="submit" class="tbl-link" style="display:inline-flex; align-items:center; gap:4px; border:none; background:none; color:var(--green); cursor:pointer; padding:0;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><polyline points="20 6 9 17 4 12"/></svg>
                                        Aprobar
                                    </button>
                                </form>
<<<<<<< HEAD
                                <form action="{{ route('gestion.servicios.historial.deny', $service) }}" method="POST" style="display:inline; margin-left:10px;" onsubmit="return confirm('¿Cancelar esta orden?')">
=======
                                <form action="{{ route('gestion.servicios.historial.deny', $service) }}" method="POST" style="display:inline; margin-left:10px;" data-confirm="¿Denegar esta orden?">
>>>>>>> 748c4e1ad51103d8ab70a724731e58ea1f9a4912
                                    @csrf
                                    <button type="submit" class="tbl-link" style="display:inline-flex; align-items:center; gap:4px; border:none; background:none; color:var(--danger); cursor:pointer; padding:0;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Cancelar
                                    </button>
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
