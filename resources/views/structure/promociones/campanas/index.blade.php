@extends('structure.commercial_management.erp')

@section('title', 'Campañas · Promociones')
@section('page-title', 'Marketing y promociones')

@section('erp_content')
<div class="mk" data-no-mayus id="mkCamp">
    @include('structure.promociones._shell', ['tab' => 'campanas'])

    <div class="mk-bar">
        <div class="mk-search">
            @include('structure.promociones._ic', ['n' => 'busca'])
            <input type="search" id="kQ" placeholder="Buscar campaña" aria-label="Buscar campañas" autocomplete="off">
        </div>
        <div class="mk-chips" role="group" aria-label="Estado" id="kChips"></div>
        <span style="flex:1"></span>
        <button type="button" class="mk-btn sec" id="kBtnFiltros" aria-haspopup="dialog">@include('structure.promociones._ic', ['n' => 'ajustes']) Filtros <span class="mk-pill" id="kNFiltros" hidden></span></button>
        <div class="mk-seg" role="group" aria-label="Vista">
            <button type="button" data-vista="tarjetas" aria-label="Tarjetas" data-tip-mk="Tarjetas"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></button>
            <button type="button" data-vista="calendario" aria-label="Calendario de envíos" data-tip-mk="Calendario">@include('structure.promociones._ic', ['n' => 'calendario'])</button>
        </div>
    </div>
    <div class="mk-sub" aria-live="polite"><span class="total" id="kTotal"></span><span id="kAplicados" class="mk-chips"></span></div>

    <div id="kLista"></div>
    <div class="mk-pag" id="kPag"></div>

    <dialog class="mk-drawer" id="kFiltros" aria-labelledby="kFT">
        <div class="mk-dh"><h2 id="kFT">Filtros</h2><button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="mk-db">
            <label class="mk-switch"><span>Solo con cupón</span><input type="checkbox" id="kf_cupon"></label>
            <label class="mk-switch"><span>Solo con ventas atribuidas</span><input type="checkbox" id="kf_ventas"></label>
            <div class="mk-f" style="margin-top:14px;"><label for="kf_orden">Ordenar por</label>
                <select id="kf_orden" class="mk-in"><option value="recientes">Más recientes</option><option value="envio">Fecha de envío</option><option value="nombre">Nombre</option></select></div>
        </div>
        <div class="mk-df"><button type="button" class="mk-btn ghost" id="kFLimpiar">Limpiar</button><button type="button" class="mk-btn" id="kFAplicar">Aplicar</button></div>
    </dialog>

    <dialog class="mk-drawer" id="kDet" aria-labelledby="kDetT"><div id="kDetC" style="display:flex; flex-direction:column; height:100%;"></div></dialog>
</div>

