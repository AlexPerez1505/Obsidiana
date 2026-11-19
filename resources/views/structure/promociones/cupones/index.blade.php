@extends('structure.commercial_management.erp')

@section('title', 'Cupones · Promociones')
@section('page-title', 'Marketing y promociones')

@section('erp_content')
<div class="mk" data-no-mayus id="mkCupones">
    @include('structure.promociones._shell', ['tab' => 'cupones'])

    {{-- ===== Barra: búsqueda, estados rápidos, filtros y vista ===== --}}
    <div class="mk-bar">
        <div class="mk-search">
            @include('structure.promociones._ic', ['n' => 'busca'])
            <input type="search" id="cQ" placeholder="Buscar código o nombre" aria-label="Buscar cupones" autocomplete="off">
        </div>
        <div class="mk-chips" role="group" aria-label="Estado" id="cChips"></div>
        <span style="flex:1"></span>
        <button type="button" class="mk-btn sec" id="cBtnFiltros" aria-haspopup="dialog">@include('structure.promociones._ic', ['n' => 'ajustes']) Filtros <span class="mk-pill" id="cNFiltros" hidden></span></button>
        <div class="mk-seg" role="group" aria-label="Vista">
            <button type="button" data-vista="tarjetas" aria-label="Vista de tarjetas" data-tip-mk="Tarjetas"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></button>
            <button type="button" data-vista="tabla" aria-label="Vista de tabla" data-tip-mk="Tabla"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></button>
        </div>
    </div>
    <div class="mk-sub" aria-live="polite"><span class="total" id="cTotal"></span><span id="cAplicados" class="mk-chips"></span></div>

    <div id="cLista"></div>
    <div class="mk-pag" id="cPag"></div>
    <div class="mk-bulk" id="cBulk" hidden>
        <b id="cBulkN"></b>
        <span style="flex:1"></span>
        @can('cupones.gestionar')
            <button type="button" class="mk-btn sec sm" data-lote="pausar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>Pausar</button>
            <button type="button" class="mk-btn sec sm" data-lote="activar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="6 4 20 12 6 20 6 4"/></svg>Activar</button>
        @endcan
        <button type="button" class="mk-btn sec sm" data-lote="copiar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Copiar códigos</button>
        <button type="button" class="mk-btn sec sm" data-lote="limpiar" aria-label="Quitar selección"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>

    {{-- ===== Panel de filtros ===== --}}
    <dialog class="mk-drawer" id="cFiltros" aria-labelledby="cFiltrosT">
        <div class="mk-dh"><h2 id="cFiltrosT">Filtros</h2><button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="mk-db">
            <p class="mk-h4">Tipo</p>
            <div class="mk-opts" style="margin-bottom:14px;">
                @foreach (['' => 'Todos', 'general' => 'General', 'exclusivo' => 'Exclusivo de un cliente', 'molde' => 'Molde de campaña'] as $v => $t)
                    <label class="mk-opt"><input type="radio" name="f_tipo" value="{{ $v }}"><b>{{ $t }}</b></label>
                @endforeach
            </div>
            <p class="mk-h4">Descuento</p>
            <div class="mk-seg" style="margin-bottom:14px;">
                <label><input type="radio" name="f_desc" value="">Todos</label>
                <label><input type="radio" name="f_desc" value="porcentaje">Porcentaje</label>
                <label><input type="radio" name="f_desc" value="monto">Monto fijo</label>
            </div>
            <label class="mk-switch"><span>Solo con usos en ventas</span><input type="checkbox" id="f_usos"></label>
            <label class="mk-switch"><span>Incluir códigos personales de campañas<small>Los que se generan uno por cliente</small></span><input type="checkbox" id="f_personales"></label>
            <div class="mk-f" style="margin-top:14px;">
                <label for="f_orden">Ordenar por</label>
                <div class="mk-r2">
                    <select id="f_orden" class="mk-in">
                        <option value="recientes">Más recientes</option><option value="codigo">Código</option><option value="vence">Vencimiento</option>
                        <option value="usos">Usos</option><option value="descuento">Descuento</option>
                    </select>
                    <select id="f_dir" class="mk-in" aria-label="Dirección"><option value="desc">Descendente</option><option value="asc">Ascendente</option></select>
                </div>
            </div>
            <div class="mk-f"><label for="f_pp">Por página</label><select id="f_pp" class="mk-in"><option>12</option><option>24</option><option>48</option></select></div>
        </div>
        <div class="mk-df">
            <button type="button" class="mk-btn ghost" id="cFLimpiar">Limpiar</button>
            <button type="button" class="mk-btn" id="cFAplicar">Aplicar</button>
        </div>
    </dialog>

    {{-- ===== Panel de detalle ===== --}}
    <dialog class="mk-drawer" id="cDet" aria-labelledby="cDetT"><div id="cDetC" style="display:flex; flex-direction:column; height:100%;"></div></dialog>

    {{-- ===== Asistente de creación / edición ===== --}}
    <dialog class="mk-dlg" id="cWz" aria-labelledby="cWzT">
        <form id="wzForm" novalidate style="display:flex; flex-direction:column; max-height:inherit;">
            <div class="mk-dh">
                <h2 id="cWzT">Nuevo cupón</h2>
                <button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="mk-steps" role="tablist" aria-label="Pasos">
                <button type="button" class="mk-step" data-ir="1"><span class="b">1</span>Descuento</button>
                <button type="button" class="mk-step" data-ir="2"><span class="b">2</span>Participantes</button>
                <button type="button" class="mk-step" data-ir="3"><span class="b">3</span>Vigencia y límites</button>
                <button type="button" class="mk-step" data-ir="4"><span class="b">4</span>Revisión</button>
            </div>
            <div class="mk-wz">
                <div class="mk-wz-main">
                    <div id="wzAviso"></div>

                    {{-- Paso 1 --}}
                    <section class="mk-panel" data-paso="1">
                        <div id="wzPresets">
                            <p class="mk-h4">Empezar con</p>
                            <div class="mk-opts" style="margin-bottom:16px;">
                                <button type="button" class="mk-opt" data-preset="bienvenida"><b>Bienvenida</b><span>10 % · desde $1,000 · tope $300</span></button>
                                <button type="button" class="mk-opt" data-preset="fijo"><b>Monto fijo</b><span>$500 · desde $5,000</span></button>
                                <button type="button" class="mk-opt" data-preset="temporada"><b>Temporada</b><span>15 % · vence fin de mes</span></button>
                                <button type="button" class="mk-opt" data-preset="vip"><b>Código personal</b><span>10 % · uno por cliente</span></button>
                            </div>
                        </div>
                        <div class="mk-f">
                            <span class="mk-lbl">Tipo de descuento</span>
                            <div class="mk-seg"><label><input type="radio" name="descuento_tipo" value="porcentaje">Porcentaje</label><label><input type="radio" name="descuento_tipo" value="monto">Monto fijo</label></div>
                        </div>
                        <div class="mk-r2">
                            <div class="mk-f"><label for="w_valor">Valor</label>
                                <div class="mk-affix" id="w_valor_box"><input id="w_valor" name="descuento_valor" class="mk-in" type="number" step="0.01" min="0" inputmode="decimal" required><span id="w_valor_suf">%</span></div>
                                <span class="mk-err" data-err="descuento_valor"></span></div>
                            <div class="mk-f" id="w_tope_box"><label for="w_tope">Tope <span class="mk-muted" style="font-weight:400;">opcional</span></label>
                                <div class="mk-affix pre"><span>$</span><input id="w_tope" name="tope_descuento" class="mk-in" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Sin tope"></div>
                                <span class="mk-err" data-err="tope_descuento"></span></div>
                        </div>
                        <div class="mk-r2">
                            <div class="mk-f"><label for="w_codigo">Código</label>
                                <div style="display:flex; gap:6px;"><input id="w_codigo" name="codigo" class="mk-in mk-code" maxlength="40" placeholder="Automático" style="text-transform:uppercase;">
                                    <button type="button" class="mk-btn sec" id="w_gen" data-tip-mk="Generar código">Generar</button></div>
                                <span class="mk-err" data-err="codigo"></span></div>
                            <div class="mk-f"><label for="w_nombre">Nombre</label><input id="w_nombre" name="nombre" class="mk-in" maxlength="255" required placeholder="Ej. Bienvenida">
                                <span class="mk-err" data-err="nombre"></span></div>
                        </div>
                    </section>

                    {{-- Paso 2 --}}
                    <section class="mk-panel" data-paso="2" hidden>
                        <p class="mk-h4">¿Quién lo puede usar?</p>
                        <div class="mk-opts" style="margin-bottom:12px;" role="radiogroup">
                            <label class="mk-opt"><input type="radio" name="w_quien" value="general"><b>Cualquier cliente</b><span>Respeta límites y consentimiento</span></label>
                            <label class="mk-opt"><input type="radio" name="w_quien" value="exclusivo"><b>Un cliente</b><span>Solo su cuenta lo puede usar</span></label>
                            <label class="mk-opt"><input type="radio" name="w_quien" value="molde"><b>Código por cliente</b><span>Se genera uno por destinatario de campaña</span></label>
                        </div>
                        <div class="mk-f" id="w_cli_box" hidden>
                            <label for="w_cli">Cliente</label>
                            <div class="mk-ac"><input id="w_cli" class="mk-in" placeholder="Buscar por nombre o teléfono" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="w_cli_l"><div class="mk-ac-list" id="w_cli_l" role="listbox"></div></div>
                            <div class="mk-tags" id="w_cli_tag"></div>
                            <span class="mk-err" data-err="cliente_id"></span>
                        </div>
                        <p class="mk-h4" style="margin-top:6px;">¿En qué productos?</p>
                        <div class="mk-seg" style="margin-bottom:10px;"><label><input type="radio" name="aplica_a" value="todo">Todos</label><label><input type="radio" name="aplica_a" value="seleccion">Elegidos</label></div>
                        <div id="w_alc_box" hidden>
                            <div class="mk-ac"><input id="w_prod" class="mk-in" placeholder="Buscar producto o paquete" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="w_prod_l"><div class="mk-ac-list" id="w_prod_l" role="listbox"></div></div>
                            <div class="mk-tags" id="w_prod_tags"></div>
                            @if ($tiposEquipo->isNotEmpty())
                                <p class="mk-lbl" style="margin:12px 0 6px;">Tipos de equipo</p>
                                <div class="mk-chips" id="w_tipos">
                                    @foreach ($tiposEquipo as $t)<button type="button" class="mk-chip" aria-pressed="false" data-tipo="{{ $t->id }}">{{ $t->name }}</button>@endforeach
                                </div>
                            @endif
                            <span class="mk-err" data-err="alcance"></span>
                        </div>
                    </section>

                    {{-- Paso 3 --}}
                    <section class="mk-panel" data-paso="3" hidden>
                        <div class="mk-r2">
                            <div class="mk-f"><label for="w_ini">Empieza</label><input id="w_ini" name="inicia_en" class="mk-in" type="datetime-local"><span class="mk-err" data-err="inicia_en"></span></div>
                            <div class="mk-f"><label for="w_fin">Vence</label><input id="w_fin" name="vence_en" class="mk-in" type="datetime-local"><span class="mk-err" data-err="vence_en"></span></div>
                        </div>
                        <div class="mk-r3">
                            <div class="mk-f"><label for="w_min">Compra mínima</label><div class="mk-affix pre"><span>$</span><input id="w_min" name="compra_minima" class="mk-in" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0"></div><span class="mk-err" data-err="compra_minima"></span></div>
                            <div class="mk-f"><label for="w_max">Usos totales</label><input id="w_max" name="usos_max" class="mk-in" type="number" min="1" placeholder="Sin límite"><span class="mk-err" data-err="usos_max"></span></div>
                            <div class="mk-f"><label for="w_pc">Usos por cliente</label><input id="w_pc" name="usos_por_cliente" class="mk-in" type="number" min="1" value="1"><span class="mk-err" data-err="usos_por_cliente"></span></div>
                        </div>
                        <label class="mk-switch"><span>Combinable con otros descuentos</span><input type="checkbox" id="w_acum"></label>
                        <label class="mk-switch"><span>Solo primera compra</span><input type="checkbox" id="w_primera"></label>
                        <label class="mk-switch"><span>Activo al guardar</span><input type="checkbox" id="w_activo"></label>
                    </section>

                    {{-- Paso 4 --}}
                    <section class="mk-panel" data-paso="4" hidden>
                        <ul class="mk-rows" id="w_rev"></ul>
                        <div class="mk-f" style="margin-top:12px;"><label for="w_cond">Nota para el cliente <span class="mk-muted" style="font-weight:400;">opcional</span></label>
                            <textarea id="w_cond" name="condiciones" class="mk-in" maxlength="500" style="min-height:60px;"></textarea><span class="mk-err" data-err="condiciones"></span></div>
                    </section>
                </div>
                <aside class="mk-wz-side">
                    <details open><summary>Vista previa</summary>
                        <div>
                            <p class="mk-lbl" style="margin:0 0 8px;">Vista previa</p>
                            <div id="w_prev"></div>
                            <ul class="mk-rows" id="w_prev_l" style="margin-top:10px;"></ul>
                        </div>
                    </details>
                </aside>
            </div>
            <div class="mk-df" style="justify-content:space-between;">
                <button type="button" class="mk-btn ghost" id="w_atras">Atrás</button>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="mk-btn sec" data-mk-cerrar>Cancelar</button>
                    <button type="button" class="mk-btn" id="w_sig">Siguiente</button>
                </div>
            </div>
        </form>
    </dialog>
