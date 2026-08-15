<style>
    .ns-page { max-width: 900px; margin: 0 auto; }

    .ns-header {
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        flex-wrap: wrap; margin-bottom: 22px;
    }
    .ns-header-title { display: flex; align-items: center; gap: 14px; }
    .ns-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: rgba(0,122,255,0.12); color: #007AFF;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ns-icon svg { width: 24px; height: 24px; }
    .ns-header-title h2 { margin: 0; font-size: 22px; color: #fff; }
    :root[data-theme="light"] .ns-header-title h2 { color: var(--text); }
    .ns-header-title p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 13px; }
    :root[data-theme="light"] .ns-header-title p { color: var(--muted); }
    .ns-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .ns-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
        text-decoration: none; cursor: pointer; transition: all .16s ease; border: none;
    }
    .ns-btn svg { width: 16px; height: 16px; }
    .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
    :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
    .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
    .ns-btn--primary { background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
    .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }
    .ns-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; }
    .ns-btn--success { background: #22C55E; color: #fff; box-shadow: 0 0 14px rgba(34,197,94,0.35); }
    .ns-btn--success:hover { background: #16A34A; box-shadow: 0 0 20px rgba(34,197,94,0.5); }
    .ns-btn--success:disabled { opacity: 0.55; cursor: not-allowed; }

    .ns-stepper {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 24px; padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    :root[data-theme="light"] .ns-stepper { border-color: rgba(15,23,42,0.08); }
    .ns-step { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.45); }
    :root[data-theme="light"] .ns-step { color: var(--muted); }
    .ns-step-number {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800;
        background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);
    }
    :root[data-theme="light"] .ns-step-number { background: rgba(15,23,42,0.08); color: var(--muted); }
    .ns-step.completed { color: #22C55E; }
    :root[data-theme="light"] .ns-step.completed { color: #16A34A; }
    .ns-step.completed .ns-step-number { background: #22C55E; color: #fff; }
    .ns-step.active { color: #fff; }
    :root[data-theme="light"] .ns-step.active { color: var(--text); }
    .ns-step.active .ns-step-number { background: #007AFF; color: #fff; }
    .ns-step-line { flex: 1; height: 1px; min-width: 30px; background: rgba(255,255,255,0.08); }
    :root[data-theme="light"] .ns-step-line { background: rgba(15,23,42,0.08); }

    .ns-customer-summary {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        margin-bottom: 22px;
    }
    .ns-customer-main { display: flex; align-items: center; gap: 14px; }
    .ns-customer-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: #007AFF; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 800; flex-shrink: 0;
    }
    .ns-customer-info h4 { margin: 0 0 4px; font-size: 15px; color: #fff; }
    :root[data-theme="light"] .ns-customer-info h4 { color: var(--text); }
    .ns-customer-info p { margin: 0; font-size: 12px; color: rgba(255,255,255,0.5); }
    :root[data-theme="light"] .ns-customer-info p { color: var(--muted); }
    .ns-registrar {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: rgba(255,255,255,0.6);
    }
    :root[data-theme="light"] .ns-registrar { color: var(--muted); }
    .ns-registrar svg { width: 18px; height: 18px; }

    .ns-summary {
        display: flex; align-items: center; gap: 14px; margin-bottom: 22px;
    }
    .ns-summary-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: #007AFF; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 800; flex-shrink: 0;
    }
    .ns-summary-info h4 { margin: 0 0 4px; font-size: 15px; color: #fff; }
    :root[data-theme="light"] .ns-summary-info h4 { color: var(--text); }
    .ns-summary-info p { margin: 0; font-size: 12px; color: rgba(255,255,255,0.5); }
    :root[data-theme="light"] .ns-summary-info p { color: var(--muted); }

    .ns-section { margin-bottom: 22px; }
    .ns-section-title {
        display: flex; align-items: center; gap: 10px;
        font-size: 16px; font-weight: 800; color: #fff;
        margin-bottom: 6px;
    }
    :root[data-theme="light"] .ns-section-title { color: var(--text); }
    .ns-section-title svg { width: 20px; height: 20px; color: #007AFF; }
    .ns-section-subtitle { margin: 0 0 18px; font-size: 13px; color: rgba(255,255,255,0.55); }
    :root[data-theme="light"] .ns-section-subtitle { color: var(--muted); }

    .ns-form-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
        margin-bottom: 18px;
    }
    @media (max-width: 760px) { .ns-form-grid { grid-template-columns: 1fr; } }
    .ns-field { display: flex; flex-direction: column; gap: 6px; }
    .ns-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
    :root[data-theme="light"] .ns-field label { color: var(--text); }
    .ns-field select, .ns-field input, .ns-field textarea {
        padding: 12px 14px; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
        background: rgba(8,18,40,0.55); color: #fff; font-size: 14px; outline: none;
        font-family: inherit;
    }
    :root[data-theme="light"] .ns-field select, :root[data-theme="light"] .ns-field input, :root[data-theme="light"] .ns-field textarea { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
    .ns-field select option { background: #0b1220; color: #fff; }
    .ns-field textarea { min-height: 90px; resize: vertical; }
    .ns-field select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.5)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
    :root[data-theme="light"] .ns-field select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23334155' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); }

    .ns-upload-grid {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
        margin-bottom: 10px;
    }
    @media (max-width: 760px) { .ns-upload-grid { grid-template-columns: repeat(2, 1fr); } }
    .ns-upload-box {
        position: relative; aspect-ratio: 1 / 1;
        border: 1px dashed rgba(255,255,255,0.2); border-radius: 14px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 8px; cursor: pointer; overflow: hidden;
        background: rgba(8,18,40,0.35); color: rgba(255,255,255,0.65);
        transition: all .16s ease;
    }
    :root[data-theme="light"] .ns-upload-box { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); color: var(--muted); }
    .ns-upload-box:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
    .ns-upload-box svg { width: 26px; height: 26px; }
    .ns-upload-box .ns-upload-label { font-size: 12px; font-weight: 700; text-align: center; }
    .ns-upload-box .ns-upload-hint { font-size: 10px; opacity: 0.7; }
    .ns-upload-box input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .ns-upload-preview { position: absolute; inset: 0; object-fit: cover; width: 100%; height: 100%; display: none; }
    .ns-upload-hint-text { font-size: 11px; color: rgba(255,255,255,0.4); margin-bottom: 22px; }
    :root[data-theme="light"] .ns-upload-hint-text { color: var(--muted); }

    .ns-signature {
        background: #fff; border-radius: 14px; overflow: hidden;
        width: 100%; height: 160px; position: relative;
    }
    .ns-signature canvas { width: 100%; height: 100%; touch-action: none; }
    .ns-signature-clear {
        position: absolute; right: 10px; bottom: 10px;
        padding: 6px 12px; border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.2); background: rgba(0,0,0,0.6);
        color: #fff; font-size: 11px; cursor: pointer;
    }
    .ns-sig-tabs { display: flex; gap: 8px; margin-bottom: 12px; }
    .ns-sig-tab {
        padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 700;
        border: 1px solid rgba(255,255,255,0.12); background: transparent;
        color: rgba(255,255,255,0.65); cursor: pointer; transition: all .16s ease;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .ns-sig-tab svg { width: 15px; height: 15px; }
    :root[data-theme="light"] .ns-sig-tab { border-color: rgba(15,23,42,0.14); color: var(--muted); }
    .ns-sig-tab:hover { border-color: #007AFF; color: #007AFF; }
    .ns-sig-tab.active { background: #007AFF; border-color: #007AFF; color: #fff; }
    .ns-sig-upload {
        border: 1px dashed rgba(255,255,255,0.2); border-radius: 14px;
        background: rgba(8,18,40,0.35); color: rgba(255,255,255,0.65);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 8px; cursor: pointer; position: relative; overflow: hidden;
        width: 100%; height: 160px;
    }
    :root[data-theme="light"] .ns-sig-upload { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); color: var(--muted); }
    .ns-sig-upload:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
    .ns-sig-upload svg { width: 28px; height: 28px; }
    .ns-sig-upload .ns-sig-label { font-size: 13px; font-weight: 700; }
    .ns-sig-upload .ns-sig-hint { font-size: 11px; opacity: 0.7; }
    .ns-sig-upload input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .ns-sig-preview {
        position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: contain; background: #fff; display: none;
    }
    .ns-sig-clear-upload {
        position: absolute; right: 10px; bottom: 10px;
        padding: 6px 12px; border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.2); background: rgba(0,0,0,0.6);
        color: #fff; font-size: 11px; cursor: pointer; display: none; z-index: 2;
    }

    .ns-table-header {
        display: grid; grid-template-columns: 1.8fr 100px 130px 130px 44px; gap: 10px;
        padding: 10px 0; font-size: 12px; font-weight: 700;
        color: rgba(255,255,255,0.6); border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    :root[data-theme="light"] .ns-table-header { color: var(--muted); border-color: rgba(15,23,42,0.08); }
    .ns-table-row {
        display: grid; grid-template-columns: 1.8fr 100px 130px 130px 44px; gap: 10px;
        align-items: center; padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .ns-table-row input {
        width: 100%; padding: 10px 12px; border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
    }
    :root[data-theme="light"] .ns-table-row input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
    .ns-table-row input::placeholder { color: rgba(255,255,255,0.4); }
    :root[data-theme="light"] .ns-table-row input::placeholder { color: var(--muted); }
    .ns-table-row input[type="number"] { text-align: right; }
    .refaccion-cell { display: flex; align-items: center; gap: 10px; }
    .ref-preview-img {
        width: 44px; height: 44px; object-fit: cover; border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55);
        flex-shrink: 0;
    }
    :root[data-theme="light"] .ref-preview-img { border-color: rgba(15,23,42,0.14); }
    .refaccion-cell-info { flex: 1; min-width: 0; }
    .refaccion-select {
        width: 100%; padding: 10px 12px; border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
        margin-bottom: 6px; cursor: pointer;
    }
    :root[data-theme="light"] .refaccion-select { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
    .ref-concepto { margin-bottom: 4px; }
    .ref-subtipo { font-size: 12px; color: rgba(255,255,255,0.55); min-height: 16px; }
    :root[data-theme="light"] .ref-subtipo { color: var(--muted); }
    .ns-row-total { text-align: right; font-size: 14px; font-weight: 700; color: #fff; }
    :root[data-theme="light"] .ns-row-total { color: var(--text); }
    .ns-remove-btn {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        border: none; background: rgba(239,68,68,0.12); color: #EF4444; cursor: pointer;
    }
    .ns-remove-btn svg { width: 16px; height: 16px; }
    .ns-add-btn {
        margin-top: 14px; padding: 10px 16px;
        border: 1px dashed rgba(0,122,255,0.5); border-radius: 10px;
        background: transparent; color: #007AFF; font-size: 13px; font-weight: 700;
        cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    }

    .ns-totals {
        display: flex; flex-direction: column; gap: 10px;
        margin-top: 24px; padding-top: 20px;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    :root[data-theme="light"] .ns-totals { border-color: rgba(15,23,42,0.08); }
    .ns-totals-row {
        display: flex; align-items: center; justify-content: space-between;
        font-size: 14px; color: rgba(255,255,255,0.7);
    }
    :root[data-theme="light"] .ns-totals-row { color: var(--text); }
    .ns-totals-row input {
        width: 120px; padding: 8px 12px; border-radius: 10px; text-align: right;
        border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
    }
    :root[data-theme="light"] .ns-totals-row input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
    .ns-totals-row.total {
        font-size: 18px; font-weight: 800; color: #fff;
        padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);
    }
    :root[data-theme="light"] .ns-totals-row.total { color: var(--text); border-color: rgba(15,23,42,0.1); }
    .ns-totals-row .ns-total-value { color: #007AFF; font-size: 20px; }
    .ns-iva-check {
        display: flex; align-items: center; gap: 8px; cursor: pointer;
        font-size: 14px; color: rgba(255,255,255,0.75);
    }
    :root[data-theme="light"] .ns-iva-check { color: var(--text); }
    .ns-iva-check input { width: 18px; height: 18px; accent-color: #007AFF; cursor: pointer; }

    .ns-alert {
        display: none; align-items: center; gap: 10px;
        background: rgba(239,68,68,0.12); color: #F87171;
        border: 1px solid rgba(239,68,68,0.25); border-radius: 12px;
        padding: 12px 16px; font-size: 13px; font-weight: 600;
        margin-bottom: 22px;
    }
    .ns-alert svg { width: 18px; height: 18px; flex-shrink: 0; }
    .ns-alert.visible { display: flex; }

    .ns-columns {
        display: grid; grid-template-columns: 1fr 1fr; gap: 22px;
        align-items: start;
    }
    @media (max-width: 860px) { .ns-columns { grid-template-columns: 1fr; } }

    .ns-technician-list { display: flex; flex-direction: column; gap: 12px; }
    .ns-technician-card {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        padding: 14px; border-radius: 14px;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(8,18,40,0.45);
        cursor: pointer; transition: all .16s ease;
    }
    :root[data-theme="light"] .ns-technician-card { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); }
    .ns-technician-card:hover { border-color: rgba(0,122,255,0.35); }
    .ns-technician-card.selected {
        border-color: #007AFF;
        background: rgba(0,122,255,0.12);
        box-shadow: 0 0 14px rgba(0,122,255,0.15);
    }
    .ns-technician-main { display: flex; align-items: center; gap: 12px; }
    .ns-technician-avatar {
        width: 42px; height: 42px; border-radius: 50%;
        background: #007AFF; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 800; flex-shrink: 0;
    }
    .ns-technician-info h4 { margin: 0 0 3px; font-size: 15px; color: #fff; }
    :root[data-theme="light"] .ns-technician-info h4 { color: var(--text); }
    .ns-technician-info p { margin: 0; font-size: 12px; color: rgba(255,255,255,0.55); }
    :root[data-theme="light"] .ns-technician-info p { color: var(--muted); }
    .ns-technician-badge {
        padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800;
        white-space: nowrap;
    }
    .ns-technician-badge.green { background: rgba(34,197,94,0.18); color: #22C55E; }
    .ns-technician-badge.yellow { background: rgba(234,179,8,0.18); color: #EAB308; }
    .ns-technician-badge.red { background: rgba(239,68,68,0.18); color: #EF4444; }
    :root[data-theme="light"] .ns-technician-badge.green { background: rgba(34,197,94,0.12); }
    :root[data-theme="light"] .ns-technician-badge.yellow { background: rgba(234,179,8,0.12); }
    :root[data-theme="light"] .ns-technician-badge.red { background: rgba(239,68,68,0.12); }

    .ns-active-title { margin: 0 0 4px; font-size: 16px; font-weight: 800; color: #fff; }
    :root[data-theme="light"] .ns-active-title { color: var(--text); }
    .ns-active-subtitle { margin: 0 0 18px; font-size: 13px; color: rgba(255,255,255,0.55); }
    :root[data-theme="light"] .ns-active-subtitle { color: var(--muted); }
    .ns-active-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
    .ns-active-list.hidden { display: none; }
    .ns-active-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 14px; border-radius: 12px;
        background: rgba(8,18,40,0.35); border: 1px solid rgba(255,255,255,0.06);
    }
    :root[data-theme="light"] .ns-active-item { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }
    .ns-active-dot { width: 9px; height: 9px; border-radius: 50%; background: #22C55E; flex-shrink: 0; }
    .ns-active-info { display: flex; flex-direction: column; }
    .ns-active-info strong { font-size: 14px; color: #fff; }
    :root[data-theme="light"] .ns-active-info strong { color: var(--text); }
    .ns-active-info span { font-size: 12px; color: rgba(255,255,255,0.55); }
    :root[data-theme="light"] .ns-active-info span { color: var(--muted); }

    .ns-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
    @media (max-width: 520px) { .ns-dates { grid-template-columns: 1fr; } }
    .ns-date-field { display: flex; flex-direction: column; gap: 6px; }
    .ns-date-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
    :root[data-theme="light"] .ns-date-field label { color: var(--text); }
    .ns-date-inputs { display: flex; gap: 8px; }
    .ns-date-inputs input {
        width: 56px; padding: 10px; text-align: center; border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
    }
    :root[data-theme="light"] .ns-date-inputs input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
    .ns-date-inputs input.year { width: 70px; }
    .ns-date-inputs input::placeholder { color: rgba(255,255,255,0.4); }
    :root[data-theme="light"] .ns-date-inputs input::placeholder { color: var(--muted); }

    .ns-notify {
        display: flex; align-items: center; gap: 10px;
        font-size: 13px; color: rgba(255,255,255,0.75); cursor: pointer;
    }
    :root[data-theme="light"] .ns-notify { color: var(--text); }
    .ns-notify input { width: 18px; height: 18px; accent-color: #007AFF; cursor: pointer; }
    .ns-notify span { font-size: 12px; color: rgba(255,255,255,0.5); }
    :root[data-theme="light"] .ns-notify span { color: var(--muted); }

    .ns-modal-overlay {
        position: fixed; inset: 0; z-index: 100;
        display: none; align-items: center; justify-content: center;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
    }
    .ns-modal-overlay.active { display: flex; }
    .ns-modal {
        width: 100%; max-width: 460px; margin: 18px;
        background: #0b1220; border: 1px solid rgba(255,255,255,0.1);
        border-radius: 18px; padding: 24px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.4);
    }
    :root[data-theme="light"] .ns-modal { background: #fff; border-color: rgba(15,23,42,0.1); }
    .ns-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px;
    }
    .ns-modal-title { font-size: 18px; font-weight: 800; color: #fff; }
    :root[data-theme="light"] .ns-modal-title { color: var(--text); }
    .ns-modal-close {
        background: transparent; border: none; color: rgba(255,255,255,0.55); cursor: pointer;
    }
    :root[data-theme="light"] .ns-modal-close { color: var(--muted); }
    .ns-modal-close svg { width: 22px; height: 22px; }
    .ns-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
    @media (max-width: 520px) { .ns-modal-grid { grid-template-columns: 1fr; } }
    .ns-modal-field { display: flex; flex-direction: column; gap: 6px; }
    .ns-modal-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
    :root[data-theme="light"] .ns-modal-field label { color: var(--text); }
    .ns-modal-field input {
        padding: 11px 13px; border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
    }
    :root[data-theme="light"] .ns-modal-field input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
    .ns-modal-field input::placeholder { color: rgba(255,255,255,0.4); }
    :root[data-theme="light"] .ns-modal-field input::placeholder { color: var(--muted); }
    .ns-modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px; }

    .resumen-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px;
        align-items: stretch;
    }
    @media (max-width: 900px) { .resumen-grid { grid-template-columns: 1fr; } }
    .resumen-grid > .resumen-card { height: 100%; }
    .resumen-title {
        display: flex; align-items: center; gap: 10px;
        font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 14px;
    }
    :root[data-theme="light"] .resumen-title { color: var(--text); }
    .resumen-title svg { width: 18px; height: 18px; }
    .resumen-title--between { justify-content: space-between; }
    .resumen-count { font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 600; }
    :root[data-theme="light"] .resumen-count { color: var(--muted); }
    .resumen-detail {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 14px;
    }
    .resumen-detail:last-child { border-bottom: none; }
    .resumen-label { color: rgba(255,255,255,0.5); font-size: 11px; font-weight: 700; }
    :root[data-theme="light"] .resumen-label { color: var(--muted); }
    .resumen-value { color: #fff; font-weight: 600; text-align: right; }
    :root[data-theme="light"] .resumen-value { color: var(--text); }
    .resumen-actions {
        display: flex; align-items: center; gap: 12px;
        margin-top: 24px;
    }
    .resumen-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
        text-decoration: none; cursor: pointer; transition: all .16s ease; border: none;
    }
    .resumen-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
    .resumen-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
    :root[data-theme="light"] .resumen-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
    .resumen-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
    .resumen-btn--primary { background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
    .resumen-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }

    .ns-quote-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .ns-quote-table th {
        text-align: right; font-size: 11px; font-weight: 700;
        color: rgba(255,255,255,0.55); padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .ns-quote-table th:first-child { text-align: left; }
    :root[data-theme="light"] .ns-quote-table th { color: var(--muted); }
    .ns-quote-table td {
        padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 14px; color: #fff;
    }
    :root[data-theme="light"] .ns-quote-table td { color: var(--text); }
    .ns-quote-table td:first-child { text-align: left; }
    .ns-quote-table td:nth-child(2),
    .ns-quote-table td:nth-child(3),
    .ns-quote-table td:nth-child(4) { text-align: right; }
    .ns-quote-table tr:last-child td { border-bottom: none; }
    .ns-quote-table .ns-empty td {
        text-align: center; color: rgba(255,255,255,0.5); padding: 20px 0;
    }
    :root[data-theme="light"] .ns-quote-table .ns-empty td { color: var(--muted); }

    .ns-hidden { display: none; }
</style>