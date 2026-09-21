@extends('layouts.dashboard')

@section('title', 'Venta rápida')
@section('page-title', 'Venta rápida')
@section('page-sub', 'Escanea, cobra y listo. Venta al público en general.')

@push('head')
    <style>
        .vr { max-width: 720px; margin: 0 auto; padding-bottom: 120px; }
        .vr .card { margin-bottom: 14px; }

        /* ---------- Escáner ---------- */
        .vr-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .vr-big { display: flex; align-items: center; justify-content: center; gap: 9px;
                  padding: 15px 12px; border-radius: 12px; font-size: 15px; font-weight: 700;
                  cursor: pointer; text-align: center; -webkit-tap-highlight-color: transparent; }
        .vr-big svg { width: 22px; height: 22px; flex: 0 0 22px; }
        .vr-big:active { transform: scale(.98); }
        .vr-btns.una { grid-template-columns: 1fr; }

        .vr-lector { margin-top: 12px; border-radius: 12px; overflow: hidden; background: #000;
                     position: relative; }
        .vr-lector #vr-camara { width: 100%; }
        .vr-lector #vr-camara video { width: 100% !important; display: block; }
        .vr-lector .cerrar { position: absolute; top: 10px; right: 10px; z-index: 5; width: 36px; height: 36px;
                             border-radius: 50%; border: 0; background: rgba(0,0,0,.55); color: #fff;
                             font-size: 20px; line-height: 1; cursor: pointer; }
        .vr-lector .guia { position: absolute; left: 0; right: 0; bottom: 10px; text-align: center; color: #fff;
                           font-size: 13px; text-shadow: 0 1px 3px rgba(0,0,0,.8); pointer-events: none; }

        .vr-manual { display: flex; gap: 8px; margin-top: 12px; }
        .vr-manual input { flex: 1; min-width: 0; font-family: ui-monospace, Consolas, monospace;
                           letter-spacing: .04em; text-transform: uppercase; font-size: 16px; }

        .vr-aviso { margin-top: 12px; padding: 11px 14px; border-radius: 10px; font-size: 14px; line-height: 1.4; }
        .vr-aviso.mal { background: var(--danger-soft); color: var(--danger); }
        .vr-aviso.bien { background: var(--green-soft, rgba(22,163,74,.12)); color: var(--green); }
        .vr-aviso.info { background: var(--primary-soft); color: var(--primary-strong, var(--primary)); }

        /* ---------- Piezas ---------- */
        .vr-cab { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
        .vr-cab h3 { margin: 0; flex: 1; font-size: 15px; }
        .vr-cab .n { padding: 2px 10px; border-radius: 999px; background: var(--surface-2);
                     border: 1px solid var(--border); font-size: 12.5px; font-weight: 700; }

        .vr-fila { display: grid; grid-template-columns: 52px 1fr auto; gap: 12px; align-items: start;
                   padding: 12px 0; border-bottom: 1px solid var(--border); }
        .vr-fila:last-child { border-bottom: 0; }
        .vr-fila.nueva { animation: vr-entra .5s ease; }
        @keyframes vr-entra { from { background: var(--primary-soft); } to { background: transparent; } }
        .vr-fila img, .vr-fila .sinfoto { width: 52px; height: 52px; border-radius: 10px; object-fit: cover;
                                          border: 1px solid var(--border); background: var(--surface-2); }
        .vr-fila .nom { font-weight: 700; font-size: 14.5px; line-height: 1.3; }
        .vr-fila .det { color: var(--muted); font-size: 13px; margin-top: 2px; line-height: 1.35; }
        .vr-fila .cods { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
        .vr-fila .cod { font-family: ui-monospace, Consolas, monospace; font-size: 11.5px; font-weight: 700;
                        padding: 2px 7px; border-radius: 6px; background: var(--surface-2); border: 1px solid var(--border); }
        .vr-fila .cod.extra { color: var(--muted); font-style: italic; font-family: inherit; font-weight: 600; }
        .vr-fila .quitar { width: 32px; height: 32px; border: 0; border-radius: 8px; background: transparent;
                           color: var(--muted); font-size: 20px; line-height: 1; cursor: pointer; }
        .vr-fila .quitar:hover { background: var(--danger-soft); color: var(--danger); }

        .vr-ctrl { grid-column: 2 / -1; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 4px; }
        .vr-precio { display: flex; align-items: center; gap: 4px; }
        .vr-precio span { color: var(--muted); font-weight: 700; }
        .vr-precio input { width: 110px; font-size: 16px; font-weight: 700; text-align: right;
                           font-variant-numeric: tabular-nums; }
        .vr-precio input.falta { border-color: var(--danger); background: var(--danger-soft); }
        .vr-cant { display: inline-flex; align-items: center; border: 1px solid var(--border); border-radius: 9px;
                   overflow: hidden; background: var(--surface); }
        .vr-cant button { width: 38px; height: 38px; border: 0; background: transparent; font-size: 20px;
                          color: var(--text); cursor: pointer; }
        .vr-cant button:active { background: var(--surface-2); }
        .vr-cant b { min-width: 34px; text-align: center; font-size: 16px; font-variant-numeric: tabular-nums; }
        .vr-imp { margin-left: auto; font-weight: 700; font-size: 15px; font-variant-numeric: tabular-nums; }

        /* ---------- Opciones ---------- */
        .vr-op { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .vr-op label { display: block; font-size: 12px; font-weight: 700; color: var(--muted);
                       text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; }
        .vr-op input, .vr-op select { width: 100%; font-size: 15px; }
        .vr-op .ancho { grid-column: 1 / -1; }
        .vr-cli-busca { position: relative; }
        .vr-cli-lista { position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 30; max-height: 260px;
                        overflow-y: auto; background: var(--surface); border: 1px solid var(--border);
                        border-radius: 10px; box-shadow: var(--shadow, 0 8px 24px rgba(0,0,0,.14)); }
        .vr-cli-lista button { display: block; width: 100%; text-align: left; padding: 10px 12px; border: 0;
                               border-bottom: 1px solid var(--border); background: transparent; color: var(--text);
                               cursor: pointer; font-size: 14.5px; }
        .vr-cli-lista button:last-child { border-bottom: 0; }
        .vr-cli-lista button:hover, .vr-cli-lista button:focus { background: var(--surface-2); }
        .vr-cli-lista button small { display: block; color: var(--muted); font-size: 12.5px; margin-top: 1px; }
        .vr-cli-lista .nada { padding: 10px 12px; color: var(--muted); font-size: 13.5px; }
        .vr-cli-sel { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px;
                      background: var(--primary-soft); border: 1px solid var(--primary); }
        .vr-cli-sel > span { flex: 1; min-width: 0; }
        .vr-cli-sel b { display: block; font-size: 14.5px; }
        .vr-cli-sel small { color: var(--muted); font-size: 12.5px; }
        .vr-cli-sel button { width: 32px; height: 32px; border: 0; border-radius: 8px; background: transparent;
                             color: var(--muted); font-size: 20px; line-height: 1; cursor: pointer; }
        .vr-cli-sel button:hover { background: var(--danger-soft); color: var(--danger); }
        .vr-chk { display: flex; align-items: center; gap: 9px; font-size: 14.5px; padding-top: 24px; }
        .vr-chk input { width: 20px; height: 20px; }

        /* ---------- Pie: cobrar ---------- */
        .vr-foot { position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; padding: 10px 14px
                   calc(10px + env(safe-area-inset-bottom)); background: var(--surface);
                   border-top: 1px solid var(--border); box-shadow: 0 -6px 24px rgba(0,0,0,.12); }
        .vr-foot-in { max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 9px; }
        .vr-metodos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; }
        .vr-metodos button { padding: 9px 6px; border-radius: 9px; border: 1px solid var(--border);
                             background: var(--surface); color: var(--text); font-weight: 700; font-size: 13.5px;
                             cursor: pointer; }
        .vr-metodos button.on { background: var(--primary-soft); border-color: var(--primary); color: var(--primary-strong, var(--primary)); }
        .vr-cobrar { display: flex; align-items: center; justify-content: space-between; width: 100%;
                     padding: 15px 18px; border-radius: 12px; border: 0; background: var(--green);
                     color: #fff; font-size: 17px; font-weight: 800; cursor: pointer; }
        .vr-cobrar:disabled { opacity: .45; cursor: not-allowed; }
        .vr-cobrar small { display: block; font-size: 12px; font-weight: 600; opacity: .9; }
        .vr-cobrar .tot { font-variant-numeric: tabular-nums; font-size: 20px; }
        @media (min-width: 1025px) {
            /* En escritorio el pie respeta el menú lateral. */
            .vr-foot { left: var(--sidebar-w, 0); }
            .app.collapsed .vr-foot { left: var(--sidebar-w-collapsed, 0); }
        }
        .vr [hidden], .vr-foot[hidden] { display: none !important; }

        /* ---------- Listo ---------- */
        .vr-listo { text-align: center; padding: 28px 16px; }
        .vr-listo .ok { width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 14px; display: flex;
                        align-items: center; justify-content: center; background: var(--green-soft, rgba(22,163,74,.12)); }
        .vr-listo .ok svg { width: 38px; height: 38px; stroke: var(--green); }
        .vr-listo h2 { margin: 0 0 4px; font-size: 22px; }
        .vr-listo .tot { font-size: 30px; font-weight: 800; margin: 8px 0 2px; font-variant-numeric: tabular-nums; }
        .vr-listo .muted { margin-bottom: 20px; }
        .vr-listo .acciones { display: grid; gap: 9px; }
        .vr-listo .acciones .btn { padding: 13px; font-size: 15px; font-weight: 700; border-radius: 11px; text-decoration: none; }

        @media (max-width: 480px) {
            .vr-op { grid-template-columns: 1fr; }
            .vr-chk { padding-top: 0; }
        }
    </style>
@endpush

@section('content')
    <div class="vr" data-vr>
        {{-- ===================== Escáner ===================== --}}
        <x-ui.card>
            <div class="vr-btns" data-btns>
                <button type="button" class="btn vr-big" data-camara>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    Escanear con la cámara
                </button>
                <label class="btn btn--ghost vr-big" data-foto-label>
                    <input type="file" accept="image/*" capture="environment" data-foto hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v7h-7"/></svg>
                    Tomar foto del QR
                </label>
            </div>

            {{-- Solo aparece si se entró por http: ahí el navegador no deja usar la cámara en vivo. --}}
            <div class="vr-aviso info" data-https hidden style="margin-top:12px;">
                <b>Para que el QR se detecte solo, abre esta pantalla por HTTPS:</b>
                <div style="margin-top:8px; display:flex; flex-direction:column; gap:8px;">
                    <a class="btn" data-https-link href="#" style="text-align:center;">Abrir en HTTPS</a>
                    @if (Route::has('publico.certificado'))
                        <small>La primera vez en cada teléfono, si marca "no seguro":
                            <a data-cert-link href="#" style="font-weight:700;">instala el certificado</a>
                            (iPhone: Ajustes → Perfil descargado → Instalar, y luego General → Información → Config. de confianza de certificados → activar).</small>
                    @endif
                </div>
            </div>

            <div class="vr-lector" data-lector hidden>
                <div id="vr-camara"></div>
                <button type="button" class="cerrar" data-camara-cerrar aria-label="Cerrar cámara">×</button>
                <div class="guia">Apunta al QR de la etiqueta. Se agrega solo.</div>
            </div>
            <div id="vr-foto-lector" hidden></div>

            <div class="vr-manual">
                <input type="text" data-manual placeholder="MB-000147" autocomplete="off" spellcheck="false" inputmode="text">
                <button type="button" class="btn btn--ghost" data-manual-ok>Agregar</button>
            </div>

            <div class="vr-aviso" data-aviso hidden></div>
        </x-ui.card>

        {{-- ===================== Piezas ===================== --}}
        <x-ui.card>
            <div class="vr-cab">
                <h3>Se lleva</h3>
                <span class="n" data-n>0 piezas</span>
                <button type="button" class="btn btn--ghost" data-vaciar style="padding:6px 10px;">Vaciar</button>
            </div>

            <div data-lista></div>

            <div class="empty-state" data-vacio>
                <span class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6h15l-1.5 8h-12z"/><circle cx="9" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/><path d="M6 6L5 3H2"/></svg>
                </span>
                <h3>Nada todavía</h3>
                <p>Escanea la etiqueta de cada pieza que se lleva el cliente.</p>
            </div>
        </x-ui.card>

        {{-- ===================== Opciones ===================== --}}
        <x-ui.card>
            <div class="vr-op">
                <div class="ancho">
                    <label for="vr-congreso">Congreso</label>
                    <select id="vr-congreso" data-congreso>
                        <option value="">Sin congreso (mostrador)</option>
                        @foreach ($congresos as $c)
                            <option value="{{ $c->id }}" @selected($c->id === $congresoSugerido)>
                                {{ $c->nombre }} · {{ $c->estadoLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="ancho">
                    <label for="vr-cliente">Cliente <span style="font-weight:500; text-transform:none;">(opcional · si no se elige, va como público en general)</span></label>
                    {{-- Cliente ya elegido: se muestra como ficha y se puede quitar. --}}
                    <div class="vr-cli-sel" data-cliente-sel hidden>
                        <span>
                            <b data-cliente-nombre></b>
                            <small data-cliente-detalle></small>
                        </span>
                        <button type="button" data-cliente-quitar aria-label="Quitar cliente">×</button>
                    </div>
                    <div class="vr-cli-busca" data-cliente-busca>
                        <input type="text" id="vr-cliente" data-cliente-q autocomplete="off" placeholder="Buscar por nombre, teléfono o correo">
                        <div class="vr-cli-lista" data-cliente-lista hidden></div>
                    </div>
                    <input type="hidden" data-cliente-id value="">
                </div>
                <div class="ancho" data-nota-wrap>
                    <label for="vr-nota">Nombre para la nota <span style="font-weight:500; text-transform:none;">(si no está registrado)</span></label>
                    <input type="text" id="vr-nota" data-nota maxlength="255" placeholder="Se anota en la venta y en la salida">
                </div>
                <div data-ref-wrap hidden>
                    <label for="vr-ref">Referencia del pago</label>
                    <input type="text" id="vr-ref" data-ref maxlength="255" placeholder="Últimos dígitos, folio…">
                </div>
                <div>
                    <label for="vr-garantia">Garantía</label>
                    <select id="vr-garantia" data-garantia>
                        @foreach ($garantias as $g)
                            {{-- Lo que se vende en un stand suele ser insumo: sin garantía salvo que se elija. --}}
                            <option value="{{ $g }}" @selected((int) $g === 0)>{{ $g > 0 ? $g.' meses' : 'Sin garantía' }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="vr-chk">
                    <input type="checkbox" data-iva> Lleva IVA ({{ (int) round($ivaTasa * 100) }}%)
                </label>
            </div>
        </x-ui.card>

        {{-- ===================== Listo ===================== --}}
        <x-ui.card data-listo hidden>
            <div class="vr-listo">
                <div class="ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 12 10 18 20 6"/></svg>
                </div>
                <h2>Venta <span data-l-folio></span></h2>
                <div class="tot" data-l-total></div>
                <div class="muted">Cobrada en <span data-l-metodo></span> · <span data-l-cliente>Público en general</span></div>
                <div class="acciones">
                    <button type="button" class="btn" data-otra>Otra venta</button>
                    <a class="btn btn--ghost" data-l-recibo href="#" target="_blank" rel="noopener">Recibo (PDF)</a>
                    <a class="btn btn--ghost" data-l-ver href="#">Ver la venta</a>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- ===================== Pie fijo: cobrar ===================== --}}
    <div class="vr-foot" data-foot>
        <div class="vr-foot-in">
            <div class="vr-metodos" data-metodos>
                @foreach ($metodos as $clave => $etiqueta)
                    <button type="button" data-metodo="{{ $clave }}" class="{{ $clave === 'efectivo' ? 'on' : '' }}">{{ $etiqueta }}</button>
                @endforeach
            </div>
            <button type="button" class="vr-cobrar" data-cobrar disabled>
                <span>Cobrar <small data-desglose></small></span>
                <span class="tot" data-total>$0.00</span>
            </button>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const URL_PIEZA = @json(route('commercial.ventas.rapida.pieza'));
            const URL_STORE = @json(route('commercial.ventas.rapida.store'));
            const IVA = {{ (float) $ivaTasa }};
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

            const $ = (s) => document.querySelector(s);
            const lista = $('[data-lista]'), vacio = $('[data-vacio]'), aviso = $('[data-aviso]');
            const manual = $('[data-manual]'), foot = $('[data-foot]'), listo = $('[data-listo]');
            const btnCobrar = $('[data-cobrar]');

            const fmt = (n) => '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // producto_id -> { producto_id, nombre, marca_modelo, foto, precio, cantidad, seriales:[{id, codigo, no_serie}] }
            const lineas = new Map();
            let metodo = 'efectivo';
            let enviando = false;

            /* ==========================================================
               Escáner con la cámara (html5-qrcode)

               La cámara en vivo solo funciona en un origen seguro (https o
               localhost). Si se entra por http en la red local, el botón
               se esconde y queda "Tomar foto del QR", que abre la cámara
               nativa del teléfono y decodifica la foto: eso sí funciona
               en http.
            ========================================================== */
            let lector = null;
            let ultimoLeido = { texto: '', en: 0 };

            if (!window.isSecureContext) {
                $('[data-camara]').hidden = true;
                $('[data-btns]').classList.add('una');

                // Misma pantalla, mismo servidor, pero por https (Apache en 443).
                const seguro = 'https://' + location.hostname + location.pathname + location.search;
                $('[data-https-link]').href = seguro;
                const cert = $('[data-cert-link]');
                if (cert) cert.href = 'https://' + location.hostname + '/certificado-local';
                $('[data-https]').hidden = false;
            }

            $('[data-camara]').addEventListener('click', abrirCamara);
            $('[data-camara-cerrar]').addEventListener('click', cerrarCamara);

            async function abrirCamara() {
                if (typeof Html5Qrcode === 'undefined') {
                    return avisar('mal', 'El lector aún no carga. Espera un momento e intenta de nuevo.');
                }

                $('[data-lector]').hidden = false;
                lector = lector || new Html5Qrcode('vr-camara', { verbose: false, useBarCodeDetectorIfSupported: true });

                try {
                    await lector.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: (w, h) => { const s = Math.min(w, h) * 0.7; return { width: s, height: s }; } },
                        (texto) => {
                            // El mismo QR se lee muchas veces por segundo: se
                            // procesa una vez y se ignora un par de segundos.
                            const ahora = Date.now();
                            if (texto === ultimoLeido.texto && ahora - ultimoLeido.en < 2500) return;
                            ultimoLeido = { texto, en: ahora };
                            procesar(texto);
                        },
                        () => {}
                    );
                } catch (e) {
                    $('[data-lector]').hidden = true;
                    avisar('mal', 'No se pudo abrir la cámara. Revisa que el navegador tenga permiso, o usa "Tomar foto del QR".');
                }
            }

            async function cerrarCamara() {
                $('[data-lector]').hidden = true;
                if (lector && lector.isScanning) {
                    try { await lector.stop(); } catch (e) {}
                }
            }

            /* ---------- Foto del QR (funciona sin https) ---------- */
            $('[data-foto]').addEventListener('change', async function () {
                const archivo = this.files && this.files[0];
                this.value = '';
                if (!archivo) return;

                if (typeof Html5Qrcode === 'undefined') {
                    return avisar('mal', 'El lector aún no carga. Espera un momento e intenta de nuevo.');
                }

                avisar('info', 'Leyendo la foto…');
                const lectorFoto = new Html5Qrcode('vr-foto-lector', { verbose: false, useBarCodeDetectorIfSupported: true });

                try {
                    const r = await lectorFoto.scanFileV2(archivo, false);
                    procesar(r.decodedText);
                } catch (e) {
                    avisar('mal', 'No se encontró un QR en la foto. Acércate más y que quede bien enfocado.');
                } finally {
                    try { lectorFoto.clear(); } catch (e) {}
                }
            });

            /* ---------- Pistola lectora / teclado ---------- */
            let buffer = '', ultimaTecla = 0;

            document.addEventListener('keydown', function (e) {
                const enCampo = typeof e.target?.matches === 'function' && e.target.matches('input, textarea, select');
                if (enCampo && e.target !== manual) return;

                const ahora = Date.now();
                if (ahora - ultimaTecla > 120) buffer = '';
                ultimaTecla = ahora;

                if (e.key === 'Enter') {
                    const leido = (enCampo ? e.target.value : buffer).trim();
                    buffer = '';
                    if (leido) {
                        e.preventDefault();
                        if (enCampo) e.target.value = '';
                        procesar(leido);
                    }
                    return;
                }

                if (e.key.length === 1) buffer += e.key;
            });

            $('[data-manual-ok]').addEventListener('click', function () {
                const v = manual.value.trim();
                if (v) { manual.value = ''; procesar(v); }
            });

            /* ==========================================================
               Consultar la etiqueta y sumarla
            ========================================================== */
            async function procesar(leido) {
                let data;

                try {
                    const r = await fetch(URL_PIEZA, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ codigo: leido }),
                    });
                    data = await r.json();
                } catch (e) {
                    return avisar('mal', 'No se pudo consultar la etiqueta. Revisa tu conexión.');
                }

                if (!data.encontrado) return avisar('mal', data.mensaje || 'No se reconoció esa etiqueta.');
                if (!data.vendible) return avisar('mal', `${data.codigo}: ${data.motivo}`);

                if (yaEsta(data.serial_id)) {
                    return avisar('info', `${data.codigo} ya está en la lista.`);
                }

                const linea = lineas.get(data.producto_id) || {
                    producto_id: data.producto_id,
                    nombre: data.nombre,
                    marca_modelo: data.marca_modelo,
                    foto: data.foto,
                    precio: data.precio,
                    cantidad: 0,
                    seriales: [],
                };

                linea.seriales.push({ id: data.serial_id, codigo: data.codigo, no_serie: data.no_serie });
                linea.cantidad = Math.max(linea.cantidad + 1, linea.seriales.length);
                lineas.set(data.producto_id, linea);

                vibrar();
                pintar(data.producto_id);
                avisar('bien', `${data.codigo} agregado · ${data.nombre}${data.marca_modelo ? ' ' + data.marca_modelo : ''}`);
            }

            function yaEsta(serialId) {
                for (const l of lineas.values()) {
                    if (l.seriales.some(s => s.id === serialId)) return true;
                }
                return false;
            }

            /* ==========================================================
               Pintar la lista
            ========================================================== */
            function pintar(resaltar) {
                lista.innerHTML = '';
                const filas = Array.from(lineas.values()).reverse();

                filas.forEach(function (l) {
                    const fila = document.createElement('div');
                    fila.className = 'vr-fila' + (l.producto_id === resaltar ? ' nueva' : '');

                    const foto = l.foto ? `<img src="${l.foto}" alt="">` : '<span class="sinfoto"></span>';
                    const extra = l.cantidad - l.seriales.length;
                    const cods = l.seriales.map(s => `<span class="cod">${s.codigo}</span>`).join('')
                        + (extra > 0 ? `<span class="cod extra">+${extra} del stock</span>` : '');
                    const sinPrecio = l.precio === null || l.precio === undefined || l.precio === '';

                    fila.innerHTML = `
                        ${foto}
                        <div>
                            <div class="nom">${l.nombre}</div>
                            <div class="det">${l.marca_modelo || ''}</div>
                            <div class="cods">${cods}</div>
                        </div>
                        <button type="button" class="quitar" title="Quitar" aria-label="Quitar">×</button>
                        <div class="vr-ctrl">
                            <span class="vr-precio"><span>$</span>
                                <input type="number" inputmode="decimal" step="0.01" min="0" placeholder="Precio"
                                       value="${sinPrecio ? '' : Number(l.precio).toFixed(2)}" class="${sinPrecio ? 'falta' : ''}" data-precio>
                            </span>
                            <span class="vr-cant">
                                <button type="button" data-menos aria-label="Una menos">−</button>
                                <b>${l.cantidad}</b>
                                <button type="button" data-mas aria-label="Una más">+</button>
                            </span>
                            <span class="vr-imp">${sinPrecio ? '—' : fmt(l.precio * l.cantidad)}</span>
                        </div>
                    `;

                    fila.querySelector('.quitar').addEventListener('click', () => { lineas.delete(l.producto_id); pintar(); });

                    fila.querySelector('[data-precio]').addEventListener('input', function () {
                        l.precio = this.value === '' ? null : Number(this.value);
                        this.classList.toggle('falta', l.precio === null);
                        fila.querySelector('.vr-imp').textContent = l.precio === null ? '—' : fmt(l.precio * l.cantidad);
                        totalizar();
                    });

                    // "+" vende una pieza más del mismo producto aunque no se
                    // haya escaneado: el sistema toma otra del stock (primero
                    // las de este congreso). "−" quita la última escaneada.
                    fila.querySelector('[data-mas]').addEventListener('click', () => { l.cantidad++; pintar(); });
                    fila.querySelector('[data-menos]').addEventListener('click', () => {
                        if (l.cantidad > l.seriales.length) l.cantidad--;
                        else { l.seriales.pop(); l.cantidad = l.seriales.length; }
                        if (l.cantidad <= 0) lineas.delete(l.producto_id);
                        pintar();
                    });

                    lista.appendChild(fila);
                });

                const piezas = filas.reduce((s, l) => s + l.cantidad, 0);
                $('[data-n]').textContent = piezas === 1 ? '1 pieza' : `${piezas} piezas`;
                vacio.hidden = filas.length > 0;
                totalizar();
            }

            function totalizar() {
                let subtotal = 0, faltaPrecio = false;
                for (const l of lineas.values()) {
                    if (l.precio === null || l.precio === undefined) faltaPrecio = true;
                    else subtotal += Number(l.precio) * l.cantidad;
                }
                const conIva = $('[data-iva]').checked;
                const iva = conIva ? Math.round(subtotal * IVA * 100) / 100 : 0;
                const total = subtotal + iva;

                $('[data-total]').textContent = fmt(total);
                $('[data-desglose]').textContent = conIva && subtotal > 0 ? `${fmt(subtotal)} + IVA ${fmt(iva)}` : '';
                btnCobrar.disabled = enviando || lineas.size === 0 || faltaPrecio;
                btnCobrar.title = faltaPrecio ? 'Falta ponerle precio a una pieza' : '';
            }

            $('[data-iva]').addEventListener('change', totalizar);

            $('[data-vaciar]').addEventListener('click', async function () {
                if (lineas.size && !(await window.confirmModal({ message: '¿Vaciar la lista?', danger: true }))) return;
                lineas.clear();
                pintar();
            });

            /* ---------- Forma de pago ---------- */
            $('[data-metodos]').addEventListener('click', function (e) {
                const b = e.target.closest('[data-metodo]');
                if (!b) return;
                metodo = b.dataset.metodo;
                this.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
                // Efectivo no lleva referencia; tarjeta y transferencia sí pueden.
                $('[data-ref-wrap]').hidden = metodo === 'efectivo';
            });

            /* ==========================================================
               Cliente registrado (opcional)

               Se busca en el directorio de clientes con el mismo endpoint
               del cotizador. Si se elige uno, la venta va a su nombre; si
               no, queda como público en general.
            ========================================================== */
            const URL_CLIENTES = @json(route('commercial.cotizaciones.clientes.buscar'));
            const cliQ = $('[data-cliente-q]'), cliLista = $('[data-cliente-lista]');
            let cliente = null;
            let cliReloj = null;

            cliQ.addEventListener('input', function () {
                clearTimeout(cliReloj);
                const q = this.value.trim();
                if (q.length < 2) { cliLista.hidden = true; return; }
                cliReloj = setTimeout(() => buscarClientes(q), 250);
            });

            cliQ.addEventListener('keydown', function (e) {
                // El Enter aquí no es una etiqueta: elige el primer resultado.
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    cliLista.querySelector('button')?.click();
                }
                if (e.key === 'Escape') cliLista.hidden = true;
            }, true);

            document.addEventListener('click', function (e) {
                if (!e.target.closest('[data-cliente-busca]')) cliLista.hidden = true;
            });

            async function buscarClientes(q) {
                try {
                    const r = await fetch(URL_CLIENTES + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                    const lista = await r.json();
                    const encontrados = (Array.isArray(lista) ? lista : (lista.data || []))
                        .filter(c => c.nombre !== 'Público en general');

                    cliLista.innerHTML = encontrados.length
                        ? encontrados.map(c => `
                            <button type="button" data-id="${c.id}" data-nombre="${escapar(c.nombre)}" data-detalle="${escapar([c.telefono, c.correo].filter(Boolean).join(' · '))}">
                                ${escapar(c.nombre)}
                                <small>${escapar([c.telefono, c.correo].filter(Boolean).join(' · ')) || 'Sin teléfono ni correo'}</small>
                            </button>`).join('')
                        : '<div class="nada">No hay ningún cliente con eso. Puedes anotar su nombre abajo.</div>';
                    cliLista.hidden = false;
                } catch (e) {
                    cliLista.innerHTML = '<div class="nada">No se pudo buscar. Revisa tu conexión.</div>';
                    cliLista.hidden = false;
                }
            }

            cliLista.addEventListener('click', function (e) {
                const b = e.target.closest('button[data-id]');
                if (!b) return;
                elegirCliente({ id: Number(b.dataset.id), nombre: b.dataset.nombre, detalle: b.dataset.detalle });
            });

            function elegirCliente(c) {
                cliente = c;
                $('[data-cliente-id]').value = c.id;
                $('[data-cliente-nombre]').textContent = c.nombre;
                $('[data-cliente-detalle]').textContent = c.detalle || '';
                $('[data-cliente-sel]').hidden = false;
                $('[data-cliente-busca]').hidden = true;
                $('[data-nota-wrap]').hidden = true;
                cliLista.hidden = true;
                cliQ.value = '';
            }

            function quitarCliente() {
                cliente = null;
                $('[data-cliente-id]').value = '';
                $('[data-cliente-sel]').hidden = true;
                $('[data-cliente-busca]').hidden = false;
                $('[data-nota-wrap]').hidden = false;
            }

            $('[data-cliente-quitar]').addEventListener('click', quitarCliente);

            function escapar(t) {
                return String(t ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
            }

            /* ==========================================================
               Cobrar
            ========================================================== */
            btnCobrar.addEventListener('click', async function () {
                if (btnCobrar.disabled) return;

                const items = Array.from(lineas.values()).map(l => ({
                    producto_id: l.producto_id,
                    cantidad: l.cantidad,
                    precio_unitario: Number(l.precio),
                    seriales: l.seriales.map(s => s.id),
                }));

                const piezas = items.reduce((s, i) => s + i.cantidad, 0);
                const etiquetaMetodo = $('[data-metodos] .on')?.textContent.trim().toLowerCase() || metodo;

                if (!(await window.confirmModal({ message: `Cobrar ${$('[data-total]').textContent} en ${etiquetaMetodo} por ${piezas} pieza${piezas === 1 ? '' : 's'}.\n\nLa venta queda registrada y entregada. ¿Confirmar?`, confirmText: 'Sí, cobrar' }))) return;

                enviando = true;
                totalizar();
                btnCobrar.querySelector('span').firstChild.textContent = 'Registrando… ';

                try {
                    const r = await fetch(URL_STORE, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({
                            items,
                            metodo,
                            customer_id: cliente ? cliente.id : null,
                            congreso_id: $('[data-congreso]').value || null,
                            aplica_iva: $('[data-iva]').checked,
                            garantia_meses: Number($('[data-garantia]').value),
                            nota: $('[data-nota]').value.trim() || null,
                            referencia: metodo === 'efectivo' ? null : ($('[data-ref]').value.trim() || null),
                        }),
                    });
                    const data = await r.json();

                    if (!r.ok || !data.ok) {
                        const msg = data.mensaje || (data.errors ? Object.values(data.errors).flat().join(' ') : 'No se pudo registrar la venta.');
                        throw new Error(msg);
                    }

                    mostrarListo(data);
                } catch (e) {
                    avisar('mal', e.message || 'No se pudo registrar la venta.');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } finally {
                    enviando = false;
                    btnCobrar.querySelector('span').firstChild.textContent = 'Cobrar ';
                    totalizar();
                }
            });

            function mostrarListo(data) {
                cerrarCamara();
                $('[data-l-folio]').textContent = data.folio;
                $('[data-l-total]').textContent = fmt(data.total);
                $('[data-l-metodo]').textContent = (data.metodo || '').toLowerCase();
                $('[data-l-cliente]').textContent = data.cliente || 'Público en general';
                $('[data-l-recibo]').href = data.recibo;
                $('[data-l-ver]').href = data.ver;

                document.querySelectorAll('[data-vr] > .card:not([data-listo])').forEach(c => c.hidden = true);
                foot.hidden = true;
                listo.hidden = false;
                vibrar([40, 60, 40]);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            $('[data-otra]').addEventListener('click', function () {
                lineas.clear();
                quitarCliente();
                $('[data-nota]').value = '';
                $('[data-ref]').value = '';
                document.querySelectorAll('[data-vr] > .card').forEach(c => c.hidden = false);
                listo.hidden = true;
                foot.hidden = false;
                ocultarAviso();
                pintar();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            /* ---------- Avisos ---------- */
            function avisar(tipo, texto) {
                aviso.className = 'vr-aviso ' + tipo;
                aviso.textContent = texto;
                aviso.hidden = false;
                clearTimeout(avisar.reloj);
                if (tipo !== 'mal') avisar.reloj = setTimeout(ocultarAviso, 3500);
            }
            function ocultarAviso() { aviso.hidden = true; }

            function vibrar(patron) {
                try { navigator.vibrate?.(patron || 50); } catch (e) {}
            }

            pintar();
        });
        </script>
    @endpush
@endsection
