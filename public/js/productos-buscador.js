/*
 * Buscador dentro de la barra de categorías de Productos.
 *
 * La lupa abre el buscador encima de la barra (misma forma y tamaño, se
 * despliega desde la lupa). Mientras se escribe salen sugerencias con foto;
 * Enter muestra todos los resultados. Flechas para moverse, Esc para cerrar.
 */
(function () {
    'use strict';

    const caja = document.querySelector('[data-pvb]');
    if (!caja) return;

    const form = caja.querySelector('[data-pvb-form]');
    const campo = caja.querySelector('[data-pvb-q]');
    const lupa = caja.querySelector('[data-pvb-abrir]');
    const cerrarBtn = caja.querySelector('[data-pvb-cerrar]');
    const sug = caja.querySelector('[data-pvb-sug]');
    const URL_SUG = caja.dataset.urlSug;
    const VOLVER = caja.dataset.volver || '';

    let timer = null;
    let ctrl = null;
    let seq = 0;
    let activo = -1;

    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // Resalta lo que se escribió dentro del texto (sin romper el escapado).
    function resaltar(texto, q) {
        let html = esc(texto);
        q.split(/\s+/).filter(p => p.length >= 2).forEach(function (p) {
            const re = new RegExp('(' + esc(p).replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            html = html.replace(re, '<mark>$1</mark>');
        });
        return html;
    }

    function abrir() {
        caja.classList.add('is-abierto');
        lupa.setAttribute('aria-expanded', 'true');
        // Seguro: si la animación no corre (pestaña sin pintar), queda abierto igual.
        clearTimeout(abrir.t);
        abrir.t = setTimeout(() => { if (caja.classList.contains('is-abierto')) caja.classList.add('is-listo'); }, 480);
        // Espera a que empiece a desplegarse para dar el foco.
        setTimeout(function () { campo.focus(); campo.select(); }, 60);
        if (campo.value.trim().length >= 2) pedir();
    }

    function cerrar() {
        // Con una búsqueda ya aplicada, cerrar es quitarla.
        if (caja.dataset.buscando === '1' && caja.dataset.sinBusqueda) {
            location.href = caja.dataset.sinBusqueda;
            return;
        }
        caja.classList.remove('is-abierto', 'is-listo');
        lupa.setAttribute('aria-expanded', 'false');
        ocultarSug();
        lupa.focus();
    }

    function ocultarSug() {
        sug.classList.remove('is-visible');
        sug.hidden = true;
        sug.innerHTML = '';
        campo.setAttribute('aria-expanded', 'false');
        campo.removeAttribute('aria-activedescendant');
        activo = -1;
    }

    function mostrarSug(html) {
        sug.innerHTML = html;
        sug.hidden = false;
        requestAnimationFrame(() => sug.classList.add('is-visible'));
        campo.setAttribute('aria-expanded', 'true');
    }

    function pedir() {
        clearTimeout(timer);
        const q = campo.value.trim();
        if (q.length < 2) { ocultarSug(); return; }
        timer = setTimeout(async function () {
            ctrl?.abort();
            ctrl = new AbortController();
            const mio = ++seq;
            if (sug.hidden) mostrarSug('<div class="pvb-sug-cargando"><span></span><span></span><span></span></div>');
            caja.classList.add('is-cargando');
            try {
                const r = await fetch(URL_SUG + '?' + new URLSearchParams({ q, volver: VOLVER }), {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin', signal: ctrl.signal,
                });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const data = await r.json();
                if (mio !== seq) return;      // llegó tarde: hay otra búsqueda
                pintar(data, q);
            } catch (e) {
                if (e.name === 'AbortError' || mio !== seq) return;
                mostrarSug('<p class="pvb-sug-msg">No se pudo buscar. Presiona Enter para ver los resultados.</p>');
            } finally {
                if (mio === seq) caja.classList.remove('is-cargando');
            }
        }, 200);
    }

    function pintar(data, q) {
        activo = -1;
        if (!data.items.length) {
            mostrarSug('<div class="pvb-sug-vacio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M8.5 11h5"/></svg>' +
                '<b>Sin coincidencias</b><span>No encontramos nada con “' + esc(q) + '”. Prueba con menos palabras.</span></div>');
            return;
        }
        const items = data.items.map(function (it, i) {
            return '<a class="pvb-item" role="option" id="pvb-op-' + i + '" href="' + esc(it.url) + '" style="--i:' + i + '">' +
                '<span class="pvb-item-foto">' + (it.img ? '<img src="' + esc(it.img) + '" alt="" loading="lazy" onerror="this.remove()">' : '') + '</span>' +
                '<span class="pvb-item-txt"><b>' + resaltar(it.nombre, q) + '</b><small>' + resaltar([it.sub, it.tipo].filter(Boolean).join(' · '), q) + '</small></span>' +
                '<span class="pvb-item-stock ' + (it.stock > 0 ? '' : 'is-cero') + '">' + (it.stock > 0 ? it.stock + ' disp.' : 'Sin stock') + '</span>' +
                '</a>';
        }).join('');
        mostrarSug(
            '<div class="pvb-sug-cab"><span>Productos</span><span>' + data.total + (data.total === 1 ? ' resultado' : ' resultados') + '</span></div>' +
            '<div class="pvb-sug-lista">' + items + '</div>' +
            '<button type="button" class="pvb-todos"><span>Ver ' + (data.total === 1 ? 'el resultado' : 'los ' + data.total + ' resultados') + '</span><kbd>↵</kbd></button>'
        );
        sug.querySelector('.pvb-todos').addEventListener('click', () => form.requestSubmit());
    }

    function mover(delta) {
        const items = Array.from(sug.querySelectorAll('.pvb-item'));
        if (!items.length) return;
        activo = (activo + delta + items.length) % items.length;
        items.forEach((el, i) => el.classList.toggle('is-activo', i === activo));
        campo.setAttribute('aria-activedescendant', items[activo].id);
        items[activo].scrollIntoView({ block: 'nearest' });
    }

    if (caja.classList.contains('is-abierto')) caja.classList.add('is-listo');

    lupa.addEventListener('click', abrir);
    cerrarBtn.addEventListener('click', cerrar);
    campo.addEventListener('input', pedir);

    campo.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); mover(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); mover(-1); }
        else if (e.key === 'Enter' && activo >= 0) {
            e.preventDefault();
            sug.querySelectorAll('.pvb-item')[activo]?.click();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            if (!sug.hidden) ocultarSug(); else cerrar();
        }
    });

    // Enviar sin texto no busca nada: mejor cerrar.
    form.addEventListener('submit', function (e) {
        if (!campo.value.trim()) { e.preventDefault(); cerrar(); }
    });

    // Clic fuera: se ocultan las sugerencias.
    document.addEventListener('click', function (e) {
        if (!caja.contains(e.target)) ocultarSug();
    });

    // Atajo: "/" abre el buscador (si no se está escribiendo en otro campo).
    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        const t = e.target;
        if (t && t.matches && t.matches('input, textarea, select, [contenteditable]')) return;
        e.preventDefault();
        abrir();
    });
})();
