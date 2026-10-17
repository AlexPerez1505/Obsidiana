{{--
    Estilos y ventanas del módulo de garantías. Se incluye una vez por
    pantalla; todo usa los tokens del layout (claro y oscuro).

    Ventanas: <button data-abrir="idDialogo"> abre un <dialog id="idDialogo" class="gt-modal">,
    y cualquier [data-cerrar] dentro lo cierra.
--}}
@once
@include('partials.tabla-filtrable.estilos')
<style>
    .gt-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:16px; }
    .gt-kpis .stat-ico svg { width:20px; height:20px; }
    .gt-kpis .stat-lbl small { display:block; font-size:12px; }
    .gt-kpi-link { text-decoration:none; color:inherit; }
    .gt-kpi-link:hover .card { border-color:var(--primary); }

    .gt-2 { display:grid; grid-template-columns:minmax(0,1.6fr) minmax(0,1fr); gap:18px; align-items:start; }
    .gt-2b { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:18px; align-items:start; }
    .gt-col { display:grid; gap:18px; min-width:0; }
    .gt-mb { margin-bottom:18px; }

    .gt-kv { margin:0; padding:4px 18px 10px; display:grid; }
    .gt-kv > div { display:flex; justify-content:space-between; gap:16px; padding:9px 0; border-bottom:1px solid var(--border); }
    .gt-kv > div:last-child { border-bottom:0; }
    .gt-kv dt { color:var(--muted); font-size:13px; flex:0 0 auto; }
    .gt-kv dd { margin:0; font-size:13.5px; font-weight:500; text-align:right; overflow-wrap:anywhere; }

    .gt-txt { padding:12px 18px 16px; font-size:13.5px; line-height:1.55; white-space:pre-line; }
    .gt-txt h4 { margin:0 0 4px; font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; }
    .gt-txt + .gt-txt { border-top:1px solid var(--border); }
    .gt-muted { color:var(--muted); }
    .gt-s { color:var(--muted); font-size:12.5px; }
    .gt-num { font-variant-numeric:tabular-nums; }
    .gt-mono { font-variant-numeric:tabular-nums; letter-spacing:.01em; }
    .gt-vacio { padding:22px 18px; color:var(--muted); font-size:13.5px; text-align:center; }

    /* Lista de renglones dentro de una tarjeta */
    .gt-fila { display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--border);
               color:inherit; text-decoration:none; }
    .gt-fila:last-child { border-bottom:0; }
    a.gt-fila:hover { background:var(--surface-2); }
    .gt-fila .t { font-weight:600; font-size:14px; }
    .gt-fila .s { color:var(--muted); font-size:12.5px; margin-top:1px; }
    .gt-fila .der { margin-left:auto; text-align:right; flex:0 0 auto; display:flex; align-items:center; gap:8px; }
    .gt-fila > div:first-child { min-width:0; }

    /* Búsqueda grande por serie */
    .gt-buscar { display:flex; gap:8px; margin-bottom:16px; }
    .gt-buscar .f-search { flex:1; max-width:none; }
    .gt-rapidos { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }

    /* Pestañas de vista (abiertas, atrasadas...) */
    .gt-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
    .gt-tab { display:inline-flex; align-items:center; gap:7px; padding:7px 13px; border-radius:8px; font-size:13.5px;
              border:1px solid var(--border); background:var(--surface); color:var(--text); text-decoration:none; }
    .gt-tab:hover { background:var(--surface-2); }
    .gt-tab.on { border-color:var(--primary); color:var(--primary); background:var(--primary-soft); font-weight:600; }
    .gt-tab .n { font-size:11.5px; color:var(--muted); font-weight:600; }
    .gt-tab.on .n { color:var(--primary); }

    /* Gráfica de barras sencilla */
    .gt-barras { display:flex; align-items:flex-end; gap:12px; height:170px; padding:18px 18px 0; }
    .gt-barra { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; height:100%; justify-content:flex-end; min-width:0; }
    .gt-barra .b { width:100%; max-width:46px; border-radius:6px 6px 2px 2px; background:var(--primary); opacity:.85;
                   min-height:3px; transition:opacity .15s; }
    .gt-barra:hover .b { opacity:1; }
    .gt-barra .v { font-size:12px; font-weight:600; font-variant-numeric:tabular-nums; }
    .gt-barras-mes { display:flex; gap:12px; padding:8px 18px 16px; }
    .gt-barras-mes span { flex:1; text-align:center; font-size:12px; color:var(--muted); text-transform:capitalize; }

    /* Flujo de atención (6 etapas) */
    .gt-pasos { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:6px; padding:16px 18px; }
    .gt-paso { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .gt-paso .barra { height:5px; border-radius:5px; background:var(--border); }
    .gt-paso.hecho .barra { background:var(--green); }
    .gt-paso.actual .barra { background:var(--primary); }
    .gt-paso .n { font-size:11.5px; color:var(--muted); }
    .gt-paso .t { font-size:12.5px; font-weight:600; line-height:1.25; }
    .gt-paso.pendiente .t { color:var(--muted); font-weight:500; }

    /* Bitácora */
    .gt-bitacora { list-style:none; margin:0; padding:6px 18px 12px; }
    .gt-bitacora li { position:relative; padding:9px 0 9px 20px; border-left:2px solid var(--border); margin-left:5px; }
    .gt-bitacora li::before { content:""; position:absolute; left:-6px; top:14px; width:10px; height:10px; border-radius:50%;
                              background:var(--surface); border:2px solid var(--muted); }
    .gt-bitacora li.e-creada::before, .gt-bitacora li.e-cerrada::before { border-color:var(--green); }
    .gt-bitacora li.e-anulada::before, .gt-bitacora li.e-seguridad::before { border-color:var(--danger); }
    .gt-bitacora li.e-aprobacion::before, .gt-bitacora li.e-fechas::before, .gt-bitacora li.e-dictamen::before { border-color:var(--accent); }
    .gt-bitacora li.e-portal::before { border-color:var(--primary); }
    .gt-bitacora .d { font-size:13.5px; line-height:1.45; }
    .gt-bitacora .m { font-size:12px; color:var(--muted); margin-top:2px; }

    /* Avisos dentro de la página */
    .gt-aviso { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-radius:10px; margin-bottom:14px;
                font-size:13.5px; background:var(--accent-soft); color:var(--text); border:1px solid transparent; }
    .gt-aviso.rojo { background:var(--danger-soft); }
    .gt-aviso.azul { background:var(--primary-soft); }
    .gt-aviso svg { width:18px; height:18px; flex:0 0 auto; margin-top:1px; }
    .gt-aviso b { display:block; }

    /* Archivos */
    .gt-archivos { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; padding:14px 18px; }
    .gt-arch { display:flex; flex-direction:column; gap:5px; text-decoration:none; color:inherit; min-width:0; }
    .gt-arch .pv { aspect-ratio:4/3; border-radius:8px; overflow:hidden; border:1px solid var(--border); background:var(--surface-2);
                   display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:12px; font-weight:600; }
    .gt-arch .pv img { width:100%; height:100%; object-fit:cover; display:block; }
    .gt-arch .nm { font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .gt-arch:hover .pv { border-color:var(--primary); }

    /* Componentes de una torre */
    .gt-torre { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:10px; padding:14px 18px; }
    .gt-comp { display:block; padding:12px 14px; border:1px solid var(--border); border-radius:10px; text-decoration:none; color:inherit; }
    .gt-comp:hover { border-color:var(--primary); }
    .gt-comp.yo { border-color:var(--primary); background:var(--primary-soft); }
    .gt-comp .t { font-weight:600; font-size:13.5px; }
    .gt-comp .s { color:var(--muted); font-size:12px; margin:2px 0 8px; }

    /* Formularios */
    .gt-form { padding:4px 18px 18px; }
    .gt-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0 16px; }
    .gt-form textarea { min-height:84px; }
    .gt-check { display:flex !important; align-items:flex-start; gap:9px; font-weight:500 !important; cursor:pointer; }
    .gt-check input { width:auto !important; margin:3px 0 0; }
    .gt-acciones { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; padding:14px 18px; border-top:1px solid var(--border); }

    /* Ventanas */
    .gt-modal { width:min(620px, calc(100vw - 24px)); max-height:88vh; padding:0; overflow:hidden;
                border:1px solid var(--border); border-radius:16px; background:var(--surface); color:var(--text); }
    .gt-modal::backdrop { background:rgba(15,23,42,.45); }
    .gt-modal form { display:flex; flex-direction:column; max-height:88vh; margin:0; }
    .gt-modal-head { display:flex; align-items:flex-start; gap:12px; padding:18px 22px 14px; border-bottom:1px solid var(--border); }
    .gt-modal-head > div { flex:1; min-width:0; }
    .gt-modal-head h2 { margin:0 0 3px; font-size:16px; font-weight:600; }
    .gt-modal-body { flex:1; overflow-y:auto; padding:4px 22px 18px; }
    .gt-modal-pie { display:flex; justify-content:flex-end; gap:10px; padding:14px 22px; border-top:1px solid var(--border); background:var(--surface-2); }

    .tbl-scroll { overflow-x:auto; }
    .gt-tabla { width:100%; border-collapse:collapse; }
    .gt-tabla td .s { color:var(--muted); font-size:12.5px; }
    .gt-tabla tr.anulada td { opacity:.55; }
    .gt-grupo td { background:var(--surface-2); font-size:12.5px; font-weight:600; color:var(--muted); padding-top:8px; padding-bottom:8px; }

    @media (max-width: 960px) {
        .gt-2, .gt-2b { grid-template-columns:1fr; }
        .gt-pasos { grid-template-columns:repeat(3, minmax(0,1fr)); row-gap:14px; }
    }
    @media (max-width: 640px) {
        .gt-buscar { flex-direction:column; }
        .gt-modal { width:100vw; max-width:100vw; max-height:100dvh; border-radius:0; }
        .gt-modal form { max-height:100dvh; }
        .gt-modal-pie { flex-direction:column-reverse; }
        .gt-modal-pie .btn { width:100%; text-align:center; }
        .gt-fila { flex-wrap:wrap; }
        .gt-fila .der { margin-left:0; }
        .gt-barras { gap:6px; } .gt-barras-mes { gap:6px; }
    }
</style>

@push('scripts')
<script>
(function () {
    document.addEventListener('click', function (e) {
        var abrir = e.target.closest('[data-abrir]');
        if (abrir) {
            var d = document.getElementById(abrir.getAttribute('data-abrir'));
            if (d && d.showModal) { e.preventDefault(); d.showModal(); }
            return;
        }
        var cerrar = e.target.closest('[data-cerrar]');
        if (cerrar) {
            var dlg = cerrar.closest('dialog');
            if (dlg) { e.preventDefault(); dlg.close(); }
        }
    });
})();
</script>
@endpush
@endonce
