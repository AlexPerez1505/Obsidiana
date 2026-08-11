        <!-- Paso 2: Tecnico externo -->
        <div class="step-panel" data-step="2" id="step-panel-externo">
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
                    <h3 style="font-size:17px; margin:0 0 4px;">Asignar tecnico externo</h3>
                    <p class="muted" style="font-size:13px; margin:0 0 14px;">Selecciona un tecnico externo disponible o registra uno nuevo</p>
                    <input type="hidden" name="external_technician_id" id="external_technician_id" value="{{ $externalTechnicians->first()?->id }}">

                    @if($externalTechnicians->count())
                        <div class="technician-list" id="ext-tech-list">
                            @foreach($externalTechnicians as $index => $tech)
                                <div class="tech-row {{ $index === 0 ? 'active' : '' }}" data-tech="{{ $index }}">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="tech-avatar" style="background:var(--accent); overflow:hidden; position:relative;">
                                            @if($tech->photo)
                                                <img src="{{ Storage::url($tech->photo) }}" alt="{{ $tech->name }}" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <span class="tech-initials" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center;">{{ collect(explode(' ', $tech->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') }}</span>
                                            @else
                                                {{ collect(explode(' ', $tech->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') }}
                                            @endif
                                        </div>
                                        <div>
                                            <div style="font-weight:700;">{{ $tech->name }}</div>
                                            <div class="muted" style="font-size:13px;">
                                                {{ $tech->specialty ? 'Especialidad: '.$tech->specialty : 'Sin especialidad' }}
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge warn">Externo</span>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn--ghost" id="btn-show-add-ext" style="margin-top:12px; width:100%;">
                            + Agregar otro tecnico externo
                        </button>
                    @else
                        <div class="card" style="padding:18px; text-align:center;" id="ext-empty">
                            <p class="muted" style="margin:0 0 14px;">No hay tecnicos externos registrados.</p>
                            <button type="button" class="btn" id="btn-show-add-ext">Agregar tecnico externo</button>
                        </div>
                    @endif
                </div>

                <div class="card" style="padding:18px;" id="ext-tech-detail">
                    <h3 style="display:flex; align-items:center; gap:10px; font-size:17px; margin:0 0 14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--accent)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Informacion del tecnico externo
                    </h3>
                    <p class="muted" style="font-size:13px; margin:0 0 12px;">Detalles de <strong id="active-ext-tech-name">{{ $externalTechnicians->first()?->name ?? 'Nuevo tecnico' }}</strong></p>

                    @if($externalTechnicians->count())
                        <div id="ext-tech-info">
                            <div class="form-group" style="margin-bottom:10px;">
                                <label>Telefono</label>
                                <input type="text" id="ext-tech-phone" value="{{ $externalTechnicians->first()?->phone ?? '' }}" readonly>
                            </div>
                            <div class="form-group" style="margin-bottom:10px;">
                                <label>Especialidad</label>
                                <input type="text" id="ext-tech-specialty" value="{{ $externalTechnicians->first()?->specialty ?? '' }}" readonly>
                            </div>
                            <div class="form-group" style="margin-bottom:10px;">
                                <label>Email</label>
                                <input type="text" id="ext-tech-email" value="{{ $externalTechnicians->first()?->email ?? '' }}" readonly>
                            </div>
                            <div class="form-group" style="margin-bottom:10px;">
                                <label>Ubicacion</label>
                                <input type="text" id="ext-tech-location-text" value="{{ $externalTechnicians->first()?->location ?? '' }}" readonly>
                                <a href="#" id="ext-tech-location" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:6px; color:var(--primary); font-size:14px; text-decoration:none; margin-top:6px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span>Ver en Google Maps</span>
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

@push('scripts')
<script>
    window.extTechnicians = @json($externalTechnicians->values());
    let selectedExtTech = 0;
    const extInfo = document.getElementById('ext-tech-info');
    const extTechList = document.getElementById('ext-tech-list');

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function createExtTechRowHtml(tech, index) {
        const initials = tech.name
            ? tech.name.split(' ').filter(w => w).map(w => w.substring(0, 1).toUpperCase()).slice(0, 2).join('')
            : '?';
        const photoUrl = tech.photo ? (window.location.origin + '/storage/' + tech.photo) : '';
        const avatar = photoUrl
            ? `<img src="${escapeHtml(photoUrl)}" alt="${escapeHtml(tech.name || '')}" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" onload="this.style.display='block'; this.nextElementSibling.style.display='none';"><span class="tech-initials" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center; color:#fff;">${escapeHtml(initials)}</span>`
            : escapeHtml(initials);
        return `
            <div class="tech-row" data-tech="${index}">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tech-avatar" style="background:var(--accent); overflow:hidden; position:relative;">${avatar}</div>
                    <div>
                        <div style="font-weight:700;">${escapeHtml(tech.name)}</div>
                        <div class="muted" style="font-size:13px;">
                            ${tech.specialty ? 'Especialidad: ' + escapeHtml(tech.specialty) : 'Sin especialidad'}
                        </div>
                    </div>
                </div>
                <span class="badge warn">Externo</span>
            </div>
        `;
    }

    function renderExtTechInfo(index) {
        const tech = window.extTechnicians[index];
        if (!tech) return;
        document.getElementById('active-ext-tech-name').textContent = tech.name;
        if (extInfo) {
            const phone = document.getElementById('ext-tech-phone');
            const specialty = document.getElementById('ext-tech-specialty');
            const email = document.getElementById('ext-tech-email');
            const locationText = document.getElementById('ext-tech-location-text');
            const locationLink = document.getElementById('ext-tech-location');
            if (phone) phone.value = tech.phone || '';
            if (specialty) specialty.value = tech.specialty || '';
            if (email) email.value = tech.email || '';
            if (locationText) locationText.value = tech.location || '';
            if (locationLink) {
                const query = encodeURIComponent(tech.location || '');
                locationLink.href = tech.location ? 'https://www.google.com/maps/search/?api=1&query=' + query : '#';
                locationLink.style.display = tech.location ? 'inline-flex' : 'none';
            }
        }
    }

    function selectExtTech(index) {
        selectedExtTech = index;
        document.querySelectorAll('#ext-tech-list .tech-row').forEach((row, i) => {
            row.classList.toggle('active', i === index);
        });
        if (extInfo) {
            extInfo.classList.remove('hidden');
        }
        renderExtTechInfo(index);
        const tech = window.extTechnicians[index];
        const technicianInput = document.getElementById('external_technician_id');
        if (technicianInput && tech) technicianInput.value = tech.id;
    }
    window.selectExtTech = selectExtTech;

    document.querySelectorAll('#ext-tech-list .tech-row').forEach(row => {
        row.addEventListener('click', () => selectExtTech(parseInt(row.dataset.tech)));
    });

    function addExtTechnician(tech) {
        if (!extTechList) {
            location.reload();
            return;
        }
        window.extTechnicians.push(tech);
        const index = window.extTechnicians.length - 1;
        extTechList.insertAdjacentHTML('beforeend', createExtTechRowHtml(tech, index));
        const newRow = extTechList.lastElementChild;
        if (newRow) {
            newRow.addEventListener('click', () => selectExtTech(index));
        }
        selectExtTech(index);
    }
    window.addExtTechnician = addExtTechnician;

    if (window.extTechnicians.length) {
        renderExtTechInfo(0);
    }
</script>
@endpush
