@extends('structure.gestion_servicios.layout')

@section('title', 'Aprobaciones de servicios')

@section('service_content')
<div class="catalog-card service-section" style="padding:22px;">
    <div style="margin-bottom:12px;">
        <a href="{{ route('gestion.servicios.historial') }}" class="btn btn--ghost" style="font-size:13px; padding:6px 12px; text-decoration:none;">← Regresar</a>
    </div>
    <div class="catalog-header" style="margin-bottom:16px;">
        <div>
            <h2 style="margin:0; color:#fff;">Aprobaciones pendientes</h2>
            <p style="margin:6px 0 0; color:rgba(255,255,255,.55); font-size:13px;">Solicitudes de pasos que requieren validación de administrador.</p>
        </div>
        <span class="catalog-count">{{ $approvals->count() }} solicitud(es)</span>
    </div>

    <div class="service-table-wrap">
        <table class="service-table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Paso</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvals as $track)
                    <tr>
                        <td>{{ $track->service?->service_number ?? '—' }}</td>
                        <td>{{ $track->service?->customer?->nombre ?? '—' }}</td>
                        <td>{{ $track->serviceStep?->name ?? 'Paso' }}</td>
                        <td>
                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700;">
                                @if($track->status === 'pendiente')
                                    <span style="background:var(--accent-soft); color:var(--accent);">PENDIENTE</span>
                                @elseif($track->status === 'rechazado')
                                    <span style="background:var(--danger-soft); color:var(--danger);">RECHAZADO</span>
                                @else
                                    {{ strtoupper($track->status) }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:8px; justify-content:center;">
                                <a href="{{ route('gestion.servicios.historial.show', $track->service) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:12px;">Ver</a>

                                @if(in_array($track->status, ['pendiente', 'rechazado']))
                                    <form method="POST" action="{{ route('service-tracking.approve', $track) }}" style="display:inline;" onsubmit="return confirm('¿Aprobar este paso?');">
                                        @csrf
                                        <button type="submit" class="btn" style="padding:6px 12px; font-size:12px; background:var(--green); color:#fff; border:none; cursor:pointer;">Aprobar</button>
                                    </form>
                                @endif

                                @if($track->status !== 'rechazado')
                                    <form method="POST" action="{{ route('service-tracking.reject', $track) }}" style="display:inline;" onsubmit="return confirm('¿Rechazar este paso?');">
                                        @csrf
                                        <button type="submit" class="btn" style="padding:6px 12px; font-size:12px; background:var(--danger); color:#fff; border:none; cursor:pointer;">Rechazar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; color:rgba(255,255,255,.55);">No hay solicitudes de aprobación pendientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
