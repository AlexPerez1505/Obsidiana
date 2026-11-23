/*
 * Consulta por escaneo.
 *
 * - La pistola es un teclado rápido que termina con Enter: se escucha en
 *   todo el documento, sin robarle lo que se teclea en campos editables.
 *   También se puede escribir y dar Enter o "Buscar".
 * - Cada lectura lleva un número; solo la vigente puede pintar la ficha y
 *   sonar. Una respuesta atrasada o una lectura duplicada en curso no
 *   reemplazan el resultado ni disparan otra señal.
 * - La ficha se cierra sola a los 10 s, contados desde que el resumen ya se
 *   ve. "Mantener abierto", desplazar el historial o navegarlo con teclado
 *   cancelan el cierre y el desplazamiento automático.
 * - Señales: tono + vibración según el resultado, cada una se apaga aparte.
 *   Los tonos se generan al momento (Web Audio): no hay archivos que cargar
 *   y la consulta nunca espera al sonido.
 */
(function () {
    'use strict';

    const raiz = document.querySelector('[data-cxe]');
    if (!raiz) return;

    const URL_BUSCAR = raiz.dataset.urlBuscar;
    const URL_FICHA = raiz.dataset.urlFicha;
    const URL_HISTORIAL = raiz.dataset.urlHistorial;

    const SEGUNDOS = 10;
    const PAUSA_LECTOR = 120;     // ms entre teclas: más que esto = otra lectura
    const reducido = window.matchMedia('(prefers-reduced-motion: reduce)');

    const $ = (sel, el = raiz) => el.querySelector(sel);
    const input = $('[data-cxe-input]');
    const form = $('[data-cxe-form]');
    const marco = $('[data-cxe-marco]');
    const resultadoEl = $('[data-cxe-resultado]');
    const listoEl = $('[data-cxe-listo]');
    const listoT = $('[data-cxe-listo-t]');
    const anuncio = $('[data-cxe-anuncio]');
    const modal = $('[data-cxe-modal]');
    const resumenEl = $('[data-cxe-resumen]');
    const linea = $('[data-cxe-linea]');
    const historiaN = $('[data-cxe-historia-n]');
    const avisoModal = $('[data-cxe-aviso-modal]');
    const avisoPagina = $('[data-cxe-aviso-pagina]');
    const cuentaEl = $('[data-cxe-cuenta]');
    const barra = $('[data-cxe-barra]');
    const barraV = $('[data-cxe-barra-v]');
    const btnMantener = $('[data-cxe-mantener]');
    const btnCerrar = $('[data-cxe-cerrar]');
    const btnUltimo = $('[data-cxe-ultimo]');
    const almacenLista = $('[data-cxe-almacen-lista]');
    const almacenVacio = $('[data-cxe-almacen-vacio]');
    const almacenLimpiar = $('[data-cxe-almacen-limpiar]');
    const ALMACEN_KEY = 'cxe.almacen.recientes';

    /* =====================================================================
       Señales: sonido y vibración
       ===================================================================== */
    const Senal = (function () {
        const AC = window.AudioContext || window.webkitAudioContext;
        const puedeVibrar = typeof navigator.vibrate === 'function';
        const btnSonido = $('[data-cxe-sonido]');
        const btnVibrar = $('[data-cxe-vibrar]');
        const btnActivar = $('[data-cxe-activar-sonido]');
        let ctx = null;

        const leer = (k, def) => { try { const v = localStorage.getItem('cxe.' + k); return v === null ? def : v === '1'; } catch (e) { return def; } };
        const guardar = (k, v) => { try { localStorage.setItem('cxe.' + k, v ? '1' : '0'); } catch (e) { /* sin almacenamiento */ } };

        let sonido = !!AC && leer('sonido', true);
        let vibrar = puedeVibrar && leer('vibrar', true);

        function pintar() {
            btnSonido.setAttribute('aria-pressed', String(sonido));
            if (!AC) { btnSonido.disabled = true; btnSonido.title = 'Este navegador no puede reproducir sonido'; }

            btnVibrar.setAttribute('aria-pressed', String(vibrar));
            if (!puedeVibrar) {
                btnVibrar.disabled = true;
                btnVibrar.title = 'Este equipo o navegador no puede vibrar';
                $('[data-cxe-vibrar-t]').textContent = 'Sin vibración';
            }

            // El navegador pide un toque antes de dejar sonar.
            btnActivar.hidden = !(sonido && AC && (!ctx || ctx.state !== 'running'));
        }

        function contexto() {
            if (!AC) return null;
            if (!ctx) {
                try { ctx = new AC(); } catch (e) { return null; }
                ctx.onstatechange = pintar;
            }
            return ctx;
        }

        // Cualquier tecla o toque cuenta como permiso para el audio; la
        // primera lectura de la pistola ya lo desbloquea.
        function desbloquear() {
            if (!sonido) return;
            const c = contexto();
            if (c && c.state !== 'running') c.resume().then(pintar, pintar);
        }
        document.addEventListener('pointerdown', desbloquear, true);
        document.addEventListener('keydown', desbloquear, true);

        function tonos(lista) {
            if (!sonido) return;
            const c = contexto();
            if (!c || c.state !== 'running') return;     // sin permiso: solo lo visual
            let t = c.currentTime + 0.01;
            lista.forEach(function ([frec, dur, pausa, tipo, vol]) {
                const o = c.createOscillator();
                const g = c.createGain();
                o.type = tipo;
                o.frequency.setValueAtTime(frec, t);
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(vol, t + 0.012);
                g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                o.connect(g).connect(c.destination);
                o.start(t);
                o.stop(t + dur + 0.02);
                t += dur + pausa;
            });
        }

        function vibra(patron) {
            if (vibrar && puedeVibrar) { try { navigator.vibrate(patron); } catch (e) { /* sin vibración */ } }
        }

        btnSonido.addEventListener('click', function () {
            sonido = !sonido; guardar('sonido', sonido);
            if (sonido) { desbloquear(); setTimeout(() => tonos([[880, .07, 0, 'sine', .08]]), 30); }
            pintar();
        });
        btnVibrar.addEventListener('click', function () {
            vibrar = !vibrar; guardar('vibrar', vibrar);
            if (vibrar) vibra(40);
            pintar();
        });
        btnActivar.addEventListener('click', function () {
            const c = contexto();
            if (!c) return;
            c.resume().then(function () { pintar(); tonos([[660, .07, .02, 'sine', .1], [880, .1, 0, 'sine', .1]]); }, pintar);
            input.focus({ preventScroll: true });
        });

        pintar();

        return {
            // Tono ascendente suave + un pulso corto.
            exito() { tonos([[660, .075, .015, 'sine', .14], [990, .12, 0, 'sine', .14]]); vibra(60); },
            // Dos tonos graves cortos + dos pulsos marcados.
            error() { tonos([[220, .12, .07, 'triangle', .22], [175, .14, 0, 'triangle', .22]]); vibra([120, 70, 120]); },
        };
    })();

    /* =====================================================================
       Estado
       ===================================================================== */
    let seq = 0;                 // número de la lectura vigente
    let senalada = 0;            // última lectura que ya sonó
    let enVuelo = null;          // { codigo, ctrl }
    let actual = null;           // ficha que se está viendo
    let cargados = 0;            // eventos del historial ya pintados
    let retenida = false;        // el operador la dejó abierta
    let pendiente = null;        // lectura que llegó con la ficha retenida
    let reloj = null;            // intervalo de la cuenta regresiva
    let fin = 0;
    let scrollAnim = null;
    let scrollProgramado = null;
    let cerrando = null;

    /** Una sola señal por lectura resuelta, y solo si sigue vigente. */
    function senal(tipo, mio) {
        if (mio !== seq || senalada === mio) return;
        senalada = mio;
        tipo === 'exito' ? Senal.exito() : Senal.error();
    }

    /* =====================================================================
       Lector (teclado)
       ===================================================================== */
    let buffer = '';
    let ultimaTecla = 0;

    document.addEventListener('keydown', function (e) {
        const t = e.target;
        const editable = t && typeof t.matches === 'function'
            && t.matches('input, textarea, select, [contenteditable=""], [contenteditable="true"]');

        // Campos editables se respetan; el propio maneja su Enter.
        if (editable) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        const ahora = Date.now();
        if (ahora - ultimaTecla > PAUSA_LECTOR) buffer = '';
        ultimaTecla = ahora;

        if (e.key === 'Enter') {
            const leido = buffer.trim();
            buffer = '';
            // Solo cuenta si llegó a velocidad de pistola; un Enter humano
            // sobre un botón sigue funcionando.
            if (leido.length >= 3) {
                e.preventDefault();
                e.stopPropagation();
                if (!modal.open) input.value = leido;
                consultar(leido);
            }
            return;
        }

        if (e.key.length === 1) {
            buffer += e.key;
            // Las teclas de la pistola no deben accionar botones ni
            // desplazar el historial mientras se arma la lectura.
            if (buffer.length > 1 && e.key === ' ') e.preventDefault();
        }
    }, true);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const v = input.value.trim();
        if (!v) { input.focus(); return; }
        input.select();
        consultar(v);
    });

    // La cámara hace lo mismo que la pistola.
    $('[data-lectorqr]')?.addEventListener('lector-qr', e => consultar(e.detail.texto));

    /* =====================================================================
       Consultar
       ===================================================================== */
    async function consultar(leido) {
        // La misma lectura que ya va en camino: no se duplica.
        if (enVuelo && enVuelo.codigo === leido) return;

        enVuelo?.ctrl.abort();
        const mio = ++seq;
        const ctrl = new AbortController();
        enVuelo = { codigo: leido, ctrl };

        buscando(true);

        let data;
        try {
            const r = await fetch(URL_BUSCAR + '?' + new URLSearchParams({ codigo: leido }), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin', cache: 'no-store', signal: ctrl.signal,
            });
            if (r.status === 401 || r.status === 419) { location.reload(); return; }
            if (!r.ok) throw new Error('HTTP ' + r.status);
            data = await r.json();
        } catch (err) {
            if (err.name === 'AbortError' || mio !== seq) return;
            enVuelo = null;
            buscando(false);
            errorConexion(leido, () => consultar(leido));
            return;
        }

        if (mio !== seq) return;      // llegó tarde: ya hay otra lectura
        enVuelo = null;
        buscando(false);
        resultado(data, leido, mio);
    }

    function resultado(data, leido, mio) {
        const codigo = data.codigo || leido;

        if (data.estado === 'no_encontrado') {
            marcar('error', 'Código no encontrado');
            senal('error', mio);
            aviso('error', ICO.error, 'Código no encontrado: <b>' + esc(codigo) + '</b>' +
                '<small>Revisa la etiqueta o corrige el código y vuelve a buscar.</small>');
            decir('Código no encontrado: ' + codigo);
            // El código se queda para corregirlo o volver a escanearlo.
            input.value = leido;
            listoParaOtra();
            return;
        }

        if (data.estado === 'varios') {
            marcar('varios', data.opciones.length + ' coincidencias');
            opciones(data);
            decir(data.opciones.length + ' coincidencias. Elige una.');
            return;
        }

        if (!data.ficha) { errorConexion(leido, () => consultar(leido)); return; }

        const ficha = data.ficha;
        ficha.codigo = codigo;
        marcar('ok', 'Encontrado');
        senal('exito', mio);
        limpiarAviso(avisoPagina);
        decir('Encontrado: ' + ficha.resumen.nombre);

        if (modal.open && retenida && !(actual && actual.tipo === ficha.tipo && actual.id === ficha.id)) {
            pendiente = ficha;
            avisoPendiente();
            return;
        }

        mostrar(ficha);
    }

    async function elegir(tipo, id, codigo) {
        enVuelo?.ctrl.abort();
        enVuelo = null;
        const mio = ++seq;
        buscando(true);
        try {
            const r = await fetch(URL_FICHA + '?' + new URLSearchParams({ tipo, id, codigo }), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store',
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const data = await r.json();
            if (mio !== seq) return;
            buscando(false);
            limpiarAviso(avisoModal);
            limpiarAviso(avisoPagina);
            data.ficha.codigo = codigo;
            marcar('ok', 'Encontrado');
            senal('exito', mio);           // la coincidencia elegida es válida
            decir('Encontrado: ' + data.ficha.resumen.nombre);
            mostrar(data.ficha);
        } catch (e) {
            if (mio !== seq) return;
            buscando(false);
            errorConexion(codigo, () => elegir(tipo, id, codigo));
        }
    }

    /* =====================================================================
       Indicadores del lector
       ===================================================================== */
    function buscando(si) {
        marco.classList.toggle('is-buscando', si);
        if (si) {
            marco.classList.remove('is-error', 'is-ok');
            resultadoEl.className = 'cxe-resultado';
            resultadoEl.innerHTML = '';
            listo('buscando', 'Consultando…');
        }
    }

    let marcaTimer = null;
    function marcar(tipo, texto) {
        clearTimeout(marcaTimer);
        marco.classList.remove('is-error', 'is-ok');
        resultadoEl.className = 'cxe-resultado';
        void resultadoEl.offsetWidth;               // reinicia la animación

        if (tipo === 'ok') {
            marco.classList.add('is-ok');
            resultadoEl.classList.add('is-ok');
            resultadoEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
            listo('listo', texto);
        } else if (tipo === 'error') {
            marco.classList.add('is-error');
            resultadoEl.classList.add('is-error');
            resultadoEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/></svg>';
            listo('error', texto);
        } else if (tipo === 'varios') {
            listo('varios', texto);
        } else if (tipo === 'conexion') {
            listo('conexion', texto);
        }

        // Pasado un momento vuelve a "Listo para escanear".
        marcaTimer = setTimeout(function () {
            marco.classList.remove('is-ok');
            resultadoEl.className = 'cxe-resultado';
            if (!marco.classList.contains('is-buscando')) listo('listo', 'Listo para escanear');
        }, tipo === 'ok' ? 1600 : 4000);
    }

    function listo(tono, texto) {
        listoEl.dataset.tono = tono;
        listoT.textContent = texto;
    }

    function leerAlmacen() {
        try {
            const guardados = JSON.parse(localStorage.getItem(ALMACEN_KEY) || '[]');
            return Array.isArray(guardados) ? guardados : [];
        } catch (e) { return []; }
    }

    function guardarAlmacen(items) {
        try { localStorage.setItem(ALMACEN_KEY, JSON.stringify(items.slice(0, 20))); } catch (e) { /* sin almacenamiento */ }
    }

    function pintarAlmacen() {
        if (!almacenLista) return;
        const items = leerAlmacen();
        if (almacenVacio) almacenVacio.hidden = items.length > 0;
        if (almacenLimpiar) almacenLimpiar.hidden = items.length === 0;
        almacenLista.querySelectorAll('.cxe-almacen-item').forEach(el => el.remove());

        items.forEach(function (item) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'cxe-almacen-item';
            boton.dataset.cxeAlmacenCodigo = item.codigo || '';
            boton.innerHTML = (item.foto
                ? '<img class="cxe-almacen-foto" src="' + esc(item.foto) + '" alt="">'
                : '<span class="cxe-almacen-foto" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h18v14H3z"/><path d="m3 17 5-5 4 4 3-3 6 6M8 10h.01"/></svg></span>') +
                '<span><span class="cxe-almacen-nombre">' + esc(item.nombre || 'Equipo') + '</span>' +
                '<span class="cxe-almacen-meta"><span class="cxe-almacen-codigo">' + esc(item.codigo || '') + '</span>' +
                (item.almacen ? '<span>' + esc(item.almacen) + '</span>' : '') + '</span></span>' +
                '<span class="cxe-almacen-estado">' + esc(item.estado || 'Consultado') + '</span>' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
            almacenLista.appendChild(boton);
        });
    }

    function registrarEnAlmacen(resumen) {
        if (!resumen || !almacenLista) return;
        const codigo = String(resumen.etiqueta || resumen.codigo_escaneado || resumen.referencia || '').trim().toUpperCase();
        if (!codigo) return;
        const ubicacion = (resumen.ubicaciones || []).find(u => u.almacen)?.almacen || '';
        const items = leerAlmacen().filter(item => item.codigo !== codigo);
        items.unshift({
            codigo,
            nombre: resumen.nombre,
            foto: resumen.foto || '',
            almacen: ubicacion,
            estado: resumen.estado_fisico?.texto || 'Consultado',
        });
        guardarAlmacen(items);
        pintarAlmacen();
    }

    function decir(texto) {
        anuncio.textContent = '';
        setTimeout(() => { anuncio.textContent = texto; }, 30);
    }

    /* =====================================================================
       Ficha
       ===================================================================== */
    function mostrar(ficha) {
        detenerTodo();
        cancelarCierre();
        const yaAbierta = modal.open;
        actual = ficha;
        registrarEnAlmacen(ficha.resumen);
        retenida = false;
        pendiente = null;
        limpiarAviso(avisoModal);
        btnMantener.setAttribute('aria-pressed', 'false');

        resumenEl.innerHTML = htmlResumen(ficha.resumen);
        pintarHistorial(ficha.historial, true);

        if (!yaAbierta) {
            modal.showModal();
            // El foco va a un botón (no al historial): la pistola sigue
            // funcionando y Escape cierra.
            btnCerrar.focus({ preventScroll: true });
        } else if (!reducido.matches) {
            // Otro producto en la misma ficha: cambio breve.
            [resumenEl, linea].forEach(function (el) {
                el.classList.remove('is-cambia'); void el.offsetWidth; el.classList.add('is-cambia');
            });
        }

        // La cuenta arranca cuando el resumen ya está pintado: leer una
        // medida obliga al navegador a acomodarlo antes de seguir.
        cuentaEl.textContent = 'Se cierra en ' + SEGUNDOS + ' s';
        barra.classList.remove('is-quieta');
        barraV.style.transform = 'scaleX(1)';
        void resumenEl.offsetHeight;
        setTimeout(function () {
            if (actual !== ficha || retenida) return;
            arrancarCuenta();
            programarDesplazamiento();
        }, 0);
    }

    /*
     * Cierre: salida breve y luego se cierra de verdad. La limpieza (y el
     * foco de regreso al lector) se hace aquí mismo: no se depende del
     * evento "close", que algunos navegadores entregan tarde o no entregan.
     */
    function cerrar() {
        if (!modal.open || cerrando) return;
        detenerTodo();
        if (reducido.matches || document.hidden) { cerrarYa(); return; }
        modal.classList.add('is-saliendo');
        cerrando = setTimeout(cerrarYa, 170);
    }

    function cerrarYa() {
        clearTimeout(cerrando);
        cerrando = null;
        modal.classList.remove('is-saliendo');
        if (modal.open) modal.close();
        alCerrar();
    }

    function alCerrar() {
        detenerTodo();
        actual = null;
        pendiente = null;
        retenida = false;
        limpiarAviso(avisoModal);
        listoParaOtra();
    }

    function cancelarCierre() {
        if (!cerrando) return;
        clearTimeout(cerrando);
        cerrando = null;
        modal.classList.remove('is-saliendo');
    }

    modal.addEventListener('cancel', function (e) {
        // Escape: misma salida que "Cerrar".
        e.preventDefault();
        cerrar();
    });

    // Por si algo más la cierra (otro script, el navegador): misma limpieza.
    modal.addEventListener('close', function () {
        if (actual) alCerrar();
    });

    // Clic en el fondo (fuera de la hoja) también cierra.
    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrar();
    });

    btnCerrar.addEventListener('click', cerrar);
    btnMantener.addEventListener('click', retener);
    btnUltimo.addEventListener('click', function () {
        retener();
        irAlFinal(!reducido.matches);
    });

    function listoParaOtra() {
        // Con la ficha abierta lo de atrás está inerte: la pistola se sigue
        // escuchando en el documento.
        if (modal.open) return;
        input.focus({ preventScroll: true });
        input.select();
    }

    /* =====================================================================
       Cuenta regresiva (tiempo real, no cuadros de animación)
       ===================================================================== */
    function arrancarCuenta() {
        fin = performance.now() + SEGUNDOS * 1000;
        const paso = function () {
            const queda = Math.max(0, fin - performance.now());
            barraV.style.transform = 'scaleX(' + (queda / (SEGUNDOS * 1000)) + ')';
            cuentaEl.textContent = 'Se cierra en ' + Math.ceil(queda / 1000) + ' s';
            if (queda <= 0) cerrar();
        };
        paso();
        reloj = setInterval(paso, 100);
    }

    function retener() {
        if (!modal.open || retenida) return;
        retenida = true;
        detenerTodo();
        btnMantener.setAttribute('aria-pressed', 'true');
        barra.classList.add('is-quieta');
        barraV.style.transform = 'scaleX(1)';
        cuentaEl.textContent = 'Abierta hasta que la cierres';
    }

    function detenerTodo() {
        clearInterval(reloj);
        reloj = null;
        clearInterval(scrollAnim);
        scrollAnim = null;
        clearTimeout(scrollProgramado);
        scrollProgramado = null;
    }

    /* =====================================================================
       Desplazamiento del historial
       ===================================================================== */
    function programarDesplazamiento() {
        linea.scrollTop = 0;
        enfocarVisible();
        const max = linea.scrollHeight - linea.clientHeight;
        if (max <= 4) return;

        if (reducido.matches) { irAlFinal(false); return; }

        // Se deja ver el inicio y se baja suave hasta el final antes del
        // cierre. Va por tiempo, así llega aunque el navegador pinte lento.
        scrollProgramado = setTimeout(function () {
            const desde = linea.scrollTop;
            const total = linea.scrollHeight - linea.clientHeight - desde;
            const dur = Math.min(7000, Math.max(1400, total * 9));
            const t0 = performance.now();
            scrollAnim = setInterval(function () {
                const p = Math.min(1, (performance.now() - t0) / dur);
                const s = p < .5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
                linea.scrollTop = desde + total * s;
                if (p >= 1) { clearInterval(scrollAnim); scrollAnim = null; }
            }, 16);
        }, 900);
    }

    function irAlFinal(suave) {
        clearInterval(scrollAnim);
        scrollAnim = null;
        clearTimeout(scrollProgramado);
        linea.scrollTo({ top: linea.scrollHeight, behavior: suave ? 'smooth' : 'auto' });
        if (suave) scrollProgramado = setTimeout(function () { linea.scrollTop = linea.scrollHeight; enfocarVisible(); }, 700);
    }

    // Resalta el evento que va entrando a la vista.
    let focoActual = null;
    function enfocarVisible() {
        const caja = linea.getBoundingClientRect();
        const marca = caja.top + caja.height * 0.62;
        let elegido = null;
        for (const ev of linea.querySelectorAll('.cxe-ev')) {
            const r = ev.getBoundingClientRect();
            if (r.top <= marca) elegido = ev; else break;
        }
        if (elegido !== focoActual) {
            focoActual?.classList.remove('is-foco');
            elegido?.classList.add('is-foco');
            focoActual = elegido;
        }
    }
    linea.addEventListener('scroll', enfocarVisible, { passive: true });

    // Lo que hace la persona (no el código) detiene el cierre y el
    // desplazamiento.
    ['wheel', 'touchstart', 'pointerdown'].forEach(function (ev) {
        linea.addEventListener(ev, retener, { passive: true });
    });
    linea.addEventListener('keydown', function (e) {
        if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(e.key)) retener();
    });
    linea.addEventListener('focusin', function (e) {
        // Llegar con Tab a una liga del historial también es leerlo.
        if (e.target !== linea) retener();
    });

    /* =====================================================================
       Historial
       ===================================================================== */
    function pintarHistorial(h, nuevo) {
        if (nuevo) { cargados = 0; focoActual = null; }

        if (!h || !h.eventos || (nuevo && h.eventos.length === 0)) {
            linea.innerHTML = '<p class="cxe-sin-dato">Sin historial disponible.</p>';
            historiaN.textContent = '';
            return;
        }

        if (nuevo) linea.dataset.ultimo = h.eventos[h.eventos.length - 1]?.clave || '';

        const html = h.eventos.map(ev => htmlEvento(ev, ev.clave === linea.dataset.ultimo)).join('');
        const boton = h.hay_anteriores
            ? '<div class="cxe-anteriores" data-cxe-anteriores>' +
              '<button type="button" class="cxe-btn cxe-btn--chico" data-cxe-mas>Cargar movimientos anteriores</button></div>'
            : '';

        if (nuevo) {
            linea.innerHTML = boton + html;
        } else {
            // Se insertan arriba sin mover lo que se está leyendo: se mide
            // antes de tocar nada (incluido el botón que se reemplaza).
            const antes = linea.scrollHeight;
            const pos = linea.scrollTop;
            linea.querySelector('[data-cxe-anteriores]')?.remove();
            linea.insertAdjacentHTML('afterbegin', boton + html);
            linea.scrollTop = pos + (linea.scrollHeight - antes);
        }

        cargados += h.eventos.length;
        historiaN.textContent = cargados < h.total
            ? 'Últimos ' + cargados + ' de ' + h.total + ' movimientos'
            : h.total + (h.total === 1 ? ' movimiento' : ' movimientos');
    }

    linea.addEventListener('click', function (e) {
        const b = e.target.closest('[data-cxe-mas]');
        if (b) cargarAnteriores(b);
    });

    async function cargarAnteriores(boton) {
        retener();
        const ficha = actual;
        if (!ficha) return;
        boton.disabled = true;
        boton.textContent = 'Cargando…';
        try {
            const r = await fetch(URL_HISTORIAL + '?' + new URLSearchParams({ tipo: ficha.tipo, id: ficha.id, cargados }), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store',
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const h = await r.json();
            if (actual !== ficha) return;
            pintarHistorial(h, false);
        } catch (e) {
            if (actual !== ficha) return;
            // El resumen se queda; solo esta sección ofrece reintentar.
            const caja = boton.closest('[data-cxe-anteriores]');
            caja.innerHTML = '<span>No se pudieron cargar los movimientos anteriores.</span>' +
                '<button type="button" class="cxe-btn cxe-btn--chico" data-cxe-mas>Reintentar</button>';
        }
    }

    /* =====================================================================
       Avisos
       ===================================================================== */
    const ICO = {
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>',
        varios: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
        conexion: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 2 20 20"/><path d="M8.5 16.5a5 5 0 0 1 7 0M2 8.8a15 15 0 0 1 4.2-2.6M10.7 5.1A15 15 0 0 1 22 8.8M5 12.9a10 10 0 0 1 5.2-2.7M16.8 11.6a10 10 0 0 1 2.2 1.3M12 20h.01"/></svg>',
        pendiente: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    };

    function contenedorAviso() {
        return modal.open ? avisoModal : avisoPagina;
    }

    /** html: el mensaje (puede traer <small>); extra: botones u opciones. */
    function aviso(tipo, icono, html, extra) {
        const el = contenedorAviso();
        el.innerHTML = '<div class="cxe-aviso cxe-aviso--' + tipo + '" role="status">' + icono +
            '<span class="cxe-aviso-t">' + html + '</span>' + (extra || '') + '</div>';
        return el.firstElementChild;
    }

    function limpiarAviso(el) { el.innerHTML = ''; }

    /** Falla de red: aviso distinto, sin sonido, la ficha vigente se queda. */
    function errorConexion(codigo, reintentar) {
        marcar('conexion', 'Sin conexión');
        const caja = aviso('conexion', ICO.conexion,
            'No se pudo consultar <b>' + esc(codigo) + '</b><small>Revisa la conexión y vuelve a intentar.</small>',
            '<button type="button" class="cxe-btn cxe-btn--chico" data-cxe-reintentar>Reintentar</button>');
        decir('No se pudo consultar ' + codigo);
        if (!modal.open) input.value = codigo;
        caja.querySelector('[data-cxe-reintentar]').addEventListener('click', function () {
            enVuelo = null;
            reintentar();
        });
        listoParaOtra();
    }

    function opciones(data) {
        // Elegir lleva tiempo: si la ficha está abierta, no se cierra sola.
        if (modal.open) retener();
        const items = data.opciones.map(o =>
            '<li><button type="button" class="cxe-opcion" data-tipo="' + esc(o.tipo) + '" data-id="' + o.id + '">' +
            '<b>' + esc(o.titulo) + '</b><span class="cxe-opcion-por">' + esc(o.por) + '</span>' +
            '<small>' + esc(o.detalle) + '</small></button></li>').join('');

        const caja = aviso('varios', ICO.varios,
            data.opciones.length + ' coincidencias para <b>' + esc(data.codigo) + '</b><small>Elige cuál consultar.</small>',
            '<ul class="cxe-opciones">' + items + '</ul>');

        caja.querySelectorAll('.cxe-opcion').forEach(function (b) {
            b.addEventListener('click', function () {
                if (b.disabled) return;
                caja.querySelectorAll('.cxe-opcion').forEach(x => { x.disabled = true; });
                elegir(b.dataset.tipo, b.dataset.id, data.codigo);
            });
        });
        caja.querySelector('.cxe-opcion')?.focus({ preventScroll: true });
    }

    function avisoPendiente() {
        const r = pendiente.resumen;
        const caja = aviso('pendiente', ICO.pendiente,
            'Escaneo pendiente: <b>' + esc(pendiente.codigo) + '</b><small>' + esc(r.nombre) + '</small>',
            '<button type="button" class="cxe-btn cxe-btn--chico cxe-btn--primario" data-cxe-ver-pendiente>Ver escaneo pendiente</button>');
        caja.querySelector('[data-cxe-ver-pendiente]').addEventListener('click', function () {
            const f = pendiente;
            pendiente = null;
            if (f) mostrar(f);
        });
    }

    /* =====================================================================
       HTML
       ===================================================================== */
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function enlace(texto, url, clase) {
        const c = clase ? ' class="' + clase + '"' : '';
        return url ? '<a' + c + ' href="' + esc(url) + '">' + esc(texto) + '</a>' : '<span' + c + '>' + esc(texto) + '</span>';
    }

    const SVG_PIN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    const SVG_TAG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg>';

    function htmlResumen(r) {
        const cod = String(r.codigo_escaneado || '').toUpperCase();
        const igual = v => v && String(v).toUpperCase() === cod;

        // Identificadores: SKU (referencia), etiqueta y serie.
        const ids = [];
        if (r.referencia) ids.push('<span class="' + (igual(r.referencia) ? 'is-escaneado' : '') + '">SKU <b>' + esc(r.referencia) + '</b></span>');
        if (r.etiqueta) ids.push('<span class="' + (igual(r.etiqueta) ? 'is-escaneado' : '') + '">' + SVG_TAG + '<b>' + esc(r.etiqueta) + '</b></span>');
        if (r.no_serie) ids.push('<span class="' + (igual(r.no_serie) ? 'is-escaneado' : '') + '">Serie <b>' + esc(r.no_serie) + '</b></span>');
        if (cod && ![r.referencia, r.etiqueta, r.no_serie].some(igual)) ids.push('<span class="is-escaneado">Escaneado <b>' + esc(r.codigo_escaneado) + '</b></span>');

        // Ubicación de un vistazo.
        const ubic = (r.ubicaciones || []).filter(u => u.cantidad > 0 || r.alcance === 'pieza');
        const ubicTexto = ubic.length
            ? ubic.map(u => (u.almacen ? esc(u.almacen) : 'Sin ubicación registrada') + (u.lugar && u.lugar !== 'En almacén' ? ' · ' + esc(u.lugar) : '') +
                (r.alcance === 'producto' ? ' (' + u.cantidad + ')' : '')).join(' · ')
            : 'Sin ubicación registrada';

        const marca = [r.marca, r.modelo].filter(Boolean).join(' ');
        const foto = r.foto
            ? '<img class="cxe-foto" src="' + esc(r.foto) + '" alt="">'
            : '<span class="cxe-foto" role="img" aria-label="Sin foto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 17-5-5-6 6"/></svg></span>';

        const chip = s => s ? '<span class="cxe-chip cxe-chip--' + esc(s.tono) + '">' + esc(s.texto) + '</span>' : '';

        // La cifra principal: disponible para surtir. Cero solo si es cero.
        let kpi;
        if (!r.cantidades) {
            kpi = '<div class="cxe-kpi"><div class="cxe-kpi-e">Disponible para surtir</div>' +
                  '<div class="cxe-sin-dato">Sin piezas registradas</div></div>';
        } else {
            const c = r.cantidades;
            const sec = ['<span>Existencia física <b>' + c.fisica + '</b></span>', '<span>Reservadas <b>' + c.apartada + '</b></span>'];
            if (c.en_proceso) sec.push('<span>En proceso <b>' + c.en_proceso + '</b></span>');
            if (c.fuera) sec.push('<span>En congreso <b>' + c.fuera + '</b></span>');
            if (c.entregada) sec.push('<span>Entregadas <b>' + c.entregada + '</b></span>');
            if (c.baja) sec.push('<span>De baja <b>' + c.baja + '</b></span>');
            kpi = '<div class="cxe-kpi"><div class="cxe-kpi-e">Disponible para surtir</div>' +
                  '<div class="cxe-kpi-v' + (c.disponible ? '' : ' is-cero') + '">' + c.disponible + '<small>' + esc(r.unidad === 'Pieza' ? (c.disponible === 1 ? 'pieza' : 'piezas') : r.unidad) + '</small></div>' +
                  '<div class="cxe-kpi-sec">' + sec.join('') + '</div></div>';
        }

        // Lo demás, a un toque (no estorba mientras se escanea).
        const datos = [];
        const dato = (e, v) => datos.push('<div><dt>' + e + '</dt><dd>' + v + '</dd></div>');
        dato('Alcance', esc(r.alcance_label) + ' · ' + esc(r.origen));
        dato('Unidad', esc(r.unidad) + (r.equivalencia ? ' · ' + esc(r.equivalencia) : ''));
        if (ubic.length > 1 || r.alcance === 'producto') {
            dato('Por ubicación', '<ul>' + ubic.map(u => '<li>' + (u.almacen ? esc(u.almacen) : '<span class="cxe-sin-dato">Sin ubicación registrada</span>') +
                (u.lugar ? ' · ' + esc(u.lugar) : '') + ' · <b>' + u.cantidad + '</b></li>').join('') + '</ul>');
        }
        if (r.condicion) dato('Condición', esc(r.condicion));
        if (r.fabricacion) dato('Fabricación', esc(r.fabricacion));
        if (r.caducidad) dato('Caducidad', esc(r.caducidad));
        if (r.falta) dato('Le falta', esc(r.falta));
        if (r.componentes_por_llegar && r.componentes_por_llegar.length) dato('Componentes por llegar', esc(r.componentes_por_llegar.join(', ')));
        if (r.venta) dato('Venta', r.venta.oculta ? '<span class="cxe-sin-dato">Venta de otro asesor</span>'
            : enlace(r.venta.folio, r.venta.url) + (r.venta.cliente ? ' · ' + esc(r.venta.cliente) : ''));
        if (r.salida) dato('Orden de salida', enlace(r.salida.folio, r.salida.url) + ' · ' + esc(r.salida.estado));
        if (r.entrada) dato('Entrada', enlace(r.entrada.folio, r.entrada.url));
        if (r.alcance === 'producto' && typeof r.stock_oficial === 'number') dato('Stock del producto', r.stock_oficial + (r.producto_url ? ' · ' + enlace('Ver producto', r.producto_url) : ''));

        return '<div class="cxe-res">' + foto +
            '<div style="min-width:0;">' +
                '<h2 id="cxe-titulo">' + esc(r.nombre) + '</h2>' +
                '<div class="cxe-sub">' + (marca ? '<span>' + esc(marca) + '</span>' : '') + ids.join('') + '</div>' +
                '<div class="cxe-sub"><span>' + SVG_PIN + ubicTexto + '</span></div>' +
                '<div class="cxe-estados" aria-label="Estado actual">' + chip(r.estado_comercial) + chip(r.estado_embarque) + chip(r.estado_fisico) + '</div>' +
            '</div>' + kpi + '</div>' +
            '<details class="cxe-mas"><summary><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m9 18 6-6-6-6"/></svg>Más datos</summary>' +
            '<dl class="cxe-datos">' + datos.join('') + '</dl></details>';
    }

    const ICONOS = {
        recibido: '<path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/>',
        proceso: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
        inspeccion: '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        traslado: '<path d="M5 12h14M13 6l6 6-6 6"/>',
        reserva: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        picking: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        empaque: '<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
        venta: '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        salida: '<rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        entregado: '<path d="M20 6 9 17l-5-5"/>',
        devuelto: '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-4"/>',
        cancelado: '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
        ajuste: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    };

    function htmlEvento(ev, ultimo) {
        const meta = [];
        if (ev.origen || ev.destino) meta.push(esc(ev.origen || '—') + ' → ' + esc(ev.destino || '—'));
        if (ev.folio) meta.push(enlace(ev.folio, ev.url, 'cxe-ev-folio'));
        if (ev.responsable) meta.push(esc(ev.responsable));
        if (ev.detalle) meta.push(esc(ev.detalle));

        return '<article class="cxe-ev' + (ultimo ? ' cxe-ev--ultimo' : '') + '" data-icono="' + esc(ev.icono) + '">' +
            '<span class="cxe-ev-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            (ICONOS[ev.icono] || ICONOS.ajuste) + '</svg></span>' +
            '<div class="cxe-ev-t"><div class="cxe-ev-cuando"><time datetime="' + esc(ev.iso) + '">' + esc(ev.fecha) + ' · ' + esc(ev.hora) + '</time></div>' +
            '<div class="cxe-ev-accion">' + esc(ev.accion) +
            (ev.cantidad != null ? ' <span class="cxe-ev-cant">· ' + ev.cantidad + (ev.cantidad === 1 ? ' pieza' : ' piezas') + '</span>' : '') +
            (ultimo ? '<span class="cxe-ev-ultimo-tag">Último</span>' : '') + '</div>' +
            (meta.length ? '<div class="cxe-ev-meta">' + meta.map(m => '<span>' + m + '</span>').join('') + '</div>' : '') +
            '</div></article>';
    }

    // Foto que el navegador no puede abrir (p. ej. HEIC del iPhone en
    // Chrome): en vez de la imagen rota, el icono de "sin foto".
    resumenEl.addEventListener('error', function (e) {
        const img = e.target;
        if (!(img instanceof HTMLImageElement) || !img.classList.contains('cxe-foto')) return;
        const sin = document.createElement('span');
        sin.className = 'cxe-foto';
        sin.setAttribute('role', 'img');
        sin.setAttribute('aria-label', 'Foto en un formato que este navegador no puede mostrar');
        sin.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 17-5-5-6 6"/></svg>';
        img.replaceWith(sin);
    }, true);

    almacenLista?.addEventListener('click', function (e) {
        const boton = e.target.closest('[data-cxe-almacen-codigo]');
        if (!boton || !boton.dataset.cxeAlmacenCodigo) return;
        input.value = boton.dataset.cxeAlmacenCodigo;
        consultar(boton.dataset.cxeAlmacenCodigo);
    });

    almacenLimpiar?.addEventListener('click', function () {
        try { localStorage.removeItem(ALMACEN_KEY); } catch (e) { /* sin almacenamiento */ }
        pintarAlmacen();
    });

    pintarAlmacen();

    // Al entrar, el lector queda listo.
    listoParaOtra();
})();
