        <!-- Paso 3: Tecnico interno -->
        <div class="step-panel" data-step="3" id="step-panel-interno">
            @php
                $technicians = $internalTechnicians->filter(function ($t) {
                    $name = strtolower($t->name);
                    return str_contains($name, 'joel') || str_contains($name, 'icelda');
                })->values();

                $techniciansData = $technicians->map(function ($t) {
                    $active = \App\Models\Service::where('internal_technician_id', $t->id)
                        ->where('status', 'en_progreso')
                        ->get()
                        ->map(fn($s) => [$s->service_number, trim(($s->customer?->nombre ?? '') . ' ' . ($s->customer?->apellido ?? ''))]);
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                        'email' => $t->email,
                        'status_label' => $t->statusLabel(),
                        'initials' => collect(explode(' ', $t->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join(''),
                        'active_services' => $active,
                    ];
                })->values();
            @endphp

            <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:22px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="client-avatar" id="tech-client-avatar">JD</div>
                    <div>
                        <div class="client-name" id="tech-client-name">DR. Jhone Doe</div>
                        <div class="client-info" id="tech-client-info">Cliente seleccionado</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; color:var(--muted); font-size:14px;">
                    Registrado por: <strong style="color:var(--text);">{{ auth()->user()?->name ?? 'Invitado' }}</strong>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start;">
                <div>
                    <h3 style="font-size:17px; margin:0 0 4px;">Asignar tecnico responsable</h3>
                    <p class="muted" style="font-size:13px; margin:0 0 14px;">Selecciona alguno de los tecnicos del sistema</p>
                    <input type="hidden" name="internal_technician_id" id="internal_technician_id" value="{{ $technicians->first()?->id }}">

                    @if($technicians->count())
                        <div class="technician-list" id="int-tech-list">
                            @foreach($technicians as $index => $tech)
                                @php
                                    $initials = collect(explode(' ', $tech->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                                    $statusClass = match($tech->status) {
                                        'approved' => 'ok',
                                        'banned' => 'danger',
                                        default => 'warn',
                                    };
                                    $statusLabel = $tech->statusLabel();
                                @endphp
                                <div class="tech-row {{ $index === 0 ? 'active' : '' }}" data-tech="{{ $index }}">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="tech-avatar" style="background:var(--primary);">{{ $initials }}</div>
                                        <div>
                                            <div style="font-weight:700;">{{ $tech->name }}</div>
                                            <div class="muted" style="font-size:13px;">{{ $tech->email }}</div>
                                        </div>
                                    </div>
                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="card" style="padding:18px; text-align:center;">
                            <p class="muted" style="margin:0;">No hay usuarios registrados disponibles como tecnicos.</p>
                        </div>
                    @endif
                </div>

                <div class="card" style="padding:18px;">
                    <h3 style="display:flex; align-items:center; gap:10px; font-size:17px; margin:0 0 14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Servicios activos del tecnico
                    </h3>
                    <p class="muted" style="font-size:13px; margin:0 0 12px;">Mostrando servicios activos de <strong id="active-int-tech-name">{{ $technicians->first()?->name ?? 'Selecciona un tecnico' }}</strong></p>

                    <div id="int-active-services">
                        @if($technicians->isNotEmpty())
                            @forelse($techniciansData->first()['active_services'] ?? [] as $svc)
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                                    <span class="badge ok" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                                    <span>{{ $svc[0] }}</span>
                                    <span class="muted">{{ $svc[1] }}</span>
                                </div>
                            @empty
                                <p class="muted" style="font-size:13px;">No hay servicios activos asignados.</p>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>


        </div>

@push('scripts')
<script>
    const intTechnicians = @json($techniciansData);
    window.intTechnicians = intTechnicians;
    let selectedIntTech = 0;
    function selectIntTech(index) {
        selectedIntTech = index;
        document.querySelectorAll('#int-tech-list .tech-row').forEach((row, i) => {
            row.classList.toggle('active', i === index);
        });
        const tech = intTechnicians[index];
        if (!tech) return;
        document.getElementById('active-int-tech-name').textContent = tech.name;
        const list = document.getElementById('int-active-services');
        list.innerHTML = (tech.active_services || []).map(s => `
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <span class="badge ok" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                <span>${s[0]}</span>
                <span class="muted">${s[1]}</span>
            </div>`).join('');
        if (!tech.active_services || !tech.active_services.length) {
            list.innerHTML = '<p class="muted" style="font-size:13px;">No hay servicios activos asignados.</p>';
        }
        const technicianInput = document.getElementById('internal_technician_id');
        if (technicianInput) technicianInput.value = tech.id;
    }
    document.querySelectorAll('#int-tech-list .tech-row').forEach(row => {
        row.addEventListener('click', () => selectIntTech(parseInt(row.dataset.tech)));
    });
</script>
@endpush
