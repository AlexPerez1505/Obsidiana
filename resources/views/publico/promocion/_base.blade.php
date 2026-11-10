<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Promoción') · Grupo MediBuy</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { --bg:#f5f6f8; --surface:#fff; --surface-2:#f1f3f6; --border:#e3e6eb; --text:#16202c; --muted:#5f6b7a;
                --primary:#1b589d; --primary-soft:#e7eef7; --ok:#1f7a3d; --ok-soft:#e6f4ea; --danger:#b42318; --danger-soft:#fdecea; --warn:#9a6700; --warn-soft:#fff6db; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font:15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:760px; margin:0 auto; padding:16px; }
        .top { display:flex; align-items:center; gap:10px; padding:6px 0 16px; }
        .top img { height:34px; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:12px; margin-bottom:14px; overflow:hidden; }
        .pad { padding:18px; }
        h1 { font-size:22px; line-height:1.25; margin:0 0 8px; letter-spacing:-.01em; }
        h2 { font-size:15px; margin:0 0 10px; display:flex; align-items:center; gap:8px; }
        h2 svg { width:18px; height:18px; color:var(--muted); }
        .muted { color:var(--muted); }
        .small { font-size:13px; }
        .hero img { display:block; width:100%; max-height:300px; object-fit:cover; background:var(--surface-2); }
        .codigo { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-weight:700; letter-spacing:.06em; font-size:18px;
                  border:1.5px dashed var(--primary); color:var(--primary); padding:6px 12px; border-radius:8px; display:inline-block; background:var(--primary-soft); }
        ul.cond { margin:10px 0 0; padding-left:18px; font-size:13.5px; color:var(--muted); }
        ul.cond li { margin:3px 0; }
        .prod { display:flex; gap:12px; align-items:center; padding:12px 0; border-bottom:1px solid var(--border); }
        .prod:last-child { border-bottom:0; }
        .prod img, .prod .ph { width:58px; height:58px; border-radius:10px; object-fit:cover; background:var(--surface-2); flex:0 0 auto; }
        .prod .t { flex:1; min-width:0; }
        .prod .t b { display:block; }
        .prod .precio { font-weight:600; font-variant-numeric:tabular-nums; }
        .paso { display:inline-flex; align-items:center; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
        .paso button { width:36px; height:36px; border:0; background:var(--surface-2); font-size:18px; cursor:pointer; color:var(--text); }
        .paso input { width:44px; height:36px; border:0; text-align:center; font:inherit; font-weight:600; background:var(--surface); color:var(--text); }
        .tot { display:flex; justify-content:space-between; padding:6px 0; font-variant-numeric:tabular-nums; }
        .tot.total { border-top:1px solid var(--border); margin-top:6px; padding-top:12px; font-size:19px; font-weight:700; }
        .tot.desc { color:var(--ok); }
        .aviso { padding:12px 14px; border-radius:10px; font-size:14px; margin-bottom:14px; display:flex; gap:10px; }
        .aviso svg { width:18px; height:18px; flex:0 0 auto; margin-top:2px; }
        .aviso.ok { background:var(--ok-soft); color:var(--ok); }
        .aviso.warn { background:var(--warn-soft); color:var(--warn); }
        .aviso.err { background:var(--danger-soft); color:var(--danger); }
        .aviso.info { background:var(--primary-soft); color:var(--primary); }
        label.lbl { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
        input.in { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; font-size:16px; background:var(--surface); color:var(--text); }
        input.in:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-soft); }
        .btn { display:inline-flex; justify-content:center; align-items:center; gap:8px; width:100%; padding:13px 16px; border:0; border-radius:10px;
               background:var(--primary); color:#fff; font:inherit; font-weight:600; font-size:15.5px; cursor:pointer; text-decoration:none; }
        .btn[disabled] { opacity:.5; cursor:not-allowed; }
        .btn.ghost { background:var(--surface); color:var(--text); border:1px solid var(--border); }
        .check { display:flex; gap:8px; align-items:flex-start; font-size:13.5px; margin:10px 0; }
        .pie { text-align:center; font-size:12.5px; color:var(--muted); padding:10px 0 30px; }
        .pie a { color:var(--muted); }
        .err { color:var(--danger); font-size:13px; margin-top:6px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top"><img src="{{ asset('images/logo.png') }}" alt="Grupo MediBuy"></div>
    @yield('contenido')
</div>
</body>
</html>
