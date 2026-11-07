{{--
    Estilos del módulo Marketing y promociones. Todo cuelga de .mkt para no
    tocar nada fuera del módulo. Usa los tokens del tablero (--surface,
    --border, --primary…) así respeta el tema claro/oscuro del sistema.
--}}
<style>
    .mkt { --mkt-r: 10px; }
    .mkt .mkt-tabs { display:flex; gap:4px; border-bottom:1px solid var(--border); margin:0 0 18px; overflow-x:auto; }
    .mkt .mkt-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px; font-size:13.5px; font-weight:500; color:var(--muted);
                    text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-1px; white-space:nowrap; }
    .mkt .mkt-tab svg { width:16px; height:16px; }
    .mkt .mkt-tab:hover { color:var(--text); }
    .mkt .mkt-tab.on { color:var(--primary); border-bottom-color:var(--primary); }

    .mkt .mkt-grid { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:18px; align-items:start; }
    .mkt .mkt-col { display:flex; flex-direction:column; gap:18px; min-width:0; }
    @media (max-width: 1100px) { .mkt .mkt-grid { grid-template-columns:1fr; } }

    .mkt .mkt-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--mkt-r); }
    .mkt .mkt-card-h { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:14px 18px; border-bottom:1px solid var(--border); }
    .mkt .mkt-card-h h3 { margin:0; font-size:14.5px; font-weight:600; display:flex; align-items:center; gap:8px; }
    .mkt .mkt-card-h h3 svg { width:17px; height:17px; color:var(--muted); }
    .mkt .mkt-card-b { padding:16px 18px; }
    .mkt .mkt-sub { margin:2px 0 0; font-size:12.5px; color:var(--muted); font-weight:400; }

    .mkt .mkt-kpis { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr)); gap:12px; margin-bottom:18px; }
    .mkt .mkt-kpi { background:var(--surface); border:1px solid var(--border); border-radius:var(--mkt-r); padding:14px 16px; }
    .mkt .mkt-kpi .l { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:6px; }
    .mkt .mkt-kpi .l svg { width:14px; height:14px; }
    .mkt .mkt-kpi .n { font-size:22px; font-weight:650; margin-top:4px; letter-spacing:-.01em; }
    .mkt .mkt-kpi .s { font-size:12px; color:var(--muted); margin-top:2px; }

    .mkt .mkt-f { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
    .mkt .mkt-f > label, .mkt .mkt-lbl { font-size:12.5px; font-weight:600; color:var(--text); }
    .mkt .mkt-f .ayuda, .mkt .ayuda { font-size:12px; color:var(--muted); margin:0; }
    .mkt .mkt-in, .mkt select.mkt-in, .mkt textarea.mkt-in { width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:8px;
                    background:var(--surface); color:var(--text); font:inherit; font-size:13.5px; }
    .mkt textarea.mkt-in { resize:vertical; min-height:92px; }
    .mkt .mkt-in:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-soft); }
    .mkt .mkt-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .mkt .mkt-row3 { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
    @media (max-width: 640px) { .mkt .mkt-row, .mkt .mkt-row3 { grid-template-columns:1fr; } }
    .mkt .mkt-check { display:flex; align-items:flex-start; gap:9px; font-size:13.5px; cursor:pointer; margin:4px 0 10px; }
    .mkt .mkt-check input { margin-top:3px; }
    .mkt .mkt-err { color:var(--danger); font-size:12px; }

    .mkt .mkt-seg { display:inline-flex; border:1px solid var(--border); border-radius:8px; overflow:hidden; }
    .mkt .mkt-seg label { padding:7px 12px; font-size:13px; cursor:pointer; border-right:1px solid var(--border); }
    .mkt .mkt-seg label:last-child { border-right:0; }
    .mkt .mkt-seg input { display:none; }
    .mkt .mkt-seg label:has(input:checked) { background:var(--primary-soft); color:var(--primary); font-weight:600; }

    .mkt .mkt-ac { position:relative; }
    .mkt .mkt-ac-list { position:absolute; z-index:30; left:0; right:0; top:100%; margin-top:4px; background:var(--surface); border:1px solid var(--border);
                        border-radius:8px; box-shadow:var(--shadow); max-height:260px; overflow:auto; display:none; }
    .mkt .mkt-ac-list.on { display:block; }
    .mkt .mkt-ac-item { padding:8px 11px; font-size:13px; cursor:pointer; display:flex; justify-content:space-between; gap:10px; }
    .mkt .mkt-ac-item:hover { background:var(--surface-2); }
    .mkt .mkt-ac-item small { color:var(--muted); }

    .mkt .mkt-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .mkt .mkt-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 6px 4px 10px; border:1px solid var(--border); border-radius:999px;
                     font-size:12.5px; background:var(--surface-2); }
    .mkt .mkt-chip button { border:0; background:none; color:var(--muted); cursor:pointer; padding:0 4px; font-size:15px; line-height:1; }
    .mkt .mkt-chip button:hover { color:var(--danger); }

    .mkt .mkt-list { list-style:none; margin:0; padding:0; }
    .mkt .mkt-list li { display:flex; justify-content:space-between; gap:12px; padding:9px 0; border-bottom:1px solid var(--border); font-size:13.5px; }
    .mkt .mkt-list li:last-child { border-bottom:0; }
    .mkt .mkt-list .k { color:var(--muted); }
    .mkt .mkt-list .v { font-weight:600; text-align:right; }

    .mkt .mkt-prod { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border); }
    .mkt .mkt-prod:last-child { border-bottom:0; }
    .mkt .mkt-prod img, .mkt .mkt-prod .ph { width:40px; height:40px; border-radius:8px; object-fit:cover; background:var(--surface-2); flex:0 0 auto; }
    .mkt .mkt-prod .t { flex:1; min-width:0; font-size:13px; }
    .mkt .mkt-prod .t b { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .mkt .mkt-prod .t small { color:var(--muted); }

    /* Vista previa estilo WhatsApp (sobria: solo la burbuja) */
    .mkt .mkt-wa { background:var(--surface-2); border-radius:var(--mkt-r); padding:16px; }
    .mkt .mkt-wa-bubble { background:var(--surface); border:1px solid var(--border); border-radius:10px; max-width:340px; overflow:hidden; box-shadow:0 1px 1px rgba(0,0,0,.04); }
    .mkt .mkt-wa-bubble img { display:block; width:100%; max-height:200px; object-fit:cover; background:var(--surface-2); }
    .mkt .mkt-wa-txt { padding:10px 12px 6px; font-size:13.5px; line-height:1.45; white-space:pre-wrap; word-wrap:break-word; }
    .mkt .mkt-wa-hora { text-align:right; font-size:11px; color:var(--muted); padding:0 10px 8px; }
    .mkt .mkt-wa-btn { border-top:1px solid var(--border); padding:9px; text-align:center; font-size:13.5px; color:var(--primary); font-weight:600;
                       display:flex; align-items:center; justify-content:center; gap:6px; }
    .mkt .mkt-wa-btn svg { width:15px; height:15px; }

    .mkt .mkt-aud { display:flex; align-items:baseline; gap:8px; }
    .mkt .mkt-aud b { font-size:26px; letter-spacing:-.02em; }
    .mkt .mkt-excl { margin-top:8px; display:grid; grid-template-columns:1fr auto; gap:4px 12px; font-size:12.5px; color:var(--muted); }

    .mkt .mkt-aviso { display:flex; gap:10px; padding:11px 13px; border-radius:8px; font-size:13px; border:1px solid var(--border); background:var(--surface-2); }
    .mkt .mkt-aviso svg { width:17px; height:17px; flex:0 0 auto; margin-top:1px; }
    .mkt .mkt-aviso.warn { background:var(--accent-soft); border-color:transparent; color:var(--text); }
    .mkt .mkt-aviso.warn svg { color:var(--accent); }
    .mkt .mkt-aviso.info svg { color:var(--primary); }

    .mkt .mkt-tl { list-style:none; margin:0; padding:0; }
    .mkt .mkt-tl li { padding:8px 0 8px 16px; border-left:2px solid var(--border); position:relative; font-size:13px; }
    .mkt .mkt-tl li::before { content:''; position:absolute; left:-5px; top:13px; width:8px; height:8px; border-radius:50%; background:var(--border); }
    .mkt .mkt-tl small { display:block; color:var(--muted); font-size:11.5px; }

    .mkt .mkt-filtros { display:flex; flex-wrap:wrap; gap:8px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--border); }
    .mkt .mkt-filtros .mkt-in { width:auto; min-width:160px; padding:7px 10px; }
    .mkt .mkt-filtros .crece { flex:1; min-width:200px; }
    .mkt .mkt-pie { padding:12px 16px; border-top:1px solid var(--border); }
    .mkt .mkt-codigo { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12.5px; font-weight:600; letter-spacing:.03em;
                       background:var(--surface-2); border:1px dashed var(--border); padding:2px 7px; border-radius:6px; }
    .mkt .mkt-acciones { display:flex; flex-wrap:wrap; gap:8px; }
    .mkt .mkt-acciones form { margin:0; }
    .mkt .mkt-num { font-variant-numeric:tabular-nums; }
    .mkt .mkt-muted { color:var(--muted); }
    .mkt dialog.mkt-dlg { border:0; border-radius:12px; padding:0; width:min(520px, calc(100vw - 32px)); background:var(--surface); color:var(--text); box-shadow:0 20px 60px rgba(0,0,0,.25); }
    .mkt dialog.mkt-dlg::backdrop { background:rgba(15,23,42,.45); }
    .mkt .mkt-dlg-h { padding:16px 20px; border-bottom:1px solid var(--border); font-weight:600; font-size:15px; }
    .mkt .mkt-dlg-b { padding:16px 20px; }
    .mkt .mkt-dlg-f { padding:12px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; }
    .mkt .erp-table td { vertical-align:middle; }
</style>
