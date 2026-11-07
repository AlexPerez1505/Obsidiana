@php
    $emp = config('medibuy.empresa');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquetas · {{ $movimiento->folio }}</title>

    <style>
        /*
           Hoja de etiquetas para imprimir y pegar en cada pieza.

           Se dibuja con CSS del navegador y no con dompdf a propósito: así
           el QR sale vectorial y nítido al tamaño real de la etiqueta, que
           es lo que hace que una pistola lectora lo agarre a la primera.
        */
        * { box-sizing:border-box; }
        body { margin:0; background:#f1f3f6; color:#111;
               font-family:system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }

        .barra { position:sticky; top:0; z-index:5; display:flex; align-items:center;
                 gap:12px; padding:12px 18px; background:#fff; border-bottom:1px solid #e3e6ea; }
        .barra h1 { flex:1; margin:0; font-size:15px; font-weight:600; }
        .barra .n { color:#6b7280; font-size:13px; }
        .barra button, .barra a { padding:8px 14px; border:1px solid #d7dbe0; border-radius:8px;
                                  background:#fff; color:#111; font-size:13.5px; font-family:inherit;
                                  text-decoration:none; cursor:pointer; }
        .barra button.primario { background:#007aff; border-color:#007aff; color:#fff; }

        .hoja { max-width:900px; margin:0 auto; padding:18px; }

        .rejilla { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px, 1fr)); gap:10px; }

        .etiqueta { display:flex; align-items:center; gap:11px; padding:11px;
                    background:#fff; border:1px solid #cfd4da; border-radius:8px;
                    break-inside:avoid; page-break-inside:avoid; }
        .etiqueta .qr { width:74px; height:74px; flex:0 0 74px; }
        .etiqueta .qr svg { width:100%; height:100%; display:block; }
        .etiqueta .txt { min-width:0; }
        .etiqueta .cod { font-size:14px; font-weight:800; letter-spacing:.03em; font-family:ui-monospace, Consolas, monospace; }
        .etiqueta .eq { margin-top:2px; font-size:11px; line-height:1.3; color:#374151;
                        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .etiqueta .em { margin-top:3px; font-size:9px; letter-spacing:.06em;
                        text-transform:uppercase; color:#9ca3af; }

        .vacio { padding:40px; text-align:center; color:#6b7280; background:#fff;
                 border:1px solid #e3e6ea; border-radius:12px; }

        @media print {
            body { background:#fff; }
            .barra { display:none; }
            .hoja { max-width:none; padding:0; }
            .rejilla { grid-template-columns:repeat(3, 1fr); gap:6px; }
            .etiqueta { border-color:#999; }
            @page { margin:10mm; }
        }
    </style>
</head>
<body>

<div class="barra">
    <h1>Etiquetas de {{ $movimiento->folio }}</h1>
    <span class="n">{{ $piezas->count() }} pieza(s)</span>
    <a href="{{ route('inventory.movimientos.show', $movimiento) }}">Volver</a>
    <button type="button" class="primario" onclick="window.print()">Imprimir</button>
</div>

<div class="hoja">
    @if ($piezas->isEmpty())
        <p class="vacio">Esta entrada no tiene piezas registradas.</p>
    @else
        <div class="rejilla">
            @foreach ($piezas as $pieza)
                @include('structure.gestion_Inventario.entrada_salida._etiqueta', ['pieza' => $pieza])
            @endforeach
        </div>
    @endif
</div>

</body>
</html>
