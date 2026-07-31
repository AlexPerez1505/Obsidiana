<style>
    /* ===== Estilos intensificados del formulario de congresos ===== */
    .is-hidden { display: none !important; }
    .step-2 { margin-top: 26px; }
    .step-2.is-hidden { display: none; }

    .step-indicator {
        display: flex; align-items: center; gap: 12px; margin-bottom: 22px;
        font-size: 14px; color: rgba(255, 255, 255, 0.65);
        text-transform: uppercase; letter-spacing: .05em;
    }
    .step-dot {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 50%;
        background: rgba(8, 18, 40, 0.7);
        color: rgba(255, 255, 255, 0.6);
        font-size: 13px; font-weight: 800; border: 1px solid rgba(0, 168, 255, 0.4);
        box-shadow: 0 0 8px rgba(0, 168, 255, 0.15);
        transition: all .25s ease;
    }
    .step-dot--active {
        background: linear-gradient(135deg, #00A8FF, #007aff);
        color: #fff; border-color: rgba(255, 255, 255, 0.55);
        box-shadow: 0 0 20px rgba(0, 168, 255, 0.65), 0 0 8px rgba(0, 168, 255, 0.35) inset;
        transform: scale(1.08);
    }
    .step-arrow { color: rgba(0, 168, 255, 0.65); font-size: 18px; text-shadow: 0 0 8px rgba(0, 168, 255, 0.4); }

    .form-section {
        --glow-color: #00A8FF;
        background: linear-gradient(145deg, rgba(8, 18, 40, 0.88), rgba(4, 12, 30, 0.88));
        border: 1px solid var(--glow-color);
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.35), 0 0 14px rgba(0, 168, 255, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.04);
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .form-section:hover { border-color: var(--glow-color); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 22px var(--glow-color), inset 0 1px 0 rgba(255, 255, 255, 0.06); }
    .section-info { --glow-color: #00d0ff; }
    .section-schedule { --glow-color: #22c55e; }
    .section-access { --glow-color: #a855f7; }
    .section-location { --glow-color: #f97316; }
    .form-section-title {
        margin: 0 0 16px; font-size: 17px; color: #fff;
        font-weight: 700; letter-spacing: .02em;
        text-shadow: 0 0 8px var(--glow-color);
    }
    .form-label { margin: 0 0 8px; font-size: 14px; color: #fff; font-weight: 500; }

    /* Inputs */
    .form-input {
        width: 100%; padding: 12px 14px; border: 1px solid rgba(0, 168, 255, 0.55);
        border-radius: 10px; font-size: 15px;
        background: rgba(4, 10, 24, 0.72);
        color: #fff; outline: none;
        transition: border-color .18s ease, box-shadow .18s ease, transform .12s ease;
    }
    .form-input:hover { border-color: rgba(0, 168, 255, 0.8); }
    .form-input:focus {
        border-color: #00A8FF;
        box-shadow: 0 0 0 3px rgba(0, 168, 255, 0.18), 0 0 18px rgba(0, 168, 255, 0.45);
        transform: translateY(-1px);
    }
    .form-input::placeholder { color: rgba(255, 255, 255, 0.4); }

    /* Fecha y hora (flatpickr) */
    input.flatpickr-date.form-input,
    input.flatpickr-time.form-input {
        padding-right: 38px;
        background-repeat: no-repeat !important;
        background-position: right 12px center !important;
        background-size: 16px 16px;
    }
    input.flatpickr-date.form-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2300A8FF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
    }
    input.flatpickr-time.form-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2300A8FF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
    }

    /* Selects y opciones desplegables */
    select.form-input {
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2300A8FF' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 38px;
        cursor: pointer;
    }

    /* Select simple: se mantiene el renderizado nativo para que el desplegado se vea limpio */
    select.form-input:not([multiple]) option,
    select.form-input:not([multiple]) option:checked,
    select.form-input:not([multiple]) option:hover {
        background: transparent;
        color: inherit;
        padding: 0;
    }

    /* Zona de carga de archivos */
    .image-upload {
        display: flex; align-items: center; justify-content: center; min-height: 130px;
        border: 2px dashed rgba(0, 168, 255, 0.65); border-radius: 14px; padding: 16px;
        text-align: center; cursor: pointer; color: rgba(255, 255, 255, 0.65); font-size: 14px;
        background: rgba(4, 10, 24, 0.55);
        transition: border-color .2s ease, color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .image-upload input { display: none; }
    .image-upload:hover, .image-upload.dragover {
        border-color: #00A8FF; color: #fff;
        background: rgba(0, 168, 255, 0.08);
        box-shadow: 0 0 24px rgba(0, 168, 255, 0.35), 0 0 0 4px rgba(0, 168, 255, 0.08);
    }

    /* Previsualización de archivos */
    .files-preview { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; }
    .files-preview:empty { display: none; }
    .file-card {
        position: relative; width: 104px; border: 1px solid rgba(0, 168, 255, 0.4);
        border-radius: 12px; background: linear-gradient(180deg, rgba(8, 18, 40, 0.9), rgba(4, 12, 30, 0.9));
        overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .file-card:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 8px 22px rgba(0, 168, 255, 0.25); }
    .file-card .thumb {
        width: 100%; height: 78px; object-fit: cover; background: linear-gradient(135deg, rgba(0, 168, 255, 0.12), rgba(0, 168, 255, 0.04));
        display: flex; align-items: center; justify-content: center; font-size: 28px;
    }
    .file-card .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .file-card .name { padding: 7px 10px; font-size: 12px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-card .remove {
        position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border: none;
        border-radius: 50%; background: rgba(0, 0, 0, 0.75); color: #fff; cursor: pointer;
        font-size: 14px; line-height: 1; display: flex; align-items: center; justify-content: center;
        transition: background .15s ease, transform .15s ease;
    }
    .file-card .remove:hover { background: #ef4444; transform: scale(1.1); }

    /* Textarea */
    textarea.form-input { resize: vertical; min-height: 100px; font-family: inherit; }

    /* Select multiple */
    select.form-input[multiple] {
        padding: 10px;
        background-image: none;
        cursor: default;
    }
    select.form-input[multiple] option { background: #081228; color: #fff; padding: 9px 12px; border-radius: 7px; margin: 3px 0; transition: background .15s ease, transform .12s ease; }
    select.form-input[multiple] option:hover { transform: translateX(3px); background: rgba(0, 168, 255, 0.45); }
    select.form-input[multiple] option:checked { background: linear-gradient(135deg, rgba(0, 168, 255, 0.4), rgba(0, 122, 255, 0.4)); color: #fff; }

    /* Switches de acceso */
    .ui-switch { position: relative; display: inline-block; width: 54px; height: 28px; flex: 0 0 auto; }
    .ui-switch input { opacity: 0; width: 0; height: 0; }
    .ui-switch .slider { position: absolute; cursor: pointer; inset: 0; background-color: rgba(255, 255, 255, 0.15); border-radius: 28px; transition: .3s; box-shadow: inset 0 0 6px rgba(0,0,0,0.4); }
    .ui-switch .slider:before { position: absolute; content: ""; height: 24px; width: 24px; left: 2px; bottom: 2px; background-color: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 2px 5px rgba(0,0,0,0.4); }
    .ui-switch input:checked + .slider { background-color: #00c950; box-shadow: 0 0 14px rgba(34, 197, 80, 0.55), inset 0 0 8px rgba(34, 197, 80, 0.3); }
    .ui-switch input:checked + .slider:before { transform: translateX(26px); }

    .access-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .access-title { margin: 0; font-weight: 700; color: #fff; }
    .access-desc { margin: 5px 0 0; font-size: 13px; color: rgba(255, 255, 255, 0.55); }
    .access-fields { margin-top: 14px; }
    .access-fields.is-hidden { display: none; }
    .form-divider { border: 0; border-top: 1px solid rgba(0, 168, 255, 0.35); margin: 18px 0; }

    /* Custom select (categoría) */
    .custom-select { position: relative; }
    .custom-select-trigger {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        width: 100%; padding: 12px 14px; border: 1px solid rgba(0, 168, 255, 0.55);
        border-radius: 10px; font-size: 15px;
        background: rgba(4, 10, 24, 0.72); color: #fff; outline: none;
        cursor: pointer; transition: border-color .18s ease, box-shadow .18s ease, transform .12s ease;
    }
    .custom-select:hover .custom-select-trigger, .custom-select.is-open .custom-select-trigger { border-color: rgba(0, 168, 255, 0.85); }
    .custom-select.is-open .custom-select-trigger {
        border-color: #00A8FF;
        box-shadow: 0 0 0 3px rgba(0, 168, 255, 0.18), 0 0 18px rgba(0, 168, 255, 0.45);
        transform: translateY(-1px);
    }
    .custom-select-label { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .custom-select-arrow { color: #00A8FF; flex: 0 0 auto; transition: transform .2s ease; filter: drop-shadow(0 0 4px rgba(0, 168, 255, 0.4)); }
    .custom-select.is-open .custom-select-arrow { transform: rotate(180deg); }
    .custom-select-options {
        position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 30;
        max-height: 180px; overflow-y: auto; display: none;
        border: 1px solid rgba(0, 168, 255, 0.55); border-radius: 10px;
        background: rgba(4, 10, 24, 0.96);
        box-shadow: 0 10px 32px rgba(0, 0, 0, 0.5), 0 0 18px rgba(0, 168, 255, 0.25);
    }
    .custom-select.is-open .custom-select-options { display: block; }
    .custom-select-option {
        padding: 12px 14px; cursor: pointer; color: #fff; font-size: 15px;
        border-bottom: 1px solid rgba(0, 168, 255, 0.18);
        transition: background .14s ease, color .14s ease, padding-left .14s ease;
    }
    .custom-select-option:last-child { border-bottom: none; }
    .custom-select-option:hover, .custom-select-option[aria-selected="true"] {
        background: rgba(0, 168, 255, 0.35); padding-left: 18px;
    }
    .custom-select-option[aria-selected="true"] {
        background: linear-gradient(135deg, rgba(0, 168, 255, 0.55), rgba(0, 122, 255, 0.55));
        color: #fff; font-weight: 600;
    }

    /* Sugerencias de dirección */
    .address-suggestions {
        border: 1px solid rgba(0, 168, 255, 0.55); border-radius: 10px;
        background: rgba(4, 10, 24, 0.96); margin-top: 5px; overflow: hidden; display: none;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45), 0 0 14px rgba(0, 168, 255, 0.2);
    }
    .address-suggestion { padding: 11px 14px; cursor: pointer; border-bottom: 1px solid rgba(0, 168, 255, 0.22); font-size: 14px; color: #fff; transition: background .14s ease, padding-left .14s ease; }
    .address-suggestion:last-child { border-bottom: none; }
    .address-suggestion:hover { background: rgba(0, 168, 255, 0.18); padding-left: 18px; color: #00d0ff; }
    .address-map-hint { display: flex; align-items: center; gap: 10px; margin-top: 10px; font-size: 13px; color: rgba(255, 255, 255, 0.55); }
    .address-map-hint a { color: #00A8FF; font-weight: 600; transition: color .15s ease, text-shadow .15s ease; }
    .address-map-hint a:hover { color: #00d0ff; text-shadow: 0 0 8px rgba(0, 168, 255, 0.5); }

    .form-actions { display: flex; justify-content: flex-end; gap: 14px; margin-top: 22px; }
    .form-actions .btn {
        position: relative; overflow: hidden;
        padding: 12px 22px; border-radius: 10px; font-weight: 700;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .form-actions .btn:hover { transform: translateY(-2px); }
    .form-actions .btn:not(.btn--secondary) {
        background: linear-gradient(135deg, #00A8FF, #007aff); color: #fff; border: none;
        box-shadow: 0 6px 18px rgba(0, 168, 255, 0.4);
    }
    .form-actions .btn:not(.btn--secondary):hover { box-shadow: 0 8px 26px rgba(0, 168, 255, 0.6); }
    .form-actions .btn--secondary {
        background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(0, 168, 255, 0.5); color: #fff;
    }
    .form-actions .btn--secondary:hover { background: rgba(0, 168, 255, 0.14); border-color: #00A8FF; }

    /* ===== Modo claro ===== */
    :root[data-theme="light"] .form-section {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(247, 248, 250, 0.95));
        border-color: rgba(15, 23, 42, 0.14);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }
    :root[data-theme="light"] .form-section:hover { border-color: rgba(0, 122, 255, 0.45); box-shadow: 0 10px 30px rgba(0, 122, 255, 0.14); }
    :root[data-theme="light"] .form-section-title,
    :root[data-theme="light"] .form-label,
    :root[data-theme="light"] .access-title { color: var(--text); text-shadow: none; }
    :root[data-theme="light"] .access-desc,
    :root[data-theme="light"] .address-map-hint { color: var(--muted); }
    :root[data-theme="light"] .form-input {
        background: rgba(15, 23, 42, 0.04); border-color: rgba(15, 23, 42, 0.18); color: var(--text);
    }
    :root[data-theme="light"] .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.12), 0 0 14px rgba(0, 122, 255, 0.2); }
    :root[data-theme="light"] .form-input::placeholder { color: var(--muted); }
    :root[data-theme="light"] input.flatpickr-date.form-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23007aff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
    }
    :root[data-theme="light"] input.flatpickr-time.form-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23007aff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
    }
    :root[data-theme="light"] select.form-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23007aff' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    }
    :root[data-theme="light"] select.form-input:not([multiple]) option,
    :root[data-theme="light"] select.form-input:not([multiple]) option:checked,
    :root[data-theme="light"] select.form-input:not([multiple]) option:hover { background: transparent; color: inherit; padding: 0; }
    :root[data-theme="light"] select.form-input[multiple] option { background: #fff; color: var(--text); padding: 10px 12px; }
    :root[data-theme="light"] select.form-input[multiple] option:checked { background: linear-gradient(135deg, rgba(0, 122, 255, 0.14), rgba(0, 168, 255, 0.14)); }
    :root[data-theme="light"] select.form-input[multiple] option:hover { background: rgba(0, 122, 255, 0.22); color: var(--text); }
    :root[data-theme="light"] .custom-select-trigger { background: rgba(15, 23, 42, 0.04); border-color: rgba(15, 23, 42, 0.18); color: var(--text); }
    :root[data-theme="light"] .custom-select:hover .custom-select-trigger,
    :root[data-theme="light"] .custom-select.is-open .custom-select-trigger { border-color: var(--primary); }
    :root[data-theme="light"] .custom-select-arrow { color: var(--primary); filter: none; }
    :root[data-theme="light"] .custom-select-options { background: #fff; border-color: rgba(15, 23, 42, 0.14); box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12); }
    :root[data-theme="light"] .custom-select-option { color: var(--text); border-bottom-color: rgba(15, 23, 42, 0.08); }
    :root[data-theme="light"] .custom-select-option:hover,
    :root[data-theme="light"] .custom-select-option[aria-selected="true"] { background: rgba(0, 122, 255, 0.12); }
    :root[data-theme="light"] .custom-select-option[aria-selected="true"] { background: linear-gradient(135deg, rgba(0, 122, 255, 0.18), rgba(0, 168, 255, 0.18)); color: var(--text); }
    :root[data-theme="light"] .image-upload {
        border-color: rgba(0, 122, 255, 0.45); color: var(--muted);
        background: rgba(15, 23, 42, 0.03);
    }
    :root[data-theme="light"] .image-upload:hover { border-color: var(--primary); color: var(--text); box-shadow: 0 0 20px rgba(0, 122, 255, 0.16); }
    :root[data-theme="light"] .file-card { background: #fff; border-color: rgba(15, 23, 42, 0.12); box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08); }
    :root[data-theme="light"] .file-card .name { color: var(--text); }
    :root[data-theme="light"] .file-card .thumb { background: rgba(15, 23, 42, 0.04); }
    :root[data-theme="light"] .ui-switch .slider { background-color: rgba(15, 23, 42, 0.2); }
    :root[data-theme="light"] .ui-switch input:checked + .slider { background-color: #22c55e; box-shadow: 0 0 12px rgba(34, 197, 80, 0.4); }
    :root[data-theme="light"] .ui-switch .slider:before { background-color: #fff; }
    :root[data-theme="light"] .form-divider { border-top-color: rgba(15, 23, 42, 0.1); }
    :root[data-theme="light"] .address-suggestions { background: #fff; border-color: rgba(15, 23, 42, 0.14); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12); }
    :root[data-theme="light"] .address-suggestion { color: var(--text); border-bottom-color: rgba(15, 23, 42, 0.08); }
    :root[data-theme="light"] .address-suggestion:hover { background: rgba(0, 122, 255, 0.1); color: var(--primary); }
    :root[data-theme="light"] .address-map-hint a { color: var(--primary); }
    :root[data-theme="light"] .step-indicator { color: var(--muted); }
    :root[data-theme="light"] .step-dot { background: rgba(15, 23, 42, 0.08); color: var(--muted); border-color: rgba(15, 23, 42, 0.18); }
    :root[data-theme="light"] .step-dot--active { background: linear-gradient(135deg, var(--primary), #005bb5); color: #fff; border-color: var(--primary); }
    :root[data-theme="light"] .step-arrow { color: var(--primary); }
    :root[data-theme="light"] .form-actions .btn:not(.btn--secondary) { background: linear-gradient(135deg, var(--primary), #005bb5); }
    :root[data-theme="light"] .form-actions .btn--secondary { background: rgba(15, 23, 42, 0.04); border-color: rgba(15, 23, 42, 0.2); color: var(--text); }
    :root[data-theme="light"] .form-actions .btn--secondary:hover { background: rgba(15, 23, 42, 0.08); border-color: var(--primary); }

    /* ===== Flatpickr ===== */
    .flatpickr-calendar {
        background: rgba(4, 10, 24, 0.96);
        border: 1px solid rgba(0, 168, 255, 0.55);
        border-radius: 12px;
        box-shadow: 0 10px 32px rgba(0, 0, 0, 0.5), 0 0 18px rgba(0, 168, 255, 0.25);
        color: #fff;
    }
    .flatpickr-calendar.open { z-index: 100; }
    .flatpickr-months .flatpickr-month { color: #fff; fill: #fff; }
    .flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month { color: #00A8FF; }
    .flatpickr-months .flatpickr-prev-month:hover, .flatpickr-months .flatpickr-next-month:hover { color: #00d0ff; }
    .flatpickr-current-month .flatpickr-monthDropdown-months { background: rgba(4, 10, 24, 0.95); color: #fff; }
    .flatpickr-weekdays { background: transparent; }
    .flatpickr-weekday { color: rgba(255, 255, 255, 0.6); }
    .flatpickr-days { background: transparent; }
    .flatpickr-day {
        color: #fff; border-radius: 8px;
        transition: background .14s ease, color .14s ease;
    }
    .flatpickr-day:hover { background: rgba(0, 168, 255, 0.35); color: #fff; border-color: transparent; }
    .flatpickr-day.today { border-color: #00A8FF; color: #00A8FF; }
    .flatpickr-day.selected, .flatpickr-day.selected:hover, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: linear-gradient(135deg, #00A8FF, #007aff); color: #fff; border-color: transparent; box-shadow: 0 0 10px rgba(0, 168, 255, 0.5);
    }
    .flatpickr-day.disabled, .flatpickr-day.disabled:hover, .flatpickr-day.notAllowed, .flatpickr-day.notAllowed:hover { color: rgba(255, 255, 255, 0.3); }
    .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay, .flatpickr-day.flatpickr-disabled { color: rgba(255, 255, 255, 0.35); }
    .flatpickr-time { background: transparent; border-top: 1px solid rgba(0, 168, 255, 0.35); }
    .flatpickr-time .numInputWrapper {
        background: rgba(8, 18, 40, 0.85) !important;
        border-radius: 8px;
        border: 1px solid rgba(0, 168, 255, 0.35);
    }
    .flatpickr-time .numInputWrapper input.numInput {
        color: #fff !important;
        background: transparent !important;
        font-weight: 600;
    }
    .flatpickr-time .flatpickr-am-pm {
        color: #fff;
        background: rgba(0, 168, 255, 0.15);
        border: 1px solid rgba(0, 168, 255, 0.45);
        border-radius: 8px;
        padding: 4px 10px;
        margin-left: 6px;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
        transition: background .14s ease, color .14s ease, border-color .14s ease, box-shadow .14s ease;
    }
    .flatpickr-time .flatpickr-am-pm:hover, .flatpickr-time .flatpickr-am-pm:focus {
        background: rgba(0, 168, 255, 0.35);
        border-color: #00A8FF;
        box-shadow: 0 0 10px rgba(0, 168, 255, 0.35);
    }
    .flatpickr-time .flatpickr-am-pm.flatpickr-active, .flatpickr-time .flatpickr-am-pm:active {
        background: linear-gradient(135deg, #00A8FF, #007aff);
        color: #fff;
        border-color: #00A8FF;
        box-shadow: 0 0 14px rgba(0, 168, 255, 0.5);
    }
    .flatpickr-time .flatpickr-time-separator { color: #fff; font-weight: 700; }
    .flatpickr-time .numInputWrapper span.arrowUp, .flatpickr-time .numInputWrapper span.arrowDown {
        background: transparent !important;
        border-color: rgba(0, 168, 255, 0.35);
    }
    .flatpickr-time .numInputWrapper span.arrowUp:after { border-bottom-color: #00A8FF; }
    .flatpickr-time .numInputWrapper span.arrowDown:after { border-top-color: #00A8FF; }
    .flatpickr-time input.flatpickr-hour, .flatpickr-time input.flatpickr-minute { color: #fff; }

    /* Light mode flatpickr */
    :root[data-theme="light"] .flatpickr-calendar {
        background: #fff; border-color: rgba(15, 23, 42, 0.14);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
        color: var(--text);
    }
    :root[data-theme="light"] .flatpickr-months .flatpickr-month { color: var(--text); fill: var(--text); }
    :root[data-theme="light"] .flatpickr-months .flatpickr-prev-month, :root[data-theme="light"] .flatpickr-months .flatpickr-next-month { color: var(--primary); }
    :root[data-theme="light"] .flatpickr-current-month .flatpickr-monthDropdown-months { background: #fff; color: var(--text); }
    :root[data-theme="light"] .flatpickr-weekday { color: var(--muted); }
    :root[data-theme="light"] .flatpickr-day { color: var(--text); }
    :root[data-theme="light"] .flatpickr-day:hover { background: rgba(0, 122, 255, 0.12); color: var(--text); }
    :root[data-theme="light"] .flatpickr-day.today { border-color: var(--primary); color: var(--primary); }
    :root[data-theme="light"] .flatpickr-day.selected, :root[data-theme="light"] .flatpickr-day.selected:hover { background: linear-gradient(135deg, var(--primary), #005bb5); color: #fff; }
    :root[data-theme="light"] .flatpickr-day.disabled, :root[data-theme="light"] .flatpickr-day.notAllowed { color: rgba(15, 23, 42, 0.35); }
    :root[data-theme="light"] .flatpickr-time { border-top-color: rgba(15, 23, 42, 0.1); }
    :root[data-theme="light"] .flatpickr-time .numInputWrapper {
        background: #fff !important;
        border-color: rgba(15, 23, 42, 0.14);
    }
    :root[data-theme="light"] .flatpickr-time .numInputWrapper input.numInput {
        color: var(--text) !important;
        background: transparent !important;
    }
    :root[data-theme="light"] .flatpickr-time .flatpickr-time-separator { color: var(--text); }
    :root[data-theme="light"] .flatpickr-time .flatpickr-am-pm {
        color: var(--text);
        background: rgba(0, 122, 255, 0.08);
        border-color: rgba(0, 122, 255, 0.3);
    }
    :root[data-theme="light"] .flatpickr-time .flatpickr-am-pm:hover, :root[data-theme="light"] .flatpickr-time .flatpickr-am-pm:focus {
        background: rgba(0, 122, 255, 0.18);
        border-color: var(--primary);
    }
    :root[data-theme="light"] .flatpickr-time .flatpickr-am-pm.flatpickr-active, :root[data-theme="light"] .flatpickr-time .flatpickr-am-pm:active {
        background: linear-gradient(135deg, var(--primary), #005bb5);
        color: #fff;
        border-color: var(--primary);
    }
    :root[data-theme="light"] .flatpickr-time .numInputWrapper span.arrowUp,
    :root[data-theme="light"] .flatpickr-time .numInputWrapper span.arrowDown { background: transparent !important; }
    :root[data-theme="light"] .flatpickr-time .numInputWrapper span.arrowUp:after { border-bottom-color: var(--primary); }
    :root[data-theme="light"] .flatpickr-time .numInputWrapper span.arrowDown:after { border-top-color: var(--primary); }

    @media (max-width: 1024px) {
        form > div:first-of-type { grid-template-columns: 1fr !important; }
    }
</style>
