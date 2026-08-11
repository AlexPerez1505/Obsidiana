@extends('layouts.dashboard')

@section('title', 'Historial de Servicios')

@section('content')
    <div class="card" style="margin-bottom:24px; background:linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h1 class="section-title" style="margin:0; font-size:34px; line-height:1.15; letter-spacing:-0.02em; font-weight:800;">Historial de Servicios</h1>
                <p class="muted" style="margin:14px 0 0; font-size:15.5px; line-height:1.6; max-width:680px;">Registro y seguimiento de órdenes de servicio.</p>
            </div>
            <a href="{{ route('service-tracking.approvals') }}" style="background:linear-gradient(135deg, #22C55E, #16A34A); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(34,197,94,0.35), 0 0 30px rgba(22,163,74,0.2); transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Aprobaciones
            </a>
            <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo Servicio
            </a>
        </div>
    </div>

    <div class="card">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid var(--border, rgba(255,255,255,0.1));">
                    <th style="padding:12px 10px;">Servicio</th>
                    <th style="padding:12px 10px;">Cliente</th>
                    <th style="padding:12px 10px;">Tipo de Técnico</th>
                    <th style="padding:12px 10px;">Estado</th>
                    <th style="padding:12px 10px;">Total</th>
                    <th style="padding:12px 10px;">Fecha</th>
                    <th style="padding:12px 10px; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cotizaciones as $cotizacion)
                    <tr style="border-bottom:1px solid var(--border, rgba(255,255,255,0.06));">
                        <td style="padding:12px 10px; font-weight:600; color:#00A8FF;">{{ $cotizacion->service_number ?? 'NS-' . $cotizacion->id }}</td>
                        <td style="padding:12px 10px;">{{ trim(($cotizacion->cliente_nombre ?? '') . ' ' . ($cotizacion->cliente_apellido ?? '')) ?: '—' }}</td>
                        <td style="padding:12px 10px;">
                            @if ($cotizacion->service_type === 'externo')
                                <span style="background:rgba(124,58,237,0.15); color:#A78BFA; padding:4px 10px; border-radius:8px; font-size:12.5px; font-weight:600;">Externo</span>
                            @elseif ($cotizacion->service_type === 'interno')
                                <span style="background:rgba(0,168,255,0.15); color:#38BDF8; padding:4px 10px; border-radius:8px; font-size:12.5px; font-weight:600;">Interno</span>
                            @else
                                <span style="background:rgba(107,114,128,0.15); color:#9CA3AF; padding:4px 10px; border-radius:8px; font-size:12.5px; font-weight:600;">Sin asignar</span>
                            @endif
                        </td>
                        <td style="padding:12px 10px;">
                            @php
                                $statusColors = [
                                    'registrado' => ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#3B82F6', 'text' => 'Registrado'],
                                    'en_progreso' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#F59E0B', 'text' => 'En Progreso'],
                                    'validado' => ['bg' => 'rgba(34,197,94,0.15)', 'color' => '#22C55E', 'text' => 'Validado'],
                                    'entregado' => ['bg' => 'rgba(34,197,94,0.15)', 'color' => '#22C55E', 'text' => 'Entregado'],
                                    'cancelado' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#EF4444', 'text' => 'Cancelado'],
                                ];
                                
                                // Contar pasos completados - Asegurar que se cargan correctamente
                                $completedCount = 0;
                                if ($cotizacion->service && $cotizacion->service->serviceTrackings) {
                                    $completedCount = $cotizacion->service->serviceTrackings
                                        ->where('status', 'completado')
                                        ->count();
                                }
                                
                                $status = $statusColors[$cotizacion->status] ?? ['bg' => 'rgba(107,114,128,0.15)', 'color' => '#9CA3AF', 'text' => ucfirst(str_replace('_', ' ', $cotizacion->status))];
                            @endphp
                            <span style="background:{{ $status['bg'] }}; color:{{ $status['color'] }}; padding:4px 10px; border-radius:8px; font-size:12.5px; font-weight:600;">
                                {{ $status['text'] }}
                                @if ($completedCount > 0)
                                    <span style="margin-left:4px;">({{ $completedCount }} paso{{ $completedCount > 1 ? 's' : '' }})</span>
                                @endif
                                @if (!$cotizacion->qr_token)
                                    <span style="margin-left:4px;">Sin QR</span>
                                @endif
                            </span>
                        </td>
                        <td style="padding:12px 10px;">—</td>
                        <td style="padding:12px 10px;" class="muted">{{ $cotizacion->created_at ? \Carbon\Carbon::parse($cotizacion->created_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td style="padding:12px 10px; text-align:center;">
                            <div class="action-menu" style="position:relative; display:inline-block;">
                                <button type="button" onclick="toggleActionMenu(this)" style="background:transparent; border:none; color:var(--text, #e5e7eb); cursor:pointer; padding:6px; border-radius:8px; transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                </button>
                                <div class="action-dropdown" style="display:none; position:absolute; right:0; top:100%; margin-top:6px; background:var(--surface-2, #1f2937); border:1px solid var(--border, rgba(255,255,255,0.1)); border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.3); min-width:150px; z-index:50; overflow:hidden;">
                                    <a href="{{ $cotizacion->service_type === 'externo' ? route('gestion.servicios.historial.externo.show', $cotizacion->id) : route('gestion.servicios.historial.show', $cotizacion->id) }}" style="display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--text, #e5e7eb); text-decoration:none; font-size:13.5px; transition:all 0.15s;" onmouseover="this.style.background='rgba(0,168,255,0.1)'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#00A8FF" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <a href="#" style="display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--text, #e5e7eb); text-decoration:none; font-size:13.5px; transition:all 0.15s;" onmouseover="this.style.background='rgba(0,168,255,0.1)'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </a>
                                    <form method="POST" action="#" style="margin:0;" onsubmit="return false;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="width:100%; display:flex; align-items:center; gap:10px; padding:10px 14px; background:transparent; border:none; color:var(--text, #e5e7eb); text-decoration:none; font-size:13.5px; cursor:pointer; transition:all 0.15s;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:24px 10px; text-align:center;" class="muted">No hay servicios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function toggleActionMenu(btn) {
            const dropdown = btn.nextElementSibling;
            const isOpen = dropdown.style.display === 'block';
            document.querySelectorAll('.action-dropdown').forEach(d => d.style.display = 'none');
            dropdown.style.display = isOpen ? 'none' : 'block';
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.action-menu')) {
                document.querySelectorAll('.action-dropdown').forEach(d => d.style.display = 'none');
            }
        });
    </script>
@endsection
