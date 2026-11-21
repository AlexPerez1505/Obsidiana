/*
 * Mensajería de WhatsApp (Obsidiana).
 *
 * Bandeja + chat + panel de contacto. Todo pasa por el servidor (el token de
 * Meta nunca llega al navegador). Tiempo real: consulta /mensajeria/sync cada
 * 3 s; si nada cambió, el servidor contesta al instante sin tocar la base.
 *
 * Reglas de la conversación:
 *  - Si estás al final del chat, lo nuevo se desliza a la vista; si estás
 *    leyendo arriba, se respeta tu posición y aparece "Nuevos mensajes".
 *  - Cada mensaje saliente lleva un uuid: reintentar nunca lo duplica.
 *  - Un borrador por conversación: cambiar de chat no borra lo escrito.
 */
(function () {
    'use strict';

    const raiz = document.getElementById('msj');
    if (!raiz) return;

    const CFG = JSON.parse(raiz.dataset.config);
    const R = CFG.rutas;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const MOVIMIENTO = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $ = (id) => document.getElementById(id);

    // Mensajería trae su propio tiempo real: el global no debe redibujarla.
    try { window.ObsVivo && window.ObsVivo.apagar(); } catch (e) {}

    // El alto de la barra superior, para que el chat ocupe justo la pantalla.
    function medirTop() {
        const top = document.querySelector('.topbar');
        raiz.style.setProperty('--msj-top', (top ? top.getBoundingClientRect().height : 76) + 'px');
    }
    medirTop();
    window.addEventListener('resize', medirTop);

    /* ==================================================================
     |  Estado
     * ================================================================== */
    const S = {
        convs: new Map(),        // id -> conversación
        orden: [],               // ids en orden de actividad
        activa: null,            // id de la conversación abierta
        mensajes: new Map(),     // id (o uuid temporal) -> mensaje de la activa
        hayMas: false,
        cargandoViejos: false,
        filtro: '',
        q: '',
        bandeja: '',
        rev: CFG.rev,
        desde: CFG.ahora || new Date().toISOString(),
        borradores: new Map(),
        cita: null,              // {wamid, quien, texto}
        adjunto: null,           // File
        nuevosSinVer: 0,
        plantillas: null,
    };

    /* ==================================================================
     |  Utilidades
     * ================================================================== */
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function url(ruta, c, m) {
        return ruta.replace('__C__', encodeURIComponent(c ?? '')).replace('__M__', encodeURIComponent(m ?? ''));
    }

    async function pedir(u, opts = {}) {
        const r = await fetch(u, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, ...(opts.json ? { 'Content-Type': 'application/json' } : {}) },
            method: opts.method || 'GET',
            body: opts.json ? JSON.stringify(opts.json) : opts.body,
            cache: 'no-store',
        });
        let j = null;
        try { j = await r.json(); } catch (e) {}
        if (!r.ok) {
            const msg = j?.errors ? Object.values(j.errors)[0][0] : (j?.message || 'No se pudo completar (' + r.status + ').');
            const err = new Error(msg); err.status = r.status; throw err;
        }
        return j;
    }

    function toast(msg, tipo) {
        // El toast del sistema usa 'warn' para los avisos de error.
        if (window.showToast) window.showToast(msg, tipo === 'error' ? 'warn' : (tipo || 'ok'));
    }

    function uuid() {
        if (crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function iniciales(nombre) {
        const limpio = String(nombre || '').replace(/^\+/, '').trim();
        if (/^\d/.test(limpio)) return limpio.slice(-2);
        const p = limpio.split(/\s+/).filter(Boolean);
        return ((p[0]?.[0] || '') + (p.length > 1 ? p[p.length - 1][0] : (p[0]?.[1] || ''))).toUpperCase() || '?';
    }

    function tono(texto) {
        let h = 0;
        for (const ch of String(texto)) h = (h * 31 + ch.charCodeAt(0)) % 360;
        return h;
    }

    function avatar(el, nombre) {
        el.textContent = iniciales(nombre);
        el.style.setProperty('--h', tono(nombre));
    }

    function hora(iso) {
        if (!iso) return '';
        return new Date(iso).toLocaleTimeString('es-MX', { hour: 'numeric', minute: '2-digit' });
    }

    function diaClave(iso) {
        const d = new Date(iso);
        return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
    }

    function etiquetaDia(iso) {
        const d = new Date(iso), hoy = new Date();
        const ayer = new Date(); ayer.setDate(hoy.getDate() - 1);
        if (diaClave(iso) === diaClave(hoy.toISOString())) return 'Hoy';
        if (diaClave(iso) === diaClave(ayer.toISOString())) return 'Ayer';
        const dias = (hoy - d) / 864e5;
        if (dias < 7) return d.toLocaleDateString('es-MX', { weekday: 'long' }).replace(/^./, (c) => c.toUpperCase());
        return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: d.getFullYear() === hoy.getFullYear() ? undefined : 'numeric' });
    }

    function cuandoLista(iso) {
        if (!iso) return '';
        const d = new Date(iso), hoy = new Date();
        if (diaClave(iso) === diaClave(hoy.toISOString())) return hora(iso);
        const ayer = new Date(); ayer.setDate(hoy.getDate() - 1);
        if (diaClave(iso) === diaClave(ayer.toISOString())) return 'Ayer';
        if ((hoy - d) / 864e5 < 7) return d.toLocaleDateString('es-MX', { weekday: 'short' });
        return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit' });
    }

    function peso(b) {
        if (!b) return '';
        return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    }

    function mmss(s) {
        if (!isFinite(s) || s < 0) s = 0;
        return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
    }

    /* Texto seguro: escapa, convierte enlaces http(s) y el formato de WhatsApp. */
    function textoSeguro(t) {
        let h = esc(t);
        h = h.replace(/\bhttps?:\/\/[^\s<]+[^\s<.,;:!?)"'\]]/gi, (u) => {
            const real = u.replace(/&amp;/g, '&');
            return '<a href="' + esc(real) + '" target="_blank" rel="noopener noreferrer nofollow">' + u + '</a>';
        });
        h = h.replace(/(^|[\s(])\*([^*\n]+)\*(?=[\s).,!?]|$)/g, '$1<b>$2</b>')
             .replace(/(^|[\s(])_([^_\n]+)_(?=[\s).,!?]|$)/g, '$1<i>$2</i>')
             .replace(/(^|[\s(])~([^~\n]+)~(?=[\s).,!?]|$)/g, '$1<s>$2</s>');
        return h;
    }

    const ICO = {
        reloj: '<svg class="msj-palomitas" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2"/></svg>',
        una: '<svg class="msj-palomitas" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
        dos: (leido) => '<svg class="msj-palomitas' + (leido ? ' leido' : '') + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m2.5 12.5 4.5 4.5 9.5-9.5"/><path d="m11.5 16.6.4.4 9.5-9.5"/></svg>',
        alerta: '<svg class="msj-palomitas" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/></svg>',
        play: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>',
        pausa: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="6.5" y="5" width="4" height="14" rx="1"/><rect x="13.5" y="5" width="4" height="14" rx="1"/></svg>',
        bajar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>',
        pin: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z"/></svg>',
        persona: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
        responder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 6 6v5"/></svg>',
        reenviado: '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0-6 6v5"/></svg>',
        mic: '<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3" stroke="currentColor" stroke-width="2" fill="none"/></svg>',
        enlace: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>',
        tel: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>',
        copiar: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>',
        respuesta: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 6 6v5"/></svg>',
        nocompat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>',
    };

    function iconoEstado(e) {
        return { enviando: ICO.reloj, enviado: ICO.una, entregado: ICO.dos(false), leido: ICO.dos(true), fallo: ICO.alerta }[e] || '';
    }
    const ETIQUETA_ESTADO = { enviando: 'Enviando', enviado: 'Enviado', entregado: 'Entregado', leido: 'Leído', fallo: 'Falló' };

    /* ==================================================================
     |  Bandeja
     * ================================================================== */
    const lista = $('msjLista');

    function ordenar() {
        S.orden = [...S.convs.values()]
            .filter(visibleConFiltro)
            .sort((a, b) => (b.actividad || '').localeCompare(a.actividad || '') || b.id - a.id)
            .map((c) => c.id);
    }

    function visibleConFiltro(c) {
        if (S.filtro === 'sin_leer' && !c.no_leidos && c.id !== S.activa) return false;
        if (S.bandeja && c.bandeja !== S.bandeja) return false;
        if (S.q) {
            const q = S.q.toLowerCase(), dig = S.q.replace(/\D/g, '');
            if (!(c.nombre || '').toLowerCase().includes(q) && !(dig.length >= 3 && (c.telefono || '').replace(/\D/g, '').includes(dig))) return false;
        }
        return true;
    }

    function itemConv(c) {
        const li = document.createElement('li');
        li.className = 'msj-conv';
        li.dataset.id = c.id;
        li.tabIndex = 0;
        li.setAttribute('role', 'button');
        pintarItem(li, c);
        li.addEventListener('click', () => abrir(c.id));
        li.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(c.id); } });
        return li;
    }

    function pintarItem(li, c) {
        li.classList.toggle('is-activa', c.id === S.activa);
        li.classList.toggle('con-nuevos', c.no_leidos > 0);
        li.setAttribute('aria-label', c.nombre + (c.no_leidos ? ', ' + c.no_leidos + ' sin leer' : ''));
        const est = c.vista_saliente ? iconoEstado(c.vista_estado) : '';
        li.innerHTML =
            '<span class="msj-av"></span>' +
            '<span class="msj-conv-nom">' + esc(c.nombre) + '</span>' +
            '<span class="msj-conv-hora">' + esc(cuandoLista(c.actividad)) + '</span>' +
            '<span class="msj-conv-vista">' + est + '<span>' + esc(c.vista || 'Sin mensajes') + '</span></span>' +
            (c.no_leidos ? '<span class="msj-conv-n">' + (c.no_leidos > 99 ? '99+' : c.no_leidos) + '</span>' : '<span></span>');
        avatar(li.querySelector('.msj-av'), c.nombre);
    }

    function pintarLista() {
        ordenar();
        const existentes = new Map([...lista.children].filter((n) => n.dataset.id).map((n) => [Number(n.dataset.id), n]));
        let previo = null;
        for (const id of S.orden) {
            let li = existentes.get(id);
            const c = S.convs.get(id);
            if (!li) li = itemConv(c);
            else { pintarItem(li, c); existentes.delete(id); }
            const sig = previo ? previo.nextSibling : lista.firstChild;
            if (sig !== li) lista.insertBefore(li, sig);
            previo = li;
        }
        existentes.forEach((n) => n.remove());
        $('msjListaVacia').hidden = S.orden.length > 0;

        const sinLeer = [...S.convs.values()].filter((c) => c.no_leidos > 0).length;
        const n = $('msjSinLeerN');
        n.hidden = !sinLeer; n.textContent = sinLeer;
        const nav = document.getElementById('navMsjNoLeidos');
        if (nav) { nav.hidden = !sinLeer; nav.style.display = sinLeer ? '' : 'none'; nav.textContent = sinLeer; }
    }

    async function cargarConversaciones() {
        const p = new URLSearchParams();
        if (S.q) p.set('q', S.q);
        if (S.filtro) p.set('filtro', S.filtro);
        if (S.bandeja) p.set('bandeja', S.bandeja);
        try {
            const j = await pedir(R.conversaciones + '?' + p);
            if (!S.q && !S.filtro && !S.bandeja) S.convs.clear();
            j.conversaciones.forEach((c) => S.convs.set(c.id, c));
            pintarLista();
        } catch (e) {
            toast(e.message, 'error');
        }
    }

    let tBuscar = null;
    $('msjBuscar').addEventListener('input', (e) => {
        S.q = e.target.value.trim();
        clearTimeout(tBuscar);
        pintarLista();
        tBuscar = setTimeout(cargarConversaciones, 250);
    });
    document.querySelectorAll('.msj-filtro').forEach((b) => b.addEventListener('click', () => {
        document.querySelectorAll('.msj-filtro').forEach((x) => { x.classList.toggle('is-on', x === b); x.setAttribute('aria-selected', x === b); });
        S.filtro = b.dataset.filtro;
        pintarLista();
        cargarConversaciones();
    }));
    $('msjBandeja')?.addEventListener('change', (e) => { S.bandeja = e.target.value; pintarLista(); cargarConversaciones(); });

    /* ==================================================================
     |  Abrir una conversación
     * ================================================================== */
    const hilo = $('msjHilo');
    const hiloIn = $('msjHiloIn');
    const texto = $('msjTexto');

    async function abrir(id, empujar = true) {
        if (S.activa && texto) S.borradores.set(S.activa, texto.value);
        cerrarContacto();
        quitarCita();
        quitarAdjunto();
        S.activa = id;
        S.mensajes.clear();
        S.nuevosSinVer = 0;
        $('msjNuevos').hidden = true;
        hiloIn.innerHTML = '';

        const c = S.convs.get(id);
        $('msjChatVacio').hidden = true;
        $('msjChatIn').hidden = false;
        raiz.classList.add('con-chat');
        if (c) pintarCabecera(c);
        pintarLista();

        if (empujar) {
            try { history.replaceState(null, '', location.pathname + '?c=' + id); } catch (e) {}
        }
        if (texto) {
            texto.value = S.borradores.get(id) || '';
            ajustarTexto();
        }

        try {
            const j = await pedir(url(R.mensajes, id));
            if (S.activa !== id) return;
            S.convs.set(id, j.conversacion);
            pintarCabecera(j.conversacion);
            S.hayMas = j.hay_mas;
            j.mensajes.forEach((m) => S.mensajes.set(m.id, m));
            pintarHilo();
            if (MOVIMIENTO) { hilo.classList.remove('is-entrando'); void hilo.offsetWidth; hilo.classList.add('is-entrando'); }
            irAlFinal(false);
            marcarLeida(id);
            if (texto && matchMedia('(min-width: 861px)').matches) texto.focus({ preventScroll: true });
        } catch (e) {
            toast(e.message, 'error');
        }
    }

    function pintarCabecera(c) {
        avatar($('msjAv'), c.nombre);
        $('msjNombre').textContent = c.nombre;
        const bandeja = CFG.bandejas.length > 1 ? CFG.bandejas.find((b) => b.id === c.bandeja) : null;
        $('msjSub').textContent = c.telefono + (bandeja ? ' · responde desde ' + (bandeja.numero || bandeja.id) : '');
        pintarVentana(c);
    }

    function pintarVentana(c) {
        const v = $('msjVentana');
        const abierta = c.ventana_hasta && new Date(c.ventana_hasta) > new Date();
        v.hidden = false;
        v.classList.toggle('is-cerrada', !abierta);
        if (abierta) {
            const h = (new Date(c.ventana_hasta) - new Date()) / 36e5;
            v.classList.toggle('is-pronto', h < 2);
            v.textContent = h < 1 ? 'Ventana: ' + Math.max(1, Math.round(h * 60)) + ' min' : 'Ventana: ' + Math.floor(h) + ' h';
            v.title = 'Puedes escribir libremente hasta las ' + hora(c.ventana_hasta) + '. Después, solo con plantilla.';
        } else {
            v.classList.remove('is-pronto');
            v.textContent = 'Ventana cerrada';
            v.title = 'Pasaron más de 24 h desde el último mensaje del cliente: solo se puede escribir con plantilla.';
        }
        const barra = $('msjBarra');
        $('msjCerrada').hidden = !!abierta;
        if (barra) barra.hidden = !abierta || !CFG.puedeResponder;
        if (!abierta) { quitarCita(); }
    }

    // La ventana se cierra sola con el tiempo.
    setInterval(() => { const c = S.convs.get(S.activa); if (c) pintarVentana(c); }, 60000);

    function marcarLeida(id) {
        const c = S.convs.get(id);
        if (!c || !c.no_leidos) return;
        c.no_leidos = 0;
        pintarLista();
        pedir(url(R.leer, id), { method: 'POST' }).catch(() => {});
    }

    $('msjVolver').addEventListener('click', () => {
        if (S.activa && texto) S.borradores.set(S.activa, texto.value);
        raiz.classList.remove('con-chat');
        cerrarContacto();
        S.activa = null;
        try { history.replaceState(null, '', location.pathname); } catch (e) {}
        pintarLista();
        setTimeout(() => { if (!S.activa) { $('msjChatIn').hidden = true; $('msjChatVacio').hidden = false; } }, 280);
    });

    /* ==================================================================
     |  Hilo de mensajes
     * ================================================================== */
    function ordenados() {
        return [...S.mensajes.values()].sort((a, b) => {
            if (typeof a.id === 'number' && typeof b.id === 'number') return a.id - b.id;
            if (typeof a.id === 'number') return -1;
            if (typeof b.id === 'number') return 1;
            return (a.momento || '').localeCompare(b.momento || '');
        });
    }

    function pintarHilo(nuevos = new Set()) {
        const msgs = ordenados();
        const frag = document.createDocumentFragment();
        let dia = null, prev = null;
        const viejos = new Map([...hiloIn.querySelectorAll('.msj-m')].map((n) => [n.dataset.k, n]));

        for (const m of msgs) {
            const d = diaClave(m.momento);
            if (d !== dia) {
                const sep = document.createElement('div');
                sep.className = 'msj-fecha';
                sep.textContent = etiquetaDia(m.momento);
                frag.appendChild(sep);
                dia = d; prev = null;
            }
            const primero = !prev || prev.dir !== m.dir || (new Date(m.momento) - new Date(prev.momento)) > 5 * 60000;
            const k = String(m.id);
            let el = viejos.get(k);
            const firma = firmaMensaje(m, primero);
            if (!el || el.dataset.firma !== firma) el = burbuja(m, primero, nuevos.has(m.id));
            frag.appendChild(el);
            prev = m;
        }
        hiloIn.replaceChildren(frag);
        $('msjMas').hidden = !S.cargandoViejos;
    }

    function firmaMensaje(m, primero) {
        return [m.estado, m.error, m.media?.estado, m.media?.url, m.datos?.reaccion, m.cuerpo, primero ? 1 : 0].join('|');
    }

    function burbuja(m, primero, esNuevo) {
        const el = document.createElement('div');
        el.className = 'msj-m ' + m.dir + (primero ? ' primero' : '') + (m.estado === 'fallo' ? ' fallo' : '') + (m.tipo === 'sticker' && m.media?.url ? ' es-sticker' : '') + (esNuevo && MOVIMIENTO ? ' es-nuevo' : '');
        el.dataset.k = String(m.id);
        el.dataset.firma = firmaMensaje(m, primero);
        if (m.wamid) el.dataset.wamid = m.wamid;

        const b = document.createElement('div');
        b.className = 'msj-b';
        let h = '';
        if (m.dir === 'out' && primero && m.autor && m.tipo !== 'sticker') h += '<span class="msj-autor">' + esc(m.autor) + '</span>';
        if (m.datos?.reenviado) h += '<span class="msj-reenviado">' + ICO.reenviado + 'Reenviado</span>';
        if (m.cita) h += '<span class="msj-q" data-ir-cita><b>' + (m.cita.dir === 'in' ? esc(S.convs.get(S.activa)?.nombre || 'Cliente') : 'Tú') + '</b>' + esc(m.cita.texto) + '</span>';
        const [contenido, conMedia] = contenidoMensaje(m);
        h += contenido;
        h += '<span class="msj-meta" title="' + esc(ETIQUETA_ESTADO[m.estado] || '') + '">' + esc(hora(m.momento)) + (m.dir === 'out' ? iconoEstado(m.estado) : '') + '</span>';
        b.innerHTML = h;
        if (conMedia) b.classList.add('con-media');
        el.appendChild(b);

        if (m.datos?.reaccion) {
            const r = document.createElement('span');
            r.className = 'msj-reaccion'; r.textContent = m.datos.reaccion;
            b.appendChild(r);
        }

        if (m.estado === 'fallo') {
            const e = document.createElement('div');
            e.className = 'msj-error';
            e.innerHTML = '<span>' + esc(m.error || 'No se pudo enviar.') + '</span>' + (CFG.puedeResponder && typeof m.id === 'number' ? ' <button type="button" data-reintentar>Reintentar</button>' : '');
            el.appendChild(e);
        }

        // Responder a este mensaje.
        if (CFG.puedeResponder && m.wamid && m.tipo !== 'system') {
            const acc = document.createElement('div');
            acc.className = 'msj-acc';
            acc.innerHTML = '<button type="button" class="msj-icono" data-citar aria-label="Responder a este mensaje" title="Responder">' + ICO.responder + '</button>';
            el.appendChild(acc);
        }

        montarMedios(el, m);
        return el;
    }

    function contenidoMensaje(m) {
        const d = m.datos || {};
        const pie = m.cuerpo ? '<p class="msj-texto">' + textoSeguro(m.cuerpo) + '</p>' : '';
        const media = m.media || {};

        if (['image', 'video', 'audio', 'sticker', 'document'].includes(m.tipo)) {
            if (!media.url) {
                if (media.estado === 'pendiente') {
                    return ['<div class="msj-media-estado"><span class="msj-girando"></span>Descargando ' + esc(etiquetaTipo(m)) + '…</div>' + pie, true];
                }
                return ['<div class="msj-media-estado"><span>No se pudo recuperar ' + esc(etiquetaTipo(m).toLowerCase()) + '.</span>' +
                    (m.error ? '<small>' + esc(m.error) + '</small>' : '') +
                    (m.dir === 'in' ? '<button type="button" class="msj-btn msj-btn--mini" data-reintentar-media>Reintentar</button>' : '') + '</div>' + pie, true];
            }
            if (m.tipo === 'image') {
                return ['<button type="button" class="msj-foto" data-visor aria-label="Ver foto"><img src="' + esc(media.url) + '" alt="' + esc(m.cuerpo || 'Foto') + '" loading="lazy"></button>' + pie, true];
            }
            if (m.tipo === 'video') {
                return ['<video class="msj-video" src="' + esc(media.url) + '" controls preload="metadata" playsinline></video>' + pie, true];
            }
            if (m.tipo === 'sticker') {
                return ['<img class="msj-sticker" src="' + esc(media.url) + '" alt="Sticker" loading="lazy">', true];
            }
            if (m.tipo === 'audio') {
                return ['<div class="msj-audio" data-audio="' + esc(media.url) + '"></div>', false];
            }
            const ext = (media.nombre || '').split('.').pop().slice(0, 4).toUpperCase() || 'DOC';
            return ['<a class="msj-doc" href="' + esc(media.url) + '?descargar=1" download>' +
                '<span class="msj-doc-ico">' + esc(ext) + '</span>' +
                '<span class="msj-doc-info"><b>' + esc(media.nombre || 'Documento') + '</b><small>' + esc([peso(media.tamano), (media.mime || '').split('/').pop()].filter(Boolean).join(' · ')) + '</small></span>' +
                ICO.bajar + '</a>' + pie, true];
        }

        if (m.tipo === 'location' && d.ubicacion) {
            const u = d.ubicacion;
            const mapa = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(u.lat + ',' + u.lng);
            return ['<div class="msj-card"><div class="msj-mapa">' + ICO.pin + '</div>' +
                '<span class="msj-card-et">Ubicación</span>' +
                (u.nombre ? '<b>' + esc(u.nombre) + '</b>' : '') + (u.direccion ? '<small>' + esc(u.direccion) + '</small>' : '') +
                '<a class="msj-card-link" href="' + esc(mapa) + '" target="_blank" rel="noopener noreferrer">' + ICO.enlace + 'Abrir en el mapa</a></div>', false];
        }

        if (m.tipo === 'contacts' && d.contactos) {
            return [d.contactos.map((c) => '<div class="msj-card"><span class="msj-card-et">' + ICO.persona + 'Contacto</span><b>' + esc(c.nombre || 'Sin nombre') + '</b>' +
                (c.empresa ? '<small>' + esc(c.empresa) + '</small>' : '') +
                (c.telefonos || []).map((t) => '<small>' + esc(t.numero) + (t.tipo ? ' · ' + esc(t.tipo) : '') + '</small>').join('') +
                (c.correos || []).map((x) => '<small>' + esc(x) + '</small>').join('') +
                ((c.telefonos || [])[0]?.wa_id ? '<a class="msj-card-link" href="#" data-chatear="' + esc(c.telefonos[0].wa_id) + '">Escribirle</a>' : '') +
                '</div>').join(''), false];
        }

        if ((m.tipo === 'interactive' || m.tipo === 'button') && d.seleccion) {
            const s = d.seleccion;
            return ['<div class="msj-card"><span class="msj-card-et">' + ICO.respuesta + (s.tipo === 'lista' ? 'Eligió de la lista' : (s.tipo === 'formulario' ? 'Respondió el formulario' : 'Tocó el botón')) + '</span>' +
                '<b>' + esc(s.titulo || m.cuerpo || '') + '</b>' + (s.descripcion ? '<small>' + esc(s.descripcion) + '</small>' : '') + '</div>', false];
        }

        if (m.tipo === 'order' && d.pedido) {
            return ['<div class="msj-card"><span class="msj-card-et">Pedido del catálogo</span>' +
                (d.pedido.productos || []).map((p) => '<small>' + esc(p.sku) + ' × ' + esc(p.cantidad) + (p.precio ? ' · $' + esc(p.precio) + ' ' + esc(p.moneda || '') : '') + '</small>').join('') +
                (m.cuerpo ? '<p class="msj-texto">' + textoSeguro(m.cuerpo) + '</p>' : '') + '</div>', false];
        }

        if (m.tipo === 'template' && d.plantilla) {
            return [renderPlantilla(d.plantilla, media), !!media.url];
        }

        if (m.tipo === 'unsupported' || d.no_compatible) {
            return ['<div class="msj-nocompat">' + ICO.nocompat + '<span>Este tipo de mensaje no se puede mostrar aquí. ' + esc(d.no_compatible || '') + ' Ábrelo en el teléfono del negocio.</span></div>', false];
        }

        if (m.tipo === 'system') {
            return ['<div class="msj-nocompat">' + ICO.nocompat + '<span>' + esc(m.cuerpo || 'Aviso de WhatsApp') + '</span></div>', false];
        }

        return [m.cuerpo ? '<p class="msj-texto">' + textoSeguro(m.cuerpo) + '</p>' : '<p class="msj-texto"><i>Mensaje vacío</i></p>', false];
    }

    function etiquetaTipo(m) {
        if (m.tipo === 'audio' && m.datos?.voz) return 'la nota de voz';
        return { image: 'la foto', video: 'el video', audio: 'el audio', sticker: 'el sticker', document: 'el documento' }[m.tipo] || 'el archivo';
    }

    function renderPlantilla(p, media, faltan) {
        const var_ = (s) => esc(s).replace(/\{\{\s*([\w]+)\s*\}\}/g, '<span class="msj-pl-falta">{{$1}}</span>');
        let h = '<div class="msj-pl-render"><span class="msj-pl-chip">Plantilla · ' + esc(p.nombre || '') + '</span>';
        if (p.encabezado_formato && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(p.encabezado_formato)) {
            if (media && media.url) {
                h += '<div class="msj-pl-media">' + (p.encabezado_formato === 'IMAGE' ? '<img src="' + esc(media.url) + '" alt="">' :
                    p.encabezado_formato === 'VIDEO' ? '<video src="' + esc(media.url) + '" controls preload="metadata" playsinline></video>' :
                        '<a class="msj-doc" href="' + esc(media.url) + '?descargar=1"><span class="msj-doc-ico">DOC</span><span class="msj-doc-info"><b>' + esc(media.nombre || 'Documento') + '</b></span></a>') + '</div>';
            } else {
                h += '<div class="msj-media-estado" style="min-height:90px">' + ({ IMAGE: 'Imagen', VIDEO: 'Video', DOCUMENT: 'Documento' }[p.encabezado_formato]) + ' del encabezado</div>';
            }
        } else if (p.encabezado) {
            h += '<span class="msj-pl-h">' + var_(p.encabezado) + '</span>';
        }
        if (p.cuerpo) h += '<p class="msj-texto">' + (faltan ? var_(p.cuerpo) : textoSeguro(p.cuerpo)) + '</p>';
        if (p.pie) h += '<span class="msj-pl-pie">' + esc(p.pie) + '</span>';
        if (p.botones?.length) {
            h += '<div class="msj-pl-botones">' + p.botones.map((b) => '<span>' + (b.tipo === 'URL' ? ICO.enlace : b.tipo === 'PHONE_NUMBER' ? ICO.tel : b.tipo === 'COPY_CODE' ? ICO.copiar : ICO.responder) + esc(b.texto) + '</span>').join('') + '</div>';
        }
        return h + '</div>';
    }

    /* Reproductor de audio propio (play, avance, duración, velocidad). */
    function montarMedios(el, m) {
        const a = el.querySelector('[data-audio]');
        if (a) reproductor(a, a.dataset.audio, !!m.datos?.voz);
    }

    function reproductor(cont, src, voz) {
        const audio = new Audio();
        audio.preload = 'metadata';
        audio.src = src;
        cont.innerHTML =
            '<button type="button" class="msj-audio-play" aria-label="Reproducir">' + ICO.play + '</button>' +
            '<div class="msj-audio-barra" role="slider" tabindex="0" aria-label="Avance" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span class="msj-audio-avance"></span><span class="msj-audio-bola"></span></div>' +
            '<button type="button" class="msj-audio-vel" aria-label="Velocidad">1×</button>' +
            '<span class="msj-audio-t">0:00</span>' +
            (voz ? '<span class="msj-voz">' + ICO.mic + 'Nota de voz</span>' : '<span></span>');
        const btn = cont.querySelector('.msj-audio-play');
        const barra = cont.querySelector('.msj-audio-barra');
        const av = cont.querySelector('.msj-audio-avance');
        const bola = cont.querySelector('.msj-audio-bola');
        const t = cont.querySelector('.msj-audio-t');
        const vel = cont.querySelector('.msj-audio-vel');
        const velocidades = [1, 1.5, 2];
        let duracion = 0;

        const ponerT = () => { t.textContent = audio.paused && !audio.currentTime ? mmss(duracion) : mmss(audio.currentTime); };
        audio.addEventListener('loadedmetadata', () => {
            duracion = audio.duration;
            // Las grabaciones OGG a veces dicen "Infinity" hasta leerse completas.
            if (!isFinite(duracion)) {
                audio.currentTime = 1e7;
                audio.addEventListener('timeupdate', function una() { audio.removeEventListener('timeupdate', una); duracion = audio.duration; audio.currentTime = 0; ponerT(); });
            }
            ponerT();
        });
        audio.addEventListener('timeupdate', () => {
            const p = duracion ? Math.min(100, audio.currentTime / duracion * 100) : 0;
            av.style.width = p + '%'; bola.style.left = p + '%';
            barra.setAttribute('aria-valuenow', Math.round(p));
            ponerT();
        });
        audio.addEventListener('play', () => {
            document.querySelectorAll('.msj audio, .msj video').forEach(() => {});
            window.__msjSonando && window.__msjSonando !== audio && window.__msjSonando.pause();
            window.__msjSonando = audio;
            btn.innerHTML = ICO.pausa; btn.setAttribute('aria-label', 'Pausar');
        });
        audio.addEventListener('pause', () => { btn.innerHTML = ICO.play; btn.setAttribute('aria-label', 'Reproducir'); });
        audio.addEventListener('ended', () => { audio.currentTime = 0; av.style.width = '0'; bola.style.left = '0'; ponerT(); });
        audio.addEventListener('error', () => { t.textContent = 'No se puede reproducir'; });
        btn.addEventListener('click', () => { audio.paused ? audio.play().catch(() => {}) : audio.pause(); });
        vel.addEventListener('click', () => {
            const i = (velocidades.indexOf(audio.playbackRate) + 1) % velocidades.length;
            audio.playbackRate = velocidades[i]; vel.textContent = velocidades[i] + '×';
        });
        const saltar = (x) => {
            const r = barra.getBoundingClientRect();
            if (duracion) audio.currentTime = Math.max(0, Math.min(1, (x - r.left) / r.width)) * duracion;
        };
        barra.addEventListener('click', (e) => saltar(e.clientX));
        barra.addEventListener('keydown', (e) => {
            if (!duracion) return;
            if (e.key === 'ArrowRight') audio.currentTime = Math.min(duracion, audio.currentTime + 5);
            if (e.key === 'ArrowLeft') audio.currentTime = Math.max(0, audio.currentTime - 5);
        });
        return audio;
    }

    /* Clics dentro del hilo: visor, reintentos, responder, ir a la cita. */
    hiloIn.addEventListener('click', async (e) => {
        const el = e.target.closest('.msj-m');
        if (!el) return;
        const m = S.mensajes.get(Number(el.dataset.k)) || S.mensajes.get(el.dataset.k);
        if (!m) return;

        if (e.target.closest('[data-visor]')) return abrirVisor(m);
        if (e.target.closest('[data-citar]')) return citar(m);
        if (e.target.closest('[data-ir-cita]')) {
            const destino = m.cita && hiloIn.querySelector('.msj-m[data-wamid="' + CSS.escape(String(mensajeCitadoWamid(m))) + '"]');
            if (destino) {
                destino.scrollIntoView({ behavior: MOVIMIENTO ? 'smooth' : 'auto', block: 'center' });
                destino.classList.add('is-resaltado'); setTimeout(() => destino.classList.remove('is-resaltado'), 1400);
            }
            return;
        }
        if (e.target.closest('[data-reintentar]')) return reintentar(m);
        if (e.target.closest('[data-reintentar-media]')) {
            const b = e.target.closest('button'); b.disabled = true; b.textContent = 'Intentando…';
            try { const j = await pedir(url(R.reintentarMedia, null, m.id), { method: 'POST' }); actualizarMensajes([j.mensaje]); }
            catch (err) { toast(err.message, 'error'); b.disabled = false; b.textContent = 'Reintentar'; }
            return;
        }
        const chat = e.target.closest('[data-chatear]');
        if (chat) { e.preventDefault(); nuevaConversacion(chat.dataset.chatear); }
    });

    function mensajeCitadoWamid(m) {
        return m.responde_a || [...S.mensajes.values()].find((x) => x.id === m.id)?.responde_a;
    }

    async function reintentar(m) {
        if (m.incierto) {
            const ok = await (window.confirmModal ? window.confirmModal({
                title: '¿Reintentar el envío?',
                message: 'Se perdió la conexión al mandarlo: puede que WhatsApp sí lo haya enviado. Si no ha llegado su confirmación, se manda otra vez.',
                confirmText: 'Reintentar',
            }) : Promise.resolve(true));
            if (!ok) return;
        }
        m.estado = 'enviando'; m.error = null;
        actualizarMensajes([m]);
        try {
            const j = await pedir(url(R.reintentar, null, m.id), { method: 'POST' });
            actualizarMensajes([j.mensaje]);
        } catch (e) {
            m.estado = 'fallo'; m.error = e.message;
            actualizarMensajes([m]);
        }
    }

    /* ---------- Desplazamiento ---------- */
    function cercaDelFinal() {
        return hilo.scrollHeight - hilo.scrollTop - hilo.clientHeight < 120;
    }

    function irAlFinal(suave) {
        requestAnimationFrame(() => {
            hilo.scrollTo({ top: hilo.scrollHeight, behavior: suave && MOVIMIENTO ? 'smooth' : 'auto' });
        });
        S.nuevosSinVer = 0;
        $('msjNuevos').hidden = true;
    }

    $('msjNuevos').addEventListener('click', () => irAlFinal(true));

    hilo.addEventListener('scroll', () => {
        if (cercaDelFinal()) {
            S.nuevosSinVer = 0; $('msjNuevos').hidden = true;
            if (S.activa && S.convs.get(S.activa)?.no_leidos) marcarLeida(S.activa);
        }
        if (hilo.scrollTop < 80 && S.hayMas && !S.cargandoViejos) cargarViejos();
    }, { passive: true });

    async function cargarViejos() {
        const ids = [...S.mensajes.keys()].filter((k) => typeof k === 'number');
        if (!ids.length) return;
        S.cargandoViejos = true;
        $('msjMas').hidden = false;
        const id = S.activa;
        try {
            const j = await pedir(url(R.mensajes, id) + '?antes=' + Math.min(...ids));
            if (S.activa !== id) return;
            const alto = hilo.scrollHeight, arriba = hilo.scrollTop;
            j.mensajes.forEach((m) => S.mensajes.set(m.id, m));
            S.hayMas = j.hay_mas;
            S.cargandoViejos = false;
            pintarHilo();
            // Se conserva lo que estabas viendo.
            hilo.scrollTop = hilo.scrollHeight - alto + arriba;
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            S.cargandoViejos = false;
            $('msjMas').hidden = true;
        }
    }

    /* Integra mensajes nuevos o cambiados (de la sincronía o de un envío). */
    function actualizarMensajes(lista) {
        if (!lista.length) return;
        const estabaAbajo = cercaDelFinal();
        const nuevos = new Set();
        let entrantesNuevos = 0;

        for (const m of lista) {
            if (m.conversacion_id && m.conversacion_id !== S.activa) continue;
            // El mensaje temporal (optimista) se reemplaza por el real.
            if (m.uuid && S.mensajes.has('t:' + m.uuid)) S.mensajes.delete('t:' + m.uuid);
            if (!S.mensajes.has(m.id)) { nuevos.add(m.id); if (m.dir === 'in') entrantesNuevos++; }
            S.mensajes.set(m.id, m);
        }
        pintarHilo(nuevos);

        if (nuevos.size) {
            const mios = lista.some((m) => nuevos.has(m.id) && m.dir === 'out');
            if (estabaAbajo || mios) irAlFinal(true);
            else if (entrantesNuevos) {
                S.nuevosSinVer += entrantesNuevos;
                const b = $('msjNuevos');
                b.querySelector('span').textContent = S.nuevosSinVer === 1 ? '1 mensaje nuevo' : S.nuevosSinVer + ' mensajes nuevos';
                b.hidden = false;
            }
            if (entrantesNuevos && !document.hidden && estabaAbajo) marcarLeida(S.activa);
        }
    }

    /* ==================================================================
     |  Visor de fotos
     * ================================================================== */
    let visorOrigen = null;
    function abrirVisor(m) {
        visorOrigen = document.activeElement;
        $('msjVisorImg').src = m.media.url;
        $('msjVisorImg').alt = m.cuerpo || 'Foto';
        $('msjVisorPie').textContent = m.cuerpo || '';
        $('msjVisorBajar').href = m.media.url + '?descargar=1';
        $('msjVisor').hidden = false;
        $('msjVisorX').focus();
    }
    function cerrarVisor() {
        $('msjVisor').hidden = true;
        $('msjVisorImg').removeAttribute('src');
        visorOrigen?.focus?.();
    }
    $('msjVisorX').addEventListener('click', cerrarVisor);
    $('msjVisor').addEventListener('click', (e) => { if (e.target === e.currentTarget) cerrarVisor(); });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!$('msjVisor').hidden) return cerrarVisor();
        if ($('msjContacto').classList.contains('is-open')) return cerrarContacto();
        if (!$('msjAdjMenu')?.hidden) return cerrarMenu();
    });

    /* ==================================================================
     |  Panel de contacto
     * ================================================================== */
    async function abrirContacto() {
        if (!S.activa) return;
        const p = $('msjContacto'), cuerpo = $('msjContactoCuerpo');
        const c = S.convs.get(S.activa);
        p.classList.add('is-open');
        p.setAttribute('aria-hidden', 'false');
        cuerpo.innerHTML = '<div class="msj-ct-top"><span class="msj-av"></span><b>' + esc(c.nombre) + '</b><span>' + esc(c.telefono) + '</span></div><p class="msj-nota" style="text-align:center">Cargando…</p>';
        avatar(cuerpo.querySelector('.msj-av'), c.nombre);
        try {
            const j = await pedir(url(R.contacto, S.activa));
            const cl = j.cliente;
            let h = '<div class="msj-ct-top"><span class="msj-av"></span><b>' + esc(cl ? cl.nombre : c.nombre) + '</b><span>' + esc(j.telefono) + '</span>' +
                (j.nombre_perfil ? '<span>WhatsApp: ' + esc(j.nombre_perfil) + '</span>' : '') + '</div>';
            if (cl) {
                h += '<div class="msj-ct-sec"><h4>En Obsidiana</h4>' +
                    fila('Teléfono', cl.telefono) + fila('Correo', cl.correo) + fila('RFC', cl.rfc) + fila('Dirección', cl.direccion) +
                    fila('Ventas', cl.ventas_total) + fila('Cotizaciones', cl.cotizaciones_total) +
                    '<a class="msj-btn msj-btn--mini" href="' + esc(cl.url) + '">Abrir ficha del cliente</a></div>';
                if (cl.ventas?.length) {
                    h += '<div class="msj-ct-sec"><h4>Últimas ventas</h4>' + cl.ventas.map((v) =>
                        '<a class="msj-ct-venta" href="' + esc(v.url) + '"><b>' + esc(v.folio) + '</b><span>' + esc(v.fecha) + ' · $' + Number(v.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) + '</span></a>').join('') + '</div>';
                }
            } else {
                h += '<div class="msj-ct-sec"><h4>En Obsidiana</h4><p class="msj-nota">Este número no está ligado a ningún cliente.</p>' +
                    '<a class="msj-btn msj-btn--mini" href="' + esc(j.nuevo_cliente_url) + '">Registrar como cliente</a></div>';
            }
            h += '<div class="msj-ct-sec"><h4>Conversación</h4>' + fila('Desde', j.desde ? new Date(j.desde).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }) : '—') + fila('Mensajes', j.mensajes) + '</div>';
            cuerpo.innerHTML = h;
            avatar(cuerpo.querySelector('.msj-av'), cl ? cl.nombre : c.nombre);
        } catch (e) {
            cuerpo.querySelector('.msj-nota').textContent = e.message;
        }
    }
    function fila(et, v) { return v === null || v === undefined || v === '' ? '' : '<div class="msj-ct-fila"><span>' + esc(et) + '</span><b>' + esc(v) + '</b></div>'; }
    function cerrarContacto() {
        const p = $('msjContacto');
        p.classList.remove('is-open');
        p.setAttribute('aria-hidden', 'true');
    }
    $('msjInfo').addEventListener('click', () => $('msjContacto').classList.contains('is-open') ? cerrarContacto() : abrirContacto());
    $('msjQuien').addEventListener('click', abrirContacto);
    $('msjContactoX').addEventListener('click', cerrarContacto);

    /* ==================================================================
     |  Responder: texto, cita, adjuntos, voz
     * ================================================================== */
    const barra = $('msjBarra');
    const btnEnviar = $('msjEnviar');
    const btnMic = $('msjMic');

    function ajustarTexto() {
        if (!texto) return;
        texto.style.height = 'auto';
        texto.style.height = Math.min(160, texto.scrollHeight) + 'px';
        const hay = texto.value.trim() !== '' || !!S.adjunto;
        btnEnviar.hidden = !hay;
        if (btnMic) btnMic.hidden = hay || !grabadorPosible();
    }

    if (texto) {
        texto.addEventListener('input', () => { ajustarTexto(); if (S.activa) S.borradores.set(S.activa, texto.value); });
        texto.addEventListener('keydown', (e) => {
            // Enter manda; Shift+Enter hace un renglón nuevo (en teléfono, Enter es renglón).
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && matchMedia('(hover: hover)').matches) {
                e.preventDefault();
                barra.requestSubmit();
            }
        });
        texto.addEventListener('paste', (e) => {
            const f = [...(e.clipboardData?.files || [])][0];
            if (f) { e.preventDefault(); ponerAdjunto(f); }
        });
    }

    function citar(m) {
        const c = S.convs.get(S.activa);
        S.cita = { wamid: m.wamid, quien: m.dir === 'in' ? (c?.nombre || 'Cliente') : 'Tú', texto: textoPlano(m) };
        $('msjCitaQuien').textContent = S.cita.quien;
        $('msjCitaTexto').textContent = S.cita.texto;
        $('msjCita').hidden = false;
        texto?.focus();
    }
    function quitarCita() { S.cita = null; $('msjCita').hidden = true; }
    $('msjCitaX')?.addEventListener('click', quitarCita);

    function textoPlano(m) {
        if (m.cuerpo) return m.cuerpo.slice(0, 120);
        return { image: '📷 Foto', video: '🎬 Video', audio: '🎤 Audio', sticker: '✨ Sticker', document: '📄 ' + (m.media?.nombre || 'Documento'), location: '📍 Ubicación', contacts: '👤 Contacto' }[m.tipo] || 'Mensaje';
    }

    /* ---------- Adjuntar ---------- */
    const menu = $('msjAdjMenu');
    const inputArchivo = $('msjArchivo');
    function cerrarMenu() { if (menu) { menu.hidden = true; $('msjAdjuntar').setAttribute('aria-expanded', 'false'); } }
    $('msjAdjuntar')?.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.hidden = !menu.hidden;
        $('msjAdjuntar').setAttribute('aria-expanded', String(!menu.hidden));
    });
    document.addEventListener('click', (e) => { if (menu && !menu.hidden && !e.target.closest('.msj-mas')) cerrarMenu(); });
    menu?.querySelectorAll('[data-tomar]').forEach((b) => b.addEventListener('click', () => {
        inputArchivo.accept = b.dataset.tomar;
        inputArchivo.value = '';
        cerrarMenu();
        inputArchivo.click();
    }));
    inputArchivo?.addEventListener('change', () => { if (inputArchivo.files[0]) ponerAdjunto(inputArchivo.files[0]); });

    const LIMITES = { image: 5, video: 16, audio: 16, document: 100 };
    function tipoArchivo(f) {
        const t = f.type || '', n = f.name.toLowerCase();
        if (['image/jpeg', 'image/png'].includes(t)) return 'image';
        if (['video/mp4', 'video/3gpp'].includes(t)) return 'video';
        if (t.startsWith('audio/') || /\.(mp3|m4a|aac|amr|ogg|opus)$/.test(n)) return 'audio';
        if (/\.(pdf|docx?|xlsx?|pptx?|txt)$/.test(n)) return 'document';
        return null;
    }

    function ponerAdjunto(f) {
        const tipo = tipoArchivo(f);
        if (!tipo) return toast('WhatsApp no acepta ese formato. Fotos JPG o PNG, videos MP4, audios MP3/M4A/OGG/AAC/AMR o documentos PDF, Word, Excel, PowerPoint o TXT.', 'error');
        if (f.size > LIMITES[tipo] * 1048576) return toast('Ese archivo pesa ' + peso(f.size) + '. WhatsApp acepta hasta ' + LIMITES[tipo] + ' MB para este tipo.', 'error');
        S.adjunto = f;
        const vista = $('msjAdjVista');
        vista.innerHTML = '';
        if (tipo === 'image') { const i = new Image(); i.src = URL.createObjectURL(f); vista.appendChild(i); }
        else if (tipo === 'video') { const v = document.createElement('video'); v.src = URL.createObjectURL(f); v.muted = true; vista.appendChild(v); }
        else vista.textContent = (f.name.split('.').pop() || '').toUpperCase().slice(0, 4);
        $('msjAdjNombre').textContent = f.name;
        $('msjAdjPeso').textContent = peso(f.size) + (tipo === 'audio' ? ' · audio' : (tipo === 'document' ? ' · documento' : ' · se puede escribir un pie de foto'));
        $('msjAdj').hidden = false;
        if (texto) texto.placeholder = tipo === 'audio' ? 'Los audios no llevan texto' : 'Agrega un comentario (opcional)';
        ajustarTexto();
        texto?.focus();
    }
    function quitarAdjunto() {
        S.adjunto = null;
        if ($('msjAdj')) $('msjAdj').hidden = true;
        if (inputArchivo) inputArchivo.value = '';
        if (texto) texto.placeholder = 'Escribe un mensaje';
        ajustarTexto();
    }
    $('msjAdjX')?.addEventListener('click', quitarAdjunto);

    // Soltar un archivo sobre el chat.
    const chatEl = $('msjChat');
    chatEl.addEventListener('dragover', (e) => { if (S.activa && CFG.puedeResponder) e.preventDefault(); });
    chatEl.addEventListener('drop', (e) => {
        if (!S.activa || !CFG.puedeResponder) return;
        e.preventDefault();
        const f = e.dataTransfer.files[0];
        if (f) ponerAdjunto(f);
    });

    /* ---------- Mandar ---------- */
    barra?.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!S.activa) return;
        const t = texto.value.trim();
        if (!t && !S.adjunto) return;
        const f = S.adjunto;
        mandar({ texto: t, archivo: f, voz: false });
        texto.value = '';
        S.borradores.delete(S.activa);
        quitarAdjunto();
        quitarCita();
        ajustarTexto();
    });

    function mandar({ texto: t, archivo, voz }) {
        const id = S.activa;
        const u = uuid();
        const tipo = archivo ? (tipoArchivo(archivo) || 'document') : 'text';
        const temporal = {
            id: 't:' + u, uuid: u, dir: 'out', tipo, cuerpo: tipo === 'audio' ? null : (t || null),
            datos: voz ? { voz: true } : {}, estado: 'enviando', autor: CFG.yo, momento: new Date().toISOString(),
            cita: S.cita ? { dir: S.cita.quien === 'Tú' ? 'out' : 'in', texto: S.cita.texto } : null,
            media: archivo ? { url: URL.createObjectURL(archivo), nombre: archivo.name, tamano: archivo.size, mime: archivo.type, estado: 'listo' } : null,
        };
        S.mensajes.set(temporal.id, temporal);
        actualizarMensajes([]);
        pintarHilo(new Set([temporal.id]));
        irAlFinal(true);

        const fd = new FormData();
        if (t) fd.append('texto', t);
        if (archivo) fd.append('archivo', archivo, archivo.name);
        if (voz) fd.append('voz', '1');
        fd.append('uuid', u);
        if (S.cita?.wamid) fd.append('responde_a', S.cita.wamid);

        subir(url(R.enviar, id), fd, (p) => progresoTemporal(temporal, p))
            .then((j) => { if (S.activa === id) actualizarMensajes([j.mensaje]); refrescarConv(id); })
            .catch((err) => {
                temporal.estado = 'fallo';
                temporal.error = err.message;
                if (S.activa === id) pintarHilo();
                // Si el servidor no respondió, puede que sí se guardó: la sincronía lo reconcilia por uuid.
                if (!err.status) S.rev = null;
            });
    }

    function progresoTemporal(m, p) {
        const el = hiloIn.querySelector('.msj-m[data-k="' + CSS.escape(String(m.id)) + '"] .msj-meta');
        if (el && p < 1) el.textContent = Math.round(p * 100) + '%';
    }

    function subir(u, fd, onProg) {
        return new Promise((resolve, reject) => {
            const x = new XMLHttpRequest();
            x.open('POST', u);
            x.setRequestHeader('Accept', 'application/json');
            x.setRequestHeader('X-CSRF-TOKEN', CSRF);
            x.upload.onprogress = (e) => { if (e.lengthComputable && onProg) onProg(e.loaded / e.total); };
            x.onload = () => {
                let j = null; try { j = JSON.parse(x.responseText); } catch (e) {}
                if (x.status >= 200 && x.status < 300) return resolve(j);
                const msg = j?.errors ? Object.values(j.errors)[0][0] : (j?.message || 'No se pudo enviar (' + x.status + ').');
                const err = new Error(x.status === 413 ? 'El archivo es demasiado grande para el servidor.' : msg); err.status = x.status; reject(err);
            };
            x.onerror = () => { const err = new Error('Sin conexión. Revisa tu internet e intenta de nuevo.'); reject(err); };
            x.send(fd);
        });
    }

    async function refrescarConv(id) {
        try { await sincronizar(true); } catch (e) {}
    }

    /* ---------- Nota de voz (OGG/Opus, lo que pide WhatsApp) ---------- */
    let grabador = null, grabT0 = 0, grabTimer = null, grabBlob = null, grabCancelada = false;

    function grabadorPosible() {
        return CFG.puedeResponder && window.Recorder && window.Recorder.isRecordingSupported && window.Recorder.isRecordingSupported();
    }
    if (btnMic && !grabadorPosible()) btnMic.hidden = true;

    btnMic?.addEventListener('click', async () => {
        if (!grabadorPosible()) return toast('Este navegador no permite grabar notas de voz.', 'error');
        grabBlob = null; grabCancelada = false;
        try {
            grabador = new window.Recorder({ encoderPath: R.encoder, numberOfChannels: 1, encoderSampleRate: 48000, encoderApplication: 2048, streamPages: false });
            grabador.ondataavailable = (bytes) => {
                if (grabCancelada) return;
                grabBlob = new Blob([bytes], { type: 'audio/ogg' });
                const vista = $('msjGrabVista');
                vista.innerHTML = '<div class="msj-audio"></div>';
                reproductor(vista.firstChild, URL.createObjectURL(grabBlob), true);
                $('msjGrabando').classList.add('is-lista');
                $('msjGrabStop').textContent = 'Enviar';
            };
            await grabador.start();
        } catch (e) {
            return toast('No se pudo usar el micrófono. Revisa el permiso del navegador.', 'error');
        }
        barra.hidden = true;
        $('msjGrabando').hidden = false;
        $('msjGrabando').classList.remove('is-lista');
        $('msjGrabVista').innerHTML = '';
        $('msjGrabStop').textContent = 'Detener';
        grabT0 = Date.now();
        $('msjGrabT').textContent = '0:00';
        grabTimer = setInterval(() => {
            const s = (Date.now() - grabT0) / 1000;
            $('msjGrabT').textContent = mmss(s);
            if (s > 15 * 60) $('msjGrabStop').click(); // Límite razonable.
        }, 250);
    });

    $('msjGrabStop')?.addEventListener('click', () => {
        if (grabBlob) {
            const f = new File([grabBlob], 'nota-de-voz-' + Date.now() + '.ogg', { type: 'audio/ogg' });
            terminarGrabacion();
            mandar({ texto: '', archivo: f, voz: true });
            return;
        }
        clearInterval(grabTimer);
        try { grabador?.stop(); } catch (e) {}
    });

    $('msjGrabX')?.addEventListener('click', () => { grabCancelada = true; try { grabador?.stop(); } catch (e) {} terminarGrabacion(); });

    function terminarGrabacion() {
        clearInterval(grabTimer);
        try { grabador?.close?.(); } catch (e) {}
        grabador = null; grabBlob = null;
        $('msjGrabando').hidden = true;
        $('msjGrabVista').innerHTML = '';
        const c = S.convs.get(S.activa);
        if (c) pintarVentana(c);
        ajustarTexto();
    }

    /* ==================================================================
     |  Plantillas
     * ================================================================== */
    const dlgPl = $('msjPl');
    let plSel = null, plMedia = null;

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-abrir-plantillas]')) { cerrarMenu(); abrirPlantillas(); }
        const x = e.target.closest('.msj-dlg [data-cerrar]');
        if (x) x.closest('dialog').close();
    });

    async function abrirPlantillas() {
        if (!S.activa || !CFG.puedeResponder) return;
        plSel = null; plMedia = null;
        $('msjPlDetalle').innerHTML = '<p class="msj-pl-hint">Elige una plantilla para ver cómo se verá y llenar sus datos.</p>';
        dlgPl.showModal();
        if (!S.plantillas) {
            $('msjPlLista').innerHTML = '<li class="msj-nota" style="padding:12px">Cargando plantillas de WhatsApp…</li>';
            try {
                const j = await pedir(R.plantillas);
                if (j.error) toast(j.error, 'error');
                S.plantillas = j.plantillas;
                llenarFiltros();
            } catch (e) {
                $('msjPlLista').innerHTML = '<li class="msj-nota" style="padding:12px">' + esc(e.message) + '</li>';
                return;
            }
        }
        pintarPlantillas();
        $('msjPlBuscar').focus();
    }

    const ESTADOS_PL = { APPROVED: ['Aprobada', 'ok'], PENDING: ['En revisión', 'pend'], REJECTED: ['Rechazada', 'mal'], PAUSED: ['Pausada', 'pend'], DISABLED: ['Desactivada', 'mal'], IN_APPEAL: ['En apelación', 'pend'], LIMIT_EXCEEDED: ['Límite excedido', 'mal'] };
    const CATS = { MARKETING: 'Marketing', UTILITY: 'Utilidad', AUTHENTICATION: 'Autenticación' };

    function llenarFiltros() {
        const uniq = (k) => [...new Set(S.plantillas.map((p) => p[k]).filter(Boolean))].sort();
        $('msjPlIdioma').innerHTML = '<option value="">Idioma</option>' + uniq('idioma').map((v) => '<option>' + esc(v) + '</option>').join('');
        $('msjPlCategoria').innerHTML = '<option value="">Categoría</option>' + uniq('categoria').map((v) => '<option value="' + esc(v) + '">' + esc(CATS[v] || v) + '</option>').join('');
        $('msjPlEstado').innerHTML = '<option value="">Estado</option>' + uniq('estado').map((v) => '<option value="' + esc(v) + '">' + esc((ESTADOS_PL[v] || [v])[0]) + '</option>').join('');
    }
    ['msjPlBuscar', 'msjPlIdioma', 'msjPlCategoria', 'msjPlEstado'].forEach((id) => $(id).addEventListener('input', pintarPlantillas));

    function pintarPlantillas() {
        const q = $('msjPlBuscar').value.trim().toLowerCase();
        const idi = $('msjPlIdioma').value, cat = $('msjPlCategoria').value, est = $('msjPlEstado').value;
        const items = (S.plantillas || []).filter((p) =>
            (!q || p.nombre.toLowerCase().includes(q) || cuerpoDe(p).toLowerCase().includes(q)) &&
            (!idi || p.idioma === idi) && (!cat || p.categoria === cat) && (!est || p.estado === est));
        $('msjPlLista').innerHTML = items.length ? items.map((p, i) => {
            const [et, cls] = ESTADOS_PL[p.estado] || [p.estado, ''];
            return '<li class="msj-pl-it' + (plSel === p ? ' is-on' : '') + '" data-i="' + S.plantillas.indexOf(p) + '" tabindex="0" role="button">' +
                '<b>' + esc(p.nombre) + '</b><span class="msj-pl-tags"><span class="msj-tag msj-tag--' + cls + '">' + esc(et) + '</span><span class="msj-tag">' + esc(CATS[p.categoria] || p.categoria || '') + '</span><span class="msj-tag">' + esc(p.idioma) + '</span></span></li>';
        }).join('') : '<li class="msj-nota" style="padding:12px">Sin plantillas con esos filtros.</li>';
    }

    $('msjPlLista').addEventListener('click', (e) => { const li = e.target.closest('[data-i]'); if (li) elegirPlantilla(S.plantillas[Number(li.dataset.i)]); });
    $('msjPlLista').addEventListener('keydown', (e) => { const li = e.target.closest('[data-i]'); if (li && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); elegirPlantilla(S.plantillas[Number(li.dataset.i)]); } });

    function parte(p, tipo) { return (p.componentes || []).find((c) => String(c.type).toUpperCase() === tipo) || null; }
    function cuerpoDe(p) { return parte(p, 'BODY')?.text || ''; }
    function varsDe(t) { return [...new Set([...String(t || '').matchAll(/\{\{\s*([\w]+)\s*\}\}/g)].map((m) => m[1]))]; }

    function elegirPlantilla(p) {
        plSel = p; plMedia = null;
        pintarPlantillas();
        const h = parte(p, 'HEADER'), b = parte(p, 'BODY');
        const botones = parte(p, 'BUTTONS')?.buttons || [];
        let campos = '';

        if (h && String(h.format).toUpperCase() === 'TEXT' && varsDe(h.text).length) {
            campos += '<h4>Encabezado</h4>' + varsDe(h.text).map((v, i) => campo('header', v, h.example?.header_text?.[i] || h.example?.header_text_named_params?.find((x) => x.param_name === v)?.example)).join('');
        }
        if (p.media) {
            const acepta = { image: 'image/jpeg,image/png', video: 'video/mp4', document: 'application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt' }[p.media];
            campos += '<h4>Encabezado</h4><label class="msj-campo"><span>' + ({ image: 'Imagen', video: 'Video', document: 'Documento' }[p.media]) + ' <em>(obligatorio)</em></span><input type="file" id="msjPlMedia" accept="' + acepta + '"></label>';
        }
        if (b && varsDe(b.text).length) {
            const ej = b.example?.body_text?.[0] || [];
            const ejN = b.example?.body_text_named_params || [];
            campos += '<h4>Mensaje</h4>' + varsDe(b.text).map((v, i) => campo('body', v, ej[i] || ejN.find((x) => x.param_name === v)?.example)).join('');
        }
        botones.forEach((bt, i) => {
            const t = String(bt.type).toUpperCase();
            if (t === 'URL' && varsDe(bt.url).length) campos += '<h4>Botón</h4>' + campo('botones', i, bt.example?.[0], 'Final del enlace de "' + bt.text + '"');
            if (t === 'COPY_CODE') campos += '<h4>Botón</h4>' + campo('botones', i, bt.example?.[0], 'Código para copiar');
        });

        const [et] = ESTADOS_PL[p.estado] || [p.estado];
        const bloqueo = !p.usable
            ? '<div class="msj-pl-bloqueo"><b>No se puede enviar.</b> ' + (p.estado !== 'APPROVED' ? 'Su estado en Meta es "' + esc(et) + '".' + (p.motivo && p.motivo !== 'NONE' ? ' Motivo: ' + esc(p.motivo) + '.' : '') : esc(p.no_usable || '')) + '</div>'
            : '';

        $('msjPlDetalle').innerHTML = bloqueo +
            '<form class="msj-pl-form" id="msjPlForm">' + (campos || '<p class="msj-nota">Esta plantilla no lleva datos variables.</p>') + '</form>' +
            '<div class="msj-pl-previa"><span class="msj-pl-previa-et">Vista previa</span><div id="msjPlVista"></div></div>' +
            '<div class="msj-pl-acciones"><small>' + esc(CATS[p.categoria] || '') + ' · ' + esc(p.idioma) + '</small>' +
            '<button type="button" class="msj-btn" data-cerrar>Cancelar</button>' +
            '<button type="button" class="msj-btn msj-btn--primario" id="msjPlEnviar"' + (p.usable ? '' : ' disabled') + '>Enviar plantilla</button></div>';

        $('msjPlForm').addEventListener('input', vistaPlantilla);
        $('msjPlMedia')?.addEventListener('change', (e) => { plMedia = e.target.files[0] || null; vistaPlantilla(); });
        $('msjPlEnviar').addEventListener('click', enviarPlantilla);
        vistaPlantilla();
    }

    function campo(grupo, clave, ejemplo, etiqueta) {
        return '<label class="msj-campo"><span>' + esc(etiqueta || ('{{' + clave + '}}')) + (ejemplo ? ' <em>· ej. ' + esc(ejemplo) + '</em>' : '') + '</span>' +
            '<input type="text" data-grupo="' + grupo + '" data-clave="' + esc(clave) + '" maxlength="1024" autocomplete="off"></label>';
    }

    function valoresPlantilla() {
        const v = { header: {}, body: {}, botones: {} };
        $('msjPlForm')?.querySelectorAll('[data-grupo]').forEach((i) => { v[i.dataset.grupo][i.dataset.clave] = i.value; });
        return v;
    }

    function vistaPlantilla() {
        const p = plSel; if (!p) return;
        const v = valoresPlantilla();
        const llenar = (t, vals) => String(t || '').replace(/\{\{\s*([\w]+)\s*\}\}/g, (m, k) => (vals[k] || '').trim() ? vals[k] : m);
        const h = parte(p, 'HEADER'), b = parte(p, 'BODY'), f = parte(p, 'FOOTER');
        const formato = h ? String(h.format).toUpperCase() : null;
        const vista = {
            nombre: p.nombre,
            encabezado_formato: formato,
            encabezado: formato === 'TEXT' ? llenar(h.text, v.header) : null,
            cuerpo: llenar(b?.text, v.body),
            pie: f?.text || null,
            botones: (parte(p, 'BUTTONS')?.buttons || []).map((bt) => ({ tipo: String(bt.type).toUpperCase(), texto: bt.text })),
        };
        const media = plMedia ? { url: URL.createObjectURL(plMedia), nombre: plMedia.name } : null;
        $('msjPlVista').innerHTML = '<div class="msj-m out primero"><div class="msj-b' + (media ? ' con-media' : '') + '">' + renderPlantilla(vista, media, true) +
            '<span class="msj-meta">' + esc(hora(new Date().toISOString())) + ICO.una + '</span></div></div>';

        const faltan = $('msjPlForm').querySelectorAll('[data-grupo]');
        const completos = [...faltan].every((i) => i.value.trim() !== '') && (!p.media || !!plMedia);
        $('msjPlEnviar').disabled = !p.usable || !completos;
        $('msjPlEnviar').title = completos ? '' : 'Llena todos los datos' + (p.media ? ' y elige el archivo del encabezado' : '');
    }

    async function enviarPlantilla() {
        const p = plSel; if (!p || !S.activa) return;
        const btn = $('msjPlEnviar');
        btn.disabled = true; btn.textContent = 'Enviando…';
        const fd = new FormData();
        fd.append('nombre', p.nombre);
        fd.append('idioma', p.idioma);
        fd.append('valores', JSON.stringify(valoresPlantilla()));
        fd.append('uuid', uuid());
        if (plMedia) fd.append('media', plMedia, plMedia.name);
        const id = S.activa;
        try {
            const j = await subir(url(R.plantilla, id), fd);
            dlgPl.close();
            if (S.activa === id) actualizarMensajes([j.mensaje]);
            if (j.mensaje.estado === 'fallo') toast(j.mensaje.error || 'WhatsApp no aceptó la plantilla.', 'error');
            else toast('Plantilla enviada.');
            refrescarConv(id);
        } catch (e) {
            toast(e.message, 'error');
            btn.disabled = false; btn.textContent = 'Enviar plantilla';
        }
    }

    /* ==================================================================
     |  Nueva conversación
     * ================================================================== */
    const dlgNueva = $('msjNuevaDlg');
    $('msjNueva')?.addEventListener('click', () => { $('msjNuevaTel').value = ''; dlgNueva.showModal(); $('msjNuevaTel').focus(); });
    $('msjNuevaForm')?.addEventListener('submit', (e) => { e.preventDefault(); nuevaConversacion($('msjNuevaTel').value); });

    async function nuevaConversacion(tel) {
        try {
            const j = await pedir(R.nueva, { method: 'POST', json: { telefono: tel, bandeja: S.bandeja || null } });
            dlgNueva.open && dlgNueva.close();
            S.convs.set(j.conversacion.id, j.conversacion);
            await abrir(j.conversacion.id);
            if (!j.conversacion.ventana_hasta) abrirPlantillas();
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    /* ==================================================================
     |  Tiempo real
     * ================================================================== */
    let sincronizando = false, tSync = null;

    async function sincronizar(forzar) {
        if (sincronizando) return;
        sincronizando = true;
        try {
            const p = new URLSearchParams({ rev: forzar ? '' : (S.rev || ''), desde: S.desde });
            if (S.activa) p.set('conv', S.activa);
            const j = await pedir(R.sync + '?' + p);
            S.rev = j.rev;
            if (j.sin_cambios) return;
            S.desde = j.desde;

            let cambioActiva = false;
            for (const c of j.conversaciones) {
                const antes = S.convs.get(c.id);
                // La abierta y vista: lo nuevo ya se leyó.
                if (c.id === S.activa && c.no_leidos && !document.hidden && cercaDelFinal()) { c.no_leidos = 0; cambioActiva = true; }
                S.convs.set(c.id, c);
                if (c.id === S.activa) pintarCabecera(c);
                if (!antes && c.no_leidos && document.hidden) avisarNavegador(c);
            }
            pintarLista();
            if (j.mensajes.length) actualizarMensajes(j.mensajes);
            if (cambioActiva) pedir(url(R.leer, S.activa), { method: 'POST' }).catch(() => {});
        } catch (e) {
            // Se reintenta en la siguiente vuelta.
        } finally {
            sincronizando = false;
        }
    }

    function programar() {
        clearTimeout(tSync);
        tSync = setTimeout(async () => { await sincronizar(false); programar(); }, document.hidden ? 15000 : 3000);
    }
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { sincronizar(false); if (S.activa) marcarLeida(S.activa); } programar(); });

    function avisarNavegador(c) {
        try { document.title = '(' + [...S.convs.values()].filter((x) => x.no_leidos).length + ') Mensajería'; } catch (e) {}
    }
    document.addEventListener('visibilitychange', () => { if (!document.hidden) document.title = document.title.replace(/^\(\d+\)\s*/, ''); });

    /* ==================================================================
     |  Arranque
     * ================================================================== */
    (async function () {
        await cargarConversaciones();
        const pedido = CFG.abrir || Number(new URLSearchParams(location.search).get('c')) || null;
        if (pedido) await abrir(pedido, false);
        programar();
    })();
})();
