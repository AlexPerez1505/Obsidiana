{{--
    Lector de QR con la cámara del celular (o de la compu), para cualquier
    pantalla que escanee etiquetas.

        @include('partials.lector_qr', ['id' => 'algo-unico'])

    Cuando lee un código dispara el evento "lector-qr" en el bloque
    [data-lectorqr], con event.detail.texto. La pantalla lo escucha y hace lo
    mismo que con la pistola. El mismo código no se repite antes de 2.5 s.

    Dos formas, igual que en Venta rápida:
      • "Cámara": video en vivo que detecta el QR solo. El navegador solo la
        permite en https (o localhost); por http se esconde y se ofrece abrir
        la misma pantalla por https.
      • "Foto del QR": abre la cámara nativa del teléfono, se toma la foto y
        se lee. Funciona también por http.
    Para apagar la cámara desde fuera: LectorQR.detener(bloque).
--}}
@php $lqId = 'lq-'.($id ?? uniqid()); @endphp
<div class="lq" data-lectorqr id="{{ $lqId }}">
    <div class="lq-btns">
        <button type="button" class="btn lq-cam" data-lq-camara>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            <span data-lq-camara-t>Escanear con cámara</span>
        </button>
        <label class="btn btn--ghost lq-foto">
            <input type="file" accept="image/*" capture="environment" data-lq-foto hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/></svg>
            Foto del QR
        </label>
    </div>

    <div class="lq-video" data-lq-video hidden>
        <div id="{{ $lqId }}-vivo"></div>
        <button type="button" class="lq-cerrar" data-lq-cerrar aria-label="Cerrar cámara">×</button>
        <p class="lq-tip">Apunta al QR de la etiqueta. Puedes escanear uno tras otro.</p>
    </div>
    <div id="{{ $lqId }}-foto" hidden></div>
    <p class="lq-msg" data-lq-msg hidden></p>

    {{-- Solo por http: ahí el navegador no deja usar la cámara en vivo. --}}
    <div class="lq-https" data-lq-https hidden>
        Para que el QR se detecte solo con la cámara, abre esta pantalla por HTTPS.
        <a class="btn btn--ghost" data-lq-https-link href="#">Abrir en HTTPS</a>
        @if (Route::has('publico.certificado'))
            <small>Si el teléfono marca "no seguro" la primera vez, <a data-lq-cert href="{{ route('publico.certificado') }}">instala el certificado</a>.</small>
        @endif
        <small>Mientras tanto, "Foto del QR" sí funciona.</small>
    </div>
</div>

