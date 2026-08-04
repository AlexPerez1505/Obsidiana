<style>
    /* =========================================================
       Recursos Humanos · Estilos de la pantalla de listado
       Separado de index.blade.php para mantener el código limpio.
       ========================================================= */

    /* ===== Contenedor general: evita que se estire demasiado en pantallas grandes ===== */
    .uc-wrap {
        max-width: 1440px;
        margin: 0 auto;
    }

    /* ===== Toolbar de Control de Usuarios ===== */
    .uc-toolbar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .uc-search {
        position: relative; flex: 1 1 240px; min-width: 200px; max-width: 420px;
    }
    .uc-toolbar .uc-search input[type="text"] {
        width: 100%; padding: 12px 14px 12px 44px !important;
        border: 1.5px solid #94a3b8; border-radius: 10px;
        font-size: 14.5px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
    }
    .uc-toolbar .uc-search input[type="text"]:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(0,122,255,.16);
    }
    .uc-search svg {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        width: 18px; height: 18px; color: var(--muted); pointer-events: none;
        display: block;
    }

    /* ===== Stat cards ===== */
    .stat-row .card {
        border: 1.5px solid #94a3b8;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
        transition: border-color .15s, box-shadow .15s;
    }
    .stat-row .card:hover { border-color: var(--primary); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
    .stat-row .stat-ico {
        border: 1.5px solid rgba(0,0,0,.08);
    }
    .uc-filter {
        position: relative; flex: 0 0 auto;
    }
    .uc-filter select {
        appearance: none; -webkit-appearance: none;
        padding: 11px 34px 11px 14px;
        border: 1.5px solid #94a3b8; border-radius: 10px;
        font-size: 14px; font-family: inherit; font-weight: 600;
        background: var(--surface); color: var(--text);
        cursor: pointer; outline: none; transition: border .15s;
    }
    .uc-filter select:focus { border-color: var(--primary); }
    .uc-filter::after {
        content: ''; position: absolute; right: 14px; top: 50%;
        transform: translateY(-50%) rotate(45deg);
        width: 7px; height: 7px;
        border-right: 2px solid var(--muted); border-bottom: 2px solid var(--muted);
        pointer-events: none;
    }
    .uc-spacer { flex: 1 1 0%; min-width: 0; }
    .uc-btn-add {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        padding: 10px 18px; border: 1.5px solid rgba(255,255,255,.35); border-radius: 10px;
        background: var(--primary); color: #fff;
        font-size: 14px; font-weight: 700; cursor: pointer;
        text-decoration: none; transition: background .15s;
        box-shadow: 0 2px 0 rgba(0,0,0,.12);
        white-space: nowrap;
    }
    .uc-btn-add:hover { background: var(--primary-strong); }
    .uc-btn-add svg { width: 17px; height: 17px; flex: 0 0 auto; }
    .uc-view-toggle {
        display: inline-flex; border: 1.5px solid #94a3b8; border-radius: 10px;
        overflow: hidden; flex: 0 0 auto;
    }
    .uc-view-toggle button {
        padding: 10px 12px; border-right: 1px solid #94a3b8; border-left: none; border-top: none; border-bottom: none; background: var(--surface);
        color: var(--muted); cursor: pointer; transition: background .15s, color .15s;
        display: flex; align-items: center; justify-content: center;
    }
    .uc-view-toggle button:last-child { border-right: none; }
    .uc-view-toggle button.active {
        background: var(--primary-soft); color: var(--primary);
    }
    .uc-view-toggle button svg { width: 18px; height: 18px; }

    /* ===== Grid de tarjetas ===== */
    .uc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    .uc-grid.uc-list-view {
        grid-template-columns: 1fr;
    }

    /* ===== Tarjeta de usuario ===== */
    .uc-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.04);
        position: relative; transition: box-shadow .2s, transform .2s, border-color .15s;
        display: flex; flex-direction: column; gap: 14px;
        min-width: 0;
    }
    .uc-card:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 30px rgba(17,24,39,.10);
        transform: translateY(-2px);
    }
    .uc-card-top {
        display: flex; align-items: flex-start; gap: 14px;
    }

    /* ===== Avatar con status dot ===== */
    .uc-avatar-wrap {
        position: relative; flex: 0 0 auto;
    }
    .uc-avatar {
        width: 58px; height: 58px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 20px; overflow: hidden;
        border: 2px solid #94a3b8; flex: 0 0 auto;
    }
    .uc-avatar img {
        width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .uc-status-dot {
        position: absolute; bottom: 2px; right: 2px;
        width: 13px; height: 13px; border-radius: 50%;
        border: 3px solid var(--surface);
    }
    .uc-status-dot.green { background: #22c55e; }
    .uc-status-dot.yellow { background: #f59e0b; }
    .uc-status-dot.red { background: #ef4444; }

    /* ===== Info del usuario ===== */
    .uc-info { flex: 1; min-width: 0; }
    .uc-name {
        font-size: 15.5px; font-weight: 700; margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .uc-role {
        font-size: 12.5px; color: var(--muted); margin: 3px 0 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .uc-status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11.5px; font-weight: 600; margin-top: 6px;
    }
    .uc-status-badge .dot {
        width: 7px; height: 7px; border-radius: 50%;
    }
    .uc-status-badge .dot.green { background: #22c55e; }
    .uc-status-badge .dot.yellow { background: #f59e0b; }
    .uc-status-badge .dot.red { background: #ef4444; }
    .uc-status-badge.active { color: #22c55e; }
    .uc-status-badge.leave { color: #f59e0b; }
    .uc-status-badge.banned { color: #ef4444; }

    /* ===== Three-dots menu ===== */
    .uc-dots {
        position: absolute; bottom: 16px; right: 16px;
        width: 30px; height: 30px; border-radius: 8px;
        border: 1.5px solid #94a3b8; background: var(--surface-2); color: var(--muted);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .15s, color .15s, border-color .15s;
    }
    .uc-dots:hover { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
    .uc-dots svg { width: 17px; height: 17px; }
    .uc-dots-menu {
        position: absolute; bottom: 52px; right: 16px;
        background: var(--surface); border: 1px solid #94a3b8;
        border-radius: 12px; box-shadow: 0 10px 30px rgba(17,24,39,.14);
        padding: 6px; min-width: 180px; z-index: 20;
        opacity: 0; visibility: hidden; transform: translateY(6px) scale(.97);
        transform-origin: bottom right; pointer-events: none;
        transition: opacity .16s, transform .18s, visibility .16s;
    }
    .uc-dots-menu.open {
        opacity: 1; visibility: visible; transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    .uc-dots-menu a, .uc-dots-menu button {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px; border-radius: 8px;
        font-size: 13.5px; font-weight: 600; color: var(--text);
        text-decoration: none; border: none; background: none;
        cursor: pointer; width: 100%; text-align: left; font-family: inherit;
        transition: background .12s;
    }
    .uc-dots-menu a:hover, .uc-dots-menu button:hover {
        background: var(--surface-2);
    }
    .uc-dots-menu .danger { color: var(--danger); }
    .uc-dots-menu .ok { color: var(--green); }
    .uc-dots-menu svg { width: 16px; height: 16px; flex: 0 0 auto; }

    /* ===== Contact details ===== */
    .uc-contact {
        display: flex; flex-direction: column; gap: 5px;
        font-size: 12.5px; color: var(--muted);
        padding-top: 12px; border-top: 1px solid var(--border);
    }
    .uc-contact-row {
        display: flex; align-items: center; gap: 8px;
    }
    .uc-contact-row svg { width: 14px; height: 14px; flex: 0 0 auto; opacity: .7; }
    .uc-contact-row span {
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ===== List view ===== */
    .uc-list-view .uc-card {
        flex-direction: row; align-items: center; gap: 18px;
        padding: 14px 20px;
    }
    .uc-list-view .uc-card-top { flex: 1; min-width: 0; }
    .uc-list-view .uc-contact {
        flex-direction: row; gap: 20px; align-items: center;
        border-top: none; padding-top: 0; flex: 0 0 auto;
    }
    .uc-list-view .uc-dots { position: relative; bottom: auto; right: auto; }
    .uc-list-view .uc-dots-menu {
        position: absolute; bottom: auto; top: 100%; right: 0;
        transform-origin: top right; transform: translateY(-6px) scale(.97);
    }
    .uc-list-view .uc-dots-menu.open { transform: translateY(0) scale(1); }

    .uc-empty {
        text-align: center; padding: 60px 20px; color: var(--muted);
    }
    .uc-empty svg { width: 48px; height: 48px; margin: 0 auto 14px; opacity: .4; }
    .uc-empty p { font-size: 15px; font-weight: 600; margin: 0; }

    /* ===== Responsive ===== */
    @media (max-width: 900px) {
        .uc-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
    }

    @media (max-width: 767px) {
        .uc-toolbar { flex-direction: column; align-items: stretch; }
        .uc-search { max-width: none; width: 100%; }
        .uc-filter { flex: 1 1 auto; width: 100%; }
        .uc-filter select { width: 100%; }
        .uc-spacer { display: none; }
        .uc-btn-add { width: 100%; order: 3; }
        .uc-view-toggle { align-self: center; order: 4; }
        .uc-grid { grid-template-columns: 1fr; gap: 14px; }
        .uc-card { padding: 16px; }
        .uc-list-view .uc-card { flex-direction: column; align-items: flex-start; padding: 16px; }
        .uc-list-view .uc-contact { flex-direction: column; gap: 5px; border-top: 1px solid var(--border); padding-top: 12px; margin-top: 2px; }
        .uc-avatar { width: 52px; height: 52px; font-size: 18px; }
        .uc-dots { bottom: 14px; right: 14px; }
        .uc-dots-menu { bottom: 48px; right: 14px; min-width: 170px; }
    }
</style>
