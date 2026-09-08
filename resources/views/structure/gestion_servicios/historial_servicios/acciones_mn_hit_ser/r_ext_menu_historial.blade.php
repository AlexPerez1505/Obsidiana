<div class="step-panel" data-step="4">
    <div class="resumen-grid">
        <!-- Acción Requerida -->
    <div class="resumen-card">
        <h3 class="resumen-title">
            <x-gravityui-thunderbolt width="18" height="18" />
            Acción Requerida
        </h3>

        <div class="resumen-alert">
            <x-gravityui-circle-info width="18" height="18" />
            <span>Registro protegido. Requiere captura vía formulario QR para asegurar identidad y firmas.</span>
        </div>

        <div class="resumen-actions">
            <button type="button" class="resumen-btn resumen-btn--primary">
                <x-gravityui-layout-cells-large width="16" height="16" />
                Generar QR
            </button>
            <button type="button" class="resumen-btn resumen-btn--ghost">
                <x-gravityui-link width="16" height="16" />
                Abrir Enlace
            </button>
        </div>

        <ul class="resumen-list">
            <li>Aplica exclusivamente a mantenimientos externos.</li>
            <li>Genera un acceso controlado mediante token temporal.</li>
            <li>Sincroniza automáticamente el movimiento de salida foránea.</li>
        </ul>
    </div>

    <!-- Ficha Técnica del Servicio -->
    <div class="resumen-card">
        <h3 class="resumen-title">
            <x-gravityui-circle-info width="18" height="18" />
            Ficha Técnica del Servicio
        </h3>

        <div class="resumen-detail">
            <span class="resumen-label">IDENTIFICACIÓN</span>
            <span class="resumen-value">endoscopia <span class="resumen-sep">|</span> adaptador_usb</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">NO. DE SERIE</span>
            <span class="resumen-value">gtvgvegr</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">MARCA / MODELO</span>
            <span class="resumen-value">dffrtgrtg ggagr</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">MÉDICO / TITULAR</span>
            <span class="resumen-value">gtvg</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">RESPONSABLE</span>
            <span class="resumen-value" style="font-weight:700;">Ing. José Alex Esquivel Perez</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">VALIDACIÓN OS</span>
            <span class="resumen-value resumen-pending">
                <x-gravityui-clock width="14" height="14" />
                Pendiente
            </span>
        </div>
    </div>

    <!-- Ruta de Trabajo -->
    <div class="resumen-card">
        <h3 class="resumen-title">
            <x-gravityui-paper-plane width="18" height="18" />
            Ruta de Trabajo
        </h3>

        <div class="resumen-step resumen-step--active">
            <div class="resumen-step-icon resumen-step-icon--active">
                <x-gravityui-briefcase width="18" height="18" />
            </div>
            <div class="resumen-step-body">
                <div class="resumen-step-name">Paso 1: Salida a mantenimiento foráneo</div>
                <div class="resumen-step-status" style="color:var(--primary);">EN PROCESO</div>
            </div>
        </div>

        <div class="resumen-step resumen-step--pending">
            <div class="resumen-step-icon">
                <x-gravityui-file-check width="18" height="18" />
            </div>
            <div class="resumen-step-body">
                <div class="resumen-step-name">Paso 2: Regreso de mantenimiento foráneo</div>
                <div class="resumen-step-status" style="color:var(--muted);">PENDIENTE</div>
            </div>
        </div>

        <div class="resumen-step resumen-step--pending">
            <div class="resumen-step-icon">
                <x-gravityui-file-text width="18" height="18" />
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
    <div class="resumen-card">
        <h3 class="resumen-title resumen-title--between">
            <span style="display:inline-flex; align-items:center; gap:8px;">
                <x-gravityui-clock width="18" height="18" />
                Auditoría de Movimientos
            </span>
            <span class="resumen-count">0 Eventos</span>
        </h3>

        <div class="resumen-empty">
            <x-gravityui-cloud-arrow-up-in width="42" height="42" />
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
</style>
