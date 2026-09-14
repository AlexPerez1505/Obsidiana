@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');

    $nombre = trim(collect([
        $producto?->tipo_equipo,
        $producto?->subtipo,
    ])->filter()->implode(' · ')) ?: 'Equipo';

    $marcaModelo = collect([$producto?->marca, $producto?->modelo])->filter()->implode(' ');

    $fotoUnidad = $pieza->fotoUrl();
    $fotoCatalogo = $producto?->imagen_path ? asset('storage/'.$producto->imagen_path) : null;
    $video = $entrada?->video_path ? asset('storage/'.$entrada->video_path) : null;

    // Cómo se pinta cada estado del proceso.
    $tono = match ($pieza->estado) {
        'disponible' => 'ok',
        'vendido' => 'info',
        'baja' => 'mal',
        default => 'proceso',
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Se llega por el QR de la pieza, no por buscador. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $pieza->codigo }} · {{ $nombre }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:#f5f7fa; --surface:#fff; --surface-2:#f7f8fa;
            --text:#333; --muted:#8b8f98; --border:#ebebeb;
            --primary:#007aff; --green:#15803d; --green-soft:#e8f7ec;
            --ambar:#b45309; --ambar-soft:#fdf3e3; --rojo:#b91c1c; --rojo-soft:#fdecec;
            --azul-soft:#e8f1fd;
        }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text);
               font-family:Quicksand, system-ui, sans-serif; font-size:15px; line-height:1.55; }

        .pw { max-width:760px; margin:0 auto; padding:22px 16px 48px; }

        .marca { display:flex; align-items:center; gap:10px; margin-bottom:18px; }
        .marca img { height:34px; }
        .marca b { font-size:16px; }

        .tarjeta { background:var(--surface); border:1px solid var(--border);
                   border-radius:14px; padding:20px; margin-bottom:14px; }

        .cab { display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap; }
        .cab .txt { flex:1; min-width:200px; }
        .codigo { display:inline-block; padding:3px 9px; border-radius:6px;
                  background:var(--surface-2); border:1px solid var(--border);
                  font-size:12.5px; font-weight:700; letter-spacing:.04em; color:var(--muted); }
        h1 { margin:8px 0 2px; font-size:22px; font-weight:700; line-height:1.25; }
        .mm { margin:0; color:var(--muted); font-size:14.5px; }

        .foto-principal { width:120px; height:120px; object-fit:cover; border-radius:12px;
                          border:1px solid var(--border); background:var(--surface-2); flex:0 0 auto; }

        .chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .chip { display:inline-flex; align-items:center; gap:7px; padding:5px 12px;
                border-radius:999px; font-size:13px; font-weight:600;
                background:var(--surface-2); color:var(--muted); border:1px solid var(--border); }
        .chip::before { content:""; width:7px; height:7px; border-radius:50%; background:currentColor; }
        .chip.ok { background:var(--green-soft); color:var(--green); border-color:transparent; }
        .chip.info { background:var(--azul-soft); color:var(--primary); border-color:transparent; }
        .chip.proceso { background:var(--ambar-soft); color:var(--ambar); border-color:transparent; }
        .chip.mal { background:var(--rojo-soft); color:var(--rojo); border-color:transparent; }
        .chip.plano::before { display:none; }

        h2 { margin:0 0 12px; font-size:11px; font-weight:700; letter-spacing:.08em;
             text-transform:uppercase; color:var(--muted); }

        .datos { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px; }
        .datos .e { display:block; color:var(--muted); font-size:12.5px; }
        .datos .v { display:block; margin-top:2px; font-weight:600; }

        .galeria { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:10px; }
        .galeria img { width:100%; aspect-ratio:1; object-fit:cover; border-radius:10px;
                       border:1px solid var(--border); background:var(--surface-2); }

        video { width:100%; border-radius:10px; border:1px solid var(--border); background:#000; }

        .punto { display:flex; align-items:flex-start; gap:10px; padding:10px 0;
                 border-bottom:1px solid var(--border); }
        .punto:last-child { border-bottom:none; }
        .punto .r { flex:0 0 auto; padding:2px 9px; border-radius:6px;
                    font-size:11.5px; font-weight:700; }
        .punto .r.si { background:var(--green-soft); color:var(--green); }
        .punto .r.no { background:var(--rojo-soft); color:var(--rojo); }
        .punto .r.na { background:var(--surface-2); color:var(--muted); }
        .punto .t { flex:1; min-width:0; font-size:14px; }
        .punto .n { display:block; margin-top:2px; color:var(--muted); font-size:13px; }

        .pie { margin-top:22px; text-align:center; color:var(--muted); font-size:13px; line-height:1.7; }
        .pie a { color:var(--primary); text-decoration:none; }

        @media (max-width:520px) {
            .foto-principal { width:84px; height:84px; }
            h1 { font-size:19px; }
        }
    </style>
</head>
<body>
<div class="pw">

    <div class="marca">
        <img src="{{ asset('images/logomedy.png') }}" alt="{{ $emp['nombre'] }}"
             onerror="this.style.display='none'">
        <b>{{ $emp['nombre'] }}</b>
    </div>

    {{-- ===================== Identidad de la pieza ===================== --}}
    <div class="tarjeta">
        <div class="cab">
            @if ($fotoUnidad || $fotoCatalogo)
                <img class="foto-principal" src="{{ $fotoUnidad ?: $fotoCatalogo }}" alt="{{ $nombre }}">
            @endif

            <div class="txt">
                <span class="codigo">{{ $pieza->codigo }}</span>
                <h1>{{ $nombre }}</h1>
                @if ($marcaModelo)<p class="mm">{{ $marcaModelo }}</p>@endif

                <div class="chips">
                    <span class="chip {{ $tono }}">{{ $pieza->estadoLabel() }}</span>
                    <span class="chip {{ $pieza->condicion === 'usado' ? 'proceso' : 'ok' }}">
                        {{ $pieza->condicion === 'usado' ? 'Equipo usado' : 'Equipo nuevo' }}
                    </span>
                    @if ($estadoGeneral)
                        <span class="chip plano">{{ $estadoGeneral }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Datos ===================== --}}
    <div class="tarjeta">
        <h2>Datos de la pieza</h2>
        <div class="datos">
            <div><span class="e">Etiqueta</span><span class="v">{{ $pieza->codigo }}</span></div>
            <div><span class="e">Número de serie</span><span class="v">{{ $pieza->no_serie ?: 'Sin serie' }}</span></div>
            <div><span class="e">Proceso actual</span><span class="v">{{ $pieza->estadoLabel() }}</span></div>
            @if ($entrada?->movement_date)
                <div><span class="e">Ingresó</span><span class="v">{{ $entrada->movement_date->format('d/m/Y') }}</span></div>
            @endif
            @if ($producto?->tipo_equipo)
                <div><span class="e">Tipo</span><span class="v">{{ $producto->tipo_equipo }}</span></div>
            @endif
            @if ($producto?->marca)
                <div><span class="e">Marca</span><span class="v">{{ $producto->marca }}</span></div>
            @endif
            @if ($producto?->modelo)
                <div><span class="e">Modelo</span><span class="v">{{ $producto->modelo }}</span></div>
            @endif
        </div>

        @if ($producto?->descripcion)
            <p style="margin:16px 0 0; color:var(--muted);">{{ $producto->descripcion }}</p>
        @endif
    </div>

    {{-- ===================== Imágenes ===================== --}}
    @if ($fotoUnidad && $fotoCatalogo)
        <div class="tarjeta">
            <h2>Imágenes</h2>
            <div class="galeria">
                <img src="{{ $fotoUnidad }}" alt="Esta pieza">
                <img src="{{ $fotoCatalogo }}" alt="{{ $nombre }}">
            </div>
        </div>
    @endif

    {{-- ===================== Video ===================== --}}
    @if ($video)
        <div class="tarjeta">
            <h2>Video de verificación</h2>
            <video controls preload="metadata" playsinline src="{{ $video }}"></video>
            <p style="margin:10px 0 0; color:var(--muted); font-size:13.5px;">
                Grabado al recibir el equipo, para dejar constancia de cómo llegó.
            </p>
        </div>
    @endif

    {{-- ===================== Checklist (solo usado) ===================== --}}
    @if ($checklist->isNotEmpty())
        <div class="tarjeta">
            <h2>Revisión al recibirlo</h2>
            @foreach ($checklist as $punto)
                <div class="punto">
                    <span class="r {{ $punto['clave'] }}">{{ $punto['respuesta'] }}</span>
                    <span class="t">
                        {{ $punto['titulo'] }}
                        @if ($punto['nota'])<span class="n">{{ $punto['nota'] }}</span>@endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <p class="pie">
        {{ $emp['nombre'] }}
        @if ($con['telefono']) · {{ $con['telefono'] }} @endif
        @if ($con['correo']) · <a href="mailto:{{ $con['correo'] }}">{{ $con['correo'] }}</a> @endif
        <br>
        ¿Dudas sobre este equipo? Menciona la etiqueta <b>{{ $pieza->codigo }}</b>.
    </p>

</div>
</body>
</html>