</div>

<script>
(function () {
    const { api, debounce, copiar, ic, esc, money, toast, confirmar, abrir, cerrar, vacio, error, url } = window.MK;
    const $ = id => document.getElementById(id);
    const R = {
        lista: @json(route('promociones.datos.cupones')),
        det: id => @json(url('promociones/datos/cupones')) + '/' + id,
        store: @json(route('promociones.cupones.store')),
        upd: id => @json(url('promociones/cupones')) + '/' + id,
        estado: id => @json(url('promociones/cupones')) + '/' + id + '/estado',
        dup: id => @json(url('promociones/cupones')) + '/' + id + '/duplicar',
        lote: @json(route('promociones.cupones.lote')),
        sugerir: @json(route('promociones.cupones.sugerir')),
        productos: @json(route('commercial.cotizaciones.productos.buscar')),
        clientes: @json(route('commercial.cotizaciones.clientes.buscar')),
    };
    const PUEDE = @json(auth()->user()->can('cupones.gestionar'));
    const TIPOS = @json($tiposEquipo->pluck('name', 'id'));
    const ESTADOS = [['todos', 'Todos'], ['activo', 'Activos'], ['programado', 'Programados'], ['vencido', 'Vencidos'], ['agotado', 'Agotados'], ['pausado', 'Pausados']];

    // ---------------- Estado de la lista (vive en la URL) ----------------
    const u = url.leer();
    let vistaGuardada = null; try { vistaGuardada = localStorage.getItem('mk-cupones-vista'); } catch (e) {}
    const st = {
        q: u.q || '', estado: u.estado || 'todos', tipo: u.tipo || '', descuento_tipo: u.descuento_tipo || '',
        personales: u.personales === '1', con_usos: u.con_usos === '1', orden: u.orden || 'recientes', dir: u.dir || 'desc',
        page: +u.page || 1, por_pagina: u.por_pagina || '12', vista: u.vista || vistaGuardada || (innerWidth < 640 ? 'tarjetas' : 'tarjetas'),
        periodo: u.periodo || '',
    };
    let datos = { data: [], conteos: {} }, sel = new Set(), carga = 0;

    function guardarUrl(extra = {}) {
        url.poner({ periodo: st.periodo, q: st.q, estado: st.estado !== 'todos' ? st.estado : '', tipo: st.tipo, descuento_tipo: st.descuento_tipo,
            personales: st.personales, con_usos: st.con_usos, orden: st.orden !== 'recientes' ? st.orden : '', dir: st.dir !== 'desc' ? st.dir : '',
            page: st.page > 1 ? st.page : '', por_pagina: st.por_pagina !== '12' ? st.por_pagina : '', vista: st.vista !== 'tarjetas' ? st.vista : '', ...extra });
    }

    // ---------------- Carga ----------------
    function skeleton() {
        $('cLista').innerHTML = st.vista === 'tarjetas'
            ? `<div class="mk-tickets" aria-busy="true">${'<div class="mk-sk-card"></div>'.repeat(6)}</div>`
            : `<div class="mk-card" aria-busy="true">${'<div style="display:flex;gap:12px;padding:12px;border-bottom:1px solid var(--border)"><span class="mk-sk" style="width:18px;height:16px"></span><span class="mk-sk" style="flex:1;height:16px"></span><span class="mk-sk" style="width:80px;height:16px"></span></div>'.repeat(6)}</div>`;
    }

    async function cargar() {
        const yo = ++carga;
        skeleton();
        const p = new URLSearchParams({ estado: st.estado, orden: st.orden, dir: st.dir, page: st.page, por_pagina: st.por_pagina });
        if (st.q) p.set('q', st.q);
        if (st.tipo) p.set('tipo', st.tipo);
        if (st.descuento_tipo) p.set('descuento_tipo', st.descuento_tipo);
        if (st.personales) p.set('personales', 1);
        if (st.con_usos) p.set('con_usos', 1);
        try {
            const d = await api(R.lista + '?' + p);
            if (yo !== carga) return;
            datos = d;
            if (st.page > d.paginas && d.paginas > 0) { st.page = d.paginas; return cargar(); }
            pintar();
        } catch (e) {
            if (yo !== carga) return;
            $('cLista').innerHTML = error(e.message);
            $('cPag').innerHTML = '';
        }
    }

    // ---------------- Pintar ----------------
    function chips() {
        $('cChips').innerHTML = ESTADOS.map(([k, t]) =>
            `<button type="button" class="mk-chip" data-estado="${k}" aria-pressed="${st.estado === k}">${t}<span class="n">${datos.conteos[k] ?? ''}</span></button>`).join('');
    }

    function aplicados() {
        const a = [];
        const tipoL = { general: 'General', exclusivo: 'Exclusivo', molde: 'Molde de campaña' };
        if (st.q) a.push(['q', `“${st.q}”`]);
        if (st.tipo) a.push(['tipo', tipoL[st.tipo]]);
        if (st.descuento_tipo) a.push(['descuento_tipo', st.descuento_tipo === 'porcentaje' ? 'Porcentaje' : 'Monto fijo']);
        if (st.con_usos) a.push(['con_usos', 'Con usos']);
        if (st.personales) a.push(['personales', 'Con códigos personales']);
        $('cAplicados').innerHTML = a.map(([k, t]) => `<span class="mk-chip aplicado">${esc(t)}<button type="button" data-quitar="${k}" aria-label="Quitar filtro ${esc(t)}">${ic('x')}</button></span>`).join('')
            + (a.length > 1 ? `<button type="button" class="mk-link" data-quitar="todo">Quitar todos</button>` : '');
        const n = a.filter(x => x[0] !== 'q').length;
        $('cNFiltros').hidden = !n; $('cNFiltros').textContent = n;
    }

    function menu(c, extraIzq = false) {
        if (!PUEDE) return `<div class="mk-menu"><button type="button" class="mk-ico" data-mk-menu aria-label="Acciones de ${esc(c.codigo)}" aria-haspopup="true" aria-expanded="false">${ic('mas')}</button>
            <div class="mk-menu-panel" role="menu"><button type="button" class="mk-mi" role="menuitem" data-acc="ver" data-id="${c.id}">${ic('ver')}Ver detalle</button>
            <button type="button" class="mk-mi" role="menuitem" data-acc="usos" data-id="${c.id}">${ic('lista')}Consultar usos</button></div></div>`;
        return `<div class="mk-menu"><button type="button" class="mk-ico" data-mk-menu aria-label="Acciones de ${esc(c.codigo)}" aria-haspopup="true" aria-expanded="false">${ic('mas')}</button>
            <div class="mk-menu-panel" role="menu">
              <button type="button" class="mk-mi" role="menuitem" data-acc="ver" data-id="${c.id}">${ic('ver')}Ver detalle</button>
              <button type="button" class="mk-mi" role="menuitem" data-acc="editar" data-id="${c.id}">${ic('editar')}Editar</button>
              <button type="button" class="mk-mi" role="menuitem" data-acc="duplicar" data-id="${c.id}">${ic('duplicar')}Duplicar</button>
              <button type="button" class="mk-mi" role="menuitem" data-acc="estado" data-id="${c.id}">${c.pausado ? ic('play') + 'Activar' : ic('pausa') + 'Pausar'}</button>
              <div class="mk-sep"></div>
              <button type="button" class="mk-mi" role="menuitem" data-acc="usos" data-id="${c.id}">${ic('lista')}Consultar usos</button>
              <button type="button" class="mk-mi" role="menuitem" data-acc="copiar" data-codigo="${esc(c.codigo)}">${ic('copiar')}Copiar código</button>
            </div></div>`;
    }

    const tipoIc = c => c.tipo === 'general' ? ic('globo') : ic('usuario');
    const uso = c => c.usos_max ? `<div><div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--muted);margin-bottom:3px;"><span>Uso</span><span class="mk-num">${c.vigentes}/${c.usos_max}</span></div><div class="mk-bar-uso ${c.pct >= 100 ? 'lleno' : ''}" role="progressbar" aria-valuenow="${c.vigentes}" aria-valuemin="0" aria-valuemax="${c.usos_max}" aria-label="Usos"><i style="width:${c.pct}%"></i></div></div>`
        : `<div class="meta"><span>${ic('ventas')}${c.usados} uso(s)</span></div>`;

    function tarjetas() {
        return `<div class="mk-tickets mk-fade">${datos.data.map(c => `
          <article class="mk-ticket ${['activo', 'programado'].includes(c.estado) ? '' : 'apagado'} ${sel.has(c.id) ? 'sel' : ''}" data-id="${c.id}" tabindex="0" aria-label="Cupón ${esc(c.codigo)}, ${esc(c.descuento)}, ${esc(c.estado_label)}">
            ${PUEDE ? `<input type="checkbox" class="mk-check" data-sel="${c.id}" ${sel.has(c.id) ? 'checked' : ''} aria-label="Seleccionar ${esc(c.codigo)}">` : ''}
            <div class="l"><span class="v mk-num">${esc(c.descuento)}</span><span class="s">${c.tope ? 'máx. ' + esc(c.tope) : (c.descuento_tipo === 'monto' ? 'de descuento' : 'descuento')}</span></div>
            <div class="r">
              <div class="top"><span class="mk-badge ${c.tono}">${esc(c.estado_label)}</span><span class="mk-pill" data-tip-mk="${esc(c.tipo_label)}">${tipoIc(c)}${c.tipo === 'general' ? 'General' : (c.tipo === 'molde' ? 'Molde' : 'Exclusivo')}</span>${menu(c)}</div>
              <div class="cod"><span class="mk-code">${esc(c.codigo)}</span><button type="button" class="mk-ico" style="width:26px;height:26px" data-acc="copiar" data-codigo="${esc(c.codigo)}" aria-label="Copiar ${esc(c.codigo)}" data-tip-mk="Copiar">${ic('copiar')}</button></div>
              <div class="nom">${esc(c.nombre)}${c.cliente ? ' · ' + esc(c.cliente) : ''}</div>
              <div class="meta"><span>${ic('cal')}${esc(c.vence_rel)}</span>${c.minimo ? `<span>Mín. ${esc(c.minimo)}</span>` : ''}</div>
              ${uso(c)}
            </div>
          </article>`).join('')}</div>`;
    }

    function th(clave, t, cls = '') {
        const act = st.orden === clave;
        return `<th class="${cls}" aria-sort="${act ? (st.dir === 'asc' ? 'ascending' : 'descending') : 'none'}"><button type="button" data-orden="${clave}">${t}${act ? ic(st.dir === 'asc' ? 'arriba' : 'abajo') : ''}</button></th>`;
    }

    function tabla() {
        const todos = datos.data.length && datos.data.every(c => sel.has(c.id));
        return `<div class="mk-card mk-fade"><div class="mk-table-wrap"><table class="mk-table">
          <thead><tr>${PUEDE ? `<th class="w-chk"><input type="checkbox" id="cTodos" ${todos ? 'checked' : ''} aria-label="Seleccionar página"></th>` : ''}
            ${th('codigo', 'Código')}${th('descuento', 'Descuento')}<th>Tipo</th>${th('vence', 'Vigencia')}${th('usos', 'Usos', 'num')}<th>Estado</th><th class="num"><span style="position:absolute;width:1px;height:1px;overflow:hidden">Acciones</span></th></tr></thead>
          <tbody>${datos.data.map(c => `<tr data-id="${c.id}" class="${sel.has(c.id) ? 'sel' : ''}" tabindex="0">
            ${PUEDE ? `<td class="w-chk"><input type="checkbox" data-sel="${c.id}" ${sel.has(c.id) ? 'checked' : ''} aria-label="Seleccionar ${esc(c.codigo)}"></td>` : ''}
            <td><span class="mk-code">${esc(c.codigo)}</span><div class="mk-muted" style="font-size:12px;max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(c.nombre)}</div></td>
            <td class="mk-num"><b>${esc(c.descuento)}</b>${c.tope ? `<div class="mk-muted" style="font-size:11.5px">máx. ${esc(c.tope)}</div>` : ''}</td>
            <td><span class="mk-pill">${tipoIc(c)}${esc(c.tipo_label)}</span></td>
            <td class="mk-muted" style="font-size:12.5px;white-space:nowrap">${c.vence ? esc(c.vence) : 'Sin vencimiento'}</td>
            <td class="num mk-num">${c.usos_max ? `${c.vigentes}/${c.usos_max}<div class="mk-bar-uso" style="width:70px;margin-left:auto;margin-top:3px"><i style="width:${c.pct}%"></i></div>` : c.usados}</td>
            <td><span class="mk-badge ${c.tono}">${esc(c.estado_label)}</span></td>
            <td class="num">${menu(c)}</td></tr>`).join('')}</tbody></table></div></div>`;
    }

    function pintar() {
        chips(); aplicados();
        const n = datos.total;
        $('cTotal').textContent = n === 1 ? '1 cupón' : `${n} cupones`;
        document.querySelectorAll('[data-vista]').forEach(b => b.setAttribute('aria-pressed', b.dataset.vista === st.vista));

        if (!datos.data.length) {
            const filtrado = st.q || st.tipo || st.descuento_tipo || st.con_usos || st.estado !== 'todos';
            $('cLista').innerHTML = `<div class="mk-card">${filtrado
                ? vacio({ icono: 'busca', titulo: 'Sin resultados', texto: 'Prueba con otra búsqueda o quita filtros.', boton: 'Quitar filtros', accion: 'limpiar' })
                : vacio({ icono: 'tag', titulo: 'Crea tu primer cupón', boton: PUEDE ? 'Crear cupón' : '', accion: 'nuevo' })}</div>`;
            $('cPag').innerHTML = '';
            bulk();
            return;
        }
        $('cLista').innerHTML = st.vista === 'tabla' ? tabla() : tarjetas();
        $('cPag').innerHTML = datos.paginas > 1 ? `<span>Página ${datos.pagina} de ${datos.paginas}</span><div class="btns">
            <button type="button" class="mk-btn sec sm" data-pag="-1" ${datos.pagina <= 1 ? 'disabled' : ''} aria-label="Página anterior">${ic('atras')}</button>
            <button type="button" class="mk-btn sec sm" data-pag="1" ${datos.pagina >= datos.paginas ? 'disabled' : ''} aria-label="Página siguiente">${ic('flecha')}</button></div>` : '';
        bulk();
        // Volver a la posición después de cerrar un detalle o recargar.
        const y = sessionStorage.getItem('mk-cupones-y');
        if (y) { sessionStorage.removeItem('mk-cupones-y'); scrollTo(0, +y); }
    }

    function bulk() {
        $('cBulk').hidden = sel.size === 0;
        $('cBulkN').textContent = sel.size === 1 ? '1 seleccionado' : `${sel.size} seleccionados`;
    }

    // ---------------- Eventos de la lista ----------------
    $('cQ').value = st.q;
    $('cQ').addEventListener('input', debounce(e => { st.q = e.target.value.trim(); st.page = 1; guardarUrl(); cargar(); }, 300));
    $('cChips').addEventListener('click', e => {
        const b = e.target.closest('[data-estado]'); if (!b) return;
        st.estado = b.dataset.estado; st.page = 1; guardarUrl(); cargar();
    });
    document.querySelectorAll('[data-vista]').forEach(b => b.addEventListener('click', () => {
        st.vista = b.dataset.vista; try { localStorage.setItem('mk-cupones-vista', st.vista); } catch (e) {}
        guardarUrl(); pintar();
    }));
    $('cAplicados').addEventListener('click', e => {
        const b = e.target.closest('[data-quitar]'); if (!b) return;
        const k = b.dataset.quitar;
        if (k === 'todo') { Object.assign(st, { q: '', tipo: '', descuento_tipo: '', con_usos: false, personales: false }); $('cQ').value = ''; }
        else if (k === 'q') { st.q = ''; $('cQ').value = ''; }
        else st[k] = typeof st[k] === 'boolean' ? false : '';
        st.page = 1; guardarUrl(); cargar();
    });

    $('cLista').addEventListener('click', e => {
        if (e.target.closest('.mk-menu') && !e.target.closest('[data-acc]')) return;
        const acc = e.target.closest('[data-acc]');
        if (acc) { e.stopPropagation(); return accion(acc.dataset.acc, +acc.dataset.id, acc); }
        const a = e.target.closest('[data-accion]');
        if (a) { if (a.dataset.accion === 'reintentar') cargar(); if (a.dataset.accion === 'nuevo') abrirWz(); if (a.dataset.accion === 'limpiar') limpiarFiltros(); return; }
        const o = e.target.closest('[data-orden]');
        if (o) { st.dir = st.orden === o.dataset.orden && st.dir === 'desc' ? 'asc' : 'desc'; st.orden = o.dataset.orden; guardarUrl(); cargar(); return; }
        if (e.target.matches('[data-sel], #cTodos')) return;
        const fila = e.target.closest('[data-id]');
        if (fila) verDetalle(+fila.dataset.id, fila);
    });
    $('cLista').addEventListener('change', e => {
        if (e.target.id === 'cTodos') { datos.data.forEach(c => e.target.checked ? sel.add(c.id) : sel.delete(c.id)); pintar(); return; }
        const s = e.target.dataset.sel; if (!s) return;
        e.target.checked ? sel.add(+s) : sel.delete(+s);
        e.target.closest('[data-id]')?.classList.toggle('sel', e.target.checked);
        bulk();
    });
    $('cLista').addEventListener('keydown', e => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-id]')) { e.preventDefault(); verDetalle(+e.target.dataset.id, e.target); }
    });
    $('cPag').addEventListener('click', e => {
        const b = e.target.closest('[data-pag]'); if (!b) return;
        st.page += +b.dataset.pag; guardarUrl(); cargar(); $('mkCupones').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    $('cLista').addEventListener('click', e => { const a = e.target.closest('.mk-empty [data-accion]'); });
    document.addEventListener('click', e => { const a = e.target.closest('[data-accion="reintentar"]'); if (a && a.closest('#cDet')) verDetalle(detId); });

    // Lote
    $('cBulk').addEventListener('click', async e => {
        const b = e.target.closest('[data-lote]'); if (!b) return;
        const a = b.dataset.lote;
        if (a === 'limpiar') { sel.clear(); pintar(); return; }
        if (a === 'copiar') {
            const cods = datos.data.filter(c => sel.has(c.id)).map(c => c.codigo);
            try { await navigator.clipboard.writeText(cods.join('\n')); toast(`${cods.length} código(s) copiados.`); } catch (er) { toast('No se pudo copiar.', 'warn'); }
            return;
        }
        if (!(await confirmar({ title: a === 'pausar' ? 'Pausar cupones' : 'Activar cupones',
            message: `${a === 'pausar' ? 'Pausar' : 'Activar'} ${sel.size} cupón(es). Las reservas y ventas ya hechas se respetan.`, confirmText: a === 'pausar' ? 'Pausar' : 'Activar', danger: false }))) return;
        b.disabled = true;
        try { const r = await api(R.lote, { method: 'POST', body: { ids: [...sel], accion: a } }); toast(r.mensaje); sel.clear(); cargar(); }
        catch (er) { toast(er.message, 'warn'); }
        b.disabled = false;
    });

    // ---------------- Acciones ----------------
    async function accion(a, id, el) {
        if (a === 'copiar') return copiar(el.dataset.codigo);
        if (a === 'ver') return verDetalle(id, el);
        if (a === 'usos') return verDetalle(id, el, 'usos');
        if (a === 'editar') return abrirWz(id);
        if (a === 'duplicar') {
            try { const r = await api(R.dup(id), { method: 'POST' }); toast(r.mensaje); cargar(); abrirWz(r.id); }
            catch (er) { toast(er.message, 'warn'); }
            return;
        }
        if (a === 'estado') {
            const c = datos.data.find(x => x.id === id) || det;
            const pausar = !(c && c.pausado);
            if (pausar && !(await confirmar({ title: 'Pausar cupón', message: `${c?.codigo || 'El cupón'} dejará de aplicarse a compras nuevas. Las reservas y ventas existentes se respetan.`, confirmText: 'Pausar', danger: false }))) return;
            try { const r = await api(R.estado(id), { method: 'POST' }); toast(r.mensaje); cargar(); if ($('cDet').open && detId === id) verDetalle(id); }
            catch (er) { toast(er.message, 'warn'); }
        }
    }

    // ---------------- Panel de filtros ----------------
    $('cBtnFiltros').addEventListener('click', e => {
        document.querySelectorAll('[name="f_tipo"]').forEach(r => r.checked = r.value === st.tipo);
        document.querySelectorAll('[name="f_desc"]').forEach(r => r.checked = r.value === st.descuento_tipo);
        $('f_usos').checked = st.con_usos; $('f_personales').checked = st.personales;
        $('f_orden').value = st.orden; $('f_dir').value = st.dir; $('f_pp').value = st.por_pagina;
        abrir($('cFiltros'), e.currentTarget);
    });
    $('cFAplicar').addEventListener('click', () => {
        st.tipo = document.querySelector('[name="f_tipo"]:checked')?.value || '';
        st.descuento_tipo = document.querySelector('[name="f_desc"]:checked')?.value || '';
        st.con_usos = $('f_usos').checked; st.personales = $('f_personales').checked;
        st.orden = $('f_orden').value; st.dir = $('f_dir').value; st.por_pagina = $('f_pp').value; st.page = 1;
        cerrar($('cFiltros')); guardarUrl(); cargar();
    });
    function limpiarFiltros() { Object.assign(st, { q: '', tipo: '', descuento_tipo: '', con_usos: false, personales: false, estado: 'todos', page: 1 }); $('cQ').value = ''; guardarUrl(); cargar(); }
    $('cFLimpiar').addEventListener('click', () => { cerrar($('cFiltros')); limpiarFiltros(); });

    // ---------------- Detalle (panel lateral) ----------------
    let det = null, detId = null;
    async function verDetalle(id, origen, pestana = 'resumen') {
        detId = id;
        sessionStorage.setItem('mk-cupones-y', scrollY);
        guardarUrl({ ver: id });
        $('cDetC').innerHTML = `<div class="mk-dh"><h2 id="cDetT"><span class="mk-sk" style="width:140px;height:18px;display:inline-block"></span></h2><button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar">${ic('x')}</button></div>
            <div class="mk-db"><div class="mk-sk" style="height:90px;margin-bottom:12px"></div><div class="mk-sk" style="height:200px"></div></div>`;
        abrir($('cDet'), origen);
        try {
            det = await api(R.det(id));
            pintarDetalle(pestana);
        } catch (e) {
            $('cDetC').querySelector('.mk-db').innerHTML = error(e.message);
        }
    }
    $('cDet').addEventListener('close', () => { guardarUrl(); });

    function pintarDetalle(pestana) {
        const c = det, r = c.resultados;
        const acc = PUEDE ? `<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
            <button type="button" class="mk-btn sm" data-acc="editar" data-id="${c.id}">${ic('editar')}Editar</button>
            <button type="button" class="mk-btn sec sm" data-acc="duplicar" data-id="${c.id}">${ic('duplicar')}Duplicar</button>
            <button type="button" class="mk-btn sec sm" data-acc="estado" data-id="${c.id}">${c.pausado ? ic('play') + 'Activar' : ic('pausa') + 'Pausar'}</button></div>` : '';
        $('cDetC').innerHTML = `
          <div class="mk-dh"><h2 id="cDetT"><span class="mk-code" style="font-size:15px">${esc(c.codigo)}</span><button type="button" class="mk-ico" style="width:28px;height:28px" data-acc="copiar" data-codigo="${esc(c.codigo)}" aria-label="Copiar código">${ic('copiar')}</button></h2>
            <span class="mk-badge ${c.tono}">${esc(c.estado_label)}</span><button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar">${ic('x')}</button></div>
          <div class="mk-db">
            <div class="mk-ticket" style="cursor:default;margin-bottom:14px"><div class="l"><span class="v">${esc(c.descuento)}</span><span class="s">${c.tope ? 'máx. ' + esc(c.tope) : ''}</span></div>
              <div class="r"><div class="nom" style="color:var(--text);font-weight:600">${esc(c.nombre)}</div><div class="meta"><span>${tipoIc(c)}${esc(c.tipo_label)}${c.cliente ? ' · ' + esc(c.cliente) : ''}</span></div>
              <div class="meta"><span>${ic('cal')}${esc(c.vence_rel)}</span></div>${uso(c)}</div></div>
            ${acc}
            <div class="mk-itabs" role="tablist">
              ${[['resumen', 'Resumen'], ['condiciones', 'Condiciones'], ['usos', `Usos (${c.usos_lista.length})`], ['actividad', 'Actividad']].map(([k, t]) => `<button type="button" role="tab" data-ptab="${k}" aria-selected="${k === pestana}">${t}</button>`).join('')}
            </div>
            <div data-pp="resumen" ${pestana !== 'resumen' ? 'hidden' : ''}>
              <div class="mk-mini"><div><b class="mk-num">${r.usados}</b><span>usos en ventas</span></div><div><b class="mk-num">${r.reservados}</b><span>reservados</span></div><div><b class="mk-num">${money(r.descontado, 0)}</b><span>descontado</span></div></div>
              <ul class="mk-rows">
                <li><span class="k">Ventas con el cupón</span><span class="v">${money(r.ventas)}</span></li>
                ${r.copias !== null ? `<li><span class="k">Códigos personales generados</span><span class="v">${r.copias}</span></li>` : ''}
                ${c.campana ? `<li><span class="k">Campaña</span><span class="v"><a href="${@json(url('promociones/campanas'))}/${c.campana.id}">${esc(c.campana.nombre)}</a></span></li>` : ''}
                <li><span class="k">Compra mínima</span><span class="v">${c.minimo || 'Sin mínimo'}</span></li>
                <li><span class="k">Límite</span><span class="v">${c.usos_max ? c.usos_max + ' en total' : 'Sin límite total'} · ${c.form.usos_por_cliente} por cliente</span></li>
              </ul>
            </div>
            <div data-pp="condiciones" ${pestana !== 'condiciones' ? 'hidden' : ''}>
              <ul class="mk-rows">${c.condiciones.map(x => `<li><span>${esc(x)}</span></li>`).join('')}</ul>
              <p class="mk-lbl" style="margin:14px 0 4px">Aplica a</p><ul class="mk-rows">${c.alcance.map(x => `<li><span>${esc(x)}</span></li>`).join('')}</ul>
            </div>
            <div data-pp="usos" ${pestana !== 'usos' ? 'hidden' : ''}>
              ${c.usos_lista.length ? `<ul class="mk-rows">${c.usos_lista.map(x => `<li><span><b style="font-weight:600">${esc(x.cliente)}</b><br><small class="mk-muted">${x.url ? `<a href="${x.url}">${esc(x.documento)}</a>` : esc(x.documento || '')} · ${esc(x.fecha)}${x.motivo ? ' · ' + esc(x.motivo) : ''}</small></span>
                 <span class="v"><span class="mk-badge ${x.tono}">${esc(x.estado)}</span><br><small class="mk-num">-${money(x.descuento)}</small></span></li>`).join('')}</ul>`
                : vacio({ icono: 'lista', titulo: 'Aún no se usa', texto: 'Aparecerá aquí al aplicarse en una cotización o venta.' })}
            </div>
            <div data-pp="actividad" ${pestana !== 'actividad' ? 'hidden' : ''}>
              <ul class="mk-tl">${c.actividad.map(x => `<li>${esc(x.texto)}<small>${esc(x.fecha)} · ${esc(x.quien)}</small></li>`).join('')}</ul>
            </div>
          </div>`;
    }
    $('cDet').addEventListener('click', e => {
        const t = e.target.closest('[data-ptab]');
        if (t) {
            $('cDet').querySelectorAll('[data-ptab]').forEach(b => b.setAttribute('aria-selected', b === t));
            $('cDet').querySelectorAll('[data-pp]').forEach(p => p.hidden = p.dataset.pp !== t.dataset.ptab);
            return;
        }
        const a = e.target.closest('[data-acc]');
        if (a) {
            if (a.dataset.acc === 'editar') cerrar($('cDet'));
            accion(a.dataset.acc, +a.dataset.id || detId, a);
        }
    });

    // ---------------- Asistente ----------------
    const W = { paso: 1, edit: null, sucio: false, items: [], tipos: new Set(), cliente: null, guardando: false };
    const wz = $('cWz'), f = $('wzForm');
    const rad = (n, v) => { if (v === undefined) return f.querySelector(`[name="${n}"]:checked`)?.value; f.querySelectorAll(`[name="${n}"]`).forEach(r => r.checked = r.value === v); };

    function limpiarErrores() { f.querySelectorAll('[data-err]').forEach(e => e.textContent = ''); f.querySelectorAll('.mk-in.err').forEach(i => i.classList.remove('err')); document.querySelectorAll('.mk-step.mal').forEach(s => s.classList.remove('mal')); }
    function ponerError(campo, msg) {
        const e = f.querySelector(`[data-err="${campo}"]`); if (e) e.textContent = msg;
        f.querySelector(`[name="${campo}"]`)?.classList.add('err');
    }

    function rellenar(d) {
        rad('descuento_tipo', d.descuento_tipo || 'porcentaje');
        $('w_valor').value = d.descuento_valor ?? ''; $('w_tope').value = d.tope_descuento ?? '';
        $('w_codigo').value = d.codigo || ''; $('w_nombre').value = d.nombre || '';
        rad('w_quien', d.tipo === 'exclusivo' ? (d.cliente ? 'exclusivo' : 'molde') : (d.tipo || 'general'));
        W.cliente = d.cliente || null;
        rad('aplica_a', d.aplica_a || 'todo');
        W.items = (d.items || []).slice(); W.tipos = new Set((d.tipos || []).map(Number));
        $('w_ini').value = d.inicia_en || ''; $('w_fin').value = d.vence_en || '';
        $('w_min').value = d.compra_minima || ''; $('w_max').value = d.usos_max ?? ''; $('w_pc').value = d.usos_por_cliente || 1;
        $('w_acum').checked = !!d.acumulable; $('w_primera').checked = !!d.solo_primera_compra; $('w_activo').checked = (d.estado || 'activo') === 'activo';
        $('w_cond').value = d.condiciones || '';
    }

    async function abrirWz(id = null) {
        limpiarErrores();
        W.edit = id; W.sucio = false; W.paso = 1;
        $('cWzT').textContent = id ? 'Editar cupón' : 'Nuevo cupón';
        $('wzPresets').hidden = !!id;
        $('wzAviso').innerHTML = '';
        rellenar({});
        abrir(wz);
        guardarUrl(id ? { editar: id } : { nuevo: 1 });
        if (id) {
            $('w_sig').disabled = true;
            try {
                const d = await api(R.det(id));
                rellenar(d.form);
                $('cWzT').innerHTML = `Editar <span class="mk-code">${esc(d.codigo)}</span>`;
                if (d.form.con_usos) $('wzAviso').innerHTML = `<div class="mk-badge info" style="height:auto;padding:6px 10px;margin-bottom:12px;border-radius:8px">Ya tiene usos: los cambios aplican a compras nuevas.</div>`;
            } catch (e) { toast(e.message, 'warn'); cerrar(wz); return; }
            $('w_sig').disabled = false;
        }
        W.sucio = false;
        irA(1); vista();
    }
    wz._antesDeCerrar = async () => {
        if (W.guardando) return;
        if (W.sucio && !(await confirmar({ title: 'Descartar cambios', message: 'Tienes cambios sin guardar en el cupón. ¿Cerrar de todos modos?', confirmText: 'Descartar', cancelText: 'Seguir editando', danger: true }))) return;
        cerrar(wz); guardarUrl();
    };
    wz.addEventListener('cancel', e => { e.preventDefault(); wz._antesDeCerrar(); });
    f.addEventListener('input', () => { W.sucio = true; vista(); });
    f.addEventListener('change', () => { W.sucio = true; vista(); });
    f.addEventListener('submit', e => e.preventDefault());

    function validarPaso(n) {
        limpiarErrores();
        let ok = true;
        const mal = (c, m) => { ponerError(c, m); ok = false; };
        if (n === 1) {
            const v = parseFloat($('w_valor').value);
            if (!(v > 0)) mal('descuento_valor', 'Escribe un valor mayor a 0.');
            else if (rad('descuento_tipo') === 'porcentaje' && v > 100) mal('descuento_valor', 'Un porcentaje no puede pasar de 100.');
            if (!$('w_nombre').value.trim()) mal('nombre', 'Ponle un nombre.');
            const cod = $('w_codigo').value.trim();
            if (cod && !/^[A-Za-z0-9_-]{3,40}$/.test(cod)) mal('codigo', 'De 3 a 40 letras, números, guion o guion bajo.');
        }
        if (n === 2) {
            if (rad('w_quien') === 'exclusivo' && !W.cliente) mal('cliente_id', 'Elige al cliente.');
            if (rad('aplica_a') === 'seleccion' && !W.items.length && !W.tipos.size) mal('alcance', 'Elige al menos un producto o tipo de equipo.');
        }
        if (n === 3) {
            if ($('w_ini').value && $('w_fin').value && $('w_fin').value <= $('w_ini').value) mal('vence_en', 'Debe vencer después de empezar.');
            if (!(+$('w_pc').value >= 1)) mal('usos_por_cliente', 'Mínimo 1.');
        }
        return ok;
    }

    function irA(n) {
        W.paso = n;
        f.querySelectorAll('[data-paso]').forEach(p => p.hidden = +p.dataset.paso !== n);
        document.querySelectorAll('#cWz .mk-step').forEach(s => {
            const k = +s.dataset.ir;
            s.toggleAttribute('aria-current', false);
            if (k === n) s.setAttribute('aria-current', 'step');
            s.classList.toggle('hecho', k < n);
        });
        $('w_atras').style.visibility = n === 1 ? 'hidden' : 'visible';
        $('w_sig').innerHTML = n < 4 ? 'Siguiente' : (W.edit ? ic('check') + 'Guardar cambios' : ic('check') + 'Crear cupón');
        if (n === 4) revision();
        f.querySelector(`[data-paso="${n}"] input:not([type=hidden]), [data-paso="${n}"] textarea`)?.focus({ preventScroll: true });
    }
    document.querySelectorAll('#cWz .mk-step').forEach(s => s.addEventListener('click', () => {
        const k = +s.dataset.ir;
        if (k < W.paso) return irA(k);
        for (let i = W.paso; i < k; i++) { if (!validarPaso(i)) return irA(i); }
        irA(k);
    }));
    $('w_atras').addEventListener('click', () => irA(Math.max(1, W.paso - 1)));
    $('w_sig').addEventListener('click', () => {
        if (W.paso < 4) { if (validarPaso(W.paso)) irA(W.paso + 1); return; }
        guardar();
    });

    // Presets
    const finDeMes = () => { const d = new Date(); const u = new Date(d.getFullYear(), d.getMonth() + 1, 0); const z = n => String(n).padStart(2, '0'); return `${u.getFullYear()}-${z(u.getMonth() + 1)}-${z(u.getDate())}T23:59`; };
    const PRESETS = {
        bienvenida: { descuento_tipo: 'porcentaje', descuento_valor: 10, tope_descuento: 300, compra_minima: 1000, nombre: 'Bienvenida', codigo: 'BIENVENIDO10', usos_por_cliente: 1, solo_primera_compra: true, tipo: 'general', aplica_a: 'todo' },
        fijo: { descuento_tipo: 'monto', descuento_valor: 500, compra_minima: 5000, nombre: 'Descuento fijo', usos_por_cliente: 1, tipo: 'general', aplica_a: 'todo' },
        temporada: { descuento_tipo: 'porcentaje', descuento_valor: 15, tope_descuento: 2000, nombre: 'Temporada', vence_en: finDeMes(), usos_por_cliente: 1, tipo: 'general', aplica_a: 'todo' },
        vip: { descuento_tipo: 'porcentaje', descuento_valor: 10, nombre: 'Código personal', codigo: 'VIP', tipo: 'exclusivo', cliente: null, usos_por_cliente: 1, aplica_a: 'todo' },
    };
    $('wzPresets').addEventListener('click', e => {
        const b = e.target.closest('[data-preset]'); if (!b) return;
        rellenar(PRESETS[b.dataset.preset]); W.sucio = true;
        document.querySelectorAll('[data-preset]').forEach(x => x.setAttribute('aria-pressed', x === b));
        vista();
    });
    $('w_gen').addEventListener('click', async () => {
        try { const r = await api(R.sugerir + '?prefijo=' + encodeURIComponent(($('w_codigo').value.split('-')[0] || 'MB').slice(0, 12))); $('w_codigo').value = r.codigo; W.sucio = true; vista(); }
        catch (e) { toast(e.message, 'warn'); }
    });

    // Buscadores con teclado
    function buscador(input, lista, ruta, pintarFila, elegir) {
        let res = [], act = -1;
        const buscar = debounce(async () => {
            const q = input.value.trim();
            if (q.length < 2) { lista.classList.remove('on'); input.setAttribute('aria-expanded', 'false'); return; }
            try { res = await api(ruta + '?q=' + encodeURIComponent(q)); } catch (e) { res = []; }
            act = -1;
            lista.innerHTML = res.length ? res.map((d, i) => `<div class="mk-ac-item" role="option" data-i="${i}">${pintarFila(d)}</div>`).join('') : '<div class="mk-ac-item"><small>Sin resultados</small></div>';
            lista.classList.add('on'); input.setAttribute('aria-expanded', 'true');
        }, 250);
        input.addEventListener('input', buscar);
        const tomar = i => { if (res[i]) { elegir(res[i]); input.value = ''; lista.classList.remove('on'); W.sucio = true; vista(); } };
        lista.addEventListener('mousedown', e => { const it = e.target.closest('[data-i]'); if (it) { e.preventDefault(); tomar(+it.dataset.i); } });
        input.addEventListener('keydown', e => {
            if (!lista.classList.contains('on')) return;
            const its = lista.querySelectorAll('[data-i]');
            if (e.key === 'ArrowDown') { e.preventDefault(); act = Math.min(its.length - 1, act + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); act = Math.max(0, act - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); tomar(act < 0 ? 0 : act); return; }
            else if (e.key === 'Escape') { e.stopPropagation(); lista.classList.remove('on'); return; }
            its.forEach((x, i) => x.classList.toggle('act', i === act));
        });
        input.addEventListener('blur', () => setTimeout(() => lista.classList.remove('on'), 150));
    }
    buscador($('w_cli'), $('w_cli_l'), R.clientes, d => `<span>${esc(d.nombre)}</span><small>${esc(d.telefono || '')}</small>`, d => { W.cliente = { id: d.id, nombre: d.nombre }; });
    buscador($('w_prod'), $('w_prod_l'), R.productos, d => `<span>${esc(d.nombre)} <small>${esc(d.modelo || '')}</small></span><small>${d.tipo_item === 'paquete' ? 'Paquete' : money(d.precio, 0)}</small>`,
        d => { const k = d.tipo_item + '-' + d.id; if (!W.items.some(x => x.clave === k)) W.items.push({ clave: k, nombre: (d.nombre + ' ' + (d.modelo || '')).trim() }); });
    $('w_tipos')?.addEventListener('click', e => {
        const b = e.target.closest('[data-tipo]'); if (!b) return;
        const t = +b.dataset.tipo; W.tipos.has(t) ? W.tipos.delete(t) : W.tipos.add(t); W.sucio = true; vista();
    });
    f.addEventListener('click', e => {
        const q = e.target.closest('[data-quitar-item]');
        if (q) { W.items = W.items.filter(x => x.clave !== q.dataset.quitarItem); W.sucio = true; vista(); }
        if (e.target.closest('[data-quitar-cli]')) { W.cliente = null; W.sucio = true; vista(); }
    });

    function payload() {
        const quien = rad('w_quien');
        return {
            codigo: $('w_codigo').value.trim(), nombre: $('w_nombre').value.trim(),
            tipo: quien === 'general' ? 'general' : 'exclusivo', cliente_id: quien === 'exclusivo' ? W.cliente?.id : null,
            descuento_tipo: rad('descuento_tipo'), descuento_valor: $('w_valor').value, tope_descuento: rad('descuento_tipo') === 'porcentaje' ? ($('w_tope').value || null) : null,
            compra_minima: $('w_min').value || 0, inicia_en: $('w_ini').value || null, vence_en: $('w_fin').value || null,
            usos_max: $('w_max').value || null, usos_por_cliente: $('w_pc').value || 1,
            aplica_a: rad('aplica_a'), alcance: rad('aplica_a') === 'seleccion' ? W.items.map(x => x.clave).concat([...W.tipos].map(t => 'tipo_equipo-' + t)) : [],
            acumulable: $('w_acum').checked, solo_primera_compra: $('w_primera').checked, estado: $('w_activo').checked ? 'activo' : 'pausado',
            condiciones: $('w_cond').value.trim() || null,
        };
    }

    function textoDesc(p) {
        const v = parseFloat(p.descuento_valor) || 0;
        return p.descuento_tipo === 'porcentaje' ? `${+v.toFixed(2)} %` : money(v, v % 1 ? 2 : 0);
    }
    const fecha = v => v ? new Date(v).toLocaleString('es-MX', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

    function vista() {
        const p = payload(), quien = rad('w_quien');
        const pct = p.descuento_tipo === 'porcentaje';
        $('w_valor_suf').textContent = pct ? '%' : 'MXN';
        $('w_tope_box').style.visibility = pct ? 'visible' : 'hidden';
        $('w_cli_box').hidden = quien !== 'exclusivo';
        $('w_cli_tag').innerHTML = W.cliente ? `<span class="mk-chip aplicado">${esc(W.cliente.nombre)}<button type="button" data-quitar-cli aria-label="Quitar cliente">${ic('x')}</button></span>` : '';
        $('w_alc_box').hidden = p.aplica_a !== 'seleccion';
        $('w_prod_tags').innerHTML = W.items.map(x => `<span class="mk-chip aplicado">${esc(x.nombre)}<button type="button" data-quitar-item="${x.clave}" aria-label="Quitar ${esc(x.nombre)}">${ic('x')}</button></span>`).join('');
        $('w_tipos')?.querySelectorAll('[data-tipo]').forEach(b => b.setAttribute('aria-pressed', W.tipos.has(+b.dataset.tipo)));

        const quienL = { general: 'Cualquier cliente', exclusivo: W.cliente ? 'Solo ' + W.cliente.nombre : 'Un cliente', molde: 'Un código por cliente' }[quien];
        $('w_prev').innerHTML = `<div class="mk-ticket ${p.estado === 'activo' ? '' : 'apagado'}" style="cursor:default">
            <div class="l"><span class="v">${textoDesc(p)}</span><span class="s">${pct && p.tope_descuento ? 'máx. ' + money(p.tope_descuento, 0) : ''}</span></div>
            <div class="r"><div class="top"><span class="mk-badge ${p.estado === 'activo' ? 'ok' : 'warn'}">${p.estado === 'activo' ? 'Activo' : 'Pausado'}</span><span class="mk-pill">${quien === 'general' ? ic('globo') : ic('usuario')}${quien === 'general' ? 'General' : (quien === 'molde' ? 'Molde' : 'Exclusivo')}</span></div>
            <div class="cod"><span class="mk-code">${esc((p.codigo || 'AUTOMÁTICO').toUpperCase())}</span></div>
            <div class="nom">${esc(p.nombre || 'Sin nombre')}</div>
            <div class="meta"><span>${ic('cal')}${p.vence_en ? 'Vence ' + fecha(p.vence_en) : 'Sin vencimiento'}</span></div></div></div>`;
        const filas = [
            ['Quién', quienL], ['Productos', p.aplica_a === 'seleccion' ? `${W.items.length + W.tipos.size} elegido(s)` : 'Todos'],
            ['Compra mínima', +p.compra_minima ? money(p.compra_minima, 0) : 'Sin mínimo'],
            ['Usos', `${p.usos_por_cliente} por cliente${p.usos_max ? ' · ' + p.usos_max + ' total' : ''}`],
        ];
        if (pct && +p.descuento_valor) {
            const base = Math.max(+p.compra_minima || 0, 1000);
            const d = Math.min(base * p.descuento_valor / 100, +p.tope_descuento || Infinity);
            filas.push(['Ejemplo', `En ${money(base, 0)} descuenta ${money(d, 0)}`]);
        }
        $('w_prev_l').innerHTML = filas.map(([k, v]) => `<li><span class="k">${k}</span><span class="v">${esc(v)}</span></li>`).join('');
        if (W.paso === 4) revision();
    }

    function revision() {
        const p = payload(), quien = rad('w_quien');
        const tipos = [...W.tipos].map(t => TIPOS[t]).filter(Boolean);
        const filas = [
            ['Código', (p.codigo || 'Se genera al guardar').toUpperCase()], ['Nombre', p.nombre], ['Descuento', textoDesc(p) + (p.tope_descuento ? ` (máx. ${money(p.tope_descuento, 0)})` : '')],
            ['Quién', { general: 'Cualquier cliente', exclusivo: W.cliente?.nombre || '—', molde: 'Código personal por destinatario de campaña' }[quien]],
            ['Productos', p.aplica_a === 'seleccion' ? W.items.map(x => x.nombre).concat(tipos).join(', ') : 'Todos'],
            ['Vigencia', `${p.inicia_en ? fecha(p.inicia_en) : 'Desde ya'} – ${p.vence_en ? fecha(p.vence_en) : 'sin vencimiento'}`],
            ['Compra mínima', +p.compra_minima ? money(p.compra_minima) : 'Sin mínimo'],
            ['Usos', `${p.usos_por_cliente} por cliente · ${p.usos_max || 'sin límite'} en total`],
            ['Reglas', [p.acumulable ? 'Combinable' : 'No combinable', p.solo_primera_compra ? 'Solo primera compra' : null].filter(Boolean).join(' · ')],
            ['Estado', p.estado === 'activo' ? 'Activo al guardar' : 'Pausado'],
        ];
        $('w_rev').innerHTML = filas.map(([k, v]) => `<li><span class="k">${k}</span><span class="v">${esc(v)}</span></li>`).join('');
    }

    async function guardar() {
        for (let i = 1; i <= 3; i++) { if (!validarPaso(i)) return irA(i); }
        W.guardando = true;
        const b = $('w_sig'); b.disabled = true; b.textContent = 'Guardando…';
        try {
            const r = await api(W.edit ? R.upd(W.edit) : R.store, { method: W.edit ? 'PUT' : 'POST', body: payload() });
            W.sucio = false; W.guardando = false;
            toast(r.mensaje);
            cerrar(wz);
            st.page = W.edit ? st.page : 1;
            await cargar();
            verDetalle(r.id);
        } catch (e) {
            W.guardando = false;
            if (e.status === 422) {
                const pasoDe = { codigo: 1, nombre: 1, descuento_tipo: 1, descuento_valor: 1, tope_descuento: 1, tipo: 2, cliente_id: 2, aplica_a: 2, alcance: 2,
                    inicia_en: 3, vence_en: 3, usos_max: 3, usos_por_cliente: 3, compra_minima: 3, condiciones: 4 };
                let primero = 4;
                Object.entries(e.errores).forEach(([k, m]) => {
                    const campo = k.split('.')[0]; ponerError(campo, m[0]);
                    primero = Math.min(primero, pasoDe[campo] || 4);
                    document.querySelector(`#cWz .mk-step[data-ir="${pasoDe[campo] || 4}"]`)?.classList.add('mal');
                });
                irA(primero);
                toast('Revisa los campos marcados.', 'warn');
            } else toast(e.message, 'warn');
        }
        b.disabled = false; irA(W.paso);
    }

    // El botón "Crear → Cupón" del encabezado abre el asistente sin recargar.
    document.querySelector('[data-crear-cupon]')?.addEventListener('click', e => { e.preventDefault(); abrirWz(); });

    // ---------------- Arranque ----------------
    cargar().then(() => {
        if (u.nuevo && PUEDE) abrirWz();
        else if (u.editar && PUEDE) abrirWz(+u.editar);
        else if (u.ver) verDetalle(+u.ver);
    });
})();
</script>
@endsection
