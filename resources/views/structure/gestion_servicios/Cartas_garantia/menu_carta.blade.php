@extends('structure.gestion_servicios.layout')

@section('title', 'Cartas de garantía')

@section('service_content')
<div class="catalog-card service-section" style="padding:22px;">
    <div style="margin-bottom:12px;">
        <a href="{{ url('gestion-servicios') }}" class="btn btn--ghost" style="font-size:13px; padding:6px 12px; text-decoration:none;">← Regresar</a>
    </div>

    <div class="catalog-header" style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; margin-bottom:16px;">
        <div>
            <h2 style="margin:0; color:#fff;">Cartas de garantía</h2>
            <p style="margin:6px 0 0; color:rgba(255,255,255,.55); font-size:13px;">Listado de cartas de garantía registradas.</p>
        </div>
        <a href="{{ route('cartas.garantia.create') }}" class="btn" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35); transition:all 0.2s ease;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva carta
        </a>
    </div>

    <div class="service-table-wrap">
        <table class="service-table" style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid var(--border, rgba(255,255,255,0.1));">
                    <th style="padding:12px 10px;">ID</th>
                    <th style="padding:12px 10px;">Tipo de equipo</th>
                    <th style="padding:12px 10px;">Subtipo</th>
                    <th style="padding:12px 10px;">Nombre</th>
                    <th style="padding:12px 10px;">Archivo</th>
                    <th style="padding:12px 10px;">Fecha</th>
                    <th style="padding:12px 10px; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cartas ?? [] as $carta)
                    <tr style="border-bottom:1px solid var(--border, rgba(255,255,255,0.06));">
                        <td style="padding:12px 10px; font-weight:600;">#{{ $carta->id }}</td>
                        <td style="padding:12px 10px;">{{ $carta->tipoEquipo?->tipo_equipo ?? $carta->id_tipo_equipo }}</td>
                        <td style="padding:12px 10px;">{{ $carta->subtipo?->subtipo ?? $carta->id_subtipo }}</td>
                        <td style="padding:12px 10px;">{{ $carta->nombre }}</td>
                        <td style="padding:12px 10px;">
                            @if($carta->archivo_carta)
                                <a href="{{ asset('storage/' . $carta->archivo_carta) }}" target="_blank" style="color:#00A8FF; text-decoration:none;">Ver archivo</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 10px;" class="muted">{{ $carta->created_at ? \Carbon\Carbon::parse($carta->created_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td style="padding:12px 10px; text-align:center;">
                            <div class="action-menu" style="position:relative; display:inline-block;">
                                <button type="button" onclick="toggleActionMenu(this)" style="background:transparent; border:none; color:var(--text, #e5e7eb); cursor:pointer; padding:6px; border-radius:8px; transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                </button>
                                <div class="action-dropdown" style="display:none; position:absolute; right:0; top:100%; margin-top:6px; background:var(--surface-2, #1f2937); border:1px solid var(--border, rgba(255,255,255,0.1)); border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.3); min-width:150px; z-index:50; overflow:hidden; text-align:left;">
                                    <a href="#" style="display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--text, #e5e7eb); text-decoration:none; font-size:13.5px; transition:all 0.15s;" onmouseover="this.style.background='rgba(0,168,255,0.1)'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#00A8FF" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <a href="#" style="display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--text, #e5e7eb); text-decoration:none; font-size:13.5px; transition:all 0.15s;" onmouseover="this.style.background='rgba(245,158,11,0.1)'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </a>
                                    <form method="POST" action="#" style="margin:0;" onsubmit="return confirm('¿Eliminar esta carta de garantía?');">
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
                        <td colspan="7" style="padding:24px 10px; text-align:center; color:rgba(255,255,255,.55);">No hay cartas de garantía registradas.</td>
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
