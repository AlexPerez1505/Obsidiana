window.ObsVivo = (function () {
    const URL_VIVO = (document.currentScript && document.currentScript.dataset.url) || '/_vivo';
    const INTERVALO = 3000;
    const CONTENEDOR = 'main.content';
    // Clases que pone el JavaScript de la pantalla y que el servidor no conoce.
    const ESTADO = ['active', 'open', 'is-open', 'is-on', 'show', 'con-nota', 'falla', 'lleno', 'encima',
        'cargando', 'subiendo', 'con-error', 'expanded', 'collapsed', 'selected', 'seleccionado', 'abierto'];
    const NO_TOCAR = new Set(['SCRIPT', 'STYLE', 'DIALOG', 'CANVAS', 'VIDEO', 'AUDIO', 'IFRAME', 'svg', 'TEMPLATE']);

    let rev = Number(document.querySelector('meta[name="obs-rev"]')?.content || 0);
    let handler = null, apagado = false, ocupado = false, timer = null;
    let visto = rev;

    // Crear o editar algo: lo que se está capturando no debe moverse.
    if (/\/(crear|create|editar|edit|nuevo|nueva)(\/|$)/.test(location.pathname)) apagado = true;

    function programar(ms) {
        clearTimeout(timer);
        timer = setTimeout(revisar, ms || (document.hidden ? 15000 : INTERVALO));
    }

    async function revisar() {
        if (apagado) return;
        if (ocupado) return programar();

        try {
            const r = await fetch(URL_VIVO, { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
            if (!r.ok) return programar(15000);
            const j = await r.json();

            if (j.rev && j.rev !== visto) {
                // Avisa a quien le interese (p. ej. el revisor de permisos del
                // layout) que algo cambió en el sistema, sin otra consulta.
                visto = j.rev;
                document.dispatchEvent(new CustomEvent('obs:cambio', { detail: { rev: j.rev } }));
            }

            if (j.rev && j.rev !== rev) {
                ocupado = true;
                try {
                    if (await aplicar()) rev = j.rev;
                } catch (e) {
                    // Se reintenta con la siguiente revisión.
                } finally {
                    ocupado = false;
                }
            }
        } catch (e) {
            return programar(10000);
        }

        programar();
    }

    /* Algo abierto encima de la pantalla: se espera a que se cierre. */
    function hayAlgoAbierto() {
        if (document.querySelector('dialog[open], #cmOverlay:not([hidden]), #galOverlay:not([hidden]), [data-row-menu-pop]:not([hidden])')) return true;

        // Ventanas propias de cada pantalla (p. ej. las unidades de un producto).
        return [...document.querySelectorAll(CONTENEDOR + ' [class*="modal"], ' + CONTENEDOR + ' [class*="overlay"]')]
            .some(function (el) {
                const css = getComputedStyle(el);
                return css.position === 'fixed' && css.display !== 'none' && css.visibility !== 'hidden'
                    && el.getBoundingClientRect().width > 0;
            });
    }

    /** true si se aplicó; false si hay que volver a intentar. */
    async function aplicar() {
        if (handler) { await handler(); return true; }
        if (hayAlgoAbierto()) return false;

        const res = await fetch(location.href, {
            headers: { 'X-Obs-Vivo': '1', 'Accept': 'text/html' },
            cache: 'no-store',
            credentials: 'same-origin',
        });
        // Si la sesión se cerró o la pantalla ya no existe, no se toca nada.
        if (!res.ok || res.redirected) return true;

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const nuevo = doc.querySelector(CONTENEDOR);
        const actual = document.querySelector(CONTENEDOR);
        if (!nuevo || !actual) return true;

        // Volvió a abrirse algo mientras llegaba la respuesta.
        if (hayAlgoAbierto()) return false;

        morph(actual, nuevo);
        document.dispatchEvent(new CustomEvent('obs:vivo'));
        return true;
    }

    /* ---------- actualizar el DOM en su lugar ---------- */
    function sucio(el) {
        if (el.matches('input[type=checkbox], input[type=radio]')) return el.checked !== el.defaultChecked;
        if (el.matches('input[type=file]')) return !!el.files?.length;
        if (el.matches('input, textarea')) return el.value !== el.defaultValue;
        if (el.tagName === 'SELECT') {
            if (el.multiple) return [...el.options].some(o => o.selected !== o.defaultSelected);
            // Sin opción marcada en el HTML, la de fábrica es la primera.
            const def = Math.max(0, [...el.options].findIndex(o => o.defaultSelected));
            return el.options.length > 0 && el.selectedIndex !== def;
        }
        return false;
    }

    function saltar(el) {
        if (el.nodeType !== 1) return false;
        if (el.dataset?.live === 'off' || NO_TOCAR.has(el.tagName)) return true;
        if (el === document.activeElement || el.contains(document.activeElement) && el.matches('input, textarea, select, [contenteditable]')) return true;
        if (el.tagName === 'FORM' && [...el.elements].some(sucio)) return true;
        if (el.matches?.('input, textarea, select') && sucio(el)) return true;
        return false;
    }

    function atributos(a, b) {
        for (const { name } of [...a.attributes]) {
            if (!b.hasAttribute(name) && !preservar(a, name)) a.removeAttribute(name);
        }
        for (const { name, value } of [...b.attributes]) {
            if (preservar(a, name)) continue;
            if (name === 'class') { clases(a, b); continue; }
            if (a.getAttribute(name) !== value) a.setAttribute(name, value);
        }
    }

    // style/hidden los maneja la pantalla (vista lista/tarjetas, filtros,
    // paneles); open de <details> lo abre el usuario; data-*-listo es la
    // marca de "ya equipado" que ponen los scripts (no se debe equipar dos veces).
    function preservar(el, nombre) {
        return nombre === 'style' || nombre === 'hidden' || nombre.endsWith('-listo')
            || (nombre === 'open' && el.tagName === 'DETAILS');
    }

    function clases(a, b) {
        const quedan = [...a.classList].filter(c => ESTADO.includes(c));
        const nuevas = new Set([...b.classList, ...quedan]);
        if ([...nuevas].sort().join(' ') !== [...a.classList].sort().join(' ')) a.className = [...nuevas].join(' ');
    }

    function mismo(a, b) {
        if (a.nodeType !== b.nodeType) return false;
        if (a.nodeType !== 1) return true;
        return a.tagName === b.tagName && (a.id || '') === (b.id || '');
    }

    // El servidor lo manda vacío pero en pantalla tiene contenido: lo dibujó
    // el JavaScript (mapa, gráfica, fotos elegidas). Se deja como está.
    function loDibujoJs(a, b) {
        return a.children.length > 0 && b.children.length === 0 && !b.textContent.trim();
    }

    function morph(a, b) {
        if (saltar(a)) return;
        atributos(a, b);
        if (loDibujoJs(a, b)) return;
        hijos(a, b);
    }

    function hijos(a, b) {
        let i = 0;

        for (const nb of [...b.childNodes]) {
            const ca = a.childNodes[i];

            if (!ca) { a.appendChild(document.importNode(nb, true)); i++; continue; }

            if (mismo(ca, nb)) {
                if (ca.nodeType === 1) morph(ca, nb);
                else if (ca.nodeValue !== nb.nodeValue) ca.nodeValue = nb.nodeValue;
                i++;
                continue;
            }

            // ¿Está más adelante (mismo id)? Se trae a su lugar.
            if (nb.nodeType === 1 && nb.id) {
                const x = [...a.childNodes].slice(i + 1).find(n => n.nodeType === 1 && n.id === nb.id);
                if (x) { a.insertBefore(x, ca); morph(x, nb); i++; continue; }
            }

            if (saltar(ca)) { i++; continue; }

            a.replaceChild(document.importNode(nb, true), ca);
            i++;
        }

        while (a.childNodes.length > i) {
            const x = a.childNodes[i];
            if (x.nodeType === 1 && saltar(x)) { i++; continue; }
            a.removeChild(x);
        }
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !apagado) { clearTimeout(timer); revisar(); }
    });
    if (!apagado) programar();

    return {
        on(fn) { handler = fn; },
        apagar() { apagado = true; clearTimeout(timer); },
        ahora() { clearTimeout(timer); revisar(); },
        get rev() { return rev; },
    };
})();
