        <!-- Paso 2: Selección de Equipo -->
        <div class="step-panel" data-step="2">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:22px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="client-avatar" id="sel-client-avatar">JD</div>
                    <div>
                        <div class="client-name" id="sel-client-name">DR. Jhone Doe</div>
                        <div class="client-info" id="sel-client-info">Cliente seleccionado</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; color:var(--muted); font-size:14px;">
                    Registrado por: <strong style="color:var(--text);">{{ auth()->user()?->name ?? 'Invitado' }}</strong>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>

            <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Selecciona el equipo
            </h3>
            <p class="muted" style="margin:0 0 18px; font-size:13px;">Elige el equipo registrado que recibirá el servicio.</p>

            <div class="equipment-filter">
                <button type="button" class="filter-btn" data-filter="Interno">Internos</button>
                <button type="button" class="filter-btn" data-filter="Externo">Externos</button>
                <button type="button" class="filter-btn" data-filter="todos">Todos</button>
            </div>

            <div class="equipment-grid" id="equipment-grid">
                @forelse ($equipos as $equipo)
                    <div class="equipment-card"
                         data-id="{{ $equipo->id }}"
                         data-externo-interno="{{ $equipo->externo_interno }}"
                         data-tipo="{{ $equipo->tipo }}"
                         data-subtipo="{{ $equipo->subtipo }}"
                         data-marca="{{ $equipo->marca }}"
                         data-modelo="{{ $equipo->modelo }}"
                         data-serie="{{ $equipo->serie }}"
                         data-descripcion="{{ $equipo->descripcion }}"
                         data-observaciones="{{ $equipo->observaciones }}">
                        <div class="equip-header">
                            @if ($equipo->imagen)
                                <img src="{{ asset('storage/' . $equipo->imagen) }}" alt="" class="equip-thumb">
                            @else
                                <div class="equip-thumb-placeholder">{{ strtoupper(substr($equipo->tipo, 0, 2)) }}</div>
                            @endif
                            <div>
                                <div class="equip-title">{{ $equipo->tipo }}{{ $equipo->marca ? ' - ' . $equipo->marca : '' }}</div>
                                <div class="equip-meta">
                                    {{ $equipo->modelo ? 'Modelo: ' . $equipo->modelo . ' | ' : '' }}
                                    {{ $equipo->serie ? 'Serie: ' . $equipo->serie : '' }}
                                </div>
                            </div>
                        </div>
                        <div class="equip-meta" style="min-height:2.8em;">{{ Str::limit($equipo->descripcion, 80) }}</div>
                        <span class="equip-badge {{ $equipo->externo_interno === 'Externo' ? 'externo' : 'interno' }}">
                            {{ $equipo->externo_interno ?? 'Sin clasificar' }}
                        </span>
                    </div>
                @empty
                    <p class="muted" style="grid-column:1/-1; text-align:center;">No hay equipos registrados. Registra uno en <em>Gestión de Inventario &gt; Nuevo Equipo</em>.</p>
                @endforelse
            </div>
            <p id="no-equipment-msg" class="muted hidden" style="text-align:center; margin-top:18px;">No hay equipos registrados para esta categoría.</p>

            <input type="hidden" id="equipo_seleccionado_id">
            <input type="hidden" name="tipo_equipo" id="tipo_equipo" value="{{ old('tipo_equipo') }}">
            <input type="hidden" name="subtipo" id="subtipo" value="{{ old('subtipo') }}">
            <input type="hidden" name="marca" id="marca" value="{{ old('marca') }}">
            <input type="hidden" name="modelo" id="modelo" value="{{ old('modelo') }}">
            <input type="hidden" name="serie" id="serie" value="{{ old('serie') }}">
            <input type="hidden" name="descripcion_equipo" id="descripcion_equipo" value="{{ old('descripcion_equipo') }}">
            <input type="hidden" name="observaciones" id="observaciones" value="{{ old('observaciones') }}">
        </div>

@push('scripts')
<script>
    (function () {
        function selectEquipment(card) {
            document.querySelectorAll('.equipment-card').forEach(function (c) { c.classList.remove('selected'); });
            card.classList.add('selected');

            document.getElementById('equipo_seleccionado_id').value = card.dataset.id || '';
            document.getElementById('tipo_equipo').value = card.dataset.tipo || '';
            document.getElementById('subtipo').value = card.dataset.subtipo || '';
            document.getElementById('marca').value = card.dataset.marca || '';
            document.getElementById('modelo').value = card.dataset.modelo || '';
            document.getElementById('serie').value = card.dataset.serie || '';
            document.getElementById('descripcion_equipo').value = card.dataset.descripcion || '';
            document.getElementById('observaciones').value = card.dataset.observaciones || '';

            document.getElementById('tipo_equipo').dispatchEvent(new Event('input', { bubbles: true }));
        }

        function filterCards(filter) {
            var visibleCount = 0;
            document.querySelectorAll('.equipment-card').forEach(function (card) {
                var value = card.dataset.externoInterno || '';
                if (!filter || filter === 'todos' || value === filter) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            var msg = document.getElementById('no-equipment-msg');
            if (msg) msg.classList.toggle('hidden', visibleCount > 0);
        }

        function setActiveFilter(filter) {
            document.querySelectorAll('.equipment-filter .filter-btn').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.filter === filter);
            });
        }

        function restoreSelection() {
            var id = document.getElementById('equipo_seleccionado_id').value;
            if (!id) return;
            var card = document.querySelector('.equipment-card[data-id="' + id + '"]');
            if (card) {
                document.querySelectorAll('.equipment-card').forEach(function (c) { c.classList.remove('selected'); });
                card.classList.add('selected');
            }
        }

        window.filterEquipos = function (defaultFilter) {
            if (!defaultFilter) {
                if (parseInt(document.getElementById('mantenimiento_interno').value)) defaultFilter = 'Interno';
                else if (parseInt(document.getElementById('mantenimiento_externo').value)) defaultFilter = 'Externo';
                else defaultFilter = 'todos';
            }
            setActiveFilter(defaultFilter);
            filterCards(defaultFilter);
            restoreSelection();
        };

        document.querySelectorAll('.equipment-filter .filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setActiveFilter(btn.dataset.filter);
                filterCards(btn.dataset.filter);
            });
        });

        document.querySelectorAll('.equipment-card').forEach(function (card) {
            card.addEventListener('click', function () { selectEquipment(card); });
        });
    })();
</script>
@endpush
