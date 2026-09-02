<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    @stack('head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Aplica el tema guardado antes de pintar (evita parpadeo) --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <style>
        :root {
            --bg:#ffffff; --surface:#ffffff; --surface-2:#f7f8fa;
            --text:#333333; --muted:#888888; --border:#ebebeb;
            --primary:#007aff; --primary-strong:#0062cc; --primary-soft:#e6f0ff;
            --accent:#f97316; --accent-soft:#fff3e8;
            --danger:#ff4a4a; --danger-soft:#ffebeb; --green:#15803d; --green-soft:#e6ffe6;
            --shadow:0 4px 20px rgba(17,24,39,.05);
            --sidebar-bg:#ffffff; --topbar-bg:#ffffff;
            --sidebar-w:252px; --sidebar-w-collapsed:78px;
        }
        :root[data-theme="dark"] {
            --bg:#070c17; --surface:#0f1a30; --surface-2:#0c1526;
            --text:#e8eef8; --muted:#93a4bd; --border:rgba(90,140,230,.18);
            --primary:#0a84ff; --primary-strong:#007aff; --primary-soft:rgba(10,132,255,.18);
            --accent:#fb923c; --accent-soft:rgba(249,115,22,.12);
            --danger:#f87171; --danger-soft:rgba(220,38,38,.12); --green:#4ade80; --green-soft:rgba(22,163,74,.14);
            --shadow:0 6px 26px rgba(0,0,0,.45);
            --sidebar-bg:#0a1223; --topbar-bg:#0a1223;
        }
        * { box-sizing:border-box; }
        html, body { margin:0; padding:0; }
        body { font-family:'Quicksand',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
               background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; }

        /* ===== Estructura ===== */
        .app { display:grid; grid-template-columns:var(--sidebar-w) 1fr; min-height:100vh;
               transition:grid-template-columns .34s cubic-bezier(.4,0,.2,1); }
        .app.no-anim, .app.no-anim .brand-text, .app.no-anim .nav-label,
        .app.no-anim .help-text, .app.no-anim .collapse-btn svg { transition:none !important; }
        .app.collapsed { grid-template-columns:var(--sidebar-w-collapsed) 1fr; }

        /* ===== Sidebar ===== */
        .sidebar { background:var(--sidebar-bg);
                   box-shadow:1px 0 3px rgba(17,24,39,.05), 1px 0 1px rgba(17,24,39,.03);
                   display:flex; flex-direction:column; padding:20px 16px; position:sticky; top:0; height:100vh;
                   z-index:40; }
        .app.collapsed .sidebar { padding-left:12px; padding-right:12px; }

        .brand { display:flex; align-items:center; gap:11px; padding:4px 6px 20px; min-height:56px;
                 transition:justify-content .3s, padding .3s; }
        .brand-logo { width:44px; height:44px; flex:0 0 auto;
                      display:flex; align-items:center; justify-content:center; color:var(--primary); }
        .brand-logo img { width:100%; height:100%; object-fit:contain; display:block; }
        .brand-text { min-width:0; overflow:hidden; opacity:1; transform:translateX(0);
                      transition:opacity .22s ease .06s, transform .28s cubic-bezier(.4,0,.2,1); }
        .brand-name { font-weight:700; letter-spacing:-.01em; font-size:19px; line-height:1.1; white-space:nowrap; }
        .brand-sub { font-size:8.5px; letter-spacing:.06em; text-transform:uppercase; color:var(--muted);
                     margin-top:3px; white-space:nowrap; }
        .app.collapsed .brand { justify-content:center; gap:0; padding-left:0; padding-right:0; }
        .app.collapsed .brand-logo { width:50px; height:50px; margin:0 auto; }
        .app.collapsed .brand-text { opacity:0; transform:translateX(-8px); width:0;
                                     margin:0; padding:0; pointer-events:none; }

        /* Botón contraer: flotante, minimalista, gira según el estado */
        .collapse-btn { position:absolute; top:30px; right:-13px; width:26px; height:26px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; z-index:60;
            border:1px solid var(--border); background:var(--surface); color:var(--muted);
            cursor:pointer; box-shadow:0 2px 8px rgba(17,24,39,.10);
            transition:transform .2s ease, color .2s ease, background .2s ease, box-shadow .2s ease; }
        .collapse-btn:hover { color:var(--text); background:var(--surface-2); transform:scale(1.08); }
        .collapse-btn:active { transform:scale(.94); }
        .collapse-btn svg { width:15px; height:15px; transition:transform .34s cubic-bezier(.4,0,.2,1); }
        .app.collapsed .collapse-btn svg { transform:rotate(180deg); }
        .app.collapsed .collapse-btn span { display:none; }

        .nav { display:flex; flex-direction:column; gap:4px; margin-top:6px; }
        .nav-item { position:relative; display:flex; align-items:center; gap:13px; padding:11px 13px; border-radius:12px;
                    color:var(--muted); text-decoration:none; font-weight:600; font-size:14.5px; white-space:nowrap;
                    transition:background .16s ease, color .16s ease; }
        .nav-item:hover { background:var(--surface-2); color:var(--text); }
        .nav-item.active { background:linear-gradient(135deg,var(--primary-strong),var(--primary));
                           color:#fff; box-shadow:0 8px 18px rgba(0,122,255,.28); }
        .nav-item svg { width:20px; height:20px; flex:0 0 auto; }
        .nav-label { display:inline-block; overflow:hidden; white-space:nowrap; transition:opacity .2s ease .05s; }
        .app.collapsed .nav-item { justify-content:center; gap:0; }
        .app.collapsed .nav-label { opacity:0; width:0; }

        /* Tooltip elegante al pasar el mouse con el menú contraído */
        .app.collapsed .nav-item::after { content:attr(data-tip); position:absolute; left:calc(100% + 16px); top:50%;
            transform:translateY(-50%) translateX(-4px); white-space:nowrap; background:var(--surface); color:var(--text);
            border:1px solid var(--border); padding:7px 11px; border-radius:10px; font-size:13px; font-weight:600;
            box-shadow:0 10px 28px rgba(17,24,39,.16); opacity:0; pointer-events:none; z-index:70;
            transition:opacity .16s ease, transform .18s cubic-bezier(.4,0,.2,1); }
        .app.collapsed .nav-item::before { content:""; position:absolute; left:calc(100% + 10px); top:50%;
            transform:translateY(-50%) rotate(45deg); width:9px; height:9px; background:var(--surface);
            border-left:1px solid var(--border); border-bottom:1px solid var(--border);
            opacity:0; pointer-events:none; z-index:70; transition:opacity .16s ease; }
        .app.collapsed .nav-item:hover::after { opacity:1; transform:translateY(-50%) translateX(0); }
        .app.collapsed .nav-item:hover::before { opacity:1; }

        .sidebar-foot { margin-top:auto; }
        .help-box { background:var(--surface-2); border:1px solid var(--border); border-radius:14px;
                    padding:16px; text-align:center; transition:padding .3s ease; }
        .help-ico { width:44px; height:44px; border-radius:50%; background:var(--primary-soft); color:var(--primary);
                    display:flex; align-items:center; justify-content:center; margin:0 auto 8px;
                    transition:margin .3s ease; }
        .help-box b { display:block; font-size:14px; }
        .help-box small { color:var(--muted); font-size:12px; }
        .help-text { overflow:hidden; transition:opacity .2s ease; }
        .app.collapsed .help-text { opacity:0; height:0; margin:0; }
        .app.collapsed .help-box { padding:11px; }
        .app.collapsed .help-ico { margin-bottom:0; }

        /* ===== Área principal ===== */
        .main { display:flex; flex-direction:column; min-width:0; }
        /* Sin borde: la separacion con el contenido la da una sombra muy
           tenue, que ademas solo tiene sentido hacia abajo. */
        .topbar { position:sticky; top:0; z-index:30; display:flex; align-items:center; gap:16px;
                  padding:16px 26px; background:var(--topbar-bg);
                  box-shadow:0 1px 3px rgba(17,24,39,.05), 0 1px 1px rgba(17,24,39,.03); }
        .page-title { font-size:25px; font-weight:700; margin:0; line-height:1.15; letter-spacing:-.01em; }
        .page-sub { color:var(--muted); font-size:14px; margin:2px 0 0; }
        .topbar-spacer { flex:1; }
        /* Fila de acciones de la pantalla, dentro del contenido y a la derecha. */
        .content-actions { display:flex; align-items:center; justify-content:flex-end;
                           gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .content-actions .btn { display:inline-flex; align-items:center; gap:6px; }
        /* Hamburguesa: solo en móvil (en escritorio ya existe el botón "Contraer" del sidebar) */
        .hamburger { display:none; align-items:center; justify-content:center; width:42px; height:42px;
                     border:1px solid var(--border); background:var(--surface); color:var(--text);
                     border-radius:11px; cursor:pointer; flex:0 0 auto; }
        .hamburger:hover { background:var(--surface-2); }

        .icon-btn { position:relative; width:42px; height:42px; border:none; background:transparent;
                    color:var(--muted); border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;
                    transition:background .18s ease, color .18s ease; }
        .icon-btn:hover { background:var(--surface-2); color:var(--text); }
        .icon-btn svg { width:19px; height:19px; }
        .badge-dot { position:absolute; top:-5px; right:-5px; min-width:18px; height:18px; padding:0 5px;
                     background:var(--danger); color:#fff; border-radius:999px; font-size:11px; font-weight:700;
                     display:flex; align-items:center; justify-content:center; }

        /* Toggle de tema: luna en claro, sol en oscuro */
        #theme-toggle .ico-sun { display:none; }
        :root[data-theme="dark"] #theme-toggle .ico-sun { display:block; }
        :root[data-theme="dark"] #theme-toggle .ico-moon { display:none; }

        .user-btn { display:flex; align-items:center; gap:10px; border:1px solid var(--border); background:var(--surface);
                    padding:6px 10px 6px 6px; border-radius:999px; cursor:pointer; color:var(--text);
                    transition:background .18s ease, border-color .18s ease, box-shadow .18s ease; }
        .user-btn:hover { background:var(--surface-2); box-shadow:0 2px 10px rgba(17,24,39,.06); }
        .dd.open .user-btn { background:var(--surface-2); border-color:var(--primary); }
        .user-btn .chev { color:var(--muted); transition:transform .24s cubic-bezier(.4,0,.2,1); }
        .dd.open .user-btn .chev { transform:rotate(180deg); }
        .avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#4da3ff,var(--primary));
                  color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px;
                  flex:0 0 auto; }
        .user-name { font-weight:600; font-size:14px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        /* ===== Dropdowns ===== */
        .dd { position:relative; }
        .dd-panel { position:absolute; right:0; top:calc(100% + 10px); width:290px; background:var(--surface);
                    border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow); padding:8px;
                    z-index:50; opacity:0; visibility:hidden; transform:translateY(-8px) scale(.97);
                    transform-origin:top right; pointer-events:none;
                    transition:opacity .18s ease, transform .22s cubic-bezier(.4,0,.2,1), visibility .18s; }
        .dd.open .dd-panel { opacity:1; visibility:visible; transform:translateY(0) scale(1); pointer-events:auto; }
        .dd-head { padding:12px 12px 10px; }
        .dd-head b { font-size:15px; }
        .dd-head small { display:block; color:var(--muted); font-size:12.5px; margin-top:2px; }
        .dd-sep { height:1px; background:var(--border); margin:4px 6px 6px; }
        .dd-item { display:flex; gap:12px; align-items:center; padding:10px 12px; border-radius:12px;
                   text-decoration:none; color:var(--text); width:100%; border:none; background:none; cursor:pointer;
                   text-align:left; font-family:inherit; transition:background .14s ease; }
        .dd-item:hover { background:var(--surface-2); }
        .dd-item .di-ico { width:36px; height:36px; border-radius:10px; background:var(--primary-soft); color:var(--primary);
                           display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .dd-item.danger .di-ico { background:var(--danger-soft); color:var(--danger); }
        .dd-item.danger b { color:var(--danger); }
        .dd-item b { font-size:14.5px; }
        .dd-item small { display:block; color:var(--muted); font-size:12.5px; }
        .dd-notif { max-height:340px; overflow-y:auto; }
        .dd-empty { padding:26px 12px; text-align:center; color:var(--muted); font-size:14px; }

        /* ===== Contenido ===== */
        .content { padding:26px; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:10px;
                padding:18px; }
        .grid { display:grid; gap:18px; }
        .stat-row { grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
        .stat { display:flex; align-items:center; gap:14px; }
        .stat-ico { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .stat-ico svg { width:20px; height:20px; }
        .stat-ico.blue { background:var(--primary-soft); color:var(--primary); }
        .stat-ico.orange { background:var(--accent-soft); color:var(--accent); }
        .stat-ico.green { background:var(--green-soft); color:var(--green); }
        .stat-num { font-size:26px; font-weight:600; line-height:1.1; letter-spacing:-.02em; }
        .stat-lbl { color:var(--muted); font-size:13px; margin-top:2px; }

        /* Tarjeta con acento superior: da color sin invadir el fondo */
        .card--accent { position:relative; overflow:hidden; }
        .card--accent::before { content:""; position:absolute; top:0; left:0; right:0; height:2px; background:var(--primary); }
        .card--accent.is-green::before { background:var(--green); }
        .card--accent.is-amber::before { background:var(--accent); }
        .card--accent.is-danger::before { background:var(--danger); }

        /* Encabezado de una tarjeta con titulo y accion */
        .card-head { display:flex; align-items:center; gap:10px; padding:14px 18px;
                     border-bottom:1px solid var(--border); }
        .card-head h3 { margin:0; font-size:14px; font-weight:600; letter-spacing:-.01em; }
        .card-head .right { margin-left:auto; display:flex; align-items:center; gap:8px; }

        /* ===== Cambio de vista: lista o tarjetas ===== */
        .view-switch { display:inline-flex; border:1px solid var(--border); border-radius:7px; overflow:hidden; }
        .view-switch button { width:34px; height:34px; border:none; background:var(--surface); color:var(--muted);
                              cursor:pointer; display:inline-flex; align-items:center; justify-content:center;
                              transition:background .15s ease, color .15s ease; }
        .view-switch button + button { border-left:1px solid var(--border); }
        .view-switch button:hover { color:var(--text); }
        .view-switch button.active { background:var(--surface-2); color:var(--primary); }
        .view-switch svg { width:16px; height:16px; }

        /* Rejilla de tarjetas equivalente a una tabla */
        .data-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(290px,1fr)); gap:14px; }
        .data-card { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:18px;
                     transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .data-card:hover { border-color:var(--primary); transform:translateY(-2px);
                           box-shadow:0 6px 16px rgba(17,24,39,.07); }
        .data-card-top { display:flex; align-items:center; gap:11px; padding-bottom:14px;
                         border-bottom:1px solid var(--border); }
        .data-card-top .t { font-weight:600; line-height:1.3; }
        .data-card-top .s { color:var(--muted); font-size:12.5px; margin-top:1px; }
        .data-card-top .right { margin-left:auto; }
        .data-card dl { margin:14px 0 0; display:grid; gap:9px; }
        .data-card dl > div { display:flex; justify-content:space-between; gap:12px; align-items:baseline; }
        .data-card dt { color:var(--muted); font-size:13px; }
        .data-card dd { margin:0; font-size:13px; font-weight:500; text-align:right; overflow-wrap:anywhere; }
        .data-card-foot { margin-top:16px; padding-top:13px; border-top:1px solid var(--border); text-align:right; }
        [data-theme="dark"] .data-card:hover { box-shadow:0 6px 18px rgba(0,0,0,.4); }

        .tbl-link { color:var(--primary); text-decoration:none; font-size:13px; font-weight:500; }
        .tbl-link:hover { text-decoration:underline; }
        .tbl-link + .tbl-link { margin-left:14px; }

        @media (prefers-reduced-motion:reduce) {
            .view-switch button, .data-card { transition:none; }
        }

        /* Estado vacio con caracter */
        .empty-state { text-align:center; padding:44px 20px; }
        .empty-state .ico { width:46px; height:46px; margin:0 auto 14px; border-radius:12px;
                            display:flex; align-items:center; justify-content:center;
                            background:var(--surface-2); color:var(--muted); }
        .empty-state .ico svg { width:22px; height:22px; }
        .empty-state h3 { margin:0 0 5px; font-size:15px; font-weight:600; }
        .empty-state p { margin:0 0 18px; color:var(--muted); font-size:14px; }

        h2.section-title { font-size:17px; margin:0 0 4px; }

        /* ===== Cabecera de pagina (componente x-ui.page-header) =====
           Minimalista: sin tarjeta, sin icono, sin subtitulo. */
        .page-header { margin-bottom:22px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .page-header-title { margin:0; font-size:18px; font-weight:600; letter-spacing:-.01em;
                             line-height:1.2; color:var(--text); min-width:0; }
        .page-header-count { font-size:12px; font-weight:500; color:var(--muted);
                             background:var(--surface-2); border:1px solid var(--border);
                             padding:1px 7px; border-radius:5px; }
        .page-header-back { width:34px; height:34px; border-radius:9px; flex:0 0 auto; display:inline-flex;
                            align-items:center; justify-content:center; color:var(--muted); text-decoration:none;
                            transition:background .15s ease, color .15s ease, transform .1s ease; }
        .page-header-back:hover { background:var(--surface-2); color:var(--text); }
        .page-header-back:active { transform:scale(.92); }
        .page-header-back svg { width:18px; height:18px; }
        .page-header-actions { margin-left:auto; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .page-header-actions .btn { display:inline-flex; align-items:center; gap:6px; }

        /* Accion secundaria sin peso visual: mismo lenguaje que la flecha
           de regresar, para que no compita con la accion principal. */
        .btn-icono { width:34px; height:34px; border-radius:9px; flex:0 0 auto; display:inline-flex;
                     align-items:center; justify-content:center; border:1px solid transparent;
                     background:transparent; color:var(--muted); text-decoration:none; cursor:pointer;
                     transition:background .15s ease, color .15s ease, transform .1s ease; }
        .btn-icono:hover { background:var(--surface-2); color:var(--text); }
        .btn-icono:active { transform:scale(.92); }
        .btn-icono svg { width:17px; height:17px; }

        /* Dato accionable (un saldo, un contador): se lee como informacion,
           no como boton, pero lleva a su propia pantalla. */
        .pill-dato { display:inline-flex; align-items:center; gap:8px; padding:7px 13px 7px 11px;
                     border:1px solid var(--border); border-radius:8px; background:var(--surface);
                     color:var(--text); font-size:13.5px; line-height:1.5; text-decoration:none;
                     transition:background .16s ease, border-color .16s ease; }
        .pill-dato:hover { background:var(--surface-2); border-color:var(--muted); }
        .pill-dato .punto { width:7px; height:7px; border-radius:50%; background:var(--muted); flex:0 0 7px; }
        .pill-dato .et { color:var(--muted); }
        .pill-dato .val { font-weight:600; font-variant-numeric:tabular-nums; }
        .pill-dato.es-pagado .punto { background:var(--green); }
        .pill-dato.es-vencido .punto { background:var(--danger); }
        .pill-dato.es-parcial .punto { background:var(--accent, #f59e0b); }

        /* Pie de acciones pegado al fondo, para formularios largos */
        .page-foot { position:sticky; bottom:0; z-index:20; display:flex; justify-content:flex-end;
                     align-items:center; gap:10px; margin-top:22px; padding:14px 0;
                     border-top:1px solid var(--border);
                     background:color-mix(in srgb, var(--bg) 88%, transparent);
                     backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
        .page-foot .btn { display:inline-flex; align-items:center; gap:6px; }
        /* Si el formulario esta en rejilla, el pie ocupa todo el ancho. */
        .rgrid-sidebar > .page-foot, .rgrid-2 > .page-foot { grid-column:1 / -1; }

        @media (max-width:640px) {
            .page-header { align-items:flex-start; }
            .page-header-actions { width:100%; }
            .page-header-actions .btn { flex:1; justify-content:center; }
            /* El icono conserva su tamaño; solo se estiran los que llevan texto. */
            .page-header-actions .btn-icono { flex:0 0 34px; }
            .page-header-actions .pill-dato { flex:1; justify-content:center; }
        }
        @media (prefers-reduced-motion: reduce) { .page-header-back { transition:none; } }

        /* ===== Utilidades (compatibles con las vistas existentes) ===== */
        label { display:block; font-size:13px; font-weight:600; margin:14px 0 6px; }
        /* Controles de formulario: mismo aspecto sin repetir estilos en cada vista. */
        input[type=text], input[type=email], input[type=password], input[type=tel],
        input[type=number], input[type=date], input[type=search], select, textarea {
            width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:7px; font-size:14px;
            font-family:inherit; outline:none; background:var(--surface); color:var(--text);
            transition:border-color .15s ease; }
        select { cursor:pointer; }
        textarea { resize:vertical; }
        input:hover, select:hover, textarea:hover { border-color:var(--muted); }
        input:focus, select:focus, textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-soft); }
        input::placeholder, textarea::placeholder { color:var(--muted); }
        .btn { display:inline-block; padding:8px 14px; border:1px solid var(--primary); border-radius:7px; background:var(--primary);
               color:#fff; font-size:13.5px; font-weight:500; line-height:1.5; cursor:pointer; text-decoration:none; text-align:center;
               transition:background .16s ease, border-color .16s ease; }
        .btn:hover { background:var(--primary-strong); border-color:var(--primary-strong); }
        .btn--ghost { background:var(--surface); color:var(--text); border:1px solid var(--border); }
        .btn--ghost:hover { background:var(--surface-2); }

        /* En oscuro el azul saturado vibra sobre el fondo casi negro:
           se apaga un poco y el texto pasa a blanco puro para mantener contraste. */
        [data-theme="dark"] .btn, [data-theme="dark"] .erp-btn {
            background:#2563eb; border-color:#2563eb; color:#f8fafc; }
        [data-theme="dark"] .btn:hover, [data-theme="dark"] .erp-btn:hover {
            background:#1d4ed8; border-color:#1d4ed8; }
        [data-theme="dark"] .btn--ghost, [data-theme="dark"] .erp-btn.ghost {
            background:var(--surface-2); color:var(--text); border-color:var(--border); }
        [data-theme="dark"] .btn--ghost:hover, [data-theme="dark"] .erp-btn.ghost:hover {
            background:var(--surface); border-color:var(--muted); }
        [data-theme="dark"] .btn--danger, [data-theme="dark"] .erp-btn.danger {
            background:#dc2626; border-color:#dc2626; color:#fff; }

        /* En oscuro una sombra negra no se ve: la separacion se logra
           oscureciendo el borde de la sombra en vez de aclararlo. */
        [data-theme="dark"] .topbar { box-shadow:0 1px 0 rgba(255,255,255,.06), 0 2px 8px rgba(0,0,0,.4); }
        [data-theme="dark"] .sidebar { box-shadow:1px 0 0 rgba(255,255,255,.06), 2px 0 8px rgba(0,0,0,.4); }
        .btn--danger { background:var(--danger); }
        .btn--danger:hover { filter:brightness(.92); }
        .btn--block { display:block; width:100%; }
        .link { color:var(--primary); text-decoration:none; font-weight:600; }
        .link:hover { text-decoration:underline; }
        .muted { color:var(--muted); font-size:14px; }
        .err { color:var(--danger); font-size:13px; margin:6px 0 0; }
        .alert { padding:11px 14px; border-radius:10px; font-size:14px; margin-bottom:16px; }
        .alert--ok { background:var(--green-soft); color:var(--green); border:1px solid var(--border); }
        .alert--err { background:var(--danger-soft); color:var(--danger); border:1px solid var(--border); }
        .section { border-top:1px solid var(--border); margin-top:26px; padding-top:20px; }
        .badge { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:6px;
                 font-size:12px; font-weight:500; background:var(--surface-2); color:var(--muted);
                 border:1px solid var(--border); }
        .badge::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; flex:0 0 6px; }
        .badge--plain::before { display:none; }
        .badge--ok { background:var(--green-soft); color:var(--green); border-color:transparent; }
        .badge--warn { background:var(--accent-soft); color:var(--accent); border-color:transparent; }
        .badge--info { background:var(--primary-soft); color:var(--primary); border-color:transparent; }
        .badge--danger { background:var(--danger-soft); color:var(--danger); border-color:transparent; }
        .danger-box { border:1px solid var(--border); background:var(--danger-soft); border-radius:14px; padding:18px; margin-top:12px; }
        /* ===== Tablas ===== */
        table { width:100%; border-collapse:collapse; font-size:14px; }
        thead th { background:var(--surface-2); }
        th, td { text-align:left; padding:13px 16px; border-bottom:1px solid var(--border); }
        th { color:var(--muted); font-weight:500; font-size:12px; letter-spacing:.02em;
             white-space:nowrap; border-bottom:1px solid var(--border); }
        thead th:first-child { border-top-left-radius:9px; }
        thead th:last-child { border-top-right-radius:9px; }
        tbody tr { transition:background .15s ease; }
        tbody tr:hover td { background:var(--surface-2); }
        tbody tr:last-child td { border-bottom:none; }
        td.num, th.num { text-align:right; font-variant-numeric:tabular-nums; }

        /* Celda de identidad: avatar + nombre + dato secundario */
        .cell-id { display:flex; align-items:center; gap:11px; min-width:0; }
        .cell-id .t { font-weight:600; color:var(--text); line-height:1.3; }
        .cell-id .s { color:var(--muted); font-size:12.5px; margin-top:1px; }

        /* Avatar con tinte suave; el color lo pone la vista con .a1 .. .a5 */
        .avatar { width:36px; height:36px; flex:0 0 36px; border-radius:9px; display:inline-flex;
                  align-items:center; justify-content:center; font-size:12.5px; font-weight:600;
                  background:var(--primary-soft); color:var(--primary); }
        .avatar.a1 { background:var(--primary-soft); color:var(--primary); }
        .avatar.a2 { background:var(--green-soft); color:var(--green); }
        .avatar.a3 { background:var(--accent-soft); color:var(--accent); }
        .avatar.a4 { background:var(--danger-soft); color:var(--danger); }
        .avatar.a5 { background:var(--surface-2); color:var(--muted); }

        /* Botón ojo para contraseñas */
        .pw-wrap { position:relative; display:block; }
        .pw-wrap input { padding-right:44px; }
        .pw-toggle { position:absolute; right:6px; top:50%; transform:translateY(-50%); background:none; border:none;
                     cursor:pointer; padding:6px; line-height:0; color:var(--muted); border-radius:6px; }
        .pw-toggle:hover { color:var(--text); background:var(--surface-2); }
        .pw-toggle svg { width:20px; height:20px; display:block; }
        .pw-toggle .icon-off { display:none; }
        .pw-toggle.is-visible .icon-on { display:none; }
        .pw-toggle.is-visible .icon-off { display:block; }

        .overlay { display:none; position:fixed; inset:0; background:rgba(2,6,23,.5); z-index:40; }

        /* ===== Toast moderno (arriba a la derecha) ===== */
        .toast{ position:fixed; top:22px; right:22px; left:auto; bottom:auto; z-index:9999;
                display:flex; align-items:center; gap:12px; padding:13px 16px 13px 13px; border-radius:15px;
                background:var(--surface); color:var(--text); border:1px solid var(--border);
                box-shadow:0 16px 44px rgba(17,24,39,.16); opacity:0; pointer-events:none;
                transform:translateX(120%); transition:opacity .3s ease, transform .34s cubic-bezier(.22,1,.36,1);
                font-size:14px; font-weight:600; max-width:min(360px, calc(100% - 44px)); }
        .toast.show{ opacity:1; transform:translateX(0); }
        .toast .toast-ico{ width:34px; height:34px; border-radius:10px; flex:0 0 auto;
                display:flex; align-items:center; justify-content:center;
                background:var(--green-soft); color:var(--green); }
        .toast .toast-ico.warn{ background:var(--danger-soft); color:var(--danger); }
        .toast .toast-ico svg{ width:19px; height:19px; }
        .toast #appToastMsg{ line-height:1.35; }
        .toast b{ font-weight:800; }
        @media (max-width:640px){ .toast{ top:14px; right:14px; left:14px; max-width:none; } }

        .nav-group { display:flex; flex-direction:column; gap:4px; }
        .nav-toggle { cursor:pointer; }
        .nav-chev { width:16px; height:16px; margin-left:auto; transition:transform .2s ease; flex:0 0 auto; pointer-events:none; }
        .nav-group.open .nav-chev { transform:rotate(180deg); }
        .submenu { display:none; flex-direction:column; gap:4px; padding-left:14px; margin-left:20px; border-left:2px solid var(--border); }
        .nav-group.open .submenu { display:flex; }
        .submenu .nav-item { padding:9px 12px; font-size:13.5px; }
        .nav-sub { gap:10px; position:relative; }
        .submenu .nav-bullet { width:6px; height:6px; color:var(--muted); flex:0 0 auto; }
        .submenu-label { font-size:10.5px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); padding:8px 12px 2px; }
        .nav-count { margin-left:auto; background:var(--primary); color:#fff; font-size:11px; font-weight:800; min-width:20px; height:20px; padding:0 6px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
        /* Bloques del menu: parten la lista en secciones para que no se lea
           como una sola columna larga de ocho apartados. */
        .nav-section { padding:15px 13px 5px; font-size:10.5px; font-weight:800; letter-spacing:.07em;
                       text-transform:uppercase; color:var(--muted); white-space:nowrap; }
        .nav-section:first-child { padding-top:4px; }
        /* Contraido el texto no cabe: el bloque se marca con una linea fina. */
        .app.collapsed .nav-section { padding:0; height:1px; margin:10px 12px; overflow:hidden;
                                      color:transparent; background:var(--border); }

        /* ===== Menu contraido: los grupos abren en un panel flotante =====
           Antes el submenu se ocultaba a la fuerza, asi que al hacer clic en
           un icono no pasaba absolutamente nada. */
        .app.collapsed .nav-chev { display:none; }
        /* El panel ya lleva el nombre del apartado: el tooltip sobraria. */
        .app.collapsed .nav-toggle::after, .app.collapsed .nav-toggle::before { display:none; }

        .app.collapsed .nav-group .submenu {
            display:flex; position:fixed; z-index:80; min-width:214px; padding:6px;
            margin:0; border:1px solid var(--border); border-radius:12px; background:var(--surface);
            box-shadow:0 14px 40px rgba(17,24,39,.16);
            opacity:0; transform:translateX(-6px); pointer-events:none;
            transition:opacity .14s ease, transform .16s cubic-bezier(.4,0,.2,1); }
        .app.collapsed .nav-group[data-flotante] .submenu {
            opacity:1; transform:translateX(0); pointer-events:auto; }
        .app.collapsed .submenu::before {
            content:attr(data-nombre); display:block; padding:7px 11px 8px; margin-bottom:5px;
            font-size:10.5px; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
            color:var(--muted); border-bottom:1px solid var(--border); }
        .app.collapsed .submenu .nav-item { justify-content:flex-start; gap:10px; padding:8px 11px; }
        .app.collapsed .submenu .nav-label { opacity:1; width:auto; }
        /* Dentro del panel cada opcion ya se lee: no hacen falta tooltips. */
        .app.collapsed .submenu .nav-item::after, .app.collapsed .submenu .nav-item::before { display:none; }

        /* ===== Responsive ===== */
        @media (max-width:1024px) {
            .hamburger { display:flex; }
            .app { grid-template-columns:1fr; }
            .sidebar { position:fixed; left:0; top:0; z-index:60; width:var(--sidebar-w);
                       transform:translateX(-100%); transition:transform .25s; }
            .app.sidebar-open .sidebar { transform:translateX(0); }
            .app.sidebar-open .overlay { display:block; }
            .app.collapsed { grid-template-columns:1fr; }
            .app.collapsed .brand-text, .app.collapsed .nav-label, .app.collapsed .help-text {
                opacity:1; width:auto; height:auto; transform:none; margin:initial; }
            .app.collapsed .nav-item { justify-content:flex-start; gap:13px; }
            .app.collapsed .brand { justify-content:flex-start; padding:4px 6px 20px; }
            /* El cajón móvil se ve completo: se deshace todo lo de contraído.
               Sin esto, quien dejó el menú contraído en escritorio abría el
               cajón en el teléfono y los submenús no respondían. */
            .app.collapsed .nav-group .submenu {
                display:none; position:static; min-width:0; padding:0 0 0 14px; margin-left:20px;
                border:0; border-left:2px solid var(--border); border-radius:0; background:none;
                box-shadow:none; opacity:1; transform:none; pointer-events:auto; }
            .app.collapsed .nav-group.open .submenu { display:flex; }
            .app.collapsed .submenu::before { display:none; }
            .app.collapsed .nav-chev { display:block; }
            .app.collapsed .nav-section { padding:15px 13px 5px; height:auto; margin:0;
                                          color:var(--muted); background:none; }
            /* En móvil el sidebar se abre/cierra con la hamburguesa; ocultamos el botón flotante */
            .collapse-btn { display:none; }
        }
        @media (max-width:640px) {
            .content { padding:16px; }
            .user-name { display:none; }

            /* ===== Cabecera en una sola línea =====
               El título competía por el ancho con los cuatro controles y
               terminaba partido en cinco renglones. Ahora ocupa lo que sobra
               y lo que no cabe se corta con puntos suspensivos. */
            .topbar { padding:10px 14px; gap:8px; }
            .topbar-titulo { flex:1; min-width:0; }
            .page-title { font-size:17px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
            .page-sub { display:none; }
            .topbar-spacer { display:none; }
            .icon-btn { width:36px; height:36px; }
            .icon-btn svg { width:18px; height:18px; }
            .hamburger { width:38px; height:38px; }
            .user-btn { padding:0; }
            .user-btn .chev { display:none; }

            /* Acciones de pantalla: a lo ancho, no apretadas en una esquina. */
            .content-actions { gap:8px; }
            .content-actions .btn { flex:1; justify-content:center; }

            /* Pie de formulario: el botón principal arriba y al alcance del pulgar. */
            .page-foot { flex-direction:column-reverse; align-items:stretch;
                         gap:8px; padding:12px 0 14px; }
            .page-foot .btn { width:100%; justify-content:center; }
        }

        /* Variantes de un mismo texto segun el ancho de la pantalla. */
        .solo-angosto { display:none; }
        @media (max-width:640px) {
            .solo-ancho { display:none; }
            .solo-angosto { display:inline; }
        }
    </style>
</head>
<body>
@php
    $h = now()->hour;
    $saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
    $u = auth()->user();
    $initials = collect(explode(' ', trim($u->name)))->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
    $primerNombre = collect(explode(' ', trim($u->name)))->filter()->first() ?: $u->name;
@endphp
<div class="app" id="app">
    <div class="overlay" id="overlay"></div>

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-logo">
                {{-- Icono del logo (public/images/logo-icon.png). Si no existe, usa el ícono por defecto. --}}
                <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('app.name') }}"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/></svg>
            </div>
            <div class="brand-text">
                <div class="brand-name">{{ config('app.name') }}</div>
                <div class="brand-sub">Plataforma empresarial</div>
            </div>
        </div>

        <button class="collapse-btn" id="collapse-btn" type="button" aria-label="Contraer menú" title="Contraer menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" data-tip="Dashboard">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>
            <div class="nav-section">Operación</div>
            <div class="nav-group {{ request()->routeIs('commercial.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión Comercial">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/><path d="M20 20c0-2.5-1.3-4.2-3.5-5"/></svg>
                    <span class="nav-label">Gestión Comercial</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.clientes.index') ? 'active' : '' }}" href="{{ route('commercial.clientes.index') }}" data-tip="Clientes">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Clientes</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.cotizaciones.*') ? 'active' : '' }}" href="{{ route('commercial.cotizaciones.index') }}" data-tip="Cotizaciones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Cotizaciones</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.ventas.*') ? 'active' : '' }}" href="{{ route('commercial.ventas.index') }}" data-tip="Ventas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Ventas</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.cobranza.*') ? 'active' : '' }}" href="{{ route('commercial.cobranza.index') }}" data-tip="Cobranza">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Cobranza</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('commercial.facturas.*') ? 'active' : '' }}" href="{{ route('commercial.facturas.index') }}" data-tip="Facturación">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Facturación</span>
                    </a>
                    <a class="nav-item nav-sub" href="#" data-tip="Promociones">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Promociones</span>
                    </a>
                </div>
            </div>
            <div class="nav-group {{ request()->routeIs('inventory.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión de Inventario">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span class="nav-label">Gestión de Inventario</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.movimientos.*') ? 'active' : '' }}" href="{{ route('inventory.movimientos.index') }}" data-tip="Entrada / Salida">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Entrada / Salida</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.equipos.*') ? 'active' : '' }}" href="{{ route('inventory.equipos.index') }}" data-tip="Equipos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Equipos</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.productos.*') ? 'active' : '' }}" href="{{ route('inventory.productos.index') }}" data-tip="Productos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Productos</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('inventory.fichas.*') ? 'active' : '' }}" href="{{ route('inventory.fichas.index') }}" data-tip="Fichas técnicas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Fichas técnicas</span>
                    </a>
                    <a class="nav-item nav-sub" href="#" data-tip="Stock">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Stock</span>
                    </a>
                </div>
            </div>
            <a class="nav-item" href="#" data-tip="Gestión de Servicios">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                <span class="nav-label">Gestión de Servicios</span>
            </a>
            <div class="nav-section">Administración</div>
            <div class="nav-group {{ request()->routeIs('admin.*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión Administrativa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    <span class="nav-label">Gestión Administrativa</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.agenda.index') ? 'active' : '' }}" href="{{ route('admin.agenda.index') }}" data-tip="Agenda">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Agenda</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" data-tip="Usuarios">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Usuarios</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.vehicles.*') ? 'active' : '' }}" href="{{ route('admin.vehicles.index') }}" data-tip="Vehículos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Vehículos</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.viatics.*') ? 'active' : '' }}" href="{{ route('admin.viatics.index') }}" data-tip="Viáticos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Viáticos</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.materials.*') ? 'active' : '' }}" href="{{ route('admin.materials.index') }}" data-tip="Materiales">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Materiales</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}" data-tip="Reportes">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Reportes</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}" data-tip="Permisos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Permisos</span>
                    </a>
                </div>
            </div>
            <div class="nav-group {{ request()->is('structure/marketing*') ? 'open' : '' }}">
                <a class="nav-item nav-toggle" href="#" data-tip="Gestión de Marketing">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    <span class="nav-label">Gestión de Marketing</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub" href="#" data-tip="Inicio">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Inicio</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.guia_de_marca.index') ? 'active' : '' }}" href="{{ route('marketing.guia_de_marca.index') }}" data-tip="Guía de marca">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Guía de marca</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.agenda.index') ? 'active' : '' }}" href="{{ route('marketing.agenda.index') }}" data-tip="Calendario">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Calendario</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.aprobacion_flyers.index') ? 'active' : '' }}" href="{{ route('marketing.aprobacion_flyers.index') }}" data-tip="Aprobación de flyers">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Aprobación de flyers</span>
                    </a>
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.biblioteca_catalogo.index') ? 'active' : '' }}" href="{{ route('marketing.biblioteca_catalogo.index') }}" data-tip="Biblioteca & catálogo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Biblioteca & catálogo</span>
                    </a>
                    <div class="submenu-label">Datos</div>
                    <a class="nav-item nav-sub {{ request()->routeIs('marketing.tareas.index') ? 'active' : '' }}" href="{{ route('marketing.tareas.index') }}" data-tip="Tareas">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Tareas</span>
                        <span class="nav-count">6</span>
                    </a>
                </div>
            </div>
            <div class="nav-section">Sistema</div>
            <div class="nav-group">
                <a class="nav-item nav-toggle" href="#" data-tip="Configuración">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span class="nav-label">Configuración</span>
                    <svg class="nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <div class="submenu">
                    <a class="nav-item nav-sub" href="{{ route('configuracion.catalogos.index') }}" data-tip="Catálogo">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Catálogo</span>
                    </a>
                    <a class="nav-item nav-sub" href="#" data-tip="Permisos">
                        <svg class="nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="nav-label">Permisos</span>
                    </a>
                </div>
            </div>
        </nav>

    </aside>

    {{-- ===================== MAIN ===================== --}}
    <div class="main">
        <header class="topbar">
            <button class="hamburger" id="hamburger" type="button" aria-label="Menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-titulo">
                {{-- Dentro de una seccion se muestra su nombre; en el inicio, el saludo.
                     En el telefono el saludo va con el primer nombre: el nombre
                     completo partia la cabecera en cinco renglones. --}}
                <h1 class="page-title">@hasSection('page-title')@yield('page-title')@else<span id="greet-word">{{ $saludo }}</span>, <span class="solo-ancho">{{ $u->name }}</span><span class="solo-angosto">{{ $primerNombre }}</span>@endif</h1>
                @hasSection('page-title')
                    @hasSection('page-sub')<p class="page-sub">@yield('page-sub')</p>@endif
                @else
                    <p class="page-sub">@yield('page-sub', 'Resumen general de tu cuenta')</p>
                @endif
            </div>
            <div class="topbar-spacer"></div>

            {{-- Tema --}}
            <button class="icon-btn" id="theme-toggle" type="button" aria-label="Cambiar tema">
                <svg class="ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg class="ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/></svg>
            </button>

            {{-- Notificaciones --}}
            <div class="dd" id="dd-notif">
                <button class="icon-btn" type="button" aria-label="Notificaciones" data-dd="dd-notif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                </button>
                <div class="dd-panel">
                    <div class="dd-head"><b>Notificaciones</b></div>
                    <div class="dd-empty">No tienes notificaciones nuevas.</div>
                </div>
            </div>

            {{-- Usuario --}}
            <div class="dd" id="dd-user">
                <button class="user-btn" type="button" data-dd="dd-user">
                    <span class="avatar">{{ Str::upper($initials) }}</span>
                    <span class="user-name">{{ $u->name }}</span>
                    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="dd-panel">
                    <div class="dd-head"><b>Acciones rápidas</b><small>Tu cuenta y herramientas</small></div>
                    <a class="dd-item" href="{{ route('profile.edit') }}">
                        <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span>
                        <span><b>Editar perfil</b><small>Actualiza tu información personal</small></span>
                    </a>
                    {{-- El tablero dejo de ser la pantalla de cuenta, asi que
                         la seguridad de la sesion se entra por aqui. --}}
                    <a class="dd-item" href="{{ route('account') }}">
                        <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5l-8-3z"/></svg></span>
                        <span><b>Mi cuenta</b><small>Sesiones activas e historial de accesos</small></span>
                    </a>
                    @if ($u->isAdmin())
                        <a class="dd-item" href="{{ route('admin.users.index') }}">
                            <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg></span>
                            <span><b>Panel de usuarios</b><small>Administra las cuentas</small></span>
                        </a>
                    @endif
                    <div class="dd-sep"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dd-item danger" type="submit">
                            <span class="di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                            <span><b>Cerrar sesión</b><small>Cerrar sesión en tu cuenta actual</small></span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
    </div>
