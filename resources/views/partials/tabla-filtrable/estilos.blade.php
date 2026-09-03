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

        /* Accesos rápidos de estado */
        .flt-toggles { display:inline-flex; gap:6px; }
        .flt-tgl { display:inline-flex; align-items:center; justify-content:center; width:38px; height:36px;
                   border:1px solid var(--border); border-radius:10px; background:var(--surface);
                   color:var(--muted); cursor:pointer; transition:background .15s, color .15s, border-color .15s; }
        .flt-tgl svg { width:17px; height:17px; }
        .flt-tgl:hover { background:var(--surface-2); color:var(--text); }
        .flt-tgl.is-on { background:var(--primary); border-color:var(--primary); color:#fff; }

        /* ===================== Panel desplegable ===================== */
        .flt-panel { position:absolute; top:calc(100% + 8px); left:0; z-index:60; width:340px; max-width:88vw;
                     max-height:min(70vh,520px); overflow-y:auto; padding:20px 22px;
                     background:var(--surface); border:1px solid var(--border); border-radius:14px;
                     box-shadow:var(--shadow); }
        .flt-panel[hidden] { display:none; }
        .flt-group + .flt-group { margin-top:22px; }
        .flt-group h4 { margin:0 0 6px; padding-bottom:10px; border-bottom:1px solid var(--border);
                        font-size:15px; font-weight:700; color:var(--text); }

        .flt-opt { display:flex; align-items:center; gap:10px; padding:11px 0; cursor:pointer; }
        .flt-opt + .flt-opt { border-top:1px solid var(--border); }
        .flt-opt-txt { flex:1; min-width:0; font-size:14.5px; color:var(--text);
                       overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .flt-opt input[type="checkbox"] { flex:none; width:20px; height:20px; margin:0; cursor:pointer;
                                          accent-color:var(--primary); }

        .flt-dot { flex:none; width:11px; height:11px; border-radius:50%; }
        .flt-dot.c1 { background:#ef4444; }
        .flt-dot.c2 { background:#f59e0b; }
        .flt-dot.c3 { background:#10b981; }
        .flt-dot.c4 { background:#6366f1; }
        .flt-dot.c5 { background:#8b8f9a; }

        .flt-fechas { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px; }
        .flt-fechas input { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:9px;
                            background:var(--surface); color:var(--text); font-family:inherit; font-size:13.5px; outline:none; }
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

        /* ===================== Menú de tres puntos por fila ===================== */
        .row-menu { position:relative; display:inline-block; }
        .row-menu-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
                        padding:0; border:1px solid transparent; border-radius:8px; background:none;
                        color:var(--muted); cursor:pointer; transition:background .15s, color .15s, border-color .15s; }
        .row-menu-btn svg { width:18px; height:18px; }
        .row-menu-btn:hover { background:var(--surface-2); color:var(--text); }
        .row-menu-btn:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .row-menu-btn[aria-expanded="true"] { background:var(--surface-2); border-color:var(--border); color:var(--primary); }

        /* Se posiciona con 'fixed' desde JS para que no lo recorte el scroll de la tabla. */
        .row-menu-pop { position:fixed; z-index:80; display:flex; flex-direction:column; gap:2px;
                        min-width:176px; padding:6px; background:var(--surface);
                        border:1px solid var(--border); border-radius:11px; box-shadow:var(--shadow); }
        .row-menu-pop[hidden] { display:none; }
        .row-menu-pop a, .row-menu-pop button { display:flex; align-items:center; gap:10px; padding:9px 10px;
                          width:100%; border:0; background:none; border-radius:8px; color:var(--text);
                          font-family:inherit; font-size:13.5px; text-align:left; text-decoration:none;
                          white-space:nowrap; cursor:pointer; }
        .row-menu-pop svg { flex:none; width:16px; height:16px; color:var(--muted); }
        .row-menu-pop a:hover, .row-menu-pop button:hover { background:var(--surface-2); }
        .row-menu-pop a:hover svg, .row-menu-pop button:hover svg { color:var(--primary); }
        .row-menu-pop .es-danger { color:var(--danger); }
        .row-menu-pop .es-danger svg { color:var(--danger); }
        .row-menu-pop .es-danger:hover { background:var(--danger-soft); }
        .row-menu-pop .es-danger:hover svg { color:var(--danger); }
        .row-menu-pop a:focus-visible, .row-menu-pop button:focus-visible { outline:2px solid var(--primary); outline-offset:-2px; }

        .f-oculto { display:none !important; }
        .f-conteo { margin:14px 0 0; color:var(--muted); font-size:13px; }

        @media (prefers-reduced-motion:reduce) {
            .flt-btn, .flt-tgl, .row-menu-btn { transition:none; }
        }
    </style>
@endonce
