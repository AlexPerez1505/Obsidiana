{{--
    Aviso de cliente repetido.

    Se abre en dos momentos: en vivo, al salir del campo de teléfono o
    correo (consulta a clientes.similar), y al regresar del servidor
    cuando el guardado se detuvo porque ya existía (session cliente_similar).
    En los dos casos se pintan los datos del cliente que ya está, para que
    quien captura sepa de quién se trata y no lo registre dos veces.

    Espera en la página los campos #telefono y #gmail. Si se está editando,
    se manda $ignorar con el id del propio cliente.
--}}

<dialog class="dup-modal" id="modalDuplicado" aria-labelledby="modalDuplicadoT">
    <div class="dup-box">
        <div class="dup-head">
            <span class="dup-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
            <div style="min-width:0; flex:1;">
                <h3 id="modalDuplicadoT">Este cliente ya está registrado</h3>
                <p class="dup-msg" data-dup-mensaje></p>
            </div>
            <button type="button" class="dup-x" data-dup-cerrar aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="dup-cliente">
            <div class="dup-cliente-top">
                <span class="dup-avatar" data-dup-iniciales></span>
                <div style="min-width:0;">
                    <div class="dup-nombre" data-dup-nombre></div>
                    <div class="dup-sub"><span data-dup-categoria></span> <span class="dup-estado" data-dup-estado></span></div>
                </div>
            </div>
            <dl class="dup-datos">
                <div><dt>Teléfono</dt><dd data-dup-telefono></dd></div>
                <div><dt>Correo</dt><dd data-dup-correo></dd></div>
                <div><dt>Dirección</dt><dd data-dup-direccion></dd></div>
                <div><dt>Lo conocimos</dt><dd data-dup-conocido></dd></div>
                <div><dt>Asesor</dt><dd data-dup-asesor></dd></div>
                <div><dt>Alta</dt><dd data-dup-alta></dd></div>
            </dl>
            <p class="dup-nota" data-dup-sin-acceso hidden>Este cliente lo registró otro asesor: pídele a él o a un administrador que lo atienda.</p>
        </div>

        <div class="dup-foot">
            <button type="button" class="btn btn--ghost" data-dup-cerrar>Corregir los datos</button>
            <a class="btn" data-dup-ver href="#" hidden>Ver cliente</a>
        </div>
    </div>
</dialog>

