@extends('structure.gestion_servicios.layout')

@section('title', 'Asignar técnico')

@section('service_content')
    @include('structure.gestion_servicios.Historial_se.registro_ns.externo_estilos_base')
    <style>
        .ns-grid {
            display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 20px;
            align-items: start;
        }
        @media (max-width: 760px) { .ns-grid { grid-template-columns: 1fr; } }

        .ns-section-title {
            font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 6px;
        }
        :root[data-theme="light"] .ns-section-title { color: var(--text); }
        .ns-section-subtitle { margin: 0 0 18px; font-size: 12px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-section-subtitle { color: var(--muted); }

        .ns-technician-list {
            display: flex; flex-direction: column; gap: 12px;
            margin-bottom: 18px;
        }
        .ns-technician-list--scrollable {
            max-height: 320px;
            overflow-y: auto;
            padding-right: 6px;
        }
        .ns-technician-list--scrollable::-webkit-scrollbar { width: 6px; }
        .ns-technician-list--scrollable::-webkit-scrollbar-thumb { background: rgba(0,122,255,0.4); border-radius: 4px; }
        .ns-technician-list--scrollable::-webkit-scrollbar-track { background: transparent; }
        .ns-technician-card {
            padding: 16px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(8,18,40,0.45);
            cursor: pointer; transition: all .16s ease;
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
        }
        :root[data-theme="light"] .ns-technician-card { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); }
        .ns-technician-card:hover { border-color: rgba(0,122,255,0.35); }
        .ns-technician-card.selected {
            border-color: #007AFF;
            background: rgba(0,122,255,0.12);
            box-shadow: 0 0 18px rgba(0,122,255,0.25);
        }
        .ns-technician-main { display: flex; align-items: center; gap: 12px; }
        .ns-technician-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(0,122,255,0.2); color: #007AFF;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; flex-shrink: 0;
        }
        .ns-technician-info h4 { margin: 0 0 3px; font-size: 14px; color: #fff; }
        :root[data-theme="light"] .ns-technician-info h4 { color: var(--text); }
        .ns-technician-info p { margin: 0; font-size: 11px; color: rgba(255,255,255,0.5); }
        :root[data-theme="light"] .ns-technician-info p { color: var(--muted); }
        .ns-check {
            width: 20px; height: 20px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            color: transparent; transition: all .16s ease;
        }
        .ns-technician-card.selected .ns-check { background: #007AFF; border-color: #007AFF; color: #fff; }
        .ns-check svg { width: 10px; height: 10px; }

        .ns-empty {
            text-align: center; padding: 36px 20px; color: rgba(255,255,255,0.45);
        }
        :root[data-theme="light"] .ns-empty { color: var(--muted); }
        .ns-empty p { margin: 0 0 16px; font-size: 13px; }

        .ns-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .ns-field label { font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.75); }
        :root[data-theme="light"] .ns-field label { color: var(--text); }
        .ns-field input {
            padding: 11px 13px; border: 1px solid rgba(255,255,255,0.12); border-radius: 11px;
            background: rgba(8,18,40,0.55); color: #fff; font-size: 13px; outline: none;
        }
        :root[data-theme="light"] .ns-field input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-field input::placeholder { color: rgba(255,255,255,0.35); }
        :root[data-theme="light"] .ns-field input::placeholder { color: var(--muted); }

        .ns-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000; padding: 20px;
        }
        .ns-modal-overlay.ns-hidden { display: none; }
        .ns-modal {
            width: 100%; max-width: 560px;
            background: #0B1221;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            max-height: 90vh; overflow-y: auto;
        }
        :root[data-theme="light"] .ns-modal { background: #fff; border-color: rgba(15,23,42,0.1); }
        .ns-modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 20px; }
        .ns-modal-title { font-size: 18px; font-weight: 800; color: #fff; margin: 0 0 4px; }
        :root[data-theme="light"] .ns-modal-title { color: var(--text); }
        .ns-modal-subtitle { font-size: 13px; color: rgba(255,255,255,0.55); margin: 0; }
        :root[data-theme="light"] .ns-modal-subtitle { color: var(--muted); }
        .ns-modal-close {
            background: transparent; border: none; color: rgba(255,255,255,0.6);
            cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;
        }
        .ns-modal-close:hover { color: #fff; }
        .ns-modal-body { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        .ns-modal-body .ns-field { margin-bottom: 14px; }
        .ns-modal-body .ns-field.full-width { grid-column: 1 / -1; }
        .ns-modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
        @media (max-width: 560px) { .ns-modal-body { grid-template-columns: 1fr; } }

        .ns-file-upload {
            position: relative;
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all .16s ease;
            background: rgba(8,18,40,0.35);
        }
        :root[data-theme="light"] .ns-file-upload { background: rgba(15,23,42,0.03); border-color: rgba(15,23,42,0.18); }
        .ns-file-upload:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-file-upload input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .ns-file-upload svg { width: 36px; height: 36px; color: rgba(255,255,255,0.4); margin-bottom: 8px; }
        :root[data-theme="light"] .ns-file-upload svg { color: var(--muted); }
        .ns-file-upload p { margin: 0; font-size: 13px; color: rgba(255,255,255,0.6); }
        :root[data-theme="light"] .ns-file-upload p { color: var(--muted); }
        .ns-file-upload small { display: block; margin-top: 4px; font-size: 11px; color: rgba(255,255,255,0.4); }
        :root[data-theme="light"] .ns-file-upload small { color: var(--muted); }
        .ns-file-preview { max-width: 100%; max-height: 120px; border-radius: 8px; margin-top: 10px; object-fit: cover; }
    </style>

    @php
        $fullName = trim($customer->nombre . ' ' . ($customer->apellido ?? ''));
        $initials = collect([$customer->nombre, $customer->apellido])->filter()->map(fn($n) => mb_substr($n, 0, 1))->implode('');
    @endphp

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <h2>Asignar técnico</h2>
                    <p>Selecciona un técnico especializado para el servicio</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancelar e iniciar
                </a>
                <a href="{{ route('gestion.servicios.nuevo.externo.equipo', ['customer_id' => $customer->id]) }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
                <button type="submit" form="technicianForm" class="ns-btn ns-btn--success" id="saveBtn" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Guardar registro
                </button>
            </div>
        </div>

        <div class="ns-stepper">
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Cliente</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step active">
                <div class="ns-step-number">3</div>
                <span>Tecnico</span>
            </div>
        </div>

        <div class="ns-customer-summary">
            <div class="ns-customer-main">
                <div class="ns-customer-avatar">{{ $initials ?: 'C' }}</div>
                <div class="ns-customer-info">
                    <h4>{{ $fullName }}</h4>
                    <p>{{ $customer->telefono ?: 'Sin teléfono' }}</p>
                </div>
            </div>
            <div class="ns-registrar">
                Registrado por: <strong>{{ auth()->user()?->name ?? 'Oliver' }}</strong>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>

        <form id="technicianForm" method="POST" action="{{ route('gestion.servicios.nuevo.externo.guardar') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="external_technician_id" id="external_technician_id" value="" disabled>
            <input type="hidden" name="nuevo_tecnico" id="nuevo_tecnico" value="" disabled>

            <div class="ns-grid">
                <div class="catalog-card service-section">
                    <div class="ns-section-title">Asignar tecnico externo</div>
                    <p class="ns-section-subtitle">Selecciona un tecnico externo disponible o registra uno nuevo</p>

                    <div class="ns-field" style="margin-bottom: 12px;">
                        <label>Buscar técnico</label>
                        <input type="text" id="technicianSearch" placeholder="Escribe un nombre..." oninput="filterTechnicians(this.value)" autocomplete="off">
                    </div>

                    <div class="ns-technician-list {{ count($technicians) > 4 ? 'ns-technician-list--scrollable' : '' }}" id="technicianList">
                        @forelse ($technicians as $tech)
                            @php
                                $techName = trim($tech->nombre . ' ' . ($tech->apellidos ?? ''));
                                $techInitials = collect([$tech->nombre, $tech->apellidos])->filter()->map(fn($n) => mb_substr($n, 0, 1))->implode('');
                                $techPhone = $tech->telefono ?: 'Sin teléfono';
                                $techEmail = $tech->correo ?: 'Sin correo';
                            @endphp
                            <div class="ns-technician-card" onclick="selectTechnician({{ $tech->id }})" data-id="{{ $tech->id }}">
                                <div class="ns-technician-main">
                                    <div class="ns-technician-avatar">{{ $techInitials ?: 'T' }}</div>
                                    <div class="ns-technician-info">
                                        <h4>{{ $techName }}</h4>
                                        <p>{{ $techPhone }} &nbsp;|&nbsp; {{ $techEmail }}</p>
                                    </div>
                                </div>
                                <div class="ns-check">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                            </div>
                        @empty
                            <div class="ns-empty" id="emptyMessage">
                                <p>No hay tecnicos externos registrados.</p>
                            </div>
                        @endforelse
                    </div>

                    <button type="button" class="ns-btn ns-btn--primary" style="width: 100%;" onclick="showNewTechnician()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Agregar tecnico externo
                    </button>
                </div>

                <div class="catalog-card service-section" id="detailsCard">
                    <div class="ns-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;color:#007AFF;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Informacion del tecnico externo
                    </div>
                    <p class="ns-section-subtitle" id="detailsSubtitle">Selecciona un técnico de la lista para ver sus detalles.</p>

                    <div id="existingDetails" class="ns-hidden">
                        <div class="ns-field">
                            <label>Nombre</label>
                            <input type="text" id="existingName" readonly>
                        </div>
                        <div class="ns-field">
                            <label>Teléfono</label>
                            <input type="text" id="existingPhone" readonly>
                        </div>
                        <div class="ns-field">
                            <label>Correo</label>
                            <input type="text" id="existingEmail" readonly>
                        </div>
                        <div class="ns-field">
                            <label>Especialidad</label>
                            <input type="text" id="existingSpecialty" readonly>
                        </div>
                        <div class="ns-field">
                            <label>Empresa</label>
                            <input type="text" id="existingCompany" readonly>
                        </div>
                    </div>


                </div>
            </div>

        @include('structure.gestion_servicios.Historial_se.registro_NS.externo.tecnico.modal_crear_tecnico')
        </form>
    </div>

    <script>
        const technicians = @json($technicians);
        let selectedTechnicianId = null;
        let isNewTechnician = false;

        function setModalFieldsEnabled(enabled) {
            const fields = ['newNombre','newApellidos','newTelefono','newCorreo','newEspecialidad','newDomicilio','newEmpresa','newPhoto'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = !enabled;
            });
        }

        function selectTechnician(id) {
            selectedTechnicianId = id;
            isNewTechnician = false;
            document.getElementById('newTechnicianModal').classList.add('ns-hidden');

            document.querySelectorAll('.ns-technician-card').forEach(card => {
                card.classList.toggle('selected', parseInt(card.dataset.id) === id);
            });

            const tech = technicians.find(t => t.id === id);
            if (tech) {
                const extId = document.getElementById('external_technician_id');
                extId.value = id;
                extId.disabled = false;
                document.getElementById('nuevo_tecnico').value = '';
                document.getElementById('nuevo_tecnico').disabled = true;
                setModalFieldsEnabled(false);
                document.getElementById('detailsSubtitle').textContent = 'Detalles del técnico seleccionado';
                document.getElementById('existingDetails').classList.remove('ns-hidden');

                document.getElementById('existingName').value = (tech.nombre + ' ' + (tech.apellidos || '')).trim();
                document.getElementById('existingPhone').value = tech.telefono || 'Sin teléfono';
                document.getElementById('existingEmail').value = tech.correo || 'Sin correo';
                document.getElementById('existingSpecialty').value = tech.especialidad || 'Sin especialidad';
                document.getElementById('existingCompany').value = tech.empresa || 'Sin empresa';
            }

            document.getElementById('saveBtn').disabled = false;
        }

        function showNewTechnician() {
            selectedTechnicianId = null;
            isNewTechnician = true;

            document.querySelectorAll('.ns-technician-card').forEach(card => {
                card.classList.remove('selected');
            });

            const extId = document.getElementById('external_technician_id');
            extId.value = '';
            extId.disabled = true;
            document.getElementById('nuevo_tecnico').value = '1';
            document.getElementById('nuevo_tecnico').disabled = false;
            setModalFieldsEnabled(true);
            document.getElementById('detailsSubtitle').textContent = 'Completa los datos del nuevo técnico.';
            document.getElementById('existingDetails').classList.add('ns-hidden');
            document.getElementById('saveBtn').disabled = true;

            document.getElementById('newTechnicianModal').classList.remove('ns-hidden');
            document.getElementById('newNombre').focus();
            enableSaveIfNewValid();
        }

        function closeNewTechnicianModal() {
            document.getElementById('newTechnicianModal').classList.add('ns-hidden');
            document.getElementById('newNombre').value = '';
            document.getElementById('newApellidos').value = '';
            document.getElementById('newTelefono').value = '';
            document.getElementById('newCorreo').value = '';
            document.getElementById('newEspecialidad').value = '';
            document.getElementById('newDomicilio').value = '';
            document.getElementById('newEmpresa').value = '';
            document.getElementById('newPhoto').value = '';
            document.getElementById('photoPreview').src = '';
            document.getElementById('photoPreview').classList.add('ns-hidden');
            document.getElementById('photoUploadText').textContent = 'Haz clic aqui para subir una foto';
            document.getElementById('nuevo_tecnico').value = '';
            document.getElementById('nuevo_tecnico').disabled = true;
            const extId = document.getElementById('external_technician_id');
            extId.value = '';
            extId.disabled = true;
            setModalFieldsEnabled(false);
            isNewTechnician = false;
            document.getElementById('modalSaveBtn').disabled = true;
            document.getElementById('saveBtn').disabled = true;
        }

        function enableSaveIfNewValid() {
            const nombre = document.getElementById('newNombre').value.trim();
            document.getElementById('modalSaveBtn').disabled = !nombre;
        }

        function submitNewTechnician() {
            const nombre = document.getElementById('newNombre').value.trim();
            if (!nombre) {
                document.getElementById('newNombre').focus();
                return;
            }
            document.getElementById('nuevo_tecnico').disabled = false;
            document.getElementById('nuevo_tecnico').value = '1';
            setModalFieldsEnabled(true);
            document.getElementById('technicianForm').submit();
        }

        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const text = document.getElementById('photoUploadText');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('ns-hidden');
                };
                reader.readAsDataURL(input.files[0]);
                text.textContent = input.files[0].name;
            } else {
                preview.src = '';
                preview.classList.add('ns-hidden');
                text.textContent = 'Haz clic aqui para subir una foto';
            }
        }

        function filterTechnicians(query) {
            const normalized = query.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
            const cards = document.querySelectorAll('.ns-technician-card');
            let anyVisible = false;
            cards.forEach(function (card) {
                const name = card.querySelector('.ns-technician-info h4').textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                const match = !normalized || name.includes(normalized);
                card.style.display = match ? '' : 'none';
                if (match) anyVisible = true;
            });
            const empty = document.getElementById('emptyMessage');
            if (empty) {
                empty.style.display = anyVisible ? 'none' : 'block';
                if (!anyVisible) {
                    empty.querySelector('p').textContent = 'No se encontraron técnicos con ese nombre.';
                } else {
                    empty.querySelector('p').textContent = 'No hay tecnicos externos registrados.';
                }
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('newTechnicianModal').classList.contains('ns-hidden')) {
                closeNewTechnicianModal();
            }
        });
    </script>
@endsection
