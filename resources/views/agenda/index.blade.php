@extends('layouts.dashboard')
@section('title', 'Agenda')
@section('page-title', 'Agenda')

{{--
    Agenda empresarial y personal.

    El esqueleto vive aquí; lo dibuja public/js/agenda.js con los endpoints
    JSON de AgendaController (que validan permisos). Todo el estilo está
    encapsulado bajo .agx / .agx-capa (no toca el encabezado ni otros módulos).

    Zonas: navegación plegable (izquierda) · barra compacta + calendario
    (centro) · panel de detalle bajo demanda (derecha; hoja inferior en el
    celular). Tooltips, vistas previas, menús y popovers flotan en #agxCapa,
    fuera del flujo, para que nada los recorte.
--}}

@section('content')
<div class="agx" id="agx" data-live="off">
    {{-- ===================== Navegación lateral plegable ===================== --}}
    <nav class="agx-nav" id="agxNav" aria-label="Agendas y secciones">
        <button type="button" class="agx-nav-plegar" data-plegar data-tip="Contraer o expandir el panel" data-tip-atajo="[" aria-label="Contraer o expandir el panel lateral">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
        </button>

        <div class="agx-nav-g">
            <button type="button" class="agx-nav-i" data-ambito="mia" data-tip="Tus actividades, seguimientos y los eventos que te tocan">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.2-3.6 4-5.5 7-5.5s5.8 1.9 7 5.5"/></svg>
                <span>Mi agenda</span>
            </button>
            <button type="button" class="agx-nav-i" data-ambito="empresa" data-tip="Eventos de la empresa, departamentos y equipos">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12"/><path d="M9 20v-5h6v5"/></svg>
                <span>Empresa</span>
            </button>
            <button type="button" class="agx-nav-i" data-ambito="todas" data-tip="Tu agenda y la de la empresa juntas">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2.5"/><path d="M4 10h16M9 3v4M15 3v4"/></svg>
                <span>Todo junto</span>
            </button>
        </div>

        <div class="agx-nav-g">
            <span class="agx-nav-t">Trabajo</span>
            <button type="button" class="agx-nav-i" data-seccion="hoy" data-tip="Tu jornada: próxima actividad, pendientes y progreso" data-tip-atajo="H">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <span>Hoy</span><b class="agx-n" data-n="hoy"></b>
            </button>
            <button type="button" class="agx-nav-i" data-seccion="vencidos" data-tip="Tareas y seguimientos cuya fecha ya pasó">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5l3 2"/></svg>
                <span>Vencidos</span><b class="agx-n is-alerta" data-n="vencidos"></b>
            </button>
            <button type="button" class="agx-nav-i" data-seccion="sin" data-tip="Prospectos activos sin un siguiente paso programado">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="8" r="3.5"/><path d="M3.5 20c1-3.4 3.6-5.3 6.5-5.3"/><path d="M15 15l5 5M20 15l-5 5"/></svg>
                <span>Sin seguimiento</span><b class="agx-n" data-n="sin"></b>
            </button>
        </div>

        <div class="agx-nav-g agx-nav-cats">
            <span class="agx-nav-t">Calendarios</span>
            <div id="agxCats" role="group" aria-label="Mostrar u ocultar categorías"></div>
        </div>
    </nav>

    {{-- ===================== Zona principal ===================== --}}
    <section class="agx-main" aria-label="Calendario">
        <header class="agx-bar">
            <button type="button" class="agx-ico agx-solo-movil" data-abrir-nav aria-label="Secciones y calendarios">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
            </button>
            <div class="agx-periodo">
                <h2 id="agxTitulo" aria-live="polite">Agenda</h2>
                <span id="agxSub"></span>
            </div>
            <div class="agx-navfechas">
                <button type="button" class="agx-ico" data-nav="-1" aria-label="Periodo anterior" data-tip="Anterior" data-tip-atajo="←">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                </button>
                <button type="button" class="agx-btn" data-hoy data-tip="Ir a hoy" data-tip-atajo="T">Hoy</button>
                <button type="button" class="agx-ico" data-nav="1" aria-label="Periodo siguiente" data-tip="Siguiente" data-tip-atajo="→">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>

            <div class="agx-bar-der">
                <div class="agx-seg agx-solo-escritorio" role="group" aria-label="Vista">
                    <button type="button" data-vista="mes" data-tip="Vista mensual" data-tip-atajo="M">Mes</button>
                    <button type="button" data-vista="semana" data-tip="Vista semanal" data-tip-atajo="S">Semana</button>
                    <button type="button" data-vista="dia" data-tip="Vista de un día" data-tip-atajo="D">Día</button>
                    <button type="button" data-vista="lista" data-tip="Lista de los próximos 30 días" data-tip-atajo="L">Lista</button>
                </div>
                <div class="agx-buscar" id="agxBuscarCaja">
                    <button type="button" class="agx-ico" data-buscar aria-label="Buscar" data-tip="Buscar en la agenda" data-tip-atajo="/">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
                    </button>
                    <input type="search" id="agxBuscar" placeholder="Título, cliente o motivo…" autocomplete="off" aria-label="Buscar en la agenda">
                </div>
                <button type="button" class="agx-ico" data-filtros aria-haspopup="dialog" aria-expanded="false" aria-label="Filtros" data-tip="Filtros" data-tip-atajo="F">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    <b class="agx-cuenta" id="agxFiltrosN" hidden></b>
                </button>
                <button type="button" class="agx-ico" data-mas aria-haspopup="menu" aria-expanded="false" aria-label="Más opciones" data-tip="Más opciones">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/></svg>
                </button>
                <button type="button" class="agx-btn agx-btn--primario agx-crear" data-crear data-tip="Crear actividad" data-tip-atajo="C">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    <span>Crear</span>
                </button>
            </div>
        </header>

        {{-- Selector compacto de fecha (celular) --}}
        <div class="agx-tira agx-solo-movil" id="agxTira" aria-label="Días de la semana"></div>

        {{-- Filtros activos como chips --}}
        <div class="agx-chips" id="agxChips" hidden></div>

        <div class="agx-lienzo" id="agxLienzo">
            <div class="agx-cal" id="agxCal" tabindex="-1"></div>
            <div class="agx-guardando" id="agxGuardando" hidden role="status"><span class="agx-spin"></span><span>Guardando…</span></div>
        </div>
    </section>

    {{-- ===================== Panel de detalle (bajo demanda) ===================== --}}
    <aside class="agx-panel" id="agxPanel" role="dialog" aria-modal="false" aria-labelledby="agxPanelT" hidden>
        <div class="agx-agarre agx-solo-movil" aria-hidden="true"></div>
        <header class="agx-panel-h">
            <div class="agx-panel-tit">
                <span class="agx-panel-tipo" id="agxPanelTipo"></span>
                <h3 id="agxPanelT" tabindex="-1"></h3>
            </div>
            <div class="agx-panel-acc" id="agxPanelAcc"></div>
            <button type="button" class="agx-ico" data-cerrar aria-label="Cerrar panel" data-tip="Cerrar" data-tip-atajo="Esc">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </header>
        <div class="agx-panel-c" id="agxPanelC"></div>
    </aside>
    <div class="agx-velo" id="agxVelo" hidden></div>

    {{-- Botón flotante "Crear" en el celular --}}
    <button type="button" class="agx-fab agx-solo-movil" data-crear aria-label="Crear actividad">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    </button>

    {{-- Toast con "Deshacer" --}}
    <div class="agx-toast" id="agxToast" role="status" aria-live="polite" hidden>
        <span id="agxToastTxt"></span>
        <button type="button" id="agxToastAcc" hidden>Deshacer</button>
    </div>
