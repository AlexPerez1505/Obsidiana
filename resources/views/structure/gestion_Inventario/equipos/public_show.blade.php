<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $equipo->tipo_equipo }} · {{ $equipo->no_serie }}</title>
    <style>
        :root { --bg:#f6f7f9; --surface:#fff; --text:#1f2937; --muted:#6b7280; --border:#e5e7eb; --primary:#3b82f6; }
        * { box-sizing:border-box; }
        body { margin:0; padding:24px; font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background:var(--bg); color:var(--text); }
        .card { max-width:520px; margin:0 auto; background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:28px; box-shadow:0 10px 30px rgba(0,0,0,.06); }
        .header { text-align:center; margin-bottom:22px; }
        .header h1 { margin:0 0 6px; font-size:1.35rem; }
        .header p { margin:0; color:var(--muted); font-size:.9rem; }
        .img-wrap { min-height:180px; display:grid; place-items:center; border:1px dashed var(--border); border-radius:14px; margin-bottom:22px; overflow:hidden; background:#f9fafb; }
        .img-wrap img { max-width:100%; max-height:240px; object-fit:contain; }
        .no-img { color:#9ca3af; }
        .grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
        .item { padding:12px 14px; background:#f9fafb; border-radius:10px; }
        .item label { display:block; color:var(--muted); font-size:.68rem; font-weight:800; text-transform:uppercase; margin-bottom:4px; }
        .item span { font-size:.92rem; font-weight:700; }
        .full { grid-column:1 / -1; }
        .badge { display:inline-block; padding:4px 12px; border-radius:999px; font-size:.78rem; font-weight:800; border:1px solid var(--border); }
        .badge.green { color:#16a329; background:#f0fdf4; }
        .badge.blue { color:#1689ff; background:#eff6ff; }
        .badge.red { color:#ef4444; background:#fef2f2; }
        .footer { text-align:center; margin-top:22px; color:var(--muted); font-size:.8rem; }
        @media (max-width:520px) { .grid { grid-template-columns:1fr; } body { padding:12px; } .card { padding:18px; } }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ $equipo->tipo_equipo }}</h1>
            <p>{{ $equipo->marca }} · {{ $equipo->modelo }}</p>
        </div>

        <div class="img-wrap">
            @if($equipo->imagen_path)
                <img src="{{ asset('storage/' . $equipo->imagen_path) }}" alt="{{ $equipo->tipo_equipo }}">
            @else
                <svg class="no-img" width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            @endif
        </div>

        @php
            $tone = match($equipo->estado) {
                'Mantenimiento' => 'blue',
                'Inactivo', 'Dañado', 'Baja' => 'red',
                default => 'green',
            };
        @endphp

        <div class="grid">
            <div class="item"><label>Subtipo</label><span>{{ $equipo->subtipo ?: '—' }}</span></div>
            <div class="item"><label>Estado</label><span class="badge {{ $tone }}">{{ $equipo->estado ?: 'Activo' }}</span></div>
            <div class="item"><label>Número de serie</label><span>{{ $equipo->no_serie ?: '—' }}</span></div>
            <div class="item"><label>Número de serie base</label><span>{{ $equipo->no_serie_base ?: '—' }}</span></div>
            <div class="item"><label>Fecha de adquisición</label><span>{{ $equipo->fecha_adquisicion ? $equipo->fecha_adquisicion->format('d/m/Y') : '—' }}</span></div>
            <div class="item"><label>Registrado por</label><span>{{ $equipo->registradoPor?->name ?? '—' }}</span></div>
            <div class="item full"><label>Descripción</label><span>{{ $equipo->descripcion ?: '—' }}</span></div>
        </div>

        <div class="footer">
            Código: {{ $equipo->no_serie_base ?: $equipo->no_serie ?: $equipo->id }}
        </div>
    </div>
</body>
</html>
