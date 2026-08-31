@extends('layouts.dashboard')

@section('title', 'Tablero')

@section('content')
    @php
        $idsActivos = collect($activas)->pluck('id')->all();
        $disponibles = array_values(array_diff(array_keys($catalogo), $idsActivos));
    @endphp

    <div class="content-actions">
        <button type="button" class="btn btn--ghost" data-abrir-agregar @disabled(empty($disponibles))>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
            Agregar tarjeta
        </button>
        <button type="button" class="btn" data-editar-toggle aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            <span data-editar-texto>Editar tablero</span>
        </button>
    </div>

    @if (empty($activas))
        <x-ui.card>
            <div class="empty-state">
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                </span>
                <h3>Tu tablero está vacío</h3>
                <p>Agrega las tarjetas que quieras ver.</p>
                <button type="button" class="btn" data-abrir-agregar>Agregar tarjeta</button>
            </div>
        </x-ui.card>
    @else
        <p class="dash-ayuda" data-ayuda hidden>
            Arrastra una tarjeta para moverla, jala su esquina para cambiar ancho y alto, o quítala con la ✕.
        </p>

        <div class="dash-grid" data-grid>
            @foreach ($activas as $widget)
                @php
                    $def = $catalogo[$widget['id']];
                    $lim = $limites[$widget['id']];
                @endphp

                <section class="dash-w"
                         style="--w:{{ $widget['w'] }}; --h:{{ $widget['h'] }};"
                         data-widget="{{ $widget['id'] }}"
                         data-w="{{ $widget['w'] }}" data-h="{{ $widget['h'] }}"
                         data-wmin="{{ $lim['w_min'] }}" data-wmax="{{ $lim['w_max'] }}"
                         data-hmin="{{ $lim['h_min'] }}" data-hmax="{{ $lim['h_max'] }}">

                    <button type="button" class="dash-quitar" data-quitar
                            title="Quitar del tablero" aria-label="Quitar {{ $def['titulo'] }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>

                    <span class="dash-medida" data-medida aria-hidden="true">{{ $widget['w'] }}×{{ $widget['h'] }}</span>

                    @include('dashboard.widgets.' . $widget['id'], [
                        'w' => $datos[$widget['id']] ?? [],
                        'titulo' => $def['titulo'],
                        'ancho' => $widget['w'],
                        'alto' => $widget['h'],
                    ])

                    {{-- Jalar esta esquina cambia ancho y alto a la vez --}}
                    <span class="dash-handle" data-handle title="Arrastra para cambiar el tamaño" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15 15 21M21 9 9 21"/></svg>
                    </span>
                </section>
            @endforeach

            {{-- Marca el hueco a donde va a caer la tarjeta que se arrastra --}}
            <div class="dash-hueco" data-hueco hidden></div>
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.widgets.update') }}" id="dashForm">
        @csrf
        @method('PUT')
        <div class="dash-guardar" data-barra hidden>
            <span class="dash-guardar-txt">Hay cambios sin guardar</span>
            <button type="button" class="btn btn--ghost" data-descartar>Descartar</button>
            <button type="submit" class="btn">Guardar tablero</button>
        </div>
        <div data-campos hidden></div>
    </form>

    {{-- ===================== Modal: agregar tarjeta ===================== --}}
    <dialog class="dash-modal" id="dashAgregar" aria-labelledby="dashAgregarT">
        <div class="dash-modal-box">
            <div class="dash-modal-head">
                <div>
                    <h3 id="dashAgregarT">Agregar tarjeta</h3>
                    <p>El tamaño y la posición se ajustan luego, sobre el tablero.</p>
                </div>
                <button type="button" class="dash-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="dash-modal-body">
                @forelse ($disponibles as $id)
                    @php $def = $catalogo[$id]; @endphp
                    <button type="button" class="dash-add" data-agregar="{{ $id }}"
                            data-w="{{ $def['w'] }}" data-h="{{ $def['h'] }}">
                        <span class="dash-add-txt">
                            <span class="dash-add-name">{{ $def['titulo'] }}</span>
                            <span class="dash-add-desc">{{ $def['descripcion'] }}</span>
                        </span>
                        <span class="dash-add-grupo">{{ $def['grupo'] }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                @empty
                    <p class="dw-vacio">Ya tienes todas las tarjetas en el tablero.</p>
                @endforelse
            </div>

            <div class="dash-modal-foot">
                <button type="button" class="btn btn--ghost" data-restaurar>Restaurar original</button>
                <span class="dash-foot-sep"></span>
                <button type="button" class="btn btn--ghost" data-cerrar>Cerrar</button>
            </div>
        </div>
    </dialog>

    <form method="POST" action="{{ route('dashboard.widgets.reset') }}" id="dashReset" hidden>
        @csrf
        @method('DELETE')
    </form>

    @include('dashboard._estilos')

    <script>
    (function () {
        var form = document.getElementById('dashForm');
        var campos = form.querySelector('[data-campos]');
        var barra = form.querySelector('[data-barra]');
        var grid = document.querySelector('[data-grid]');
        var modal = document.getElementById('dashAgregar');
        var btnEditar = document.querySelector('[data-editar-toggle]');
        var txtEditar = document.querySelector('[data-editar-texto]');

        var editando = false;
        var sucio = false;

        function agregarCampo(id, w, h, i) {
            [['id', id], ['w', w], ['h', h]].forEach(function (par) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'widgets[' + i + '][' + par[0] + ']';
                input.value = par[1];
                campos.appendChild(input);
            });
        }

        function sincronizarCampos() {
            campos.innerHTML = '';

            if (!grid) return;

            grid.querySelectorAll('[data-widget]').forEach(function (t, i) {
                agregarCampo(t.dataset.widget, t.dataset.w, t.dataset.h, i);
            });
        }

        // ---------- Modal de agregar ----------
        document.querySelectorAll('[data-abrir-agregar]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (typeof modal.showModal === 'function') { modal.showModal(); } else { modal.setAttribute('open', ''); }
            });
        });

        modal.querySelectorAll('[data-cerrar]').forEach(function (b) {
            b.addEventListener('click', function () { modal.close(); });
        });

        modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });

        document.querySelectorAll('[data-restaurar]').forEach(function (b) {
            b.addEventListener('click', function () {
                sucio = false;
                document.getElementById('dashReset').submit();
            });
        });

        // El contenido de una tarjeta nueva lo arma el servidor, así que se envía.
        modal.querySelectorAll('[data-agregar]').forEach(function (b) {
            b.addEventListener('click', function () {
                sincronizarCampos();
                agregarCampo(b.dataset.agregar, b.dataset.w, b.dataset.h, campos.children.length / 3);
                // form.submit() no dispara el evento submit; el aviso se apaga aquí.
                sucio = false;
                form.submit();
            });
        });

        if (!grid) return;

        var hueco = grid.querySelector('[data-hueco]');
        var ayuda = document.querySelector('[data-ayuda]');

        // ---------- Modo edición ----------
        function pintarModo() {
            grid.classList.toggle('is-editando', editando);
            btnEditar.setAttribute('aria-pressed', editando ? 'true' : 'false');
            txtEditar.textContent = editando ? 'Terminar edición' : 'Editar tablero';
            if (ayuda) ayuda.hidden = !editando;
        }

        btnEditar.addEventListener('click', function () {
            editando = !editando;
            pintarModo();
        });

        function marcarSucio() {
            sucio = true;
            barra.hidden = false;
        }

        function aplicar(tarjeta, w, h) {
            if (+tarjeta.dataset.w === w && +tarjeta.dataset.h === h) return;

            tarjeta.dataset.w = w;
            tarjeta.dataset.h = h;
            tarjeta.style.setProperty('--w', w);
            tarjeta.style.setProperty('--h', h);

            var medida = tarjeta.querySelector('[data-medida]');
            if (medida) medida.textContent = w + '×' + h;

            marcarSucio();
        }

        function limites(tarjeta) {
            return {
                wmin: +tarjeta.dataset.wmin, wmax: +tarjeta.dataset.wmax,
                hmin: +tarjeta.dataset.hmin, hmax: +tarjeta.dataset.hmax,
            };
        }

        function medidasRejilla() {
            var estilo = getComputedStyle(grid);

            return {
                cols: estilo.gridTemplateColumns.split(' ').length,
                gap: parseFloat(estilo.columnGap) || 0,
                fila: parseFloat(estilo.gridAutoRows) || 60,
                ancho: grid.getBoundingClientRect().width,
            };
        }

        // ---------- Quitar ----------
        grid.addEventListener('click', function (e) {
            if (!editando) return;

            if (e.target.closest('[data-quitar]')) {
                e.preventDefault();
                e.target.closest('[data-widget]').remove();
                marcarSucio();
                return;
            }

            // Editando, los enlaces de dentro no navegan.
            if (e.target.closest('a')) e.preventDefault();
        });

        // ---------- Redimensionar y arrastrar ----------
        var accion = null;

        grid.addEventListener('pointerdown', function (e) {
            if (!editando || e.button > 0) return;

            var tarjeta = e.target.closest('[data-widget]');
            if (!tarjeta) return;

            if (e.target.closest('[data-quitar]')) return;

            var enHandle = !!e.target.closest('[data-handle]');
            var r = tarjeta.getBoundingClientRect();
            var m = medidasRejilla();

            e.preventDefault();
            grid.setPointerCapture(e.pointerId);

            accion = {
                tipo: enHandle ? 'medir' : 'mover',
                tarjeta: tarjeta,
                m: m,
                anchoCol: (m.ancho - m.gap * (m.cols - 1)) / m.cols,
                izq: r.left,
                arriba: r.top,
                // Para mover: cuánto hay que compensar para que la tarjeta no
                // brinque bajo el dedo al empezar a arrastrarla.
                dx: e.clientX - r.left,
                dy: e.clientY - r.top,
                ancho: r.width,
                alto: r.height,
                movido: false,
            };
        });

        grid.addEventListener('pointermove', function (e) {
            if (!accion) return;

            if (accion.tipo === 'medir') {
                var lim = limites(accion.tarjeta);
                var w = +accion.tarjeta.dataset.w;

                // Con menos de cuatro columnas el ancho no se toca: la rejilla
                // ya está comprimida y movería la tarjeta sin sentido.
                if (accion.m.cols >= 4) {
                    w = Math.round((e.clientX - accion.izq + accion.m.gap) / (accion.anchoCol + accion.m.gap));
                    w = Math.max(lim.wmin, Math.min(lim.wmax, w));
                }

                var h = Math.round((e.clientY - accion.arriba + accion.m.gap) / (accion.m.fila + accion.m.gap));
                h = Math.max(lim.hmin, Math.min(lim.hmax, h));

                aplicar(accion.tarjeta, w, h);

                return;
            }

            // ----- Mover -----
            if (!accion.movido) {
                // Se despega hasta que hay intención real de arrastrar.
                if (Math.abs(e.clientX - accion.izq - accion.dx) < 4 && Math.abs(e.clientY - accion.arriba - accion.dy) < 4) return;

                accion.movido = true;
                accion.tarjeta.classList.add('is-volando');
                accion.tarjeta.style.width = accion.ancho + 'px';
                accion.tarjeta.style.height = accion.alto + 'px';

                hueco.style.setProperty('--w', accion.tarjeta.dataset.w);
                hueco.style.setProperty('--h', accion.tarjeta.dataset.h);
                hueco.hidden = false;
                accion.tarjeta.after(hueco);
            }

            accion.tarjeta.style.left = (e.clientX - accion.dx) + 'px';
            accion.tarjeta.style.top = (e.clientY - accion.dy) + 'px';

            // Qué tarjeta está debajo del dedo: la que se arrastra no cuenta,
            // porque tiene pointer-events desactivados.
            var debajo = document.elementFromPoint(e.clientX, e.clientY);
            var destino = debajo && debajo.closest ? debajo.closest('[data-widget]') : null;

            if (!destino || destino === accion.tarjeta) return;

            var r = destino.getBoundingClientRect();
            var despues = (e.clientY - r.top) > r.height / 2 || (e.clientX - r.left) > r.width / 2;

            destino.parentNode.insertBefore(hueco, despues ? destino.nextSibling : destino);
        });

        function soltar(e) {
            if (!accion) return;

            if (accion.tipo === 'mover' && accion.movido) {
                hueco.parentNode.insertBefore(accion.tarjeta, hueco);
                hueco.hidden = true;

                accion.tarjeta.classList.remove('is-volando');
                accion.tarjeta.style.left = accion.tarjeta.style.top = '';
                accion.tarjeta.style.width = accion.tarjeta.style.height = '';

                marcarSucio();
            }

            if (e && e.pointerId !== undefined && grid.hasPointerCapture(e.pointerId)) {
                grid.releasePointerCapture(e.pointerId);
            }

            accion = null;
        }

        grid.addEventListener('pointerup', soltar);
        grid.addEventListener('pointercancel', soltar);

        // ---------- Guardado ----------
        form.addEventListener('submit', function () {
            sincronizarCampos();
            sucio = false;
        });

        form.querySelector('[data-descartar]').addEventListener('click', function () {
            sucio = false;
            window.location.reload();
        });

        window.addEventListener('beforeunload', function (e) {
            if (!sucio) return;
            e.preventDefault();
            e.returnValue = '';
        });

        pintarModo();
    })();
    </script>
@endsection
