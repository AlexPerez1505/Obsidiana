{{--
    Kit visual del módulo Marketing y promociones. Todo cuelga de .mk para
    no tocar el encabezado ni otros módulos. Usa los tokens del tablero
    (--surface, --border, --primary…) para respetar el tema del sistema.
--}}
<style>
.mk { --mk-r: 10px; --mk-r-sm: 8px; --mk-sh: 0 1px 2px rgba(16,24,40,.05); --mk-sh-2: 0 8px 24px rgba(16,24,40,.12);
      --mk-t: 180ms cubic-bezier(.2,.8,.2,1); color: var(--text); font-size: 14px; }
.mk *, .mk *::before, .mk *::after { box-sizing: border-box; }
.mk [hidden] { display: none !important; }
.mk svg { width: 16px; height: 16px; flex: 0 0 auto; }
.mk .mk-num { font-variant-numeric: tabular-nums; }
.mk .mk-muted { color: var(--muted); }
.mk :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; border-radius: 6px; }

/* ---------- Encabezado y pestañas ---------- */
.mk-head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0 0 6px; }
.mk-head h1 { font-size: 19px; font-weight: 650; letter-spacing: -.01em; margin: 0; flex: 1; min-width: 180px; }
.mk-head-tools { display: flex; gap: 8px; align-items: center; }
.mk-sel { position: relative; display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 10px; border: 1px solid var(--border);
          border-radius: var(--mk-r-sm); background: var(--surface); color: var(--text); }
.mk-sel select { border: 0; background: transparent; color: inherit; font: inherit; font-size: 13px; padding-right: 2px; cursor: pointer; outline: none; }
.mk-sel svg { color: var(--muted); }
.mk-tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--border); margin: 0 0 16px; overflow-x: auto; scrollbar-width: none; }
.mk-tabs::-webkit-scrollbar { display: none; }
.mk-tab { display: inline-flex; align-items: center; gap: 7px; padding: 10px 12px; font-size: 13.5px; font-weight: 550; color: var(--muted);
          text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; transition: color var(--mk-t); }
.mk-tab:hover { color: var(--text); }
.mk-tab[aria-current="page"] { color: var(--primary); border-bottom-color: var(--primary); }
.mk-tab .n { font-size: 11px; font-weight: 650; padding: 1px 6px; border-radius: 999px; background: var(--surface-2); color: var(--muted); }
.mk-tab[aria-current="page"] .n { background: var(--primary-soft); color: var(--primary); }
.mk-tab .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
.mk-tab .dot.ok { background: var(--green); }

/* ---------- Botones ---------- */
.mk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 34px; padding: 0 13px; border-radius: var(--mk-r-sm);
          border: 1px solid var(--primary); background: var(--primary); color: #fff; font: inherit; font-size: 13.5px; font-weight: 600;
          cursor: pointer; text-decoration: none; white-space: nowrap; transition: background var(--mk-t), transform 90ms, box-shadow var(--mk-t); }
.mk-btn:hover { background: var(--primary-strong); }
.mk-btn:active { transform: scale(.98); }
.mk-btn[disabled] { opacity: .55; cursor: not-allowed; transform: none; }
.mk-btn.sec { background: var(--surface); color: var(--text); border-color: var(--border); }
.mk-btn.sec:hover { background: var(--surface-2); }
.mk-btn.ghost { background: transparent; border-color: transparent; color: var(--text); }
.mk-btn.ghost:hover { background: var(--surface-2); }
.mk-btn.danger { color: var(--danger); }
.mk-btn.sm { height: 30px; padding: 0 10px; font-size: 13px; }
.mk-ico { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--mk-r-sm);
          border: 1px solid transparent; background: transparent; color: var(--muted); cursor: pointer; transition: background var(--mk-t), color var(--mk-t); }
.mk-ico:hover { background: var(--surface-2); color: var(--text); }
.mk-ico.bordered { border-color: var(--border); background: var(--surface); }

/* ---------- Menús ---------- */
.mk-menu { position: relative; display: inline-block; }
.mk-menu-panel { position: absolute; right: 0; top: calc(100% + 4px); z-index: 60; min-width: 190px; padding: 5px; background: var(--surface);
                 border: 1px solid var(--border); border-radius: var(--mk-r); box-shadow: var(--mk-sh-2); display: none; }
.mk-menu.on .mk-menu-panel { display: block; animation: mk-in 150ms ease-out; }
.mk-menu-panel.izq { right: auto; left: 0; }
.mk-mi { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: 0; background: none; border-radius: 6px; color: var(--text);
         font: inherit; font-size: 13.5px; text-align: left; cursor: pointer; text-decoration: none; }
