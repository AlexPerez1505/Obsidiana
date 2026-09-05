{{--
    Menú de tres puntos de una fila.

    Vive aparte del listado filtrable porque no depende de él: lo usa
    cualquier tabla o tarjeta que necesite acciones por renglón.

    El panel se posiciona con position:fixed calculado en JS, y esa es la
    parte importante: dentro de una tarjeta con overflow un menú absoluto
    se recorta, y con una sola fila ni siquiera se alcanza a ver.

    Uso:
        <div class="row-menu" data-row-menu>
            <button class="row-menu-btn" data-row-menu-toggle aria-expanded="false">…</button>
            <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                <a href="…" role="menuitem">…</a>
                <button class="es-danger" …>…</button>
            </div>
        </div>
--}}

@once
    <style>
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
    </style>
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
