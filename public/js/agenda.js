/*
 * Agenda empresarial y personal (resources/views/agenda/index.blade.php).
 *
 * Carga por rango desde /agenda/datos y pinta mes/semana/día/lista, las
 * secciones Hoy/Vencidos/Sin seguimiento y, en el celular, una lista diaria.
 * Tooltips, vistas previas, menús y popovers viven en #agxCapa (fuera del
 * flujo, nunca se recortan). Todo se guarda por los endpoints de
 * AgendaController, que validan permisos; aquí solo se muestra lo que el
 * servidor ya entregó filtrado.
 */
(function () {
    'use strict';

    const CFG = window.AGENDA;
    const raiz = document.getElementById('agx');
    const capa = document.getElementById('agxCapa');
    if (!CFG || !raiz || !capa) return;
    document.body.appendChild(capa); // la capa flota sobre todo, fuera de contenedores con overflow

    const $ = (s, el) => (el || raiz).querySelector(s);
    const $$ = (s, el) => Array.from((el || raiz).querySelectorAll(s));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const DIAS = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const ESTADOS = Object.assign({}, CFG.estadosTarea, CFG.estadosEvento);
    const mqMovil = window.matchMedia('(max-width: 760px)');
    const mqTactil = window.matchMedia('(hover: none), (pointer: coarse)');
    const movil = () => mqMovil.matches;
    const tactil = () => mqTactil.matches;
    const CERRADOS = ['completado', 'realizado', 'cancelado'];

    // ---------- Iconos (trazo, sin emojis) ----------
    const P = {
        reloj: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5l3 2"/>',
        usuario: '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.2-3.6 4-5.5 7-5.5s5.8 1.9 7 5.5"/>',
        cliente: '<rect x="3.5" y="7" width="17" height="12.5" rx="2"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7M3.5 12.5h17"/>',
        enlace: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        x: '<path d="M6 6l12 12M18 6L6 18"/>',
        alerta: '<circle cx="12" cy="12" r="8.5"/><path d="M12 8v5M12 16.3v.01"/>',
        proceso: '<circle cx="12" cy="12" r="8.5"/><path d="M12 3.5a8.5 8.5 0 0 1 0 17z" fill="currentColor" stroke="none"/>',
        pregunta: '<circle cx="12" cy="12" r="8.5"/><path d="M9.7 9.6a2.4 2.4 0 1 1 3.3 2.2c-.6.3-1 .8-1 1.4v.3M12 16.4v.01"/>',
        lugar: '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
        video: '<rect x="3" y="6.5" width="12.5" height="11" rx="2"/><path d="M15.5 10.5l5-3v9l-5-3"/>',
        calendario: '<rect x="4" y="5" width="16" height="15" rx="2.5"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        repetir: '<path d="M4 11V9a3 3 0 0 1 3-3h12l-3-3M20 13v2a3 3 0 0 1-3 3H5l3 3"/>',
        ojo: '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/>',
        candado: '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        grupo: '<circle cx="9" cy="9" r="3"/><path d="M3.5 19c.8-3 3-4.5 5.5-4.5s4.7 1.5 5.5 4.5"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16 14.6c2.2.1 3.9 1.5 4.5 4.4"/>',
        puntos: '<circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/>',
        editar: '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
        copiar: '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
        campana: '<path d="M6 16v-5a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        flecha: '<path d="M5 12h14M13 6l6 6-6 6"/>',
        abajo: '<path d="M6 9l6 6 6-6"/>',
        derecha: '<path d="M9 6l6 6-6 6"/>',
        descarga: '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        barras: '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
        nota: '<path d="M5 6h14M5 11h14M5 16h9"/>',
        comentario: '<path d="M4 5h16v11H9l-5 4z"/>',
        pasos: '<path d="M4 7l2 2 3-3M4 15l2 2 3-3M12 8h8M12 16h8"/>',
        mas: '<path d="M12 5v14M5 12h14"/>',
        bandera: '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
    };
    const ico = (n, extra) => '<svg viewBox="0 0 24 24" aria-hidden="true"' + (extra ? ' ' + extra : '') + '>' + (P[n] || '') + '</svg>';

    // ---------- Fechas como texto Y-m-d (hora de la empresa) ----------
    const aFecha = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const aTexto = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const sumar = (s, n) => { const d = aFecha(s); d.setDate(d.getDate() + n); return aTexto(d); };
    const lunes = (s) => { const d = aFecha(s); d.setDate(d.getDate() - ((d.getDay() + 6) % 7)); return aTexto(d); };
    const diaSem = (s) => DIAS[(aFecha(s).getDay() + 6) % 7];
    const largo = (s) => diaSem(s) + ' ' + aFecha(s).getDate() + ' de ' + MESES[aFecha(s).getMonth()];
    const corto = (s) => aFecha(s).getDate() + ' ' + MESES[aFecha(s).getMonth()].slice(0, 3);
    const minutos = (h) => { const [a, b] = String(h).split(':').map(Number); return a * 60 + (b || 0); };
    const hm = (m) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
    const mayus = (t) => t.charAt(0).toUpperCase() + t.slice(1);
    const ahoraHM = () => { const d = new Date(); return hm(d.getHours() * 60 + d.getMinutes()); };

    // ---------- Estado ----------
    const leer = () => { try { return JSON.parse(localStorage.getItem('agx-v2') || '{}'); } catch (e) { return {}; } };
    const G = leer();
    const S = {
        vista: G.vista || 'mes',
        ambito: CFG.ambitoInicial || G.ambito || 'todas',
        seccion: null,
        fecha: CFG.hoy,
        filtros: {},
        cliente: null,
        q: '',
        ocultos: new Set(G.ocultos || []),
        items: null,
        rangoCargado: null,
        paneles: null,
        abierto: null,
        token: 0,
        error: null,
    };
    if (G.plegada) raiz.classList.add('is-plegada');
    const guardar = () => { try { localStorage.setItem('agx-v2', JSON.stringify({ vista: S.vista, ambito: S.ambito, ocultos: [...S.ocultos], plegada: raiz.classList.contains('is-plegada') })); } catch (e) { /* sin almacenamiento */ } };

    // ---------- Red ----------
    async function pedir(url, opciones = {}) {
        const r = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, ...(opciones.body ? { 'Content-Type': 'application/json' } : {}) },
        }, opciones));
        let datos = null;
        try { datos = await r.json(); } catch (e) { datos = null; }
        if (!r.ok) {
            const err = new Error((datos && (datos.message || datos.mensaje)) || (r.status >= 500 ? 'El servidor no pudo completar la operación.' : 'No se pudo completar la operación.'));
            err.status = r.status; err.datos = datos || {};
            throw err;
        }
        return datos || {};
    }
    const enviar = (url, metodo, cuerpo) => pedir(url, { method: metodo, body: JSON.stringify(cuerpo || {}) });
    const urlItem = (key, extra) => CFG.rutas.item + '/' + key + (extra ? '/' + extra : '');
    function primerError(e) {
        const errs = e && e.datos && e.datos.errors;
        if (errs) { const k = Object.keys(errs)[0]; if (k) return errs[k][0]; }
        return (e && e.message) || 'Algo salió mal.';
    }

    function parametros(extra) {
        const p = new URLSearchParams();
        p.set('ambito', S.ambito);
        Object.entries(S.filtros).forEach(([k, v]) => { if (v) p.set(k, v); });
        if (S.cliente) p.set('cliente', S.cliente.id);
        if (S.q) p.set('q', S.q);
        Object.entries(extra || {}).forEach(([k, v]) => p.set(k, v));
        return p;
    }

    const vistaReal = () => (movil() ? 'movil' : S.vista);

    function rango() {
        const v = vistaReal();
        if (v === 'mes') {
            const d = aFecha(S.fecha);
            const primero = aTexto(new Date(d.getFullYear(), d.getMonth(), 1));
            const ultimo = aTexto(new Date(d.getFullYear(), d.getMonth() + 1, 0));
            return [lunes(primero), sumar(lunes(ultimo), 6)];
        }
        if (v === 'semana' || v === 'movil') { const l = lunes(S.fecha); return [l, sumar(l, 6)]; }
        if (v === 'dia') return [S.fecha, S.fecha];
        return [S.fecha, sumar(S.fecha, 30)];
    }

    // ---------- Carga ----------
    async function cargar({ silencioso } = {}) {
        const token = ++S.token;
        const [desde, hasta] = rango();
        const clave = desde + '|' + hasta + '|' + parametros().toString();
        if (!silencioso && S.rangoCargado !== clave) { S.items = null; pintar(); }
        try {
            const [datos, paneles] = await Promise.all([
                pedir(CFG.rutas.datos + '?' + parametros({ desde, hasta })),
                pedir(CFG.rutas.paneles + '?' + parametros()),
            ]);
            if (token !== S.token) return;
            S.items = datos.items || [];
            S.paneles = paneles;
            S.rangoCargado = clave;
            S.error = null;
        } catch (e) {
            if (token !== S.token) return;
            S.error = e.status === 403 ? e.message : 'No se pudo cargar la agenda. Revisa tu conexión e inténtalo de nuevo.';
            if (!S.items) S.items = [];
            if (silencioso) toast(S.error, { error: true });
        }
        pintar();
        pintarNav();
    }

    const visibles = () => (S.items || []).filter((i) => !S.ocultos.has(i.tipo));

    // ---------- Formato común ----------
    const cerrado = (i) => CERRADOS.includes(i.estado);
    const horaTxt = (i) => (i.hora ? i.hora + (i.hora_fin ? '–' + i.hora_fin : '') : (i.fuente === 'seguimiento' ? 'Sin hora' : 'Todo el día'));
    function cuandoTxt(i) {
        let t = largo(i.fecha);
        if (i.fecha_fin && i.fecha_fin !== i.fecha) t += ' al ' + largo(i.fecha_fin);
        return mayus(t) + ' · ' + (i.hora ? i.hora + (i.hora_fin ? '–' + i.hora_fin : '') : (i.fuente === 'seguimiento' ? 'sin hora (pendiente del día)' : 'todo el día'));
    }
    function estadoInfo(i) {
        if (i.vencido) return { cls: 'is-vencido', ico: 'alerta', txt: 'Vencido' };
        if (i.mi_respuesta === 'pendiente' && !cerrado(i)) return { cls: 'is-info', ico: 'pregunta', txt: 'Por confirmar asistencia' };
        if (i.estado === 'completado' || i.estado === 'realizado') return { cls: 'is-ok', ico: 'check', txt: i.estado_label };
        if (i.estado === 'cancelado') return { cls: '', ico: 'x', txt: 'Cancelado' };
        if (i.estado === 'en_proceso') return { cls: 'is-info', ico: 'proceso', txt: 'En proceso' };
        return { cls: '', ico: null, txt: i.estado_label || '' };
    }
    function estadoChip(i) {
        const e = estadoInfo(i);
        if (!e.txt || i.fuente === 'congreso' || i.fuente === 'ocupado') return '';
        return '<span class="agx-estado ' + e.cls + '">' + (e.ico ? ico(e.ico) : '') + esc(e.txt) + '</span>';
    }
    const ORIGEN_TIP = 'Viene de Clientes: es el mismo seguimiento de la ficha del cliente; lo que cambies aquí se ve allá.';
    const ORIGEN_AGENDA_TIP = 'Seguimiento creado desde la Agenda; también aparece en la ficha del cliente.';

    function iconosEv(i) {
        const e = estadoInfo(i);
        let h = '';
        if (i.prioridad === 'alta' && !cerrado(i)) h += '<span class="agx-prio" data-tip="Prioridad alta"></span>';
        if (i.origen === 'cliente') h += '<span data-tip="' + esc(ORIGEN_TIP) + '">' + ico('enlace') + '</span>';
        if (i.serie) h += '<span data-tip="Se repite">' + ico('repetir') + '</span>';
        if (e.ico && i.fuente !== 'ocupado') h += '<span data-tip="' + esc(e.txt) + '">' + ico(e.ico) + '</span>';
        return h ? '<span class="agx-ev-i">' + h + '</span>' : '';
    }
    function claseEv(i) {
        return 'agx-ev agx-t-' + esc(i.tipo) + (i.vencido ? ' is-vencido' : '') + (cerrado(i) ? ' is-cerrado' : '')
            + (i.fuente === 'ocupado' ? ' is-ocupado' : '') + (i.mi_respuesta === 'pendiente' && !cerrado(i) ? ' is-pendiente-resp' : '');
    }
    function etiquetaEv(i) {
        return [i.titulo, i.tipo_label, horaTxt(i), estadoInfo(i).txt, i.prioridad === 'alta' ? 'prioridad alta' : '', i.origen === 'cliente' ? 'desde Clientes' : ''].filter(Boolean).join(', ');
    }
    const arrastrable = (i) => !tactil() && i.puede && i.puede.mover;

    /** Evento compacto (mes, todo el día, popovers). */
    function evMini(i, conHora = true) {
        return '<button type="button" class="' + claseEv(i) + '" data-key="' + esc(i.key) + '"' + (arrastrable(i) ? ' draggable="true"' : '') + ' aria-label="' + esc(etiquetaEv(i)) + '">'
            + (conHora && i.hora ? '<span class="agx-ev-h">' + esc(i.hora) + '</span>' : '')
            + '<span class="agx-ev-t">' + esc(i.titulo) + '</span>' + iconosEv(i) + '</button>';
    }

    /** Evento con horario en semana/día: más información cuando hay espacio. */
    function evCol(i, estilo, alto, estrecho) {
        const bajo = alto < 40 && !estrecho;
        const meta = [i.cliente && i.fuente !== 'seguimiento' ? i.cliente.nombre : null, i.motivo, i.ubicacion, i.responsable && i.responsable.nombre && i.responsable.id !== CFG.yo.id ? i.responsable.nombre : null].filter(Boolean);
        return '<button type="button" class="' + claseEv(i) + (bajo ? ' is-corto' : '') + (estrecho ? ' is-estrecho' : '') + '" data-key="' + esc(i.key) + '"' + (arrastrable(i) ? ' draggable="true"' : '') + ' style="' + estilo + '" aria-label="' + esc(etiquetaEv(i)) + '">'
            + '<span class="agx-ev-arriba"><span class="agx-ev-h">' + esc(i.hora + (i.hora_fin && !bajo ? '–' + i.hora_fin : '')) + '</span>' + (bajo ? '<span class="agx-ev-t">' + esc(i.titulo) + '</span>' : '') + iconosEv(i) + '</span>'
            + (bajo ? '' : '<span class="agx-ev-t">' + esc(i.titulo) + '</span>')
            + (!bajo && alto > 62 && meta.length ? '<span class="agx-ev-meta"><span>' + esc(meta.join(' · ')) + '</span></span>' : '')
            + '</button>';
    }

    function delDia(fecha, lista) {
        return (lista || visibles()).filter((i) => i.fecha <= fecha && i.fecha_fin >= fecha)
            .sort((a, b) => ((b.todo_el_dia || !b.hora) - (a.todo_el_dia || !a.hora)) || String(a.hora || '').localeCompare(String(b.hora || '')) || cerrado(a) - cerrado(b));
    }

    // ---------- Pintar ----------
    function pintarBarra() {
        const v = vistaReal();
        const d = aFecha(S.fecha);
        let t; let sub = '';
        if (S.seccion === 'hoy') { t = 'Hoy'; sub = largo(CFG.hoy); }
        else if (S.seccion === 'vencidos') { t = 'Vencidos'; sub = 'pendientes cuya fecha ya pasó'; }
        else if (S.seccion === 'sin') { t = 'Sin seguimiento'; sub = 'prospectos sin siguiente paso'; }
        else if (v === 'mes' || v === 'movil') t = MESES[d.getMonth()] + ' ' + d.getFullYear();
        else if (v === 'semana') { const l = lunes(S.fecha); const f = sumar(l, 6); t = MESES[aFecha(l).getMonth()] + ' ' + aFecha(l).getFullYear(); sub = aFecha(l).getDate() + ' – ' + aFecha(f).getDate() + ' ' + MESES[aFecha(f).getMonth()].slice(0, 3); }
        else if (v === 'dia') { t = largo(S.fecha); sub = String(d.getFullYear()); }
        else { t = 'Próximos 30 días'; sub = 'desde el ' + corto(S.fecha); }
        $('#agxTitulo').textContent = mayus(t);
        $('#agxSub').textContent = sub;
        $$('.agx-navfechas button').forEach((b) => { b.disabled = !!S.seccion; b.style.visibility = S.seccion ? 'hidden' : ''; });
        $$('[data-vista]').forEach((b) => { const on = !S.seccion && b.dataset.vista === S.vista; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
    }

    function pintarNav() {
        $$('[data-ambito]').forEach((b) => { const on = !S.seccion && b.dataset.ambito === S.ambito; b.classList.toggle('is-on', on); b.setAttribute('aria-current', on ? 'true' : 'false'); });
        $$('.agx-nav [data-seccion]').forEach((b) => { const on = S.seccion === b.dataset.seccion; b.classList.toggle('is-on', on); b.setAttribute('aria-current', on ? 'true' : 'false'); });
        const p = S.paneles;
        if (p) {
            const k = p.indicadores || {};
            $('[data-n="hoy"]').textContent = k.hoy || '';
            $('[data-n="vencidos"]').textContent = k.vencidos || '';
            $('[data-n="sin"]').textContent = (p.sin_seguimiento && p.sin_seguimiento.total) || '';
        }
    }

    function pintarCategorias() {
        const tipos = CFG.tipos.map((t) => [t.valor, t.valor === 'seguimiento' ? 'Seguimientos de clientes' : t.label]).concat([['congreso', 'Congresos']]);
        $('#agxCats').innerHTML = tipos.map(([v, l]) => '<button type="button" class="agx-cat agx-t-' + v + '" data-cat="' + v + '" aria-pressed="' + (!S.ocultos.has(v)) + '" data-tip="' + esc((S.ocultos.has(v) ? 'Mostrar: ' : 'Ocultar: ') + l) + '"><span class="agx-cat-c">' + ico('check') + '</span><span>' + esc(l) + '</span></button>').join('');
    }

    function pintarChips() {
        const chips = [];
        const nombre = (lista, id, k = 'name') => ((lista || []).find((x) => String(x.id) === String(id)) || {})[k] || id;
        const etiquetas = {
            usuario: ['Agenda de', (v) => nombre(CFG.supervisados, v)],
            departamento: ['Departamento', (v) => nombre(CFG.departamentos, v, 'nombre')],
            tipo: ['Tipo', (v) => (v.startsWith('seg:') ? 'Seguimiento · ' + (CFG.tiposContacto[v.slice(4)] || v) : v === 'seguimiento' ? 'Seguimientos' : v === 'congreso' ? 'Congresos' : ((CFG.tipos.find((t) => t.valor === v) || {}).label || v))],
            estado: ['Estado', (v) => (v === 'vencido' ? 'Vencido' : (ESTADOS[v] || v))],
            prioridad: ['Prioridad', (v) => CFG.prioridades[v] || v],
            origen: ['Origen', (v) => (v === 'cliente' ? 'Desde Clientes' : 'Manual')],
        };
        Object.entries(S.filtros).forEach(([k, v]) => { if (v && etiquetas[k]) chips.push([k, etiquetas[k][0], etiquetas[k][1](v)]); });
        if (S.cliente) chips.push(['cliente', 'Cliente', S.cliente.nombre]);
        if (S.q) chips.push(['q', 'Búsqueda', '“' + S.q + '”']);
        const caja = $('#agxChips');
        caja.hidden = !chips.length;
        caja.innerHTML = chips.map(([k, l, v]) => '<span class="agx-chip"><b>' + esc(l) + '</b><span data-tip-auto>' + esc(v) + '</span><button type="button" data-quitar="' + k + '" aria-label="Quitar filtro ' + esc(l) + '" data-tip="Quitar">' + ico('x') + '</button></span>').join('')
            + (chips.length > 1 ? '<button type="button" class="agx-chip-limpiar" data-limpiar>Quitar todos</button>' : '');
        const n = Object.values(S.filtros).filter(Boolean).length + (S.cliente ? 1 : 0);
        $('#agxFiltrosN').hidden = !n; $('#agxFiltrosN').textContent = n;
    }

    function pintar() {
        pintarBarra();
        pintarChips();
        const cal = $('#agxCal');
        const v = vistaReal();
        if (movil()) pintarTira();
        if (S.error && !(S.items || []).length) {
            cal.innerHTML = '<div class="agx-error" role="alert">' + ico('alerta') + '<span>' + esc(S.error) + '</span><button type="button" class="agx-btn agx-btn--sm" data-reintentar>Reintentar</button></div>';
            return;
        }
        if (S.items === null || (S.seccion && !S.paneles)) { cal.innerHTML = esqueleto(S.seccion ? 'lista' : v); return; }
        const scroll = cal.scrollTop;
        if (S.seccion === 'hoy') cal.innerHTML = vistaHoy();
        else if (S.seccion === 'vencidos') cal.innerHTML = vistaVencidos();
        else if (S.seccion === 'sin') cal.innerHTML = vistaSin();
        else if (v === 'mes') cal.innerHTML = vistaMes();
        else if (v === 'lista') cal.innerHTML = vistaLista();
        else if (v === 'movil') cal.innerHTML = vistaMovil();
        else cal.innerHTML = vistaSemana(v === 'dia' ? 1 : 7);
        // Semana/día abren a las 7:00 la primera vez; después conservan la posición.
        const clave = (S.seccion || v) + S.fecha;
        if ((v === 'semana' || v === 'dia') && !S.seccion && cal.dataset.pos !== clave) {
            cal.scrollTop = 7 * parseFloat(getComputedStyle(raiz).getPropertyValue('--agx-hora') || '48');
        } else if (cal.dataset.pos === clave) cal.scrollTop = scroll;
        cal.dataset.pos = clave;
    }

    function esqueleto(v) {
        const b = (w, h, extra) => '<span class="agx-sk" style="width:' + w + ';height:' + (h || 12) + 'px;' + (extra || '') + '"></span>';
        if (v === 'mes') {
            let h = '<div class="agx-mes" aria-busy="true">' + DIAS.map((d) => '<div class="agx-mes-h">' + d + '</div>').join('');
            for (let n = 0; n < 35; n++) h += '<div class="agx-dia">' + b('22px', 18, 'border-radius:9px;margin-bottom:4px') + (n % 3 ? b('85%', 16) : '') + (n % 4 === 1 ? b('60%', 16, 'margin-top:3px') : '') + '</div>';
            return h + '</div><span class="agx-sr">Cargando…</span>';
        }
        if (v === 'semana' || v === 'dia') {
            const n = v === 'dia' ? 1 : 7;
            let h = '<div class="agx-sem" style="--dias:' + n + '"><div></div>' + Array.from({ length: n }, () => '<div class="agx-sem-h">' + b('60%', 22, 'margin:0 auto') + '</div>').join('') + '</div><div class="agx-rej" style="--dias:' + n + '" aria-busy="true"><div class="agx-horas">' + '<div></div>'.repeat(12) + '</div>';
            for (let c = 0; c < n; c++) h += '<div class="agx-col">' + '<div class="agx-slot"></div>'.repeat(12) + '<span class="agx-sk" style="position:absolute;left:6px;right:6px;top:' + (40 + (c * 37) % 300) + 'px;height:' + (50 + (c * 13) % 40) + 'px"></span></div>';
            return h + '</div>';
        }
        let h = '<div class="agx-lst" aria-busy="true">';
        for (let n = 0; n < 7; n++) h += '<div class="agx-fila sin-check">' + b('44px') + '<div>' + b((50 + (n * 7) % 35) + '%', 13) + b('30%', 10, 'margin-top:6px') + '</div><span></span></div>';
        return h + '</div>';
    }

    function vacio(titulo, texto, conCrear) {
        const filtrado = Object.values(S.filtros).some(Boolean) || S.q || S.cliente || S.ocultos.size;
        return '<div class="agx-vacio">' + ico('calendario') + '<b>' + esc(filtrado ? 'Nada coincide con los filtros' : titulo) + '</b><span>' + esc(filtrado ? 'Quita algún filtro o muestra más calendarios.' : texto) + '</span>'
            + (filtrado ? '<button type="button" class="agx-btn agx-btn--sm" data-limpiar>Quitar filtros</button>' : conCrear ? '<button type="button" class="agx-btn agx-btn--sm" data-crear>' + ico('mas') + 'Crear actividad</button>' : '') + '</div>';
    }

    // ----- Mes -----
    function vistaMes() {
        const [desde, hasta] = rango();
        const mes = aFecha(S.fecha).getMonth();
        const filas = Math.round(((aFecha(hasta) - aFecha(desde)) / 864e5 + 1) / 7);
        const cal = $('#agxCal');
        const altoFila = Math.max(96, ((cal.clientHeight || 600) - 30) / filas);
        const max = Math.max(1, Math.floor((altoFila - 34) / 23));
        let h = '<div class="agx-mes" role="grid" aria-label="Mes">' + DIAS.map((d) => '<div class="agx-mes-h" role="columnheader">' + d + '</div>').join('');
        for (let n = 0; n < filas * 7; n++) {
            const f = sumar(desde, n);
            const lista = delDia(f);
            const fuera = aFecha(f).getMonth() !== mes;
            const cabe = lista.length > max ? max - 1 : max;
            h += '<div class="agx-dia' + (fuera ? ' is-fuera' : '') + (n % 7 >= 5 ? ' is-finde' : '') + (f === CFG.hoy ? ' is-hoy' : '') + '" data-fecha="' + f + '" data-drop="dia" role="gridcell">'
                + '<div class="agx-dia-cab"><button type="button" class="agx-dia-n" data-ir-dia="' + f + '" data-tip="Ver el día" aria-label="' + esc(mayus(largo(f))) + ', ' + lista.length + ' actividades">' + aFecha(f).getDate() + '</button>'
                + '<button type="button" class="agx-dia-crear" data-crear-en="' + f + '" aria-label="Crear el ' + esc(largo(f)) + '" data-tip="Crear aquí">' + ico('mas') + '</button></div>'
                + lista.slice(0, cabe).map((i) => evMini(i)).join('')
                + (lista.length > cabe ? '<button type="button" class="agx-mas-n" data-mas-dia="' + f + '" aria-haspopup="dialog">+' + (lista.length - cabe) + ' más</button>' : '')
                + '</div>';
        }
        return h + '</div>';
    }

    // ----- Semana / día -----
    function acomodar(lista) {
        const ev = lista.map((i) => {
            const ini = minutos(i.hora);
            const fin = i.hora_fin && i.fecha_fin === i.fecha ? Math.max(minutos(i.hora_fin), ini + 20) : ini + 60;
            return { i, ini, fin, col: 0, cols: 1 };
        }).sort((a, b) => a.ini - b.ini || b.fin - a.fin);
        let grupo = []; let finGrupo = -1;
        const cerrar = () => { const n = Math.max(1, ...grupo.map((g) => g.col + 1)); grupo.forEach((g) => { g.cols = n; }); grupo = []; };
        ev.forEach((e) => {
            if (e.ini >= finGrupo) { cerrar(); finGrupo = -1; }
            const usadas = grupo.filter((g) => g.fin > e.ini).map((g) => g.col);
            let c = 0; while (usadas.includes(c)) c++;
            e.col = c; grupo.push(e); finGrupo = Math.max(finGrupo, e.fin);
        });
        cerrar();
        return ev;
    }

    function vistaSemana(n) {
        const inicio = n === 1 ? S.fecha : lunes(S.fecha);
        const dias = Array.from({ length: n }, (_, k) => sumar(inicio, k));
        const alto = parseFloat(getComputedStyle(raiz).getPropertyValue('--agx-hora') || '48');
        let h = '<div class="agx-sem" style="--dias:' + n + '"><div></div>'
            + dias.map((f) => '<button type="button" class="agx-sem-h' + (f === CFG.hoy ? ' is-hoy' : '') + '" data-ir-dia="' + f + '" data-tip="' + esc(mayus(largo(f))) + '">' + diaSem(f) + '<b>' + aFecha(f).getDate() + '</b></button>').join('')
            + '<div class="agx-todo-lbl">todo el día</div>'
            + dias.map((f) => {
                const sinHora = delDia(f).filter((i) => i.todo_el_dia || !i.hora || i.fecha !== f);
                return '<div class="agx-todo-dia" data-fecha="' + f + '" data-drop="dia-sin-hora">' + sinHora.slice(0, 3).map((i) => evMini(i, false)).join('')
                    + (sinHora.length > 3 ? '<button type="button" class="agx-mas-n" data-mas-dia="' + f + '">+' + (sinHora.length - 3) + ' más</button>' : '') + '</div>';
            }).join('')
            + '</div><div class="agx-rej" style="--dias:' + n + '"><div class="agx-horas">'
            + Array.from({ length: 24 }, (_, hh) => '<div>' + (hh ? '<span>' + String(hh).padStart(2, '0') + ':00</span>' : '') + '</div>').join('') + '</div>';
        dias.forEach((f) => {
            h += '<div class="agx-col' + (f === CFG.hoy ? ' is-hoy' : '') + '" data-fecha="' + f + '">';
            for (let hh = 0; hh < 24; hh++) h += '<div class="agx-slot" data-fecha="' + f + '" data-hora="' + String(hh).padStart(2, '0') + ':00" data-drop="hora"></div>';
            acomodar(delDia(f).filter((i) => i.hora && !i.todo_el_dia && i.fecha === f)).forEach((e) => {
                const top = (e.ini / 60) * alto;
                const altura = Math.max(((e.fin - e.ini) / 60) * alto - 2, 20);
                const ancho = 100 / e.cols;
                h += evCol(e.i, 'top:' + top + 'px;height:' + altura + 'px;left:calc(' + (ancho * e.col) + '% + 2px);width:calc(' + ancho + '% - 4px)', altura, n > 1 && e.cols >= 3);
            });
            if (f === CFG.hoy) h += '<div class="agx-ahora" style="top:' + (minutos(ahoraHM()) / 60) * alto + 'px" aria-hidden="true"></div>';
            h += '</div>';
        });
        return h + '</div>';
    }

    // ----- Filas (lista, celular, secciones) -----
    function fila(i, opciones = {}) {
        const puedeCompletar = i.puede && i.puede.completar && !cerrado(i);
        const conCheck = opciones.check !== false && (i.clase === 'tarea' || i.fuente === 'seguimiento') && i.fuente !== 'ocupado';
        const sub = [];
        if (i.cliente && i.cliente.nombre && i.fuente !== 'seguimiento') sub.push('<span>' + esc(i.cliente.nombre) + '</span>');
        sub.push('<span>' + esc(i.motivo || i.tipo_label) + '</span>');
        if (i.ubicacion) sub.push('<span>' + esc(i.ubicacion) + '</span>');
        if (i.responsable && i.responsable.nombre && i.responsable.id !== CFG.yo.id) sub.push('<span>' + esc(i.responsable.nombre) + '</span>');
        const acc = [];
        if (i.cliente && i.cliente.url) acc.push('<a class="agx-ico" href="' + esc(i.cliente.url) + '" aria-label="Abrir expediente de ' + esc(i.cliente.nombre) + '" data-tip="Abrir expediente del cliente">' + ico('cliente') + '</a>');
        if (i.puede && i.puede.mover) acc.push('<button type="button" class="agx-ico" data-rapido="mover" data-k="' + esc(i.key) + '" aria-label="Reprogramar ' + esc(i.titulo) + '" data-tip="Reprogramar">' + ico('calendario') + '</button>');
        return '<div class="agx-fila agx-t-' + esc(i.tipo) + (cerrado(i) ? ' is-cerrado' : '') + (conCheck ? '' : ' sin-check') + '" data-fila="' + esc(i.key) + '">'
            + '<button type="button" class="agx-fila-abrir" data-key="' + esc(i.key) + '" aria-label="' + esc(etiquetaEv(i)) + '"></button>'
            + (conCheck ? '<button type="button" class="agx-check-btn' + (i.estado === 'completado' || i.estado === 'realizado' ? ' is-hecho' : '') + '" data-completar="' + esc(i.key) + '"' + (puedeCompletar ? '' : ' disabled') + ' aria-label="Completar ' + esc(i.titulo) + '" data-tip="' + esc(puedeCompletar ? 'Marcar como completado' : (estadoInfo(i).txt || 'No disponible')) + '">' + ico('check') + '</button>' : '')
            + '<span class="agx-fila-h">' + esc(i.hora || (i.fuente === 'seguimiento' ? 'Sin hora' : 'Todo el día')) + (opciones.fecha ? '<small>' + esc(corto(i.fecha)) + '</small>' : i.hora_fin ? '<small>' + esc(i.hora_fin) + '</small>' : '') + '</span>'
            + '<span class="agx-fila-cuerpo"><span class="agx-fila-barra"></span><span style="min-width:0;flex:1"><span class="agx-fila-t" style="display:block">' + esc(i.titulo) + '</span><span class="agx-fila-s">' + sub.join('<span aria-hidden="true">·</span>') + '</span></span></span>'
            + '<span class="agx-fila-der">' + (i.prioridad === 'alta' && !cerrado(i) ? '<span class="agx-estado is-alta">Alta</span>' : '') + (i.origen === 'cliente' ? '<span class="agx-estado">' + ico('enlace') + 'Clientes</span>' : '') + estadoChip(i) + '<span class="agx-fila-acc">' + acc.join('') + '</span></span>'
            + '</div>';
    }

    function vistaLista() {
        const [desde, hasta] = rango();
        let h = '<div class="agx-lst">'; let hay = false;
        for (let f = desde; f <= hasta; f = sumar(f, 1)) {
            const lista = delDia(f);
            if (!lista.length) continue;
            hay = true;
            h += '<div class="agx-lst-dia' + (f === CFG.hoy ? ' is-hoy' : '') + '"><b>' + esc(f === CFG.hoy ? 'Hoy' : mayus(largo(f))) + '</b>' + (f === CFG.hoy ? esc(largo(f)) : '') + '<span>· ' + lista.length + '</span></div>' + lista.map((i) => fila(i)).join('');
        }
        return h + (hay ? '' : vacio('Sin actividades en los próximos 30 días', 'Crea una actividad o elige otro periodo.', true)) + '</div>';
    }

    function vistaMovil() {
        const lista = delDia(S.fecha);
        return '<div class="agx-lst"><div class="agx-lst-dia' + (S.fecha === CFG.hoy ? ' is-hoy' : '') + '"><b>' + esc(S.fecha === CFG.hoy ? 'Hoy' : mayus(largo(S.fecha))) + '</b>' + (S.fecha === CFG.hoy ? esc(largo(S.fecha)) : '') + (lista.length ? '<span>· ' + lista.length + '</span>' : '') + '</div>'
            + (lista.length ? lista.map((i) => fila(i)).join('') : vacio('Día libre', 'No tienes nada este día.', true)) + '</div>';
    }

    function pintarTira() {
        const l = lunes(S.fecha);
        const items = S.items || [];
        $('#agxTira').innerHTML = Array.from({ length: 7 }, (_, k) => {
            const f = sumar(l, k);
            const tiene = items.some((i) => i.fecha <= f && i.fecha_fin >= f && !S.ocultos.has(i.tipo));
            return '<button type="button" class="' + (f === S.fecha ? 'is-on ' : '') + (f === CFG.hoy ? 'is-hoy ' : '') + (tiene ? 'tiene' : '') + '" data-dia-tira="' + f + '" aria-pressed="' + (f === S.fecha) + '" aria-label="' + esc(mayus(largo(f))) + (tiene ? ', con actividades' : '') + '"><small>' + diaSem(f).slice(0, 2) + '</small><b>' + aFecha(f).getDate() + '</b><i></i></button>';
        }).join('');
    }

    // ----- Hoy -----
    function vistaHoy() {
        const p = S.paneles || {};
        const hoy = (p.hoy || []).filter((i) => !S.ocultos.has(i.tipo));
        const abiertos = hoy.filter((i) => !cerrado(i) && i.fuente !== 'congreso');
        const hechos = hoy.filter((i) => i.estado === 'completado' || i.estado === 'realizado').length;
        const total = hoy.filter((i) => i.estado !== 'cancelado' && i.fuente !== 'congreso').length;
        const pct = total ? Math.round((hechos / total) * 100) : 0;
        const ahora = ahoraHM();
        const proxima = abiertos.filter((i) => i.hora && (i.hora_fin || i.hora) >= ahora).sort((a, b) => a.hora.localeCompare(b.hora))[0] || abiertos.find((i) => !i.hora) || null;
        const esEmpresa = (i) => i.visibilidad === 'empresa' || i.visibilidad === 'grupo' || i.fuente === 'congreso';
        const sinProx = (i) => !proxima || i.key !== proxima.key;
        const seg = hoy.filter((i) => i.fuente === 'seguimiento' && sinProx(i));
        const empresa = hoy.filter((i) => esEmpresa(i) && sinProx(i));
        const otras = hoy.filter((i) => i.fuente === 'actividad' && !esEmpresa(i) && sinProx(i));
        const venc = (p.vencidos || []).filter((i) => !S.ocultos.has(i.tipo));

        let h = '<div class="agx-hoy"><div class="agx-hoy-cab"><div class="agx-anillo" style="background:conic-gradient(var(--green) ' + pct + '%, var(--agx-linea) 0)" role="img" aria-label="Progreso del día: ' + hechos + ' de ' + total + '" data-tip="' + hechos + ' de ' + total + ' completadas hoy"><b>' + pct + '%</b></div>'
            + '<div><h3>' + esc(mayus(largo(CFG.hoy))) + '</h3><p>' + (total ? hechos + ' de ' + total + ' completadas' + (abiertos.length ? ' · ' + abiertos.length + ' por hacer' : ' · todo listo') : 'Nada programado para hoy') + (venc.length ? ' · <span style="color:var(--danger)">' + venc.length + ' vencido(s)</span>' : '') + '</p></div></div>';
        if (proxima) {
            h += '<button type="button" class="agx-prox agx-t-' + esc(proxima.tipo) + '" data-key="' + esc(proxima.key) + '"><span class="agx-prox-ico">' + ico(proxima.fuente === 'seguimiento' ? 'cliente' : 'calendario') + '</span>'
                + '<span style="min-width:0"><span class="agx-prox-k">Próxima actividad</span><span class="agx-prox-t" style="display:block">' + esc(proxima.titulo) + '</span><span class="agx-prox-s" style="display:block">' + esc([proxima.tipo_label, proxima.motivo, proxima.ubicacion].filter(Boolean).join(' · ')) + '</span></span>'
                + '<span class="agx-prox-h">' + esc(proxima.hora || 'Hoy') + '<small>' + esc(proxima.hora ? (proxima.hora_fin ? 'hasta ' + proxima.hora_fin : '') : 'sin hora') + '</small></span></button>';
        }
        const grupo = (t, lista, op) => (lista.length ? '<div class="agx-sec-t">' + t + ' <span class="agx-n">' + lista.length + '</span></div>' + lista.map((i) => fila(i, op)).join('') : '');
        h += grupo('Seguimientos de hoy', seg);
        h += grupo('Tareas y actividades', otras);
        h += grupo('Eventos de la empresa', empresa, { check: false });
        h += grupo('Vencidos', venc.slice(0, 15), { fecha: true });
        if (venc.length > 15) h += '<button type="button" class="agx-btn agx-btn--sm" style="margin:8px 6px" data-seccion="vencidos">Ver los ' + venc.length + ' vencidos</button>';
        if (!hoy.length && !venc.length) h += vacio('Día despejado', 'No tienes actividades ni pendientes vencidos.', true);
        return h + '</div>';
    }

    function vistaVencidos() {
        const lista = ((S.paneles || {}).vencidos || []).filter((i) => !S.ocultos.has(i.tipo));
        return '<div class="agx-lst">' + (lista.length ? '<div class="agx-lst-dia"><b>' + lista.length + ' pendiente(s)</b> complétalos o reprográmalos</div>' + lista.map((i) => fila(i, { fecha: true })).join('') : vacio('Sin vencidos', 'Vas al día con tus tareas y seguimientos.')) + '</div>';
    }

    function vistaSin() {
        const sin = (S.paneles || {}).sin_seguimiento || { total: 0, lista: [] };
        if (!sin.lista.length) return '<div class="agx-lst">' + vacio('Todos tienen siguiente paso', 'Cada prospecto activo tiene un seguimiento programado.') + '</div>';
        return '<div class="agx-lst"><div class="agx-lst-dia"><b>' + sin.total + ' prospecto(s)</b> sin seguimiento programado' + (sin.total > sin.lista.length ? ' · se muestran ' + sin.lista.length : '') + '</div>'
            + sin.lista.map((c) => '<div class="agx-fila sin-check agx-t-otro"><a class="agx-fila-abrir" href="' + esc(c.url) + '" aria-label="Abrir expediente de ' + esc(c.nombre) + '"></a>'
                + '<span class="agx-fila-h">' + esc(c.alta || '') + '<small>alta</small></span>'
                + '<span class="agx-fila-cuerpo"><span class="agx-fila-barra"></span><span style="min-width:0"><span class="agx-fila-t" style="display:block">' + esc(c.nombre) + '</span><span class="agx-fila-s"><span>' + esc([c.asesor, c.telefono].filter(Boolean).join(' · ') || 'Prospecto') + '</span></span></span></span>'
                + '<span class="agx-fila-der"><button type="button" class="agx-btn agx-btn--sm" style="pointer-events:auto" data-programar-cli="' + c.id + '" data-nombre="' + esc(c.nombre) + '">' + ico('mas') + 'Programar</button></span></div>').join('') + '</div>';
    }

    // ---------- Capa flotante: tooltips ----------
    const tip = document.createElement('div');
    tip.className = 'agx-tip'; tip.setAttribute('role', 'tooltip'); tip.id = 'agxTip'; tip.hidden = true;
    capa.appendChild(tip);
    let tipTimer = null; let tipDe = null; let tipCaliente = 0;

    function posicionar(el, ancla, { lado = 'abajo', sep = 8 } = {}) {
        const r = ancla.getBoundingClientRect();
        const w = el.offsetWidth; const h = el.offsetHeight;
        const vw = window.innerWidth; const vh = window.innerHeight;
        let top; let left;
        if (lado === 'derecha') {
            left = r.right + sep; top = r.top;
            if (left + w > vw - 8) left = r.left - w - sep;
        } else if (lado === 'arriba') {
            top = r.top - h - sep; left = r.left + (r.width - w) / 2;
            if (top < 8) top = r.bottom + sep;
        } else {
            top = r.bottom + sep; left = r.left;
            if (top + h > vh - 8) top = r.top - h - sep;
        }
        el.style.left = Math.max(8, Math.min(left, vw - w - 8)) + 'px';
        el.style.top = Math.max(8, Math.min(top, vh - h - 8)) + 'px';
    }

    function textoTip(el) {
        if (el.hasAttribute('data-tip-auto')) return el.scrollWidth > el.clientWidth + 1 ? el.textContent.trim() : '';
        return el.getAttribute('data-tip') || '';
    }
    function mostrarTip(el) {
        if (!document.contains(el)) return;
        const t = textoTip(el); if (!t) return;
        tip.innerHTML = esc(t) + (el.dataset.tipAtajo ? '<kbd>' + esc(el.dataset.tipAtajo) + '</kbd>' : '');
        tip.hidden = false;
        const enNav = !!el.closest('.agx-nav');
        posicionar(tip, el, { lado: enNav ? 'derecha' : 'arriba', sep: 6 });
        requestAnimationFrame(() => tip.classList.add('is-on'));
        el.setAttribute('aria-describedby', 'agxTip');
        tipDe = el;
    }
    function ocultarTip() {
        clearTimeout(tipTimer);
        if (tipDe) { tipDe.removeAttribute('aria-describedby'); tipCaliente = Date.now(); }
        tipDe = null; tip.classList.remove('is-on'); tip.hidden = true;
    }
    function quiereTip(el) {
        if (!el || tactil()) return;
        clearTimeout(tipTimer);
        tipTimer = setTimeout(() => mostrarTip(el), Date.now() - tipCaliente < 400 ? 60 : 450);
    }
    document.addEventListener('mouseover', (e) => {
        const el = e.target.closest && e.target.closest('[data-tip], [data-tip-auto]');
        if (!el || !(raiz.contains(el) || capa.contains(el)) || el === tipDe) return;
        if (el.closest('.agx-ev') && !el.closest('.agx-ev-i')) return; // el evento usa vista previa
        ocultarTip(); quiereTip(el);
    });
    document.addEventListener('mouseout', (e) => {
        const el = e.target.closest && e.target.closest('[data-tip], [data-tip-auto]');
        if (el && (!e.relatedTarget || !el.contains(e.relatedTarget))) ocultarTip();
    });
    document.addEventListener('focusin', (e) => {
        const el = e.target.closest && e.target.closest('[data-tip]');
        if (el && (raiz.contains(el) || capa.contains(el)) && el.matches(':focus-visible')) { ocultarTip(); quiereTip(el); }
    });
    document.addEventListener('focusout', ocultarTip);
    window.addEventListener('scroll', ocultarTip, true);

    // ---------- Capa flotante: popovers, menús, vista previa ----------
    let flot = null; let flotAncla = null; let flotCerrarTimer = null; let flotTipo = null; let pvTimer = null;

    function abrirFlot(ancla, html, { tipo = 'pop', clase = '', lado = 'abajo', foco = true } = {}) {
        cerrarFlot();
        ocultarTip();
        flot = document.createElement('div');
        flot.className = 'agx-flot' + (tipo === 'menu' ? ' is-menu' : '') + (clase ? ' ' + clase : '');
        flot.setAttribute('role', tipo === 'menu' ? 'menu' : 'dialog');
        flot.innerHTML = html;
        capa.appendChild(flot);
        posicionar(flot, ancla, { lado });
        requestAnimationFrame(() => flot && flot.classList.add('is-on'));
        flotAncla = ancla; flotTipo = tipo;
        if (ancla.setAttribute && ancla.hasAttribute('aria-expanded')) ancla.setAttribute('aria-expanded', 'true');
        if (foco) { const p = flot.querySelector('input:not([type=hidden]), select, textarea, button, a'); if (p) setTimeout(() => p.focus({ preventScroll: true }), 30); }
        flot.addEventListener('mouseenter', () => clearTimeout(flotCerrarTimer));
        flot.addEventListener('mouseleave', () => { if (flotTipo === 'preview') programarCierre(); });
        return flot;
    }
    function cerrarFlot({ volverFoco } = {}) {
        clearTimeout(flotCerrarTimer); clearTimeout(pvTimer);
        if (!flot) return;
        const viejo = flot; const ancla = flotAncla;
        flot = null; flotAncla = null; flotTipo = null;
        viejo.classList.remove('is-on');
        setTimeout(() => viejo.remove(), 170);
        if (ancla && ancla.hasAttribute && ancla.hasAttribute('aria-expanded')) ancla.setAttribute('aria-expanded', 'false');
        if (volverFoco && ancla && ancla.focus && document.contains(ancla)) ancla.focus({ preventScroll: true });
    }
    function programarCierre() { clearTimeout(flotCerrarTimer); flotCerrarTimer = setTimeout(() => cerrarFlot(), 220); }
    document.addEventListener('mousedown', (e) => {
        if (flot && !flot.contains(e.target) && !(flotAncla && flotAncla.contains && flotAncla.contains(e.target))) cerrarFlot();
    });
    window.addEventListener('resize', () => cerrarFlot());

    // Menús: las flechas recorren las opciones.
    capa.addEventListener('keydown', (e) => {
        if (!flot || flotTipo !== 'menu' || !['ArrowDown', 'ArrowUp'].includes(e.key)) return;
        const op = $$('.agx-mi', flot); const i = op.indexOf(document.activeElement);
        e.preventDefault();
        (op[(i + (e.key === 'ArrowDown' ? 1 : -1) + op.length) % op.length] || op[0]).focus();
    });

    function buscarItem(key) {
        const p = S.paneles || {};
        return (S.items || []).concat(p.hoy || [], p.vencidos || [], p.proximos || []).find((x) => x.key === key) || null;
    }

    // Vista previa interactiva: al pasar el cursor (con retraso) o con el foco del teclado.
    function contenidoPreview(i) {
        const lineas = [['reloj', cuandoTxt(i)]];
        const e = estadoInfo(i);
        if (e.txt && i.fuente !== 'congreso') lineas.push([e.ico || 'bandera', e.txt + (i.prioridad === 'alta' ? ' · prioridad alta' : '')]);
        if (i.responsable && i.responsable.nombre) lineas.push(['usuario', i.responsable.nombre]);
        if (i.cliente && i.cliente.nombre) lineas.push(['cliente', i.cliente.nombre + (i.cliente.etapa === 'prospecto' ? ' (prospecto)' : '')]);
        if (i.motivo) lineas.push(['nota', i.motivo]);
        if (i.ubicacion) lineas.push(['lugar', i.ubicacion]);
        if (i.origen === 'cliente') lineas.push(['enlace', 'Viene de Clientes']);
        const p = i.puede || {};
        const acc = [];
        if (i.fuente === 'congreso') { if (i.url) acc.push('<a class="agx-btn agx-btn--sm" href="' + esc(i.url) + '">Abrir congresos</a>'); }
        else acc.push('<button type="button" class="agx-btn agx-btn--sm" data-key="' + esc(i.key) + '">Ver detalle</button>');
        if (p.completar) acc.push('<button type="button" class="agx-btn agx-btn--sm agx-btn--primario" data-rapido="completar" data-k="' + esc(i.key) + '">' + ico('check') + (i.clase === 'evento' && i.fuente !== 'seguimiento' ? 'Realizado' : 'Completar') + '</button>');
        if (p.mover) acc.push('<button type="button" class="agx-btn agx-btn--sm" data-rapido="mover" data-k="' + esc(i.key) + '">Reprogramar</button>');
        if (p.responder && i.mi_respuesta !== 'aceptada') acc.push('<button type="button" class="agx-btn agx-btn--sm" data-rapido="aceptar" data-k="' + esc(i.key) + '">Asistiré</button>');
        return '<div class="agx-pv agx-t-' + esc(i.tipo) + '"><div class="agx-pv-tipo">' + esc(i.tipo_label) + '</div><div class="agx-pv-t">' + esc(i.titulo) + '</div>'
            + lineas.map(([n, t]) => '<div class="agx-pv-l">' + ico(n) + '<span>' + esc(t) + '</span></div>').join('')
            + '<div class="agx-pv-acc">' + acc.join('') + '</div></div>';
    }
    function previewDe(ev) {
        clearTimeout(pvTimer);
        const i = buscarItem(ev.dataset.key);
        if (!i || i.fuente === 'ocupado') return;
        pvTimer = setTimeout(() => {
            if (!document.contains(ev) || (flot && flotTipo !== 'preview')) return; // no tapa un menú abierto
            abrirFlot(ev, contenidoPreview(i), { tipo: 'preview', lado: ev.closest('.agx-col') ? 'derecha' : 'abajo', foco: false });
        }, 380);
    }
    function alEntrarEv(e) {
        if (tactil()) return;
        const ev = e.target.closest('.agx-ev[data-key]');
        if (!ev || (e.relatedTarget && ev.contains(e.relatedTarget))) return;
        if (flot && flotTipo === 'preview' && flotAncla === ev) { clearTimeout(flotCerrarTimer); return; }
        previewDe(ev);
    }
    function alSalirEv(e) {
        const ev = e.target.closest('.agx-ev[data-key]');
        if (!ev || (e.relatedTarget && (ev.contains(e.relatedTarget) || (flot && flot.contains(e.relatedTarget))))) return;
        clearTimeout(pvTimer);
        if (flot && flotTipo === 'preview' && flotAncla === ev) programarCierre();
    }
    raiz.addEventListener('mouseover', alEntrarEv);
    raiz.addEventListener('mouseout', alSalirEv);
    raiz.addEventListener('focusin', (e) => { const ev = e.target.closest('.agx-ev[data-key]'); if (ev && ev.matches(':focus-visible') && !tactil()) previewDe(ev); });
    raiz.addEventListener('focusout', (e) => {
        const ev = e.target.closest('.agx-ev[data-key]');
        if (ev && flotTipo === 'preview' && !(e.relatedTarget && flot && flot.contains(e.relatedTarget))) { clearTimeout(pvTimer); programarCierre(); }
    });

    // "+N más": lista del día en un popover.
    function popDia(boton, f) {
        const lista = delDia(f);
        abrirFlot(boton, '<h4>' + esc(mayus(largo(f))) + ' <small>' + lista.length + ' actividades</small></h4><div class="agx-dia-lista">' + lista.map((i) => evMini(i)).join('') + '</div>'
            + '<div class="agx-pv-acc"><button type="button" class="agx-btn agx-btn--sm" data-ir-dia="' + f + '">Ver el día</button><button type="button" class="agx-btn agx-btn--sm" data-crear-en="' + f + '">' + ico('mas') + 'Crear</button></div>', { clase: 'is-ancho' });
    }
    capa.addEventListener('mouseover', alEntrarEv);
    capa.addEventListener('mouseout', alSalirEv);

    function menuMas(boton) {
        const op = [['ics', 'descarga', 'Exportar a calendario (.ics)'], ['preferencias', 'campana', 'Mis avisos y recordatorios']];
        if (CFG.puede.carga) op.push(['carga', 'barras', 'Carga de trabajo']);
        if (CFG.puede.equipos) op.push(['equipos', 'grupo', 'Equipos de trabajo']);
        abrirFlot(boton, op.map(([a, i, t]) => '<button type="button" class="agx-mi" role="menuitem" data-menu="' + a + '">' + ico(i) + esc(t) + '</button>').join(''), { tipo: 'menu' });
    }

    // Filtros avanzados: popover en escritorio, hoja inferior en celular.
    function htmlFiltros() {
        const op = (v, t, actual) => '<option value="' + esc(v) + '"' + (String(actual || '') === String(v) ? ' selected' : '') + '>' + esc(t) + '</option>';
        const sel = (k, opciones, txt) => '<label class="agx-campo"><span>' + esc(txt[0]) + '</span><select data-filtro="' + k + '"><option value="">' + esc(txt[1]) + '</option>' + opciones + '</select></label>';
        const f = S.filtros;
        let h = '<div class="agx-form">';
        if (CFG.puede.verOtros) {
            h += sel('usuario', CFG.supervisados.filter((u) => u.id !== CFG.yo.id).map((u) => op(u.id, u.name, f.usuario)).join(''), ['Agenda de', 'Yo']);
            h += sel('departamento', CFG.departamentos.map((d) => op(d.id, d.nombre, f.departamento)).join(''), ['Departamento', 'Todos']);
        }
        h += sel('tipo', '<optgroup label="Seguimientos">' + op('seguimiento', 'Todos los seguimientos', f.tipo) + Object.entries(CFG.tiposContacto).map(([v, t]) => op('seg:' + v, t, f.tipo)).join('') + '</optgroup><optgroup label="Actividades">'
            + CFG.tipos.filter((t) => t.valor !== 'seguimiento').map((t) => op(t.valor, t.label, f.tipo)).join('') + op('congreso', 'Congresos', f.tipo) + '</optgroup>', ['Tipo', 'Todos']);
        h += sel('estado', [['vencido', 'Vencido'], ['pendiente', 'Pendiente'], ['en_proceso', 'En proceso'], ['completado', 'Completado'], ['programado', 'Programado'], ['realizado', 'Realizado'], ['cancelado', 'Cancelado']].map(([v, t]) => op(v, t, f.estado)).join(''), ['Estado', 'Todos']);
        h += sel('prioridad', Object.entries(CFG.prioridades).map(([v, t]) => op(v, t, f.prioridad)).join(''), ['Prioridad', 'Todas']);
        h += sel('origen', op('cliente', 'Automático (desde Clientes)', f.origen) + op('manual', 'Manual (desde la Agenda)', f.origen), ['Origen', 'Todos']);
        h += '<label class="agx-campo agx-full"><span>Cliente</span><input type="search" id="agxFiltroCli" placeholder="Buscar cliente…" autocomplete="off" value="' + esc(S.cliente ? S.cliente.nombre : '') + '"><div class="agx-sug" id="agxFiltroCliSug" hidden></div></label>';
        h += '<div class="agx-pie"><button type="button" class="agx-btn agx-btn--sm agx-izq" data-limpiar>Quitar filtros</button><button type="button" class="agx-btn agx-btn--sm agx-btn--primario" data-cerrar-flot>Listo</button></div></div>';
        return h;
    }
    function abrirFiltros(boton) {
        if (movil()) { abrirPanel('Filtros', htmlFiltros()); enlazarFiltros($('#agxPanelC')); return; }
        enlazarFiltros(abrirFlot(boton, '<h4>Filtros</h4>' + htmlFiltros(), { clase: 'is-ancho' }));
    }
    function enlazarFiltros(cont) {
        $$('[data-filtro]', cont).forEach((s) => s.addEventListener('change', () => { S.filtros[s.dataset.filtro] = s.value; cargar(); }));
        buscadorCliente(cont.querySelector('#agxFiltroCli'), null, cont.querySelector('#agxFiltroCliSug'), (c) => { S.cliente = c; cargar(); });
    }

    // ---------- Toast con "Deshacer" ----------
    let toastTimer = null;
    function toast(texto, { error, deshacer } = {}) {
        const t = $('#agxToast');
        clearTimeout(toastTimer);
        $('#agxToastTxt').textContent = texto;
        const b = $('#agxToastAcc');
        const ocultar = () => { t.classList.remove('is-on'); setTimeout(() => { if (!t.classList.contains('is-on')) t.hidden = true; }, 200); };
        b.hidden = !deshacer;
        b.onclick = deshacer ? async () => { b.hidden = true; ocultar(); await deshacer(); } : null;
        t.classList.toggle('is-error', !!error);
        t.hidden = false;
        requestAnimationFrame(() => t.classList.add('is-on'));
        toastTimer = setTimeout(ocultar, deshacer ? 6500 : error ? 5000 : 2800);
    }
    const guardando = (si) => { $('#agxGuardando').hidden = !si; };

    // ---------- Acciones rápidas ----------
    async function completarRapido(key, boton) {
        const i = buscarItem(key); if (!i) return;
        cerrarFlot();
        const nuevo = i.clase === 'evento' && i.fuente !== 'seguimiento' ? 'realizado' : 'completado';
        const antes = i.estado;
        // Confirmación inmediata en pantalla; si el servidor falla, se regresa.
        if (boton && boton.classList.contains('agx-check-btn')) boton.classList.add('is-hecho');
        const filaEl = $('[data-fila="' + CSS.escape(key) + '"]'); if (filaEl) filaEl.classList.add('is-cerrado', 'is-hecho-ahora');
        const evEl = $('.agx-ev[data-key="' + CSS.escape(key) + '"]'); if (evEl) evEl.classList.add('is-cerrado', 'is-hecho-ahora');
        guardando(true);
        try {
            await enviar(urlItem(key, 'estado'), 'POST', { estado: nuevo });
            const puedeDeshacer = i.fuente === 'actividad' || i.fecha >= CFG.hoy;
            toast(i.fuente === 'seguimiento' ? 'Seguimiento completado.' : 'Marcado como ' + (ESTADOS[nuevo] || nuevo).toLowerCase() + '.', {
                deshacer: puedeDeshacer ? async () => {
                    guardando(true);
                    try {
                        if (i.fuente === 'seguimiento') await enviar(urlItem(key, 'mover'), 'POST', { fecha: i.fecha, hora: i.hora || null, motivo: 'Se deshizo la marca de completado' });
                        else await enviar(urlItem(key, 'estado'), 'POST', { estado: antes === 'en_proceso' ? 'en_proceso' : (i.clase === 'evento' ? 'programado' : 'pendiente') });
                        toast('Se deshizo.');
                    } catch (e) { toast(primerError(e), { error: true }); }
                    guardando(false); cargar({ silencioso: true });
                } : null,
            });
        } catch (e) {
            if (boton) boton.classList.remove('is-hecho');
            if (filaEl) filaEl.classList.remove('is-cerrado');
            if (evEl) evEl.classList.remove('is-cerrado');
            toast(primerError(e), { error: true });
        }
        guardando(false);
        cargar({ silencioso: true });
    }

    async function responderRapido(key, respuesta) {
        cerrarFlot(); guardando(true);
        try { const r = await enviar(urlItem(key, 'responder'), 'POST', { respuesta }); toast(r.mensaje); }
        catch (e) { toast(primerError(e), { error: true }); }
        guardando(false); await cargar({ silencioso: true });
        if (S.abierto === key) abrirItem(key);
    }

    // ---------- Panel lateral (detalle / formulario) ----------
    let ultimoFoco = null;
    const panelAbierto = () => !$('#agxPanel').hidden;
    function abrirPanel(titulo, html, { tipo, color, acciones } = {}) {
        cerrarFlot(); ocultarTip();
        const panel = $('#agxPanel'); const velo = $('#agxVelo');
        if (panel.hidden) ultimoFoco = document.activeElement;
        $('#agxPanelT').textContent = titulo;
        $('#agxPanelTipo').textContent = tipo || '';
        const on = panel.classList.contains('is-on');
        panel.className = 'agx-panel' + (color ? ' agx-t-' + color : '') + (on ? ' is-on' : '');
        $('#agxPanelAcc').innerHTML = acciones || '';
        $('#agxPanelC').innerHTML = html;
        $('#agxPanelC').scrollTop = 0;
        panel.hidden = false; velo.hidden = false;
        requestAnimationFrame(() => { panel.classList.add('is-on'); velo.classList.add('is-on'); });
        setTimeout(() => $('#agxPanelT').focus({ preventScroll: true }), 40);
    }
    async function cerrarPanel({ forzar } = {}) {
        const f = $('#agxForm');
        if (!forzar && f && f.dataset.sucio === '1') {
            const ok = window.confirmModal ? await window.confirmModal({ title: 'Descartar cambios', message: 'Tienes cambios sin guardar. ¿Cerrar sin guardar?', confirmText: 'Descartar', danger: true }) : true;
            if (!ok) return;
        }
        const panel = $('#agxPanel'); const velo = $('#agxVelo');
        panel.classList.remove('is-on'); velo.classList.remove('is-on');
        setTimeout(() => { if (!panel.classList.contains('is-on')) { panel.hidden = true; velo.hidden = true; $('#agxPanelC').innerHTML = ''; } }, 220);
        S.abierto = null;
        if (ultimoFoco && document.contains(ultimoFoco)) ultimoFoco.focus({ preventScroll: true });
        const u = new URL(location.href); if (u.searchParams.has('item')) { u.searchParams.delete('item'); history.replaceState(null, '', u); }
    }

    async function abrirItem(key, { accion } = {}) {
        if (!key || key.startsWith('o-')) return;
        if (key.startsWith('c-')) {
            const i = buscarItem(key); if (!i) return;
            abrirPanel(i.titulo, '<div class="agx-props">' + ico('calendario') + '<div>' + esc(cuandoTxt(i)) + '</div>' + (i.ubicacion ? ico('lugar') + '<div>' + esc(i.ubicacion) + '</div>' : '') + ico('grupo') + '<div>Agenda de la empresa</div></div>'
                + (i.url ? '<div class="agx-acc-prin" style="margin-top:12px"><a class="agx-btn agx-btn--sm" href="' + esc(i.url) + '">Abrir congresos</a></div>' : ''), { tipo: 'Congreso', color: 'congreso' });
            return;
        }
        S.abierto = key;
        const previo = buscarItem(key);
        abrirPanel(previo ? previo.titulo : 'Cargando…', esqueletoDetalle(), { tipo: previo ? previo.tipo_label : '', color: previo ? previo.tipo : '' });
        try {
            const { item } = await pedir(urlItem(key));
            if (S.abierto !== key) return;
            pintarDetalle(item);
            if (accion) accionDetalle(accion);
        } catch (e) {
            if (S.abierto !== key) return;
            abrirPanel('No disponible', '<div class="agx-error">' + ico('alerta') + '<span>' + esc(e.status === 404 ? 'Esta actividad no existe o no tienes acceso a ella.' : e.message) + '</span></div>');
        }
    }
    function esqueletoDetalle() {
        const b = (w, h, m) => '<span class="agx-sk" style="width:' + w + ';height:' + (h || 12) + 'px;margin:' + (m || '0 0 10px') + '"></span>';
        return '<div aria-busy="true">' + b('70%', 28, '0 0 16px') + b('85%') + b('60%') + b('75%') + b('50%') + b('100%', 60, '14px 0 0') + '</div>';
    }

    const COLORES = ['#3b82f6', '#8b5cf6', '#14b8a6', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#22c55e', '#64748b'];
    const iniciales = (n) => { const p = String(n || '?').trim().split(/\s+/); return ((p[0] || '')[0] || '') + ((p[1] || '')[0] || ''); };
    function avatar(nombre, respuesta) {
        let h = 0; for (const c of String(nombre)) h = (h * 31 + c.charCodeAt(0)) >>> 0;
        const txt = { aceptada: 'asistirá', rechazada: 'no asistirá', pendiente: 'no ha respondido' }[respuesta];
        return '<span class="agx-av' + (respuesta ? ' r-' + respuesta : '') + '" style="--av:' + COLORES[h % COLORES.length] + '" data-tip="' + esc(nombre + (txt ? ' · ' + txt : '')) + '" role="img" aria-label="' + esc(nombre + (txt ? ', ' + txt : '')) + '" tabindex="0">' + esc(iniciales(nombre).toUpperCase()) + '</span>';
    }
    function avatares(lista, max = 3) {
        const resto = lista.slice(max);
        return '<span class="agx-avs">' + lista.slice(0, max).map((p) => avatar(p.nombre, p.respuesta)).join('')
            + (resto.length ? '<span class="agx-av agx-av-mas" data-tip="' + esc(resto.map((p) => p.nombre).join(', ')) + '" tabindex="0" role="img" aria-label="' + esc('y ' + resto.length + ' más: ' + resto.map((p) => p.nombre).join(', ')) + '">+' + resto.length + '</span>' : '') + '</span>';
    }

    function pintarDetalle(i) {
        const p = i.puede || {};
        const seg = i.fuente === 'seguimiento';
        const props = [];
        const prop = (n, html) => { if (html) props.push(ico(n) + '<div>' + html + '</div>'); };
        prop('reloj', esc(cuandoTxt(i)) + (i.serie && i.recurrencia ? '<br><small>' + esc(textoRecurrencia(i.recurrencia)) + '</small>' : ''));
        prop('bandera', estadoChip(i) + (i.prioridad === 'alta' ? ' <span class="agx-estado is-alta">Prioridad alta</span>' : i.prioridad === 'baja' ? ' <span class="agx-estado">Prioridad baja</span>' : ''));
        prop('usuario', esc(i.responsable && i.responsable.nombre) + ' <small>· responsable</small>');
        if (i.cliente) {
            prop('cliente', esc(i.cliente.nombre) + (i.cliente.etapa === 'prospecto' ? ' <span class="agx-estado">Prospecto</span>' : '')
                + (i.cliente_detalle ? '<br><small>' + esc([i.cliente_detalle.telefono, i.cliente_detalle.correo].filter(Boolean).join(' · ')) + '</small>' : '')
                + (i.cliente.url ? '<br><a href="' + esc(i.cliente.url) + '">Abrir expediente</a>' : ''));
        }
        if (i.participantes && i.participantes.length) {
            const r = { aceptada: 0, rechazada: 0, pendiente: 0 }; i.participantes.forEach((x) => { r[x.respuesta]++; });
            prop('grupo', avatares(i.participantes) + ' <small>' + r.aceptada + ' asistirán · ' + r.pendiente + ' sin responder' + (r.rechazada ? ' · ' + r.rechazada + ' no' : '') + '</small>');
        }
        if (i.roles && i.roles.length) prop('grupo', esc(i.roles.map((x) => x.nombre).join(', ')) + ' <small>· departamentos</small>');
        if (i.equipos && i.equipos.length) prop('grupo', esc(i.equipos.map((x) => x.nombre).join(', ')) + ' <small>· equipos</small>');
        prop('lugar', esc(i.ubicacion));
        if (i.enlace) prop('video', '<a href="' + esc(i.enlace) + '" target="_blank" rel="noopener noreferrer">Unirse a la videollamada</a>');
        const visTxt = { privado: 'Privado', participantes: 'Compartido con participantes', grupo: 'Departamento o equipo', empresa: 'Toda la empresa' }[i.visibilidad];
        prop(i.visibilidad === 'privado' ? 'candado' : 'ojo', esc(visTxt) + (i.fuente === 'actividad' && !i.ocupa_horario ? ' <small>· informativo, no ocupa horario</small>' : ''));
        prop('enlace', seg ? '<span data-tip="' + esc(i.origen === 'cliente' ? ORIGEN_TIP : ORIGEN_AGENDA_TIP) + '" tabindex="0">' + (i.origen === 'cliente' ? 'Automático, desde Clientes' : 'Creado en la Agenda · también en Clientes') + '</span>' : (i.creador ? 'Creado por ' + esc(i.creador) : ''));
        if (i.resultado) prop('check', '<b>Resultado:</b> ' + esc(i.resultado));
        if (i.motivo_cancelacion) prop('x', '<b>Motivo de cancelación:</b> ' + esc(i.motivo_cancelacion));
        if (i.anterior) prop('repetir', '<small>Contacto anterior: ' + esc(i.anterior.fecha + (i.anterior.resultado ? ' · ' + i.anterior.resultado : '')) + '</small>');

        const prin = [];
        if (p.responder) {
            prin.push('<button type="button" class="agx-btn agx-btn--sm' + (i.mi_respuesta === 'aceptada' ? ' agx-btn--primario' : '') + '" data-a="responder" data-v="aceptada" aria-pressed="' + (i.mi_respuesta === 'aceptada') + '">' + ico('check') + 'Asistiré</button>');
            prin.push('<button type="button" class="agx-btn agx-btn--sm' + (i.mi_respuesta === 'rechazada' ? ' agx-btn--peligro' : '') + '" data-a="responder" data-v="rechazada" aria-pressed="' + (i.mi_respuesta === 'rechazada') + '">No asistiré</button>');
        }
        if (p.completar) prin.push('<button type="button" class="agx-btn agx-btn--sm agx-btn--primario" data-a="completar">' + ico('check') + (i.clase === 'evento' && !seg ? 'Marcar realizado' : 'Completar') + '</button>');
        if (p.mover) prin.push('<button type="button" class="agx-btn agx-btn--sm" data-a="mover">' + ico('calendario') + 'Reprogramar</button>');
        if (p.editar) prin.push('<button type="button" class="agx-btn agx-btn--sm" data-a="editar">' + ico('editar') + 'Editar</button>');

        const menu = [];
        if (p.completar && i.clase === 'tarea' && i.estado === 'pendiente') menu.push(['en_proceso', 'proceso', 'Marcar en proceso']);
        if (p.reasignar) menu.push(['reasignar', 'usuario', 'Reasignar']);
        if (!CERRADOS.includes(i.estado)) menu.push(['posponer', 'campana', 'Posponer recordatorio']);
        if (p.duplicar) menu.push(['duplicar', 'copiar', 'Duplicar']);
        if (p.cancelar) menu.push(['-'], ['cancelar', 'x', 'Cancelar ' + (seg ? 'seguimiento' : 'actividad'), true]);
        const acciones = menu.length ? '<button type="button" class="agx-ico" data-menu-item aria-haspopup="menu" aria-expanded="false" aria-label="Más acciones" data-tip="Más acciones">' + ico('puntos') + '</button>' : '';

        const desc = i.descripcion || '';
        const largaDesc = desc.length > 240 || desc.split('\n').length > 5;
        let h = (prin.length ? '<div class="agx-acc-prin">' + prin.join('') + '</div>' : '') + '<div id="agxAccion"></div>'
            + '<div class="agx-props">' + props.join('') + '</div>'
            + (desc ? '<div class="agx-desc' + (largaDesc ? ' is-corto' : '') + '">' + esc(desc) + '</div>' + (largaDesc ? '<button type="button" class="agx-vermas" data-vermas>Ver más</button>' : '') : '');

        const pasos = i.checklist || [];
        const hechos = pasos.filter((c) => c.hecho).length;
        h += '<details class="agx-det"' + (pasos.length ? ' open' : '') + '><summary>' + ico('pasos') + 'Checklist' + (pasos.length ? ' <span class="agx-n">' + hechos + '/' + pasos.length + '</span>' : '') + ico('abajo') + '</summary>'
            + (pasos.length ? '<div class="agx-barra" style="margin:0 0 6px"><i style="width:' + Math.round((hechos / pasos.length) * 100) + '%"></i></div>' : '')
            + pasos.map((c) => '<label class="agx-paso' + (c.hecho ? ' is-hecho' : '') + '"><input type="checkbox" data-paso="' + c.id + '"' + (c.hecho ? ' checked' : '') + '><span>' + esc(c.texto) + '</span></label>').join('')
            + (p.editar || p.completar ? '<form data-f="paso" class="agx-form" style="grid-template-columns:1fr auto;margin:6px 0 8px"><label class="agx-campo"><span class="agx-sr">Nuevo paso</span><input type="text" name="texto" maxlength="255" placeholder="Agregar un paso…" required></label><button class="agx-btn agx-btn--sm" style="align-self:end">Agregar</button></form>' : (pasos.length ? '' : '<p class="agx-muted" style="margin:0 0 8px">Sin pasos.</p>'))
            + '</details>';

        const coms = i.comentarios || [];
        h += '<details class="agx-det"' + (coms.length ? ' open' : '') + '><summary>' + ico('comentario') + 'Comentarios' + (coms.length ? ' <span class="agx-n">' + coms.length + '</span>' : '') + ico('abajo') + '</summary>'
            + coms.map((c) => '<div class="agx-com"><small>' + esc(c.usuario) + ' · ' + esc(c.cuando) + '</small>' + esc(c.texto).replace(/\n/g, '<br>') + '</div>').join('')
            + '<form data-f="comentario" style="margin:8px 0"><label class="agx-campo"><span class="agx-sr">Comentario</span><textarea name="texto" maxlength="2000" rows="2" placeholder="Comentario interno para el equipo…" required></textarea></label><div class="agx-pie"><button class="agx-btn agx-btn--sm">Comentar</button></div></form></details>';

        if ((i.mis_recordatorios || []).length) {
            h += '<details class="agx-det"><summary>' + ico('campana') + 'Mis recordatorios <span class="agx-n">' + i.mis_recordatorios.length + '</span>' + ico('abajo') + '</summary>'
                + i.mis_recordatorios.map((r) => '<div class="agx-part"><span>' + esc({ recordatorio: 'Recordatorio', vencido: 'Aviso si se vence', pospuesto: 'Pospuesto' }[r.tipo] || r.tipo) + '</span><small class="agx-muted">' + esc(r.cuando) + (r.estado === 'enviado' ? ' · enviado' : '') + '</small></div>').join('') + '</details>';
        }
        h += '<details class="agx-det"><summary>' + ico('reloj') + 'Historial <span class="agx-n">' + (i.historial || []).length + '</span>' + ico('abajo') + '</summary>'
            + ((i.historial || []).map((x) => '<div class="agx-hist"><b>' + esc(x.accion) + '</b> · ' + esc(x.usuario) + ' <small>' + esc(x.cuando) + '</small>' + cambiosTxt(x) + (x.motivo ? '<div><small>Motivo: ' + esc(x.motivo) + '</small></div>' : '') + '</div>').join('') || '<p class="agx-muted">Sin movimientos.</p>') + '</details>';

        abrirPanel(i.titulo, h, { tipo: i.tipo_label, color: i.tipo, acciones });
        const c = $('#agxPanelC'); c._item = i; c._menu = menu;
    }

    function cambiosTxt(x) {
        if (!x.despues || !x.antes) return '';
        const nombres = { fecha: 'Fecha', fecha_inicio: 'Fecha', hora: 'Hora', hora_inicio: 'Hora', user_id: 'Responsable', responsable_id: 'Responsable', estado: 'Estado', prioridad: 'Prioridad', tipo: 'Tipo', ubicacion: 'Ubicación', titulo: 'Título', respuesta: 'Respuesta' };
        const usuario = (id) => (CFG.usuarios.find((u) => u.id === Number(id)) || {}).name || id;
        const val = (k, v) => (v === null || v === undefined || v === '' ? '—' : (k === 'user_id' || k === 'responsable_id') ? usuario(v) : (k === 'estado' ? (ESTADOS[v] || v) : v));
        const partes = Object.keys(x.despues).filter((k) => nombres[k]).map((k) => nombres[k] + ': ' + esc(val(k, x.antes[k])) + ' → ' + esc(val(k, x.despues[k])));
        return partes.length ? '<div><small>' + partes.join(' · ') + '</small></div>' : '';
    }

    function textoRecurrencia(r) {
        if (!r || !r.freq || r.freq === 'ninguna') return '';
        const n = Number(r.intervalo || 1);
        const base = { diaria: n > 1 ? 'Cada ' + n + ' días' : 'Se repite cada día', semanal: n > 1 ? 'Cada ' + n + ' semanas' : 'Se repite cada semana', mensual: n > 1 ? 'Cada ' + n + ' meses' : 'Se repite cada mes' }[r.freq];
        return base + (r.freq === 'semanal' && r.dias && r.dias.length ? ' (' + r.dias.map((d) => DIAS[d - 1]).join(', ') + ')' : '') + (r.hasta ? ' hasta el ' + largo(r.hasta) : r.veces ? ', ' + r.veces + ' veces' : '');
    }

    function menuDetalle(boton) {
        const menu = $('#agxPanelC')._menu || [];
        abrirFlot(boton, menu.map((m) => (m[0] === '-' ? '<div class="agx-mi-sep" role="separator"></div>' : '<button type="button" class="agx-mi' + (m[3] ? ' is-peligro' : '') + '" role="menuitem" data-a="' + m[0] + '">' + ico(m[1]) + esc(m[2]) + '</button>')).join(''), { tipo: 'menu' });
    }

    function cajaAccion(html) {
        const caja = $('#agxAccion'); if (!caja) return null;
        caja.innerHTML = '<div class="agx-caja">' + html + '</div>';
        const pri = caja.querySelector('input:not([type=hidden]):not([type=radio]), select, textarea');
        if (pri) setTimeout(() => pri.focus(), 30);
        caja.scrollIntoView({ block: 'nearest' });
        return caja;
    }
    const alcanceHtml = (i) => (i.serie ? '<div class="agx-campo agx-full"><span>Aplicar a</span><div class="agx-opc"><label><input type="radio" name="alcance" value="una" checked> Solo esta</label><label><input type="radio" name="alcance" value="futuras"> Esta y las siguientes</label></div></div>' : '');
    const opcionesUsuarios = (lista, sel) => lista.map((u) => '<option value="' + u.id + '"' + (Number(sel) === u.id ? ' selected' : '') + '>' + esc(u.name) + '</option>').join('');
    const pie = (txt, peligro) => '<div class="agx-pie"><button type="button" class="agx-btn agx-btn--sm" data-cerrar-caja>Cancelar</button><button class="agx-btn agx-btn--sm ' + (peligro ? 'agx-btn--peligro' : 'agx-btn--primario') + '">' + txt + '</button></div>';

    function accionDetalle(nombre, boton) {
        const c = $('#agxPanelC'); const i = c && c._item; if (!i) return;
        cerrarFlot();
        const seg = i.fuente === 'seguimiento';
        if (nombre === 'responder') return responderRapido(i.key, boton.dataset.v);
        if (nombre === 'en_proceso') return ejecutar(() => enviar(urlItem(i.key, 'estado'), 'POST', { estado: 'en_proceso' }));
        if (nombre === 'duplicar') return ejecutar(() => enviar(urlItem(i.key, 'duplicar'), 'POST'), (r) => r.key && abrirItem(r.key));
        if (nombre === 'editar') return formulario(i);
        if (nombre === 'completar') {
            return cajaAccion('<h4>' + (seg ? 'Completar seguimiento' : i.clase === 'evento' ? 'Marcar como realizado' : 'Completar') + '</h4><form class="agx-form" data-f="completar">'
                + '<label class="agx-campo agx-full"><span>Resultado' + (seg ? ' del contacto' : '') + '</span><textarea name="resultado" maxlength="' + (seg ? 500 : 1000) + '" placeholder="¿Cómo salió?"></textarea></label>'
                + (seg ? '<details class="agx-mas-opc"' + (i.cliente && i.cliente.etapa === 'prospecto' ? ' open' : '') + '><summary>' + ico('derecha') + 'Programar el siguiente contacto</summary><div class="agx-form">'
                    + '<label class="agx-campo"><span>Fecha</span><input type="date" name="siguiente_fecha" min="' + CFG.hoy + '"></label>'
                    + '<label class="agx-campo"><span>Hora (opcional)</span><input type="time" name="siguiente_hora"></label>'
                    + '<label class="agx-campo"><span>Tipo de contacto</span><select name="siguiente_tipo">' + Object.entries(CFG.tiposContacto).map(([v, t]) => '<option value="' + v + '"' + (v === i.subtipo ? ' selected' : '') + '>' + esc(t) + '</option>').join('') + '</select></label>'
                    + '<label class="agx-campo"><span>Motivo</span><input type="text" name="siguiente_nota" maxlength="500" value="' + esc(i.descripcion || '') + '"></label></div></details>' : '')
                + pie('Guardar') + '</form>');
        }
        if (nombre === 'mover') {
            return cajaAccion('<h4>Reprogramar</h4><form class="agx-form" data-f="mover">'
                + '<label class="agx-campo"><span>Nueva fecha</span><input type="date" name="fecha" required value="' + esc(i.fecha < CFG.hoy && seg ? CFG.hoy : i.fecha) + '"' + (seg ? ' min="' + CFG.hoy + '"' : '') + '></label>'
                + '<label class="agx-campo"><span>Hora <small>(vacía = sin hora)</small></span><input type="time" name="hora" value="' + esc(i.hora || '') + '"></label>'
                + '<label class="agx-campo agx-full"><span>Motivo (opcional)</span><input type="text" name="motivo" maxlength="255" placeholder="Queda en el historial"></label>'
                + alcanceHtml(i) + pie('Reprogramar') + '</form>');
        }
        if (nombre === 'reasignar') {
            const lista = CFG.asignables.filter((u) => u.id !== (i.responsable && i.responsable.id));
            return cajaAccion('<h4>Reasignar</h4><form class="agx-form" data-f="reasignar"><label class="agx-campo agx-full"><span>Nuevo responsable</span><select name="user_id" required>' + opcionesUsuarios(lista) + '</select></label>'
                + '<label class="agx-campo agx-full"><span>Motivo (opcional)</span><input type="text" name="motivo" maxlength="255"></label>' + pie('Reasignar') + '</form>');
        }
        if (nombre === 'posponer') {
            return cajaAccion('<h4>Posponer recordatorio</h4><form class="agx-form" data-f="posponer"><label class="agx-campo agx-full"><span>Recordármelo de nuevo en</span><select name="minutos"><option value="10">10 minutos</option><option value="30">30 minutos</option><option value="60" selected>1 hora</option><option value="180">3 horas</option><option value="1440">1 día</option></select></label>'
                + '<p class="agx-muted agx-full" style="margin:0;font-size:12.5px">La fecha de la actividad no cambia.</p>' + pie('Posponer') + '</form>');
        }
        if (nombre === 'cancelar') {
            return cajaAccion('<h4>Cancelar ' + (seg ? 'seguimiento' : 'actividad') + '</h4><form class="agx-form" data-f="cancelar"><label class="agx-campo agx-full"><span>Motivo</span><input type="text" name="motivo" maxlength="255" placeholder="Queda en el historial"></label>'
                + alcanceHtml(i) + '<div class="agx-pie"><button type="button" class="agx-btn agx-btn--sm" data-cerrar-caja>Volver</button><button class="agx-btn agx-btn--sm agx-btn--peligro">Cancelar ' + (seg ? 'seguimiento' : 'actividad') + '</button></div></form>');
        }
        return null;
    }

    function datosForm(form) {
        const d = {};
        new FormData(form).forEach((v, k) => { if (k.endsWith('[]')) { const kk = k.slice(0, -2); (d[kk] = d[kk] || []).push(v); } else d[k] = v; });
        return d;
    }

    async function conConflictos(fn, cuerpo) {
        try { return await fn(cuerpo); }
        catch (e) {
            if (e.status === 409 && e.datos.conflictos) {
                const lista = e.datos.conflictos.map((x) => '· ' + x.usuario + ': ' + x.titulo + ' (' + x.cuando + ')').join('\n');
                const ok = window.confirmModal ? await window.confirmModal({ title: 'Choque de horario', message: 'Ese horario ya está ocupado:\n' + lista + '\n\n¿Guardar de todos modos?', confirmText: 'Guardar de todos modos', danger: false }) : false;
                if (ok) return fn(Object.assign({}, cuerpo, { confirmar_conflictos: true }));
                const err = new Error('Sin guardar por el choque de horario.'); err.silencioso = true; throw err;
            }
            throw e;
        }
    }

    async function ejecutar(fn, despues) {
        guardando(true);
        try {
            const r = await fn();
            toast(r.mensaje || 'Listo.');
            await cargar({ silencioso: true });
            if (despues) despues(r); else if (S.abierto) abrirItem(S.abierto);
            return r;
        } catch (e) {
            if (!e.silencioso) toast(primerError(e), { error: true });
            return null;
        } finally { guardando(false); }
    }

    async function enviarCaja(form) {
        const i = $('#agxPanelC')._item; if (!i) return;
        const key = i.key; const d = datosForm(form); const tipo = form.dataset.f;
        const boton = form.querySelector('button:not([type=button])'); if (boton) boton.disabled = true;
        guardando(true);
        try {
            let r; let deshacer = null;
            if (tipo === 'completar') {
                r = await enviar(urlItem(key, 'estado'), 'POST', Object.assign({ estado: i.clase === 'evento' && i.fuente !== 'seguimiento' ? 'realizado' : 'completado' }, d));
                if (!r.siguiente && (i.fuente === 'actividad' || i.fecha >= CFG.hoy)) {
                    deshacer = async () => {
                        if (i.fuente === 'seguimiento') await enviar(urlItem(key, 'mover'), 'POST', { fecha: i.fecha, hora: i.hora || null, motivo: 'Se deshizo la marca de completado' });
                        else await enviar(urlItem(key, 'estado'), 'POST', { estado: i.clase === 'evento' ? 'programado' : 'pendiente' });
                        toast('Se deshizo.'); await cargar({ silencioso: true }); abrirItem(key);
                    };
                }
            } else if (tipo === 'mover') {
                r = await conConflictos((c) => enviar(urlItem(key, 'mover'), 'POST', c), { fecha: d.fecha, hora: d.hora || null, motivo: d.motivo, alcance: d.alcance || 'una' });
                if ((d.alcance || 'una') === 'una' && (i.fuente === 'actividad' || i.fecha >= CFG.hoy)) {
                    deshacer = async () => { await enviar(urlItem(key, 'mover'), 'POST', { fecha: i.fecha, hora: i.hora || null, motivo: 'Se deshizo la reprogramación', confirmar_conflictos: true }); toast('Se regresó a su fecha anterior.'); await cargar({ silencioso: true }); abrirItem(key); };
                }
            } else if (tipo === 'reasignar') r = await enviar(urlItem(key, 'reasignar'), 'POST', d);
            else if (tipo === 'posponer') r = await enviar(urlItem(key, 'posponer'), 'POST', { minutos: Number(d.minutos) });
            else if (tipo === 'cancelar') r = await enviar(urlItem(key, 'cancelar'), 'POST', { motivo: d.motivo, alcance: d.alcance || 'una' });
            else if (tipo === 'comentario') r = await enviar(urlItem(key, 'comentarios'), 'POST', d);
            else if (tipo === 'paso') r = await enviar(urlItem(key, 'checklist'), 'POST', d);
            if (r && r.mensaje) toast(r.mensaje, { deshacer: deshacer ? async () => { guardando(true); try { await deshacer(); } catch (e) { toast(primerError(e), { error: true }); } guardando(false); } : null });
            await cargar({ silencioso: true });
            abrirItem(r && r.siguiente ? r.siguiente : key);
        } catch (e) {
            if (!e.silencioso) toast(primerError(e), { error: true });
            if (boton) boton.disabled = false;
        } finally { guardando(false); }
    }

    // ---------- Creación rápida y formulario completo ----------
    const campo = (etiqueta, control, clase) => '<label class="agx-campo' + (clase ? ' ' + clase : '') + '"><span>' + etiqueta + '</span>' + control + '<small class="agx-err" hidden></small></label>';

    function crearRapido(ancla, valores) {
        if (movil() || !ancla || !ancla.getBoundingClientRect || !ancla.offsetParent) { formulario(null, valores); return; }
        const v = Object.assign({ tipo: 'reunion', fecha_inicio: S.fecha < CFG.hoy ? CFG.hoy : S.fecha }, valores || {});
        if (v.hora_inicio && !v.hora_fin) { const m = minutos(v.hora_inicio) + 60; v.hora_fin = m < 1440 ? hm(m) : ''; }
        const tipos = CFG.tipos.map((t) => '<option value="' + t.valor + '"' + (t.valor === v.tipo ? ' selected' : '') + '>' + esc(t.label) + '</option>').join('');
        const f = abrirFlot(ancla, '<form class="agx-form" id="agxRapido" novalidate>'
            + campo('<span class="agx-sr">Título</span>', '<input type="text" name="titulo" class="agx-titulo-in" maxlength="200" placeholder="Agregar título" required>', 'agx-full')
            + campo('Tipo', '<select name="tipo">' + tipos + '</select>', 'agx-full')
            + campo('Fecha', '<input type="date" name="fecha_inicio" required value="' + esc(v.fecha_inicio) + '">', 'agx-full')
            + campo('Inicio', '<input type="time" name="hora_inicio" value="' + esc(v.hora_inicio || '') + '">')
            + campo('Fin', '<input type="time" name="hora_fin" value="' + esc(v.hora_fin || '') + '">')
            + '<div class="agx-full" id="agxRapidoAviso"></div>'
            + '<div class="agx-pie"><button type="button" class="agx-btn agx-btn--sm agx-izq" data-mas-opciones>Más opciones</button><button class="agx-btn agx-btn--sm agx-btn--primario">Crear</button></div></form>',
        { clase: 'is-ancho', lado: ancla.closest && ancla.closest('.agx-col') ? 'derecha' : 'abajo' });
        const form = f.querySelector('#agxRapido');
        const capturado = () => { const d = datosForm(form); return Object.assign(d, { todo_el_dia: !d.hora_inicio }); };
        form.querySelector('[name=tipo]').addEventListener('change', (e) => { if (e.target.value === 'seguimiento') { const d = capturado(); cerrarFlot(); formulario(null, d); } });
        const ini = form.querySelector('[name=hora_inicio]'); const fin = form.querySelector('[name=hora_fin]');
        ini.addEventListener('change', () => { if (ini.value && (!fin.value || fin.value <= ini.value)) { const m = minutos(ini.value) + 60; if (m < 1440) fin.value = hm(m); } });
        f.querySelector('[data-mas-opciones]').addEventListener('click', () => { const d = capturado(); cerrarFlot(); formulario(null, d); });
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const d = datosForm(form);
            if (!d.titulo.trim()) { form.querySelector('[name=titulo]').focus(); $('#agxRapidoAviso', f).innerHTML = '<div class="agx-aviso">Escribe un título.</div>'; return; }
            const boton = form.querySelector('.agx-btn--primario'); boton.disabled = true; guardando(true);
            try {
                const cuerpo = { titulo: d.titulo, tipo: d.tipo, fecha_inicio: d.fecha_inicio, hora_inicio: d.hora_inicio || null, hora_fin: d.hora_inicio ? (d.hora_fin || null) : null, todo_el_dia: !d.hora_inicio, recordatorios: CFG.preferencias.recordatorios || [] };
                const r = await conConflictos((c) => enviar(CFG.rutas.crear, 'POST', c), cuerpo);
                cerrarFlot();
                toast(r.mensaje || 'Actividad creada.');
                await cargar({ silencioso: true });
                const nuevo = r.key && $('.agx-ev[data-key="' + CSS.escape(r.key) + '"]'); if (nuevo) nuevo.classList.add('is-hecho-ahora');
            } catch (err) {
                boton.disabled = false;
                if (!err.silencioso && flot) $('#agxRapidoAviso', f).innerHTML = '<div class="agx-aviso">' + esc(primerError(err)) + '</div>';
            } finally { guardando(false); }
        });
    }

    function formulario(i, valores) {
        const nuevo = !i;
        const v = Object.assign({ tipo: 'reunion', fecha_inicio: S.fecha < CFG.hoy ? CFG.hoy : S.fecha, prioridad: 'normal', visibilidad: 'participantes', responsable_id: CFG.yo.id, recordatorios: CFG.preferencias.recordatorios || [], participantes: [], roles: [], equipos: [] }, valores || {});
        if (i) {
            Object.assign(v, {
                tipo: i.tipo, subtipo: i.subtipo, titulo: i.fuente === 'seguimiento' ? '' : i.titulo, descripcion: i.descripcion || '',
                fecha_inicio: i.fecha, fecha_fin: i.fecha_fin, hora_inicio: i.hora || '', hora_fin: i.hora_fin || '', todo_el_dia: i.fuente === 'actividad' && i.todo_el_dia,
                responsable_id: i.responsable && i.responsable.id, prioridad: i.prioridad, visibilidad: i.visibilidad, ubicacion: i.ubicacion || '', enlace: i.enlace || '',
                ocupa_horario: i.ocupa_horario, recordatorios: i.recordatorios_elegidos ?? (CFG.preferencias.recordatorios || []),
                participantes: (i.participantes || []).map((p) => p.id), roles: (i.roles || []).map((r) => r.id), equipos: (i.equipos || []).map((r) => r.id),
                customer_id: i.cliente && i.cliente.id, cliente_nombre: i.cliente && i.cliente.nombre, estado: i.estado, recurrencia: i.recurrencia,
            });
        }
        const seg = v.tipo === 'seguimiento';
        const tipos = CFG.tipos.map((t) => '<option value="' + t.valor + '"' + (t.valor === v.tipo ? ' selected' : '') + '>' + esc(t.label) + '</option>').join('');
        const recs = Object.entries(CFG.recordatorios).map(([m, t]) => '<label><input type="checkbox" name="recordatorios[]" value="' + m + '"' + ((v.recordatorios || []).map(Number).includes(Number(m)) ? ' checked' : '') + '> ' + esc(t) + '</label>').join('');
        const vis = Object.entries(CFG.visibilidades).map(([k, t]) => '<option value="' + k + '"' + (k === v.visibilidad ? ' selected' : '') + '>' + esc(t) + '</option>').join('');
        const gente = CFG.usuarios.filter((u) => u.id !== Number(v.responsable_id)).map((u) => '<label data-nombre="' + esc(u.name.toLowerCase()) + '"><input type="checkbox" name="participantes[]" value="' + u.id + '"' + (v.participantes.includes(u.id) ? ' checked' : '') + '> ' + esc(u.name) + '</label>').join('');
        const asignables = CFG.asignables.some((u) => u.id === Number(v.responsable_id)) ? CFG.asignables : CFG.asignables.concat([{ id: Number(v.responsable_id), name: (i && i.responsable && i.responsable.nombre) || CFG.yo.nombre }]);
        const r = v.recurrencia || {};

        let h = '<form class="agx-form" id="agxForm" novalidate>';
        h += '<div class="agx-full" data-no="seguimiento"' + (seg ? ' hidden' : '') + '>' + campo('<span class="agx-sr">Título</span>', '<input type="text" name="titulo" class="agx-titulo-in" maxlength="200" placeholder="Agregar título" value="' + esc(v.titulo || '') + '">') + '</div>';
        if (nuevo || i.fuente === 'actividad') h += campo('Tipo', '<select name="tipo"' + (nuevo ? '' : ' disabled') + '>' + tipos + '</select>', 'agx-full');
        h += '<div class="agx-full" data-solo="seguimiento"' + (seg ? '' : ' hidden') + '><div class="agx-form">'
            + campo('Cliente o prospecto', nuevo ? '<input type="search" id="agxCliBuscar" placeholder="Nombre, teléfono o correo…" autocomplete="off" value="' + esc(v.cliente_nombre || '') + '"><input type="hidden" name="customer_id" value="' + esc(v.customer_id || '') + '"><div class="agx-sug" id="agxCliSug" hidden></div>' : '<input type="text" value="' + esc(v.cliente_nombre || '') + '" disabled>', 'agx-full')
            + campo('Tipo de contacto', '<select name="subtipo">' + Object.entries(CFG.tiposContacto).map(([k, t]) => '<option value="' + k + '"' + (k === v.subtipo ? ' selected' : '') + '>' + esc(t) + '</option>').join('') + '</select>')
            + (i && i.fuente === 'seguimiento' ? campo('Estado', '<select name="estado"><option value="pendiente"' + (v.estado === 'pendiente' ? ' selected' : '') + '>Pendiente</option><option value="en_proceso"' + (v.estado === 'en_proceso' ? ' selected' : '') + '>En proceso</option></select>') : '<span></span>')
            + campo('Motivo', '<textarea name="descripcion" maxlength="500" placeholder="Ej. confirmar propuesta de torre de endoscopia">' + esc(seg ? (v.descripcion || '') : '') + '</textarea>', 'agx-full')
            + '</div></div>';
        h += campo('Fecha', '<input type="date" name="fecha_inicio" required value="' + esc(v.fecha_inicio) + '"' + (seg ? ' min="' + CFG.hoy + '"' : '') + '>', 'agx-full');
        h += campo('Inicio' + (seg ? ' <small>(opcional)</small>' : ''), '<input type="time" name="hora_inicio" value="' + esc(v.hora_inicio || '') + '">');
        h += '<div data-no="seguimiento"' + (seg ? ' hidden' : '') + '>' + campo('Fin', '<input type="time" name="hora_fin" value="' + esc(v.hora_fin || '') + '">') + '</div>';
        h += '<div class="agx-full agx-opc" data-no="seguimiento"' + (seg ? ' hidden' : '') + '><label><input type="checkbox" name="todo_el_dia" value="1"' + (v.todo_el_dia ? ' checked' : '') + '> Todo el día</label></div>';

        h += '<details class="agx-mas-opc"' + (!nuevo && !seg ? ' open' : '') + '><summary>' + ico('derecha') + 'Más opciones</summary><div class="agx-form">'
            + campo('Responsable', '<select name="responsable_id"' + (asignables.length > 1 ? '' : ' disabled') + '>' + opcionesUsuarios(asignables, v.responsable_id) + '</select>')
            + campo('Prioridad', '<select name="prioridad">' + Object.entries(CFG.prioridades).map(([k, t]) => '<option value="' + k + '"' + (k === v.prioridad ? ' selected' : '') + '>' + esc(t) + '</option>').join('') + '</select>')
            + '<div class="agx-full" data-no="seguimiento"' + (seg ? ' hidden' : '') + '><div class="agx-form">'
            + campo('Descripción', '<textarea name="descripcion_act" maxlength="5000">' + esc(seg ? '' : (v.descripcion || '')) + '</textarea>', 'agx-full')
            + campo('Fecha de fin', '<input type="date" name="fecha_fin" value="' + esc(v.fecha_fin || '') + '">')
            + campo('Visibilidad', '<select name="visibilidad">' + vis + '</select>')
            + '<div class="agx-full" data-grupo' + (v.visibilidad === 'grupo' ? '' : ' hidden') + '><div class="agx-form">'
            + campo('Departamentos', '<div class="agx-pick">' + CFG.departamentos.map((d) => '<label><input type="checkbox" name="roles[]" value="' + d.id + '"' + (v.roles.includes(d.id) ? ' checked' : '') + '> ' + esc(d.nombre) + '</label>').join('') + '</div>')
            + campo('Equipos', '<div class="agx-pick">' + (CFG.equipos.length ? CFG.equipos.map((d) => '<label><input type="checkbox" name="equipos[]" value="' + d.id + '"' + (v.equipos.includes(d.id) ? ' checked' : '') + '> ' + esc(d.nombre) + '</label>').join('') : '<span class="agx-muted" style="font-size:12.5px">Aún no hay equipos.</span>') + '</div>')
            + '</div></div>'
            + campo('Participantes <small>(reciben invitación)</small>', '<input type="search" placeholder="Filtrar personas…" data-filtrar-pick><div class="agx-pick">' + gente + '</div>', 'agx-full')
            + campo('Cliente relacionado', '<input type="search" id="agxCliBuscar2" placeholder="Buscar cliente…" autocomplete="off" value="' + esc(!seg ? (v.cliente_nombre || '') : '') + '"><input type="hidden" name="customer_id_act" value="' + esc(!seg ? (v.customer_id || '') : '') + '"><div class="agx-sug" id="agxCliSug2" hidden></div>', 'agx-full')
            + campo('Ubicación', '<input type="text" name="ubicacion" maxlength="255" value="' + esc(v.ubicacion || '') + '" placeholder="Sala, dirección…">')
            + campo('Videollamada', '<input type="url" name="enlace" maxlength="500" value="' + esc(v.enlace || '') + '" placeholder="https://…">')
            + '<div class="agx-full agx-opc"><label data-tip="Cuenta para detectar choques de horario de los participantes"><input type="checkbox" name="ocupa_horario" value="1"' + (v.ocupa_horario !== false && (v.ocupa_horario || (CFG.tipos.find((t) => t.valor === v.tipo) || {}).ocupa) ? ' checked' : '') + '> Ocupa el horario</label></div>'
            + (nuevo || (i && i.serie)
                ? campo('Repetir', '<select name="rec_freq"><option value="ninguna">No se repite</option><option value="diaria"' + (r.freq === 'diaria' ? ' selected' : '') + '>Cada día</option><option value="semanal"' + (r.freq === 'semanal' ? ' selected' : '') + '>Cada semana</option><option value="mensual"' + (r.freq === 'mensual' ? ' selected' : '') + '>Cada mes</option></select>')
                    + campo('Cada cuántos', '<input type="number" name="rec_intervalo" min="1" max="30" value="' + esc(r.intervalo || 1) + '">')
                    + '<div class="agx-campo agx-full" data-rec-dias' + (r.freq === 'semanal' ? '' : ' hidden') + '><span>Días</span><div class="agx-opc">' + DIAS.map((d, k) => '<label><input type="checkbox" name="rec_dias[]" value="' + (k + 1) + '"' + ((r.dias || []).map(Number).includes(k + 1) ? ' checked' : '') + '> ' + d + '</label>').join('') + '</div></div>'
                    + campo('Hasta', '<input type="date" name="rec_hasta" value="' + esc(r.hasta || '') + '">')
                    + campo('O número de veces', '<input type="number" name="rec_veces" min="1" max="120" value="' + esc(r.veces || '') + '">')
                : '')
            + (nuevo ? campo('Checklist <small>(un paso por renglón)</small>', '<textarea name="checklist_txt" rows="3" placeholder="Preparar presentación&#10;Confirmar sala"></textarea>', 'agx-full') : '')
            + '</div></div>'
            + '<div class="agx-campo agx-full"><span>Recordatorios</span><div class="agx-opc">' + recs + '</div>' + (seg ? '<small class="agx-muted">Sin hora, se avisa ese día a las ' + esc(CFG.horaAviso || '08:00') + '.</small>' : '') + '</div>'
            + '</div></details>';
        if (i && i.serie) h += '<div class="agx-full agx-aviso">Es parte de una serie que se repite.</div>' + alcanceHtml(i);
        if (i) h += campo('Motivo del cambio <small>(opcional, queda en el historial)</small>', '<input type="text" name="motivo" maxlength="255">', 'agx-full');
        h += '<div class="agx-full" id="agxFormAviso"></div>';
        h += '<div class="agx-pie">' + (i ? '<button type="button" class="agx-btn agx-btn--sm" data-volver="' + esc(i.key) + '">Volver</button>' : '<button type="button" class="agx-btn agx-btn--sm" data-cerrar>Cancelar</button>') + '<button class="agx-btn agx-btn--sm agx-btn--primario">' + (i ? 'Guardar cambios' : 'Crear') + '</button></div></form>';

        abrirPanel(i ? 'Editar actividad' : 'Nueva actividad', h, { tipo: i ? i.titulo : '' });
        const f = $('#agxForm');
        f._item = i;
        enlazarForm(f);
        // Lo que venía capturado de la creación rápida cuenta como cambio sin guardar.
        if (nuevo && valores && valores.titulo) f.dataset.sucio = '1';
        if (nuevo) setTimeout(() => (f.querySelector(seg ? '#agxCliBuscar' : '[name=titulo]') || f).focus(), 60);
    }

    function enlazarForm(f) {
        const tipo = f.querySelector('[name=tipo]');
        const alternar = () => {
            const seg = (tipo ? tipo.value : (f._item && f._item.tipo)) === 'seguimiento';
            $$('[data-solo="seguimiento"]', f).forEach((el) => { el.hidden = !seg; });
            $$('[data-no="seguimiento"]', f).forEach((el) => { el.hidden = seg; });
            const t = CFG.tipos.find((x) => x.valor === (tipo && tipo.value));
            const ocupa = f.querySelector('[name=ocupa_horario]');
            if (ocupa && t && !f._item) ocupa.checked = !!t.ocupa;
            const vis = f.querySelector('[name=visibilidad]');
            if (vis && !f._item && t && t.valor === 'evento_empresa' && CFG.visibilidades.empresa) vis.value = 'empresa';
            if (vis) $('[data-grupo]', f).hidden = vis.value !== 'grupo';
            const fecha = f.querySelector('[name=fecha_inicio]'); if (fecha) { if (seg) fecha.min = CFG.hoy; else fecha.removeAttribute('min'); }
            horas();
        };
        const todo = f.querySelector('[name=todo_el_dia]');
        const horas = () => { ['hora_inicio', 'hora_fin'].forEach((n) => { const el = f.querySelector('[name=' + n + ']'); if (el && todo) el.disabled = todo.checked && !(tipo && tipo.value === 'seguimiento'); }); };
        if (todo) todo.addEventListener('change', horas);
        if (tipo) tipo.addEventListener('change', alternar);
        const vis = f.querySelector('[name=visibilidad]');
        if (vis) vis.addEventListener('change', () => { $('[data-grupo]', f).hidden = vis.value !== 'grupo'; });
        const freq = f.querySelector('[name=rec_freq]');
        if (freq) freq.addEventListener('change', () => { $('[data-rec-dias]', f).hidden = freq.value !== 'semanal'; });
        const ini = f.querySelector('[name=hora_inicio]'); const fin = f.querySelector('[name=hora_fin]');
        if (ini && fin) ini.addEventListener('change', () => { if (ini.value && (!fin.value || fin.value <= ini.value)) { const m = minutos(ini.value) + 60; if (m < 1440) fin.value = hm(m); } });
        const filtro = f.querySelector('[data-filtrar-pick]');
        if (filtro) filtro.addEventListener('input', () => { const q = filtro.value.toLowerCase(); $$('.agx-pick label[data-nombre]', f).forEach((l) => { l.hidden = q && !l.dataset.nombre.includes(q); }); });
        buscadorCliente(f.querySelector('#agxCliBuscar'), f.querySelector('[name=customer_id]'), f.querySelector('#agxCliSug'));
        buscadorCliente(f.querySelector('#agxCliBuscar2'), f.querySelector('[name=customer_id_act]'), f.querySelector('#agxCliSug2'));
        f.addEventListener('input', () => { f.dataset.sucio = '1'; });
        f.addEventListener('change', () => { f.dataset.sucio = '1'; });
        f.addEventListener('submit', (e) => { e.preventDefault(); guardarForm(f); });
        alternar();
    }

    function buscadorCliente(input, oculto, sug, alElegir) {
        if (!input || !sug) return;
        let t = null;
        input.addEventListener('input', () => {
            if (oculto) oculto.value = '';
            clearTimeout(t);
            const q = input.value.trim();
            if (q.length < 2) { sug.hidden = true; return; }
            t = setTimeout(async () => {
                try {
                    const { clientes } = await pedir(CFG.rutas.clientes + '?q=' + encodeURIComponent(q));
                    sug.innerHTML = clientes.length ? clientes.map((c) => '<button type="button" data-id="' + c.id + '" data-nombre="' + esc(c.nombre) + '">' + esc(c.nombre) + ' <small class="agx-muted">' + esc((c.etapa === 'prospecto' ? 'Prospecto' : 'Cliente') + (c.telefono ? ' · ' + c.telefono : '')) + '</small></button>').join('') : '<div class="agx-muted" style="padding:8px 10px">Sin coincidencias entre tus clientes.</div>';
                    sug.hidden = false;
                } catch (e) { sug.hidden = true; }
            }, 250);
        });
        sug.addEventListener('click', (e) => {
            const b = e.target.closest('button[data-id]'); if (!b) return;
            e.stopPropagation();
            if (oculto) oculto.value = b.dataset.id;
            input.value = b.dataset.nombre; sug.hidden = true;
            if (alElegir) alElegir({ id: Number(b.dataset.id), nombre: b.dataset.nombre });
        });
    }

    function mostrarErrores(f, errores) {
        $$('.agx-campo', f).forEach((c) => { c.classList.remove('con-error'); const s = c.querySelector('.agx-err'); if (s) { s.hidden = true; s.textContent = ''; } });
        const mapa = { customer_id: ['customer_id', 'agxCliBuscar'], descripcion: ['descripcion', 'descripcion_act'], 'recurrencia.hasta': ['rec_hasta'], 'recurrencia.veces': ['rec_veces'], fecha: ['fecha_inicio'], hora: ['hora_inicio'], nota: ['descripcion'], tipo: ['subtipo', 'tipo'], user_id: ['responsable_id'] };
        let primero = null; const sueltos = [];
        Object.entries(errores || {}).forEach(([k, msgs]) => {
            const nombres = mapa[k] || [k.replace(/\.\d+$/, '')];
            let el = null;
            for (const n of nombres) { el = f.querySelector('[name="' + n + '"]:not([type=hidden])') || f.querySelector('#' + CSS.escape(n)) || f.querySelector('[name="' + n + '[]"]'); if (el) break; }
            const det = el && el.closest('details'); if (det) det.open = true;
            const c = el && el.closest('.agx-campo');
            if (c && c.querySelector('.agx-err') && !el.closest('[hidden]')) { c.classList.add('con-error'); const s = c.querySelector('.agx-err'); s.textContent = msgs[0]; s.hidden = false; primero = primero || el; }
            else sueltos.push(msgs[0]);
        });
        $('#agxFormAviso', f).innerHTML = sueltos.length ? '<div class="agx-aviso">' + sueltos.map(esc).join('<br>') + '</div>' : '';
        if (primero) primero.focus();
    }

    async function guardarForm(f) {
        const i = f._item; const d = datosForm(f); const tipo = d.tipo || (i && i.tipo);
        let cuerpo;
        if (tipo === 'seguimiento') {
            cuerpo = { tipo: 'seguimiento', customer_id: d.customer_id || (i && i.cliente && i.cliente.id), subtipo: d.subtipo, fecha_inicio: d.fecha_inicio, hora_inicio: d.hora_inicio || null, prioridad: d.prioridad, descripcion: d.descripcion, responsable_id: d.responsable_id ? Number(d.responsable_id) : null, recordatorios: (d.recordatorios || []).map(Number), estado: d.estado, motivo: d.motivo };
        } else {
            if (!(d.titulo || '').trim()) { mostrarErrores(f, { titulo: ['Escribe un título.'] }); return; }
            const rec = d.rec_freq && d.rec_freq !== 'ninguna' ? { freq: d.rec_freq, intervalo: Number(d.rec_intervalo || 1), dias: (d.rec_dias || []).map(Number), hasta: d.rec_hasta || null, veces: d.rec_veces ? Number(d.rec_veces) : null } : null;
            cuerpo = {
                titulo: d.titulo, descripcion: d.descripcion_act, tipo, fecha_inicio: d.fecha_inicio, fecha_fin: d.fecha_fin || null,
                hora_inicio: d.todo_el_dia ? null : (d.hora_inicio || null), hora_fin: d.todo_el_dia ? null : (d.hora_fin || null), todo_el_dia: !!d.todo_el_dia,
                responsable_id: d.responsable_id ? Number(d.responsable_id) : (i ? i.responsable.id : CFG.yo.id), prioridad: d.prioridad, visibilidad: d.visibilidad,
                ocupa_horario: !!d.ocupa_horario, customer_id: d.customer_id_act ? Number(d.customer_id_act) : null, ubicacion: d.ubicacion, enlace: d.enlace || null,
                participantes: (d.participantes || []).map(Number), roles: (d.roles || []).map(Number), equipos: (d.equipos || []).map(Number),
                recordatorios: (d.recordatorios || []).map(Number), checklist: (d.checklist_txt || '').split('\n').map((x) => x.trim()).filter(Boolean),
                alcance: d.alcance || 'una', motivo: d.motivo,
            };
            if (rec) cuerpo.recurrencia = rec; else if (!i) cuerpo.recurrencia = null;
        }
        const boton = f.querySelector('.agx-pie .agx-btn--primario'); boton.disabled = true; guardando(true);
        try {
            const r = await conConflictos((c) => (i ? enviar(urlItem(i.key), 'PUT', c) : enviar(CFG.rutas.crear, 'POST', c)), cuerpo);
            f.dataset.sucio = '0';
            toast(r.mensaje || 'Guardado.');
            if (!i && cuerpo.fecha_inicio && !S.seccion) S.fecha = cuerpo.fecha_inicio;
            await cargar({ silencioso: true });
            abrirItem(r.key || (i && i.key));
        } catch (e) {
            boton.disabled = false;
            if (e.silencioso) return;
            if (e.status === 422 && e.datos.errors) { mostrarErrores(f, e.datos.errors); toast('Revisa los campos marcados.', { error: true }); }
            else toast(primerError(e), { error: true });
        } finally { guardando(false); }
    }

    // ---------- Vistas del menú "Más" ----------
    async function verCarga() {
        abrirPanel('Carga de trabajo', esqueletoDetalle());
        try {
            const { carga } = await pedir(CFG.rutas.carga);
            const max = Math.max(1, ...carga.map((f) => f.vencidos + f.hoy + f.semana));
            abrirPanel('Carga de trabajo', '<p class="agx-muted" style="margin:0 0 10px">Pendientes abiertos de cada persona que puedes consultar. Elige un nombre para ver su agenda.</p><table class="agx-tabla"><thead><tr><th>Responsable</th><th>Vencidos</th><th>Hoy</th><th>7 días</th></tr></thead><tbody>'
                + carga.map((f) => '<tr><td><button type="button" class="agx-vermas" style="padding:0;color:var(--text)" data-ver-usuario="' + f.id + '">' + esc(f.nombre) + '</button><div class="agx-barra" style="margin-top:4px"><i style="width:' + Math.round(((f.vencidos + f.hoy + f.semana) / max) * 100) + '%;' + (f.vencidos ? 'background:var(--danger)' : '') + '"></i></div></td><td' + (f.vencidos ? ' style="color:var(--danger);font-weight:700"' : '') + '>' + f.vencidos + '</td><td>' + f.hoy + '</td><td>' + f.semana + '</td></tr>').join('')
                + '</tbody></table>');
        } catch (e) { abrirPanel('Carga de trabajo', '<div class="agx-error">' + esc(e.message) + '</div>'); }
    }

    function verPreferencias() {
        const p = CFG.preferencias;
        abrirPanel('Mis avisos', '<form class="agx-form" id="agxPrefs">'
            + '<div class="agx-campo agx-full"><span>Recordatorios por omisión</span><div class="agx-opc">' + Object.entries(CFG.recordatorios).map(([m, t]) => '<label><input type="checkbox" name="recordatorios[]" value="' + m + '"' + ((p.recordatorios || []).map(Number).includes(Number(m)) ? ' checked' : '') + '> ' + esc(t) + '</label>').join('') + '</div></div>'
            + '<div class="agx-campo agx-full"><span>Resumen diario</span><div class="agx-opc"><label><input type="checkbox" name="resumen" value="1"' + (p.resumen ? ' checked' : '') + '> Cada mañana: lo del día, lo vencido y los próximos eventos</label></div></div>'
            + '<div class="agx-campo agx-full"><span>Correo</span><div class="agx-opc"><label><input type="checkbox" name="correo" value="1"' + (p.correo ? ' checked' : '') + (CFG.hayCorreo ? '' : ' disabled') + '> También por correo</label></div>'
            + (CFG.hayCorreo ? '' : '<small class="agx-muted">El correo de salida del sistema aún no está configurado; los avisos llegan a la campana.</small>') + '</div>'
            + '<p class="agx-muted agx-full" style="margin:0;font-size:12.5px">Los avisos son internos: la agenda nunca escribe a clientes.</p>'
            + '<div class="agx-pie"><button type="button" class="agx-btn agx-btn--sm" data-cerrar>Cancelar</button><button class="agx-btn agx-btn--sm agx-btn--primario">Guardar</button></div></form>');
        $('#agxPrefs').addEventListener('submit', async (e) => {
            e.preventDefault();
            const d = datosForm(e.target);
            const cuerpo = { correo: !!d.correo, resumen: !!d.resumen, recordatorios: (d.recordatorios || []).map(Number) };
            try { const r = await enviar(CFG.rutas.preferencias, 'POST', cuerpo); Object.assign(CFG.preferencias, cuerpo); toast(r.mensaje); cerrarPanel({ forzar: true }); }
            catch (err) { toast(primerError(err), { error: true }); }
        });
    }

    async function verEquipos(editar) {
        abrirPanel('Equipos de trabajo', esqueletoDetalle());
        let equipos = [];
        try { ({ equipos } = await pedir(CFG.rutas.equipos)); } catch (e) { abrirPanel('Equipos de trabajo', '<div class="agx-error">' + esc(e.message) + '</div>'); return; }
        const eq = editar ? equipos.find((x) => x.id === editar) : null;
        const lista = '<p class="agx-muted" style="margin:0 0 8px">El líder consulta la agenda de su equipo. Los eventos se pueden dirigir a un equipo.</p>'
            + (equipos.length ? equipos.map((x) => '<div class="agx-part"><span><b>' + esc(x.nombre) + '</b><br>' + (x.miembros.length ? avatares(x.miembros.map((m) => ({ nombre: m.nombre + (m.lider ? ' (líder)' : '') })), 5) : '<small class="agx-muted">Sin integrantes</small>') + '</span><button type="button" class="agx-ico" data-equipo-editar="' + x.id + '" aria-label="Editar ' + esc(x.nombre) + '" data-tip="Editar">' + ico('editar') + '</button><button type="button" class="agx-ico" data-equipo-borrar="' + x.id + '" aria-label="Eliminar ' + esc(x.nombre) + '" data-tip="Eliminar">' + ico('x') + '</button></div>').join('') : '<p class="agx-muted">Aún no hay equipos.</p>');
        const marcado = (id, lider) => eq && eq.miembros.some((m) => m.id === id && (!lider || m.lider));
        const form = '<details class="agx-det" open><summary>' + (eq ? 'Editar equipo' : 'Nuevo equipo') + ico('abajo') + '</summary><form class="agx-form" id="agxEquipoF">' + campo('Nombre', '<input type="text" name="nombre" maxlength="120" required value="' + esc(eq ? eq.nombre : '') + '">', 'agx-full')
            + campo('Descripción', '<input type="text" name="descripcion" maxlength="255" value="' + esc(eq ? eq.descripcion || '' : '') + '">', 'agx-full')
            + campo('Integrantes · líder', '<div class="agx-pick" style="max-height:220px">' + CFG.usuarios.map((u) => '<label style="justify-content:space-between"><span><input type="checkbox" name="miembros[]" value="' + u.id + '"' + (marcado(u.id) ? ' checked' : '') + '> ' + esc(u.name) + '</span><span class="agx-muted" style="font-size:12px"><input type="checkbox" name="lideres[]" value="' + u.id + '"' + (marcado(u.id, true) ? ' checked' : '') + ' aria-label="' + esc(u.name) + ' es líder"> líder</span></label>').join('') + '</div>', 'agx-full')
            + '<div class="agx-pie">' + (eq ? '<button type="button" class="agx-btn agx-btn--sm agx-izq" data-equipo-nuevo>Nuevo equipo</button>' : '') + '<button class="agx-btn agx-btn--sm agx-btn--primario">' + (eq ? 'Guardar' : 'Crear equipo') + '</button></div></form></details>';
        abrirPanel('Equipos de trabajo', lista + form);
        $('#agxEquipoF').addEventListener('submit', async (e) => {
            e.preventDefault();
            const d = datosForm(e.target);
            try { const r = await enviar(CFG.rutas.equipos + (eq ? '/' + eq.id : ''), eq ? 'PUT' : 'POST', { nombre: d.nombre, descripcion: d.descripcion, miembros: (d.miembros || []).map(Number), lideres: (d.lideres || []).map(Number) }); toast(r.mensaje); verEquipos(); }
            catch (err) { toast(primerError(err), { error: true }); }
        });
    }

    // ---------- Arrastrar y soltar (con deshacer) ----------
    let arrastrado = null;
    raiz.addEventListener('dragstart', (e) => {
        const ev = e.target.closest('.agx-ev[draggable="true"]'); if (!ev) return;
        cerrarFlot(); ocultarTip(); clearTimeout(pvTimer);
        arrastrado = buscarItem(ev.dataset.key);
        ev.classList.add('is-arrastrando');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', ev.dataset.key);
    });
    raiz.addEventListener('dragend', (e) => { const ev = e.target.closest('.agx-ev'); if (ev) ev.classList.remove('is-arrastrando'); $$('.is-destino').forEach((x) => x.classList.remove('is-destino')); });
    raiz.addEventListener('dragover', (e) => {
        const t = e.target.closest('[data-drop]'); if (!t || !arrastrado) return;
        e.preventDefault();
        $$('.is-destino').forEach((x) => { if (x !== t) x.classList.remove('is-destino'); });
        t.classList.add('is-destino');
    });
    raiz.addEventListener('drop', async (e) => {
        const t = e.target.closest('[data-drop]'); if (!t || !arrastrado) return;
        e.preventDefault(); t.classList.remove('is-destino');
        const i = arrastrado; arrastrado = null;
        const cuerpo = { fecha: t.dataset.fecha, alcance: 'una' };
        if (t.dataset.drop === 'hora') {
            const r = t.getBoundingClientRect();
            const extra = Math.min(45, Math.floor(((e.clientY - r.top) / (r.height || 48)) * 4) * 15);
            cuerpo.hora = hm(minutos(t.dataset.hora) + extra);
        } else if (t.dataset.drop === 'dia') cuerpo.conservar_hora = true;
        else cuerpo.hora = null;
        if (i.fecha === cuerpo.fecha && (cuerpo.conservar_hora || i.hora === cuerpo.hora)) return;
        if (i.fuente === 'seguimiento' && cuerpo.fecha < CFG.hoy) { toast('Un seguimiento no se puede mover a un día que ya pasó.', { error: true }); return; }
        await moverItem(i, cuerpo);
    });

    async function moverItem(i, cuerpo) {
        const previo = { fecha: i.fecha, fecha_fin: i.fecha_fin, hora: i.hora, hora_fin: i.hora_fin };
        const dur = Math.round((aFecha(i.fecha_fin) - aFecha(i.fecha)) / 864e5);
        i.fecha = cuerpo.fecha; i.fecha_fin = sumar(cuerpo.fecha, dur);
        if (!cuerpo.conservar_hora) {
            if (i.hora && i.hora_fin && cuerpo.hora) { const m = minutos(cuerpo.hora) + (minutos(i.hora_fin) - minutos(i.hora)); i.hora_fin = m < 1440 ? hm(m) : '23:59'; }
            i.hora = cuerpo.hora; if (!cuerpo.hora) i.hora_fin = null;
        }
        pintar();
        const ev = $('.agx-ev[data-key="' + CSS.escape(i.key) + '"]'); if (ev) ev.classList.add('is-guardando');
        guardando(true);
        try {
            const r = await conConflictos((c) => enviar(urlItem(i.key, 'mover'), 'POST', c), cuerpo);
            const puede = i.fuente === 'actividad' || previo.fecha >= CFG.hoy;
            toast(r.mensaje, {
                deshacer: puede ? async () => {
                    guardando(true);
                    try { await enviar(urlItem(i.key, 'mover'), 'POST', { fecha: previo.fecha, hora: previo.hora || null, motivo: 'Se deshizo la reprogramación', confirmar_conflictos: true }); toast('Se regresó a su lugar.'); }
                    catch (e) { toast(primerError(e), { error: true }); }
                    guardando(false); cargar({ silencioso: true });
                } : null,
            });
        } catch (err) {
            Object.assign(i, previo); pintar();
            if (!err.silencioso) toast(primerError(err), { error: true });
        } finally { guardando(false); }
        cargar({ silencioso: true });
    }

    // ---------- Navegación ----------
    function irA(cambios) {
        Object.assign(S, cambios);
        guardar(); cerrarFlot(); pintarNav(); cargar();
    }
    function moverFecha(n) {
        const v = vistaReal();
        if (v === 'mes') { const d = aFecha(S.fecha); S.fecha = aTexto(new Date(d.getFullYear(), d.getMonth() + n, 1)); }
        else if (v === 'semana' || v === 'movil') S.fecha = sumar(S.fecha, 7 * n);
        else if (v === 'dia') S.fecha = sumar(S.fecha, n);
        else S.fecha = sumar(S.fecha, 30 * n);
        cargar();
    }
    function plegar() {
        if (window.innerWidth <= 1100) raiz.classList.toggle('is-expandida');
        else raiz.classList.toggle('is-plegada');
        ocultarTip(); guardar();
        setTimeout(() => { if (vistaReal() === 'mes') pintar(); }, 200);
    }
    function abrirNavMovil(si) {
        raiz.classList.toggle('nav-abierta', si);
        const velo = $('#agxVelo');
        if (si) { velo.hidden = false; requestAnimationFrame(() => velo.classList.add('is-on')); }
        else if (!panelAbierto()) { velo.classList.remove('is-on'); setTimeout(() => { if (!panelAbierto() && !raiz.classList.contains('nav-abierta')) velo.hidden = true; }, 200); }
    }

    // ---------- Clics (pantalla y capa flotante) ----------
    function alClic(e) {
        const t = e.target;
        if (t.closest('[data-cerrar-flot]')) { cerrarFlot(); return true; }
        const rap = t.closest('[data-rapido]');
        if (rap) {
            e.preventDefault();
            const k = rap.dataset.k;
            if (rap.dataset.rapido === 'completar') completarRapido(k, rap);
            else if (rap.dataset.rapido === 'mover') { cerrarFlot(); abrirItem(k, { accion: 'mover' }); }
            else if (rap.dataset.rapido === 'aceptar') responderRapido(k, 'aceptada');
            return true;
        }
        const comp = t.closest('[data-completar]'); if (comp) { completarRapido(comp.dataset.completar, comp); return true; }
        const menuOp = t.closest('[data-menu]');
        if (menuOp) {
            cerrarFlot();
            const a = menuOp.dataset.menu;
            if (a === 'ics') location.href = CFG.rutas.ics + '?desde=' + sumar(CFG.hoy, -30) + '&hasta=' + sumar(CFG.hoy, 180);
            if (a === 'preferencias') verPreferencias();
            if (a === 'carga') verCarga();
            if (a === 'equipos') verEquipos();
            return true;
        }
        const a = t.closest('[data-a]'); if (a) { accionDetalle(a.dataset.a, a); return true; }
        const key = t.closest('[data-key]');
        if (key && !t.closest('a')) { e.preventDefault(); cerrarFlot(); abrirItem(key.dataset.key); return true; }
        const masDia = t.closest('[data-mas-dia]'); if (masDia) { popDia(masDia, masDia.dataset.masDia); return true; }
        const irDia = t.closest('[data-ir-dia]'); if (irDia) { cerrarFlot(); irA({ fecha: irDia.dataset.irDia, vista: movil() ? S.vista : 'dia', seccion: null }); return true; }
        const crearEn = t.closest('[data-crear-en]'); if (crearEn) { crearRapido(crearEn, { fecha_inicio: crearEn.dataset.crearEn }); return true; }
        const crear = t.closest('[data-crear]'); if (crear) { crearRapido(crear, { fecha_inicio: S.seccion ? CFG.hoy : (S.fecha < CFG.hoy ? CFG.hoy : S.fecha) }); return true; }
        const quitar = t.closest('[data-quitar]');
        if (quitar) { const k = quitar.dataset.quitar; if (k === 'cliente') S.cliente = null; else if (k === 'q') { S.q = ''; $('#agxBuscar').value = ''; $('#agxBuscarCaja').classList.remove('is-abierto'); } else delete S.filtros[k]; ocultarTip(); cargar(); return true; }
        if (t.closest('[data-limpiar]')) { S.filtros = {}; S.cliente = null; S.q = ''; $('#agxBuscar').value = ''; S.ocultos.clear(); guardar(); pintarCategorias(); cerrarFlot(); if (movil() && panelAbierto()) cerrarPanel({ forzar: true }); cargar(); return true; }
        if (t.closest('[data-reintentar]')) { cargar(); return true; }
        const prog = t.closest('[data-programar-cli]'); if (prog) { e.preventDefault(); formulario(null, { tipo: 'seguimiento', customer_id: prog.dataset.programarCli, cliente_nombre: prog.dataset.nombre }); return true; }
        const verU = t.closest('[data-ver-usuario]'); if (verU) { S.filtros.usuario = verU.dataset.verUsuario; cerrarPanel({ forzar: true }); irA({ seccion: null }); return true; }
        const eqE = t.closest('[data-equipo-editar]'); if (eqE) { verEquipos(Number(eqE.dataset.equipoEditar)); return true; }
        if (t.closest('[data-equipo-nuevo]')) { verEquipos(); return true; }
        const eqB = t.closest('[data-equipo-borrar]');
        if (eqB) {
            (window.confirmModal ? window.confirmModal({ title: 'Eliminar equipo', message: '¿Eliminar este equipo? Las personas no se borran.', danger: true }) : Promise.resolve(false)).then(async (ok) => {
                if (!ok) return;
                try { const r = await enviar(CFG.rutas.equipos + '/' + eqB.dataset.equipoBorrar, 'DELETE'); toast(r.mensaje); verEquipos(); } catch (err) { toast(primerError(err), { error: true }); }
            });
            return true;
        }
        return false;
    }
    raiz.addEventListener('click', (e) => {
        const t = e.target;
        if (t.closest('[data-vermas]')) { const d = $('.agx-desc'); if (d) d.classList.remove('is-corto'); t.closest('[data-vermas]').remove(); return; }
        if (t.closest('[data-menu-item]')) { const b = t.closest('[data-menu-item]'); if (flot && flotAncla === b) cerrarFlot(); else menuDetalle(b); return; }
        if (t.closest('[data-plegar]')) { plegar(); return; }
        if (t.closest('[data-abrir-nav]')) { abrirNavMovil(true); return; }
        const amb = t.closest('[data-ambito]'); if (amb) { abrirNavMovil(false); irA({ ambito: amb.dataset.ambito, seccion: null }); return; }
        const sec = t.closest('[data-seccion]'); if (sec) { abrirNavMovil(false); irA({ seccion: sec.dataset.seccion }); return; }
        const cat = t.closest('[data-cat]');
        if (cat) { const v = cat.dataset.cat; if (S.ocultos.has(v)) S.ocultos.delete(v); else S.ocultos.add(v); guardar(); ocultarTip(); pintarCategorias(); pintar(); return; }
        const vista = t.closest('[data-vista]'); if (vista) { irA({ vista: vista.dataset.vista, seccion: null }); return; }
        const nav = t.closest('[data-nav]'); if (nav) { moverFecha(Number(nav.dataset.nav)); return; }
        if (t.closest('[data-hoy]')) { irA({ fecha: CFG.hoy, seccion: null }); return; }
        const tira = t.closest('[data-dia-tira]'); if (tira) { S.fecha = tira.dataset.diaTira; pintar(); return; }
        if (t.closest('[data-buscar]')) { abrirBuscador(); return; }
        if (t.closest('[data-filtros]')) { const b = t.closest('[data-filtros]'); if (flot && flotAncla === b) cerrarFlot(); else abrirFiltros(b); return; }
        if (t.closest('[data-mas]')) { const b = t.closest('[data-mas]'); if (flot && flotAncla === b) cerrarFlot(); else menuMas(b); return; }
        if (t.closest('[data-cerrar]')) { cerrarPanel(); return; }
        if (t.closest('[data-cerrar-caja]')) { const c = $('#agxAccion'); if (c) c.innerHTML = ''; return; }
        const volver = t.closest('[data-volver]'); if (volver) { const f = $('#agxForm'); if (f) f.dataset.sucio = '0'; abrirItem(volver.dataset.volver); return; }
        if (alClic(e)) return;
        // Creación rápida al seleccionar un espacio vacío.
        const slot = t.closest('.agx-slot'); if (slot) { crearRapido(slot, { fecha_inicio: slot.dataset.fecha, hora_inicio: slot.dataset.hora }); return; }
        const todo = t.closest('.agx-todo-dia'); if (todo && !t.closest('button')) crearRapido(todo, { fecha_inicio: todo.dataset.fecha });
    });
    capa.addEventListener('click', alClic);
    raiz.addEventListener('dblclick', (e) => {
        const dia = e.target.closest('.agx-dia');
        if (dia && !e.target.closest('button')) crearRapido(dia, { fecha_inicio: dia.dataset.fecha });
    });

    raiz.addEventListener('change', (e) => {
        const paso = e.target.closest('[data-paso]');
        if (!paso) return;
        paso.closest('.agx-paso').classList.toggle('is-hecho', paso.checked);
        enviar(CFG.rutas.checklist + '/' + paso.dataset.paso, 'POST', { hecho: paso.checked })
            .catch((err) => { paso.checked = !paso.checked; paso.closest('.agx-paso').classList.toggle('is-hecho', paso.checked); toast(primerError(err), { error: true }); });
    });
    raiz.addEventListener('submit', (e) => {
        const f = e.target.closest('form[data-f]');
        if (f && f.closest('#agxPanel')) { e.preventDefault(); enviarCaja(f); }
    });
    $('#agxVelo').addEventListener('click', () => { if (raiz.classList.contains('nav-abierta')) abrirNavMovil(false); else cerrarPanel(); });

    // Buscador que se expande
    function abrirBuscador() { $('#agxBuscarCaja').classList.add('is-abierto'); setTimeout(() => $('#agxBuscar').focus(), 20); }
    $('#agxBuscar').addEventListener('blur', () => { if (!$('#agxBuscar').value) $('#agxBuscarCaja').classList.remove('is-abierto'); });
    let tBuscar = null;
    $('#agxBuscar').addEventListener('input', (e) => {
        clearTimeout(tBuscar);
        tBuscar = setTimeout(() => { S.q = e.target.value.trim(); if (S.q && !movil() && S.vista !== 'lista' && !S.seccion) S.vista = 'lista'; cargar(); }, 300);
    });

    // Atajos de teclado (fuera de campos de texto)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (document.querySelector('#cmOverlay:not([hidden])')) return;
            if (tipDe) { ocultarTip(); return; }
            if (flot) { cerrarFlot({ volverFoco: true }); e.preventDefault(); return; }
            if (raiz.classList.contains('nav-abierta')) { abrirNavMovil(false); return; }
            if (panelAbierto()) { cerrarPanel(); return; }
            if (document.activeElement === $('#agxBuscar')) $('#agxBuscar').blur();
            return;
        }
        const en = document.activeElement;
        if (e.ctrlKey || e.metaKey || e.altKey || (en && (/^(INPUT|TEXTAREA|SELECT)$/.test(en.tagName) || en.isContentEditable))) return;
        if (document.querySelector('#cmOverlay:not([hidden])') || !document.contains(raiz)) return;
        const k = e.key.toLowerCase();
        const acciones = {
            t: () => irA({ fecha: CFG.hoy, seccion: null }), arrowleft: () => !S.seccion && moverFecha(-1), arrowright: () => !S.seccion && moverFecha(1),
            m: () => irA({ vista: 'mes', seccion: null }), s: () => irA({ vista: 'semana', seccion: null }), d: () => irA({ vista: 'dia', seccion: null }), l: () => irA({ vista: 'lista', seccion: null }),
            h: () => irA({ seccion: 'hoy' }), c: () => crearRapido($('.agx-crear'), { fecha_inicio: S.fecha < CFG.hoy ? CFG.hoy : S.fecha }), '/': () => abrirBuscador(),
            f: () => abrirFiltros($('[data-filtros]')), '[': () => plegar(),
        };
        if (acciones[k] && !(panelAbierto() && ['arrowleft', 'arrowright'].includes(k))) { e.preventDefault(); acciones[k](); }
    });

    // Tiempo real: cuando alguien cambia algo, se vuelve a pedir el rango sin recargar.
    if (window.ObsVivo) {
        window.ObsVivo.on(() => {
            cargar({ silencioso: true });
            const c = $('#agxPanelC');
            const escribiendo = c.contains(document.activeElement) && /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
            if (S.abierto && c._item && !$('#agxForm') && !$('#agxAccion form') && !escribiendo) abrirItem(S.abierto);
        });
    }

    // Al cambiar entre celular y escritorio se pide el rango que toca.
    if (mqMovil.addEventListener) mqMovil.addEventListener('change', () => { cerrarFlot(); S.rangoCargado = null; cargar(); });
    let tRes = null;
    window.addEventListener('resize', () => { clearTimeout(tRes); tRes = setTimeout(() => { if (S.items && vistaReal() === 'mes' && !S.seccion) pintar(); }, 150); });

    pintarCategorias();
    pintarNav();
    cargar();
    if (CFG.abrir) abrirItem(CFG.abrir);
})();
