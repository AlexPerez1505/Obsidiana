{{--
    Motor de un listado filtrable. Es genérico: no sabe de clientes ni de
    fichas, solo lee los data-* de cada fila.

    Espera en la página:
      #fBuscar              caja de texto            (opcional)
      #fConteo              donde va "Mostrando N …" (opcional)
      #fChips               contenedor de chips      (opcional)
      #fVacio               aviso de "nada coincide"  (opcional)
      [data-view-list] / [data-view-cards]           (opcional)
      .f-row                cada fila, en las dos vistas
      [data-flt]            bloque con [data-flt-toggle], [data-flt-panel] y [data-flt-count]
      #fLimpiar, [data-limpiar-filtros]              (opcional)

    Y en cada fila, los data-* que se quieran filtrar:
      data-buscar   texto ya en minúsculas para la búsqueda libre
      data-fecha    Y-m-d, para el rango
      data-<campo>  cualquier campo declarado en un checkbox data-f="<campo>"
      data-<pref>   "1" o "0", para los checkbox de data-f="pref"

    Parámetros del include:
      $singular, $plural   para el texto del conteo
      $estadoCampo         data-* que comparan los accesos rápidos (por defecto "activo")
      $etiquetas           mapa valor => texto para los chips
--}}

@php
    $cfg = [
        'singular' => $singular ?? 'registro',
        'plural' => $plural ?? 'registros',
        'estadoCampo' => $estadoCampo ?? 'activo',
        'etiquetas' => $etiquetas ?? [],
    ];
@endphp

