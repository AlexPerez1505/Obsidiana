<div class="step-panel" data-step="5" id="resumen-step">
    <div class="resumen-grid">
        <!-- Acción Requerida -->
    <div class="resumen-card" id="resumen-qr-card">
        <h3 class="resumen-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Acción Requerida
        </h3>

        <div class="resumen-alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>Escanear el código abre el formulario de reporte del equipo con los datos del cliente y del equipo.</span>
        </div>

        <div id="resumen-qr-wrap" style="text-align:center; padding:12px; border:1px solid var(--border); border-radius:12px; background:#fff;">
            <div id="resumen-qr-placeholder" style="padding:26px 10px; color:var(--muted); font-size:13px;">
                Completa el resumen para generar el QR de reporte.
            </div>
            <div id="resumen-qr-svg" style="display:none;"></div>
            <p style="font-size:12px; color:var(--muted); margin:10px 0 0;">Vista previa del QR de reporte</p>
        </div>

        <ul class="resumen-list" style="margin-top:14px;">
            <li>Al escanear se abre el formulario con los datos actuales.</li>
            <li>El QR se actualiza al modificar cliente, equipo o técnico.</li>
            <li>El QR de seguimiento se generará al dar clic en <strong>Guardar Orden</strong>.</li>
        </ul>
    </div>

    <div class="resumen-card">
        <h3 class="resumen-title resumen-title--between">
            <span style="display:inline-flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Resumen del servicio
            </span>
            <div style="display:flex; align-items:center; gap:6px;">
                <button type="button" class="resumen-edit-btn" onclick="window.goToStep && window.goToStep(1)">Cliente</button>
                <button type="button" class="resumen-edit-btn" onclick="window.goToStep && window.goToStep(2)">Equipo</button>
                <button type="button" class="resumen-edit-btn" onclick="window.goToStep && window.goToStep(3)">Técnico</button>
                <button type="button" class="resumen-edit-btn" id="res-edit-cotizacion" onclick="window.goToStep && window.goToStep(4)">Cotización</button>
            </div>
        </h3>

        <div class="resumen-detail">
            <span class="resumen-label">CLIENTE</span>
            <span class="resumen-value" id="res-client-name">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">TELÉFONO</span>
            <span class="resumen-value" id="res-client-phone">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">CORREO</span>
            <span class="resumen-value" id="res-client-email">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">TIPO DE SERVICIO</span>
            <span class="resumen-value" id="res-service-type">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">TIPO DE EQUIPO</span>
            <input type="text" class="resumen-input" id="res-tipo_equipo" data-sync="tipo_equipo" placeholder="Tipo de equipo">
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">SUBTIPO</span>
            <input type="text" class="resumen-input" id="res-subtipo" data-sync="subtipo" placeholder="Subtipo">
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">MARCA</span>
            <input type="text" class="resumen-input" id="res-marca" data-sync="marca" placeholder="Marca">
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">MODELO</span>
            <input type="text" class="resumen-input" id="res-modelo" data-sync="modelo" placeholder="Modelo">
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">NO. DE SERIE</span>
            <input type="text" class="resumen-input" id="res-serie" data-sync="serie" placeholder="Serie">
        </div>
        <div class="resumen-detail resumen-detail--top">
            <span class="resumen-label">DESCRIPCIÓN</span>
            <textarea class="resumen-input resumen-input--textarea" id="res-descripcion_equipo" data-sync="descripcion_equipo" rows="2" placeholder="Descripción del equipo"></textarea>
        </div>
        <div class="resumen-detail resumen-detail--top">
            <span class="resumen-label">OBSERVACIONES</span>
            <textarea class="resumen-input resumen-input--textarea" id="res-observaciones" data-sync="observaciones" rows="2" placeholder="Observaciones"></textarea>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">TÉCNICO</span>
            <span class="resumen-value" id="res-tech-name" style="font-weight:700;">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">ESPECIALIDAD</span>
            <span class="resumen-value" id="res-tech-specialty">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">TELÉFONO TÉCNICO</span>
            <span class="resumen-value" id="res-tech-phone">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">CORREO TÉCNICO</span>
            <span class="resumen-value" id="res-tech-email">-</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">UBICACIÓN TÉCNICO</span>
            <span class="resumen-value" id="res-tech-location">-</span>
        </div>
    </div>

    <!-- Cotización de refacciones -->
    <div class="resumen-card" id="resumen-cotizacion-card">
        <h3 class="resumen-title resumen-title--between">
            <span style="display:inline-flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Cotización de refacciones
            </span>
            <button type="button" class="resumen-edit-btn" id="resumen-cotizacion-edit" onclick="window.goToStep && window.goToStep(4)">Editar</button>
        </h3>
        <div id="resumen-refacciones-list" style="margin-bottom:12px;">
            <p style="color:var(--muted); font-size:13px; margin:0;">No se agregaron refacciones.</p>
        </div>
        <div class="resumen-detail" style="border-top:1px solid var(--border); padding-top:12px; margin-top:12px;">
            <span class="resumen-label">TOTAL REFACCIONES</span>
            <span class="resumen-value" id="resumen-refacciones-total" style="font-size:18px; font-weight:800; color:var(--primary);">$0.00</span>
        </div>
    </div>

    <!-- Ruta de Trabajo -->
    <div class="resumen-card" id="resumen-ruta-card">
        <h3 class="resumen-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            Ruta de Trabajo
        </h3>

        <div class="resumen-step resumen-step--active">
            <div class="resumen-step-icon resumen-step-icon--active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div class="resumen-step-body">
                <div class="resumen-step-name">Paso 1: Salida a mantenimiento foráneo</div>
                <div class="resumen-step-status" style="color:var(--primary);">EN PROCESO</div>
            </div>
        </div>

        <div class="resumen-step resumen-step--pending">
            <div class="resumen-step-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="resumen-step-body">
                <div class="resumen-step-name">Paso 2: Regreso de mantenimiento foráneo</div>
                <div class="resumen-step-status" style="color:var(--muted);">PENDIENTE</div>
            </div>
        </div>

        <div class="resumen-step resumen-step--pending">
            <div class="resumen-step-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div class="resumen-step-body">
                <div class="resumen-step-name">Paso 3: Validar Orden de Servicio</div>
                <div class="resumen-step-status" style="color:var(--muted);">PENDIENTE</div>
            </div>
        </div>

        <div class="resumen-step resumen-step--pending">
            <div class="resumen-step-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </div>
            <div class="resumen-step-body">
                <div class="resumen-step-name">Paso 4: Salida para cliente</div>
                <div class="resumen-step-status" style="color:var(--muted);">PENDIENTE</div>
            </div>
        </div>
    </div>

    <!-- Auditoría de Movimientos -->
    <div class="resumen-card" id="resumen-auditoria-card">
        <h3 class="resumen-title resumen-title--between">
            <span style="display:inline-flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Auditoría de Movimientos
            </span>
            <span class="resumen-count">0 Eventos</span>
        </h3>

        <div class="resumen-empty">
            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><path d="M8 17l4-4 4 4"/><path d="M8 12l4-4 4 4"/></svg>
            <p>Aún no se ha iniciado la bitácora de eventos para esta orden.</p>
        </div>
    </div>
