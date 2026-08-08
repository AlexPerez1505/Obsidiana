@extends('layouts.dashboard')

@section('title', 'Historial de Servicios')

@section('content')
    <div class="card" style="margin-bottom:24px; background:linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h1 class="section-title" style="margin:0; font-size:34px; line-height:1.15; letter-spacing:-0.02em; font-weight:800;">Historial de Servicios</h1>
                <p class="muted" style="margin:14px 0 0; font-size:15.5px; line-height:1.6; max-width:680px;">Registro y seguimiento de órdenes de servicio.</p>
            </div>
            <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nueva Orden
            </a>
        </div>
    </div>

    <div class="card">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid var(--border, rgba(255,255,255,0.1));">
                    <th style="padding:12px 10px;">Orden</th>
                    <th style="padding:12px 10px;">Cliente</th>
                    <th style="padding:12px 10px;">Tipo de Técnico</th>
                    <th style="padding:12px 10px;">Técnico</th>
                    <th style="padding:12px 10px;">Estado</th>
                    <th style="padding:12px 10px;">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr style="border-bottom:1px solid var(--border, rgba(255,255,255,0.06));">
                        <td style="padding:12px 10px;">
                            <a href="{{ route('gestion.servicios.historial.show', $service) }}" style="color:#00A8FF; text-decoration:none; font-weight:600;">
                                {{ $service->service_number }}
                            </a>
                        </td>
                        <td style="padding:12px 10px;">{{ trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? '')) ?: '—' }}</td>
                        <td style="padding:12px 10px;">
                            @if ($service->service_type === 'externo')
                                <span style="background:rgba(124,58,237,0.15); color:#A78BFA; padding:4px 10px; border-radius:8px; font-size:12.5px; font-weight:600;">Externo</span>
                            @else
                                <span style="background:rgba(0,168,255,0.15); color:#38BDF8; padding:4px 10px; border-radius:8px; font-size:12.5px; font-weight:600;">Interno</span>
                            @endif
                        </td>
                        <td style="padding:12px 10px;">
                            {{ $service->service_type === 'externo'
                                ? ($service->externalTechnician->name ?? '—')
                                : ($service->internalTechnician->name ?? '—') }}
                        </td>
                        <td style="padding:12px 10px; text-transform:capitalize;">{{ $service->status }}</td>
                        <td style="padding:12px 10px;" class="muted">{{ $service->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:24px 10px; text-align:center;" class="muted">No hay servicios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