</div>

<div class="agx-capa" id="agxCapa"></div>

<script>
    window.AGENDA = @json($config);
    window.AGENDA.abrir = @json($abrir);
    window.AGENDA.ambitoInicial = @json($ambito);
</script>
<script src="{{ asset('js/agenda.js') }}?v={{ @filemtime(public_path('js/agenda.js')) }}" defer></script>

<style>
/* =====================================================================
   Agenda · todo bajo .agx / .agx-capa
   ===================================================================== */
.agx, .agx-capa {
    --agx-r: 10px;
    --agx-hora: 48px;
    --agx-dur: 180ms; --agx-ease: cubic-bezier(.2,.7,.2,1);
    --agx-linea: color-mix(in srgb, var(--border) 85%, transparent);
    --agx-suave: color-mix(in srgb, var(--surface-2) 70%, var(--surface));
    --agx-sombra: 0 1px 2px rgba(15,23,42,.05), 0 8px 24px -8px rgba(15,23,42,.14);
    --agx-sombra-flot: 0 2px 6px rgba(15,23,42,.08), 0 18px 40px -12px rgba(15,23,42,.28);
    --agx-nav-w: 212px;
}
.agx *, .agx *::before, .agx *::after, .agx-capa *, .agx-capa *::before, .agx-capa *::after { box-sizing:border-box; }
.agx [hidden], .agx-capa [hidden] { display:none !important; }
.agx button, .agx-capa button, .agx input, .agx select, .agx textarea, .agx-capa input, .agx-capa select, .agx-capa textarea { font:inherit; color:inherit; }
.agx :focus-visible, .agx-capa :focus-visible { outline:2px solid var(--primary); outline-offset:1px; border-radius:6px; }
.agx svg, .agx-capa svg { width:16px; height:16px; flex:0 0 auto; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.agx-sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
.agx-muted { color:var(--muted); }

.agx {
    position:relative;
    display:grid; grid-template-columns: var(--agx-nav-w) minmax(0, 1fr);
    height: max(560px, calc(100dvh - 126px));
    border:1px solid var(--border); border-radius:14px; background:var(--surface);
    overflow:hidden; color:var(--text); font-size:13.5px;
    transition: grid-template-columns var(--agx-dur) var(--agx-ease);
}
.agx.is-plegada { --agx-nav-w: 56px; }

/* ---------- Botones base ---------- */
.agx-btn { display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 12px; border-radius:8px; border:1px solid var(--border); background:var(--surface); font-weight:600; font-size:13px; cursor:pointer; white-space:nowrap; transition: background var(--agx-dur), border-color var(--agx-dur), transform 80ms; }
.agx-btn:hover { background:var(--agx-suave); }
.agx-btn:active, .agx-ico:active, .agx-nav-i:active, .agx-fab:active { transform: translateY(1px) scale(.98); }
.agx-btn--primario { background:var(--primary); border-color:var(--primary); color:#fff !important; box-shadow:0 1px 2px color-mix(in srgb, var(--primary) 40%, transparent); }
.agx-btn--primario:hover { background:var(--primary-strong); }
.agx-btn--peligro { color:var(--danger) !important; }
.agx-btn--sm { height:28px; padding:0 10px; font-size:12.5px; }
.agx-btn[disabled] { opacity:.55; cursor:not-allowed; }
.agx-ico { position:relative; width:32px; height:32px; display:inline-grid; place-items:center; border-radius:8px; border:1px solid transparent; background:transparent; cursor:pointer; color:var(--muted); transition: background var(--agx-dur), color var(--agx-dur); }
.agx-ico:hover, .agx-ico[aria-expanded="true"] { background:var(--agx-suave); color:var(--text); }
.agx-ico svg { width:17px; height:17px; }
.agx-cuenta { position:absolute; top:2px; right:1px; min-width:15px; height:15px; padding:0 4px; border-radius:8px; background:var(--primary); color:#fff; font-size:10px; font-weight:700; display:grid; place-items:center; }

/* ---------- Navegación lateral ---------- */
.agx-nav { position:relative; display:flex; flex-direction:column; gap:14px; padding:10px 8px; border-right:1px solid var(--border); background:var(--agx-suave); overflow-y:auto; overflow-x:hidden; }
.agx-nav-plegar { align-self:flex-end; width:28px; height:28px; display:grid; place-items:center; border:0; border-radius:7px; background:none; color:var(--muted); cursor:pointer; }
.agx-nav-plegar:hover { background:var(--surface); color:var(--text); }
.agx-nav-plegar svg { transition: transform var(--agx-dur) var(--agx-ease); }
.agx.is-plegada .agx-nav-plegar { align-self:center; }
.agx.is-plegada .agx-nav-plegar svg { transform: rotate(180deg); }
.agx-nav-g { display:flex; flex-direction:column; gap:1px; }
.agx-nav-t { padding:4px 10px 6px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); white-space:nowrap; }
.agx-nav-i { position:relative; display:flex; align-items:center; gap:10px; width:100%; height:32px; padding:0 10px; border:0; border-radius:8px; background:none; color:var(--text); font-size:13.5px; font-weight:550; cursor:pointer; text-align:left; white-space:nowrap; transition: background var(--agx-dur), color var(--agx-dur); }
.agx-nav-i svg { color:var(--muted); width:17px; height:17px; }
.agx-nav-i:hover { background: color-mix(in srgb, var(--text) 5%, transparent); }
.agx-nav-i.is-on { background:var(--surface); color:var(--primary); box-shadow:0 1px 2px rgba(15,23,42,.06), 0 0 0 1px var(--border); }
.agx-nav-i.is-on svg { color:var(--primary); }
.agx-nav-i span { flex:1; overflow:hidden; text-overflow:ellipsis; }
.agx-n { min-width:20px; height:18px; padding:0 6px; border-radius:9px; font-size:11px; font-weight:700; display:grid; place-items:center; background:color-mix(in srgb, var(--text) 7%, transparent); color:var(--muted); }
.agx-n:empty { display:none; }
.agx-n.is-alerta { background:var(--danger-soft); color:var(--danger); }
.agx-cat { display:flex; align-items:center; gap:9px; width:100%; height:28px; padding:0 10px; border:0; border-radius:7px; background:none; cursor:pointer; font-size:13px; color:var(--text); text-align:left; white-space:nowrap; }
.agx-cat:hover { background: color-mix(in srgb, var(--text) 5%, transparent); }
.agx-cat-c { width:13px; height:13px; border-radius:4px; border:1.5px solid var(--c); background:var(--c); display:grid; place-items:center; transition: background var(--agx-dur); flex:0 0 13px; }
.agx-cat-c svg { width:9px !important; height:9px !important; color:#fff; stroke-width:3; transition: opacity var(--agx-dur); }
.agx-cat[aria-pressed="false"] .agx-cat-c { background:transparent; }
.agx-cat[aria-pressed="false"] .agx-cat-c svg { opacity:0; }
.agx-cat[aria-pressed="false"] span:last-child { color:var(--muted); }
.agx-cat span:last-child { overflow:hidden; text-overflow:ellipsis; }
.agx.is-plegada .agx-nav-i span, .agx.is-plegada .agx-nav-t, .agx.is-plegada .agx-cat span:last-child { display:none; }
.agx.is-plegada .agx-nav-i, .agx.is-plegada .agx-cat { justify-content:center; padding:0; }
.agx.is-plegada .agx-n { position:absolute; top:1px; right:2px; min-width:15px; height:15px; font-size:9.5px; padding:0 4px; }

/* ---------- Barra superior ---------- */
.agx-main { display:flex; flex-direction:column; min-width:0; min-height:0; }
.agx-bar { position:relative; display:flex; align-items:center; gap:10px; padding:9px 12px; border-bottom:1px solid var(--border); min-height:52px; }
.agx-periodo { display:flex; align-items:baseline; gap:8px; min-width:70px; flex:1 1 auto; overflow:hidden; }
.agx-periodo h2 { margin:0; font-size:17px; font-weight:700; letter-spacing:-.015em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.agx-periodo span { color:var(--muted); font-size:12.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
.agx-periodo h2 { flex:0 1 auto; min-width:40px; }
.agx-navfechas { display:flex; align-items:center; gap:2px; }
.agx-bar-der { margin-left:auto; display:flex; align-items:center; gap:6px; flex:0 0 auto; }
.agx-seg { display:inline-flex; padding:2px; border-radius:9px; background:var(--agx-suave); border:1px solid var(--border); margin-right:4px; }
.agx-seg button { height:26px; border:0; background:transparent; padding:0 11px; border-radius:7px; font-size:12.5px; font-weight:600; color:var(--muted); cursor:pointer; transition: background var(--agx-dur), color var(--agx-dur), box-shadow var(--agx-dur); }
.agx-seg button:hover { color:var(--text); }
.agx-seg button.is-on { background:var(--surface); color:var(--text); box-shadow:0 1px 2px rgba(15,23,42,.12); }
.agx-buscar { display:flex; align-items:center; height:32px; border-radius:8px; border:1px solid transparent; transition: border-color var(--agx-dur), background var(--agx-dur); }
.agx-buscar input { width:0; padding:0; border:0; background:transparent; outline:none; color:var(--text); font-size:13px; transition: width var(--agx-dur) var(--agx-ease), padding var(--agx-dur); }
.agx-buscar.is-abierto { border-color:var(--border); background:var(--surface); }
.agx-buscar.is-abierto input { width:210px; padding-right:8px; }
.agx-buscar.is-abierto .agx-ico { color:var(--primary); }
.agx-crear { margin-left:2px; }

/* ---------- Chips de filtros ---------- */
.agx-chips { display:flex; flex-wrap:wrap; align-items:center; gap:6px; padding:7px 12px; border-bottom:1px solid var(--border); background:var(--agx-suave); }
.agx-chip { display:inline-flex; align-items:center; gap:6px; height:24px; padding:0 4px 0 9px; border-radius:12px; background:var(--surface); border:1px solid var(--border); font-size:12px; max-width:260px; animation: agx-entra var(--agx-dur) var(--agx-ease); flex:0 0 auto; }
.agx-chip b { font-weight:600; color:var(--muted); }
.agx-chip span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.agx-chip button { width:18px; height:18px; display:grid; place-items:center; border:0; border-radius:9px; background:none; color:var(--muted); cursor:pointer; }
.agx-chip button:hover { background:var(--danger-soft); color:var(--danger); }
.agx-chip button svg { width:11px; height:11px; stroke-width:2.4; }
.agx-chip-limpiar { border:0; background:none; color:var(--primary); font-size:12px; font-weight:600; cursor:pointer; padding:0 4px; flex:0 0 auto; }

/* ---------- Lienzo ---------- */
.agx-lienzo { position:relative; flex:1; min-height:0; }
.agx-cal { position:absolute; inset:0; overflow:auto; outline:none; }
.agx-guardando { position:absolute; right:14px; bottom:14px; display:flex; align-items:center; gap:8px; padding:6px 12px; border-radius:16px; background:var(--surface); border:1px solid var(--border); box-shadow:var(--agx-sombra); font-size:12.5px; color:var(--muted); z-index:6; animation: agx-entra var(--agx-dur) var(--agx-ease); }
.agx-spin { display:inline-block; width:13px; height:13px; border-radius:50%; border:2px solid var(--border); border-top-color:var(--primary); animation: agx-giro .7s linear infinite; }

/* Colores de categoría (discretos; siempre acompañados de texto) */
.agx-t-seguimiento { --c:#3b82f6; } .agx-t-reunion { --c:#8b5cf6; } .agx-t-llamada { --c:#0ea5e9; }
.agx-t-visita { --c:#14b8a6; } .agx-t-demo { --c:#f59e0b; } .agx-t-entrega { --c:#22c55e; }
.agx-t-capacitacion { --c:#06b6d4; } .agx-t-servicio { --c:#f43f5e; } .agx-t-tarea_admin { --c:#64748b; }
.agx-t-evento_empresa { --c:#ec4899; } .agx-t-bloqueo { --c:#475569; } .agx-t-otro { --c:#94a3b8; }
.agx-t-congreso { --c:#6366f1; } .agx-t-ocupado { --c:#94a3b8; }

/* ---------- Evento ---------- */
.agx-ev { --c:#94a3b8; position:relative; display:flex; align-items:center; gap:5px; width:100%; min-width:0; height:21px; padding:0 6px 0 9px; border:0; border-radius:5px; background: color-mix(in srgb, var(--c) 11%, var(--surface)); font-size:12px; line-height:1.25; text-align:left; cursor:pointer; color:var(--text); flex:0 0 auto; transition: background var(--agx-dur), box-shadow var(--agx-dur), opacity var(--agx-dur); }
.agx-ev::before { content:''; position:absolute; left:2px; top:3px; bottom:3px; width:3px; border-radius:2px; background:var(--c); }
.agx-ev:hover, .agx-ev:focus-visible { background: color-mix(in srgb, var(--c) 19%, var(--surface)); box-shadow:0 1px 3px rgba(15,23,42,.10); }
.agx-ev-h { font-variant-numeric:tabular-nums; color: color-mix(in srgb, var(--c) 55%, var(--text)); font-weight:650; font-size:11px; flex:0 0 auto; }
.agx-ev-t { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:550; }
.agx-ev-i { display:inline-flex; gap:3px; align-items:center; flex:0 0 auto; color:var(--muted); }
.agx-ev-i svg { width:11px; height:11px; stroke-width:2.2; }
.agx-prio { width:6px; height:6px; border-radius:50%; background:var(--accent); flex:0 0 6px; }
.agx-ev.is-vencido { --c: var(--danger); }
.agx-ev.is-vencido .agx-ev-i { color:var(--danger); }
.agx-ev.is-cerrado { background: var(--agx-suave); }
.agx-ev.is-cerrado .agx-ev-t { color:var(--muted); text-decoration: line-through; text-decoration-color: color-mix(in srgb, var(--muted) 60%, transparent); }
.agx-ev.is-cerrado::before { opacity:.4; }
.agx-ev.is-pendiente-resp { background: repeating-linear-gradient(135deg, color-mix(in srgb, var(--c) 9%, var(--surface)) 0 5px, var(--surface) 5px 10px); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 40%, transparent); }
.agx-ev.is-ocupado { background: repeating-linear-gradient(135deg, var(--agx-suave) 0 5px, var(--surface) 5px 10px); cursor:default; color:var(--muted); }
.agx-ev.is-ocupado::before { background:var(--muted); }
.agx-ev[draggable="true"] { cursor:grab; }
.agx-ev.is-arrastrando { opacity:.35; }
.agx-ev.is-guardando { animation: agx-pulso 1s ease-in-out infinite; pointer-events:none; }
.agx-ev.is-hecho-ahora, .agx-fila.is-hecho-ahora { animation: agx-hecho 480ms var(--agx-ease); }

/* ---------- Mes ---------- */
.agx-mes { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); grid-template-rows: auto; grid-auto-rows: minmax(96px, 1fr); min-height:100%; }
.agx-mes-h { position:sticky; top:0; z-index:2; padding:7px 10px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); background:var(--surface); border-bottom:1px solid var(--border); }
.agx-dia { position:relative; display:flex; flex-direction:column; gap:2px; min-width:0; min-height:0; overflow:hidden; padding:4px 5px 5px; border-right:1px solid var(--agx-linea); border-bottom:1px solid var(--agx-linea); transition: background var(--agx-dur); }
.agx-dia:nth-child(7n) { border-right:0; }
.agx-dia.is-fuera { background:var(--agx-suave); }
.agx-dia.is-fuera .agx-dia-n { color:var(--muted); }
.agx-dia.is-finde:not(.is-fuera) { background: color-mix(in srgb, var(--surface-2) 35%, var(--surface)); }
.agx-dia-cab { display:flex; align-items:center; justify-content:space-between; height:24px; flex:0 0 24px; }
.agx-dia-n { min-width:24px; height:24px; padding:0 6px; display:grid; place-items:center; border-radius:12px; border:0; background:none; font-size:12.5px; font-weight:650; cursor:pointer; font-variant-numeric:tabular-nums; }
.agx-dia-n:hover { background: color-mix(in srgb, var(--text) 6%, transparent); }
.agx-dia.is-hoy .agx-dia-n { background:var(--primary); color:#fff; }
.agx-dia-crear { width:22px; height:22px; border:0; border-radius:6px; background:none; color:var(--muted); display:grid; place-items:center; cursor:pointer; opacity:0; transition: opacity var(--agx-dur), background var(--agx-dur); }
.agx-dia:hover .agx-dia-crear, .agx-dia-crear:focus-visible { opacity:1; }
.agx-dia-crear:hover { background: color-mix(in srgb, var(--primary) 10%, transparent); color:var(--primary); }
.agx-dia.is-destino, .agx-todo-dia.is-destino { background: var(--primary-soft); box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--primary) 55%, transparent); }
.agx-mas-n { align-self:flex-start; border:0; background:none; padding:1px 6px; border-radius:5px; font-size:11.5px; font-weight:650; color:var(--muted); cursor:pointer; flex:0 0 auto; }
.agx-mas-n:hover { background: color-mix(in srgb, var(--text) 6%, transparent); color:var(--text); }

/* ---------- Semana / día ---------- */
.agx-sem { display:grid; grid-template-columns:52px repeat(var(--dias), minmax(0,1fr)); position:sticky; top:0; z-index:5; background:var(--surface); border-bottom:1px solid var(--border); }
.agx-sem-h { padding:6px 4px 5px; text-align:center; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--muted); border:0; background:none; cursor:pointer; }
.agx-sem-h b { display:block; margin-top:1px; font-size:17px; letter-spacing:-.01em; color:var(--text); font-weight:650; text-transform:none; }
.agx-sem-h.is-hoy b { color:var(--primary); }
.agx-todo-dia { display:flex; flex-direction:column; gap:2px; padding:3px; min-height:28px; border-left:1px solid var(--agx-linea); border-top:1px solid var(--agx-linea); }
.agx-todo-lbl { font-size:10px; line-height:1.15; color:var(--muted); text-align:right; padding:6px 6px 0 0; border-top:1px solid var(--agx-linea); }
.agx-rej { display:grid; grid-template-columns:52px repeat(var(--dias), minmax(0,1fr)); position:relative; }
.agx-horas div { height:var(--agx-hora); position:relative; }
.agx-horas span { position:absolute; top:-7px; right:7px; font-size:10.5px; color:var(--muted); font-variant-numeric:tabular-nums; background:var(--surface); padding:0 2px; }
.agx-col { position:relative; border-left:1px solid var(--agx-linea); }
.agx-col.is-hoy { background: color-mix(in srgb, var(--primary) 2.5%, transparent); }
.agx-slot { height:var(--agx-hora); border-top:1px solid var(--agx-linea); cursor:cell; }
.agx-slot:hover { background: color-mix(in srgb, var(--primary) 4%, transparent); }
.agx-slot.is-destino { background: var(--primary-soft); }
.agx-col .agx-ev { position:absolute; flex-direction:column; align-items:stretch; justify-content:flex-start; gap:1px; height:auto; padding:3px 6px 3px 10px; border-radius:6px; overflow:hidden; z-index:2; box-shadow: 0 0 0 1px var(--surface); }
.agx-col .agx-ev:hover { z-index:3; }
.agx-col .agx-ev::before { top:4px; bottom:4px; }
.agx-col .agx-ev-t { white-space:normal; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; font-size:12px; line-height:1.3; flex:none; }
.agx-col .agx-ev.is-estrecho { padding:3px 4px 3px 8px; }
.agx-col .agx-ev.is-estrecho .agx-ev-h, .agx-col .agx-ev.is-estrecho .agx-ev-i, .agx-col .agx-ev.is-estrecho .agx-ev-meta { display:none; }
.agx-col .agx-ev.is-estrecho .agx-ev-t { -webkit-line-clamp:4; font-size:11.5px; overflow-wrap:anywhere; }
.agx-col .agx-ev.is-corto { flex-direction:row; align-items:center; }
.agx-col .agx-ev.is-corto .agx-ev-t { -webkit-line-clamp:1; }
.agx-col .agx-ev.is-corto .agx-ev-meta { display:none; }
.agx-ev-arriba { display:flex; align-items:center; gap:4px; min-width:0; }
.agx-ev-arriba .agx-ev-i { margin-left:auto; }
.agx-ev-meta { display:flex; align-items:center; gap:5px; font-size:11px; color:var(--muted); min-width:0; overflow:hidden; white-space:nowrap; }
.agx-ev-meta > span { overflow:hidden; text-overflow:ellipsis; }
.agx-ahora { position:absolute; left:-1px; right:0; height:2px; background:var(--danger); z-index:4; pointer-events:none; }
.agx-ahora::before { content:''; position:absolute; left:-4px; top:-3px; width:8px; height:8px; border-radius:50%; background:var(--danger); }

/* ---------- Listas (lista, hoy, vencidos, sin seguimiento) ---------- */
.agx-lst { max-width:900px; margin:0 auto; padding:4px 16px 90px; }
.agx-lst-dia { position:sticky; top:0; z-index:2; display:flex; align-items:baseline; gap:8px; padding:14px 6px 6px; background:var(--surface); font-size:12px; font-weight:600; color:var(--muted); }
.agx-lst-dia b { color:var(--text); font-size:13.5px; font-weight:700; }
.agx-lst-dia.is-hoy b { color:var(--primary); }
.agx-fila { --c:#94a3b8; position:relative; display:grid; grid-template-columns:22px 70px minmax(0,1fr) auto; gap:12px; align-items:center; width:100%; min-height:48px; padding:7px 10px; border-radius:9px; transition: background var(--agx-dur); }
.agx-fila + .agx-fila { box-shadow: 0 -1px 0 var(--agx-linea); }
.agx-fila:hover, .agx-fila:focus-within { background:var(--agx-suave); }
.agx-fila-abrir { position:absolute; inset:0; border:0; background:none; cursor:pointer; border-radius:9px; }
.agx-fila > *:not(.agx-fila-abrir) { position:relative; pointer-events:none; }
.agx-fila .agx-check-btn, .agx-fila .agx-fila-acc, .agx-fila .agx-fila-acc * { pointer-events:auto; }
.agx-fila-h { font-size:12.5px; font-variant-numeric:tabular-nums; color:var(--text); font-weight:650; }
.agx-fila-h small { display:block; color:var(--muted); font-weight:500; font-size:11.5px; }
.agx-fila-cuerpo { min-width:0; display:flex; gap:10px; align-items:center; }
.agx-fila-barra { width:3px; height:30px; border-radius:2px; background:var(--c); flex:0 0 3px; }
.agx-fila-t { font-weight:600; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.agx-fila-s { display:flex; gap:6px; align-items:center; margin-top:2px; font-size:12px; color:var(--muted); min-width:0; white-space:nowrap; overflow:hidden; }
.agx-fila-s > span { overflow:hidden; text-overflow:ellipsis; }
.agx-fila-s svg { width:12px; height:12px; }
.agx-fila-der { display:flex; align-items:center; gap:6px; }
.agx-fila-acc { display:flex; gap:2px; align-items:center; opacity:0; transition: opacity var(--agx-dur); }
.agx-fila:hover .agx-fila-acc, .agx-fila:focus-within .agx-fila-acc { opacity:1; }
.agx-fila.is-cerrado .agx-fila-t { color:var(--muted); text-decoration: line-through; }
.agx-fila.sin-check { grid-template-columns:70px minmax(0,1fr) auto; }
.agx-check-btn { width:22px; height:22px; border-radius:50%; border:1.6px solid color-mix(in srgb, var(--c) 65%, var(--border)); background:var(--surface); display:grid; place-items:center; cursor:pointer; transition: background var(--agx-dur), border-color var(--agx-dur), transform 120ms; padding:0; }
.agx-check-btn svg { width:12px; height:12px; stroke-width:2.8; color:transparent; transition: color var(--agx-dur); }
.agx-check-btn:hover svg { color: var(--c); }
.agx-check-btn.is-hecho { background:var(--green); border-color:var(--green); transform: scale(1.06); }
.agx-check-btn.is-hecho svg { color:#fff; }
.agx-check-btn[disabled] { opacity:.35; cursor:default; }

.agx-estado { display:inline-flex; align-items:center; gap:4px; height:20px; padding:0 7px; border-radius:10px; font-size:11px; font-weight:650; background:var(--agx-suave); color:var(--muted); white-space:nowrap; border:1px solid var(--agx-linea); }
.agx-estado svg { width:11px; height:11px; stroke-width:2.2; }
.agx-estado.is-vencido { background:var(--danger-soft); color:var(--danger); border-color:transparent; }
.agx-estado.is-ok { background:var(--green-soft); color:var(--green); border-color:transparent; }
.agx-estado.is-alta { background:var(--accent-soft); color: color-mix(in srgb, var(--accent) 85%, var(--text)); border-color:transparent; }
.agx-estado.is-info { background:var(--primary-soft); color:var(--primary); border-color:transparent; }

/* Hoy */
.agx-hoy { max-width:900px; margin:0 auto; padding:18px 16px 90px; }
.agx-hoy-cab { display:flex; align-items:center; gap:14px; margin-bottom:16px; }
.agx-hoy-cab h3 { margin:0; font-size:20px; letter-spacing:-.02em; }
.agx-hoy-cab p { margin:2px 0 0; color:var(--muted); font-size:13px; }
.agx-anillo { width:44px; height:44px; border-radius:50%; flex:0 0 44px; display:grid; place-items:center; }
.agx-anillo b { width:34px; height:34px; border-radius:50%; background:var(--surface); display:grid; place-items:center; font-size:11px; font-weight:750; }
.agx-prox { --c:#94a3b8; display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:14px; align-items:center; width:100%; padding:14px 16px; border-radius:12px; border:1px solid color-mix(in srgb, var(--c) 28%, var(--border)); background: linear-gradient(90deg, color-mix(in srgb, var(--c) 9%, var(--surface)), var(--surface) 75%); margin-bottom:4px; cursor:pointer; text-align:left; transition: box-shadow var(--agx-dur); }
.agx-prox:hover { box-shadow:var(--agx-sombra); }
.agx-prox-k { font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color: color-mix(in srgb, var(--c) 70%, var(--text)); }
.agx-prox-t { font-size:15.5px; font-weight:700; margin:2px 0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.agx-prox-s { color:var(--muted); font-size:12.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.agx-prox-h { font-size:22px; font-weight:750; letter-spacing:-.02em; font-variant-numeric:tabular-nums; text-align:right; }
.agx-prox-h small { display:block; font-size:11.5px; font-weight:600; color:var(--muted); letter-spacing:0; }
.agx-prox-ico { width:40px; height:40px; border-radius:10px; display:grid; place-items:center; background: color-mix(in srgb, var(--c) 15%, var(--surface)); color: color-mix(in srgb, var(--c) 80%, var(--text)); }
.agx-prox-ico svg { width:20px; height:20px; }
.agx-sec-t { display:flex; align-items:center; gap:8px; margin:20px 6px 4px; font-size:11.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); }
.agx-sec-t .agx-n { text-transform:none; letter-spacing:0; }

.agx-vacio { display:grid; place-items:center; text-align:center; gap:6px; padding:56px 20px; color:var(--muted); }
.agx-vacio svg { width:34px; height:34px; stroke-width:1.4; color: color-mix(in srgb, var(--muted) 60%, transparent); }
.agx-vacio b { color:var(--text); font-size:14.5px; }
.agx-vacio .agx-btn { margin-top:8px; }
.agx-error { margin:16px; padding:12px 14px; border-radius:10px; background:var(--danger-soft); color:var(--danger); display:flex; gap:10px; align-items:center; }
.agx-error .agx-btn { margin-left:auto; }

/* Skeleton: respeta la distribución de cada vista */
.agx-sk { display:block; background: linear-gradient(90deg, var(--agx-suave) 25%, color-mix(in srgb, var(--border) 45%, var(--surface)) 50%, var(--agx-suave) 75%); background-size:200% 100%; animation: agx-brillo 1.3s ease-in-out infinite; border-radius:5px; }

/* Tira de días (celular) */
.agx-tira { display:grid; grid-template-columns:repeat(7, 1fr); gap:2px; padding:6px 8px; border-bottom:1px solid var(--border); }
.agx-tira button { display:flex; flex-direction:column; align-items:center; gap:2px; height:56px; border:0; border-radius:12px; background:none; cursor:pointer; padding:5px 0; }
.agx-tira small { font-size:10.5px; font-weight:700; color:var(--muted); text-transform:uppercase; }
.agx-tira b { width:30px; height:30px; display:grid; place-items:center; border-radius:15px; font-size:14.5px; font-weight:650; }
.agx-tira i { width:4px; height:4px; border-radius:2px; background:var(--primary); opacity:0; }
.agx-tira button.tiene i { opacity:.8; }
.agx-tira button.is-hoy b { color:var(--primary); }
.agx-tira button.is-on b { background:var(--primary); color:#fff; }

/* ---------- Panel de detalle ---------- */
.agx-panel { --c:#94a3b8; position:absolute; top:0; right:0; bottom:0; width:min(420px, 92%); z-index:20; display:flex; flex-direction:column; background:var(--surface); border-left:1px solid var(--border); box-shadow:-16px 0 40px -18px rgba(15,23,42,.25); transform: translateX(16px); opacity:0; transition: transform var(--agx-dur) var(--agx-ease), opacity var(--agx-dur) var(--agx-ease); }
.agx-panel.is-on { transform:none; opacity:1; }
.agx-panel-h { display:flex; align-items:flex-start; gap:4px; padding:12px 10px 10px 16px; border-bottom:1px solid var(--border); }
.agx-panel-tit { flex:1; min-width:0; }
.agx-panel-tipo { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:650; color: color-mix(in srgb, var(--c) 70%, var(--text)); }
.agx-panel-tipo:not(:empty)::before { content:''; width:8px; height:8px; border-radius:3px; background:var(--c); }
.agx-panel-h h3 { margin:3px 0 0; font-size:16px; font-weight:700; line-height:1.3; letter-spacing:-.01em; outline:none; overflow-wrap:anywhere; }
.agx-panel-acc { display:flex; gap:2px; }
.agx-panel-c { flex:1; overflow:auto; padding:12px 16px 24px; overscroll-behavior:contain; }
.agx-velo { display:none; }
.agx-agarre { width:40px; height:4px; border-radius:2px; background:var(--border); margin:8px auto 0; }

.agx-acc-prin { display:flex; flex-wrap:wrap; gap:6px; margin:2px 0 14px; }
.agx-props { display:grid; grid-template-columns: 22px minmax(0,1fr); gap:9px 8px; align-items:start; margin:0 0 4px; }
.agx-props > svg { color:var(--muted); margin-top:1px; }
.agx-props > div { min-width:0; overflow-wrap:anywhere; line-height:1.45; }
.agx-props small { color:var(--muted); }
.agx-props a { color:var(--primary); text-decoration:none; font-weight:600; }
.agx-props a:hover { text-decoration:underline; }
.agx-desc { position:relative; margin:12px 0 0; padding:10px 12px; border-radius:9px; background:var(--agx-suave); line-height:1.5; white-space:pre-line; overflow-wrap:anywhere; }
.agx-desc.is-corto { max-height:7.6em; overflow:hidden; }
.agx-desc.is-corto::after { content:''; position:absolute; left:0; right:0; bottom:0; height:2.6em; background:linear-gradient(transparent, var(--agx-suave)); }
.agx-vermas { border:0; background:none; color:var(--primary); font-weight:600; font-size:12.5px; cursor:pointer; padding:4px 0; }
.agx-det { border-top:1px solid var(--border); margin-top:12px; }
.agx-det > summary { list-style:none; display:flex; align-items:center; gap:8px; padding:11px 2px 9px; font-size:11.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); cursor:pointer; }
.agx-det > summary::-webkit-details-marker { display:none; }
.agx-det > summary > svg:last-child { margin-left:auto; transition: transform var(--agx-dur) var(--agx-ease); }
.agx-det[open] > summary > svg:last-child { transform: rotate(180deg); }
.agx-det[open] > :not(summary) { animation: agx-entra var(--agx-dur) var(--agx-ease); }
.agx-avs { display:inline-flex; align-items:center; vertical-align:middle; }
.agx-av { width:24px; height:24px; border-radius:50%; display:grid; place-items:center; font-size:10px; font-weight:700; color:#fff; background:var(--av, #64748b); box-shadow:0 0 0 2px var(--surface); position:relative; flex:0 0 24px; }
.agx-av + .agx-av { margin-left:-6px; }
.agx-av.r-aceptada::after, .agx-av.r-rechazada::after, .agx-av.r-pendiente::after { content:''; position:absolute; right:-2px; bottom:-2px; width:9px; height:9px; border-radius:50%; border:2px solid var(--surface); background:var(--muted); }
.agx-av.r-aceptada::after { background:var(--green); }
.agx-av.r-rechazada::after { background:var(--danger); }
.agx-av-mas { background:var(--agx-suave); color:var(--muted); box-shadow:0 0 0 2px var(--surface), inset 0 0 0 1px var(--border); }
.agx-av.sm { width:18px; height:18px; flex-basis:18px; font-size:8.5px; }
.agx-part { display:flex; align-items:center; gap:9px; padding:6px 0; }
.agx-part > span { flex:1; min-width:0; }
.agx-paso { display:flex; align-items:center; gap:9px; padding:5px 0; cursor:pointer; }
.agx-paso input { width:16px; height:16px; accent-color: var(--primary); }
.agx-paso.is-hecho span { text-decoration:line-through; color:var(--muted); }
.agx-com { padding:8px 0; border-bottom:1px solid var(--agx-linea); }
.agx-com small { color:var(--muted); display:block; margin-bottom:2px; font-size:11.5px; }
.agx-hist { position:relative; padding:0 0 10px 16px; margin-left:4px; border-left:1.5px solid var(--border); font-size:12.5px; line-height:1.45; }
.agx-hist::before { content:''; position:absolute; left:-4.5px; top:4px; width:8px; height:8px; border-radius:50%; background:var(--surface); border:1.5px solid var(--primary); }
.agx-hist small { color:var(--muted); }
.agx-caja { margin:0 0 14px; padding:12px; border:1px solid var(--border); border-radius:11px; background:var(--agx-suave); animation: agx-entra var(--agx-dur) var(--agx-ease); }
.agx-caja h4 { margin:0 0 10px; font-size:13px; }

/* ---------- Formularios ---------- */
.agx-form { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.agx-full { grid-column: 1 / -1; }
.agx-campo { display:flex; flex-direction:column; gap:4px; min-width:0; }
.agx-campo > span { font-size:12px; font-weight:600; color:var(--muted); }
.agx-campo input:not([type=checkbox]):not([type=radio]), .agx-campo select, .agx-campo textarea { width:100%; min-height:34px; padding:6px 9px; border-radius:8px; border:1px solid var(--border); background:var(--surface); color:var(--text); font-size:13.5px; transition: border-color var(--agx-dur), box-shadow var(--agx-dur); }
.agx-campo input:focus, .agx-campo select:focus, .agx-campo textarea:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent); }
.agx-campo textarea { min-height:64px; resize:vertical; line-height:1.45; }
.agx-campo input.agx-titulo-in { font-size:16px; font-weight:650; border-width:0 0 1px 0; border-radius:0; padding:4px 0; min-height:36px; box-shadow:none !important; background:transparent; }
.agx-campo.con-error input, .agx-campo.con-error select, .agx-campo.con-error textarea { border-color:var(--danger) !important; }
.agx-err { color:var(--danger); font-size:11.5px; }
.agx-opc { display:flex; flex-wrap:wrap; gap:4px 12px; font-size:13px; }
.agx-opc label { display:inline-flex; align-items:center; gap:6px; cursor:pointer; min-height:26px; }
.agx-pick { border:1px solid var(--border); border-radius:8px; max-height:150px; overflow:auto; padding:3px 8px; background:var(--surface); }
.agx-pick label { display:flex; align-items:center; gap:8px; min-height:28px; font-size:13px; cursor:pointer; }
.agx-sug { margin-top:4px; border:1px solid var(--border); border-radius:8px; overflow:hidden; background:var(--surface); box-shadow:var(--agx-sombra); }
.agx-sug button { display:block; width:100%; text-align:left; padding:7px 10px; border:0; background:none; cursor:pointer; font-size:13px; }
.agx-sug button + button { border-top:1px solid var(--agx-linea); }
.agx-sug button:hover, .agx-sug button:focus { background:var(--agx-suave); }
.agx-mas-opc { grid-column:1 / -1; border-top:1px solid var(--border); padding-top:2px; }
.agx-mas-opc > summary { list-style:none; display:flex; align-items:center; gap:6px; padding:7px 0; font-size:12.5px; font-weight:650; color:var(--primary); cursor:pointer; }
.agx-mas-opc > summary::-webkit-details-marker { display:none; }
.agx-mas-opc > summary svg { transition: transform var(--agx-dur); }
.agx-mas-opc[open] > summary svg { transform: rotate(90deg); }
.agx-mas-opc > .agx-form { padding-top:4px; animation: agx-entra var(--agx-dur) var(--agx-ease); }
.agx-aviso { padding:9px 11px; border-radius:9px; font-size:12.5px; background:var(--accent-soft); color: color-mix(in srgb, var(--accent) 80%, var(--text)); }
.agx-aviso ul { margin:4px 0 0; padding-left:16px; }
.agx-pie { grid-column:1 / -1; display:flex; justify-content:flex-end; align-items:center; gap:6px; padding-top:4px; }
.agx-pie .agx-izq { margin-right:auto; }
.agx-tabla { width:100%; border-collapse:collapse; font-size:13px; }
.agx-tabla th, .agx-tabla td { padding:7px 6px; border-bottom:1px solid var(--agx-linea); text-align:right; }
.agx-tabla th:first-child, .agx-tabla td:first-child { text-align:left; }
.agx-tabla th { font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
.agx-barra { height:6px; border-radius:3px; background:var(--agx-suave); overflow:hidden; }
.agx-barra i { display:block; height:100%; background:var(--primary); border-radius:3px; }

/* ---------- Capa flotante: tooltip, vista previa, menú, popover ---------- */
.agx-capa { position:fixed; inset:0; pointer-events:none; z-index:1200; color:var(--text); font-size:13px; }
.agx-capa > * { pointer-events:auto; }
.agx-tip { position:fixed; max-width:260px; padding:5px 8px; border-radius:6px; background: #0f172a; color:#f8fafc; font-size:12px; line-height:1.35; pointer-events:none !important; opacity:0; transform: translateY(2px); transition: opacity 120ms, transform 120ms; }
.agx-tip.is-on { opacity:1; transform:none; }
.agx-tip kbd { margin-left:6px; padding:0 5px; border-radius:4px; background:rgba(255,255,255,.16); font:600 11px/16px inherit; }
.agx-flot { position:fixed; min-width:230px; max-width:min(340px, calc(100vw - 16px)); max-height:calc(100vh - 16px); overflow:auto; padding:12px; border-radius:12px; background:var(--surface); border:1px solid var(--border); box-shadow:var(--agx-sombra-flot); opacity:0; transform: translateY(4px) scale(.985); transition: opacity var(--agx-dur) var(--agx-ease), transform var(--agx-dur) var(--agx-ease); }
.agx-flot.is-on { opacity:1; transform:none; }
.agx-flot.is-menu { padding:5px; min-width:210px; }
.agx-flot.is-ancho { width:min(380px, calc(100vw - 16px)); max-width:none; }
.agx-pv { --c:#94a3b8; }
.agx-pv-tipo { display:flex; align-items:center; gap:6px; font-size:11.5px; font-weight:650; color: color-mix(in srgb, var(--c) 70%, var(--text)); }
.agx-pv-tipo::before { content:''; width:8px; height:8px; border-radius:3px; background:var(--c); }
.agx-pv-t { margin:4px 0 8px; font-size:14.5px; font-weight:700; line-height:1.3; overflow-wrap:anywhere; }
.agx-pv-l { display:flex; gap:8px; align-items:flex-start; padding:2px 0; }
.agx-pv-l svg { color:var(--muted); margin-top:1px; width:15px; height:15px; }
.agx-pv-l span { min-width:0; overflow-wrap:anywhere; }
.agx-pv-acc { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; padding-top:10px; border-top:1px solid var(--agx-linea); }
.agx-mi { display:flex; align-items:center; gap:9px; width:100%; min-height:32px; padding:0 10px; border:0; border-radius:7px; background:none; text-align:left; font-size:13px; cursor:pointer; color:var(--text); text-decoration:none; }
.agx-mi svg { color:var(--muted); }
.agx-mi:hover, .agx-mi:focus { background:var(--agx-suave); outline:none; }
.agx-mi.is-peligro, .agx-mi.is-peligro svg { color:var(--danger); }
.agx-mi-sep { height:1px; margin:4px 6px; background:var(--agx-linea); }
.agx-dia-lista { display:flex; flex-direction:column; gap:3px; max-height:330px; overflow:auto; }
.agx-dia-lista .agx-ev { height:26px; }
.agx-flot h4 { margin:0 0 8px; font-size:13px; font-weight:700; display:flex; justify-content:space-between; align-items:center; gap:8px; }
.agx-flot h4 small { font-weight:600; color:var(--muted); }

/* ---------- Toast ---------- */
.agx-toast { position:absolute; left:50%; bottom:18px; z-index:30; display:flex; align-items:center; gap:14px; padding:9px 10px 9px 14px; border-radius:10px; background:#0f172a; color:#f8fafc; font-size:13px; box-shadow:var(--agx-sombra-flot); transform: translate(-50%, 10px); opacity:0; transition: opacity var(--agx-dur), transform var(--agx-dur) var(--agx-ease); max-width:calc(100% - 24px); }
.agx-toast.is-on { opacity:1; transform: translate(-50%, 0); }
.agx-toast.is-error { background: color-mix(in srgb, var(--danger) 85%, #000); }
.agx-toast button { border:0; background:rgba(255,255,255,.14); color:#fff; font-weight:650; font-size:12.5px; padding:4px 10px; border-radius:7px; cursor:pointer; }
.agx-toast button:hover { background:rgba(255,255,255,.24); }

.agx-fab { position:fixed; right:18px; bottom: calc(18px + env(safe-area-inset-bottom)); z-index:25; width:56px; height:56px; border-radius:17px; border:0; background:var(--primary); color:#fff; box-shadow:0 10px 24px -6px color-mix(in srgb, var(--primary) 70%, transparent); place-items:center; cursor:pointer; }
.agx-fab svg { width:24px !important; height:24px !important; stroke-width:2.2; }
.agx-solo-movil { display:none !important; }

@keyframes agx-giro { to { transform: rotate(360deg); } }
@keyframes agx-entra { from { opacity:0; transform: translateY(-3px); } to { opacity:1; transform:none; } }
@keyframes agx-pulso { 50% { opacity:.5; } }
@keyframes agx-brillo { to { background-position: -200% 0; } }
@keyframes agx-hecho { 0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--green) 55%, transparent); } 100% { box-shadow: 0 0 0 9px transparent; } }

/* ---------- Tablet: la navegación arranca contraída ---------- */
@media (max-width: 1100px) {
    .agx:not(.is-expandida) { --agx-nav-w: 56px; }
    .agx:not(.is-expandida) .agx-nav-i span, .agx:not(.is-expandida) .agx-nav-t, .agx:not(.is-expandida) .agx-cat span:last-child { display:none; }
    .agx:not(.is-expandida) .agx-nav-i, .agx:not(.is-expandida) .agx-cat { justify-content:center; padding:0; }
    .agx:not(.is-expandida) .agx-n { position:absolute; top:1px; right:2px; min-width:15px; height:15px; font-size:9.5px; padding:0 4px; }
    .agx:not(.is-expandida) .agx-nav-plegar { align-self:center; }
    .agx:not(.is-expandida) .agx-nav-plegar svg { transform: rotate(180deg); }
    .agx-periodo span { display:none; }
    .agx-buscar.is-abierto input { width:150px; }
}

/* ---------- Celular: lista diaria, hojas inferiores, áreas táctiles ---------- */
@media (max-width: 760px) {
    .agx { display:flex; flex-direction:column; height:auto; min-height: calc(100dvh - 90px); border:0; border-radius:0; margin: -16px -16px 0; overflow:visible; font-size:14.5px; }
    .agx-solo-escritorio { display:none !important; }
    .agx-solo-movil { display:inline-grid !important; }
    .agx-tira.agx-solo-movil { display:grid !important; }
    .agx-agarre.agx-solo-movil { display:block !important; }
    .agx-nav { position:fixed; left:0; right:0; bottom:0; top:auto; z-index:1100; max-height:80dvh; border:0; border-radius:18px 18px 0 0; background:var(--surface); box-shadow:var(--agx-sombra-flot); padding:14px 12px calc(16px + env(safe-area-inset-bottom)); transform: translateY(105%); transition: transform 220ms var(--agx-ease); }
    .agx.nav-abierta .agx-nav { transform:none; }
    .agx .agx-nav .agx-nav-i span, .agx .agx-nav .agx-nav-t, .agx .agx-nav .agx-cat span:last-child { display:inline !important; }
    .agx .agx-nav .agx-nav-i, .agx .agx-nav .agx-cat { justify-content:flex-start !important; padding:0 12px !important; }
    .agx .agx-nav .agx-n { position:static !important; }
    .agx-nav-plegar { display:none !important; }
    .agx-nav-i, .agx-cat { height:46px; font-size:15px; }
    .agx-bar { position:sticky; top:0; z-index:10; background:var(--surface); padding:6px 8px; gap:2px; min-height:52px; }
    .agx-periodo { flex:1; }
    .agx-periodo h2 { font-size:16px; }
    .agx-navfechas .agx-btn { display:none; }
    .agx-crear { display:none !important; }
    .agx-buscar.is-abierto { position:absolute; left:8px; right:8px; top:8px; height:40px; z-index:3; }
    .agx-buscar.is-abierto input { width:100%; font-size:16px; }
    .agx-ico { width:42px; height:42px; }
    .agx-tira { position:sticky; top:52px; z-index:9; background:var(--surface); }
    .agx-chips { flex-wrap:nowrap; overflow-x:auto; }
    .agx-lienzo { flex:none; }
    .agx-cal { position:static; overflow:visible; }
    .agx-lst, .agx-hoy { padding:4px 10px 110px; }
    .agx-lst-dia { position:static; }
    .agx-fila { grid-template-columns:24px 54px minmax(0,1fr); gap:10px; min-height:60px; padding:10px 6px; }
    .agx-fila.sin-check { grid-template-columns:54px minmax(0,1fr); }
    .agx-fila-der { display:none; }
    .agx-check-btn { width:26px; height:26px; }
    .agx-panel { position:fixed; left:0; right:0; top:auto; bottom:0; width:auto; max-height:90dvh; border:0; border-radius:18px 18px 0 0; z-index:1100; transform: translateY(100%); opacity:1; box-shadow:var(--agx-sombra-flot); transition: transform 240ms var(--agx-ease); }
    .agx-panel.is-on { transform:none; }
    .agx-panel-c { padding-bottom: calc(24px + env(safe-area-inset-bottom)); }
    .agx-velo { display:block; position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.32); opacity:0; transition: opacity var(--agx-dur); }
    .agx-velo.is-on { opacity:1; }
    .agx-form { grid-template-columns:1fr; }
    .agx-campo input:not([type=checkbox]):not([type=radio]), .agx-campo select { min-height:44px; font-size:16px; }
    .agx-campo textarea { font-size:16px; }
    .agx-btn { height:40px; }
    .agx-btn--sm { height:36px; }
    .agx-toast { position:fixed; bottom: calc(88px + env(safe-area-inset-bottom)); }
    .agx-prox { grid-template-columns: minmax(0,1fr) auto; }
    .agx-prox-ico { display:none; }
}

@media (prefers-reduced-motion: reduce) {
    .agx *, .agx-capa *, .agx, .agx-panel, .agx-nav, .agx-toast { transition-duration:1ms !important; animation-duration:1ms !important; animation-iteration-count:1 !important; }
}
</style>
@endsection