<script>
(function () {
    const { api, debounce, ic, esc, money, toast, confirmar, abrir, cerrar, vacio, error, url } = window.MK;
    const $ = id => document.getElementById(id);
    const R = {
        lista: @json(route('promociones.datos.campanas')),
        det: id => @json(url('promociones/datos/campanas')) + '/' + id,
        cal: @json(route('promociones.datos.campanas.calendario')),
        crear: @json(route('promociones.campanas.create')),
    };
    const PUEDE = { gestionar: @json(auth()->user()->can('promociones.gestionar')), enviar: @json(auth()->user()->can('promociones.enviar')) };
    const BOTON = @json($boton), BAJA = @json($baja);
    const GRUPOS = [['todas', 'Todas'], ['borrador', 'Borradores'], ['programada', 'Programadas'], ['activas', 'Activas'], ['completada', 'Completadas'], ['cancelada', 'Canceladas']];

    const u = url.leer();
    let vg = null; try { vg = localStorage.getItem('mk-camp-vista'); } catch (e) {}
    const st = { q: u.q || '', estado: u.estado || 'todas', con_cupon: u.con_cupon === '1', con_ventas: u.con_ventas === '1', orden: u.orden || 'recientes',
                 page: +u.page || 1, vista: u.vista || vg || 'tarjetas', mes: u.mes || new Date().toISOString().slice(0, 7), periodo: u.periodo || '' };
    let datos = { data: [], conteos: {} }, carga = 0;

    const guardar = (extra = {}) => url.poner({ periodo: st.periodo, q: st.q, estado: st.estado !== 'todas' ? st.estado : '', con_cupon: st.con_cupon, con_ventas: st.con_ventas,
        orden: st.orden !== 'recientes' ? st.orden : '', page: st.page > 1 ? st.page : '', vista: st.vista !== 'tarjetas' ? st.vista : '', mes: st.vista === 'calendario' ? st.mes : '', ...extra });

    // Burbuja de WhatsApp: "Vista previa" (borde punteado) vs. mensaje enviado (palomitas y hora).
    function wa({ texto, imagen, enviado = null }) {
        return `<div class="mk-wa"><span class="mk-badge ${enviado ? 'ok' : 'neutral'} etq">${enviado ? 'Enviado ' + esc(enviado) : 'Vista previa'}</span>
            <div class="mk-wa-b ${enviado ? '' : 'borrador'}" style="margin-top:18px">${imagen ? `<img src="${esc(imagen)}" alt="">` : ''}
              <div class="mk-wa-t">${esc(texto)}</div><div class="mk-wa-h">${enviado ? esc(enviado.split(' ')[1] || '') + ic('check2') : 'sin enviar'}</div>
              <div class="mk-wa-btn">${ic('externo')}${esc(BOTON)}</div>${BAJA ? '<div class="mk-wa-btn">Ya no quiero promociones</div>' : ''}</div></div>`;
    }

    async function cargar() {
        const yo = ++carga;
        if (st.vista === 'calendario') return calendario(yo);
        $('kLista').innerHTML = `<div class="mk-camps" aria-busy="true">${'<div class="mk-card" style="height:250px"><div class="mk-sk" style="height:112px;border-radius:10px 10px 0 0"></div><div style="padding:12px"><div class="mk-sk" style="height:16px;width:70%;margin-bottom:8px"></div><div class="mk-sk" style="height:12px;width:50%"></div></div></div>'.repeat(4)}</div>`;
        const p = new URLSearchParams({ estado: st.estado, orden: st.orden, page: st.page });
        if (st.q) p.set('q', st.q);
        if (st.con_cupon) p.set('con_cupon', 1);
        if (st.con_ventas) p.set('con_ventas', 1);
        try {
            const d = await api(R.lista + '?' + p);
            if (yo !== carga) return;
            datos = d; pintar();
        } catch (e) { if (yo === carga) { $('kLista').innerHTML = error(e.message); $('kPag').innerHTML = ''; } }
    }

    function chips() {
        $('kChips').innerHTML = GRUPOS.map(([k, t]) => `<button type="button" class="mk-chip" data-estado="${k}" aria-pressed="${st.estado === k}">${t}<span class="n">${datos.conteos[k] ?? ''}</span></button>`).join('');
        const a = [];
        if (st.q) a.push(['q', `“${st.q}”`]);
        if (st.con_cupon) a.push(['con_cupon', 'Con cupón']);
        if (st.con_ventas) a.push(['con_ventas', 'Con ventas']);
        $('kAplicados').innerHTML = a.map(([k, t]) => `<span class="mk-chip aplicado">${esc(t)}<button type="button" data-quitar="${k}" aria-label="Quitar filtro ${esc(t)}">${ic('x')}</button></span>`).join('');
        const n = a.filter(x => x[0] !== 'q').length; $('kNFiltros').hidden = !n; $('kNFiltros').textContent = n;
        document.querySelectorAll('[data-vista]').forEach(b => b.setAttribute('aria-pressed', b.dataset.vista === st.vista));
    }

    function tarjeta(c) {
        const pct = c.destinatarios ? Math.round(c.salieron / c.destinatarios * 100) : 0;
        return `<article class="mk-camp" data-id="${c.id}" tabindex="0" aria-label="Campaña ${esc(c.nombre)}, ${esc(c.estado_label)}">
            <div class="img">${c.imagen ? `<img src="${esc(c.imagen)}" alt="" loading="lazy">` : ic('megafono')}<span class="mk-badge ${c.tono}">${esc(c.estado_label)}</span></div>
            <div class="b">
              <div class="n"><span>${esc(c.nombre)}</span>${c.cupon_codigo ? `<span class="mk-pill" data-tip-mk="Cupón">${ic('tag')}${esc(c.cupon_codigo)}</span>` : ''}</div>
              <div class="meta"><span>${ic('usuarios')}${c.destinatarios ? `<b style="color:var(--text)">${c.destinatarios}</b>&nbsp;destinatarios` : esc(c.segmento)}</span>
                <span>${ic('cal')}${esc(c.fecha_label)} ${esc(c.fecha)}</span></div>
              ${c.destinatarios ? `<div><div class="mk-bar-uso" role="progressbar" aria-label="Progreso de envío" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><i style="width:${pct}%"></i></div>
                <div class="mk-muted" style="font-size:11.5px;margin-top:3px">${c.pendientes ? c.pendientes + ' en cola · ' : ''}${c.enviados} enviados${c.simulados ? ' · ' + c.simulados + ' simulados' : ''}</div></div>` : ''}
              <div class="stats"><span data-tip-mk="Clientes que abrieron el enlace">${ic('clic')} <b>${c.clics}</b> clics</span><span>${ic('ventas')} <b>${c.ventas}</b> ventas</span>${c.ingresos ? `<span><b>${money(c.ingresos, 0)}</b></span>` : ''}</div>
            </div></article>`;
    }

    function pintar() {
        chips();
        $('kTotal').textContent = datos.total === 1 ? '1 campaña' : `${datos.total} campañas`;
        if (!datos.data.length) {
            const filtrado = st.q || st.con_cupon || st.con_ventas || st.estado !== 'todas';
            $('kLista').innerHTML = `<div class="mk-card">${filtrado
                ? vacio({ icono: 'busca', titulo: 'Sin resultados', texto: 'Prueba con otra búsqueda o quita filtros.', boton: 'Quitar filtros', accion: 'limpiar' })
                : vacio({ icono: 'megafono', titulo: 'Crea tu primera campaña', boton: PUEDE.gestionar ? 'Crear campaña' : '', accion: 'nueva' })}</div>`;
            $('kPag').innerHTML = '';
            return;
        }
        $('kLista').innerHTML = `<div class="mk-camps mk-fade">${datos.data.map(tarjeta).join('')}</div>`;
        $('kPag').innerHTML = datos.paginas > 1 ? `<span>Página ${datos.pagina} de ${datos.paginas}</span><div class="btns">
            <button type="button" class="mk-btn sec sm" data-pag="-1" ${datos.pagina <= 1 ? 'disabled' : ''} aria-label="Página anterior">${ic('atras')}</button>
            <button type="button" class="mk-btn sec sm" data-pag="1" ${datos.pagina >= datos.paginas ? 'disabled' : ''} aria-label="Página siguiente">${ic('flecha')}</button></div>` : '';
    }

    // ---------------- Calendario ----------------
    async function calendario(yo) {
        chips();
        $('kPag').innerHTML = '';
        $('kLista').innerHTML = `<div class="mk-card"><div class="mk-sk" style="height:420px;border-radius:10px"></div></div>`;
        try {
            const d = await api(R.cal + '?mes=' + st.mes);
            if (yo !== carga) return;
            const [a, m] = st.mes.split('-').map(Number);
            const primero = new Date(a, m - 1, 1), dias = new Date(a, m, 0).getDate();
            const inicio = (primero.getDay() + 6) % 7; // lunes primero
            const hoy = new Date().toISOString().slice(0, 10);
            const porDia = {};
            d.eventos.forEach(e => (porDia[e.dia] = porDia[e.dia] || []).push(e));
            let celdas = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'].map(x => `<div class="dow">${x}</div>`).join('');
            for (let i = 0; i < inicio; i++) celdas += '<div class="d fuera"></div>';
            for (let n = 1; n <= dias; n++) {
                const k = `${st.mes}-${String(n).padStart(2, '0')}`;
                celdas += `<div class="d ${k === hoy ? 'hoy' : ''}"><span class="num">${n}</span>${(porDia[k] || []).map(e =>
                    `<button type="button" class="ev ${e.tipo}" data-id="${e.id}" title="${esc(e.hora + ' · ' + e.nombre + ' · ' + e.estado_label)}">${esc(e.hora)} ${esc(e.nombre)}</button>`).join('')}</div>`;
            }
            const resto = (7 - ((inicio + dias) % 7)) % 7;
            for (let i = 0; i < resto; i++) celdas += '<div class="d fuera"></div>';
            $('kLista').innerHTML = `<div class="mk-card mk-fade"><div class="mk-card-h"><h3>${ic('cal')}${esc(d.titulo)}</h3>
                <div style="display:flex;gap:4px;align-items:center"><span class="mk-muted" style="font-size:12px;margin-right:8px"><span class="mk-badge info">Programada</span> <span class="mk-badge ok">Enviada</span></span>
                <button type="button" class="mk-ico bordered" data-mes="-1" aria-label="Mes anterior">${ic('atras')}</button><button type="button" class="mk-btn sec sm" data-mes="0">Hoy</button>
                <button type="button" class="mk-ico bordered" data-mes="1" aria-label="Mes siguiente">${ic('flecha')}</button></div></div>
                <div class="mk-cal">${celdas}</div>${d.eventos.length ? '' : `<p class="mk-hint" style="padding:12px 14px">Sin envíos este mes.</p>`}</div>`;
            $('kTotal').textContent = d.eventos.length === 1 ? '1 envío en el mes' : `${d.eventos.length} envíos en el mes`;
        } catch (e) { $('kLista').innerHTML = error(e.message); }
    }

    // ---------------- Eventos ----------------
    $('kQ').value = st.q;
    $('kQ').addEventListener('input', debounce(e => { st.q = e.target.value.trim(); st.page = 1; guardar(); cargar(); }, 300));
    $('kChips').addEventListener('click', e => { const b = e.target.closest('[data-estado]'); if (!b) return; st.estado = b.dataset.estado; st.page = 1; if (st.vista === 'calendario') st.vista = 'tarjetas'; guardar(); cargar(); });
    document.querySelectorAll('[data-vista]').forEach(b => b.addEventListener('click', () => { st.vista = b.dataset.vista; try { localStorage.setItem('mk-camp-vista', st.vista); } catch (e) {} guardar(); cargar(); }));
    $('kAplicados').addEventListener('click', e => { const b = e.target.closest('[data-quitar]'); if (!b) return; const k = b.dataset.quitar;
        if (k === 'q') { st.q = ''; $('kQ').value = ''; } else st[k] = false; st.page = 1; guardar(); cargar(); });
    $('kLista').addEventListener('click', e => {
        const a = e.target.closest('[data-accion]');
        if (a) { if (a.dataset.accion === 'reintentar') cargar(); if (a.dataset.accion === 'nueva') location.href = R.crear;
            if (a.dataset.accion === 'limpiar') { Object.assign(st, { q: '', con_cupon: false, con_ventas: false, estado: 'todas', page: 1 }); $('kQ').value = ''; guardar(); cargar(); } return; }
        const m = e.target.closest('[data-mes]');
        if (m) { const [y, mo] = st.mes.split('-').map(Number); const d = +m.dataset.mes === 0 ? new Date() : new Date(y, mo - 1 + +m.dataset.mes, 1);
            st.mes = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`; guardar(); cargar(); return; }
        const c = e.target.closest('[data-id]');
        if (c) verDetalle(+c.dataset.id, c);
    });
    $('kLista').addEventListener('keydown', e => { if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('.mk-camp')) { e.preventDefault(); verDetalle(+e.target.dataset.id, e.target); } });
    $('kPag').addEventListener('click', e => { const b = e.target.closest('[data-pag]'); if (!b) return; st.page += +b.dataset.pag; guardar(); cargar(); });

    $('kBtnFiltros').addEventListener('click', e => { $('kf_cupon').checked = st.con_cupon; $('kf_ventas').checked = st.con_ventas; $('kf_orden').value = st.orden; abrir($('kFiltros'), e.currentTarget); });
    $('kFAplicar').addEventListener('click', () => { st.con_cupon = $('kf_cupon').checked; st.con_ventas = $('kf_ventas').checked; st.orden = $('kf_orden').value; st.page = 1; cerrar($('kFiltros')); guardar(); cargar(); });
    $('kFLimpiar').addEventListener('click', () => { Object.assign(st, { con_cupon: false, con_ventas: false, orden: 'recientes', page: 1 }); cerrar($('kFiltros')); guardar(); cargar(); });

    // ---------------- Detalle ----------------
    let det = null, detId = null;
    async function verDetalle(id, origen) {
        detId = id; guardar({ ver: id });
        $('kDetC').innerHTML = `<div class="mk-dh"><h2 id="kDetT"><span class="mk-sk" style="width:160px;height:18px;display:inline-block"></span></h2><button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar">${ic('x')}</button></div>
            <div class="mk-db"><div class="mk-sk" style="height:220px;margin-bottom:12px"></div><div class="mk-sk" style="height:120px"></div></div>`;
        abrir($('kDet'), origen);
        try { det = await api(R.det(id)); pintarDetalle('resumen'); }
        catch (e) { $('kDetC').querySelector('.mk-db').innerHTML = error(e.message); }
    }
    $('kDet').addEventListener('close', () => guardar());

    function pintarDetalle(pestana) {
        const c = det, r = c.resultados, p = c.puede;
        const kpi = (v, t) => `<div><b class="mk-num">${v}</b><span>${t}</span></div>`;
        const acc = [];
        acc.push(`<a class="mk-btn sm" href="${c.urls.detalle}">${ic('externo')}Abrir detalle</a>`);
        if (PUEDE.gestionar && p.editar) acc.push(`<a class="mk-btn sec sm" href="${c.urls.editar}">${ic('editar')}Editar</a>`);
        if (PUEDE.enviar && p.enviar) acc.push(`<a class="mk-btn sec sm" href="${c.urls.detalle}#enviar">${ic('cal')}Programar o enviar</a>`);
        if (PUEDE.enviar && p.pausar) acc.push(`<button type="button" class="mk-btn sec sm" data-k="pausar">${ic('pausa')}Pausar</button>`);
        if (PUEDE.enviar && p.reanudar) acc.push(`<button type="button" class="mk-btn sec sm" data-k="reanudar">${ic('play')}Reanudar</button>`);
        if (PUEDE.gestionar) acc.push(`<button type="button" class="mk-btn sec sm" data-k="duplicar">${ic('duplicar')}Duplicar</button>`);
        if (PUEDE.gestionar && p.cancelar) acc.push(`<button type="button" class="mk-btn ghost sm danger" data-k="cancelar">${ic('cancelar')}Cancelar</button>`);
        const enviado = c.fecha_label === 'Enviada' ? c.fecha : null;
        $('kDetC').innerHTML = `
          <div class="mk-dh"><h2 id="kDetT">${esc(c.nombre)}</h2><span class="mk-badge ${c.tono}">${esc(c.estado_label)}</span><button type="button" class="mk-ico" data-mk-cerrar aria-label="Cerrar">${ic('x')}</button></div>
          <div class="mk-db">
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">${acc.join('')}</div>
            <div class="mk-itabs" role="tablist">${[['resumen', 'Resumen'], ['mensaje', 'Mensaje'], ['oferta', 'Oferta'], ['actividad', 'Actividad']].map(([k, t]) => `<button type="button" role="tab" data-ptab="${k}" aria-selected="${k === pestana}">${t}</button>`).join('')}</div>
            <div data-pp="resumen" ${pestana !== 'resumen' ? 'hidden' : ''}>
              ${r.destinatarios ? `<div class="mk-mini">${kpi(r.destinatarios, 'destinatarios')}${kpi(r.enviados, 'enviados')}${kpi(r.entregados, 'entregados')}${kpi(r.leidos, 'leídos')}${kpi(r.fallidos, 'fallidos')}${kpi(r.clics_unicos, 'abrieron el enlace')}</div>
                 <div class="mk-mini">${kpi(r.pedidos, 'pedidos')}${kpi(r.cupones_usados, 'cupones usados')}${kpi(money(r.ingresos, 0), 'en ' + r.ventas + ' venta(s)')}</div>
                 ${r.simulados ? `<p class="mk-hint">${r.simulados} envío(s) simulados: no salieron a WhatsApp.</p>` : ''}
                 <p class="mk-hint">Entregados y leídos llegan por el webhook de WhatsApp.</p>`
                : vacio({ icono: 'enviar', titulo: 'Aún no se envía', texto: c.segmento })}
              <ul class="mk-rows" style="margin-top:8px"><li><span class="k">${esc(c.fecha_label)}</span><span class="v">${esc(c.fecha)}</span></li><li><span class="k">Audiencia</span><span class="v">${esc(c.segmento)}</span></li></ul>
            </div>
            <div data-pp="mensaje" ${pestana !== 'mensaje' ? 'hidden' : ''}>${wa({ texto: c.vista_previa, imagen: c.imagen, enviado })}
              <p class="mk-hint" style="margin-top:8px">${enviado ? 'Así se envió (con los datos de cada cliente).' : 'Así se verá; cada cliente recibe su nombre y su código.'}</p></div>
            <div data-pp="oferta" ${pestana !== 'oferta' ? 'hidden' : ''}>
              <ul class="mk-rows"><li><span class="k">Cupón</span><span class="v">${c.cupon ? `<span class="mk-code">${esc(c.cupon.codigo)}</span> · ${esc(c.cupon.descuento)}${c.cupon.personal ? ' · personal' : ''}` : 'Sin cupón'}</span></li>
                <li><span class="k">Vigencia</span><span class="v">${esc(c.vigencia)}</span></li></ul>
              ${c.productos.map(x => `<div style="display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">${x.imagen ? `<img src="${esc(x.imagen)}" alt="" style="width:38px;height:38px;border-radius:8px;object-fit:cover">` : ''}
                 <span style="flex:1;font-size:13px"><b>${esc(x.nombre)}</b><br><small class="mk-muted">${esc(x.modelo || '')}</small></span><span class="mk-num" style="font-size:13px">${money(x.precio)}</span></div>`).join('')}
            </div>
            <div data-pp="actividad" ${pestana !== 'actividad' ? 'hidden' : ''}><ul class="mk-tl">${c.actividad.map(x => `<li>${esc(x.texto)}<small>${esc(x.fecha)} · ${esc(x.quien)}</small></li>`).join('')}</ul></div>
          </div>`;
    }
    $('kDet').addEventListener('click', async e => {
        const t = e.target.closest('[data-ptab]');
        if (t) { $('kDet').querySelectorAll('[data-ptab]').forEach(b => b.setAttribute('aria-selected', b === t)); $('kDet').querySelectorAll('[data-pp]').forEach(p => p.hidden = p.dataset.pp !== t.dataset.ptab); return; }
        if (e.target.closest('[data-accion="reintentar"]')) return verDetalle(detId);
        const b = e.target.closest('[data-k]'); if (!b) return;
        const k = b.dataset.k;
        const msj = { pausar: ['Pausar campaña', 'Los mensajes que faltan se quedan en espera hasta reanudar.', 'Pausar', false],
            reanudar: ['Reanudar campaña', 'Se retoma el envío de los mensajes pendientes.', 'Reanudar', false],
            cancelar: ['Cancelar campaña', 'Los mensajes que aún no salen ya no se enviarán y el enlace dejará de aceptar pedidos.', 'Cancelar campaña', true],
            duplicar: ['Duplicar campaña', 'Se crea un borrador con el mismo contenido y destinatarios.', 'Duplicar', false] }[k];
        if (!(await confirmar({ title: msj[0], message: msj[1], confirmText: msj[2], cancelText: 'Volver', danger: msj[3] }))) return;
        b.disabled = true;
        try {
            const res = await api(det.urls[k], { method: 'POST' });
            if (k === 'duplicar' && res.url) { location.href = res.url; return; }
            toast(res.mensaje); cargar(); verDetalle(detId);
        } catch (er) { toast(er.message, 'warn'); b.disabled = false; }
    });

    cargar().then(() => { if (u.ver) verDetalle(+u.ver); });
})();
</script>
@endsection
