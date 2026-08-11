@extends('structure.gestion_servicios.layout')

@section('title', 'Mantenimiento')

@section('service_content')
<div class="catalog-card service-section" style="padding:22px;">
    <div style="margin-bottom:12px;">
        <a href="{{ route('gestion.servicios.historial') }}" class="btn btn--ghost" style="font-size:13px; padding:6px 12px; text-decoration:none;">← Regresar</a>
    </div>
    <div class="catalog-header" style="margin-bottom:16px;">
        <div>
            <h2 style="margin:0; color:#fff;">Mantenimiento</h2>
            <p style="margin:6px 0 0; color:rgba(255,255,255,.55); font-size:13px;">Servicios aprobados por el administrador listos para continuar.</p>
        </div>
        <span class="catalog-count">{{ $approved->count() }} servicio(s)</span>
    </div>

    <div class="service-table-wrap">
        <table class="service-table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Paso</th>
                    <th>Estado</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approved as $track)
                    <tr>
                        <td>{{ $track->service?->service_number ?? '—' }}</td>
                        <td>{{ $track->service?->customer?->nombre ?? '—' }}</td>
                        <td>{{ $track->serviceStep?->name ?? 'Paso' }}</td>
                        <td>
                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; background:rgba(34,197,94,.18); color:#22C55E;">APROBADO</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-menu" style="position:relative; display:inline-block;">
                                <button type="button" onclick="toggleActionMenu(this)" style="background:transparent; border:none; color:var(--text, #e5e7eb); cursor:pointer; padding:6px; border-radius:8px; transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                </button>
                                <div class="action-dropdown" style="display:none; position:absolute; right:0; top:100%; margin-top:6px; background:var(--surface-2, #1f2937); border:1px solid var(--border, rgba(255,255,255,0.1)); border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.3); min-width:180px; z-index:50; overflow:hidden; text-align:left;">
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
                                    <form method="POST" action="{{ route('gestion.servicios.historial.entregar', $track->service) }}" style="margin:0;" onsubmit="return confirm('¿Marcar este servicio como entregado?');">
                                        @csrf
                                        <button type="submit" style="width:100%; display:flex; align-items:center; gap:10px; padding:10px 14px; background:transparent; border:none; color:var(--text, #e5e7eb); text-decoration:none; font-size:13.5px; cursor:pointer; transition:all 0.15s;" onmouseover="this.style.background='rgba(34,197,94,0.1)'" onmouseout="this.style.background='transparent'">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            Registrar como entregado
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; color:rgba(255,255,255,.55);">No hay servicios aprobados por el administrador.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
