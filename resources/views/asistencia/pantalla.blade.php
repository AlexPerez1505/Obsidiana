<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="robots" content="noindex,nofollow">
    <title>Control de asistencia · {{ $empresa }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        html, body { height:100%; overflow:hidden; cursor:none; user-select:none; -webkit-user-select:none; }
        body { font-family:'Quicksand', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color:#e8eef8; background:#070c17; }

        /* Fondo plano: solo cambia el tono según el resultado */
        .pantalla { position:fixed; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:4vmin; background:#0a1223; transition:background .3s; }
        .pantalla.ok { background:#0b1d17; }
        .pantalla.tarde { background:#1d170b; }
        .pantalla.mal { background:#1f0d12; }
        .pantalla.reg { background:#0b1a33; }

        .empresa { position:absolute; top:4vmin; font-size:2.2vmin; letter-spacing:.3em; text-transform:uppercase; color:#93a4bd; font-weight:600; }
        .vista { display:none; flex-direction:column; align-items:center; gap:2.4vmin; }
        .vista.on { display:flex; animation:entra .3s ease-out; }
        @keyframes entra { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

        /* Icono de línea dentro de un círculo plano */
        .ico { width:20vmin; height:20vmin; border-radius:50%; background:rgba(255,255,255,.06); display:flex; align-items:center; justify-content:center; color:#e8eef8; }
        .ico svg { width:56%; height:56%; }
        .ico.sm { width:16vmin; height:16vmin; }
        .pantalla.ok .ico { color:#4ade80; background:rgba(74,222,128,.1); }
        .pantalla.tarde .ico { color:#fb923c; background:rgba(251,146,60,.1); }
        .pantalla.mal .ico { color:#f87171; background:rgba(248,113,113,.1); }
        .pantalla.reg .ico { color:#60a5fa; background:rgba(96,165,250,.1); }
        .ico.pulso { animation:latido 2.6s ease-in-out infinite; }
        @keyframes latido { 50% { transform:scale(1.05); opacity:.85; } }

        .titulo { font-size:3.6vmin; font-weight:600; letter-spacing:.14em; text-transform:uppercase; color:#93a4bd; }
        .hora { font-size:13vmin; font-weight:700; font-variant-numeric:tabular-nums; line-height:1; }
        .fecha { font-size:3.4vmin; color:#93a4bd; }
        .pide { font-size:5.2vmin; font-weight:700; letter-spacing:.08em; }

        .tipo { font-size:4.4vmin; font-weight:700; letter-spacing:.14em; }
        .nombre { font-size:8vmin; font-weight:700; max-width:92vw; line-height:1.1; }
        .num { font-size:3.2vmin; color:#93a4bd; letter-spacing:.1em; }
        .hora-ev { font-size:9vmin; font-weight:700; font-variant-numeric:tabular-nums; }
        .estado { font-size:3.8vmin; font-weight:700; padding:1vmin 3.6vmin; border-radius:1vmin; background:rgba(255,255,255,.08); letter-spacing:.12em; }
        .pantalla.ok .estado { color:#4ade80; background:rgba(74,222,128,.12); }
        .pantalla.tarde .estado { color:#fb923c; background:rgba(251,146,60,.12); }
        .detalle { font-size:3.4vmin; color:#93a4bd; line-height:1.4; }

        .barra { width:60vmin; height:1.2vmin; background:rgba(255,255,255,.1); border-radius:99px; overflow:hidden; }
        .barra span { display:block; height:100%; width:0; background:#60a5fa; transition:width .4s; }

        .aviso { position:absolute; bottom:4vmin; left:4vmin; right:4vmin; padding:1.6vmin 3vmin; border-radius:1vmin; background:#3a1219; color:#fecaca; font-size:2.8vmin; font-weight:600; display:none; align-items:center; justify-content:center; gap:1.2vmin; }
        .aviso.on { display:flex; }
        .aviso svg { width:3.4vmin; height:3.4vmin; flex:0 0 auto; }
    </style>
</head>
<body>
@php
    $svg = fn (string $trazos) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$trazos.'</svg>';
    $huella = '<path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/><path d="M2 12a10 10 0 0 1 18-6"/><path d="M2 16h.01"/><path d="M21.8 16c.2-2 .13-5.35 0-6"/><path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2"/><path d="M8.65 22c.21-.66.45-1.32.57-2"/><path d="M9 6.8a6 6 0 0 1 9 5.2v2"/>';
    $alerta = '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>';
    $ok = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>';
@endphp
<div class="pantalla" id="pantalla">
    <div class="empresa">{{ $empresa }}</div>

    {{-- Reposo --}}
    <div class="vista on" id="v-reposo">
        <div class="titulo">Control de asistencia</div>
        <div class="ico pulso">{!! $svg($huella) !!}</div>
        <div class="pide">Coloca tu huella</div>
        <div class="fecha" id="fecha"></div>
        <div class="hora" id="hora">--:--:--</div>
    </div>

    {{-- Checada --}}
    <div class="vista" id="v-checada">
        <div class="ico" id="c-icono"></div>
        <div class="tipo" id="c-tipo"></div>
        <div class="nombre" id="c-nombre"></div>
        <div class="num" id="c-num"></div>
        <div class="hora-ev" id="c-hora"></div>
        <div class="estado" id="c-estado"></div>
        <div class="detalle" id="c-detalle"></div>
    </div>

    {{-- Huella repetida --}}
    <div class="vista" id="v-repetida">
        <div class="ico sm">{!! $svg($ok) !!}</div>
        <div class="tipo">Ya registrada</div>
        <div class="nombre" id="r-nombre"></div>
        <div class="detalle">Tu checada anterior sigue vigente</div>
    </div>

    {{-- No reconocida --}}
    <div class="vista" id="v-nr">
        <div class="ico">{!! $svg($alerta) !!}</div>
        <div class="tipo">Huella no reconocida</div>
        <div class="detalle">Limpia el dedo e inténtalo de nuevo.<br>Si continúa, avisa a Recursos Humanos.</div>
    </div>

    {{-- Registro de huella (RH) --}}
    <div class="vista" id="v-registro">
        <div class="ico pulso">{!! $svg($huella) !!}</div>
        <div class="tipo">Registro de huella</div>
        <div class="nombre" id="g-nombre"></div>
        <div class="barra"><span id="g-barra"></span></div>
        <div class="detalle" id="g-msg"></div>
    </div>

    <div class="aviso" id="aviso">{!! $svg($alerta) !!}<span id="aviso-txt"></span></div>
</div>

<script>
(function () {
    const TZ = @json($tz);
    const SEG = @json($segundos) * 1000;
    const URL_FEED = @json($urlFeed);

    const $ = id => document.getElementById(id);
    const pantalla = $('pantalla');
    const vistas = ['reposo', 'checada', 'repetida', 'nr', 'registro'];

    // Iconos de línea (mismo trazo que el resto del sistema)
    const svg = t => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + t + '</svg>';
    const ICONOS = {
        entrada: svg('<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>'),
        salida: svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>'),
        regreso: svg('<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>'),
        ok: svg('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'),
        alerta: svg('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'),
    };

    // ---- Reloj: hora del servidor (no la de la PC), en la zona de la empresa ----
    let desfase = 0;
    const fmtHora = new Intl.DateTimeFormat('es-MX', { timeZone: TZ, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const fmtFecha = new Intl.DateTimeFormat('es-MX', { timeZone: TZ, weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
    function reloj() {
        const ahora = new Date(Date.now() + desfase);
        $('hora').textContent = fmtHora.format(ahora);
        { const f = fmtFecha.format(ahora); $("fecha").textContent = f.charAt(0).toUpperCase() + f.slice(1); }
    }
    setInterval(reloj, 250); reloj();

    // ---- Vistas ----
    let temporizador = null;
    function mostrar(nombre, clase, segundos) {
        vistas.forEach(v => $('v-' + v).classList.toggle('on', v === nombre));
        pantalla.className = 'pantalla' + (clase ? ' ' + clase : '');
        clearTimeout(temporizador);
        if (segundos) temporizador = setTimeout(reposo, segundos);
    }
    function reposo() { mostrar('reposo', '', 0); }

    const TEXTOS = { entrada: 'Entrada registrada', salida: 'Salida registrada', regreso: 'Regreso registrado' };

    function checada(e) {
        const tarde = e.status === 'retardo';
        $('c-icono').innerHTML = tarde ? ICONOS.alerta : (ICONOS[e.kind] || ICONOS.ok);
        $('c-tipo').textContent = (TEXTOS[e.kind] || 'Checada registrada').toUpperCase();
        $('c-nombre').textContent = e.employee.name;
        $('c-num').textContent = 'ID ' + e.employee.number;
        $('c-hora').textContent = e.time;
        const est = $('c-estado');
        if (e.status) { est.style.display = ''; est.textContent = tarde ? 'RETARDO' : 'PUNTUAL'; } else { est.style.display = 'none'; }
        $('c-detalle').textContent = e.message && (tarde || !e.status) ? e.message : '';
        mostrar('checada', tarde ? 'tarde' : 'ok', SEG);
    }

    function registro(e) {
        $('g-nombre').textContent = e.employee ? e.employee.name : '';
        $('g-barra').style.width = Math.round(((e.step || 0) / 3) * 100) + '%';
        $('g-msg').textContent = e.message || '';
        mostrar('registro', 'reg', 0);
    }

    function procesar(e) {
        switch (e.tipo) {
            case 'checada': checada(e); break;
            case 'repetida': $('r-nombre').textContent = e.employee.name; mostrar('repetida', 'ok', SEG); break;
            case 'no_reconocido': mostrar('nr', 'mal', SEG + 1000); break;
            case 'registro': registro(e); break;
            case 'registro_listo':
                $('g-nombre').textContent = e.employee ? e.employee.name : '';
                $('g-barra').style.width = '100%'; $('g-msg').textContent = 'Huella registrada';
                mostrar('registro', 'ok', SEG); break;
            case 'registro_fallido':
                $('g-msg').textContent = e.message || 'No se pudo registrar'; mostrar('registro', 'mal', SEG + 1500); break;
        }
    }

    // ---- Consulta al servidor cada segundo ----
    let ultimo = -1;
    let fallos = 0;
    function aviso(texto) { $('aviso-txt').textContent = texto || ''; $('aviso').classList.toggle('on', !!texto); }
    async function consultar() {
        try {
            const r = await fetch(URL_FEED + '?after=' + ultimo, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error(r.status);
            const d = await r.json();
            fallos = 0;
            desfase = d.now - Date.now();
            ultimo = d.ultimo;
            d.eventos.forEach(procesar);
            aviso(d.lector_en_linea ? '' : 'El lector de huella no responde. Avisa a Recursos Humanos o a Sistemas.');
        } catch (e) {
            if (++fallos >= 3) aviso('Sin conexión con el servidor. Tus checadas se guardarán en cuanto regrese.');
        }
    }
    consultar();
    setInterval(consultar, 1000);

    // Sin menús ni atajos de navegador al alcance de quien pase
    document.addEventListener('contextmenu', e => e.preventDefault());
    // Recarga de seguridad cada 6 h por si el navegador acumula memoria
    setTimeout(() => location.reload(), 6 * 3600 * 1000);
})();
</script>
</body>
</html>
