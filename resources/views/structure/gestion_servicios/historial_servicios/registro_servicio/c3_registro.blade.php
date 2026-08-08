        <!-- Paso 3: Tecnico -->
        <div class="step-panel" data-step="3" id="step-panel-interno">
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
                    <p class="muted" style="font-size:13px; margin:0 0 14px;">Selecciona alguno de los tecnicos disponibles</p>
                    <div class="technician-list" id="tech-list">
                        <div class="tech-row active" data-tech="0">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="tech-avatar">JG</div>
                                <div>
                                    <div style="font-weight:700;">Joel Garcia</div>
                                    <div class="muted" style="font-size:13px;">Especialidad: Equipo de Laparoscopia</div>
                                </div>
                            </div>
                            <span class="badge ok">2 activos</span>
                        </div>
                        <div class="tech-row" data-tech="1">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="tech-avatar" style="background:var(--accent);">AM</div>
                                <div>
                                    <div style="font-weight:700;">Adrian Marcelo</div>
                                    <div class="muted" style="font-size:13px;">Especialidad: Equipo de Urologia</div>
                                </div>
                            </div>
                            <span class="badge warn">5 activos</span>
                        </div>
                        <div class="tech-row" data-tech="2">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="tech-avatar" style="background:var(--danger);">IM</div>
                                <div>
                                    <div style="font-weight:700;">Icelda Maldonado</div>
                                    <div class="muted" style="font-size:13px;">Especialidad: Equipo de Endoscopia</div>
                                </div>
                            </div>
                            <span class="badge danger">9 activos</span>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding:18px;">
                    <h3 style="display:flex; align-items:center; gap:10px; font-size:17px; margin:0 0 14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Servicios activos del tecnico
                    </h3>
                    <p class="muted" style="font-size:13px; margin:0 0 12px;">Mostrando servicios activos de <strong id="active-tech-name">Joel Garcia</strong></p>
                    <div id="active-services">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                            <span class="badge ok" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                            <span>OS-1038</span>
                            <span class="muted">Hospital Mercario</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="badge ok" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                            <span>OS-1042</span>
                            <span class="muted">DR. Armendariz</span>
                        </div>
                    </div>
                </div>
            </div>


        </div>

@push('scripts')
<script>
    const technicians = [
        { name: 'Joel Garcia', specialty: 'Equipo de Laparoscopia', initials: 'JG', color: 'var(--primary)', badge: 'ok', label: '2 activos', services: [['OS-1038','Hospital Mercario'], ['OS-1042','DR. Armendariz']] },
        { name: 'Adrian Marcelo', specialty: 'Equipo de Urologia', initials: 'AM', color: 'var(--accent)', badge: 'warn', label: '5 activos', services: [['OS-1010','Hospital Central'], ['OS-1011','DR. Lopez'], ['OS-1012','Clinica Norte']] },
        { name: 'Icelda Maldonado', specialty: 'Equipo de Endoscopia', initials: 'IM', color: 'var(--danger)', badge: 'danger', label: '9 activos', services: [['OS-1001','Hospital Sur'], ['OS-1002','DR. Ruiz']] }
    ];
    let selectedTech = 0;
    function selectTech(index) {
        selectedTech = index;
        document.querySelectorAll('.tech-row').forEach((row, i) => {
            row.classList.toggle('active', i === index);
        });
        const tech = technicians[index];
        document.getElementById('active-tech-name').textContent = tech.name;
        const list = document.getElementById('active-services');
        list.innerHTML = tech.services.map(s => `
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <span class="badge ok" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                <span>${s[0]}</span>
                <span class="muted">${s[1]}</span>
            </div>`).join('');
    }
    document.querySelectorAll('.tech-row').forEach(row => {
        row.addEventListener('click', () => selectTech(parseInt(row.dataset.tech)));
    });
</script>
@endpush
