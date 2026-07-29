<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Estas pantallas (login, registro, etc.) siempre en modo claro. */
        :root {
            --indigo:#007aff; --indigo-d:#0062cc; --bg:#f6f7f9; --card:#fff;
            --text:#333333; --muted:#888888; --border:#ebebeb; --red:#ff4a4a; --green:#15803d;
        }
        * { box-sizing:border-box; }
        body { margin:0; font-family:'Quicksand',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
               background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; }
        .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { background:var(--card); width:100%; max-width:420px; border-radius:16px;
                box-shadow:0 10px 25px rgba(0,0,0,.06); padding:32px; }
        .card--wide { max-width:760px; }
        .auth-logo { text-align:center; margin-bottom:24px; }
        .auth-logo img { height:60px; width:auto; max-width:85%; }
        h1 { font-size:22px; margin:0 0 4px; }
        .sub { color:var(--muted); font-size:14px; margin:0 0 24px; }
        label { display:block; font-size:13px; font-weight:600; margin:14px 0 6px; }
        input[type=text], input[type=email], input[type=password] {
            width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px;
            font-size:15px; outline:none; transition:border .15s; background:var(--card); color:var(--text); }
        input:focus { border-color:var(--indigo); box-shadow:0 0 0 3px rgba(0,122,255,.15); }
        /* Botón de "ojo" para mostrar/ocultar contraseña */
        .pw-wrap { position:relative; display:block; }
        .pw-wrap input { padding-right:44px; }
        .pw-toggle { position:absolute; right:6px; top:50%; transform:translateY(-50%);
            background:none; border:none; cursor:pointer; padding:6px; line-height:0;
            color:var(--muted); border-radius:6px; }
        .pw-toggle:hover { color:var(--text); background:#f3f4f6; }
        .pw-toggle svg { width:20px; height:20px; display:block; }
        .pw-toggle .icon-off { display:none; }
        .pw-toggle.is-visible .icon-on { display:none; }
        .pw-toggle.is-visible .icon-off { display:block; }
        .btn { display:inline-block; width:100%; margin-top:20px; padding:12px; border:none;
               border-radius:9px; background:var(--indigo); color:#fff; font-size:15px; font-weight:600;
               cursor:pointer; transition:background .15s; text-align:center; text-decoration:none; }
        .btn:hover { background:var(--indigo-d); }
        .btn--ghost { background:transparent; color:var(--indigo); border:1px solid var(--border); }
        .btn--ghost:hover { background:#f9fafb; }
        .btn--danger { background:var(--red); }
        .btn--danger:hover { background:#b91c1c; }
        .link { color:var(--indigo); text-decoration:none; font-weight:600; font-size:14px; }
        .link:hover { text-decoration:underline; }
        .foot { text-align:center; margin-top:18px; font-size:14px; color:var(--muted); }
        .alert { padding:11px 14px; border-radius:9px; font-size:14px; margin-bottom:16px; }
        .alert--ok { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert--err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .err { color:var(--red); font-size:13px; margin:6px 0 0; }
        .row { display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .check { display:flex; align-items:center; gap:8px; font-size:14px; color:var(--muted); margin-top:14px; }
        .check input { width:auto; }
        .code-input { text-align:center; font-size:28px; letter-spacing:12px; font-weight:700; }
        table { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--border); }
        th { color:var(--muted); font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:.04em; }
        .badge { display:inline-block; padding:3px 9px; border-radius:999px; font-size:12px; font-weight:600; }
        .badge--ok { background:#ecfdf5; color:#065f46; }
        .badge--warn { background:#fffbeb; color:#92400e; }
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .section { border-top:1px solid var(--border); margin-top:28px; padding-top:20px; }
        .muted { color:var(--muted); font-size:14px; }
        .danger-box { border:1px solid #fecaca; background:#fef2f2; border-radius:12px; padding:18px; margin-top:12px; }
        /* ===== Toast moderno (arriba a la derecha) ===== */
        .toast{ position:fixed; top:22px; right:22px; left:auto; bottom:auto; z-index:9999;
                display:flex; align-items:center; gap:12px; padding:13px 16px 13px 13px; border-radius:15px;
                background:var(--card); color:var(--text); border:1px solid var(--border);
                box-shadow:0 16px 44px rgba(17,24,39,.16); opacity:0; pointer-events:none;
                transform:translateX(120%); transition:opacity .3s ease, transform .34s cubic-bezier(.22,1,.36,1);
                font-size:14px; font-weight:600; max-width:min(360px, calc(100% - 44px)); }
        .toast.show{ opacity:1; transform:translateX(0); }
        .toast .toast-ico{ width:34px; height:34px; border-radius:10px; flex:0 0 auto;
                display:flex; align-items:center; justify-content:center;
                background:#e6f7ee; color:var(--green); }
        .toast .toast-ico.warn{ background:#fdecec; color:var(--red); }
        .toast .toast-ico svg{ width:19px; height:19px; }
        .toast b{ font-weight:800; }
        @media (max-width:640px){ .toast{ top:14px; right:14px; left:14px; max-width:none; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card @yield('card-class')">
            <div class="auth-logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}"
                     onerror="this.parentElement.style.display='none';">
            </div>
            @yield('content')
        </div>
    </div>

    <div class="toast" id="appToast" role="status" aria-live="polite">
        <span class="toast-ico" id="appToastIco"></span>
        <span id="appToastMsg"></span>
    </div>
    @if (session('status'))
        <span id="appFlash" data-msg="{{ session('status') }}" hidden></span>
    @endif
    @if ($errors->any())
        <span id="appFlashErr" data-msg="{{ $errors->first() }}" hidden></span>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toast de mensajes flash / errores
            var TOAST_ICON_OK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            var TOAST_ICON_WARN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>';
            function showToast(msg, type) {
                var t = document.getElementById('appToast');
                if (!t) return;
                document.getElementById('appToastMsg').textContent = msg;
                var ico = document.getElementById('appToastIco');
                ico.classList.toggle('warn', type === 'warn');
                ico.innerHTML = type === 'warn' ? TOAST_ICON_WARN : TOAST_ICON_OK;
                t.classList.add('show');
                setTimeout(function () { t.classList.remove('show'); }, 3600);
            }
            var flash = document.getElementById('appFlash');
            var flashErr = document.getElementById('appFlashErr');
            if (flash && flash.dataset.msg) { showToast(flash.dataset.msg, 'ok'); }
            else if (flashErr && flashErr.dataset.msg) { showToast(flashErr.dataset.msg, 'warn'); }

            // Botón de "ojo" para cada campo de contraseña
            var eyeOn = '<svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
            var eyeOff = '<svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

            document.querySelectorAll('input[type="password"]').forEach(function (input) {
                var wrap = document.createElement('span');
                wrap.className = 'pw-wrap';
                input.parentNode.insertBefore(wrap, input);
                wrap.appendChild(input);

                var btn = document.createElement('button');
                btn.type = 'button';               // no debe enviar el formulario
                btn.className = 'pw-toggle';
                btn.setAttribute('aria-label', 'Mostrar u ocultar contraseña');
                btn.innerHTML = eyeOn + eyeOff;
                wrap.appendChild(btn);

                btn.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.classList.toggle('is-visible', show);
                });
            });
        });
    </script>
</body>
</html>