</div>

{{-- Toast global de confirmaciones --}}
<div class="toast" id="appToast" role="status" aria-live="polite">
    <span class="toast-ico" id="appToastIco"></span>
    <span id="appToastMsg"></span>
</div>
@if (session('status'))
    <span id="appFlash" data-msg="{{ session('status') }}" data-type="ok" hidden></span>
@endif
@if ($errors->any())
    <span id="appFlashErr" data-msg="{{ $errors->first() }}" hidden></span>
@endif

<script>
    // ===== Toast global =====
    var TOAST_ICON_OK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    var TOAST_ICON_WARN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>';
    window.showToast = function (msg, type) {
        var t = document.getElementById('appToast');
        if (!t) return;
        document.getElementById('appToastMsg').textContent = msg;
        var ico = document.getElementById('appToastIco');
        ico.classList.toggle('warn', type === 'warn');
        ico.innerHTML = type === 'warn' ? TOAST_ICON_WARN : TOAST_ICON_OK;
        t.classList.add('show');
        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(function () { t.classList.remove('show'); }, 3600);
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Saludo según la hora real del navegador (evita desfase de zona horaria del servidor)
        var greetWord = document.getElementById('greet-word');
        if (greetWord) {
            var hh = new Date().getHours();
            greetWord.textContent = hh < 12 ? 'Buenos días' : (hh < 19 ? 'Buenas tardes' : 'Buenas noches');
        }

        // Mostrar mensajes flash como toast
        var flash = document.getElementById('appFlash');
        var flashErr = document.getElementById('appFlashErr');
        if (flash && flash.dataset.msg) { window.showToast(flash.dataset.msg, 'ok'); }
        else if (flashErr && flashErr.dataset.msg) { window.showToast(flashErr.dataset.msg, 'warn'); }

        var app = document.getElementById('app');
        var isMobile = function () { return window.matchMedia('(max-width:1024px)').matches; };

        // Etiqueta accesible del botón según el estado
        var collapseBtn = document.getElementById('collapse-btn');
        function syncCollapseLabel() {
            var collapsed = app.classList.contains('collapsed');
            var label = collapsed ? 'Expandir menú' : 'Contraer menú';
            collapseBtn.setAttribute('aria-label', label);
            collapseBtn.setAttribute('title', label);
        }

        // Colapsar sidebar (escritorio) / abrir-cerrar (móvil)
        function toggleSidebar() {
            if (isMobile()) {
                app.classList.toggle('sidebar-open');
            } else {
                app.classList.toggle('collapsed');
                try { localStorage.setItem('sidebar-collapsed', app.classList.contains('collapsed') ? '1' : '0'); } catch (e) {}
                syncCollapseLabel();
            }
        }
        if (!isMobile()) {
            app.classList.add('no-anim');
            try { if (localStorage.getItem('sidebar-collapsed') === '1') app.classList.add('collapsed'); } catch (e) {}
            // Forzar reflow y reactivar animaciones tras aplicar el estado inicial
            void app.offsetWidth;
            requestAnimationFrame(function () { app.classList.remove('no-anim'); });
        }
        syncCollapseLabel();
        document.getElementById('hamburger').addEventListener('click', toggleSidebar);
        document.getElementById('collapse-btn').addEventListener('click', toggleSidebar);
        document.getElementById('overlay').addEventListener('click', function () { app.classList.remove('sidebar-open'); });

        // Tema claro / oscuro
        document.getElementById('theme-toggle').addEventListener('click', function () {
            var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', cur);
            try { localStorage.setItem('theme', cur); } catch (e) {}
        });

        // Dropdowns (notificaciones / usuario)
        document.querySelectorAll('[data-dd]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var dd = document.getElementById(btn.getAttribute('data-dd'));
                var wasOpen = dd.classList.contains('open');
                document.querySelectorAll('.dd.open').forEach(function (o) { o.classList.remove('open'); });
                if (!wasOpen) dd.classList.add('open');
            });
        });
        document.addEventListener('click', function () {
            document.querySelectorAll('.dd.open').forEach(function (o) { o.classList.remove('open'); });
        });
        document.querySelectorAll('.dd-panel').forEach(function (p) {
            p.addEventListener('click', function (e) { e.stopPropagation(); });
        });

        // Ojo para contraseñas
        var eyeOn = '<svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
        var eyeOff = '<svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            var wrap = document.createElement('span');
            wrap.className = 'pw-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'pw-toggle'; b.setAttribute('aria-label', 'Mostrar u ocultar contraseña');
            b.innerHTML = eyeOn + eyeOff;
            wrap.appendChild(b);
            b.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                b.classList.toggle('is-visible', show);
            });
        });
    });

        // Submenús del sidebar
        (function () {
            var app = document.getElementById('app');
            var grupos = Array.prototype.slice.call(document.querySelectorAll('.nav-group'));
            var flotante = null;
            var temporizador = null;

            // El panel flotante toma su título del apartado al que pertenece.
            grupos.forEach(function (g) {
                var sub = g.querySelector('.submenu');
                var toggle = g.querySelector('.nav-toggle');
                if (sub && toggle) sub.dataset.nombre = toggle.dataset.tip || '';
            });

            // En móvil el menú se ve completo aunque quedara marcado contraído.
            function contraido() {
                return app.classList.contains('collapsed') && window.innerWidth > 1024;
            }

            function ocultar() {
                if (!flotante) return;
                flotante.removeAttribute('data-flotante');
                flotante = null;
            }

            function mostrar(g) {
                clearTimeout(temporizador);
                if (flotante === g) return;
                ocultar();

                var sub = g.querySelector('.submenu');
                var r = g.getBoundingClientRect();

                // El panel se ancla al ancho final de la barra, no a su medida
                // en pantalla: durante la animación de contraer esa medida
                // todavía está cambiando y el panel quedaría descuadrado.
                var barra = parseInt(getComputedStyle(document.documentElement)
                    .getPropertyValue('--sidebar-w-collapsed'), 10) || 78;

                sub.style.left = (barra + 8) + 'px';
                // Si el panel no cabe hacia abajo, sube lo necesario.
                sub.style.top = Math.max(10, Math.min(r.top, window.innerHeight - sub.offsetHeight - 10)) + 'px';

                g.setAttribute('data-flotante', '');
                flotante = g;
            }

            grupos.forEach(function (g) {
                var toggle = g.querySelector('.nav-toggle');
                if (!toggle) return;

                toggle.addEventListener('click', function (e) {
                    e.preventDefault();

                    if (contraido()) {
                        flotante === g ? ocultar() : mostrar(g);
                        return;
                    }

                    var abierto = g.classList.contains('open');
                    // Acordeón: un solo apartado abierto a la vez, para que el
                    // menú no se convierta en una lista interminable.
                    grupos.forEach(function (o) { o.classList.remove('open'); });
                    if (!abierto) g.classList.add('open');
                });

                g.addEventListener('mouseenter', function () { if (contraido()) mostrar(g); });
                g.addEventListener('mouseleave', function () {
                    // Margen para que el mouse alcance el panel sin que se cierre.
                    if (contraido()) temporizador = setTimeout(ocultar, 200);
                });
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.nav-group')) ocultar();
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') ocultar(); });
            window.addEventListener('resize', ocultar);
        })();
</script>

@stack('scripts')
</body>
</html>
