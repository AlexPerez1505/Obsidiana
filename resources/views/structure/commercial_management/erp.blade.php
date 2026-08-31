@extends('layouts.dashboard')

@section('title')
    @yield('title', 'Gestión Comercial')
@endsection

@section('content')
<style>
    /* ===== ERP · sistema de diseño limpio (SaaS, minimalista, empresarial) ===== */
    /* Alineado con el sistema base del dashboard (mismo patron que
       /gestion-inventario/equipos): mismo radio, tipografia y espaciados. */
    .erp { --erp-radius:10px; }

    /* Encabezado de página · minimalista, igual que x-ui.page-header:
       sin tarjeta, sin recuadro de icono y sin subtitulo. */
    .erp-head { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
    .erp-head-l { display:flex; align-items:center; gap:10px; min-width:0; }
    /* Todo lo que siga al bloque del titulo se va a la derecha. */
    .erp-head > .erp-head-l ~ * { margin-left:auto; }
    .erp-ic { display:none; }
    .erp-h1 { font-size:18px; font-weight:600; margin:0; letter-spacing:-.01em; line-height:1.2;
              display:flex; align-items:center; gap:10px; }
    .erp-sub { display:none; }
    .erp-count { font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2);
                 border:1px solid var(--border); padding:1px 7px; border-radius:5px; }
    .erp-actions { margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

    /* Botones */
    .erp { --erp-ease:cubic-bezier(.23,1,.32,1); }
    /* Botones sobrios, de herramienta de trabajo: compactos y con
       radio corto. Nada de pildoras grandes. */
    .erp-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:7px; font-size:13.5px;
               font-weight:500; border:1px solid var(--primary); cursor:pointer; text-decoration:none; background:var(--primary);
               color:#fff; font-family:inherit; text-align:center; line-height:1.5;
               transition:background .16s ease, border-color .16s ease, color .16s ease; }
    .erp-btn:hover { background:var(--primary-strong); border-color:var(--primary-strong); }
    .erp-btn.ghost { background:var(--surface); color:var(--text); border-color:var(--border); }
    .erp-btn.ghost:hover { background:var(--surface-2); }
    .erp-btn.danger { background:var(--danger); border-color:var(--danger); }
    .erp-btn.danger:hover { filter:brightness(.92); }
    .erp-btn.sm { padding:6px 11px; font-size:13px; }
    .erp-btn svg { width:15px; height:15px; }
    .erp-btn svg { width:16px; height:16px; }

    /* Volver atrás: borderless, solo ícono */
    .erp-back { width:38px; height:38px; border-radius:10px; border:none; background:transparent; color:var(--muted); cursor:pointer;
                display:inline-flex; align-items:center; justify-content:center; text-decoration:none; flex:0 0 auto;
                transition:background .14s ease, color .14s ease, transform .12s var(--erp-ease); }
    .erp-back:hover { background:var(--surface-2); color:var(--text); }
    .erp-back:active { transform:scale(.9); }
    .erp-back svg { width:20px; height:20px; }

    /* Estadísticas */
    .erp-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px; margin-bottom:20px; }
    .erp-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--erp-radius); padding:22px;
                display:flex; align-items:center; gap:16px; }
    .erp-stat .ic { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .erp-stat .ic.blue  { background:var(--primary-soft); color:var(--primary); }
    .erp-stat .ic.green { background:var(--green-soft); color:var(--green); }
    .erp-stat .ic.amber { background:var(--accent-soft); color:var(--accent); }
    .erp-stat .ic.slate { background:var(--surface-2); color:var(--muted); }
    .erp-stat .n { font-size:30px; font-weight:800; line-height:1; }
    .erp-stat .l { color:var(--muted); font-size:13px; margin-top:4px; text-transform:uppercase; letter-spacing:.04em; }

    /* Tarjetas y tablas */
    .erp-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--erp-radius); }
    .erp-card.pad { padding:22px; }
    .erp-table-wrap { overflow-x:auto; border-radius:var(--erp-radius); }
    .erp-table { width:100%; border-collapse:collapse; font-size:13.5px; }
    .erp-table th { text-align:left; padding:11px 16px; color:var(--muted); font-weight:400; font-size:13px;
                    border-bottom:1px solid var(--border); white-space:nowrap; }
    .erp-table td { padding:11px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .erp-table tbody tr:last-child td { border-bottom:none; }
    .erp-table tbody tr:hover td { background:var(--surface-2); }
    .erp-strong { font-weight:700; }
    .erp-empty { text-align:center; padding:40px 16px; color:var(--muted); }

    /* Badges tipo píldora */
    .erp-badge { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
    .erp-badge .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .erp-badge.ok { background:var(--green-soft); color:var(--green); }
    .erp-badge.warn { background:var(--accent-soft); color:var(--accent); }
    .erp-badge.info { background:var(--primary-soft); color:var(--primary); }
    .erp-badge.neutral { background:var(--surface-2); color:var(--muted); }
    .erp-badge.danger { background:var(--danger-soft); color:var(--danger); }

    /* Menú de acciones (tres puntos) */
    .erp-menu { position:relative; display:inline-block; }
    .erp-kebab { width:34px; height:34px; border-radius:10px; border:1px solid var(--border); background:var(--surface);
                 color:var(--muted); cursor:pointer; display:inline-flex; align-items:center; justify-content:center;
                 transition:background .15s ease, color .15s ease, border-color .15s ease; }
    .erp-kebab { transition:background .15s ease, color .15s ease, border-color .15s ease, transform .12s var(--erp-ease); }
    .erp-kebab:hover, .erp-kebab.active { background:var(--surface-2); color:var(--text); border-color:var(--primary); }
    .erp-kebab:active { transform:scale(.94); }
    .erp-kebab svg { width:18px; height:18px; }
    .erp-menu-panel { position:fixed; top:0; left:0; min-width:196px; background:var(--surface); border:1px solid var(--border);
                      border-radius:14px; box-shadow:0 18px 44px rgba(17,24,39,.16); padding:7px; z-index:1000; display:none;
                      transform-origin:top right; }
    .erp-menu-panel.open { display:block; animation:erpPop .16s var(--erp-ease); }
    @keyframes erpPop { from { opacity:0; transform:translateY(-6px) scale(.96); } to { opacity:1; transform:none; } }
    .erp-menu-item { display:flex; align-items:center; gap:11px; width:100%; padding:9px 11px; border-radius:9px; font-size:13.5px;
                     font-weight:500; color:var(--text); text-decoration:none; background:none; border:none; cursor:pointer;
                     text-align:left; font-family:inherit; transition:background .13s ease; }
    .erp-menu-item:hover { background:var(--surface-2); }
    .erp-menu-item svg { width:17px; height:17px; color:var(--muted); flex:0 0 auto; }
    .erp-menu-item.danger { color:var(--danger); }
    .erp-menu-item.danger svg { color:var(--danger); }
    .erp-menu-item.danger:hover { background:var(--danger-soft); }
    .erp-menu-sep { height:1px; background:var(--border); margin:5px 7px; }

    /* Utilidades */
    .erp-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
    .erp-search { position:relative; flex:1; min-width:220px; }
    .erp-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); width:17px; height:17px; color:var(--muted); }
    .erp-search input { width:100%; padding:11px 12px 11px 40px; border:1px solid var(--border); border-radius:11px; font-size:14px;
                        background:var(--surface); color:var(--text); outline:none; font-family:inherit; }
    .erp-search input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.13); }
    .erp-two { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
    @media (max-width:1000px){ .erp-two { grid-template-columns:1fr; } }

    @media (prefers-reduced-motion: reduce) {
        .erp-menu-panel.open { animation:none; }
        .erp-btn, .erp-kebab { transition:none; }
    }
</style>

<div class="erp">
    @yield('erp_content')
</div>
@endsection

@push('scripts')
<script>
(function () {
    let openPanel = null;
    function closeMenu() {
        if (openPanel) {
            openPanel.classList.remove('open');
            const btn = document.querySelector('.erp-kebab.active');
            if (btn) btn.classList.remove('active');
            openPanel = null;
        }
    }
    function place(panel, btn) {
        const r = btn.getBoundingClientRect();
        panel.style.visibility = 'hidden';
        panel.classList.add('open');
        const w = panel.offsetWidth, h = panel.offsetHeight;
        let left = r.right - w;
        if (left < 8) left = 8;
        if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
        let top = r.bottom + 6;
        if (top + h > window.innerHeight - 8) top = r.top - h - 6;
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
        panel.style.visibility = '';
    }
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.erp-kebab');
        if (btn) {
            e.stopPropagation();
            const panel = btn.parentElement.querySelector('.erp-menu-panel');
            const isOpen = panel.classList.contains('open');
            closeMenu();
            if (!isOpen) { place(panel, btn); btn.classList.add('active'); openPanel = panel; }
            return;
        }
        if (!e.target.closest('.erp-menu-panel')) closeMenu();
    });
    window.addEventListener('scroll', closeMenu, true);
    window.addEventListener('resize', closeMenu);
})();
</script>
@endpush
