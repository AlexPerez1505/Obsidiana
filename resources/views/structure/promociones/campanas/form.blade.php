@extends('structure.commercial_management.erp')

@php
    $editando = $campana->exists;
    $f = old('filtros', $campana->filtros ?? ['modo' => 'segmento', 'segmento' => 'todos']);
    $segUi = ($f['modo'] ?? 'segmento') === 'manual' ? 'manual' : ($f['segmento'] ?? 'todos');
    $itemsJs = old('items') ? collect(old('items'))->map(fn ($k) => ['clave' => $k, 'nombre' => $k, 'precio' => 0])->values() : $itemsIniciales->values();
@endphp

@section('title', $editando ? 'Editar campaña' : 'Nueva campaña')
@section('page-title', 'Marketing y promociones')

@section('erp_content')
<div class="mk" data-no-mayus>
    @include('structure.promociones._shell', ['tab' => 'campanas'])

    <form id="campForm" method="POST" enctype="multipart/form-data" novalidate
          action="{{ $editando ? route('promociones.campanas.update', $campana) : route('promociones.campanas.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div class="mk-card" style="overflow:visible;">
            <div class="mk-dh" style="border-bottom:0; padding-bottom:6px;">
                <a href="{{ $editando ? route('promociones.campanas.show', $campana) : route('promociones.campanas.index') }}" class="mk-ico" aria-label="Volver" data-salir>@include('structure.promociones._ic', ['n' => 'chevron', 'c' => 'rot'])</a>
                <h2>{{ $editando ? 'Editar campaña' : 'Nueva campaña' }}</h2>
                @if ($editando)<span class="mk-badge {{ $campana->tonoEstado() }}">{{ $campana->estadoLabel() }}</span>@endif
            </div>
            <div class="mk-steps" aria-label="Pasos">
                <button type="button" class="mk-step" data-ir="1"><span class="b">1</span>Contenido</button>
                <button type="button" class="mk-step" data-ir="2"><span class="b">2</span>Destinatarios</button>
                <button type="button" class="mk-step" data-ir="3"><span class="b">3</span>Programación</button>
                <button type="button" class="mk-step" data-ir="4"><span class="b">4</span>Revisión</button>
            </div>

            <div class="mk-wz" style="grid-template-columns:minmax(0,1fr) 340px;">
                <div class="mk-wz-main" style="max-height:none;">
                    @if ($errors->any())
                        <div class="mk-badge danger" style="height:auto; padding:8px 12px; border-radius:8px; margin-bottom:12px; display:block; font-weight:500;">
                            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                        </div>
                    @endif

                    {{-- ===== 1. Contenido ===== --}}
                    <section class="mk-panel" data-paso="1">
                        <div class="mk-r2">
                            <div class="mk-f"><label for="nombre">Nombre interno</label><input id="nombre" name="nombre" class="mk-in" maxlength="255" required value="{{ old('nombre', $campana->nombre) }}" placeholder="Buen Fin · ginecología"><span class="mk-err" data-err="nombre">{{ $errors->first('nombre') }}</span></div>
                            <div class="mk-f"><label for="titulo_oferta">Título en la oferta</label><input id="titulo_oferta" name="titulo_oferta" class="mk-in" maxlength="120" value="{{ old('titulo_oferta', $campana->titulo_oferta) }}" placeholder="10 % en monitores"></div>
                        </div>
                        <div class="mk-f">
                            <label for="mensaje">Texto de la oferta <span class="mk-muted" style="font-weight:400;" data-tip-mk="Variable 2 de la plantilla">· @include('structure.promociones._ic', ['n' => 'chat'])</span></label>
                            <textarea id="mensaje" name="mensaje" class="mk-in" maxlength="600" required placeholder="Tu monitor de signos vitales con 10 % de descuento y envío sin costo.">{{ old('mensaje', $campana->mensaje) }}</textarea>
                            <div style="display:flex; justify-content:space-between;"><span class="mk-err" data-err="mensaje">{{ $errors->first('mensaje') }}</span><span class="mk-hint mk-num"><span id="msgCuenta">0</span>/600</span></div>
                        </div>
                        <div class="mk-r2">
                            <div class="mk-f"><label for="imagen">Imagen <span class="mk-muted" style="font-weight:400;">JPG/PNG · 5 MB</span></label>
                                <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png" class="mk-in" style="padding-top:6px;">
                                @if ($campana->imagen_path)<label class="mk-switch" style="border:0;"><span>Quitar la imagen actual</span><input type="checkbox" name="quitar_imagen" value="1" id="quitarImg"></label>@endif
                                <span class="mk-err">{{ $errors->first('imagen') }}</span></div>
                            <div class="mk-f"><label for="cupon_id">Cupón</label>
                                <select id="cupon_id" name="cupon_id" class="mk-in">
                                    <option value="">Sin cupón</option>
                                    @foreach ($cupones as $c)
                                        <option value="{{ $c->id }}" data-codigo="{{ $c->codigo }}" data-molde="{{ $c->esPlantilla() ? 1 : 0 }}" data-desc="{{ $c->textoDescuento() }}"
                                                data-vence="{{ $c->vence_en?->format('d/m/Y') }}" @selected((string) old('cupon_id', $campana->cupon_id) === (string) $c->id)>
                                            {{ $c->codigo }} · {{ $c->textoDescuento() }}{{ $c->esPlantilla() ? ' · código por cliente' : '' }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="cupon_exclusivo" id="cupon_exclusivo" value="{{ old('cupon_exclusivo', $campana->cupon_exclusivo) ? 1 : 0 }}">
                                <span class="mk-hint" id="cuponNota"></span><span class="mk-err">{{ $errors->first('cupon_id') }}</span></div>
                        </div>
                        <p class="mk-h4">Productos de la oferta</p>
                        <div class="mk-ac"><input id="prodBuscar" class="mk-in" placeholder="Buscar producto o paquete" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="prodLista"><div id="prodLista" class="mk-ac-list" role="listbox"></div></div>
                        <div id="prodElegidos" style="margin-top:8px;"></div>
                        <span class="mk-err" data-err="items">{{ $errors->first('items') }}</span>
                        <details style="margin-top:12px;"><summary class="mk-lbl" style="cursor:pointer;">Avanzado</summary>
                            <div class="mk-f" style="margin-top:8px;"><label for="plantilla">Plantilla de WhatsApp</label><input id="plantilla" name="plantilla" class="mk-in" maxlength="120" value="{{ old('plantilla', $campana->plantilla) }}" placeholder="{{ config('promociones.whatsapp.plantilla') }}"></div>
                        </details>
                    </section>

                    {{-- ===== 2. Destinatarios ===== --}}
                    <section class="mk-panel" data-paso="2" hidden>
                        <div class="mk-opts" id="segs" role="radiogroup" aria-label="Segmento">
                            @foreach (['todos' => ['usuarios', 'Todos', 'con consentimiento'], 'nuevos' => ['plus', 'Nuevos', 'alta reciente'], 'frecuentes' => ['ventas', 'Frecuentes', 'compran seguido'],
                                       'inactivos' => ['reloj', 'Inactivos', 'sin comprar'], 'productos' => ['tag', 'Por producto', 'compraron algo'], 'monto' => ['porcentaje', 'Por monto', 'compras acumuladas'],
                                       'manual' => ['check', 'Elegir clientes', 'uno por uno']] as $k => [$icono, $t, $s])
                                <label class="mk-opt"><input type="radio" name="seg_ui" value="{{ $k }}" @checked($segUi === $k)>
                                    <b>@include('structure.promociones._ic', ['n' => $icono]){{ $t }}</b><span class="cnt mk-num" data-cnt="{{ $k }}">{{ in_array($k, ['todos', 'nuevos', 'frecuentes', 'inactivos']) ? '…' : '' }}</span><span>{{ $s }}</span></label>
                            @endforeach
                        </div>
                        <input type="hidden" name="filtros[modo]" id="f_modo">
                        <input type="hidden" name="filtros[segmento]" id="f_segmento">
                        <div style="margin-top:14px;">
                            <div class="mk-r2" data-seg="nuevos inactivos"><div class="mk-f"><label for="dias">Días</label><input id="dias" type="number" min="1" name="filtros[dias]" class="mk-in" value="{{ $f['dias'] ?? '' }}" placeholder="30 / 180"></div></div>
                            <div class="mk-r2" data-seg="frecuentes"><div class="mk-f"><label for="min_compras">Compras mínimas</label><input id="min_compras" type="number" min="1" name="filtros[min_compras]" class="mk-in" value="{{ $f['min_compras'] ?? '' }}" placeholder="3"></div></div>
                            <div class="mk-r2" data-seg="monto">
                                <div class="mk-f"><label for="monto_min">Desde</label><div class="mk-affix pre"><span>$</span><input id="monto_min" type="number" min="0" step="0.01" name="filtros[monto_min]" class="mk-in" value="{{ $f['monto_min'] ?? '' }}" placeholder="0"></div></div>
                                <div class="mk-f"><label for="monto_max">Hasta</label><div class="mk-affix pre"><span>$</span><input id="monto_max" type="number" min="0" step="0.01" name="filtros[monto_max]" class="mk-in" value="{{ $f['monto_max'] ?? '' }}" placeholder="Sin tope"></div></div>
                            </div>
                            <div data-seg="productos"><div class="mk-ac"><input id="segProdBuscar" class="mk-in" placeholder="Productos que compró" autocomplete="off"><div id="segProdLista" class="mk-ac-list"></div></div><div id="segProdChips" class="mk-tags"></div></div>
                            <div data-seg="manual"><div class="mk-ac"><input id="cliBuscar" class="mk-in" placeholder="Buscar cliente" autocomplete="off"><div id="cliLista" class="mk-ac-list"></div></div><div id="cliChips" class="mk-tags"></div></div>
                        </div>
                        <span class="mk-err" data-err="filtros">{{ $errors->first('filtros.clientes') }}</span>
                    </section>

                    {{-- ===== 3. Programación ===== --}}
                    <section class="mk-panel" data-paso="3" hidden>
                        <p class="mk-h4">Vigencia de la oferta</p>
                        <div class="mk-r2">
                            <div class="mk-f"><label for="vigencia_inicio">Abre</label><input id="vigencia_inicio" name="vigencia_inicio" type="datetime-local" class="mk-in" value="{{ old('vigencia_inicio', $campana->vigencia_inicio?->format('Y-m-d\TH:i')) }}"></div>
                            <div class="mk-f"><label for="vigencia_fin">Cierra</label><input id="vigencia_fin" name="vigencia_fin" type="datetime-local" class="mk-in" value="{{ old('vigencia_fin', $campana->vigencia_fin?->format('Y-m-d\TH:i')) }}"><span class="mk-err" data-err="vigencia_fin">{{ $errors->first('vigencia_fin') }}</span></div>
                        </div>
                        <p class="mk-h4" style="margin-top:6px;">Envío</p>
                        <div class="mk-opts" role="radiogroup" aria-label="Cuándo enviar">
                            <label class="mk-opt"><input type="radio" name="cuando_ui" value="despues" @checked(! $campana->programada_para)><b>@include('structure.promociones._ic', ['n' => 'reloj'])Después</b><span>Guardar borrador y mandar una prueba primero</span></label>
                            <label class="mk-opt"><input type="radio" name="cuando_ui" value="programar" @checked((bool) $campana->programada_para)><b>@include('structure.promociones._ic', ['n' => 'calendario'])Programar</b><span>Sale sola a la hora indicada</span></label>
                        </div>
                        <div class="mk-f" id="progBox" style="margin-top:12px;"><label for="programar_para">Fecha y hora de envío</label>
                            <input id="programar_para" type="datetime-local" class="mk-in" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}" value="{{ old('programar_para', $campana->programada_para?->format('Y-m-d\TH:i')) }}">
                            <span class="mk-err" data-err="programar_para">{{ $errors->first('programar_para') }}</span></div>
                        <input type="hidden" name="programar_para" id="programar_para_h">
                        @if ($editando && $campana->estado === 'programada')<input type="hidden" name="quitar_programacion" id="quitarProg" value="0">@endif
                    </section>

                    {{-- ===== 4. Revisión ===== --}}
                    <section class="mk-panel" data-paso="4" hidden>
                        <ul class="mk-rows" id="rev"></ul>
                        <p class="mk-hint" style="margin-top:12px;">@include('structure.promociones._ic', ['n' => 'alerta']) Al guardar no se envía nada: desde el detalle mandas una prueba a un número autorizado.</p>
                    </section>
                </div>

                <aside class="mk-wz-side">
                    <details open><summary>Vista previa</summary><div>
                        <div class="mk-wa"><span class="mk-badge neutral etq">Vista previa</span>
                            <div class="mk-wa-b borrador" style="margin-top:18px;">
                                @if (config('promociones.whatsapp.plantilla_con_imagen'))<img id="pvImg" src="{{ $campana->imagenUrl() ?? asset('images/logo.png') }}" alt="">@endif
                                <div class="mk-wa-t" id="pvTxt"></div>
                                <div class="mk-wa-h">sin enviar</div>
                                <div class="mk-wa-btn">@include('structure.promociones._ic', ['n' => 'enviar']){{ config('promociones.whatsapp.plantilla_boton_texto') }}</div>
                                @if (config('promociones.whatsapp.plantilla_boton_baja'))<div class="mk-wa-btn">Ya no quiero promociones</div>@endif
                            </div>
                        </div>
                        <div class="mk-card" style="margin-top:12px; box-shadow:none;">
                            <div class="mk-card-b">
                                <span class="mk-lbl">@include('structure.promociones._ic', ['n' => 'usuarios']) Recibirían hoy</span>
                                <div style="font-size:26px; font-weight:750; letter-spacing:-.02em;" class="mk-num" id="audN">—</div>
                                <div class="mk-hint" id="audExcl"></div>
                            </div>
                        </div>
                    </div></details>
                </aside>
            </div>

            <div class="mk-df" style="justify-content:space-between;">
                <button type="button" class="mk-btn ghost" id="atras">Atrás</button>
                <div style="display:flex; gap:8px;">
                    <a class="mk-btn sec" data-salir href="{{ $editando ? route('promociones.campanas.show', $campana) : route('promociones.campanas.index') }}">Cancelar</a>
                    <button type="button" class="mk-btn" id="sig">Siguiente</button>
                </div>
            </div>
        </div>
        <div id="ocultos"></div>
    </form>