.mk-mi:hover, .mk-mi:focus-visible { background: var(--surface-2); outline: none; }
.mk-mi svg { color: var(--muted); }
.mk-mi.danger { color: var(--danger); }
.mk-mi.danger svg { color: var(--danger); }
.mk-mi small { display: block; color: var(--muted); font-size: 12px; }
.mk-sep { height: 1px; background: var(--border); margin: 4px 0; }

/* ---------- Indicadores ---------- */
.mk-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
@media (max-width: 1000px) { .mk-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.mk-kpi { position: relative; display: flex; flex-direction: column; gap: 6px; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border);
          border-radius: var(--mk-r); box-shadow: var(--mk-sh); text-decoration: none; color: inherit; transition: border-color var(--mk-t), box-shadow var(--mk-t); min-width: 0; }
a.mk-kpi:hover { border-color: color-mix(in srgb, var(--primary) 40%, var(--border)); box-shadow: var(--mk-sh-2); }
.mk-kpi .t { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted); font-weight: 550; }
.mk-kpi .t .i { width: 26px; height: 26px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; background: var(--primary-soft); color: var(--primary); }
.mk-kpi .t .i svg { width: 15px; height: 15px; }
.mk-kpi .v { font-size: 22px; font-weight: 700; letter-spacing: -.02em; line-height: 1.1; }
.mk-kpi .c { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.mk-kpi .spark { position: absolute; right: 12px; bottom: 12px; width: 84px; height: 28px; color: var(--primary); opacity: .9; }
.mk-delta { font-weight: 650; font-size: 11.5px; padding: 1px 6px; border-radius: 999px; }
.mk-delta.up { color: var(--green); background: var(--green-soft); }
.mk-delta.down { color: var(--danger); background: var(--danger-soft); }
.mk-delta.eq { color: var(--muted); background: var(--surface-2); }

/* ---------- Barra de herramientas, chips y filtros ---------- */
.mk-bar { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
.mk-search { position: relative; flex: 1 1 260px; max-width: 420px; }
.mk-search svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.mk-search input { width: 100%; height: 34px; padding: 0 30px 0 32px; border: 1px solid var(--border); border-radius: var(--mk-r-sm); background: var(--surface);
                   color: var(--text); font: inherit; font-size: 13.5px; }
.mk-search input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
.mk-search .x { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 26px; height: 26px; }
.mk-chips { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.mk-chip { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 11px; border: 1px solid var(--border); border-radius: 999px;
           background: var(--surface); color: var(--text); font: inherit; font-size: 13px; cursor: pointer; transition: border-color var(--mk-t), background-color var(--mk-t); white-space: nowrap; }
.mk-chip:hover { border-color: color-mix(in srgb, var(--primary) 45%, var(--border)); }
.mk-chip[aria-pressed="true"] { background: var(--primary); color: #fff; border-color: var(--primary); }
.mk-chip .n { font-size: 11.5px; opacity: .75; font-variant-numeric: tabular-nums; }
.mk-chip.aplicado { background: var(--primary-soft); color: var(--primary); border-color: transparent; height: 26px; font-size: 12.5px; padding: 0 6px 0 10px; }
.mk-chip.aplicado button { border: 0; background: none; color: inherit; cursor: pointer; padding: 0 3px; display: inline-flex; }
.mk-chip.aplicado svg { width: 13px; height: 13px; }
.mk-seg { display: inline-flex; padding: 2px; border: 1px solid var(--border); border-radius: var(--mk-r-sm); background: var(--surface-2); gap: 2px; }
.mk-seg button, .mk-seg label { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 10px; border: 0; border-radius: 6px; background: transparent;
                                color: var(--muted); font: inherit; font-size: 13px; cursor: pointer; transition: background-color var(--mk-t), color var(--mk-t); }
.mk-seg input { position: absolute; opacity: 0; pointer-events: none; }
.mk-seg [aria-pressed="true"], .mk-seg label:has(input:checked) { background: var(--surface); color: var(--text); box-shadow: var(--mk-sh); font-weight: 600; }
.mk-sub { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-height: 26px; margin-bottom: 12px; font-size: 12.5px; color: var(--muted); }
.mk-sub .total { margin-right: 4px; }
.mk-link { background: none; border: 0; color: var(--primary); font: inherit; font-size: 12.5px; cursor: pointer; padding: 0; }

/* ---------- Superficies ---------- */
.mk-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--mk-r); box-shadow: var(--mk-sh); }
.mk-card-h { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 14px; border-bottom: 1px solid var(--border); }
.mk-card-h h3 { margin: 0; font-size: 13.5px; font-weight: 650; display: flex; align-items: center; gap: 7px; }
.mk-card-h h3 svg { color: var(--muted); }
.mk-card-b { padding: 12px 14px; }
.mk-grid2 { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 16px; align-items: start; }
@media (max-width: 1000px) { .mk-grid2 { grid-template-columns: 1fr; } }
.mk-rows { list-style: none; margin: 0; padding: 0; }
.mk-rows li { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
.mk-rows li:last-child { border-bottom: 0; }
.mk-rows .k { color: var(--muted); }
.mk-rows .v { font-weight: 600; text-align: right; }

/* ---------- Badges ---------- */
.mk-badge { display: inline-flex; align-items: center; gap: 5px; height: 22px; padding: 0 8px; border-radius: 999px; font-size: 11.5px; font-weight: 650; white-space: nowrap; }
.mk-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.mk-badge.ok { background: var(--green-soft); color: var(--green); }
.mk-badge.warn { background: var(--accent-soft); color: var(--accent); }
.mk-badge.info { background: var(--primary-soft); color: var(--primary); }
.mk-badge.danger { background: var(--danger-soft); color: var(--danger); }
.mk-badge.neutral { background: var(--surface-2); color: var(--muted); }
.mk-pill { display: inline-flex; align-items: center; gap: 4px; height: 20px; padding: 0 7px; border-radius: 6px; font-size: 11px; font-weight: 600;
           background: var(--surface-2); color: var(--muted); }
.mk-pill svg { width: 12px; height: 12px; }
.mk-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; letter-spacing: .04em; font-size: 12.5px; }

/* ---------- Ticket de cupón ---------- */
.mk-tickets { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 12px; }
.mk-ticket { position: relative; display: grid; grid-template-columns: 96px 1fr; background: var(--surface); border: 1px solid var(--border);
             border-radius: var(--mk-r); box-shadow: var(--mk-sh); transition: border-color var(--mk-t), box-shadow var(--mk-t), transform var(--mk-t); cursor: pointer; min-height: 132px; }
.mk-ticket:hover { box-shadow: var(--mk-sh-2); border-color: color-mix(in srgb, var(--primary) 30%, var(--border)); }
.mk-ticket.sel { border-color: var(--primary); box-shadow: 0 0 0 2px var(--primary-soft); }
.mk-ticket .l { position: relative; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px; padding: 10px 6px;
                background: var(--primary-soft); color: var(--primary); border-radius: var(--mk-r) 0 0 var(--mk-r); border-right: 2px dashed color-mix(in srgb, var(--primary) 25%, transparent); text-align: center; }
.mk-ticket.apagado .l { background: var(--surface-2); color: var(--muted); border-right-color: var(--border); }
.mk-ticket .l::before, .mk-ticket .l::after { content: ''; position: absolute; right: -9px; width: 16px; height: 16px; border-radius: 50%; background: var(--bg, #f5f6f8); border: 1px solid var(--border); }
.mk-ticket .l::before { top: -9px; } .mk-ticket .l::after { bottom: -9px; }
.mk-ticket .l .v { font-size: 25px; font-weight: 750; letter-spacing: -.03em; line-height: 1; }
.mk-ticket .l .s { font-size: 11px; font-weight: 600; opacity: .85; }
.mk-ticket .r { padding: 10px 10px 10px 14px; display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.mk-ticket .top { display: flex; align-items: center; gap: 6px; }
.mk-ticket .top .mk-menu { margin-left: auto; }
.mk-ticket .nom { font-size: 13px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mk-ticket .cod { display: flex; align-items: center; gap: 4px; }
.mk-ticket .cod .mk-code { font-size: 14px; color: var(--text); }
.mk-ticket .meta { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--muted); flex-wrap: wrap; }
.mk-ticket .meta span { display: inline-flex; align-items: center; gap: 4px; }
.mk-ticket .meta svg { width: 13px; height: 13px; }
.mk-check { position: absolute; top: 8px; left: 8px; width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; z-index: 2; }
.mk-bar-uso { height: 5px; border-radius: 999px; background: var(--surface-2); overflow: hidden; }
.mk-bar-uso i { display: block; height: 100%; background: var(--primary); border-radius: inherit; transition: width 250ms ease-out; }
.mk-bar-uso.lleno i { background: var(--accent); }

/* ---------- Tabla compacta ---------- */
.mk-table-wrap { overflow-x: auto; }
.mk-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mk-table th { text-align: left; font-size: 11.5px; font-weight: 650; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; padding: 9px 10px;
               border-bottom: 1px solid var(--border); background: var(--surface-2); white-space: nowrap; }
.mk-table th button { border: 0; background: none; color: inherit; font: inherit; text-transform: inherit; letter-spacing: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 3px; padding: 0; }
.mk-table th svg { width: 12px; height: 12px; }
.mk-table td { padding: 8px 10px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.mk-table tbody tr { cursor: pointer; transition: background var(--mk-t); }
.mk-table tbody tr:hover { background: var(--surface-2); }
.mk-table tbody tr.sel { background: var(--primary-soft); }
.mk-table td.num, .mk-table th.num { text-align: right; }
.mk-table .w-chk { width: 36px; }

/* ---------- Tarjetas de campaña ---------- */
.mk-camps { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
.mk-camp { display: flex; flex-direction: column; background: var(--surface); border: 1px solid var(--border); border-radius: var(--mk-r); box-shadow: var(--mk-sh);
           overflow: hidden; cursor: pointer; transition: box-shadow var(--mk-t), border-color var(--mk-t); }
.mk-camp:hover { box-shadow: var(--mk-sh-2); border-color: color-mix(in srgb, var(--primary) 30%, var(--border)); }
.mk-camp .img { position: relative; height: 112px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; color: var(--muted); }
.mk-camp .img img { width: 100%; height: 100%; object-fit: cover; }
.mk-camp .img svg { width: 30px; height: 30px; opacity: .5; }
.mk-camp .img .mk-badge { position: absolute; top: 8px; left: 8px; box-shadow: var(--mk-sh); }
.mk-camp .b { padding: 10px 12px 12px; display: flex; flex-direction: column; gap: 7px; }
.mk-camp .n { font-weight: 650; font-size: 14px; display: flex; gap: 6px; align-items: flex-start; }
.mk-camp .n span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.mk-camp .meta { display: flex; flex-direction: column; gap: 3px; font-size: 12px; color: var(--muted); }
.mk-camp .meta span { display: flex; gap: 5px; align-items: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mk-camp .meta svg { width: 13px; height: 13px; }
.mk-camp .stats { display: flex; gap: 12px; font-size: 12px; color: var(--muted); border-top: 1px solid var(--border); padding-top: 7px; }
.mk-camp .stats b { color: var(--text); font-weight: 650; }

/* ---------- Calendario ---------- */
.mk-cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-top: 1px solid var(--border); border-left: 1px solid var(--border); }
.mk-cal .d { min-height: 92px; padding: 6px; border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); font-size: 12px; display: flex; flex-direction: column; gap: 3px; }
.mk-cal .d.fuera { background: var(--surface-2); color: var(--muted); }
.mk-cal .d.hoy .num { background: var(--primary); color: #fff; border-radius: 999px; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; }
.mk-cal .dow { padding: 6px; font-size: 11px; font-weight: 650; color: var(--muted); text-transform: uppercase; border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); background: var(--surface-2); }
.mk-cal .ev { display: block; width: 100%; text-align: left; border: 0; border-left: 3px solid var(--primary); background: var(--primary-soft); color: var(--text); font: inherit;
              font-size: 11.5px; padding: 3px 5px; border-radius: 4px; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mk-cal .ev.enviada { border-left-color: var(--green); background: var(--green-soft); }
@media (max-width: 700px) { .mk-cal .d { min-height: 56px; padding: 3px; } .mk-cal .ev { font-size: 0; height: 6px; padding: 0; border-left-width: 0; } }

/* ---------- Diálogos: modal y panel lateral ---------- */
dialog.mk-dlg, dialog.mk-drawer { border: 0; padding: 0; color: var(--text); background: var(--surface); box-shadow: var(--mk-sh-2); }
dialog.mk-dlg::backdrop, dialog.mk-drawer::backdrop { background: rgba(15, 23, 42, .4); }
dialog.mk-dlg { border-radius: 14px; width: min(980px, calc(100vw - 32px)); max-height: calc(100dvh - 40px); }
dialog.mk-dlg.chico { width: min(480px, calc(100vw - 32px)); }
dialog.mk-dlg[open] { animation: mk-pop 200ms cubic-bezier(.2,.8,.2,1); }
dialog.mk-drawer { margin: 0 0 0 auto; height: 100dvh; max-height: 100dvh; width: min(480px, 100vw); border-radius: 14px 0 0 14px; }
dialog.mk-drawer[open] { animation: mk-slide 220ms cubic-bezier(.2,.8,.2,1); display: flex; flex-direction: column; }
.mk-dh { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid var(--border); }
.mk-dh h2 { margin: 0; font-size: 15.5px; font-weight: 650; flex: 1; min-width: 0; display: flex; align-items: center; gap: 8px; }
.mk-db { padding: 14px 16px; overflow: auto; flex: 1; }
.mk-df { display: flex; gap: 8px; justify-content: flex-end; padding: 12px 16px; border-top: 1px solid var(--border); flex-wrap: wrap; }
@keyframes mk-pop { from { opacity: 0; transform: translateY(8px) scale(.985); } }
@keyframes mk-slide { from { transform: translateX(40px); opacity: 0; } }
@keyframes mk-in { from { opacity: 0; transform: translateY(-3px); } }
.mk-fade { animation: mk-in 180ms ease-out; }

/* Pestañas internas del panel */
.mk-itabs { display: flex; gap: 2px; border-bottom: 1px solid var(--border); margin: 0 -16px 12px; padding: 0 16px; overflow-x: auto; }
.mk-itabs button { border: 0; background: none; padding: 8px 10px; font: inherit; font-size: 13px; color: var(--muted); border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; }
.mk-itabs button[aria-selected="true"] { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }
.mk-mini { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
.mk-mini div { padding: 9px 10px; border: 1px solid var(--border); border-radius: var(--mk-r-sm); }
.mk-mini b { display: block; font-size: 17px; letter-spacing: -.01em; }
.mk-mini span { font-size: 11.5px; color: var(--muted); }
.mk-tl { list-style: none; margin: 0; padding: 0; }
.mk-tl li { padding: 7px 0 7px 14px; border-left: 2px solid var(--border); position: relative; font-size: 13px; }
.mk-tl li::before { content: ''; position: absolute; left: -5px; top: 12px; width: 8px; height: 8px; border-radius: 50%; background: var(--surface); border: 2px solid var(--border); }
.mk-tl small { display: block; color: var(--muted); font-size: 11.5px; }

/* ---------- Formularios ---------- */
.mk-f { display: flex; flex-direction: column; gap: 5px; margin-bottom: 12px; min-width: 0; }
.mk-f > label, .mk-lbl { font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
.mk-in { width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border); border-radius: var(--mk-r-sm); background: var(--surface); color: var(--text); font: inherit; font-size: 14px; }
textarea.mk-in { height: auto; min-height: 84px; padding: 8px 10px; resize: vertical; }
.mk-in:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
.mk-in.err { border-color: var(--danger); }
.mk-err { color: var(--danger); font-size: 12px; min-height: 0; }
.mk-err:empty { display: none; }
.mk-hint { font-size: 12px; color: var(--muted); margin: 0; }
.mk-affix { position: relative; }
.mk-affix .mk-in { padding-right: 34px; }
.mk-affix span { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 13px; pointer-events: none; }
.mk-affix.pre .mk-in { padding-left: 24px; padding-right: 10px; } .mk-affix.pre span { left: 10px; right: auto; }
.mk-r2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.mk-r3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
@media (max-width: 640px) { .mk-r2, .mk-r3 { grid-template-columns: 1fr; } }
.mk-switch { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--border); cursor: pointer; font-size: 13.5px; }
.mk-switch:last-child { border-bottom: 0; }
.mk-switch small { display: block; color: var(--muted); font-size: 12px; }
.mk-switch input { appearance: none; width: 36px; height: 20px; border-radius: 999px; background: var(--border); position: relative; cursor: pointer; flex: 0 0 auto; transition: background var(--mk-t); margin: 0; }
.mk-switch input::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform var(--mk-t); }
.mk-switch input:checked { background: var(--primary); }
.mk-switch input:checked::after { transform: translateX(16px); }
.mk-opts { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; }
.mk-opt { position: relative; display: flex; flex-direction: column; gap: 3px; padding: 10px 11px; border: 1px solid var(--border); border-radius: var(--mk-r-sm);
          background: var(--surface); cursor: pointer; text-align: left; font: inherit; color: var(--text); transition: border-color var(--mk-t), background var(--mk-t); }
.mk-opt:hover { border-color: color-mix(in srgb, var(--primary) 45%, var(--border)); }
.mk-opt[aria-pressed="true"], .mk-opt:has(input:checked) { border-color: var(--primary); background: var(--primary-soft); }
.mk-opt input { position: absolute; opacity: 0; pointer-events: none; }
.mk-opt b { font-size: 13px; display: flex; align-items: center; gap: 6px; }
.mk-opt span { font-size: 12px; color: var(--muted); }
.mk-opt .cnt { font-size: 18px; font-weight: 700; color: var(--text); }
.mk-ac { position: relative; }
.mk-ac-list { position: absolute; z-index: 70; left: 0; right: 0; top: calc(100% + 4px); background: var(--surface); border: 1px solid var(--border); border-radius: var(--mk-r-sm);
              box-shadow: var(--mk-sh-2); max-height: 240px; overflow: auto; display: none; }
.mk-ac-list.on { display: block; }
.mk-ac-item { display: flex; justify-content: space-between; gap: 10px; padding: 8px 10px; font-size: 13px; cursor: pointer; }
.mk-ac-item:hover, .mk-ac-item.act { background: var(--surface-2); }
.mk-ac-item small { color: var(--muted); }
.mk-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }

/* ---------- Asistente por pasos ---------- */
.mk-wz { display: grid; grid-template-columns: minmax(0, 1fr) 300px; min-height: 0; }
.mk-wz-main { padding: 16px 18px; overflow: auto; max-height: calc(100dvh - 190px); }
.mk-wz-side { border-left: 1px solid var(--border); background: var(--surface-2); padding: 16px; }
.mk-steps { display: flex; gap: 4px; padding: 0 16px; border-bottom: 1px solid var(--border); overflow-x: auto; }
.mk-step { display: inline-flex; align-items: center; gap: 7px; padding: 10px 8px; border: 0; background: none; font: inherit; font-size: 13px; color: var(--muted);
           border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; }
.mk-step .b { width: 20px; height: 20px; border-radius: 50%; border: 1.5px solid currentColor; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
.mk-step[aria-current="step"] { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }
.mk-step.hecho { color: var(--text); }
.mk-step.hecho .b { background: var(--primary); border-color: var(--primary); color: #fff; }
.mk-step.mal .b { background: var(--danger); border-color: var(--danger); color: #fff; }
.mk-panel[hidden] { display: none; }
.mk-panel { animation: mk-in 180ms ease-out; }
.mk-h4 { font-size: 13px; font-weight: 650; margin: 4px 0 8px; color: var(--text); }
@media (max-width: 820px) {
    dialog.mk-dlg { width: 100vw; max-height: 100dvh; height: 100dvh; border-radius: 0; margin: 0; max-width: 100vw; }
    .mk-wz { grid-template-columns: 1fr; }
    .mk-wz-side { border-left: 0; border-top: 1px solid var(--border); order: -1; padding: 0; }
    .mk-wz-side > details > summary { padding: 10px 16px; cursor: pointer; font-size: 13px; font-weight: 600; }
    .mk-wz-side > details > div { padding: 0 16px 14px; }
    .mk-wz-main { max-height: none; }
    dialog.mk-dlg[open] { display: flex; flex-direction: column; }
    dialog.mk-dlg form { display: flex; flex-direction: column; flex: 1; min-height: 0; }
    .mk-wz { flex: 1; overflow: auto; }
}
@media (min-width: 821px) { .mk-wz-side > details > summary { display: none; } }

/* ---------- WhatsApp ---------- */
.mk-wa { background: #efeae2; border-radius: var(--mk-r); padding: 14px 12px; position: relative; }
.mk-wa .etq { position: absolute; top: 8px; right: 8px; }
.mk-wa-b { background: #fff; color: #111b21; border-radius: 8px 8px 8px 0; max-width: 290px; box-shadow: 0 1px .5px rgba(11,20,26,.13); overflow: hidden; }
.mk-wa-b.borrador { outline: 1.5px dashed rgba(17,27,33,.25); outline-offset: 3px; }
.mk-wa-b img { display: block; width: 100%; max-height: 150px; object-fit: cover; background: #e9edef; }
.mk-wa-t { padding: 7px 9px 2px; font-size: 13.5px; line-height: 1.4; white-space: pre-wrap; word-wrap: break-word; }
.mk-wa-h { text-align: right; font-size: 11px; color: #667781; padding: 0 8px 6px; display: flex; justify-content: flex-end; gap: 3px; align-items: center; }
.mk-wa-h svg { width: 14px; height: 14px; color: #53bdeb; }
.mk-wa-btn { border-top: 1px solid #e9edef; padding: 8px; text-align: center; font-size: 13.5px; color: #027eb5; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 6px; }
.mk-wa-btn svg { width: 14px; height: 14px; }

/* ---------- Estados ---------- */
.mk-empty { text-align: center; padding: 42px 16px; }
.mk-empty .ic { width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 14px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; }
.mk-empty .ic svg { width: 24px; height: 24px; }
.mk-empty h3 { margin: 0 0 4px; font-size: 15px; }
.mk-empty p { margin: 0 0 14px; color: var(--muted); font-size: 13px; }
.mk-empty.err .ic { background: var(--danger-soft); color: var(--danger); }
.mk-sk { background: var(--surface-2); border-radius: 6px; }
.mk-sk-card { height: 132px; border-radius: var(--mk-r); border: 1px solid var(--border); background: linear-gradient(90deg, var(--surface) 0 96px, var(--surface-2) 96px); }
.mk-bulk { position: sticky; bottom: 12px; z-index: 20; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 12px; padding: 8px 10px 8px 14px;
           background: var(--text); color: var(--surface); border-radius: var(--mk-r); box-shadow: var(--mk-sh-2); animation: mk-in 180ms ease-out; }
.mk-bulk .mk-btn.sec { background: transparent; color: var(--surface); border-color: color-mix(in srgb, var(--surface) 35%, transparent); }
.mk-bulk .mk-btn.sec:hover { background: color-mix(in srgb, var(--surface) 12%, transparent); }
.mk-pag { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 12px; flex-wrap: wrap; font-size: 12.5px; color: var(--muted); }
.mk-pag .btns { display: flex; gap: 4px; }

/* Aviso dentro de un diálogo abierto */
.mk-toast { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 10; max-width: min(420px, calc(100vw - 32px));
            padding: 10px 14px; border-radius: 10px; background: var(--text); color: var(--surface); font-size: 13.5px; box-shadow: var(--mk-sh-2); animation: mk-in 180ms ease-out; }
.mk-toast.warn { background: var(--accent); color: #fff; }

/* Tooltip breve */
[data-tip-mk] { position: relative; }
[data-tip-mk]:hover::after, [data-tip-mk]:focus-visible::after { content: attr(data-tip-mk); position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%);
    background: var(--text); color: var(--surface); font-size: 11.5px; font-weight: 500; padding: 4px 7px; border-radius: 5px; white-space: nowrap; z-index: 80; pointer-events: none; animation: mk-in 150ms ease-out; }

@media (max-width: 640px) {
    .mk-head h1 { font-size: 17px; }
    .mk-ticket { grid-template-columns: 82px 1fr; }
    .mk-ticket .l .v { font-size: 21px; }
    dialog.mk-drawer { width: 100vw; border-radius: 0; }
    .mk-search { max-width: none; flex-basis: 100%; }
    .mk-bar .mk-chips { overflow-x: auto; flex-wrap: nowrap; width: 100%; padding-bottom: 2px; scrollbar-width: none; }
    .mk-bar .mk-chips::-webkit-scrollbar { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    .mk *, .mk *::before, .mk *::after, dialog.mk-dlg, dialog.mk-drawer { animation: none !important; transition: none !important; }
}
</style>

<script>
window.MK = window.MK || (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const I = {
        tag: '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.2"/>',
        megafono: '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        mas: '<circle cx="12" cy="5" r="1.4" fill="currentColor"/><circle cx="12" cy="12" r="1.4" fill="currentColor"/><circle cx="12" cy="19" r="1.4" fill="currentColor"/>',
        copiar: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        ver: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        editar: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        duplicar: '<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
        pausa: '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>',
        play: '<polygon points="6 4 20 12 6 20 6 4"/>',
        lista: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        cal: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        usuarios: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        usuario: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        globo: '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        plus: '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        x: '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        flecha: '<polyline points="9 18 15 12 9 6"/>', atras: '<polyline points="15 18 9 12 15 6"/>',
        arriba: '<polyline points="18 15 12 9 6 15"/>', abajo: '<polyline points="6 9 12 15 18 9"/>',
        alerta: '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h16.9a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        reintentar: '<polyline points="23 4 23 10 17 10"/><path d="M20.5 15a9 9 0 1 1-2.1-9.4L23 10"/>',
        busca: '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        externo: '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        imagen: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        reloj: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        clic: '<path d="M9 9l5 12 1.8-5.2L21 14z"/><path d="M7.2 2.2L8 5.1"/><path d="M5.1 8l-2.9-.8"/><path d="M14 4.1L12 6"/>',
        ventas: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        check2: '<polyline points="18 7 9.5 15.5 6 12"/><polyline points="22 7 13.5 15.5"/>',
        cancelar: '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
    };
    const ic = (n, cls = '') => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="${cls}">${I[n] || ''}</svg>`;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const money = (n, dec = 2) => '$' + (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    // Con un panel o modal abierto (capa superior del navegador) el aviso y la
    // confirmación globales quedarían debajo; se dibujan dentro del diálogo abierto.
    const dialogoArriba = () => [...document.querySelectorAll('dialog[open]')].pop();
    function toast(m, t = 'ok') {
        const d = dialogoArriba();
        if (!d) return window.showToast ? window.showToast(m, t) : console.log(m);
        const el = document.createElement('div');
        el.className = 'mk-toast ' + t; el.setAttribute('role', 'status'); el.textContent = m;
        d.appendChild(el); setTimeout(() => el.remove(), 3400);
    }
    function confirmar({ title = 'Confirmar', message = '', confirmText = 'Aceptar', cancelText = 'Cancelar', danger = false } = {}) {
        if (!dialogoArriba() && window.confirmModal) return window.confirmModal({ title, message, confirmText, cancelText, danger });
        return new Promise(res => {
            const d = document.createElement('dialog');
            d.className = 'mk-dlg chico';
            d.setAttribute('aria-labelledby', 'mkConfT');
            d.innerHTML = `<div class="mk-dh"><h2 id="mkConfT"></h2></div><div class="mk-db" style="font-size:14px"></div>
                <div class="mk-df"><button type="button" class="mk-btn sec" data-r="0"></button><button type="button" class="mk-btn" data-r="1"></button></div>`;
            d.querySelector('h2').textContent = title; d.querySelector('.mk-db').textContent = message;
            d.querySelector('[data-r="0"]').textContent = cancelText; const ok = d.querySelector('[data-r="1"]'); ok.textContent = confirmText;
            if (danger) { ok.style.background = 'var(--danger)'; ok.style.borderColor = 'var(--danger)'; }
            (document.querySelector('.mk') || document.body).appendChild(d);
            const fin = v => { d.close(); d.remove(); res(v); };
            d.addEventListener('click', e => { const b = e.target.closest('[data-r]'); if (b) fin(b.dataset.r === '1'); else if (e.target === d) fin(false); });
            d.addEventListener('cancel', e => { e.preventDefault(); fin(false); });
            d.showModal(); ok.focus();
        });
    }

    async function api(url, { method = 'GET', body } = {}) {
        const r = await fetch(url, {
            method, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : undefined,
        });
        let j = null;
        try { j = await r.json(); } catch (e) { /* sin cuerpo */ }
        if (!r.ok) {
            const err = new Error(j?.message || (r.status === 403 ? 'No tienes permiso para esta acción.' : 'No se pudo completar. Intenta de nuevo.'));
            err.status = r.status; err.errores = j?.errors || {};
            throw err;
        }
        return j;
    }

    function debounce(fn, ms = 300) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

    async function copiar(texto) {
        try { await navigator.clipboard.writeText(texto); toast('Código ' + texto + ' copiado.'); }
        catch (e) {
            const ta = document.createElement('textarea'); ta.value = texto; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); toast('Código ' + texto + ' copiado.'); } catch (e2) { toast('No se pudo copiar.', 'warn'); }
            ta.remove();
        }
    }

    // ---- Menús contextuales (botón [data-mk-menu] dentro de .mk-menu) ----
    function cerrarMenus(excepto) {
        document.querySelectorAll('.mk-menu.on').forEach(m => { if (m !== excepto) { m.classList.remove('on'); m.querySelector('[data-mk-menu]')?.setAttribute('aria-expanded', 'false'); } });
    }
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-mk-menu]');
        if (b) {
            e.stopPropagation(); e.preventDefault();
            const m = b.closest('.mk-menu'); const abrir = !m.classList.contains('on');
            cerrarMenus(m); m.classList.toggle('on', abrir); b.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            if (abrir) m.querySelector('.mk-mi')?.focus();
            return;
        }
        if (!e.target.closest('.mk-menu-panel')) cerrarMenus();
    });
    document.addEventListener('keydown', e => {
        const m = document.querySelector('.mk-menu.on');
        if (!m) return;
        const items = [...m.querySelectorAll('.mk-mi')];
        const i = items.indexOf(document.activeElement);
        if (e.key === 'Escape') { e.preventDefault(); cerrarMenus(); m.querySelector('[data-mk-menu]')?.focus(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length]?.focus(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length]?.focus(); }
    });
    document.addEventListener('click', e => { if (e.target.closest('.mk-mi')) setTimeout(cerrarMenus, 0); });

    // ---- Diálogos: devuelven el foco al botón que los abrió ----
    function abrir(dlg, origen) {
        dlg._origen = origen || document.activeElement;
        if (!dlg.open) dlg.showModal();
    }
    function cerrar(dlg) {
        if (dlg.open) dlg.close();
        dlg._origen?.focus?.();
    }
    document.addEventListener('click', e => {
        const c = e.target.closest('[data-mk-cerrar]');
        if (c) { const d = c.closest('dialog'); d && (d._antesDeCerrar ? d._antesDeCerrar() : cerrar(d)); }
        // Clic en el fondo cierra
        if (e.target.matches?.('dialog.mk-drawer, dialog.mk-dlg')) { const d = e.target; d._antesDeCerrar ? d._antesDeCerrar() : cerrar(d); }
    });

    function vacio({ icono = 'tag', titulo, texto = '', boton = '', accion = '' }) {
        return `<div class="mk-empty mk-fade"><div class="ic">${ic(icono)}</div><h3>${esc(titulo)}</h3>${texto ? `<p>${esc(texto)}</p>` : '<p></p>'}${boton ? `<button type="button" class="mk-btn" data-accion="${accion}">${ic('plus')}${esc(boton)}</button>` : ''}</div>`;
    }
    function error(texto) {
        return `<div class="mk-empty err mk-fade" role="alert"><div class="ic">${ic('alerta')}</div><h3>No se pudo cargar</h3><p>${esc(texto)}</p><button type="button" class="mk-btn sec" data-accion="reintentar">${ic('reintentar')}Reintentar</button></div>`;
    }

    // Estado en la URL: volver del detalle conserva filtros y página.
    const url = {
        leer: () => Object.fromEntries(new URLSearchParams(location.search)),
        poner(o) {
            const p = new URLSearchParams();
            Object.entries(o).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined && v !== false) p.set(k, v === true ? 1 : v); });
            history.replaceState(null, '', location.pathname + (p.toString() ? '?' + p : ''));
        },
    };

    return { api, debounce, copiar, ic, esc, money, toast, confirmar, abrir, cerrar, vacio, error, url, csrf };
})();
</script>
