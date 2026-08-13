<div class="equipment-table-panel" id="equipmentTablePanel">
    <div class="equipment-table-wrap">
        <table class="equipment-table">
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Estado</th>
                    <th>Serie</th>
                    <th>Fecha de adquisición</th>
                    <th>Registrado por</th>
                    <th style="width: 70px; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipos as $equipo)
                    @php
                        $tone = match($equipo->estado) {
                            'Mantenimiento' => 'blue',
                            'Inactivo', 'Dañado', 'Baja' => 'red',
                            default => 'green',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:800;">{{ $equipo->tipo_equipo }}</div>
                            @if($equipo->subtipo)
                                <div style="font-size:12px; color:#718096;">{{ $equipo->subtipo }}</div>
                            @endif
                            @if($equipo->marca || $equipo->modelo)
                                <div style="font-size:12px; color:#718096;">{{ $equipo->marca }} {{ $equipo->modelo }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="state-pill {{ $tone }}">{{ $equipo->estado ?: 'Activo' }}</span>
                        </td>
                        <td>{{ $equipo->no_serie ?: '—' }}</td>
                        <td>{{ $equipo->fecha_adquisicion ? $equipo->fecha_adquisicion->format('d/m/Y') : '—' }}</td>
                        <td>{{ $equipo->registradoPor?->name ?? '—' }}</td>
                        <td>
                            <div class="action-dots" data-action-dots>
                                <button type="button" class="dots-btn" aria-label="Acciones">&#8942;</button>
                                <div class="action-menu">
                                    <a href="{{ route('inventory.equipos.show', $equipo) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver detalle
                                    </a>
                                    <a href="{{ route('inventory.equipos.edit', $equipo) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </a>
                                    <a href="#" onclick="window.open('{{ route('inventory.equipos.show', $equipo) }}?etiqueta=1', '_blank', 'width=400,height=260'); return false;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 1 18 1 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        Imprimir etiqueta
                                    </a>
                                    <button type="button" class="qr-btn" onclick="openQrModal({{ $equipo->id }}, '{{ $equipo->no_serie ?: ($equipo->no_serie_base ?: $equipo->id) }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
                                        Generar QR
                                    </button>
                                    <form method="POST" action="{{ route('inventory.equipos.destroy', $equipo) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Eliminar" class="danger">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px; color:#718096;">
                            No hay equipos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($equipos->hasPages())
        <div class="equipment-foot">
            <span>Mostrando {{ $equipos->firstItem() }}–{{ $equipos->lastItem() }} de {{ $equipos->total() }}</span>
            {{ $equipos->links() }}
        </div>
    @endif
</div>
