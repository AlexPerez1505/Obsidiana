<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Obsidiana') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#070c17; --surface:#0f1a30; --surface-2:#0c1526;
            --text:#e8eef8; --muted:#93a4bd; --border:rgba(90,140,230,.18);
            --primary:#3b82f6; --primary-strong:#2563eb; --primary-soft:rgba(59,130,246,.15);
            --accent:#f59e0b; --accent-soft:rgba(245,158,11,.15);
            --danger:#ef4444; --danger-soft:rgba(239,68,68,.15);
            --green:#22c55e; --green-soft:rgba(34,197,94,.15);
            --shadow:0 4px 20px rgba(17,24,39,.05);
        }
        * { box-sizing:border-box; }
        html, body { min-height:100vh; }
        body { margin:0; font-family:'Quicksand',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; }
        .public-wrap { padding:26px; }
        .content { max-width:1000px; margin:0 auto; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:24px; box-shadow:var(--shadow); }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border:1px solid var(--primary); border-radius:7px; background:var(--primary); color:#fff; font-size:13.5px; font-weight:500; text-decoration:none; cursor:pointer; transition:background .16s, border-color .16s; }
        .btn:hover { background:var(--primary-strong); border-color:var(--primary-strong); }
        .btn--ghost { background:var(--surface-2); color:var(--text); border-color:var(--border); }
        .btn--ghost:hover { background:var(--surface); }
        .btn--danger { background:var(--danger); border-color:var(--danger); }
        .btn--danger:hover { filter:brightness(.92); }
        .btn--green { background:var(--green); border-color:var(--green); }
        .btn--green:hover { filter:brightness(.92); }
    </style>
</head>
<body>
    <div class="public-wrap">
        <div class="content">
            @yield('content')
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-copy]').forEach(function(el){
            el.addEventListener('click', function(){
                var target = document.getElementById(el.dataset.copy);
                if(!target) return;
                target.select();
                document.execCommand('copy');
                alert('Enlace copiado');
            });
        });
    </script>
</body>
</html>
