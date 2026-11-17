{{--
    Componentes de una pieza: lo que trae dentro (procesador, fuente, pigtail,
    interfaz…), cada uno con su serie y sus fotos. Sirve para un set que llega
    completo y para partes que llegan después: se dejan "por llegar" y se
    reciben cuando llegan.

    Uso: incluir este parcial una vez y poner en cada pieza un botón
        <button type="button" data-componentes="{{ $pieza->id }}" data-pieza="MB-000123">…</button>
    La ventana se crea en <body> (fuera del contenido que refresca el tiempo real).
--}}
@include('structure.gestion_Inventario._subir_fotos')

@once
    @push('scripts')
        <style>
            .cmp-btn { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border:1px solid var(--border);
                       border-radius:999px; background:var(--surface); color:var(--text); font-size:12.5px; font-weight:600;
                       cursor:pointer; white-space:nowrap; }
            .cmp-btn:hover { border-color:var(--primary); color:var(--primary); }
            .cmp-btn svg { width:14px; height:14px; flex:0 0 auto; }
            .cmp-btn .cmp-n { color:var(--muted); font-weight:500; }
            .cmp-btn .cmp-falta { padding:1px 7px; border-radius:999px; background:rgba(245,158,11,.16); color:#b45309; font-size:11px; font-weight:700; }

            .cmp-dlg { padding:0; border:0; background:transparent; max-width:none; max-height:none; overflow:visible; }
            .cmp-dlg [hidden], .cmp-btn [hidden] { display:none !important; }
            .cmp-dlg::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }
            .cmp-box { width:min(720px, calc(100vw - 24px)); max-height:calc(100vh - 32px); display:flex; flex-direction:column;
                       background:var(--surface); color:var(--text); border:1px solid var(--border); border-radius:16px;
                       box-shadow:0 24px 60px rgba(2,6,23,.28); overflow:hidden; }
            .cmp-cab { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid var(--border); }
            .cmp-cab h3 { margin:0; font-size:17px; font-weight:600; letter-spacing:-.01em; }
            .cmp-cab .cmp-pz { flex:1; min-width:0; color:var(--muted); font:500 13px ui-monospace, Consolas, monospace; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .cmp-x { width:32px; height:32px; border:0; border-radius:8px; background:transparent; color:var(--muted); font-size:22px; line-height:1; cursor:pointer; }
            .cmp-x:hover { background:var(--surface-2); color:var(--text); }
            .cmp-cuerpo { min-height:0; padding:16px 20px 20px; overflow-x:hidden; overflow-y:auto; }
            .cmp-resumen { display:flex; flex-wrap:wrap; gap:6px 14px; margin:0 0 12px; font-size:13px; color:var(--muted); }
            .cmp-resumen b { color:var(--text); }
            .cmp-resumen .warn b { color:#b45309; }

            .cmp-lista { display:flex; flex-direction:column; gap:6px; }
            .cmp-vacio { padding:18px; border:1px dashed var(--border); border-radius:12px; text-align:center; color:var(--muted); font-size:13px; }
            .cmp-fila { display:grid; grid-template-columns:44px minmax(0,1fr) auto; gap:10px; align-items:center; padding:9px 10px;
                        border:1px solid var(--border); border-radius:12px; background:var(--surface); }
            .cmp-fila.por-llegar { border-style:dashed; background:var(--surface-2); }
            .cmp-fila.editando { border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
            .cmp-foto { width:44px; height:44px; padding:0; border:1px solid var(--border); border-radius:9px; overflow:hidden;
                        background:var(--surface-2); display:flex; align-items:center; justify-content:center; color:var(--muted); cursor:default; position:relative; }
            .cmp-foto[data-ver] { cursor:pointer; }
            .cmp-foto img { width:100%; height:100%; object-fit:cover; }
            .cmp-foto svg { width:18px; height:18px; }
            .cmp-foto .n { position:absolute; right:2px; bottom:2px; padding:0 5px; border-radius:999px; background:rgba(15,23,42,.75); color:#fff; font-size:10px; font-weight:700; }
            .cmp-info { min-width:0; }
            .cmp-nom { display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-weight:650; font-size:14px; }
            .cmp-rama { color:var(--muted); font-weight:400; }
            .cmp-serie { font:500 12.5px ui-monospace, Consolas, monospace; color:var(--text); }
            .cmp-serie.sin { color:var(--muted); font-family:inherit; font-style:italic; }
            .cmp-meta { margin-top:2px; font-size:11.5px; color:var(--muted); overflow-wrap:anywhere; }
            .cmp-acc { display:flex; gap:4px; flex-wrap:wrap; justify-content:flex-end; }
            .cmp-acc button { padding:5px 9px; border:0; border-radius:8px; background:transparent; color:var(--primary); font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap; }
            .cmp-acc button:hover { background:var(--surface-2); }
            .cmp-acc .recibir { background:var(--primary); color:#fff; }
            .cmp-acc .recibir:hover { background:var(--primary); filter:brightness(1.08); }
            .cmp-acc .quitar { color:var(--danger); }

            .cmp-form { margin-top:16px; padding:14px; border:1px solid var(--border); border-radius:14px; background:var(--surface-2); }
            .cmp-form-cab { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
            .cmp-form-cab h4 { margin:0; font-size:14.5px; font-weight:650; }
            .cmp-link { padding:0; border:0; background:none; color:var(--primary); font-size:12.5px; font-weight:600; cursor:pointer; }
            .cmp-rejilla { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px 12px; }
            .cmp-rejilla .ancho { grid-column:1 / -1; }
            .cmp-campo label { display:block; margin-bottom:4px; font-size:12.5px; font-weight:600; }
            .cmp-campo input, .cmp-campo select, .cmp-campo textarea { width:100%; padding:9px 11px; border:1px solid var(--border);
                        border-radius:9px; background:var(--surface); color:var(--text); font:inherit; font-size:14px; }
            .cmp-campo textarea { resize:vertical; min-height:62px; }
            .cmp-campo small { display:block; margin-top:3px; font-size:11.5px; color:var(--muted); }
            .cmp-seg { display:inline-flex; padding:3px; border:1px solid var(--border); border-radius:10px; background:var(--surface); gap:2px; }
            .cmp-seg label { position:relative; margin:0; }
            .cmp-seg input { position:absolute; opacity:0; pointer-events:none; }
            .cmp-seg span { display:block; padding:6px 12px; border-radius:7px; font-size:13px; font-weight:600; color:var(--muted); cursor:pointer; }
            .cmp-seg input:checked + span { background:var(--primary); color:#fff; }
            .cmp-seg input:focus-visible + span { outline:2px solid var(--primary); outline-offset:1px; }
            .cmp-fotos { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
            .cmp-mini { position:relative; width:58px; height:58px; border-radius:9px; overflow:hidden; border:1px solid var(--border); background:var(--surface); }
            .cmp-mini img { width:100%; height:100%; object-fit:cover; }
            .cmp-mini .pct { position:absolute; inset:auto 0 0 0; height:4px; background:var(--primary); }
            .cmp-mini button { position:absolute; top:2px; right:2px; width:20px; height:20px; padding:0; border:0; border-radius:50%;
                        background:rgba(15,23,42,.75); color:#fff; font-size:13px; line-height:20px; cursor:pointer; }
            .cmp-mas { width:58px; height:58px; border:1.5px dashed var(--border); border-radius:9px; background:var(--surface);
                       color:var(--muted); font-size:22px; cursor:pointer; }
            .cmp-mas:hover { border-color:var(--primary); color:var(--primary); }
            .cmp-pie { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
            @media (max-width: 560px) {
                .cmp-rejilla { grid-template-columns:minmax(0,1fr); }
                .cmp-fila { grid-template-columns:40px minmax(0,1fr); }
                .cmp-acc { grid-column:1 / -1; justify-content:flex-start; }
                .cmp-pie .btn { flex:1; }
            }
        </style>

        <script>
        window.ObsComponentes = (function () {
            const URL_LISTA = @json(route('inventory.componentes.index', ['pieza' => '__ID__']));
            const URL_UNO = @json(route('inventory.componentes.update', ['componente' => '__ID__']));
            const URL_FOTO = @json(route('inventory.movimientos.fotoChunk'));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
            const ICONO = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/></svg>';

            let dlg, piezaId, datos = { componentes: [] }, editando = null, fotos = [], quitar = [];
            const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            const aviso = (m, t) => window.showToast ? showToast(m, t || 'ok') : null;

            function crear() {
                if (dlg) return;
                dlg = document.createElement('dialog');
                dlg.className = 'cmp-dlg';
                dlg.innerHTML = `
                    <div class="cmp-box" role="document">
                        <div class="cmp-cab">
                            <h3>Componentes</h3>
                            <span class="cmp-pz" data-pz></span>
                            <button type="button" class="cmp-x" data-cerrar aria-label="Cerrar">×</button>
                        </div>
                        <div class="cmp-cuerpo">
                            <p class="cmp-resumen" data-resumen></p>
                            <div class="cmp-lista" data-lista></div>
                            <form class="cmp-form" data-form hidden novalidate>
                                <div class="cmp-form-cab">
                                    <h4 data-titulo>Agregar componente</h4>
                                    <button type="button" class="cmp-link" data-varios>Agregar varios a la vez</button>
                                </div>
                                <div class="cmp-rejilla">
                                    <div class="cmp-campo" data-uno>
                                        <label for="cmpNombre">Componente</label>
                                        <input id="cmpNombre" name="nombre" list="cmpSugerencias" maxlength="120" autocomplete="off" placeholder="Ej. Procesador, Fuente, Pigtail, Interfaz">
                                        <datalist id="cmpSugerencias"></datalist>
                                    </div>
                                    <div class="cmp-campo ancho" data-lista-campo hidden>
                                        <label for="cmpLista">Componentes (uno por renglón)</label>
                                        <textarea id="cmpLista" name="lista" rows="3" placeholder="Pigtail&#10;Interfaz&#10;Cable de luz"></textarea>
                                        <small>Se dan de alta sin serie; la agregas a cada uno cuando lo tengas enfrente.</small>
                                    </div>
                                    <div class="cmp-campo">
                                        <label for="cmpPadre">Va dentro de</label>
                                        <select id="cmpPadre" name="padre_id"></select>
                                    </div>
                                    <div class="cmp-campo" data-serie>
                                        <label for="cmpSerie">Número de serie</label>
                                        <input id="cmpSerie" name="no_serie" maxlength="120" autocomplete="off" placeholder="Opcional">
                                    </div>
                                    <div class="cmp-campo">
                                        <label>¿Ya llegó?</label>
                                        <div class="cmp-seg" role="radiogroup">
                                            <label><input type="radio" name="estado" value="recibido" checked><span>Ya llegó</span></label>
                                            <label><input type="radio" name="estado" value="por_llegar"><span>Por llegar</span></label>
                                        </div>
                                    </div>
                                    <div class="cmp-campo ancho" data-fotos-campo>
                                        <label>Fotos</label>
                                        <div class="cmp-fotos" data-fotos></div>
                                        <input type="file" accept="${ObsFotos.ACEPTA}" multiple hidden data-archivo>
                                    </div>
                                    <div class="cmp-campo ancho" data-notas-campo>
                                        <label for="cmpNotas">Notas</label>
                                        <textarea id="cmpNotas" name="notas" rows="2" maxlength="2000" placeholder="Opcional: estado, detalle, de dónde viene…"></textarea>
                                    </div>
                                </div>
                                <div class="cmp-pie">
                                    <button type="button" class="btn btn--ghost" data-cancelar>Cancelar</button>
                                    <button type="submit" class="btn" data-guardar>Guardar</button>
                                </div>
                            </form>
                            <div class="cmp-pie" data-agregar-pie hidden>
                                <button type="button" class="btn" data-nuevo>+ Agregar componente</button>
                            </div>
                        </div>
                    </div>`;
                document.body.appendChild(dlg);

                dlg.addEventListener('click', e => {
                    if (e.target === dlg || e.target.closest('[data-cerrar]')) return dlg.close();
                    const b = e.target.closest('button');
                    if (!b) return;
                    const id = Number(b.closest('[data-id]')?.dataset.id);
                    if (b.matches('[data-nuevo]')) abrirForm(null, null);
                    else if (b.matches('[data-cancelar]')) cerrarForm();
                    else if (b.matches('[data-varios]')) modoVarios(dlg.querySelector('[data-lista-campo]').hidden);
                    else if (b.matches('[data-dentro]')) abrirForm(null, id);
                    else if (b.matches('[data-editar]')) abrirForm(id, null);
                    else if (b.matches('[data-recibir]')) abrirForm(id, null, true);
                    else if (b.matches('[data-quitar]')) quitarComp(id);
                    else if (b.matches('[data-ver]')) verFotos(id);
                    else if (b.matches('[data-mas]')) dlg.querySelector('[data-archivo]').click();
                    else if (b.matches('[data-sacar]')) sacarFoto(Number(b.dataset.sacar));
                });
                dlg.querySelector('[data-archivo]').addEventListener('change', e => { subirFotos([...e.target.files]); e.target.value = ''; });
                dlg.querySelector('[data-form]').addEventListener('submit', guardar);
                // Esc con el visor abierto cierra solo el visor.
                dlg.addEventListener('cancel', e => {
                    const ov = dlg.querySelector('.gal-overlay');
                    if (ov && !ov.hidden) { e.preventDefault(); window.ObsGaleria?.cerrar(); }
                });
                dlg.addEventListener('close', () => {
                    const ov = dlg.querySelector('.gal-overlay');
                    if (ov) { window.ObsGaleria?.cerrar(); document.body.appendChild(ov); }
                });
            }

            async function pedir(url, opciones) {
                const r = await fetch(url, Object.assign({ headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf } }, opciones || {}));
                const json = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const primero = json.errors ? Object.values(json.errors).flat()[0] : null;
                    throw new Error(primero || json.message || 'No se pudo completar.');
                }
                return json;
            }

            async function abrir(id, etiqueta) {
                crear();
                piezaId = id;
                datos = { componentes: [] };
                cerrarForm();
                dlg.querySelector('[data-pz]').textContent = etiqueta || '';
                dlg.querySelector('[data-lista]').innerHTML = '<div class="cmp-vacio">Cargando…</div>';
                dlg.querySelector('[data-resumen]').textContent = '';
                dlg.querySelector('[data-agregar-pie]').hidden = true;
                if (!dlg.open) dlg.showModal();
                try { pintar(await pedir(URL_LISTA.replace('__ID__', id))); }
                catch (e) { dlg.querySelector('[data-lista]').innerHTML = `<div class="cmp-vacio">${esc(e.message)}</div>`; }
            }

            function pintar(d) {
                datos = d;
                const lista = dlg.querySelector('[data-lista]');
                const res = dlg.querySelector('[data-resumen]');
                res.innerHTML = d.total
                    ? `<span><b>${d.total}</b> ${d.total === 1 ? 'componente' : 'componentes'}</span>` +
                      (d.por_llegar ? `<span class="warn"><b>${d.por_llegar}</b> por llegar</span>` : '<span>Todo recibido</span>')
                    : '';
                lista.innerHTML = d.componentes.length ? d.componentes.map(fila).join('') :
                    '<div class="cmp-vacio">Esta pieza todavía no tiene componentes.<br>Agrega lo que trae dentro (procesador, fuente, pigtail, interfaz…) o lo que va a llegar después.</div>';
                dlg.querySelector('[data-agregar-pie]').hidden = !d.puede_editar || !dlg.querySelector('[data-form]').hidden;
                dlg.querySelector('#cmpSugerencias').innerHTML = (d.sugerencias || []).map(n => `<option value="${esc(n)}">`).join('');
                actualizarBotones(d);
            }

            function fila(c) {
                const f = c.fotos || [];
                const foto = f.length
                    ? `<button type="button" class="cmp-foto" data-ver title="Ver fotos"><img src="${esc(f[0].url)}" alt="" loading="lazy">${f.length > 1 ? `<span class="n">${f.length}</span>` : ''}</button>`
                    : `<span class="cmp-foto">${ICONO}</span>`;
                const padre = c.padre_id ? datos.componentes.find(x => x.id === c.padre_id) : null;
                const meta = [
                    padre ? 'Dentro de ' + esc(padre.nombre) : null,
                    c.estado === 'recibido' && c.recibido ? 'Recibido ' + esc(c.recibido) : null,
                    c.estado === 'por_llegar' ? 'Registrado ' + esc(c.capturado) : null,
                    c.notas ? esc(c.notas) : null,
                ].filter(Boolean).join(' · ');
                const acc = datos.puede_editar ? `
                    ${c.estado === 'por_llegar' ? '<button type="button" class="recibir" data-recibir>Recibir</button>' : ''}
                    <button type="button" data-editar>Editar</button>
                    <button type="button" data-dentro title="Agregar algo que viene dentro de este">+ Dentro</button>
                    ${c.puede_eliminar ? '<button type="button" class="quitar" data-quitar>Quitar</button>' : ''}` : '';
                return `<div class="cmp-fila ${c.estado === 'por_llegar' ? 'por-llegar' : ''} ${editando === c.id ? 'editando' : ''}" data-id="${c.id}" style="margin-left:${Math.min(c.nivel, 4) * 22}px">
                    ${foto}
                    <div class="cmp-info">
                        <div class="cmp-nom">${c.nivel ? '<span class="cmp-rama">↳</span>' : ''}${esc(c.nombre)}
                            <span class="badge ${c.estado === 'por_llegar' ? 'badge--warn' : 'badge--ok'}">${esc(c.estado_label)}</span></div>
                        <div class="cmp-serie ${c.no_serie ? '' : 'sin'}">${c.no_serie ? 'S/N ' + esc(c.no_serie) : 'Sin número de serie'}</div>
                        ${meta ? `<div class="cmp-meta">${meta}</div>` : ''}
                    </div>
                    <div class="cmp-acc">${acc}</div>
                </div>`;
            }

            function opcionesPadre(actual, excluir) {
                const fuera = new Set();
                if (excluir) {
                    // No puede ir dentro de sí mismo ni de lo que trae dentro.
                    fuera.add(excluir);
                    let cambio = true;
                    while (cambio) {
                        cambio = false;
                        datos.componentes.forEach(c => { if (c.padre_id && fuera.has(c.padre_id) && !fuera.has(c.id)) { fuera.add(c.id); cambio = true; } });
                    }
                }
                return '<option value="">Directo en la pieza</option>' + datos.componentes.filter(c => !fuera.has(c.id))
                    .map(c => `<option value="${c.id}" ${c.id === actual ? 'selected' : ''}>${'— '.repeat(c.nivel)}${esc(c.nombre)}${c.no_serie ? ' · ' + esc(c.no_serie) : ''}</option>`).join('');
            }

            function abrirForm(id, padre, recibir) {
                const form = dlg.querySelector('[data-form]');
                const c = id ? datos.componentes.find(x => x.id === id) : null;
                editando = c ? c.id : null;
                form.reset();
                modoVarios(false);
                dlg.querySelector('[data-varios]').hidden = !!c;
                dlg.querySelector('[data-titulo]').textContent = c ? (recibir ? 'Recibir ' + c.nombre : 'Editar ' + c.nombre)
                    : (padre ? 'Agregar dentro de ' + (datos.componentes.find(x => x.id === padre)?.nombre || '') : 'Agregar componente');
                form.nombre.value = c ? c.nombre : '';
                form.padre_id.innerHTML = opcionesPadre(c ? c.padre_id : padre, c ? c.id : null);
                form.no_serie.value = c?.no_serie || '';
                form.notas.value = c?.notas || '';
                form.estado.value = recibir ? 'recibido' : (c ? c.estado : 'recibido');
                fotos = (c?.fotos || []).map(f => ({ path: f.path, url: f.url, existente: true }));
                quitar = [];
                pintarFotos();
                form.hidden = false;
                dlg.querySelector('[data-agregar-pie]').hidden = true;
                dlg.querySelectorAll('.cmp-fila').forEach(el => el.classList.toggle('editando', Number(el.dataset.id) === editando));
                form.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                setTimeout(() => (recibir ? form.no_serie : form.nombre).focus(), 60);
            }

            function cerrarForm() {
                if (!dlg) return;
                editando = null; fotos = []; quitar = [];
                dlg.querySelector('[data-form]').hidden = true;
                dlg.querySelector('[data-agregar-pie]').hidden = !datos.puede_editar;
                dlg.querySelectorAll('.cmp-fila.editando').forEach(el => el.classList.remove('editando'));
            }

            function modoVarios(si) {
                dlg.querySelector('[data-lista-campo]').hidden = !si;
                ['[data-uno]', '[data-serie]', '[data-fotos-campo]', '[data-notas-campo]'].forEach(s => dlg.querySelector(s).hidden = si);
                dlg.querySelector('[data-varios]').textContent = si ? 'Agregar solo uno' : 'Agregar varios a la vez';
                if (si) dlg.querySelector('#cmpLista').focus();
            }

            function pintarFotos() {
                dlg.querySelector('[data-fotos]').innerHTML = fotos.map((f, i) => `
                    <span class="cmp-mini">${f.url ? `<img src="${esc(f.url)}" alt="">` : ''}
                        ${f.progreso != null && f.progreso < 100 ? `<span class="pct" style="width:${f.progreso}%"></span>` : ''}
                        <button type="button" data-sacar="${i}" aria-label="Quitar foto">×</button></span>`).join('') +
                    '<button type="button" class="cmp-mas" data-mas aria-label="Agregar fotos">+</button>';
            }

            async function subirFotos(archivos) {
                for (const original of archivos) {
                    const f = { progreso: 0, url: null };
                    fotos.push(f); pintarFotos();
                    try {
                        const archivo = await ObsFotos.preparar(original);
                        if (ObsFotos.seVe(archivo.name)) { f.url = URL.createObjectURL(archivo); pintarFotos(); }
                        const json = await ObsFotos.subir(archivo, URL_FOTO, pct => { f.progreso = pct; pintarFotos(); });
                        f.path = json.foto_path; f.progreso = 100; f.url = f.url || json.url;
                    } catch (e) {
                        fotos.splice(fotos.indexOf(f), 1);
                        aviso(e.message || 'No se pudo subir la foto.', 'warn');
                    }
                    pintarFotos();
                }
            }

            function sacarFoto(i) {
                const f = fotos[i];
                if (f?.existente) quitar.push(f.path);
                fotos.splice(i, 1);
                pintarFotos();
            }

            async function guardar(e) {
                e.preventDefault();
                const form = e.target;
                if (fotos.some(f => !f.path)) return aviso('Espera a que terminen de subir las fotos.', 'warn');
                const varios = !dlg.querySelector('[data-lista-campo]').hidden;
                const cuerpo = {
                    nombre: form.nombre.value.trim(),
                    lista: varios ? form.lista.value : null,
                    padre_id: form.padre_id.value || null,
                    no_serie: varios ? null : form.no_serie.value.trim(),
                    estado: form.estado.value,
                    notas: varios ? null : form.notas.value.trim(),
                    fotos: fotos.filter(f => !f.existente).map(f => f.path),
                    quitar_fotos: quitar,
                };
                if (!varios && !cuerpo.nombre) return aviso('Escribe qué componente es.', 'warn');
                if (varios && !cuerpo.lista.trim()) return aviso('Escribe al menos un componente.', 'warn');
                const btn = form.querySelector('[data-guardar]');
                btn.disabled = true;
                try {
                    const json = editando
                        ? await pedir(URL_UNO.replace('__ID__', editando), { method: 'PUT', body: JSON.stringify(cuerpo) })
                        : await pedir(URL_LISTA.replace('__ID__', piezaId), { method: 'POST', body: JSON.stringify(cuerpo) });
                    cerrarForm();
                    pintar(json);
                    aviso(json.message);
                } catch (err) { aviso(err.message, 'warn'); }
                finally { btn.disabled = false; }
            }

            async function quitarComp(id) {
                const c = datos.componentes.find(x => x.id === id);
                const hijos = datos.componentes.filter(x => x.padre_id === id).length;
                const ok = await confirmModal({
                    title: 'Quitar ' + (c?.nombre || 'componente'),
                    message: hijos ? 'También se quitan los ' + hijos + ' componentes que trae dentro. No se puede deshacer.' : 'Se quita de esta pieza con sus fotos. No se puede deshacer.',
                    confirmText: 'Sí, quitar', danger: true,
                });
                if (!ok) return;
                try {
                    const json = await pedir(URL_UNO.replace('__ID__', id), { method: 'DELETE' });
                    if (editando === id) cerrarForm();
                    pintar(json); aviso(json.message);
                } catch (e) { aviso(e.message, 'warn'); }
            }

            function verFotos(id) {
                const c = datos.componentes.find(x => x.id === id);
                if (!c || !window.ObsGaleria) return;
                ObsGaleria.abrir(c.fotos.map(f => ({ tipo: 'foto', url: f.url })), 0);
                // La ventana vive en la capa superior: el visor tiene que ir dentro de ella para verse encima.
                const ov = document.querySelector('.gal-overlay');
                if (ov && ov.parentNode !== dlg) dlg.appendChild(ov);
            }

            function actualizarBotones(d) {
                document.querySelectorAll(`[data-componentes="${piezaId}"]`).forEach(b => {
                    const n = b.querySelector('.cmp-n'), falta = b.querySelector('.cmp-falta');
                    if (n) n.textContent = d.total ? '· ' + d.total : '';
                    if (falta) { falta.textContent = d.por_llegar ? d.por_llegar + ' por llegar' : ''; falta.hidden = !d.por_llegar; }
                });
            }

            document.addEventListener('click', e => {
                const b = e.target.closest('[data-componentes]');
                if (!b) return;
                e.preventDefault();
                abrir(b.dataset.componentes, b.dataset.pieza);
            });

            return { abrir };
        })();
        </script>
    @endpush
@endonce