</div>
<style>.mk .rot { transform: rotate(90deg); }</style>

<script>
(function () {
    const { api, debounce, ic, esc, money, toast, confirmar } = window.MK;
    const $ = id => document.getElementById(id);
    const R = { productos: @json(route('commercial.cotizaciones.productos.buscar')), clientes: @json(route('commercial.cotizaciones.clientes.buscar')),
                audiencia: @json(route('promociones.campanas.audiencia')), segmentos: @json(route('promociones.datos.campanas.segmentos')) };
    const PLANTILLA = @json($plantillaTexto);
    const st = {
        paso: 1, sucio: false, enviando: false,
        items: @json($itemsJs),
        segProductos: @json($productosSegmento->map(fn ($p) => ['clave' => $p['clave'], 'nombre' => $p['nombre']])->values()),
        clientes: @json($clientesIniciales->map(fn ($c) => ['id' => $c->id, 'nombre' => trim($c->nombre.' '.$c->apellido)])->values()),
        aud: null,
    };
    const seg = () => document.querySelector('[name="seg_ui"]:checked')?.value || 'todos';
    const cuando = () => document.querySelector('[name="cuando_ui"]:checked')?.value || 'despues';

    function buscador(input, lista, ruta, fila, elegir) {
        let res = [], act = -1;
        input.addEventListener('input', debounce(async () => {
            const q = input.value.trim();
            if (q.length < 2) { lista.classList.remove('on'); return; }
            try { res = await api(ruta + '?q=' + encodeURIComponent(q)); } catch (e) { res = []; }
            act = -1;
            lista.innerHTML = res.length ? res.map((d, i) => `<div class="mk-ac-item" data-i="${i}">${fila(d)}</div>`).join('') : '<div class="mk-ac-item"><small>Sin resultados</small></div>';
            lista.classList.add('on'); input.setAttribute('aria-expanded', 'true');
        }, 250));
        const tomar = i => { if (res[i]) { elegir(res[i]); input.value = ''; lista.classList.remove('on'); st.sucio = true; } };
        lista.addEventListener('mousedown', e => { const it = e.target.closest('[data-i]'); if (it) { e.preventDefault(); tomar(+it.dataset.i); } });
        input.addEventListener('keydown', e => {
            if (!lista.classList.contains('on')) return;
            const its = lista.querySelectorAll('[data-i]');
            if (e.key === 'ArrowDown') { e.preventDefault(); act = Math.min(its.length - 1, act + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); act = Math.max(0, act - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); tomar(act < 0 ? 0 : act); return; }
            else if (e.key === 'Escape') { lista.classList.remove('on'); return; }
            its.forEach((x, i) => x.classList.toggle('act', i === act));
        });
        input.addEventListener('blur', () => setTimeout(() => lista.classList.remove('on'), 150));
    }
    const chip = (t, attr) => `<span class="mk-chip aplicado">${esc(t)}<button type="button" ${attr} aria-label="Quitar ${esc(t)}">${ic('x')}</button></span>`;

    function pintarItems() {
        $('prodElegidos').innerHTML = st.items.length ? st.items.map((it, i) => `<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border)">
            ${it.imagen ? `<img src="${esc(it.imagen)}" alt="" style="width:38px;height:38px;border-radius:8px;object-fit:cover">` : `<span style="width:38px;height:38px;border-radius:8px;background:var(--surface-2);display:inline-flex;align-items:center;justify-content:center;color:var(--muted)">${ic('tag')}</span>`}
            <span style="flex:1;min-width:0;font-size:13px"><b>${esc(it.nombre)}</b><br><small class="mk-muted">${esc(it.modelo || '')}${it.marca ? ' · ' + esc(it.marca) : ''}</small></span>
            <span class="mk-num" style="font-size:13px">${it.precio > 0 ? money(it.precio) : '<span class="mk-muted">sin precio</span>'}</span>
            <button type="button" class="mk-ico" data-quitar-item="${i}" aria-label="Quitar ${esc(it.nombre)}">${ic('x')}</button></div>`).join('')
            : `<p class="mk-hint">${ic('tag')} Agrega al menos un producto.</p>`;
    }
    buscador($('prodBuscar'), $('prodLista'), R.productos, d => `<span>${esc(d.nombre)} <small>${esc(d.modelo || '')}</small></span><small>${money(d.precio, 0)}</small>`,
        d => { const k = d.tipo_item + '-' + d.id; if (!st.items.some(x => x.clave === k)) st.items.push({ clave: k, nombre: d.nombre, modelo: d.modelo, marca: d.marca, imagen: d.imagen, precio: +d.precio || 0 }); pintarItems(); });
    $('prodElegidos').addEventListener('click', e => { const b = e.target.closest('[data-quitar-item]'); if (b) { st.items.splice(+b.dataset.quitarItem, 1); st.sucio = true; pintarItems(); } });

    function pintarSeg() {
        $('segProdChips').innerHTML = st.segProductos.map((x, i) => chip(x.nombre, `data-qsp="${i}"`)).join('');
        $('cliChips').innerHTML = st.clientes.map((x, i) => chip(x.nombre, `data-qcl="${i}"`)).join('');
    }
    buscador($('segProdBuscar'), $('segProdLista'), R.productos, d => `<span>${esc(d.nombre)} <small>${esc(d.modelo || '')}</small></span>`,
        d => { const k = d.tipo_item + '-' + d.id; if (!st.segProductos.some(x => x.clave === k)) st.segProductos.push({ clave: k, nombre: d.nombre }); pintarSeg(); audiencia(); });
    buscador($('cliBuscar'), $('cliLista'), R.clientes, d => `<span>${esc(d.nombre)}</span><small>${esc(d.telefono || 'sin teléfono')}</small>`,
        d => { if (!st.clientes.some(x => x.id === d.id)) st.clientes.push({ id: d.id, nombre: d.nombre }); pintarSeg(); audiencia(); });
    document.querySelector('[data-paso="2"]').addEventListener('click', e => {
        const a = e.target.closest('[data-qsp]'), b = e.target.closest('[data-qcl]');
        if (a) { st.segProductos.splice(+a.dataset.qsp, 1); pintarSeg(); audiencia(); }
        if (b) { st.clientes.splice(+b.dataset.qcl, 1); pintarSeg(); audiencia(); }
    });

    function filtros() {
        const s = seg();
        return { modo: s === 'manual' ? 'manual' : 'segmento', segmento: s === 'manual' ? null : s, dias: $('dias').value, min_compras: $('min_compras').value,
                 monto_min: $('monto_min').value, monto_max: $('monto_max').value, productos: st.segProductos.map(x => x.clave), clientes: st.clientes.map(x => x.id) };
    }
    function mostrarSeg() { const s = seg(); document.querySelectorAll('[data-seg]').forEach(el => el.hidden = !el.dataset.seg.split(' ').includes(s)); }
    document.querySelectorAll('[name="seg_ui"]').forEach(r => r.addEventListener('change', () => { mostrarSeg(); audiencia(); }));
    ['dias', 'min_compras', 'monto_min', 'monto_max'].forEach(id => $(id).addEventListener('input', () => audiencia()));

    const audiencia = debounce(async () => {
        $('audN').textContent = '…';
        try {
            const d = await api(R.audiencia, { method: 'POST', body: { filtros: filtros() } });
            st.aud = d;
            $('audN').textContent = d.elegibles.toLocaleString('es-MX');
            const e = d.excluidos, partes = [];
            if (e.sin_consentimiento) partes.push(`${e.sin_consentimiento} sin consentimiento`);
            if (e.baja) partes.push(`${e.baja} de baja`);
            if (e.sin_telefono) partes.push(`${e.sin_telefono} sin teléfono`);
            if (e.duplicado) partes.push(`${e.duplicado} repetidos`);
            $('audExcl').textContent = partes.length ? 'Excluidos: ' + partes.join(' · ') : (d.base ? 'Sin exclusiones' : 'Nadie en el segmento');
            if (st.paso === 4) revision();
        } catch (er) { $('audN').textContent = '—'; $('audExcl').innerHTML = `<button type="button" class="mk-link" id="audRe">Reintentar</button>`; $('audRe').onclick = audiencia; }
    }, 300);

    // Conteo real de cada segmento rápido
    api(R.segmentos).then(d => Object.entries(d).forEach(([k, v]) => { const el = document.querySelector(`[data-cnt="${k}"]`); if (el) el.textContent = v.toLocaleString('es-MX'); }))
        .catch(() => document.querySelectorAll('[data-cnt]').forEach(el => { if (el.textContent === '…') el.textContent = ''; }));

    // ---------- Vista previa ----------
    function cuponSel() { const o = $('cupon_id').selectedOptions[0]; return o && o.value ? o : null; }
    function vistaPrevia() {
        const o = cuponSel();
        $('cupon_exclusivo').value = o && o.dataset.molde === '1' ? 1 : 0;
        $('cuponNota').textContent = o ? (o.dataset.molde === '1' ? 'Cada cliente recibe su propio código.' : 'Mismo código para todos.') : '';
        let codigo = o ? o.dataset.codigo + (o.dataset.molde === '1' ? '-XXXXXX' : '') : 'sin código: el precio ya incluye la promoción';
        const fin = $('vigencia_fin').value;
        const vence = (o && o.dataset.vence) || (fin ? fin.slice(8, 10) + '/' + fin.slice(5, 7) + '/' + fin.slice(0, 4) : 'agotar existencias');
        const oferta = $('mensaje').value.replace(/\s+/g, ' ').trim() || '…';
        let t = PLANTILLA;
        ['María', oferta, codigo, vence].forEach((v, i) => { t = t.split('@{{' + (i + 1) + '}}').join(v); });
        $('pvTxt').textContent = t;
        $('msgCuenta').textContent = $('mensaje').value.length;
    }
    ['mensaje', 'vigencia_fin'].forEach(id => $(id).addEventListener('input', vistaPrevia));
    $('cupon_id').addEventListener('change', vistaPrevia);
    $('imagen').addEventListener('change', e => { const f = e.target.files[0]; if (f && $('pvImg')) $('pvImg').src = URL.createObjectURL(f); });

    function progVis() { $('progBox').hidden = cuando() !== 'programar'; }
    document.querySelectorAll('[name="cuando_ui"]').forEach(r => r.addEventListener('change', progVis));

    // ---------- Pasos ----------
    function err(c, m) { const e = document.querySelector(`[data-err="${c}"]`); if (e) e.textContent = m; }
    function validar(n) {
        document.querySelectorAll('[data-err]').forEach(e => e.textContent = '');
        let ok = true;
        const mal = (c, m) => { err(c, m); ok = false; };
        if (n === 1) {
            if (!$('nombre').value.trim()) mal('nombre', 'Ponle un nombre.');
            if (!$('mensaje').value.trim()) mal('mensaje', 'Escribe el texto de la oferta.');
            if (!st.items.length) mal('items', 'Agrega al menos un producto.');
        }
        if (n === 2) {
            if (seg() === 'manual' && !st.clientes.length) mal('filtros', 'Elige al menos un cliente.');
            if (seg() === 'productos' && !st.segProductos.length) mal('filtros', 'Elige al menos un producto.');
        }
        if (n === 3) {
            const fin = $('vigencia_fin').value, ini = $('vigencia_inicio').value;
            if (fin && new Date(fin) <= new Date()) mal('vigencia_fin', 'Debe cerrar en el futuro.');
            if (fin && ini && fin <= ini) mal('vigencia_fin', 'Debe cerrar después de abrir.');
            if (cuando() === 'programar') {
                const p = $('programar_para').value;
                if (!p) mal('programar_para', 'Elige fecha y hora.');
                else if (new Date(p) <= new Date()) mal('programar_para', 'Debe ser en el futuro.');
            }
        }
        return ok;
    }
    function irA(n) {
        st.paso = n;
        document.querySelectorAll('[data-paso]').forEach(p => p.hidden = +p.dataset.paso !== n);
        document.querySelectorAll('.mk-step').forEach(s => { const k = +s.dataset.ir; s.toggleAttribute('aria-current', false); if (k === n) s.setAttribute('aria-current', 'step'); s.classList.toggle('hecho', k < n); });
        $('atras').style.visibility = n === 1 ? 'hidden' : 'visible';
        $('sig').innerHTML = n < 4 ? 'Siguiente' : ic('check') + (cuando() === 'programar' ? 'Guardar y programar' : @json($editando ? 'Guardar cambios' : 'Guardar borrador'));
        if (n === 4) revision();
        window.scrollTo({ top: document.getElementById('campForm').offsetTop - 70, behavior: 'smooth' });
    }
    document.querySelectorAll('.mk-step').forEach(s => s.addEventListener('click', () => {
        const k = +s.dataset.ir;
        if (k < st.paso) return irA(k);
        for (let i = st.paso; i < k; i++) { if (!validar(i)) return irA(i); }
        irA(k);
    }));
    $('atras').addEventListener('click', () => irA(Math.max(1, st.paso - 1)));
    $('sig').addEventListener('click', () => {
        if (st.paso < 4) { if (validar(st.paso)) irA(st.paso + 1); return; }
        for (let i = 1; i <= 3; i++) { if (!validar(i)) return irA(i); }
        enviar();
    });

    function revision() {
        const o = cuponSel(), s = document.querySelector('[name="seg_ui"]:checked')?.closest('.mk-opt')?.querySelector('b')?.textContent?.trim();
        const filas = [
            ['Campaña', $('nombre').value], ['Productos', st.items.map(x => x.nombre).join(', ')],
            ['Cupón', o ? `${o.dataset.codigo} · ${o.dataset.desc}${o.dataset.molde === '1' ? ' · código por cliente' : ''}` : 'Sin cupón'],
            ['Destinatarios', `${s}${st.aud ? ' · ' + st.aud.elegibles + ' cliente(s) hoy' : ''}`],
            ['Vigencia', `${$('vigencia_inicio').value ? $('vigencia_inicio').value.replace('T', ' ') : 'Desde el envío'} – ${$('vigencia_fin').value ? $('vigencia_fin').value.replace('T', ' ') : 'sin fecha'}`],
            ['Envío', cuando() === 'programar' ? 'Programado ' + $('programar_para').value.replace('T', ' ') : 'Después (borrador)'],
        ];
        $('rev').innerHTML = filas.map(([k, v]) => `<li><span class="k">${k}</span><span class="v" style="max-width:65%">${esc(v)}</span></li>`).join('');
    }

    function enviar() {
        const box = $('ocultos'); box.innerHTML = '';
        const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; box.appendChild(i); };
        st.items.forEach(it => add('items[]', it.clave));
        const fl = filtros();
        $('f_modo').value = fl.modo; $('f_segmento').value = fl.segmento || 'todos';
        if (fl.modo === 'segmento' && fl.segmento === 'productos') st.segProductos.forEach(p => add('filtros[productos][]', p.clave));
        if (fl.modo === 'manual') st.clientes.forEach(c => add('filtros[clientes][]', c.id));
        $('programar_para_h').value = cuando() === 'programar' ? $('programar_para').value : '';
        if ($('quitarProg')) $('quitarProg').value = cuando() === 'programar' ? 0 : 1;
        st.enviando = true;
        $('sig').disabled = true; $('sig').textContent = 'Guardando…';
        $('campForm').submit();
    }

    // Cambios sin guardar
    $('campForm').addEventListener('input', () => st.sucio = true);
    $('campForm').addEventListener('change', () => st.sucio = true);
    document.addEventListener('click', async e => {
        const a = e.target.closest('a[href]');
        if (!a || !st.sucio || st.enviando || a.target === '_blank' || a.closest('.mk-ac-list')) return;
        e.preventDefault();
        if (await confirmar({ title: 'Salir sin guardar', message: 'Los cambios de la campaña se perderán.', confirmText: 'Salir', cancelText: 'Seguir editando', danger: true })) { st.enviando = true; location.href = a.href; }
    }, true);
    window.addEventListener('beforeunload', e => { if (st.sucio && !st.enviando) { e.preventDefault(); e.returnValue = ''; } });

    pintarItems(); pintarSeg(); mostrarSeg(); vistaPrevia(); progVis(); audiencia();
    // Si el servidor regresó errores, abrir el paso que los tiene.
    const pasoErr = @json($errors->has('filtros.clientes') ? 2 : ($errors->hasAny(['vigencia_fin', 'programar_para']) ? 3 : 1));
    irA(@json($errors->any()) ? pasoErr : 1);
    st.sucio = false;
})();
</script>
@endsection