<style>
    .dup-modal { border:none; border-radius:16px; padding:0; width:min(560px, calc(100vw - 32px)); background:var(--surface, #fff); color:var(--text, #1f2937); box-shadow:0 24px 64px rgba(0,0,0,.28); }
    .dup-modal::backdrop { background:rgba(15,23,42,.55); }
    .dup-box { display:flex; flex-direction:column; }
    .dup-head { display:flex; align-items:flex-start; gap:12px; padding:18px 18px 12px; }
    .dup-head h3 { margin:0 0 4px; font-size:17px; }
    .dup-ico { width:38px; height:38px; border-radius:11px; background:rgba(245,158,11,.15); color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .dup-ico svg { width:20px; height:20px; }
    .dup-msg { margin:0; color:var(--muted, #6b7280); font-size:13.5px; }
    .dup-x { border:none; background:transparent; cursor:pointer; color:var(--muted, #6b7280); width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .dup-x:hover { background:var(--surface-2, #f3f4f6); }
    .dup-x svg { width:18px; height:18px; }
    .dup-cliente { margin:0 18px; border:1px solid var(--border, #e5e7eb); border-radius:12px; padding:14px; background:var(--surface-2, #f9fafb); }
    .dup-cliente-top { display:flex; align-items:center; gap:12px; margin-bottom:12px; }
    .dup-avatar { width:42px; height:42px; border-radius:50%; background:var(--primary-soft, #dbeafe); color:var(--primary, #1d4ed8); font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .dup-nombre { font-weight:700; font-size:15px; }
    .dup-sub { font-size:12.5px; color:var(--muted, #6b7280); }
    .dup-estado { display:inline-block; margin-left:6px; padding:1px 7px; border-radius:999px; font-size:11px; font-weight:700; background:rgba(34,197,94,.14); color:#15803d; }
    .dup-estado.is-off { background:rgba(239,68,68,.12); color:#b91c1c; }
    .dup-datos { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px 14px; margin:0; }
    .dup-datos div { min-width:0; }
    .dup-datos dt { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted, #6b7280); font-weight:700; }
    .dup-datos dd { margin:2px 0 0; font-size:13.5px; overflow-wrap:anywhere; }
    .dup-nota { margin:12px 0 0; font-size:12.5px; color:var(--muted, #6b7280); }
    .dup-foot { display:flex; justify-content:flex-end; gap:10px; padding:16px 18px 18px; }
    @media (max-width: 480px) { .dup-datos { grid-template-columns:1fr; } }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modalDuplicado');
        const telefono = document.getElementById('telefono');
        const correo = document.getElementById('gmail');

        if (!modal || !telefono) return;

        const URL_SIMILAR = @json(route('commercial.clientes.similar'));
        const IGNORAR = @json($ignorar ?? null);
        const DESDE_SERVIDOR = @json(session('cliente_similar'));

        const pon = (llave, valor) => {
            const el = modal.querySelector('[data-dup-' + llave + ']');
            if (el) el.textContent = valor || '—';
        };

        function iniciales(nombre) {
            const partes = (nombre || '').trim().split(/\s+/).filter(Boolean);
            if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
            return partes.length ? partes[0].slice(0, 2).toUpperCase() : 'CL';
        }

        function abrir(aviso) {
            const c = aviso.cliente || {};

            pon('mensaje', aviso.mensaje);
            pon('iniciales', iniciales(c.nombre));
            pon('nombre', c.nombre);
            pon('categoria', c.categoria || 'Sin categoría');
            pon('telefono', c.telefono);
            pon('correo', c.correo);
            pon('direccion', c.direccion);
            pon('conocido', c.conocido);
            pon('asesor', c.asesor || 'Sin asesor');
            pon('alta', c.alta);

            const estado = modal.querySelector('[data-dup-estado]');
            estado.textContent = c.activo === false ? 'Inactivo' : 'Activo';
            estado.classList.toggle('is-off', c.activo === false);

            const ver = modal.querySelector('[data-dup-ver]');
            ver.hidden = !c.url;
            if (c.url) ver.href = c.url;
            modal.querySelector('[data-dup-sin-acceso]').hidden = !!c.url;

            // Se marca en rojo el campo que chocó, para que se vea cuál corregir.
            const campo = aviso.motivo === 'correo' ? correo : telefono;
            if (campo) campo.setCustomValidity(aviso.mensaje || 'Ya existe un cliente con este dato.');

            if (!modal.open) modal.showModal();
        }

        function cerrar() {
            if (modal.open) modal.close();
            const campo = document.activeElement;
            // Al cerrar se regresa el foco al campo que causó el aviso.
            const objetivo = modal.dataset.campo === 'correo' ? correo : telefono;
            if (objetivo && campo !== objetivo) objetivo.focus();
        }

        modal.querySelectorAll('[data-dup-cerrar]').forEach(b => b.addEventListener('click', cerrar));
        modal.addEventListener('click', e => { if (e.target === modal) cerrar(); });

        // Al escribir de nuevo se quita la marca de error: se volverá a
        // revisar al salir del campo.
        [telefono, correo].forEach(campo => {
            if (campo) campo.addEventListener('input', () => campo.setCustomValidity(''));
        });

        let ultimaConsulta = 0;

        async function revisar() {
            const tel = telefono.value.trim();
            const mail = correo ? correo.value.trim() : '';
            if (!tel && !mail) return;

            const params = new URLSearchParams({ telefono: tel, gmail: mail });
            if (IGNORAR) params.set('ignorar', IGNORAR);

            const consulta = ++ultimaConsulta;

            try {
                const r = await fetch(URL_SIMILAR + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) return;
                const json = await r.json();
                // Si el usuario siguió escribiendo, esta respuesta ya es vieja.
                if (consulta !== ultimaConsulta) return;
                if (json.similar) {
                    modal.dataset.campo = json.similar.motivo;
                    abrir(json.similar);
                }
            } catch (e) {
                // Sin red no se avisa en vivo; el servidor lo detiene al guardar.
            }
        }

        telefono.addEventListener('blur', revisar);
        if (correo) correo.addEventListener('blur', revisar);

        if (DESDE_SERVIDOR) {
            modal.dataset.campo = DESDE_SERVIDOR.motivo;
            abrir(DESDE_SERVIDOR);
        }
    });
</script>