@once
    <style>
        .lq { display:flex; flex-direction:column; gap:10px; }
        .lq-btns { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .lq-btns.una { grid-template-columns:1fr; }
        .lq-btns .btn { justify-content:center; gap:7px; cursor:pointer; }
        .lq-video { position:relative; border-radius:12px; overflow:hidden; background:#000; }
        .lq-video video { width:100% !important; display:block; max-height:320px; object-fit:cover; }
        .lq-cerrar { position:absolute; top:8px; right:8px; width:34px; height:34px; border:0; border-radius:50%; cursor:pointer;
                     background:rgba(0,0,0,.55); color:#fff; font-size:22px; line-height:1; z-index:2; }
        .lq-tip { margin:0; padding:8px 10px; font-size:12.5px; color:#e2e8f0; background:rgba(0,0,0,.6); }
        .lq-msg { margin:0; font-size:13px; font-weight:600; color:var(--danger, #dc2626); }
        .lq-msg.info { color:var(--muted, #64748b); font-weight:500; }
        .lq-https { display:flex; flex-direction:column; gap:6px; padding:10px 12px; border-radius:10px; font-size:13px;
                    background:var(--primary-soft, rgba(0,122,255,.1)); color:var(--text); }
        .lq-https .btn { align-self:flex-start; }
        .lq-https small { color:var(--muted); }
        .lq [hidden] { display:none !important; }
        @media (max-width:420px) { .lq-btns { grid-template-columns:1fr; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>
    <script>
    window.LectorQR = (function () {
        const lectores = new WeakMap();   // bloque -> Html5Qrcode en vivo

        function avisar(b, texto, tipo) {
            const m = b.querySelector('[data-lq-msg]');
            m.textContent = texto || '';
            m.className = 'lq-msg' + (tipo === 'info' ? ' info' : '');
            m.hidden = !texto;
        }

        function emitir(b, texto) {
            const ahora = Date.now();
            if (texto === b._lqUltimo && ahora - (b._lqEn || 0) < 2500) return;   // el mismo QR se lee muchas veces por segundo
            b._lqUltimo = texto; b._lqEn = ahora;
            avisar(b, '');
            if (navigator.vibrate) { try { navigator.vibrate(60); } catch (e) {} }
            b.dispatchEvent(new CustomEvent('lector-qr', { bubbles: true, detail: { texto } }));
        }

        async function abrir(b) {
            if (typeof Html5Qrcode === 'undefined') return avisar(b, 'El lector aún no carga. Espera un momento e intenta de nuevo.');
            const vivo = lectores.get(b);
            if (vivo && vivo.isScanning) return detener(b);
            b.querySelector('[data-lq-video]').hidden = false;
            const lector = vivo || new Html5Qrcode(b.id + '-vivo', { verbose: false, useBarCodeDetectorIfSupported: true });
            lectores.set(b, lector);
            try {
                await lector.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.7); return { width: s, height: s }; } },
                    texto => emitir(b, texto),
                    () => {}
                );
                b.querySelector('[data-lq-camara-t]').textContent = 'Detener cámara';
            } catch (e) {
                b.querySelector('[data-lq-video]').hidden = true;
                avisar(b, 'No se pudo abrir la cámara. Revisa el permiso del navegador, o usa "Foto del QR".');
            }
        }

        async function detener(b) {
            if (!b) return;
            const lector = lectores.get(b);
            b.querySelector('[data-lq-video]').hidden = true;
            b.querySelector('[data-lq-camara-t]').textContent = 'Escanear con cámara';
            if (lector && lector.isScanning) { try { await lector.stop(); } catch (e) {} }
        }

        async function foto(b, archivo) {
            if (typeof Html5Qrcode === 'undefined') return avisar(b, 'El lector aún no carga. Espera un momento e intenta de nuevo.');
            avisar(b, 'Leyendo la foto…', 'info');
            const lector = new Html5Qrcode(b.id + '-foto', { verbose: false, useBarCodeDetectorIfSupported: true });
            try {
                const r = await lector.scanFileV2(archivo, false);
                b._lqUltimo = null;            // una foto siempre cuenta, aunque sea el mismo código
                emitir(b, r.decodedText);
            } catch (e) {
                avisar(b, 'No se encontró un QR en la foto. Acércate más y que quede bien enfocado.');
            } finally {
                try { lector.clear(); } catch (e) {}
            }
        }

        function preparar(b) {
            if (b._lqListo) return;
            b._lqListo = true;
            if (!window.isSecureContext) {
                b.querySelector('[data-lq-camara]').hidden = true;
                b.querySelector('.lq-btns').classList.add('una');
                b.querySelector('[data-lq-https-link]').href = 'https://' + location.hostname + location.pathname + location.search;
                b.querySelector('[data-lq-https]').hidden = false;
            }
            b.querySelector('[data-lq-camara]').addEventListener('click', () => abrir(b));
            b.querySelector('[data-lq-cerrar]').addEventListener('click', () => detener(b));
            b.querySelector('[data-lq-foto]').addEventListener('change', function () {
                const a = this.files && this.files[0];
                this.value = '';
                if (a) foto(b, a);
            });
        }

        function prepararTodo() { document.querySelectorAll('[data-lectorqr]').forEach(preparar); }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', prepararTodo); else prepararTodo();
        document.addEventListener('obs:vivo', prepararTodo);
        // Al salir de la pantalla se apaga la cámara.
        window.addEventListener('pagehide', () => document.querySelectorAll('[data-lectorqr]').forEach(detener));

        return { detener, preparar };
    })();
    </script>
@endonce