</div>
</div>

<style>
.resumen-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    align-items: start;
}
.resumen-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 18px;
    box-shadow: var(--shadow);
}
.resumen-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 16px;
    color: var(--text);
}
.resumen-title--between {
    justify-content: space-between;
}
.resumen-title svg {
    color: var(--muted);
    flex-shrink: 0;
}
.resumen-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: var(--primary-soft);
    border: 1px solid var(--primary-soft);
    border-radius: 12px;
    padding: 12px;
    font-size: 13px;
    color: var(--primary-strong);
    margin-bottom: 14px;
}
.resumen-alert svg {
    flex-shrink: 0;
    color: var(--primary);
    margin-top: 1px;
}
.resumen-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.resumen-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    flex: 1;
    transition: background .16s ease, color .16s ease, border-color .16s ease;
}
.resumen-btn--primary {
    background: var(--primary);
    color: #fff;
}
.resumen-btn--primary:hover {
    background: var(--primary-strong);
}
.resumen-btn--ghost {
    background: var(--surface);
    color: var(--text);
    border: 1px solid var(--border);
}
.resumen-btn--ghost:hover {
    border-color: var(--primary);
    color: var(--primary);
}
.resumen-list {
    margin: 0;
    padding-left: 16px;
    font-size: 13px;
    color: var(--muted);
}
.resumen-list li {
    margin-bottom: 6px;
}
.resumen-list li:last-child {
    margin-bottom: 0;
}
.resumen-detail {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
}
.resumen-detail:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.resumen-label {
    color: var(--muted);
    font-weight: 600;
    letter-spacing: .03em;
}
.resumen-value {
    color: var(--text);
    text-align: right;
}
.resumen-sep {
    color: var(--muted);
    margin: 0 4px;
}
.resumen-pending {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--accent);
    font-weight: 700;
}
.resumen-step {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 14px;
    margin-bottom: 10px;
    background: var(--surface);
}
.resumen-step:last-child {
    margin-bottom: 0;
}
.resumen-step--active {
    border-color: var(--primary);
    background: var(--primary-soft);
}
.resumen-step-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-2);
    color: var(--muted);
    flex-shrink: 0;
}
.resumen-step-icon--active {
    background: var(--primary);
    color: #fff;
}
.resumen-step-body {
    flex: 1;
}
.resumen-step-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
}
.resumen-step-status {
    font-size: 12px;
    font-weight: 700;
    margin-top: 2px;
}
.resumen-count {
    font-size: 13px;
    color: var(--muted);
    font-weight: 600;
}
.resumen-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px 10px;
    color: var(--muted);
    text-align: center;
}
.resumen-empty svg {
    margin-bottom: 10px;
    color: var(--muted);
    opacity: .7;
}
.resumen-empty p {
    margin: 0;
    font-size: 13px;
}
@media (max-width: 900px) {
    .resumen-grid {
        grid-template-columns: 1fr;
    }
}
.resumen-edit-btn {
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--muted);
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.resumen-edit-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}
.resumen-detail--top {
    align-items: flex-start;
}
.resumen-input {
    border: 1px solid transparent;
    background: transparent;
    color: var(--text);
    font-size: 13px;
    text-align: right;
    padding: 4px 8px;
    border-radius: 8px;
    min-width: 140px;
    max-width: 100%;
}
.resumen-input:hover,
.resumen-input:focus {
    border-color: var(--primary);
    background: var(--surface);
    outline: none;
}
.resumen-input::placeholder {
    color: #aaa;
}
.resumen-input--textarea {
    width: 60%;
    min-width: 160px;
    text-align: left;
    resize: vertical;
}
</style>

