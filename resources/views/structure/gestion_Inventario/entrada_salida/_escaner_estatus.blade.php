{{--
    Status rápido del equipo en Entrada y salida.

    Se dispara de tres formas, sin tener que ir a otra pantalla:
      • La pistola lectora en cualquier momento (teclea muy rápido y cierra
        con Enter), siempre que no se esté escribiendo en un campo.
      • Escribiendo o pegando la etiqueta (MB-000123) en el buscador + Enter.
      • El botón "Escanear": con la cámara del celular (en vivo o con una
        foto del QR, partials.lector_qr) o tecleando la etiqueta.
    Lo que sale: si se puede vender, qué le falta, si ya se vendió (a quién y
    cómo va su salida) o si está en un congreso, con ligas a cada cosa.
--}}
<dialog id="mvScan" class="mv-scan" aria-labelledby="mvScanT">
    <div class="mv-scan-head">
        <h3 id="mvScanT">Status del equipo</h3>
        <button type="button" class="mv-scan-x" data-scan-cerrar aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <form class="mv-scan-busca" data-scan-form>
        <input type="text" data-scan-input placeholder="Escanea o escribe la etiqueta (MB-000123)" autocomplete="off" data-no-mayus aria-label="Etiqueta del equipo">
        <button type="submit" class="btn">Ver</button>
    </form>
    <div class="mv-scan-camara">
        @include('partials.lector_qr', ['id' => 'mv-scan'])
    </div>

    <div class="mv-scan-cuerpo" data-scan-cuerpo>
        <p class="mv-scan-vacio">Dispara la pistola, usa la cámara del celular o escribe el código. Puedes escanear uno tras otro sin cerrar esta ventana.</p>
    </div>
</dialog>

<style>
    .mv-scan { width:min(520px, calc(100vw - 24px)); max-height:calc(100vh - 32px); padding:0; border:1px solid var(--border);
               border-radius:16px; background:var(--surface); color:var(--text); overflow:auto; }
    .mv-scan::backdrop { background:rgba(15,23,42,.45); }
    .mv-scan-head { display:flex; align-items:center; justify-content:space-between; padding:16px 18px 0; }
    .mv-scan-head h3 { margin:0; font-size:17px; }
    .mv-scan-x { border:0; background:transparent; color:var(--muted); cursor:pointer; padding:4px; border-radius:8px; }
    .mv-scan-x svg { width:18px; height:18px; display:block; }
    .mv-scan-busca { display:flex; gap:8px; padding:12px 18px; flex-wrap:wrap; }
    .mv-scan-busca input { flex:1 1 180px; min-width:0; padding:9px 12px; border:1px solid var(--border); border-radius:10px;
                           background:var(--surface-2); color:var(--text); font-size:15px; letter-spacing:.02em; }
    .mv-scan-camara { padding:0 18px 12px; }
    .mv-scan [hidden] { display:none !important; }
    .mv-scan-cuerpo { padding:0 18px 18px; }
    .mv-scan-vacio { margin:6px 0 0; color:var(--muted); font-size:13.5px; }
    .mv-scan-err { margin:6px 0 0; padding:10px 12px; border-radius:10px; background:var(--danger-soft); color:var(--danger); font-weight:600; font-size:14px; }

    .mv-scan-pieza { display:flex; gap:12px; align-items:flex-start; }
    .mv-scan-foto { width:72px; height:72px; flex:0 0 72px; border-radius:12px; object-fit:cover; background:var(--surface-2); }
    .mv-scan-nom { font-weight:700; font-size:15px; overflow-wrap:anywhere; }
    .mv-scan-sub { color:var(--muted); font-size:13px; overflow-wrap:anywhere; }
    .mv-scan-cod { font-family:ui-monospace, Menlo, Consolas, monospace; font-size:12.5px; color:var(--muted); }
    .mv-scan-res { margin:12px 0 0; padding:10px 12px; border-radius:10px; font-weight:600; font-size:14px; line-height:1.45; }
    .mv-scan-res.ok { background:var(--green-soft); color:var(--green); }
    .mv-scan-res.warn { background:var(--accent-soft); color:var(--accent); }
    .mv-scan-res.info { background:var(--primary-soft); color:var(--primary); }
    .mv-scan-res.danger { background:var(--danger-soft); color:var(--danger); }
    .mv-scan-datos { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:8px 14px; margin:12px 0 0; }
    .mv-scan-datos dt { color:var(--muted); font-size:12px; }
    .mv-scan-datos dd { margin:1px 0 0; font-weight:600; font-size:13.5px; overflow-wrap:anywhere; }
    .mv-scan-pasos { display:flex; flex-wrap:wrap; gap:6px; margin:10px 0 0; }
    .mv-scan-paso { padding:3px 9px; border-radius:999px; font-size:12px; border:1px solid var(--border); color:var(--muted); }
    .mv-scan-paso.terminado { background:var(--green-soft); color:var(--green); border-color:transparent; }
    .mv-scan-paso.en_curso { background:var(--accent-soft); color:var(--accent); border-color:transparent; }
    .mv-scan-ligas { display:flex; flex-wrap:wrap; gap:8px; margin:14px 0 0; }
    @media (max-width:480px) { .mv-scan-datos { grid-template-columns:1fr; } }
