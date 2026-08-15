<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <style>
        :root {
            --indigo:#007aff; --indigo-d:#0062cc; --bg:#f6f7f9; --card:#fff;
            --text:#333333; --muted:#888888; --border:#ebebeb; --red:#ff4a4a; --green:#15803d;
        }
        * { box-sizing:border-box; }
        body { margin:0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
        input:focus { border-color:var(--indigo); box-shadow:0 0 0 3px rgba(0, 122, 255, 0.15); }
        .btn { display:inline-block; width:100%; margin-top:20px; padding:12px; border:none;
               border-radius:9px; background:var(--indigo); color:#fff; font-size:15px; font-weight:600;
               cursor:pointer; transition:background .15s; text-align:center; text-decoration:none; }
        .btn:hover { background:var(--indigo-d); }
        .btn--ghost { background:transparent; color:var(--indigo); border:1px solid var(--border); }
        .btn--ghost:hover { background:#f9fafb; }
        .alert { padding:11px 14px; border-radius:9px; font-size:14px; margin-bottom:16px; }
        .alert--ok { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert--err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .err { color:var(--red); font-size:13px; margin:6px 0 0; }
        .code-input { text-align:center; font-size:28px; letter-spacing:12px; font-weight:700; }
        table { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--border); }
        th { color:var(--muted); font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:.04em; }
        .badge { display:inline-block; padding:3px 9px; border-radius:999px; font-size:12px; font-weight:600; }
        .badge--ok { background:#ecfdf5; color:#065f46; }
        .badge--warn { background:#fffbeb; color:#92400e; }
        .section { border-top:1px solid var(--border); margin-top:28px; padding-top:20px; }
        .muted { color:var(--muted); font-size:14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card @yield('card-class')">
            @yield('content')
        </div>
    </div>
</body>
</html>
