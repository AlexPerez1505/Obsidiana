<style>
    /* ===================== Resumen ===================== */
    .cb-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
                gap:12px; margin-bottom:18px; }
    .cb-stat { padding:14px 16px; border:1px solid var(--border); border-radius:12px; background:var(--surface); }
    .cb-stat .k { display:block; color:var(--muted); font-size:12px; }
    .cb-stat b { display:block; margin-top:3px; font-size:20px; font-weight:700; letter-spacing:-.02em; }
    .cb-stat b.ok { color:var(--green); }
    .cb-stat b.pend { color:var(--accent); }
    .cb-stat .barra { margin-top:8px; height:5px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
    .cb-stat .barra span { display:block; height:100%; background:var(--green); }

    .cb-grid { display:grid; grid-template-columns:minmax(0,1.7fr) minmax(0,1fr); gap:18px; align-items:start; }
    @media (max-width:1000px) { .cb-grid { grid-template-columns:1fr; } }

    .cb-head { display:flex; align-items:center; gap:10px; flex-wrap:wrap;
               padding-bottom:12px; margin-bottom:6px; border-bottom:1px solid var(--border); }
    .cb-head h3 { margin:0; font-size:15px; font-weight:600; }
    .cb-head-acc { margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; }

    /* ===================== Filas ===================== */
    .cb-fila { display:flex; align-items:center; gap:12px; padding:11px 0; }
    .cb-fila + .cb-fila { border-top:1px solid var(--border); }
    .cb-fila-txt { flex:1; min-width:0; }
    .cb-fila-txt .t { font-size:13.5px; font-weight:600; color:var(--text);
                      overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cb-fila-txt .s { margin-top:1px; color:var(--muted); font-size:12px;
                      overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cb-fila-monto { flex:0 0 auto; font-size:14px; font-weight:700; white-space:nowrap; }
    .cb-fila-monto.ok { color:var(--green); }
    .cb-fila-acc { flex:0 0 auto; display:flex; align-items:center; gap:3px; }

    .cb-chip { flex:0 0 auto; padding:2px 9px; border-radius:999px;
               font-size:11.5px; font-weight:600; white-space:nowrap; }
    .cb-chip.es-pagado { background:var(--green-soft); color:var(--green); }
    .cb-chip.es-parcial { background:var(--accent-soft); color:var(--accent); }
    .cb-chip.es-vencido { background:var(--danger-soft); color:var(--danger); }
    .cb-chip.es-pendiente { background:var(--surface-2); color:var(--muted); }

    .cb-mini { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px;
               padding:0; border:0; border-radius:7px; background:none; color:var(--muted);
               cursor:pointer; transition:background .15s, color .15s; }
    .cb-mini svg { width:15px; height:15px; }
    .cb-mini:hover { background:var(--surface-2); color:var(--primary); }
    .cb-mini--ok:hover { color:var(--green); }
    .cb-mini--danger:hover { color:var(--danger); }

    .cb-vacio { margin:18px 0; text-align:center; color:var(--muted); font-size:13px; }

    .cb-alerta { margin-top:14px; padding:11px 13px; border:1px solid var(--border);
                 border-left:3px solid var(--accent); border-radius:9px;
                 background:var(--surface-2); color:var(--muted); font-size:12.5px; }
    .cb-enlace { border:0; background:none; padding:0; color:var(--primary);
                 font-family:inherit; font-size:12.5px; font-weight:600; cursor:pointer; }
    .cb-enlace:hover { text-decoration:underline; }

    .cb-mov { padding:9px 0; }
    .cb-mov + .cb-mov { border-top:1px solid var(--border); }
    .cb-mov .t { font-size:13px; color:var(--text); }
    .cb-mov .s { margin-top:1px; color:var(--muted); font-size:11.5px; }

    /* ===================== Modales ===================== */
    .cb-modal { padding:0; border:0; background:transparent; max-width:none; max-height:none; }
    .cb-modal::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }
    .cb-modal-box { display:flex; flex-direction:column; width:min(520px, calc(100vw - 28px));
                    max-height:min(88vh, 760px); background:var(--surface);
                    border:1px solid var(--border); border-radius:14px; overflow:hidden;
                    box-shadow:0 24px 60px rgba(17,24,39,.22); }
    [data-theme="dark"] .cb-modal-box { box-shadow:0 24px 60px rgba(0,0,0,.55); }

    .cb-modal-head { display:flex; align-items:center; gap:12px; padding:17px 20px;
                     border-bottom:1px solid var(--border); }
    .cb-modal-head h3 { margin:0; font-size:17px; font-weight:600; }
    .cb-x { margin-left:auto; display:inline-flex; align-items:center; justify-content:center;
            width:32px; height:32px; padding:0; border:0; border-radius:8px; background:none;
            color:var(--muted); cursor:pointer; }
    .cb-x svg { width:17px; height:17px; }
    .cb-x:hover { background:var(--surface-2); color:var(--text); }

    .cb-modal-body { flex:1; min-height:0; overflow-y:auto; padding:20px; }
    .cb-modal-body label { display:block; margin-bottom:6px; color:var(--muted); font-size:13px; }
    .cb-modal-body input[type="text"],
    .cb-modal-body input[type="date"],
    .cb-modal-body input[type="number"],
    .cb-modal-body select { width:100%; padding:10px 12px; border:1px solid var(--border);
                            border-radius:9px; background:var(--surface); color:var(--text);
                            font-family:inherit; font-size:14px; outline:none; }
    .cb-modal-body input:focus, .cb-modal-body select:focus { border-color:var(--primary); }
    .cb-mt { margin-top:14px; }
    .cb-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .cb-nota { margin:5px 0 0; color:var(--muted); font-size:11.5px; }
    .cb-explica { margin:0; color:var(--muted); font-size:13px; line-height:1.55; }
    .cb-explica b { color:var(--text); }

    .cb-file { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
    .cb-drop { display:flex !important; flex-direction:column; align-items:center; gap:4px;
               padding:20px; border:1.5px dashed var(--border); border-radius:11px;
               background:var(--surface-2); cursor:pointer; text-align:center;
               transition:border-color .15s, background .15s; }
    .cb-drop:hover, .cb-drop.is-encima { border-color:var(--primary); background:var(--primary-soft); }
    .cb-drop svg { width:22px; height:22px; color:var(--muted); }
    .cb-drop span { font-size:13px; font-weight:600; color:var(--text); overflow-wrap:anywhere; }
    .cb-drop small { color:var(--muted); font-size:11.5px; }

    .cb-modal-foot { display:flex; justify-content:flex-end; gap:10px; padding:15px 20px;
                     border-top:1px solid var(--border); background:var(--surface-2); }

    @media (max-width:600px) {
        .cb-2 { grid-template-columns:1fr; }
        .cb-modal-foot { flex-direction:column-reverse; }
        .cb-modal-foot .erp-btn { width:100%; text-align:center; }
        .cb-fila { flex-wrap:wrap; }
        .cb-fila-acc { margin-left:auto; }
    }
    @media (prefers-reduced-motion:reduce) { .cb-mini, .cb-drop { transition:none; } }
</style>
