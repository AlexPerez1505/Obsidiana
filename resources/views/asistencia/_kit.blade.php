{{-- Kit de diseño del módulo de RH. Usa los mismos tokens y proporciones del resto del sistema
     (tarjetas con borde fino, etiquetas .badge, pestañas segmentadas): sin sombras ni bordes de color.
     Se incluye una vez por página: @include('asistencia._kit') --}}
@once
@push('head')
<style>
    /* ===== Entrada suave (escalonada con --i) ===== */
    .rh-in { animation:rh-in .35s cubic-bezier(.2,.7,.2,1) both; animation-delay:calc(var(--i, 0) * 40ms); }
    @keyframes rh-in { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }

    /* ===== Tarjetas (igual que .card del sistema) ===== */
    .rh-card { background:var(--surface); border:1px solid var(--border); border-radius:10px; }
    .rh-pad { padding:16px 18px; }
    .rh-h { margin:0; font-size:15px; font-weight:600; }
    .rh-sub { font-size:13px; color:var(--muted); }
    .rh-stack-gap { display:flex; flex-direction:column; gap:10px; }
    .rh-ico-box { flex:0 0 36px; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:var(--primary-soft); color:var(--primary); }
    .rh-ico-box.ok { background:var(--green-soft); color:var(--green); } .rh-ico-box.bad { background:var(--danger-soft); color:var(--danger); } .rh-ico-box.warn { background:var(--accent-soft); color:var(--accent); } .rh-ico-box.mute { background:var(--surface-2); color:var(--muted); }

    /* ===== Secciones que se despliegan ===== */
    .rh-acc { background:var(--surface); border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .rh-acc-h { display:flex; align-items:center; gap:12px; padding:13px 16px; cursor:pointer; user-select:none; outline:none; transition:background .15s; }
    .rh-acc-h:hover { background:var(--surface-2); }
    .rh-acc-h:focus-visible { background:var(--surface-2); outline:2px solid var(--primary); outline-offset:-2px; }
    .rh-acc-main { flex:1; min-width:0; }
    .rh-acc-t { font-weight:600; line-height:1.3; }
    .rh-acc-s { font-size:13px; color:var(--muted); margin-top:2px; }
    .rh-acc-side { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .rh-chev { flex:0 0 auto; width:16px; height:16px; color:var(--muted); transition:transform .3s cubic-bezier(.2,.7,.2,1); }
    .rh-acc.open > .rh-acc-h .rh-chev { transform:rotate(180deg); }
    .rh-acc.open > .rh-acc-h { background:var(--surface-2); }
    .rh-acc-b { display:grid; grid-template-rows:0fr; transition:grid-template-rows .32s cubic-bezier(.2,.7,.2,1); }
    .rh-acc.open > .rh-acc-b { grid-template-rows:1fr; }
    .rh-acc-i { overflow:hidden; min-height:0; }
    .rh-acc-c { padding:0 18px; opacity:0; transition:opacity .25s .05s; border-top:1px solid transparent; }
    .rh-acc.open > .rh-acc-b .rh-acc-c { opacity:1; padding:16px 18px 18px; border-top-color:var(--border); }
    .rh-acc.plain { background:var(--surface-2); }
    .rh-acc.plain > .rh-acc-b .rh-acc-c { border-top-color:transparent; }

    /* ===== Indicadores ===== */
    .rh-kpis { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; }
    .rh-kpi { padding:14px 16px; background:var(--surface); border:1px solid var(--border); border-radius:10px; }
    .rh-kpi-v { font-size:24px; font-weight:700; line-height:1.15; font-variant-numeric:tabular-nums; color:var(--kc, var(--text)); }
    .rh-kpi-v.sm { font-size:15px; line-height:1.3; margin-top:4px; }
    .rh-kpi-k { margin-top:4px; font-size:12px; color:var(--muted); font-weight:500; }
    .rh-kpi.ok { --kc:var(--green); } .rh-kpi.warn { --kc:var(--accent); } .rh-kpi.mal { --kc:var(--danger); } .rh-kpi.info { --kc:var(--primary); }

    /* ===== Etiquetas (misma apariencia que .badge) ===== */
    .rh-chip { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:500;
               background:var(--surface-2); color:var(--muted); border:1px solid var(--border); white-space:nowrap; }
    .rh-chip::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; flex:0 0 6px; }
    .rh-chip.plain::before { display:none; }
    .rh-chip.ok { background:var(--green-soft); color:var(--green); border-color:transparent; }
    .rh-chip.warn { background:var(--accent-soft); color:var(--accent); border-color:transparent; }
    .rh-chip.bad { background:var(--danger-soft); color:var(--danger); border-color:transparent; }
    .rh-chip.info { background:var(--primary-soft); color:var(--primary); border-color:transparent; }

    /* ===== Barras de progreso ===== */
    .rh-bar { height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
    .rh-bar > i { display:block; height:100%; width:0; border-radius:3px; background:var(--bc, var(--green)); transition:width .7s cubic-bezier(.2,.7,.2,1); }

    /* ===== Pestañas (igual que las de Órdenes de salida) ===== */
    .rh-tabs { display:inline-flex; max-width:100%; border:1px solid var(--border); border-radius:10px; overflow-x:auto; background:var(--surface); scrollbar-width:none; }
    .rh-tabs::-webkit-scrollbar { display:none; }
    .rh-tab { flex:0 0 auto; padding:9px 14px; color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap; transition:background .15s, color .15s; display:inline-flex; align-items:center; gap:6px; }
    .rh-tab + .rh-tab { border-left:1px solid var(--border); }
    .rh-tab:hover { background:var(--surface-2); color:var(--text); }
    .rh-tab.on { background:var(--primary); color:#fff; }

    /* ===== Interruptor ===== */
    .rh-switch { position:relative; display:inline-block; width:38px; height:22px; flex:0 0 auto; }
    .rh-switch input { opacity:0; width:100%; height:100%; margin:0; position:absolute; inset:0; cursor:pointer; z-index:1; }
    .rh-switch span { position:absolute; inset:0; border-radius:99px; background:var(--border); transition:background .2s; }
    .rh-switch span::after { content:""; position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; transition:transform .2s cubic-bezier(.2,.7,.2,1); }
    .rh-switch input:checked + span { background:var(--primary); }
    .rh-switch input:checked + span::after { transform:translateX(16px); }

    /* ===== Formularios ===== */
    .rh-form label { display:block; font-size:13px; font-weight:600; color:var(--text); margin:0; }
    .rh-form input[type=text], .rh-form input[type=number], .rh-form input[type=date], .rh-form input[type=time], .rh-form input[type=tel], .rh-form input[type=month], .rh-form select, .rh-form textarea { width:100%; margin-top:6px; }
    .rh-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:14px; align-items:end; }
    .rh-actions { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
    .rh-actions .btn, .btn .ui-ico, .rh-tab .ui-ico { vertical-align:middle; }
    .btn .ui-ico { margin:-2px 4px 0 -2px; }
    .btn.ico-only .ui-ico { margin:-2px 0; }
    .ne-x, .rh-x { border:0; background:none; color:var(--muted); cursor:pointer; padding:5px; border-radius:6px; line-height:0; transition:color .15s, background .15s; }
    .ne-x:hover, .rh-x:hover { color:var(--danger); background:var(--danger-soft); }
    .rh-note { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--surface); font-size:13.5px; line-height:1.55; color:var(--muted); }
    .rh-note .ui-ico { margin-top:3px; color:var(--primary); }
    .rh-note b, .rh-note a { color:var(--text); } .rh-note a { font-weight:600; }

    /* ===== Tablas que se vuelven tarjetas en el celular ===== */
    .rh-t { width:100%; border-collapse:collapse; font-size:14px; }
    .rh-t th { padding:11px 16px; background:var(--surface-2); color:var(--muted); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.02em; text-align:left; white-space:nowrap; }
    .rh-t td { padding:12px 16px; border-top:1px solid var(--border); vertical-align:middle; }
    .rh-t tbody tr { transition:background .15s; } .rh-t tbody tr:hover { background:var(--surface-2); }
    @media (max-width: 720px) {
        .rh-t thead { display:none; }
        .rh-t, .rh-t tbody, .rh-t tr, .rh-t td { display:block; width:100%; }
        .rh-t tr { padding:10px 16px; border-top:1px solid var(--border); }
        .rh-t tr:first-child { border-top:0; }
        .rh-t td { padding:5px 0; border:0; display:flex; justify-content:space-between; gap:14px; text-align:right; }
        .rh-t td::before { content:attr(data-label); flex:0 0 auto; color:var(--muted); font-size:12px; font-weight:600; text-align:left; }
        .rh-t td[data-label=""]::before, .rh-t td:not([data-label])::before { display:none; }
        .rh-t td.rh-full { display:block; text-align:left; }
        .rh-t tbody tr:hover { background:none; }
    }

    /* Encabezados de las secciones en pantallas angostas: lo secundario baja a su propia fila */
    @media (max-width: 600px) {
        .rh-acc-h { flex-wrap:wrap; row-gap:8px; padding:12px 14px; }
        .rh-acc-main { flex:1 1 0; min-width:0; }
        .rh-acc-side { order:3; flex-basis:100%; justify-content:flex-start; gap:6px; }
        .rh-acc-h .rh-chev { order:2; }
        .rh-acc.open > .rh-acc-b .rh-acc-c { padding-left:14px; padding-right:14px; }
        .rh-kpis { grid-template-columns:repeat(2, 1fr); }
        .rh-kpi-v { font-size:21px; }
    }

    .rh-empty { text-align:center; padding:36px 18px; color:var(--muted); font-size:14px; }
    .rh-empty b { display:block; color:var(--text); font-size:15px; margin-bottom:4px; }

    @media (prefers-reduced-motion: reduce) {
        .rh-in, .rh-acc-b, .rh-acc-c, .rh-chev, .rh-bar > i { animation:none !important; transition:none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var CHEV = '<svg class="rh-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';

    // Los encabezados traen su flecha sola
    document.querySelectorAll('.rh-acc-h').forEach(function (h) {
        if (!h.querySelector('.rh-chev') && !h.hasAttribute('data-rh-nochev')) h.insertAdjacentHTML('beforeend', CHEV);
        h.setAttribute('role', 'button'); h.setAttribute('tabindex', '0');
        h.setAttribute('aria-expanded', h.closest('.rh-acc').classList.contains('open') ? 'true' : 'false');
    });

    function setOpen(acc, open) {
        if (!acc) return;
        // Grupo: abrir una cierra las demás del mismo grupo
        if (open && acc.dataset.rhGroup) {
            document.querySelectorAll('.rh-acc[data-rh-group="' + acc.dataset.rhGroup + '"].open').forEach(function (o) { if (o !== acc) setOpen(o, false); });
        }
        acc.classList.toggle('open', open);
        var h = acc.querySelector(':scope > .rh-acc-h'); if (h) h.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) { var f = acc.querySelector(':scope > .rh-acc-b [autofocus]'); if (f) setTimeout(function () { f.focus(); }, 340); }
    }
    window.rhAbrir = function (id, scroll) {
        var acc = document.getElementById(id); if (!acc) return;
        setOpen(acc, true);
        if (scroll !== false) setTimeout(function () { acc.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 120);
    };

    var IGNORAR = 'a, input, select, textarea, label, button, form, .rh-noacc';
    document.addEventListener('click', function (e) {
        var ext = e.target.closest('[data-rh-toggle]');
        if (ext) { e.preventDefault(); var t = document.getElementById(ext.dataset.rhToggle); if (t) setOpen(t, !t.classList.contains('open')); return; }
        var h = e.target.closest('.rh-acc-h'); if (!h) return;
        var inner = e.target.closest(IGNORAR);
        if (inner && h.contains(inner)) return;
        setOpen(h.closest('.rh-acc'), !h.closest('.rh-acc').classList.contains('open'));
    });
    document.addEventListener('keydown', function (e) {
        var h = e.target.closest && e.target.closest('.rh-acc-h');
        if (h && e.target === h && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); h.click(); }
    });

    // Abre lo que se pidió (hash, ?abrir= o lo que el servidor dejó en sesión) y todo lo que tenga errores
    var pedido = (location.hash || '').replace('#', '') || new URLSearchParams(location.search).get('abrir') || @json(session('abrir'));
    if (pedido) window.rhAbrir(pedido);
    document.querySelectorAll('.rh-acc[data-rh-open-on-error]').forEach(function (a) { if (@json($errors->any())) setOpen(a, true); });

    // Números que suben
    function contar(el) {
        var fin = parseFloat(el.dataset.count); if (isNaN(fin)) return;
        var dec = parseInt(el.dataset.dec || '0', 10), pre = el.dataset.prefix || '', suf = el.dataset.suffix || '';
        var dur = 700, t0 = null;
        var fmt = function (n) { return pre + n.toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf; };
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = fmt(fin); return; }
        function paso(t) {
            if (t0 === null) t0 = t;
            var p = Math.min(1, (t - t0) / dur), ease = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt(fin * ease);
            if (p < 1) requestAnimationFrame(paso); else el.textContent = fmt(fin);
        }
        requestAnimationFrame(paso);
    }
    document.querySelectorAll('[data-count]').forEach(contar);

    // Barras que crecen
    requestAnimationFrame(function () { setTimeout(function () {
        document.querySelectorAll('.rh-bar > i[data-w]').forEach(function (b) { b.style.width = Math.max(0, Math.min(100, parseFloat(b.dataset.w) || 0)) + '%'; });
    }, 80); });
})();
</script>
@endpush
@endonce
