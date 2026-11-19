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

        .rejilla { display:grid; grid-template-columns:repeat(auto-fill, 51mm); gap:10px; justify-content:center; }

        .etiqueta { display:flex; align-items:center; gap:1.5mm; padding:1mm; width:51mm; height:25mm;
                    background:#fff; outline:1px solid #cfd4da; overflow:hidden;
                    break-inside:avoid; page-break-inside:avoid; }
        .etiqueta .qr { width:21mm; height:21mm; flex:0 0 21mm; padding:1mm; background:#fff; }
        .etiqueta .qr svg { width:100%; height:100%; display:block; }
        .etiqueta .txt { min-width:0; flex:1; max-height:23mm; overflow:hidden; }
        .etiqueta .serie { font-size:7.5pt; line-height:1.1; font-weight:800; overflow-wrap:anywhere;
                          font-family:ui-monospace, Consolas, monospace; }
        .etiqueta .serie-larga { font-size:5.5pt; }
        .etiqueta .cod { margin-top:.3mm; font-size:5pt; line-height:1.1; overflow-wrap:anywhere;
                        font-family:ui-monospace, Consolas, monospace; }
        .etiqueta .modelo { margin-top:.8mm; font-size:6.5pt; line-height:1.1; font-weight:800; overflow-wrap:anywhere; }
        .etiqueta .modelo-largo { font-size:5.5pt; }
        .etiqueta .eq { margin-top:.4mm; font-size:5pt; line-height:1.1; color:#111;
                        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .etiqueta .em { margin-top:.5mm; font-size:4.5pt; line-height:1.1;
                        text-transform:uppercase; color:#111; }
        .aviso { margin:0 0 18px; color:#374151; font-size:13px; line-height:1.5; }

        .vacio { padding:40px; text-align:center; color:#6b7280; background:#fff;
                 border:1px solid #e3e6ea; border-radius:12px; }

        @media print {
            @page { size:51mm 25mm; margin:0; }
            html, body { width:51mm; margin:0; padding:0; background:#fff; }
            .barra, .aviso, .vacio { display:none; }
            .hoja { width:51mm; max-width:none; margin:0; padding:0; }
            .rejilla { display:block; }
            .etiqueta { margin:0; outline:none; break-after:page; page-break-after:always; }
            .etiqueta:last-child { break-after:auto; page-break-after:auto; }
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
    <p class="aviso">Formato térmico de 51 × 25 mm: una pieza por etiqueta. Al imprimir, selecciona la impresora térmica,
        configura el papel en 51 × 25 mm, escala al 100 %, sin márgenes y sin encabezados ni pies de página.
        Prueba primero una etiqueta y comprueba la lectura del QR antes de imprimir el lote.</p>
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
