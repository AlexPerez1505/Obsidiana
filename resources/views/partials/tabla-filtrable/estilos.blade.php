{{--
    Estilos de un listado filtrable: barra de herramientas, panel de
    filtros, chips y menú de tres puntos por fila.

    Los usa Clientes y Fichas técnicas. Si se toca algo aquí, se toca en
    las dos pantallas a la vez, que es justo la idea.
--}}

@once
    <style>
        /* ===================== Barra de herramientas ===================== */
        .f-toolbar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .f-search { position:relative; flex:1; min-width:220px; }
        .f-search svg { position:absolute; left:11px; top:50%; transform:translateY(-50%);
                        width:16px; height:16px; color:var(--muted); pointer-events:none; }
        .f-search input { width:100%; padding:9px 12px 9px 34px; border:1px solid var(--border); border-radius:9px;
                          background:var(--surface); color:var(--text); font-family:inherit; font-size:13.5px; outline:none; }
        .f-search input:focus { border-color:var(--primary); }

        /* ===================== Botones de filtro ===================== */
        .flt { position:relative; }
        .flt-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 14px;
                   border:1px solid var(--border); border-radius:10px; background:var(--surface);
                   color:var(--text); font-family:inherit; font-size:13.5px; font-weight:600;
                   line-height:1; cursor:pointer; transition:background .15s, border-color .15s; }
        .flt-btn:hover { background:var(--surface-2); }
        .flt-btn svg { width:16px; height:16px; }
        .flt-btn[aria-expanded="true"] { border-color:var(--primary); color:var(--primary); }
        .flt-btn--icon { padding:9px 11px; color:var(--muted); }
        .flt-btn--icon:hover { color:var(--danger); border-color:var(--danger); }

        .flt-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px;
                     padding:0 5px; border-radius:9px; background:var(--primary); color:#fff;
                     font-size:11px; font-weight:700; }
        /* Sin esta regla el "display" de arriba gana sobre el [hidden] del
           navegador y el contador se quedaba pintado en 0 aunque no hubiera
           ningún filtro puesto. */
        .flt-count[hidden] { display:none; }

        /* Accesos rápidos de estado */
        .flt-toggles { display:inline-flex; gap:6px; }
        .flt-tgl { display:inline-flex; align-items:center; justify-content:center; width:38px; height:36px;
                   border:1px solid var(--border); border-radius:10px; background:var(--surface);
                   color:var(--muted); cursor:pointer; transition:background .15s, color .15s, border-color .15s; }
        .flt-tgl svg { width:17px; height:17px; }
        .flt-tgl:hover { background:var(--surface-2); color:var(--text); }
        .flt-tgl.is-on { background:var(--primary); border-color:var(--primary); color:#fff; }

        /* ===================== Panel desplegable ===================== */
        /*
        | El panel se ancla por la DERECHA del botón: la barra de filtros
        | vive del lado derecho de la pantalla, así que abriéndolo hacia la
        | izquierda siempre cabe. Con left:0 se salía del viewport.
        |
        | Y va en dos columnas cuando hay espacio: con cinco grupos de
        | opciones, una sola columna se volvía una tira larguísima que no
        | cabía a lo alto.
        */
        .flt-panel { position:absolute; top:calc(100% + 8px); right:0; left:auto; z-index:60;
                     width:min(460px, 92vw); max-height:min(70vh, 520px); overflow-y:auto;
                     padding:14px 16px; background:var(--surface); border:1px solid var(--border);
                     border-radius:14px; box-shadow:var(--shadow);
                     columns:2; column-gap:22px; }
        .flt-panel[hidden] { display:none; }

        @media (max-width:640px) {
            /* En el teléfono la barra se acomoda a la izquierda, así que el
               panel se ancla por ese lado: al revés se salía de pantalla. */
            .flt-panel { columns:1; left:0; right:auto; width:min(320px, calc(100vw - 32px)); }
        }

        /* Un grupo no se parte entre columnas. */
        .flt-group { break-inside:avoid; page-break-inside:avoid; display:inline-block; width:100%; }
        .flt-group + .flt-group { margin-top:14px; }
        .flt-group h4 { margin:0 0 2px; font-size:10.5px; font-weight:800; letter-spacing:.06em;
                        text-transform:uppercase; color:var(--muted); }

        /* Sin línea entre opciones: con pocas se veía bien, con muchas la
           lista se volvía una reja. El aire alcanza para separarlas. */
        .flt-opt { display:flex; align-items:center; gap:9px; padding:6px 0; cursor:pointer;
                   border-radius:6px; }
        .flt-opt:hover { background:var(--surface-2); }
        .flt-opt-txt { flex:1; min-width:0; font-size:13px; line-height:1.35; color:var(--text); }
        .flt-opt input[type="checkbox"] { flex:none; width:16px; height:16px; margin:0; cursor:pointer;
                                          accent-color:var(--primary); }

        .flt-dot { flex:none; width:9px; height:9px; border-radius:50%; }
        .flt-dot.c1 { background:#ef4444; }
        .flt-dot.c2 { background:#f59e0b; }
        .flt-dot.c3 { background:#10b981; }
        .flt-dot.c4 { background:#6366f1; }
        .flt-dot.c5 { background:#8b8f9a; }

        .flt-fechas { display:grid; gap:7px; margin-top:6px; }
        .flt-fechas input { width:100%; padding:7px 9px; border:1px solid var(--border); border-radius:8px;
                            background:var(--surface); color:var(--text); font-family:inherit; font-size:12.5px; outline:none; }
        .flt-fechas input:focus { border-color:var(--primary); }

        /* ===================== Chips de filtros activos ===================== */
        .flt-chips { display:flex; flex-wrap:wrap; gap:6px; margin:-6px 0 16px; }
        .flt-chips[hidden] { display:none; }
        .flt-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 8px 5px 10px;
                    border:1px solid var(--border); border-radius:999px; background:var(--surface-2);
                    color:var(--text); font-size:12.5px; }
        .flt-chip button { display:inline-flex; padding:0; border:0; background:none; color:var(--muted);
                           font-size:14px; line-height:1; cursor:pointer; }
        .flt-chip button:hover { color:var(--danger); }


        .f-oculto { display:none !important; }
        .f-conteo { margin:14px 0 0; color:var(--muted); font-size:13px; }

        @media (prefers-reduced-motion:reduce) {
            .flt-btn, .flt-tgl, .row-menu-btn { transition:none; }
        }
    </style>
@endonce