</style>

<script>
(function () {
    const dlg = document.getElementById('mvScan');
    if (!dlg) return;
    const url = @json(route('inventory.movimientos.estatus'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const input = dlg.querySelector('[data-scan-input]');
    const cuerpo = dlg.querySelector('[data-scan-cuerpo]');
    const camara = dlg.querySelector('[data-lectorqr]');
    const ETIQUETA = /[A-Za-z]{2,6}-\d{4,10}/;

    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // En el celular no se enfoca el campo: el teclado taparía la cámara.
    const esTactil = window.matchMedia('(pointer: coarse)').matches;
    function enfocar() { if (!esTactil) input.focus(); }
    function abrir() {
        if (!dlg.open) dlg.showModal();
        setTimeout(enfocar, 30);
    }
    function cerrar() { pararCamara(); dlg.close(); }

    dlg.querySelector('[data-scan-cerrar]').addEventListener('click', cerrar);
    dlg.addEventListener('click', e => { if (e.target === dlg) cerrar(); });
    dlg.addEventListener('close', pararCamara);
    document.querySelectorAll('[data-scan-abrir]').forEach(b => b.addEventListener('click', abrir));

    dlg.querySelector('[data-scan-form]').addEventListener('submit', e => {
        e.preventDefault();
        const v = input.value.trim();
        if (v) { input.value = ''; consultar(v); }
    });

    async function consultar(leido) {
        abrir();
        cuerpo.innerHTML = '<p class="mv-scan-vacio">Buscando ' + esc(leido) + '…</p>';
        try {
            const r = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ codigo: leido }),
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            pintar(await r.json());
        } catch (err) {
            cuerpo.innerHTML = '<p class="mv-scan-err">No se pudo consultar. Revisa la conexión e inténtalo otra vez.</p>';
        }
        enfocar();
    }

    function pintar(d) {
        if (!d.encontrado) {
            cuerpo.innerHTML = '<p class="mv-scan-err">' + esc(d.mensaje || 'No se encontró.') + '</p>';
            return;
        }
        const dato = (t, v) => v ? '<div><dt>' + esc(t) + '</dt><dd>' + esc(v) + '</dd></div>' : '';
        const liga = (t, u, prim) => u ? '<a class="btn ' + (prim ? '' : 'btn--ghost') + '" href="' + esc(u) + '"' + (prim ? '' : '') + '>' + esc(t) + '</a>' : '';
        const pasos = (d.procesos || []).map(p => '<span class="mv-scan-paso ' + esc(p.estado) + '">' + esc(p.nombre) + ' · ' + esc({ pendiente: 'pendiente', en_curso: 'en curso', terminado: 'terminado', omitido: 'omitido' }[p.estado] || p.estado) + '</span>').join('');

        cuerpo.innerHTML =
            '<div class="mv-scan-pieza">'
            + (d.foto ? '<img class="mv-scan-foto" src="' + esc(d.foto) + '" alt="">' : '<span class="mv-scan-foto" aria-hidden="true"></span>')
            + '<div style="min-width:0;"><div class="mv-scan-nom">' + esc(d.equipo) + '</div>'
            + (d.marca_modelo ? '<div class="mv-scan-sub">' + esc(d.marca_modelo) + '</div>' : '')
            + '<div class="mv-scan-cod">' + esc(d.codigo) + (d.no_serie ? ' · Serie ' + esc(d.no_serie) : '') + '</div></div></div>'
            + '<p class="mv-scan-res ' + esc(d.tono) + '" role="status">' + esc(d.resumen.toLowerCase().startsWith(String(d.estado_label).toLowerCase()) ? d.resumen : d.estado_label + ' — ' + d.resumen) + '</p>'
            + (pasos ? '<div class="mv-scan-pasos">' + pasos + '</div>' : '')
            + '<dl class="mv-scan-datos">'
            + dato('Condición', d.condicion ? d.condicion.charAt(0).toUpperCase() + d.condicion.slice(1) : '')
            + dato('Almacén', d.almacen)
            + dato('Entrada', d.entrada ? d.entrada.folio + (d.entrada.fecha ? ' · ' + d.entrada.fecha : '') : '')
            + dato('Venta', d.venta ? d.venta.folio + (d.venta.cliente ? ' · ' + d.venta.cliente : '') : '')
            + dato('Orden de salida', d.salida ? d.salida.folio + ' · ' + d.salida.estado : '')
            + dato('Prepara', d.salida?.prepara)
            + dato('Congreso', d.congreso)
            + '</dl>'
            + '<div class="mv-scan-ligas">'
            + liga('Ver entrada', d.entrada?.url, true)
            + liga('Ver proceso', d.proceso_url)
            + liga('Ver venta', d.venta?.url)
            + liga('Orden de salida', d.salida?.url)
            + (d.ficha ? '<a class="btn btn--ghost" href="' + esc(d.ficha) + '" target="_blank" rel="noopener">Ficha pública</a>' : '')
            + '</div>';
    }

    /* ---------- Pistola: teclas muy rápidas que cierran con Enter ---------- */
    let buffer = '', ultima = 0;
    document.addEventListener('keydown', e => {
        const enCampo = typeof e.target?.matches === 'function' && e.target.matches('input, textarea, select, [contenteditable]');
        if (enCampo) return;              // el buscador y el campo del modal procesan su propio Enter
        if (document.querySelector('dialog[open]:not(#mvScan)')) return;
        const ahora = Date.now();
        if (ahora - ultima > 120) buffer = '';
        ultima = ahora;
        if (e.key === 'Enter') {
            const leido = buffer.trim();
            buffer = '';
            if (ETIQUETA.test(leido)) { e.preventDefault(); consultar(leido); }
            return;
        }
        if (e.key.length === 1) buffer += e.key;
    });

    /* ---------- El buscador de la tabla: si es una etiqueta, Enter da su status ---------- */
    const buscador = document.getElementById('fBuscar');
    buscador?.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        const v = buscador.value.trim();
        if (!ETIQUETA.test(v)) return;
        e.preventDefault();
        buscador.value = '';
        buscador.dispatchEvent(new Event('input', { bubbles: true }));
        consultar(v);
    });

    /* ---------- Cámara del celular (en vivo o foto del QR) ---------- */
    camara.addEventListener('lector-qr', e => consultar(e.detail.texto));

    function pararCamara() { if (window.LectorQR) LectorQR.detener(camara); }
})();
</script>