<script>
(function () {
    var CFG = @json($cfg);

    var buscar = document.getElementById('fBuscar');
    var conteo = document.getElementById('fConteo');
    var chips = document.getElementById('fChips');
    var vacio = document.getElementById('fVacio');
    var lista = document.querySelector('[data-view-list]');
    var tarjetas = document.querySelector('[data-view-cards]');

    var checks = Array.prototype.slice.call(document.querySelectorAll('.flt-panel input[type="checkbox"]'));
    var desde = document.querySelector('[data-f="desde"]');
    var hasta = document.querySelector('[data-f="hasta"]');
    var toggles = Array.prototype.slice.call(document.querySelectorAll('.flt-tgl'));

    function etiqueta(valor) {
        return CFG.etiquetas[valor] || valor;
    }

    function marcados(grupo) {
        return checks.filter(function (c) { return c.dataset.f === grupo && c.checked; })
                     .map(function (c) { return c.value; });
    }

    /** Todos los grupos de checkbox que hay, menos las preferencias. */
    function grupos() {
        var vistos = {};
        checks.forEach(function (c) {
            if (c.dataset.f && c.dataset.f !== 'pref') vistos[c.dataset.f] = true;
        });
        return Object.keys(vistos);
    }

    // Los dos accesos rápidos funcionan como par: si ambos están encendidos
    // (o ambos apagados) no se filtra por estado.
    function estadosActivos() {
        var on = toggles.filter(function (t) { return t.classList.contains('is-on'); })
                        .map(function (t) { return t.dataset.valor; });
        return on.length === 1 ? on : [];
    }

    function filtrar() {
        var texto = buscar ? (buscar.value || '').toLowerCase().trim() : '';
        var prefs = marcados('pref');
        var estados = estadosActivos();
        var fDesde = desde ? desde.value : '';
        var fHasta = hasta ? hasta.value : '';

        var seleccion = {};
        grupos().forEach(function (g) { seleccion[g] = marcados(g); });

        var visibles = 0;

        document.querySelectorAll('.f-row').forEach(function (fila) {
            var d = fila.dataset;
            var ok = true;

            if (texto && (d.buscar || '').indexOf(texto) === -1) ok = false;

            if (ok) {
                for (var g in seleccion) {
                    if (seleccion[g].length && seleccion[g].indexOf(d[g]) === -1) { ok = false; break; }
                }
            }

            // Una preferencia exige que su bandera esté encendida en la fila.
            if (ok) {
                for (var i = 0; i < prefs.length; i++) {
                    if (d[prefs[i]] !== '1') { ok = false; break; }
                }
            }

            if (ok && estados.length && estados.indexOf(d[CFG.estadoCampo]) === -1) ok = false;
            if (ok && fDesde && !(d.fecha && d.fecha >= fDesde)) ok = false;
            if (ok && fHasta && !(d.fecha && d.fecha <= fHasta)) ok = false;

            fila.style.display = ok ? '' : 'none';

            // Cada registro aparece dos veces (lista y tarjetas): se cuenta una.
            if (ok && (!lista || fila.closest('[data-view-list]'))) visibles++;
        });

        if (conteo) {
            conteo.textContent = 'Mostrando ' + visibles + ' ' + (visibles === 1 ? CFG.singular : CFG.plural);
        }

        // Se oculta con una clase, no con 'display': ese lo gobierna el cambio de vista.
        var hayFilas = document.querySelectorAll('.f-row').length > 0;
        var sinResultados = hayFilas && visibles === 0;

        if (vacio) vacio.hidden = !sinResultados;
        if (lista) lista.classList.toggle('f-oculto', sinResultados);
        if (tarjetas) tarjetas.classList.toggle('f-oculto', sinResultados);

        pintarContadores(fDesde, fHasta);
        pintarChips(seleccion, prefs, estados, fDesde, fHasta, texto);
    }

    function pintarContadores(fDesde, fHasta) {
        document.querySelectorAll('[data-flt]').forEach(function (bloque) {
            var badge = bloque.querySelector('[data-flt-count]');
            if (!badge) return;

            var n = bloque.querySelectorAll('.flt-panel input[type="checkbox"]:checked').length;

            if (bloque.querySelector('[data-f="desde"]')) {
                if (fDesde) n++;
                if (fHasta) n++;
            }

            badge.textContent = n;
            badge.hidden = n === 0;
        });
    }

    function chip(texto, alQuitar) {
        if (!chips) return;

        var el = document.createElement('span');
        el.className = 'flt-chip';
        el.appendChild(document.createTextNode(texto));

        var x = document.createElement('button');
        x.type = 'button';
        x.setAttribute('aria-label', 'Quitar filtro ' + texto);
        x.textContent = '×';
        x.addEventListener('click', function () { alQuitar(); filtrar(); });

        el.appendChild(x);
        chips.appendChild(el);
    }

    function quitarCheck(grupo, valor) {
        return function () {
            checks.forEach(function (c) {
                if (c.dataset.f === grupo && c.value === valor) c.checked = false;
            });
        };
    }

    function pintarChips(seleccion, prefs, estados, fDesde, fHasta, texto) {
        if (!chips) return;

        chips.textContent = '';

        if (texto) chip('Búsqueda: ' + texto, function () { buscar.value = ''; });

        for (var g in seleccion) {
            seleccion[g].forEach(function (v) {
                chip(etiqueta(g) + ': ' + v, quitarCheck(g, v));
            });
        }

        prefs.forEach(function (v) { chip(etiqueta(v), quitarCheck('pref', v)); });

        estados.forEach(function (v) {
            chip(etiqueta('estado:' + v), function () {
                toggles.forEach(function (t) { t.classList.add('is-on'); t.setAttribute('aria-pressed', 'true'); });
            });
        });

        if (fDesde) chip('Desde ' + fDesde, function () { desde.value = ''; });
        if (fHasta) chip('Hasta ' + fHasta, function () { hasta.value = ''; });

        chips.hidden = chips.children.length === 0;
    }

    // ---------- Apertura y cierre de los paneles ----------
    function cerrarPaneles(excepto) {
        document.querySelectorAll('[data-flt]').forEach(function (bloque) {
            if (bloque === excepto) return;
            bloque.querySelector('[data-flt-panel]').hidden = true;
            bloque.querySelector('[data-flt-toggle]').setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('[data-flt]').forEach(function (bloque) {
        var boton = bloque.querySelector('[data-flt-toggle]');
        var panel = bloque.querySelector('[data-flt-panel]');

        boton.addEventListener('click', function (e) {
            e.stopPropagation();
            var abrir = panel.hidden;
            cerrarPaneles(bloque);
            panel.hidden = !abrir;
            boton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        });

        panel.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    document.addEventListener('click', function () { cerrarPaneles(null); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarPaneles(null);
    });

    // ---------- Eventos de los controles ----------
    if (buscar) buscar.addEventListener('input', filtrar);
    checks.forEach(function (c) { c.addEventListener('change', filtrar); });
    [desde, hasta].forEach(function (c) { if (c) c.addEventListener('change', filtrar); });

    toggles.forEach(function (t) {
        t.addEventListener('click', function () {
            var on = !t.classList.contains('is-on');
            t.classList.toggle('is-on', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
            filtrar();
        });
    });

    function limpiar() {
        if (buscar) buscar.value = '';
        checks.forEach(function (c) { c.checked = false; });
        if (desde) desde.value = '';
        if (hasta) hasta.value = '';
        toggles.forEach(function (t, i) {
            var on = i === 0; // por omisión se muestran solo los activos
            t.classList.toggle('is-on', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        filtrar();
    }

    document.querySelectorAll('#fLimpiar, [data-limpiar-filtros]').forEach(function (b) {
        b.addEventListener('click', limpiar);
    });

    filtrar();
})();
</script>

{{-- ===================== Menú de tres puntos por fila ===================== --}}
@once
    <script>
    (function () {
        var abierto = null;

        function cerrar() {
            if (!abierto) return;
            abierto.pop.hidden = true;
            abierto.boton.setAttribute('aria-expanded', 'false');
            abierto = null;
        }

        // El popup va con position:fixed, así que se coloca a mano.
        function colocar(boton, pop) {
            var r = boton.getBoundingClientRect();
            var alto = pop.offsetHeight;
            var ancho = pop.offsetWidth;

            var top = r.bottom + 6;
            if (top + alto > window.innerHeight - 8) {
                top = Math.max(8, r.top - alto - 6); // no cabe abajo: se abre hacia arriba
            }

            var left = r.right - ancho;
            if (left < 8) left = 8;
            if (left + ancho > window.innerWidth - 8) left = window.innerWidth - ancho - 8;

            pop.style.top = top + 'px';
            pop.style.left = left + 'px';
        }

        document.querySelectorAll('[data-row-menu]').forEach(function (menu) {
            var boton = menu.querySelector('[data-row-menu-toggle]');
            var pop = menu.querySelector('[data-row-menu-pop]');

            boton.addEventListener('click', function (e) {
                e.stopPropagation();
                var estabaAbierto = abierto && abierto.pop === pop;
                cerrar();
                if (estabaAbierto) return;

                pop.hidden = false;
                colocar(boton, pop);
                boton.setAttribute('aria-expanded', 'true');
                abierto = { boton: boton, pop: pop };
            });

            pop.addEventListener('click', function (e) { e.stopPropagation(); });
        });

        document.addEventListener('click', cerrar);

        // Esos botones frenan la propagación del clic, así que se avisan aparte.
        document.querySelectorAll('[data-flt-toggle], [data-view-switch] button').forEach(function (b) {
            b.addEventListener('click', cerrar);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !abierto) return;
            var boton = abierto.boton;
            cerrar();
            boton.focus();
        });

        // Al filtrar o buscar puede desaparecer la fila del menú abierto.
        document.addEventListener('input', cerrar, true);
        document.addEventListener('change', cerrar, true);

        window.addEventListener('scroll', function () {
            if (abierto) colocar(abierto.boton, abierto.pop);
        }, true);
        window.addEventListener('resize', cerrar);
    })();
    </script>
@endonce