@push('scripts')
<script>
(function () {
    function getClient() {
        if (window.clients) {
            const id = document.getElementById('customer_id')?.value;
            return window.clients.find(c => String(c.id) === String(id));
        }
        const selected = document.querySelector('.client-card.selected');
        if (selected) {
            return {
                name: selected.dataset.name || '-',
                phone: selected.dataset.phone || '',
                email: selected.dataset.email || ''
            };
        }
        return null;
    }

    function getTechnician() {
        const isExterno = parseInt(document.getElementById('mantenimiento_externo')?.value || 0);
        const isInterno = parseInt(document.getElementById('mantenimiento_interno')?.value || 0);
        if (isExterno) {
            const id = document.getElementById('external_technician_id')?.value;
            const tech = window.extTechnicians?.find(t => String(t.id) === String(id));
            return { type: 'Externo', tech };
        }
        if (isInterno) {
            const id = document.getElementById('internal_technician_id')?.value;
            const tech = window.intTechnicians?.find(t => String(t.id) === String(id));
            return { type: 'Interno', tech };
        }
        return { type: '-', tech: null };
    }

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text || '-';
    }

    function setInput(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value || '';
    }

    function isMantenimientoExterno() { return parseInt(document.getElementById('mantenimiento_externo')?.value || 0) === 1; }
    function isMantenimientoInterno() { return parseInt(document.getElementById('mantenimiento_interno')?.value || 0) === 1; }

    function updateResumen() {
        const client = getClient();
        if (client) {
            setText('res-client-name', client.name);
            setText('res-client-phone', client.phone || 'No registrado');
            setText('res-client-email', client.email || 'No registrado');
        }

        setInput('res-tipo_equipo', document.getElementById('tipo_equipo')?.value);
        setInput('res-subtipo', document.getElementById('subtipo')?.value);
        setInput('res-marca', document.getElementById('marca')?.value);
        setInput('res-modelo', document.getElementById('modelo')?.value);
        setInput('res-serie', document.getElementById('serie')?.value);
        setInput('res-descripcion_equipo', document.getElementById('descripcion_equipo')?.value);
        setInput('res-observaciones', document.getElementById('observaciones')?.value);

        const techData = getTechnician();
        const tech = techData?.tech;
        setText('res-service-type', techData?.type === 'Externo' ? 'Mantenimiento externo' : (techData?.type === 'Interno' ? 'Mantenimiento interno' : '-'));
        setText('res-tech-name', tech?.name);
        setText('res-tech-specialty', tech?.specialty || 'No registrada');
        setText('res-tech-phone', tech?.phone || 'No registrado');
        setText('res-tech-email', tech?.email || 'No registrado');
        setText('res-tech-location', tech?.location || 'No registrada');

        const isExterno = isMantenimientoExterno();
        const isInterno = isMantenimientoInterno();

        const qrCard = document.getElementById('resumen-qr-card');
        if (qrCard) qrCard.classList.toggle('hidden', !isExterno);

        const cotizacionCard = document.getElementById('resumen-cotizacion-card');
        if (cotizacionCard) cotizacionCard.classList.toggle('hidden', !isInterno);

        const rutaCard = document.getElementById('resumen-ruta-card');
        if (rutaCard) rutaCard.classList.toggle('hidden', !isExterno);

        const auditoriaCard = document.getElementById('resumen-auditoria-card');
        if (auditoriaCard) auditoriaCard.classList.toggle('hidden', isInterno);

        const editCotizacion = document.getElementById('res-edit-cotizacion');
        if (editCotizacion) editCotizacion.classList.toggle('hidden', !isInterno);

        const cotizacionEdit = document.getElementById('resumen-cotizacion-edit');
        if (cotizacionEdit) cotizacionEdit.classList.toggle('hidden', !isInterno);

        if (typeof window.updateResumenRefacciones === 'function') window.updateResumenRefacciones();

        updateReportQr();
    }

    let qrTimeout = null;

    function buildReportUrl() {
        const client = getClient();
        const techData = getTechnician();
        const tech = techData?.tech;

        const params = new URLSearchParams();
        const setParam = (key, value) => {
            if (value) params.set(key, value);
        };

        setParam('customer_name', client?.name);
        setParam('customer_phone', client?.phone);
        setParam('customer_email', client?.email);
        setParam('equipment_type', document.getElementById('tipo_equipo')?.value);
        setParam('equipment_subtype', document.getElementById('subtipo')?.value);
        setParam('equipment_brand', document.getElementById('marca')?.value);
        setParam('equipment_model', document.getElementById('modelo')?.value);
        setParam('serial_number', document.getElementById('serie')?.value);
        setParam('description', document.getElementById('descripcion_equipo')?.value);
        setParam('observations', document.getElementById('observaciones')?.value);
        setParam('technician_name', tech?.name);

        return "{{ route('reporte.equipo.create') }}" + (params.toString() ? '?' + params.toString() : '');
    }

    function updateReportQr() {
        const container = document.getElementById('resumen-qr-svg');
        const placeholder = document.getElementById('resumen-qr-placeholder');
        const card = document.getElementById('resumen-qr-card');
        if (!container || !placeholder || (card && card.classList.contains('hidden'))) return;

        const url = buildReportUrl();
        if (!url) {
            placeholder.style.display = 'block';
            container.style.display = 'none';
            return;
        }

        placeholder.style.display = 'none';
        container.style.display = 'block';
        container.innerHTML = '<span style="color:var(--muted); font-size:12px;">Generando QR...</span>';

        clearTimeout(qrTimeout);
        qrTimeout = setTimeout(() => {
            fetch("{{ route('qr.generar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ url: url })
            })
            .then(r => {
                if (!r.ok) throw new Error('Error generando QR');
                return r.text();
            })
            .then(svg => {
                container.innerHTML = '<div style="display:inline-block; border:1px solid var(--border); border-radius:12px; padding:10px; background:#fff;">' + svg + '</div>';
            })
            .catch(() => {
                container.innerHTML = '<span style="color:var(--muted); font-size:12px;">No se pudo generar el QR</span>';
            });
        }, 300);
    }

    function syncToHidden(input) {
        const targetId = input.dataset.sync;
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (target) target.value = input.value;
    }

    document.querySelectorAll('#resumen-step .resumen-input[data-sync]').forEach(input => {
        input.addEventListener('input', () => syncToHidden(input));
    });

    const resumenStep = document.getElementById('resumen-step');
    if (resumenStep) {
        resumenStep.addEventListener('input', updateReportQr);
        resumenStep.addEventListener('change', updateReportQr);
    }

    window.updateResumen = updateResumen;
})();
</script>
@endpush
