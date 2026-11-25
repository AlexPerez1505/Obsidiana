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
        /* Un paso ya completado se queda iluminado en azul aunque te regreses. */
        .paso-chip[data-estado="listo"] { border-color:var(--primary); color:var(--text);
                                          background:var(--primary-soft); }
        .paso-chip[data-estado="listo"] .n { background:var(--primary); color:#fff; }
        /* Ya lo viste pero le falta algo: se avisa aquí, no hasta el final. */
        .paso-chip[data-estado="falta"] { border-color:var(--danger); color:var(--text); }
        .paso-chip[data-estado="falta"] .n { background:var(--danger); color:#fff; }
        @media (max-width:640px) { .paso-chip .txt { display:none; } .paso-chip { padding:8px 10px; } }

        /* Lo que falta en este paso, dicho en el paso mismo. */
        .paso-faltan { margin:0 0 16px; padding:12px 14px; border:1px solid var(--danger);
                       border-radius:10px; background:var(--danger-soft); color:var(--danger);
                       font-size:13.5px; }
        .paso-faltan b { display:block; margin-bottom:4px; }
        .paso-faltan ul { margin:0; padding-left:18px; }
        .paso-faltan li { margin-top:2px; }

        /* Al volver del servidor con error: los archivos hay que re-adjuntarlos. */
        .aviso-archivos { margin:0 0 18px; padding:13px 15px; border:1px solid var(--warn, #d97706);
                          border-radius:10px; background:var(--warn-soft, rgba(217,119,6,.1));
                          font-size:13.5px; line-height:1.5; }

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
        .generar-series .btn[hidden] { display:none; }

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

        /* ===================== Una pieza, una evidencia =====================
           Un bloque por cada pieza que llegó: su serie, sus fotos y su
           video. No hay evidencia "del lote". */
        .pieza-card { border:1px solid var(--border); border-radius:12px; background:var(--surface);
                      padding:16px 18px; margin-bottom:14px; }
        .pieza-card:last-child { margin-bottom:0; }
        .pieza-head { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .pieza-num { font-size:14px; font-weight:700; }
        .pieza-estado { margin-left:auto; font-size:12px; font-weight:600; padding:3px 10px;
                        border-radius:999px; background:var(--danger-soft); color:var(--danger); }
        .pieza-estado.listo { background:var(--primary-soft); color:var(--primary); }

        .pieza-serie { width:100%; padding:10px 12px; margin-bottom:12px; border:1px solid var(--border);
                       border-radius:9px; background:var(--surface); color:var(--text);
                       font-family:inherit; font-size:14px; }
        .pieza-serie:focus { border-color:var(--primary); outline:none; }

        .pieza-medios { display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:start; }
        .soltar--chico { padding:16px 14px; gap:4px; }
        .soltar--chico .ico svg { width:20px; height:20px; }
        .soltar--chico .t { font-size:13px; }
        .soltar--chico .d { font-size:11.5px; }
        .pieza-card .miniaturas { grid-template-columns:repeat(auto-fill, minmax(70px, 1fr)); margin-top:8px; }
        .pieza-progreso { margin-top:8px; }

        @media (max-width:640px) {
            .pieza-medios { grid-template-columns:1fr; }
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

        /* Fechas opcionales con botón para dejarlas vacías */
        .fecha-limpiable { display:flex; align-items:center; gap:6px; }
        .fecha-limpiable input { flex:1; min-width:0; }
        .fecha-limpiar { flex:0 0 34px; height:34px; border:1px solid var(--border); border-radius:8px;
                         background:var(--surface); color:var(--muted); font-size:18px; line-height:1; cursor:pointer; }
        .fecha-limpiar:hover { color:var(--danger); border-color:var(--danger); }

        /* Checklist armado por la IA y puntos agregados a mano */
        .chk-origen { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:10px 0 4px;
                      padding:10px 13px; border:1px solid var(--border); border-radius:10px;
                      background:var(--surface-2); color:var(--muted); font-size:13px; }
        .chk-origen .link { margin-left:auto; background:none; border:0; padding:0; cursor:pointer; font-size:13px; }
        [data-chk-lista].cargando { opacity:.5; pointer-events:none; }
        .chk-punto.manual .txt { font-style:italic; }
        .chk-quitar { margin-left:6px; border:0; background:none; color:var(--muted); font-size:16px;
                      line-height:1; cursor:pointer; padding:0 2px; }
        .chk-quitar:hover { color:var(--danger); }
        .chk-agregar { margin-top:18px; padding-top:14px; border-top:1px dashed var(--border); }
        .chk-agregar h4 { margin:0 0 8px; }
        .chk-agregar-fila { display:flex; gap:8px; flex-wrap:wrap; }
        .chk-agregar-fila input { flex:2 1 240px; min-width:0; }
        .chk-agregar-fila select { flex:1 1 200px; min-width:0; }

        /* Fotos que se están subiendo, o que el navegador no puede dibujar */
        .mini .mini-ext { display:flex; align-items:center; justify-content:center; width:100%; height:100%;
                          background:var(--surface-2); color:var(--muted); font-size:12px; font-weight:700; }
        .mini .mini-estado { position:absolute; left:0; right:0; bottom:0; padding:3px 0; text-align:center;
                             background:rgba(0,0,0,.55); color:#fff; font-size:11px; font-weight:600; }
        .mini.subiendo img { opacity:.6; }
        .mini.con-error { outline:2px solid var(--danger); }

        /* ===== Paneles compactos del registro de entrada ===== */
        .entrada-panel {
            position:relative;
            overflow:hidden;
            border-color:#294b76;
            border-left:3px solid #1683f7;
            border-radius:10px;
            background:linear-gradient(135deg,#0a1c34 0%,#0d2748 100%);
            box-shadow:0 8px 22px rgba(0,0,0,.12);
        }
        .entrada-panel::before {
            content:"";
            position:absolute;
            inset:0;
            pointer-events:none;
            background:radial-gradient(circle at 96% 8%, rgba(22,131,247,.12), transparent 26%);
        }
        .entrada-panel > * { position:relative; z-index:1; }
        .entrada-panel-head {
            display:flex;
            align-items:flex-start;
            gap:10px;
            margin:0 0 14px;
        }
        .entrada-panel-head .section-title { color:#f4f8ff; }
        .entrada-panel-head .campo-nota { color:#9db9d8; }
        .entrada-panel-icon {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:30px;
            height:30px;
            flex:0 0 30px;
            border-radius:9px;
            background:rgba(22,131,247,.24);
            color:#77bdff;
        }
        .entrada-panel-icon svg { width:17px; height:17px; }
        .entrada-panel-chevron {
            margin-left:auto;
            color:#b6cbe4;
            font-size:22px;
            line-height:25px;
        }
        .entrada-panel--condicion .opciones { gap:12px; }
        .entrada-panel .opcion {
            border-color:#315b8b;
            background:rgba(5,20,40,.55);
        }
        .entrada-panel .opcion:hover { border-color:#5c9bd2; }
        .entrada-panel .opcion:has(input:checked) {
            border-color:#1683f7;
            background:linear-gradient(135deg,rgba(0,104,224,.62),rgba(8,45,87,.72));
            box-shadow:0 0 0 1px rgba(22,131,247,.55), 0 7px 18px rgba(0,95,190,.18);
        }
        .entrada-panel .opcion .ico {
            background:#123a67;
            color:#a8cfff;
        }
        .entrada-panel .opcion:has(input:checked) .ico {
            background:#1683f7;
            color:#fff;
        }
        .entrada-panel .opcion .t { color:#f7fbff; }
        .entrada-panel .opcion .d { color:#a6c0dd; }
        .entrada-panel--llego .rgrid-campos { gap:0 16px; }
        .entrada-panel--llego label { color:#d9e7f7; }
        .entrada-panel--llego input,
        .entrada-panel--llego select,
        .entrada-panel--llego textarea {
            border-color:#315b8b;
            background:rgba(5,20,40,.55);
            color:#edf6ff;
        }
        .entrada-panel--llego input::placeholder,
        .entrada-panel--llego textarea::placeholder { color:#8faac7; }
        .entrada-description-panel {
            margin-top:18px;
            padding:12px 14px 14px;
            border:1px solid #294b76;
            border-radius:9px;
            background:rgba(5,20,40,.32);
        }
        .entrada-panel-head--small { margin-bottom:8px; }
        .entrada-description-grid {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:0 18px;
        }
        .entrada-description-grid .form-group { min-width:0; }

        :root[data-theme="light"] .entrada-panel {
            border-color:#b9d0e8;
            border-left-color:#1683f7;
            background:linear-gradient(135deg,#eef6ff 0%,#e5f0fb 100%);
            box-shadow:0 8px 22px rgba(30,64,100,.08);
        }
        :root[data-theme="light"] .entrada-panel::before { background:radial-gradient(circle at 96% 8%,rgba(22,131,247,.09),transparent 26%); }
        :root[data-theme="light"] .entrada-panel-head .section-title { color:#172b47; }
        :root[data-theme="light"] .entrada-panel-head .campo-nota { color:#607a98; }
        :root[data-theme="light"] .entrada-panel-chevron { color:#5d7897; }
        :root[data-theme="light"] .entrada-panel .opcion { border-color:#b8cde2; background:#f8fbff; }
        :root[data-theme="light"] .entrada-panel .opcion:has(input:checked) {
            border-color:#1683f7;
            background:linear-gradient(135deg,#d9edff,#eff8ff);
            box-shadow:0 0 0 1px rgba(22,131,247,.3), 0 7px 18px rgba(0,95,190,.1);
        }
        :root[data-theme="light"] .entrada-panel .opcion .t { color:#172b47; }
        :root[data-theme="light"] .entrada-panel .opcion .d { color:#647b95; }
        :root[data-theme="light"] .entrada-panel--llego label { color:#334e6d; }
        :root[data-theme="light"] .entrada-panel--llego input,
        :root[data-theme="light"] .entrada-panel--llego select,
        :root[data-theme="light"] .entrada-panel--llego textarea { border-color:#b8cde2; background:#fff; color:#172b47; }
        :root[data-theme="light"] .entrada-panel--llego input::placeholder,
        :root[data-theme="light"] .entrada-panel--llego textarea::placeholder { color:#8295aa; }
        :root[data-theme="light"] .entrada-description-panel { border-color:#c6d8ea; background:rgba(255,255,255,.62); }

        @media (max-width:640px) {
            .entrada-description-grid { grid-template-columns:1fr; }
        }

        /* Cabecera y pasos con el estilo compacto de Nueva entrada */
        .entrada-form-hero {
            display:flex;
            align-items:center;
            gap:10px;
            margin:0 0 12px;
            padding:0 2px;
        }
        .entrada-form-hero-icon {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:38px;
            height:38px;
            flex:0 0 38px;
            border:1px solid #1683f7;
            border-radius:10px;
            background:#0d315c;
            color:#69b8ff;
        }
        .entrada-form-hero-icon svg { width:22px; height:22px; }
        .entrada-form-hero h1 {
            margin:0;
            color:#f7fbff;
            font-size:22px;
            line-height:1.05;
            letter-spacing:-.02em;
        }
        .entrada-form-hero p {
            margin:3px 0 0;
            color:#a9c1dd;
            font-size:12px;
        }
        .pasos {
            position:relative;
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
            gap:0;
            align-items:center;
            margin:0 0 18px;
            padding:0 10px;
            overflow:visible;
        }
        .pasos::before {
            content:"";
            position:absolute;
            top:50%;
            left:10%;
            right:10%;
            height:1px;
            background:#49739f;
            transform:translateY(-50%);
        }
        .paso-chip {
            position:relative;
            z-index:1;
            justify-content:center;
            min-width:0;
            margin:0 5px;
            padding:5px 14px;
            border:1px solid #315b8b;
            border-radius:999px;
            background:#102746;
            color:#c5d6ea;
            font-size:12px;
            line-height:1.2;
        }
        .paso-chip .n {
            width:22px;
            height:22px;
            flex-basis:22px;
            background:#284a72;
            color:#e4f0ff;
            font-size:12px;
        }
        .paso-chip[data-estado="actual"] {
            border-color:#1683f7;
            background:linear-gradient(135deg,#082e59,#0d4f91);
            color:#fff;
            box-shadow:0 0 0 1px rgba(22,131,247,.3);
        }
        .paso-chip[data-estado="actual"] .n { background:#1683f7; color:#fff; }
        .paso-chip[data-estado="listo"] { background:#12345c; color:#fff; }
        .paso-chip[data-estado="falta"] { border-color:#f87171; background:#422036; color:#fff; }

        :root[data-theme="light"] .entrada-form-hero-icon { background:#e6f3ff; color:#0879df; }
        :root[data-theme="light"] .entrada-form-hero h1 { color:#172b47; }
        :root[data-theme="light"] .entrada-form-hero p { color:#607a98; }
        :root[data-theme="light"] .pasos::before { background:#a7bfd8; }
        :root[data-theme="light"] .paso-chip { border-color:#b8cde2; background:#f2f7fc; color:#48627f; }
        :root[data-theme="light"] .paso-chip .n { background:#d7e6f5; color:#365371; }
        :root[data-theme="light"] .paso-chip[data-estado="actual"] { border-color:#1683f7; background:#e3f2ff; color:#1269b3; }
        :root[data-theme="light"] .paso-chip[data-estado="actual"] .n { background:#1683f7; color:#fff; }
        :root[data-theme="light"] .paso-chip[data-estado="listo"] { border-color:#1683f7; background:#eaf5ff; color:#1269b3; }

        @media (max-width:640px) {
            .entrada-form-hero h1 { font-size:20px; }
            .pasos { grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); padding:0; }
            .paso-chip { margin:0 2px; padding:5px 8px; gap:5px; }
            .paso-chip .txt { display:inline; overflow:hidden; text-overflow:ellipsis; }
        }

        /* ===== Paso 2: piezas y evidencia con el mismo lenguaje visual ===== */
        section[data-paso="piezas"] { display:none; }
        section[data-paso="piezas"][data-activo] { display:grid; grid-template-columns:minmax(250px,.8fr) minmax(0,1.35fr); gap:16px; align-items:start; }
        section[data-paso="piezas"] > .entrada-paso-banner { grid-column:1 / -1; }
        .entrada-paso-banner { position:relative; overflow:hidden; display:flex; align-items:center; gap:16px; min-height:92px; padding:18px 22px; border:1px solid #294b76; border-radius:11px; background:linear-gradient(135deg,#0c2343 0%,#12345c 62%,#0b2343 100%); box-shadow:0 8px 22px rgba(0,0,0,.14); }
        .entrada-paso-banner::after { content:""; position:absolute; right:-10px; bottom:-42px; width:330px; height:130px; border:1px solid rgba(22,131,247,.34); border-radius:50%; transform:rotate(-17deg); pointer-events:none; }
        .entrada-paso-banner > * { position:relative; z-index:1; }
        .entrada-paso-banner-icon { display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; flex:0 0 52px; border:1px solid #1683f7; border-radius:12px; background:#0d315c; color:#75c1ff; box-shadow:0 0 12px rgba(22,131,247,.2); }
        .entrada-paso-banner-icon svg { width:30px; height:30px; }
        .entrada-paso-kicker { display:block; color:#53b3ff; font-size:12px; font-weight:800; letter-spacing:.08em; }
        .entrada-paso-banner h2 { margin:2px 0 3px; color:#f5f9ff; font-size:25px; line-height:1.05; letter-spacing:-.02em; }
        .entrada-paso-banner p { margin:0; color:#a9c3df; font-size:13px; }
        section[data-paso="piezas"] > .card { margin:0 !important; }
        section[data-paso="piezas"] .entrada-paso-intro,
        section[data-paso="piezas"] .entrada-paso-compartida,
        section[data-paso="piezas"] .entrada-paso-catalogo,
        section[data-paso="piezas"] .pieza-card { border-color:#294b76; border-radius:11px; background:linear-gradient(135deg,#0a1c34 0%,#0d2748 100%); box-shadow:0 8px 22px rgba(0,0,0,.12); }
        section[data-paso="piezas"] .entrada-paso-intro { grid-column:1; grid-row:2; }
        section[data-paso="piezas"] .entrada-paso-compartida { grid-column:1; grid-row:3; padding:16px 18px; }
        section[data-paso="piezas"] #piezas-rows { grid-column:2; grid-row:2; min-width:0; }
        section[data-paso="piezas"] .entrada-paso-catalogo { grid-column:2; grid-row:3; }
        .entrada-card-title { display:flex; align-items:center; gap:10px; }
        .entrada-card-title .section-title { flex:1; }
        .entrada-card-icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; flex:0 0 34px; border:1px solid #1683f7; border-radius:10px; background:#0d315c; color:#69b8ff; }
        .entrada-card-icon svg { width:20px; height:20px; }
        section[data-paso="piezas"] .entrada-paso-intro .section-title,
        section[data-paso="piezas"] .entrada-paso-catalogo .section-title { color:#f4f8ff; }
        section[data-paso="piezas"] .entrada-paso-intro .campo-nota,
        section[data-paso="piezas"] .entrada-paso-catalogo .campo-nota { color:#9db9d8; }
        section[data-paso="piezas"] .generar-series { margin-top:14px; border-color:#24558c; border-radius:10px; background:rgba(13,49,91,.48); }
        section[data-paso="piezas"] .generar-series .btn { display:inline-flex; align-items:center; gap:7px; width:100%; justify-content:center; }
        section[data-paso="piezas"] .entrada-inline-icon { display:block; width:17px; height:17px; flex:0 0 17px; color:#a9cfff; }
        section[data-paso="piezas"] .entrada-paso-compartida > label { display:flex; align-items:center; gap:9px; color:#edf5ff; font-weight:700; }
        section[data-paso="piezas"] .entrada-paso-compartida > label input { width:17px; height:17px; accent-color:var(--primary); }
        section[data-paso="piezas"] .entrada-paso-compartida > .campo-nota { margin:8px 0 0; color:#9db9d8; }
        section[data-paso="piezas"] .pieza-card { padding:14px 18px; margin-bottom:14px; }
        section[data-paso="piezas"] .pieza-head { margin-bottom:10px; }
        section[data-paso="piezas"] .pieza-num { display:flex; align-items:center; gap:9px; color:#f5f9ff; font-size:17px; }
        section[data-paso="piezas"] .pieza-num .entrada-card-icon { width:32px; height:32px; flex-basis:32px; }
        section[data-paso="piezas"] .pieza-estado { background:rgba(248,113,113,.14); color:#ff777c; }
        section[data-paso="piezas"] .pieza-serie { margin-bottom:12px; border-color:#24558c; background:#0c203b; color:#edf5ff; }
        section[data-paso="piezas"] .soltar { min-height:104px; padding:14px 12px; border-color:#315b8b; background:rgba(10,31,57,.72); }
        section[data-paso="piezas"] .soltar:hover, section[data-paso="piezas"] .soltar.encima { border-color:#1683f7; background:rgba(22,131,247,.14); }
        section[data-paso="piezas"] .soltar .t { color:#f5f9ff; }
        section[data-paso="piezas"] .soltar .d { color:#a8c0da; }
        section[data-paso="piezas"] .entrada-paso-catalogo input[type="file"] { border-color:#24558c; background:#0c203b; color:#edf5ff; }
        @media (max-width:900px) {
            section[data-paso="piezas"][data-activo] { grid-template-columns:1fr; }
            section[data-paso="piezas"] .entrada-paso-intro,
            section[data-paso="piezas"] .entrada-paso-compartida,
            section[data-paso="piezas"] #piezas-rows,
            section[data-paso="piezas"] .entrada-paso-catalogo { grid-column:1; grid-row:auto; }
        }
        @media (prefers-reduced-motion:reduce) { .entrada-paso-banner::after { display:none; } }

        /* ===== Paso 3: firma y resumen ===== */
        section[data-paso="firma"][data-activo] {
            display:grid;
            grid-template-columns:minmax(0,1.55fr) minmax(290px,.85fr);
            gap:16px;
            align-items:start;
            padding:18px;
            border:1px solid #294b76;
            border-radius:11px;
            background:linear-gradient(135deg,#0a1c34 0%,#0d2748 100%);
            box-shadow:0 8px 22px rgba(0,0,0,.12);
        }
        section[data-paso="firma"] > .entrada-firma-banner { grid-column:1 / -1; }
        section[data-paso="firma"] > .card { margin:0 !important; }
        .entrada-firma-banner {
            position:relative;
            overflow:hidden;
            display:flex;
            align-items:center;
            gap:16px;
            min-height:92px;
            padding:18px 22px;
            border:0;
            border-radius:11px;
            background:linear-gradient(135deg,rgba(12,35,67,.8) 0%,rgba(18,52,92,.8) 62%,rgba(11,35,67,.8) 100%);
            box-shadow:none;
        }
        .entrada-firma-banner::after {
            content:"";
            position:absolute;
            right:-10px;
            bottom:-42px;
            width:330px;
            height:130px;
            border:1px solid rgba(22,131,247,.34);
            border-radius:50%;
            transform:rotate(-17deg);
            pointer-events:none;
        }
        .entrada-firma-banner > * { position:relative; z-index:1; }
        .entrada-firma-banner-icon {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:52px;
            height:52px;
            flex:0 0 52px;
            border:1px solid #1683f7;
            border-radius:12px;
            background:#0d315c;
            color:#75c1ff;
            box-shadow:0 0 12px rgba(22,131,247,.2);
        }
        .entrada-firma-banner-icon svg { width:30px; height:30px; }
        .entrada-firma-kicker {
            display:block;
            color:#53b3ff;
            font-size:12px;
            font-weight:800;
            letter-spacing:.08em;
        }
        .entrada-firma-banner h2 {
            margin:2px 0 3px;
            color:#f5f9ff;
            font-size:25px;
            line-height:1.05;
            letter-spacing:-.02em;
        }
        .entrada-firma-banner p { margin:0; color:#a9c3df; font-size:13px; }
        section[data-paso="firma"] .entrada-firma-panel,
        section[data-paso="firma"] .entrada-firma-resumen {
            border:0;
            border-radius:11px;
            background:rgba(6,22,42,.32);
            box-shadow:none;
        }
        section[data-paso="firma"] .entrada-firma-panel { grid-column:1; grid-row:2; padding:18px; }
        section[data-paso="firma"] .entrada-firma-resumen { grid-column:2; grid-row:2; padding:18px; }
        section[data-paso="firma"] .entrada-firma-panel .section-title,
        section[data-paso="firma"] .entrada-firma-resumen .section-title { color:#f4f8ff; }
        section[data-paso="firma"] .entrada-firma-panel .section-title::before,
        section[data-paso="firma"] .entrada-firma-resumen .section-title::before {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:30px;
            height:30px;
            margin-right:9px;
            border:1px solid #1683f7;
            border-radius:9px;
            background:#0d315c;
            color:#69b8ff;
            font-size:18px;
            vertical-align:middle;
        }
        section[data-paso="firma"] .entrada-firma-panel .section-title::before { content:"✎"; }
        section[data-paso="firma"] .entrada-firma-resumen .section-title::before { content:"▣"; }
        section[data-paso="firma"] .entrada-firma-panel .campo-nota { color:#9db9d8; }
        section[data-paso="firma"] .signature-box {
            height:290px;
            border:1px dashed #315b8b;
            border-radius:11px;
            background:#fff;
        }
        section[data-paso="firma"] .resumen {
            display:grid;
            grid-template-columns:1fr;
            gap:0;
            background:transparent;
            border:0;
            border-radius:0;
        }
        section[data-paso="firma"] .resumen > div {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            min-height:52px;
            padding:10px 0;
            border-bottom:1px solid #294b76;
            background:transparent;
        }
        section[data-paso="firma"] .resumen > div:last-child { border-bottom:0; }
        section[data-paso="firma"] .resumen .e {
            display:block;
            color:#b9cce2;
            font-size:13px;
            font-weight:500;
            letter-spacing:0;
            text-transform:none;
        }
        section[data-paso="firma"] .resumen .e::before { display:inline-block; width:25px; margin-right:7px; color:#91b9e1; font-size:16px; text-align:center; vertical-align:middle; }
        section[data-paso="firma"] .resumen > div:nth-child(1) .e::before { content:"◇"; }
        section[data-paso="firma"] .resumen > div:nth-child(2) .e::before { content:"▱"; }
        section[data-paso="firma"] .resumen > div:nth-child(3) .e::before { content:"↗"; }
        section[data-paso="firma"] .resumen > div:nth-child(4) .e::before { content:"▧"; }
        section[data-paso="firma"] .resumen .v {
            margin:0;
            padding:8px 12px;
            border-radius:10px;
            background:#132b4f;
            color:#f5f9ff;
            font-size:13px;
            font-weight:700;
            text-align:right;
        }
        section[data-paso="firma"] .firma-accion { display:inline-flex; align-items:center; gap:7px; min-height:36px; padding:8px 13px; }
        section[data-paso="firma"] .firma-accion svg { width:17px; height:17px; }
        @media (max-width:900px) {
            section[data-paso="firma"][data-activo] { grid-template-columns:1fr; }
            section[data-paso="firma"] .entrada-firma-panel,
            section[data-paso="firma"] .entrada-firma-resumen { grid-column:1; grid-row:auto; }
        }
        @media (max-width:640px) {
            .entrada-firma-banner { align-items:flex-start; padding:16px; }
            .entrada-firma-banner h2 { font-size:21px; }
            .entrada-firma-banner p { font-size:12px; }
            section[data-paso="firma"] .signature-box { height:220px; }
        }
        @media (prefers-reduced-motion:reduce) { .entrada-firma-banner::after { display:none; } }

        /* ===== Variantes del paso 2 y 3 para modo claro ===== */
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-banner,
        :root[data-theme="light"] .entrada-firma-banner {
            border-color:#b8d2ec;
            background:linear-gradient(135deg,#f2f8ff 0%,#e3f1ff 62%,#f2f8ff 100%);
            box-shadow:0 8px 22px rgba(35,86,132,.12);
        }
        :root[data-theme="light"] .entrada-paso-banner::after,
        :root[data-theme="light"] .entrada-firma-banner::after { border-color:rgba(22,131,247,.22); }
        :root[data-theme="light"] .entrada-paso-banner-icon,
        :root[data-theme="light"] .entrada-firma-banner-icon,
        :root[data-theme="light"] .entrada-card-icon {
            border-color:#1683f7;
            background:#e1f1ff;
            color:#0879df;
            box-shadow:none;
        }
        :root[data-theme="light"] .entrada-paso-kicker,
        :root[data-theme="light"] .entrada-firma-kicker { color:#0879df; }
        :root[data-theme="light"] .entrada-paso-banner h2,
        :root[data-theme="light"] .entrada-firma-banner h2 { color:#172b47; }
        :root[data-theme="light"] .entrada-paso-banner p,
        :root[data-theme="light"] .entrada-firma-banner p { color:#607a98; }
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-intro,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-compartida,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-catalogo,
        :root[data-theme="light"] section[data-paso="piezas"] .pieza-card,
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-panel,
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-resumen {
            border-color:#b8d2ec;
            background:linear-gradient(135deg,#f7fbff 0%,#eaf3fb 100%);
            box-shadow:0 8px 22px rgba(35,86,132,.1);
        }
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-intro .section-title,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-catalogo .section-title,
        :root[data-theme="light"] section[data-paso="piezas"] .pieza-num,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-compartida > label,
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-panel .section-title,
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-resumen .section-title { color:#172b47; }
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-intro .campo-nota,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-catalogo .campo-nota,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-compartida > .campo-nota,
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-panel .campo-nota { color:#607a98; }
        :root[data-theme="light"] section[data-paso="piezas"] .generar-series { border-color:#b8d2ec; background:#edf6ff; }
        :root[data-theme="light"] section[data-paso="piezas"] .pieza-serie,
        :root[data-theme="light"] section[data-paso="piezas"] .entrada-paso-catalogo input[type="file"] { border-color:#b8d2ec; background:#fff; color:#172b47; }
        :root[data-theme="light"] section[data-paso="piezas"] .soltar { border-color:#9fc2e5; background:#f5faff; }
        :root[data-theme="light"] section[data-paso="piezas"] .soltar:hover,
        :root[data-theme="light"] section[data-paso="piezas"] .soltar.encima { border-color:#1683f7; background:#e6f3ff; }
        :root[data-theme="light"] section[data-paso="piezas"] .soltar .t { color:#172b47; }
        :root[data-theme="light"] section[data-paso="piezas"] .soltar .d { color:#607a98; }
        :root[data-theme="light"] section[data-paso="firma"][data-activo] { border-color:#b8d2ec; background:linear-gradient(135deg,#f7fbff 0%,#eaf3fb 100%); box-shadow:0 8px 22px rgba(35,86,132,.1); }
        :root[data-theme="light"] section[data-paso="firma"] .signature-box { border-color:#9fc2e5; background:#fff; }
        :root[data-theme="light"] section[data-paso="firma"] .resumen > div { border-bottom-color:#c5d8ea; }
        :root[data-theme="light"] section[data-paso="firma"] .resumen .e { color:#526f90; }
        :root[data-theme="light"] section[data-paso="firma"] .resumen .v { background:#e6f1fb; color:#172b47; }
        :root[data-theme="light"] section[data-paso="firma"] .resumen .e::before { color:#5d7d9f; }
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-panel .section-title::before,
        :root[data-theme="light"] section[data-paso="firma"] .entrada-firma-resumen .section-title::before { border-color:#9fc2e5; background:#e1f1ff; color:#0879df; }
    </style>
@endpush
