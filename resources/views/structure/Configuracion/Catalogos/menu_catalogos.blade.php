@extends('structure.Configuracion.layout')

@section('title', 'Catálogos')
@section('page-title', 'Catálogos')

@section('configuracion_content')
    {{-- Las categorías sueltas se retiraron: clientes, congresos y promociones
         se clasifican con el catálogo de equipo (tipo de equipo). --}}

    @include('structure.Configuracion.Catalogos._catalogo_equipo')

    {{-- ===================== Modales de categoría =====================
         Los formularios apuntan a las mismas rutas de siempre. Si el
         navegador no ejecuta el script, los enlaces siguen abriendo la
         pagina completa, que sigue existiendo. --}}

    <dialog class="cfg-modal" id="modalCatCrear" aria-labelledby="modalCatCrearT">
        <form method="POST" action="{{ route('configuracion.categorias.store') }}" class="cfg-modal-box">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <div class="cfg-modal-head">
                <h3 id="modalCatCrearT">Nueva categoría</h3>
                <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cfg-modal-body">
                <label for="catCrearNombre">Nombre</label>
                <input id="catCrearNombre" type="text" name="name" required maxlength="255" autocomplete="off"
                       placeholder="Ingrese el nombre de la categoría"
                       value="{{ old('_modal') === 'crear' ? old('name') : '' }}">
                @if (old('_modal') === 'crear')
                    @error('name')<p class="err">{{ $message }}</p>@enderror
                @endif
            </div>

            <div class="cfg-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn">Guardar categoría</button>
            </div>
        </form>
    </dialog>

    <dialog class="cfg-modal" id="modalCatEditar" aria-labelledby="modalCatEditarT">
        <form method="POST" class="cfg-modal-box" data-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="editar">
            {{-- Guarda a que categoria apunta el formulario, para reabrir el modal si falla la validacion. --}}
            <input type="hidden" name="_url" data-url-field>

            <div class="cfg-modal-head">
                <h3 id="modalCatEditarT">Editar categoría</h3>
                <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cfg-modal-body">
                <label for="catEditarNombre">Nombre</label>
                <input id="catEditarNombre" type="text" name="name" required maxlength="255" autocomplete="off"
                       placeholder="Ingrese el nombre de la categoría">
                @if (old('_modal') === 'editar')
                    @error('name')<p class="err">{{ $message }}</p>@enderror
                @endif
            </div>

            <div class="cfg-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn">Guardar cambios</button>
            </div>
        </form>
    </dialog>

    <dialog class="cfg-modal" id="modalCatEliminar" aria-labelledby="modalCatEliminarT">
        <form method="POST" class="cfg-modal-box" data-form>
            @csrf
            @method('DELETE')

            <div class="cfg-modal-head">
                <h3 id="modalCatEliminarT">Eliminar categoría</h3>
                <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="cfg-modal-body">
                <div class="danger-box" style="margin:0;">
                    <div class="cfg-del">
                        <span class="cfg-del-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                        </span>
                        <div>
                            <div class="cfg-del-name" data-nombre>—</div>
                            <div class="cfg-del-note">Esta acción no se puede deshacer.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cfg-modal-foot">
                <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                <button type="submit" class="btn btn--danger">Eliminar categoría</button>
            </div>
        </form>
    </dialog>

    @if ($errors->any() && old('_modal'))
        <span id="cfgReabrir" data-modal="{{ old('_modal') }}" data-url="{{ old('_url') }}" hidden></span>
    @endif

    <style>
        /* ===== Modales ===== */
        .cfg-modal { padding:0; border:0; background:transparent; max-width:none; max-height:none; overflow:visible; }
        .cfg-modal::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }
        .cfg-modal[open] { animation:cfgModalIn .16s ease-out; }
        @keyframes cfgModalIn { from { opacity:0; transform:translateY(6px) scale(.985); } }

        .cfg-modal-box { width:min(460px, calc(100vw - 32px)); background:var(--surface);
                         border:1px solid var(--border); border-radius:14px;
                         box-shadow:0 24px 60px rgba(17,24,39,.22); overflow:hidden; }
        [data-theme="dark"] .cfg-modal-box { box-shadow:0 24px 60px rgba(0,0,0,.55); }

        .cfg-modal-head { display:flex; align-items:center; gap:12px; padding:17px 20px;
                          border-bottom:1px solid var(--border); }
        .cfg-modal-head h3 { margin:0; font-size:17px; font-weight:600; letter-spacing:-.01em; color:var(--text); }
        .cfg-modal-x { margin-left:auto; display:inline-flex; align-items:center; justify-content:center;
                       width:32px; height:32px; padding:0; border:0; border-radius:8px;
                       background:none; color:var(--muted); cursor:pointer;
                       transition:background .15s ease, color .15s ease; }
        .cfg-modal-x svg { width:17px; height:17px; }
        .cfg-modal-x:hover { background:var(--surface-2); color:var(--text); }

        .cfg-modal-body { padding:20px; }
        .cfg-modal-body label { display:block; margin-bottom:7px; color:var(--muted); font-size:13.5px; }
        .cfg-modal-body input[type="text"] { width:100%; padding:10px 12px; border:1px solid var(--border);
                                             border-radius:9px; background:var(--surface); color:var(--text);
                                             font-family:inherit; font-size:14px; outline:none; }
        .cfg-modal-body input[type="text"]:focus { border-color:var(--primary); }

        .cfg-modal-foot { display:flex; justify-content:flex-end; align-items:center; gap:10px;
                          padding:15px 20px; border-top:1px solid var(--border); background:var(--surface-2); }

        /* Selects dentro de los modales, con el mismo trato que los inputs */
        .cfg-modal-body select { width:100%; padding:10px 12px; border:1px solid var(--border);
                                 border-radius:9px; background:var(--surface); color:var(--text);
                                 font-family:inherit; font-size:14px; outline:none; cursor:pointer; }
        .cfg-modal-body select:focus { border-color:var(--primary); }
        .cfg-modal-body select:disabled { color:var(--muted); cursor:not-allowed; }
        .cfg-modal-body .eq-mt { margin-top:16px; }
        .cfg-modal-body .eq-nota { margin:10px 0 0; color:var(--muted); font-size:12.5px; line-height:1.5; }

        /* ===== Catálogo de equipo ===== */
        .eq-card { margin-top:22px; }
        .eq-intro { margin:0 0 18px; color:var(--muted); font-size:13.5px; }
        .eq-filters { display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:16px; }
        .eq-filters label { display:flex; flex:1 1 180px; flex-direction:column; gap:7px; color:var(--text); font-size:13px; }
        .eq-filter-label { display:flex; align-items:center; gap:7px; font-weight:600; }
        .eq-filter-label svg { width:16px; height:16px; color:#9bb8dd; flex:0 0 auto; }
        .eq-filters select { width:100%; padding:10px 36px 10px 13px; border:1px solid color-mix(in srgb, var(--primary) 22%, var(--border)); border-radius:10px;
                             background:var(--surface); color:var(--text); font:inherit; outline:none; cursor:pointer; transition:border-color .18s, box-shadow .18s; }
        .eq-filters select:hover { border-color:var(--muted); }
        .eq-filters select:focus { border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb, var(--primary) 16%, transparent); }
        .eq-filters select:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .eq-search { display:flex; flex-direction:column; gap:6px; margin-bottom:18px; }
        .eq-search label { color:var(--text); font-size:13px; font-weight:500; }
        .eq-search input { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border);
                           border-radius:9px; background:var(--surface); color:var(--text); font:inherit; }
        .eq-search input:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .eq-item[hidden], .eq-vacio[hidden] { display:none; }

        .eq-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; align-items:start; }
        .eq-col { display:flex; flex-direction:column; min-width:0;
                  border:1px solid var(--border); border-radius:12px; background:var(--surface); }

        .eq-col-head { display:flex; align-items:center; gap:10px; padding:14px 16px;
                       border-bottom:1px solid var(--border); }
        .eq-col-head h3 { margin:0; font-size:14.5px; font-weight:600; color:var(--text); }
        .eq-num { margin-left:auto; padding:1px 8px; border-radius:6px; background:var(--surface-2);
                  border:1px solid var(--border); color:var(--muted); font-size:12px; font-weight:500; }

        .eq-list { max-height:320px; overflow-y:auto; padding:6px 8px; }
        .eq-item { display:flex; align-items:center; gap:10px; padding:9px 8px; border-radius:9px; }
        .eq-item:hover { background:var(--surface-2); }
        .eq-item-txt { flex:1; min-width:0; display:flex; flex-direction:column; gap:1px; }
        .eq-item-name { font-size:13.5px; font-weight:500; color:var(--text);
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .eq-item-meta { color:var(--muted); font-size:12px;
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        /* El menú de tres puntos se encoge un poco dentro de estas listas. */
        .eq-item .congress-menu-trigger { width:28px; height:28px; }
        .eq-item .congress-menu-trigger svg { width:16px; height:16px; }

        .eq-vacio { margin:0; padding:22px 10px; text-align:center; color:var(--muted); font-size:13px; }

        .eq-add { display:flex; align-items:center; justify-content:center; gap:7px; width:100%;
                  padding:11px 12px; border:0; border-top:1px solid var(--border);
                  border-radius:0 0 12px 12px; background:none; color:var(--primary);
                  font-family:inherit; font-size:13px; font-weight:600; cursor:pointer;
                  transition:background .15s ease; }
        .eq-add svg { width:15px; height:15px; }
        .eq-add:hover { background:var(--surface-2); }
        .eq-add:disabled { color:var(--muted); cursor:not-allowed; }
        .eq-add:disabled:hover { background:none; }

        @media (max-width:1200px) { .eq-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:680px) { .eq-grid { grid-template-columns:1fr; } }
        @media (prefers-reduced-motion:reduce) { .eq-add { transition:none; } }

        .cfg-del { display:flex; align-items:center; gap:14px; }
        .cfg-del-ico { flex:0 0 auto; display:flex; align-items:center; justify-content:center;
                       width:40px; height:40px; border-radius:11px; background:var(--surface); color:var(--danger); }
        .cfg-del-ico svg { width:18px; height:18px; }
        .cfg-del-name { font-size:15px; font-weight:600; color:var(--text); overflow-wrap:anywhere; }
        .cfg-del-note { margin-top:2px; color:var(--muted); font-size:13px; }

        @media (max-width:640px) {
            .cfg-modal-foot { flex-direction:column-reverse; align-items:stretch; }
            .cfg-modal-foot .btn { width:100%; text-align:center; }
        }
        @media (prefers-reduced-motion:reduce) {
            .cfg-modal[open] { animation:none; }
            .cfg-modal-x { transition:none; }
        }
    </style>

    <script>
        (function () {
            var mCrear = document.getElementById('modalCatCrear');
            var mEditar = document.getElementById('modalCatEditar');
            var mEliminar = document.getElementById('modalCatEliminar');

            function abrir(modal) {
                if (typeof modal.showModal === 'function') { modal.showModal(); }
                else { modal.setAttribute('open', ''); }
            }

            // Cierra el menu de tres puntos que quedo abierto detras del modal.
            function cerrarMenus() {
                document.querySelectorAll('.congress-menu.open').forEach(function (m) {
                    m.classList.remove('open');
                    m.classList.remove('open-up');
                    var t = m.querySelector('.congress-menu-trigger');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
            }

            // Cierre comun a todos los modales de la pantalla.
            document.querySelectorAll('.cfg-modal').forEach(function (modal) {
                modal.querySelectorAll('[data-cerrar]').forEach(function (b) {
                    b.addEventListener('click', function () { modal.close(); });
                });
                // Clic en el fondo oscuro: el evento apunta al propio <dialog>.
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) modal.close();
                });
            });

            // ---- Disparadores genericos del catalogo de equipo ----
            // data-modal-abrir dice que modal, data-url a donde envia el
            // formulario y data-valores con que llenarlo.
            var marcasPorSubtipo = window.EQ_MARCAS_POR_SUBTIPO || {};

            function llenarMarcas(modal, marcaElegida) {
                var selSub = modal.querySelector('[data-cascada-subtipo]');
                var selMarca = modal.querySelector('[data-cascada-marca]');
                if (!selSub || !selMarca) return;

                var lista = marcasPorSubtipo[selSub.value] || [];
                selMarca.innerHTML = '';
                if (modal.id === 'modalModeloCrear') {
                    modal.querySelector('button[type="submit"]').disabled = !lista.length;
                }

                if (!lista.length) {
                    var vacio = document.createElement('option');
                    vacio.value = '';
                    vacio.textContent = selSub.value ? 'Ese subtipo aún no tiene marcas' : 'Primero selecciona un subtipo';
                    selMarca.appendChild(vacio);
                    selMarca.disabled = true;
                    return;
                }

                selMarca.disabled = false;
                lista.forEach(function (m) {
                    var op = document.createElement('option');
                    op.value = m.id;
                    op.textContent = m.name;
                    selMarca.appendChild(op);
                });

                if (marcaElegida) selMarca.value = marcaElegida;
            }

            document.querySelectorAll('[data-cascada-subtipo]').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    llenarMarcas(sel.closest('.cfg-modal'), null);
                });
            });

            document.querySelectorAll('[data-modal-abrir]').forEach(function (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();

                    var modal = document.getElementById(trigger.dataset.modalAbrir);
                    if (!modal) return;

                    cerrarMenus();

                    var form = modal.querySelector('[data-form]');

                    if (trigger.dataset.url) form.action = trigger.dataset.url;

                    var valores = {};
                    try { valores = JSON.parse(trigger.dataset.valores || '{}'); } catch (err) { valores = {}; }

                    // Primero los campos normales; la marca se llena al final,
                    // cuando el subtipo ya quedo elegido.
                    Object.keys(valores).forEach(function (clave) {
                        if (clave === 'brand_id') return;

                        var destinoTexto = modal.querySelector('[data-texto="' + clave + '"]');
                        if (destinoTexto) { destinoTexto.textContent = valores[clave]; return; }

                        // Un checkbox comparte nombre con su hidden "0": se
                        // marca o desmarca en vez de asignarle valor.
                        var campos = form.querySelectorAll('[name="' + clave + '"]');
                        campos.forEach(function (campo) {
                            if (campo.type === 'checkbox') {
                                campo.checked = valores[clave] === true || valores[clave] === 1 || valores[clave] === '1';
                            } else if (campo.type !== 'hidden' || campos.length === 1) {
                                campo.value = valores[clave];
                            }
                        });
                    });

                    llenarMarcas(modal, valores.brand_id || null);

                    abrir(modal);

                    var primero = form.querySelector('input[type="text"], select');
                    if (primero) primero.focus();
                });
            });

            var btnCrear = document.querySelector('[data-cat-crear]');
            if (btnCrear) {
                btnCrear.addEventListener('click', function (e) {
                    e.preventDefault();
                    var input = document.getElementById('catCrearNombre');
                    input.value = '';
                    abrir(mCrear);
                    input.focus();
                });
            }

            document.querySelectorAll('[data-cat-editar]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    cerrarMenus();
                    var form = mEditar.querySelector('[data-form]');
                    form.action = a.dataset.url;
                    form.querySelector('[data-url-field]').value = a.dataset.url;
                    var input = document.getElementById('catEditarNombre');
                    input.value = a.dataset.nombre || '';
                    abrir(mEditar);
                    input.focus();
                    input.select();
                });
            });

            document.querySelectorAll('[data-cat-eliminar]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    cerrarMenus();
                    var form = mEliminar.querySelector('[data-form]');
                    form.action = a.dataset.url;
                    mEliminar.querySelector('[data-nombre]').textContent = a.dataset.nombre || '—';
                    abrir(mEliminar);
                });
            });

            // Si la validacion fallo, se vuelve a abrir el modal donde ocurrio.
            var reabrir = document.getElementById('cfgReabrir');
            if (reabrir) {
                if (reabrir.dataset.modal === 'crear') {
                    abrir(mCrear);
                    document.getElementById('catCrearNombre').focus();
                } else if (reabrir.dataset.modal === 'editar' && reabrir.dataset.url) {
                    var formE = mEditar.querySelector('[data-form]');
                    formE.action = reabrir.dataset.url;
                    formE.querySelector('[data-url-field]').value = reabrir.dataset.url;
                    var i = document.getElementById('catEditarNombre');
                    i.value = @json(old('name'));
                    abrir(mEditar);
                    i.focus();
                }
            }
        })();
    </script>

    <script>
        (function () {
            // ---- Buscador de categorías ----
            var catBtn = document.getElementById('catalogSearchBtn');
            var catWrap = document.getElementById('catalogSearchWrap');
            var catInput = document.getElementById('catalogSearchInput');
            var catList = document.getElementById('categoryList');
            var catNoResult = document.getElementById('catalogNoResult');
            if (catBtn && catWrap && catInput) {
                var catItems = catList ? catList.querySelectorAll('.category-item') : [];
                catBtn.addEventListener('click', function () {
                    var open = catWrap.classList.toggle('open');
                    catBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open) { catInput.focus(); } else { catInput.value = ''; filterCats(''); }
                });
                function filterCats(q) {
                    q = q.trim().toLowerCase();
                    var visible = 0;
                    catItems.forEach(function (it) {
                        var match = it.getAttribute('data-search').indexOf(q) !== -1;
                        it.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    if (catNoResult) catNoResult.style.display = visible === 0 ? 'block' : 'none';
                }
                catInput.addEventListener('input', function () { filterCats(this.value); });
            }

            // ---- Menú de tres puntos (acciones de categoría) ----
            var menus = document.querySelectorAll('.congress-menu');
            function posicionarMenu(menu) {
                var trigger = menu.querySelector('.congress-menu-trigger');
                var dropdown = menu.querySelector('.congress-menu-dropdown');
                var rect = trigger.getBoundingClientRect();
                var altura = dropdown.getBoundingClientRect().height;
                var top = rect.bottom + 4;
                if (top + altura > window.innerHeight && rect.top - altura - 4 >= 0) {
                    top = rect.top - altura - 4;
                }
                menu.style.setProperty('--menu-top', top + 'px');
                menu.style.setProperty('--menu-right', (window.innerWidth - rect.right) + 'px');
            }
            menus.forEach(function (menu) {
                var trigger = menu.querySelector('.congress-menu-trigger');
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    // Cerrar otros menús abiertos
                    menus.forEach(function (m) {
                        if (m !== menu) {
                            m.classList.remove('open');
                            m.classList.remove('open-up');
                            m.querySelector('.congress-menu-trigger').setAttribute('aria-expanded', 'false');
                        }
                    });
                    // Posición fija para menús dentro de listas con scroll propio,
                    // que si no los recortarían.
                    var open = !menu.classList.contains('open');
                    menu.classList.toggle('open', open);
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (open && menu.closest('.eq-list')) {
                        posicionarMenu(menu);
                    } else if (open && menu.closest('.category-list')) {
                        var rect = trigger.getBoundingClientRect();
                        var dropdown = menu.querySelector('.congress-menu-dropdown');
                        var altura = dropdown.getBoundingClientRect().height;
                        var top = rect.bottom + 4;
                        if (top + altura > window.innerHeight && rect.top - altura - 4 >= 0) {
                            top = rect.top - altura - 4;
                        }
                        menu.style.setProperty('--menu-top', top + 'px');
                        menu.style.setProperty('--menu-right', (window.innerWidth - rect.right) + 'px');
                    }
                });
            });
            window.addEventListener('scroll', function () {
                menus.forEach(function (menu) {
                    if (menu.classList.contains('open') && menu.closest('.eq-list')) posicionarMenu(menu);
                });
            }, true);
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function () {
                menus.forEach(function (m) {
                    m.classList.remove('open');
                    m.classList.remove('open-up');
                    m.querySelector('.congress-menu-trigger').setAttribute('aria-expanded', 'false');
                });
            });
            // Evitar que el clic dentro del menú lo cierre inmediatamente
            document.querySelectorAll('.congress-menu-dropdown').forEach(function (dd) {
                dd.addEventListener('click', function (e) { e.stopPropagation(); });
            });
        })();
    </script>
@endsection
