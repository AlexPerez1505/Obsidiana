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
        margin-bottom: 26px; padding-bottom: 20px;
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

    .ns-hidden { display: none; }
</style>
