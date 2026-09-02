{{--
    PDF de cotización / venta / factura-borrador.

    Variables: $doc (customer, seller, items, pagos, fichas y montos),
               $titulo (ej. "Cotización"), $leyenda (pie), $urlPublica (QR),
               $sello (opcional, ej. "BORRADOR").

    Criterio de diseño: nada de recuadros ni rellenos. La estructura se arma
    con espacio en blanco, jerarquía de tamaños y alguna línea fina. Las
    tablas son solo para acomodar, sin bordes visibles.

    Ojo con dompdf: no hay flexbox ni grid, y el encabezado y el pie se
    repiten en cada hoja con position:fixed.
--}}
@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');
    $ban = config('medibuy.banco');
    $comp = config('medibuy.comprobantes');
    $terminos = config('medibuy.terminos', []);

    $leyenda = $leyenda ?? 'Precios en MXN';

    $items = $doc->items;
    $cliente = trim(($doc->customer->nombre ?? '') . ' ' . ($doc->customer->apellido ?? ''));
    $financiado = $doc->modalidad === 'financiamiento' && $doc->pagos->count();

    $logoUri = \App\Support\LogoPdf::dataUri();

    $conArchivo = isset($doc->fichas) ? $doc->fichas->filter(fn ($f) => (bool) $f->archivo) : collect();
    $soloTexto = isset($doc->fichas) ? $doc->fichas->filter(fn ($f) => ! $f->archivo && $f->contenido) : collect();

    $hayBanco = array_filter($ban);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96px 46px 56px; }

        /* Helvetica es fuente base del PDF: no se incrusta, así que el archivo
           pesa mucho menos, y es más angosta que DejaVu. Probado: sostiene
           acentos, ñ, ¿ ¡ y la raya larga. */
        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 10px; line-height: 1.55; }

        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }

        .r { text-align: right; }
        .c { text-align: center; }

        /* ===================== Encabezado y pie ===================== */
        .head { position: fixed; top: -84px; left: 0; right: 0; height: 66px; }
        .head td { vertical-align: middle; }
        .head img { height: 42px; }
        .head .marca { color: #1a1d23; font-size: 14px; font-weight: bold; }
        .head .tipo { color: #1a1d23; font-size: 12px; font-weight: bold; letter-spacing: -.1px; }
        .head .meta { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; }

        .pie { position: fixed; bottom: -46px; left: 0; right: 0; height: 40px;
               padding-top: 9px; border-top: 1px solid #ededf0; }
        .pie td { font-size: 8px; color: #a8aeb8; line-height: 1.5; }
        .pie .quien { color: #6b7280; font-weight: bold; }
        .pie .num:after { content: counter(page); }

        /* ===================== Tipografía de sección ===================== */
        .rot { margin: 0 0 9px; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
               letter-spacing: 1.6px; text-transform: uppercase; }

        h1.t { margin: 0 0 16px; color: #1a1d23; font-size: 20px; font-weight: bold; letter-spacing: -.4px; }

        /* Pares etiqueta / dato, sin recuadro */
        .par td { padding: 0 0 3px; font-size: 9.5px; }
        .par .k { width: 76px; color: #a8aeb8; }
        .par .v { color: #4b5563; }
        .destinatario { margin: 0 0 4px; color: #1a1d23; font-size: 13px; font-weight: bold; }

        /* ===================== Conceptos ===================== */
        .conceptos { margin-top: 20px; }
        .conceptos .cab td { padding: 0 0 7px; border-bottom: 1px solid #1a1d23;
                             color: #1a1d23; font-size: 7.5px; font-weight: bold;
                             letter-spacing: 1.2px; text-transform: uppercase; }
        .conceptos .fila td { padding: 8px 0; border-bottom: 1px solid #f0f0f2; vertical-align: middle; }
        .conceptos .foto { width: 42px; padding-right: 10px; }
        .conceptos .foto img { width: 34px; height: 34px; }
        .conceptos .nom { color: #1a1d23; font-size: 10.5px; font-weight: bold; }
        .conceptos .det { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; }
        .conceptos .imp { color: #1a1d23; font-size: 10.5px; font-weight: bold; }
        .conceptos .uni { color: #a8aeb8; font-size: 8.5px; }
        .regalo { color: #0f9d58; font-size: 9px; font-weight: bold; }

        /* ===================== Totales ===================== */
        .totales { width: 42%; margin-left: 58%; margin-top: 12px; }
        .totales td { padding: 4px 0; font-size: 9.5px; color: #6b7280; }
        .totales .v { text-align: right; color: #3d4450; }
        .totales .granTotal td { padding-top: 12px; border-top: 1px solid #1a1d23;
                                 color: #1a1d23; font-weight: bold; }
        .totales .granTotal .e { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; }
        .totales .granTotal .v { font-size: 17px; letter-spacing: -.4px; }
        .totales .aparte td { padding-top: 9px; color: #0f9d58; font-size: 10px; font-weight: bold; }

        /* ===================== Texto suelto ===================== */
        .parrafo { margin: 14px 0 0; font-size: 9.5px; color: #6b7280; }
        .parrafo b { color: #3d4450; }

        /* ===================== QR ===================== */
        .qr { margin-top: 18px; }
        .qr td { vertical-align: middle; }
        .qr .img { width: 92px; }
        .qr .img img { width: 82px; height: 82px; }
        .qr .txt { padding-left: 13px; color: #a8aeb8; font-size: 9px; }
        .qr .txt b { display: block; color: #3d4450; font-size: 9.5px; }

        .sello { display: inline-block; margin-top: 3px; padding: 1px 7px;
                 border: 1px solid #d9a441; color: #a8752b;
                 font-size: 7px; font-weight: bold; letter-spacing: 1px; }

        /* ===================== Hoja de condiciones ===================== */
        .hoja { page-break-before: always; }

        .lineas td { padding: 5px 0; border-bottom: 1px solid #f0f0f2; font-size: 9.5px; }
        .lineas tr:last-child td { border-bottom: 0; }
        .lineas .cuando { color: #a8aeb8; font-size: 8.5px; }
        .lineas .monto { text-align: right; color: #1a1d23; font-weight: bold; }

        .resumen { margin-top: 4px; }
        .resumen td { padding: 4px 0; font-size: 9.5px; color: #6b7280; }
        .resumen .v { text-align: right; color: #1a1d23; font-weight: bold; }

        .banco td { padding: 5px 0; border-bottom: 1px solid #f0f0f2; font-size: 9.5px; }
        .banco tr:last-child td { border-bottom: 0; }
        .banco .k { width: 108px; color: #a8aeb8; }
        .banco .v { color: #1a1d23; font-weight: bold; letter-spacing: .2px; }

        ol.terminos { margin: 0; padding-left: 13px; }
        ol.terminos li { margin-bottom: 3px; font-size: 8.5px; color: #6b7280; line-height: 1.6; }

        .bloque { margin-top: 18px; }

        .ficha h2 { margin: 0 0 9px; color: #1a1d23; font-size: 13px; }
        .ficha p { margin: 0; font-size: 9.5px; color: #6b7280; }
    </style>
</head>
<body>

{{-- ===================== En cada hoja ===================== --}}
<div class="head">
    <table>
        <tr>
            <td>
                @if ($logoUri)
                    <img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">
                @else
                    <div class="marca">{{ $emp['nombre'] }}</div>
                @endif
            </td>
            <td class="r">
                <div class="tipo">{{ $titulo }} {{ $doc->folio }}</div>
                <div class="meta">{{ $doc->created_at?->format('d/m/Y') }}</div>
                @if (! empty($sello))<div class="sello">{{ $sello }}</div>@endif
            </td>
        </tr>
    </table>
</div>

<div class="pie">
    <table>
        <tr>
            <td>
                @if ($con['nombre'])<span class="quien">{{ $con['nombre'] }}</span> · {{ $con['cargo'] }}<br>@endif
                @if ($con['telefono']){{ $con['telefono'] }}@endif
                @if ($con['correo']) · {{ $con['correo'] }}@endif
            </td>
            <td class="r">
                @if ($emp['web']){{ $emp['web'] }}@endif
                @if ($emp['ubicacion']) · {{ $emp['ubicacion'] }}@endif<br>
                {{ $leyenda }} · <span class="num"></span>
            </td>
        </tr>
    </table>
</div>

{{-- ===================== Hoja 1 ===================== --}}
<h1 class="t">{{ $titulo }} {{ $doc->folio }}</h1>

<table>
    <tr>
        <td style="width:52%; padding-right:24px;">
            <p class="rot">Para</p>
            <div class="destinatario">{{ $cliente ?: '—' }}</div>
            <table class="par">
                @if ($doc->customer?->rfc)
                    <tr><td class="k">RFC</td><td class="v">{{ $doc->customer->rfc }}</td></tr>
                @endif
                @if ($doc->customer?->telefono)
                    <tr><td class="k">Teléfono</td><td class="v">{{ $doc->customer->telefono }}</td></tr>
                @endif
                @if ($doc->customer?->correo)
                    <tr><td class="k">Correo</td><td class="v">{{ $doc->customer->correo }}</td></tr>
                @endif
            </table>
        </td>
        <td style="width:48%;">
            <p class="rot">Detalles</p>
            <table class="par">
                <tr><td class="k">Emitido</td><td class="v">{{ $doc->created_at?->format('d/m/Y') ?: '—' }}</td></tr>
                <tr>
                    <td class="k">Pago</td>
                    <td class="v">{{ ucfirst($doc->modalidad) }}@if ($financiado) · {{ $doc->num_meses }} meses @endif</td>
                </tr>
                <tr><td class="k">Atendió</td><td class="v">{{ $doc->seller?->name ?: '—' }}</td></tr>
                @if ($doc->lugar_propuesta)
                    <tr><td class="k">Congreso</td><td class="v">{{ $doc->lugar_propuesta }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<table class="conceptos">
    <tr class="cab">
        <td colspan="2">Concepto</td>
        <td class="c" style="width:11%;">Cant.</td>
        <td class="r" style="width:22%;">Importe</td>
    </tr>
    @foreach ($items as $it)
        <tr class="fila">
            <td class="foto">
                {{-- La foto se lee del disco y se incrusta: dompdf no puede
                     descargarla del mismo servidor que está armando el PDF.
                     Si el archivo no está, la fila se dibuja igual sin foto. --}}
                @php ($foto = \App\Support\ImagenPdf::dataUri($it->imagen))
                @if ($foto)
                    <img src="{{ $foto }}" alt="">
                @endif
            </td>
            <td>
                <div class="nom">{{ $it->nombre }}</div>
                <div class="det">
                    {{ collect([$it->marca, $it->modelo])->filter()->implode(' · ') ?: 'Sin especificar' }}
                </div>
                @if (! empty($it->no_series))
                    <div class="det">No. Serie: {{ $it->no_series }}</div>
                @endif
            </td>
            <td class="c">{{ $it->cantidad }}</td>
            <td class="r">
                @if ($it->es_regalo)
                    <span class="regalo">Regalo</span>
                @else
                    <div class="imp">${{ number_format($it->importe(), 2) }}</div>
                    @if ($it->cantidad > 1)
                        <div class="uni">${{ number_format($it->precio_unitario + $it->sobreprecio, 2) }} c/u</div>
                    @endif
                @endif
            </td>
        </tr>
    @endforeach
</table>

<table class="totales">
    <tr><td>Subtotal</td><td class="v">${{ number_format($doc->subtotal, 2) }}</td></tr>
    @if ($doc->descuento_monto > 0)
        <tr><td>Descuento</td><td class="v">-${{ number_format($doc->descuento_monto, 2) }}</td></tr>
    @endif
    @if ($doc->envio > 0)
        <tr><td>Envío</td><td class="v">${{ number_format($doc->envio, 2) }}</td></tr>
    @endif
    @if ($doc->aplica_iva)
        <tr><td>IVA 16%</td><td class="v">${{ number_format($doc->iva_monto, 2) }}</td></tr>
    @endif
    <tr class="granTotal"><td class="e">Total</td><td class="v">${{ number_format($doc->total, 2) }}</td></tr>
    @if ($doc->valor_a_cuenta > 0)
        <tr class="aparte"><td>A financiar</td><td class="v r">${{ number_format($doc->total_contrato, 2) }}</td></tr>
    @endif
</table>

@if ($doc->nota_cliente)
    <p class="parrafo"><b>Nota.</b> {{ $doc->nota_cliente }}</p>
@endif

@if ($conArchivo->isNotEmpty())
    <p class="parrafo"><b>Fichas técnicas anexas.</b> {{ $conArchivo->pluck('titulo')->implode(' · ') }}</p>
@endif

@if (! empty($urlPublica))
    <table class="qr">
        <tr>
            <td class="img">
                {{-- Se genera del tamaño final: dompdf dibuja el SVG a su medida
                     intrínseca y no respeta el width del CSS. --}}
                <img src="{{ \App\Support\CodigoQr::dataUri($urlPublica, 82) }}" alt="Código QR">
            </td>
            <td class="txt">
                <b>Consulta en línea</b>
                Escanea para ver este documento y descargarlo.
            </td>
        </tr>
    </table>
@endif

{{-- ===================== Hoja 2: pago y condiciones ===================== --}}
@if ($financiado || $terminos || $hayBanco)
    <div class="hoja">
        <h1 class="t">Forma de pago</h1>

        @if ($financiado)
            <p class="rot">Calendario</p>
            <table class="lineas">
                @foreach ($doc->pagos as $p)
                    <tr>
                        <td>
                            {{ $p->nombre }}
                            <div class="cuando">{{ optional($p->fecha)->translatedFormat('d \d\e F, Y') ?: 'Por definir' }}</div>
                        </td>
                        <td class="monto">${{ number_format($p->monto, 2) }}</td>
                    </tr>
                @endforeach
            </table>

            <table class="resumen" style="width:42%; margin-left:58%;">
                <tr><td>Total</td><td class="v">${{ number_format($doc->total, 2) }}</td></tr>
                @if ($doc->valor_a_cuenta > 0)
                    <tr><td>Pago inicial</td><td class="v">${{ number_format($doc->valor_a_cuenta, 2) }}</td></tr>
                    <tr><td>A financiar</td><td class="v">${{ number_format($doc->total_contrato, 2) }}</td></tr>
                @endif
                <tr><td>Plazo</td><td class="v">{{ $doc->num_meses }} {{ $doc->num_meses === 1 ? 'mes' : 'meses' }}</td></tr>
            </table>
        @endif

        @if ($hayBanco)
            <div class="bloque">
                <p class="rot">Transferencia</p>
                <table class="banco">
                    @if ($ban['nombre'])<tr><td class="k">Banco</td><td class="v">{{ $ban['nombre'] }}</td></tr>@endif
                    @if ($ban['beneficiario'])<tr><td class="k">Beneficiario</td><td class="v">{{ $ban['beneficiario'] }}</td></tr>@endif
                    @if ($ban['clabe'])<tr><td class="k">CLABE</td><td class="v">{{ $ban['clabe'] }}</td></tr>@endif
                    @if ($ban['cuenta'])<tr><td class="k">Cuenta</td><td class="v">{{ $ban['cuenta'] }}</td></tr>@endif
                    @if ($ban['tarjeta'])<tr><td class="k">Tarjeta</td><td class="v">{{ $ban['tarjeta'] }}</td></tr>@endif
                    <tr><td class="k">Concepto</td><td class="v">{{ $doc->folio }}</td></tr>
                </table>

                @if ($comp['correo'] || $comp['whatsapp'])
                    <p class="parrafo" style="margin-top:14px;">
                        Envía tu comprobante
                        @if ($comp['correo']) a <b>{{ $comp['correo'] }}</b>@endif
                        @if ($comp['whatsapp']) o por WhatsApp al <b>{{ $comp['whatsapp'] }}</b>@endif.
                    </p>
                @endif
            </div>
        @endif

        @if ($terminos)
            <div class="bloque">
                <p class="rot">Términos y condiciones</p>
                <ol class="terminos">
                    @foreach ($terminos as $t)
                        <li>{{ $t }}</li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
@endif

{{-- ===================== Fichas sin PDF ===================== --}}
@foreach ($soloTexto as $f)
    <div class="hoja ficha">
        <h2>{{ $f->titulo }}</h2>
        <p>{!! nl2br(e($f->contenido)) !!}</p>
    </div>
@endforeach

</body>
</html>
