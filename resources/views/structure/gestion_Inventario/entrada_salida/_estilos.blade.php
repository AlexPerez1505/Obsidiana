@push('head')
    <style>
        /* ===================== Pasos =====================
           El formulario pedía todo de golpe en una sola columna larguísima.
           Partido en pasos cabe en una pantalla de teléfono y se ve el
           avance. */
        .pasos { display:flex; gap:6px; margin-bottom:20px; overflow-x:auto;
                 scrollbar-width:none; -ms-overflow-style:none; padding-bottom:2px; }
        .pasos::-webkit-scrollbar { height:0; }
        .paso-chip { display:flex; align-items:center; gap:8px; padding:8px 13px;
                     border:1px solid var(--border); border-radius:9px; background:var(--surface);
                     color:var(--muted); font-size:13px; font-weight:500; white-space:nowrap;
                     cursor:pointer; flex:0 0 auto;
                     transition:border-color .16s ease, color .16s ease, background .16s ease; }
        .paso-chip .n { display:inline-flex; align-items:center; justify-content:center;
                        width:20px; height:20px; border-radius:50%; background:var(--surface-2);
                        color:var(--muted); font-size:11.5px; font-weight:700; flex:0 0 20px; }
        .paso-chip[data-estado="actual"] { border-color:var(--primary); color:var(--text); }
        .paso-chip[data-estado="actual"] .n { background:var(--primary); color:#fff; }
        .paso-chip[data-estado="listo"] { color:var(--text); }
        .paso-chip[data-estado="listo"] .n { background:var(--green); color:#fff; }
        @media (max-width:640px) { .paso-chip .txt { display:none; } .paso-chip { padding:8px 10px; } }

        .paso { display:none; }
        .paso[data-activo] { display:block; }

        .paso-nav { display:flex; align-items:center; gap:10px; margin-top:18px; }
        .paso-nav .cuenta { margin-right:auto; color:var(--muted); font-size:13px; }
        @media (max-width:640px) {
            .paso-nav { flex-wrap:wrap; }
            .paso-nav .cuenta { width:100%; margin:0 0 4px; }
            .paso-nav .btn { flex:1; justify-content:center; }
        }

        /* Campos cortos en rejilla: el formulario dejó de ser una columna
           interminable sin dejar de apilarse en el teléfono. */
        .rgrid-campos { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
                        gap:0 16px; }
        @media (max-width:560px) { .rgrid-campos { grid-template-columns:1fr; } }

        /* Ofrecer generar las series: es una salida, no un campo más. */
        .generar-series { display:flex; align-items:center; gap:14px; margin-top:16px;
                          padding:13px 15px; border:1px solid var(--border); border-radius:10px;
                          background:var(--surface-2); flex-wrap:wrap; }
        .generar-series .txt { flex:1; min-width:180px; font-size:13.5px; line-height:1.45; }
        .generar-series .txt b { display:block; }
        .generar-series .txt span { color:var(--muted); font-size:12.5px; }
        .generar-series .btn { flex:0 0 auto; }

        /* Precio ya registrado: se lee como dato, no como campo por llenar. */
        .precio-caja { display:flex; align-items:center; gap:10px; padding:8px 12px;
                       border:1px solid var(--border); border-radius:7px; background:var(--surface-2); }
        .precio-caja .v { flex:1; font-size:15px; font-weight:600; font-variant-numeric:tabular-nums; }
        .precio-caja .btn { padding:5px 11px; font-size:12.5px; }

        /* ===================== Nuevo o usado ===================== */
        .opciones { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px; }
        .opcion { position:relative; display:flex; gap:12px; padding:15px;
                  border:1px solid var(--border); border-radius:11px; background:var(--surface);
                  cursor:pointer; transition:border-color .16s ease, background .16s ease; }
        .opcion:hover { border-color:var(--muted); }
        .opcion input { position:absolute; opacity:0; pointer-events:none; }
        .opcion .ico { display:flex; align-items:center; justify-content:center;
                       width:38px; height:38px; border-radius:10px; flex:0 0 38px;
                       background:var(--surface-2); color:var(--muted); }
        .opcion .ico svg { width:19px; height:19px; }
        .opcion .t { display:block; font-weight:600; font-size:14.5px; }
        .opcion .d { display:block; margin-top:3px; color:var(--muted); font-size:12.5px; line-height:1.45; }
        .opcion:has(input:checked) { border-color:var(--primary); background:var(--primary-soft); }
        .opcion:has(input:checked) .ico { background:var(--primary); color:#fff; }

        /* ===================== Subir archivos ===================== */
        .soltar { display:flex; flex-direction:column; align-items:center; justify-content:center;
                  gap:8px; padding:26px 18px; border:1.5px dashed var(--border); border-radius:12px;
                  background:var(--surface-2); color:var(--muted); text-align:center;
                  cursor:pointer; transition:border-color .16s ease, background .16s ease; }
        .soltar:hover, .soltar.encima { border-color:var(--primary); background:var(--primary-soft); }
        .soltar .ico svg { width:26px; height:26px; }
        .soltar .t { color:var(--text); font-weight:600; font-size:14px; }
        .soltar .d { font-size:12.5px; }
        .soltar input[type=file] { display:none; }
        .soltar.lleno { border-style:solid; }

        .miniaturas { display:grid; grid-template-columns:repeat(auto-fill, minmax(96px, 1fr));
                      gap:10px; margin-top:12px; }
        .mini { position:relative; aspect-ratio:1; border-radius:10px; overflow:hidden;
                border:1px solid var(--border); background:var(--surface-2); }
        .mini img, .mini video { width:100%; height:100%; object-fit:cover; display:block; }
        .mini .quitar { position:absolute; top:5px; right:5px; width:24px; height:24px;
                        display:flex; align-items:center; justify-content:center;
                        border:0; border-radius:50%; background:rgba(15,23,42,.72); color:#fff;
                        font-size:14px; line-height:1; cursor:pointer; }
        .mini .quitar:hover { background:var(--danger); }

        /* ===================== Checklist ===================== */
        .chk-grupo + .chk-grupo { margin-top:20px; }
        .chk-grupo > h4 { margin:0 0 10px; font-size:11px; font-weight:800; letter-spacing:.07em;
                          text-transform:uppercase; color:var(--muted); }
        .chk-punto { display:flex; align-items:center; gap:12px; padding:11px 0;
                     border-bottom:1px solid var(--border); }
        .chk-punto:last-child { border-bottom:none; }
        .chk-punto .txt { flex:1; min-width:0; font-size:14px; line-height:1.4; }
        .chk-punto .nota { display:none; width:100%; margin-top:8px; }
        .chk-punto.con-nota { flex-wrap:wrap; }
        .chk-punto.con-nota .nota { display:block; }

        /* Sí / No / No aplica: tres botones, no tres radios sueltos. */
        .tri { display:inline-flex; flex:0 0 auto; border:1px solid var(--border);
               border-radius:8px; overflow:hidden; background:var(--surface); }
        .tri label { display:inline-flex; align-items:center; justify-content:center;
                     margin:0; padding:6px 11px; font-size:12.5px; font-weight:600;
                     color:var(--muted); cursor:pointer; border-left:1px solid var(--border);
                     transition:background .14s ease, color .14s ease; }
        .tri label:first-of-type { border-left:0; }
        .tri input { position:absolute; opacity:0; pointer-events:none; }
        .tri label:hover { background:var(--surface-2); }
        .tri input:checked + span { color:#fff; }
        .tri label:has(input[value="si"]:checked) { background:var(--green); color:#fff; }
        .tri label:has(input[value="no"]:checked) { background:var(--danger); color:#fff; }
        .tri label:has(input[value="na"]:checked) { background:var(--muted); color:#fff; }
        @media (max-width:520px) {
            .chk-punto { flex-wrap:wrap; }
            .chk-punto .txt { flex:1 0 100%; }
        }

        /* ===================== Unidades una por una ===================== */
        .unidad-row { display:grid; grid-template-columns:34px 1fr 170px; align-items:center;
                      gap:12px; padding:11px 0; border-bottom:1px solid var(--border); }
        .unidad-row:last-child { border-bottom:none; }
        .unidad-row .unidad-num { color:var(--muted); font-size:13px; font-weight:700; }
        .unidad-row input[type="file"] { padding:6px 9px; font-size:12.5px; }
        .unidad-foto-preview { width:44px; height:44px; object-fit:cover; border-radius:7px;
                               border:1px solid var(--border); display:none; margin-top:6px; }
        @media (max-width:640px) {
            .unidad-row { grid-template-columns:26px 1fr; }
            .unidad-row input[type="file"] { grid-column:2; }
        }

        /* ===================== Firma ===================== */
        /* Se traza sobre blanco aunque el sistema esté en oscuro: es tal
           cual lo que después se guarda como imagen. */
        .signature-box { width:100%; height:170px; border:1px solid var(--border);
                         border-radius:10px; background:#fff; touch-action:none; display:block; }

        .barra-progreso { height:8px; border-radius:6px; background:var(--surface-2); overflow:hidden; }
        .barra-progreso > span { display:block; height:100%; width:0; background:var(--primary);
                                 transition:width .15s ease; }

        /* Resumen de cierre */
        .resumen { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:1px;
                   background:var(--border); border:1px solid var(--border); border-radius:11px;
                   overflow:hidden; }
        .resumen > div { padding:13px 15px; background:var(--surface); }
        .resumen .e { display:block; color:var(--muted); font-size:11px; font-weight:700;
                      letter-spacing:.06em; text-transform:uppercase; }
        .resumen .v { display:block; margin-top:3px; font-size:15px; font-weight:600; }
    </style>
@endpush
