<style>
    /* ===================== Indicadores ===================== */
    .cg-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:18px; }
    .cg-kpi { padding:15px 17px; border:1px solid var(--border); border-radius:12px; background:var(--surface); }
    .cg-kpi.es-alerta { border-color:var(--danger); }
    .cg-kpi .k { display:block; color:var(--muted); font-size:12px; }
    .cg-kpi b { display:block; margin-top:3px; font-size:21px; font-weight:700; letter-spacing:-.02em; }
    .cg-kpi b.ok { color:var(--green); }
    .cg-kpi b.pend { color:var(--accent); }
    .cg-kpi b.mal { color:var(--danger); }
    .cg-kpi .s { display:block; margin-top:3px; color:var(--muted); font-size:11.5px; }

    /* ===================== Gráficas ===================== */
    .cg-graficas { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(0,1fr); gap:18px; margin-bottom:18px; align-items:start; }
    @media (max-width:1000px) { .cg-graficas { grid-template-columns:1fr; } }

    .cg-head { display:flex; align-items:center; gap:12px; flex-wrap:wrap;
               padding-bottom:12px; margin-bottom:14px; border-bottom:1px solid var(--border); }
    .cg-head h3 { margin:0; font-size:15px; font-weight:600; }
    .cg-leyenda { margin-left:auto; display:flex; gap:12px; color:var(--muted); font-size:11.5px; }
    .cg-leyenda i { display:inline-block; width:9px; height:9px; border-radius:2px; margin-right:4px; }

    .c-vendido { background:var(--primary); }
    .c-cobrado { background:var(--green); }

    .cg-barras { display:flex; align-items:flex-end; gap:8px; height:170px; }
    .cg-mes { flex:1; min-width:0; display:flex; flex-direction:column; justify-content:flex-end;
              align-items:center; gap:6px; height:100%; }
    .cg-mes .par { display:flex; align-items:flex-end; justify-content:center; gap:2px; width:100%; height:100%; }
    .cg-mes .b { width:44%; border-radius:3px 3px 0 0; min-height:2px; }
    .cg-mes .e { color:var(--muted); font-size:10.5px; }

    .cg-asesor { padding:11px 0; }
    .cg-asesor + .cg-asesor { border-top:1px solid var(--border); }
    .cg-asesor-top { display:flex; align-items:baseline; gap:10px; }
    .cg-asesor-top .n { flex:1; min-width:0; font-size:13.5px; font-weight:600;
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cg-asesor-top .m { font-size:13.5px; font-weight:700; white-space:nowrap; }
    .cg-asesor-pie { margin-top:4px; color:var(--muted); font-size:11.5px; }

    /* El riel muestra lo vendido y encima, en verde, lo ya cobrado. */
    .cg-riel { position:relative; height:6px; margin-top:6px; border-radius:3px;
               background:var(--surface-2); overflow:hidden; }
    .cg-riel .v { position:absolute; inset:0 auto 0 0; background:var(--primary-soft); }
    .cg-riel .c { position:absolute; inset:0 auto 0 0; background:var(--green); }
    .cg-riel.chico { height:4px; margin-top:5px; max-width:120px; }
    .cg-riel.chico .v { background:var(--green); }

    .cg-vacio { margin:26px 0; text-align:center; color:var(--muted); font-size:13px; }

    /* ===================== Filtros ===================== */
    .cg-filtros { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .cg-tabs { display:inline-flex; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .cg-tab { display:inline-flex; align-items:center; gap:7px; padding:9px 14px;
              color:var(--muted); font-size:13px; font-weight:600; text-decoration:none;
              background:var(--surface); transition:background .15s, color .15s; }
    .cg-tab + .cg-tab { border-left:1px solid var(--border); }
    .cg-tab:hover { background:var(--surface-2); color:var(--text); }
    .cg-tab.is-on { background:var(--primary); color:#fff; }
    .cg-num { padding:0 6px; border-radius:9px; background:rgba(0,0,0,.08); font-size:11px; }
    .cg-tab.is-on .cg-num { background:rgba(255,255,255,.25); }

    .cg-busca { flex:1; min-width:180px; }
    .cg-busca input, .cg-filtros select {
        width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:9px;
        background:var(--surface); color:var(--text); font-family:inherit; font-size:13.5px; outline:none;
    }
    .cg-filtros select { width:auto; min-width:170px; cursor:pointer; }
    .cg-busca input:focus, .cg-filtros select:focus { border-color:var(--primary); }

    /* ===================== Tabla ===================== */
    .cg-tabla { width:100%; border-collapse:collapse; font-size:13.5px; }
    .cg-tabla th { padding:11px 14px; background:var(--surface-2); color:var(--muted);
                   font-size:11.5px; font-weight:600; letter-spacing:.02em;
                   text-transform:uppercase; text-align:left; }
    .cg-tabla td { padding:12px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .cg-tabla tr:last-child td { border-bottom:0; }
    .cg-tabla tr.es-atrasada td { background:var(--danger-soft); }
    .cg-tabla .r { text-align:right; }
    .cg-tabla .ok { color:var(--green); }

    .cg-id .t { font-weight:600; }
    .cg-id .s { margin-top:1px; color:var(--muted); font-size:12px; }
    .cg-atraso { margin-top:2px; color:var(--danger); font-size:11.5px; font-weight:600; }

    .cg-chip { display:inline-block; padding:2px 9px; border-radius:999px;
               font-size:11.5px; font-weight:600; white-space:nowrap; }
    .cg-chip.es-pagado { background:var(--green-soft); color:var(--green); }
    .cg-chip.es-parcial { background:var(--accent-soft); color:var(--accent); }
    .cg-chip.es-vencido { background:var(--danger-soft); color:var(--danger); }
    .cg-chip.es-pendiente { background:var(--surface-2); color:var(--muted); }

    .cg-link { color:var(--primary); font-size:12.5px; font-weight:500; text-decoration:none; }
    .cg-link:hover { text-decoration:underline; }
    .cg-link + .cg-link { margin-left:12px; }

    @media (max-width:640px) {
        .cg-tabs { width:100%; }
        .cg-tab { flex:1; justify-content:center; padding:9px 8px; }
        .cg-filtros select { width:100%; }
    }
    @media (prefers-reduced-motion:reduce) { .cg-tab { transition:none; } }
</style>
