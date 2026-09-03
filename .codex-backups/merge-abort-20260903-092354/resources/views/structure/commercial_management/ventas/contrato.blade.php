{{--
    Contrato de compraventa con reserva de dominio.

    Solo se emite para ventas a plazos: en una venta de contado no hay nada
    que garantizar en el tiempo y el comprobante basta.
--}}
@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');
    $ban = config('medibuy.banco');

    $c = $venta->customer;
    $comprador = trim(($c->nombre ?? '') . ' ' . ($c->apellido ?? '')) ?: '—';
    $vendedor = $ban['beneficiario'] ?: $emp['nombre'];

    $logoUri = \App\Support\LogoPdf::dataUri();

    $enLetra = fn ($n) => number_format($n, 2);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96px 52px 62px; }

        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 10px; line-height: 1.65; text-align: justify; }

        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .r { text-align: right; } .c { text-align: center; }

        .head { position: fixed; top: -84px; left: 0; right: 0; height: 66px; }
        .head td { vertical-align: middle; text-align: left; }
        .head img { height: 42px; }
        .head .tipo { color: #1a1d23; font-size: 12px; font-weight: bold; text-align: right; }
        .head .meta { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; text-align: right; }

        .pie { position: fixed; bottom: -46px; left: 0; right: 0; height: 40px;
               padding-top: 9px; border-top: 1px solid #ededf0; }
        .pie td { font-size: 8px; color: #a8aeb8; line-height: 1.5; text-align: left; }
        .pie .num:after { content: counter(page); }

        h1.t { margin: 0 0 4px; color: #1a1d23; font-size: 17px; font-weight: bold;
               letter-spacing: -.3px; text-align: center; }
        .sub { margin: 0 0 22px; color: #a8aeb8; font-size: 9px; text-align: center; }

        .rot { margin: 20px 0 8px; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
               letter-spacing: 1.6px; text-transform: uppercase; text-align: left; }

        p { margin: 0 0 9px; }
        b { color: #1a1d23; }

        .partes td { padding: 0 0 3px; font-size: 9.5px; text-align: left; }
        .partes .k { width: 92px; color: #a8aeb8; }
        .partes .v { color: #1a1d23; font-weight: bold; }

        .equipo td { padding: 7px 0; border-bottom: 1px solid #f0f0f2; font-size: 9.5px; text-align: left; }
        .equipo tr:last-child td { border-bottom: 0; }
        .equipo .nom { color: #1a1d23; font-weight: bold; }
        .equipo .det { color: #a8aeb8; font-size: 8.5px; }

        .pagos td { padding: 5px 0; border-bottom: 1px solid #f0f0f2; font-size: 9.5px; text-align: left; }
        .pagos tr:last-child td { border-bottom: 0; }
        .pagos .m { text-align: right; color: #1a1d23; font-weight: bold; }
        .pagos .f { color: #a8aeb8; font-size: 8.5px; }

        ol { margin: 0; padding-left: 15px; }
        ol li { margin-bottom: 7px; }

        .firmas { margin-top: 54px; page-break-inside: avoid; }
        .firmas td { width: 50%; padding-top: 42px; text-align: center; font-size: 9px; color: #a8aeb8; }
        .firmas td:first-child { padding-right: 26px; }
        .firmas .linea { border-top: 1px solid #a8aeb8; padding-top: 7px; }
        .firmas .quien { color: #1a1d23; font-size: 9.5px; font-weight: bold; }
    </style>
</head>
<body>

<div class="head">
    <table>
        <tr>
            <td>
                @if ($logoUri)<img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">@else<b>{{ $emp['nombre'] }}</b>@endif
            </td>
            <td>
                <div class="tipo">Contrato {{ $venta->folio }}</div>
                <div class="meta">{{ $venta->created_at?->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="pie">
    <table>
        <tr>
            <td>{{ $emp['nombre'] }} @if ($emp['web']) · {{ $emp['web'] }} @endif</td>
            <td class="r" style="text-align:right;">Contrato {{ $venta->folio }} · Página <span class="num"></span></td>
        </tr>
    </table>
</div>

<h1 class="t">Contrato de compraventa a plazos</h1>
<p class="sub">Con reserva de dominio · Folio {{ $venta->folio }}</p>

<p>
    En {{ $emp['ubicacion'] ?: 'México' }}, a {{ $venta->created_at?->translatedFormat('d \d\e F \d\e Y') }},
    comparecen por una parte <b>{{ $vendedor }}</b>, en lo sucesivo <b>EL VENDEDOR</b>,
    y por la otra <b>{{ $comprador }}</b>, en lo sucesivo <b>EL COMPRADOR</b>,
    quienes se reconocen mutuamente la capacidad legal para obligarse y celebran el
    presente contrato al tenor de las siguientes declaraciones y cláusulas.
</p>

<p class="rot">Las partes</p>
<table>
    <tr>
        <td style="width:50%; padding-right:20px;">
            <table class="partes">
                <tr><td class="k">Vendedor</td><td class="v">{{ $vendedor }}</td></tr>
                @if ($emp['rfc'])<tr><td class="k">RFC</td><td class="v">{{ $emp['rfc'] }}</td></tr>@endif
                @if ($con['telefono'])<tr><td class="k">Teléfono</td><td class="v">{{ $con['telefono'] }}</td></tr>@endif
                @if ($con['correo'])<tr><td class="k">Correo</td><td class="v">{{ $con['correo'] }}</td></tr>@endif
            </table>
        </td>
        <td style="width:50%;">
            <table class="partes">
                <tr><td class="k">Comprador</td><td class="v">{{ $comprador }}</td></tr>
                @if ($c?->rfc)<tr><td class="k">RFC</td><td class="v">{{ $c->rfc }}</td></tr>@endif
                @if ($c?->telefono)<tr><td class="k">Teléfono</td><td class="v">{{ $c->telefono }}</td></tr>@endif
                @if ($c?->direccion)<tr><td class="k">Domicilio</td><td class="v">{{ $c->direccion }}</td></tr>@endif
            </table>
        </td>
    </tr>
</table>

<p class="rot">Objeto del contrato</p>
<table class="equipo">
    @foreach ($venta->items as $it)
        <tr>
            <td>
                <div class="nom">{{ $it->nombre }}</div>
                <div class="det">{{ collect([$it->marca, $it->modelo])->filter()->implode(' · ') ?: 'Sin especificar' }}</div>
            </td>
            <td class="c" style="width:60px;">{{ $it->cantidad }} pza(s)</td>
            <td class="r" style="width:110px;">
                @if ($it->es_regalo) Sin costo @else ${{ $enLetra($it->importe()) }} @endif
            </td>
        </tr>
    @endforeach
</table>

<p class="rot">Cláusulas</p>
<ol>
    <li>
        <b>Primera. Objeto.</b> EL VENDEDOR transmite a EL COMPRADOR la propiedad del equipo
        descrito en este contrato, en las condiciones y por el precio que aquí se establecen.
    </li>

    <li>
        <b>Segunda. Precio.</b> El precio total pactado es de
        <b>${{ $enLetra($venta->montoExigible()) }} MXN</b>
        @if ($venta->valor_a_cuenta > 0)
            resultante de un valor total de ${{ $enLetra($venta->total) }} menos
            ${{ $enLetra($venta->valor_a_cuenta) }} correspondientes al equipo entregado a cuenta
        @endif
        @if ($venta->aplica_iva) e incluye el Impuesto al Valor Agregado @endif.
    </li>

    <li>
        <b>Tercera. Forma de pago.</b> EL COMPRADOR se obliga a cubrir el precio en
        {{ $venta->pagos->count() }} pago(s), conforme al calendario que forma parte de este
        contrato. Los pagos se realizarán mediante transferencia o depósito a la cuenta
        que EL VENDEDOR señale, y EL COMPRADOR remitirá el comprobante correspondiente.
    </li>

    <li>
        <b>Cuarta. Reserva de dominio.</b> EL VENDEDOR se reserva la propiedad del equipo
        hasta que EL COMPRADOR haya cubierto la totalidad del precio. En tanto ello no
        ocurra, EL COMPRADOR se obliga a conservarlo en buen estado, a no enajenarlo,
        gravarlo ni trasladarlo fuera del domicilio señalado sin autorización escrita.
    </li>

    <li>
        <b>Quinta. Mora.</b> El retraso en cualquiera de los pagos causará un interés
        moratorio del 5% mensual sobre el monto vencido, sin necesidad de requerimiento previo.
    </li>

    <li>
        <b>Sexta. Rescisión.</b> El incumplimiento en tres o más pagos consecutivos faculta a
        EL VENDEDOR para rescindir este contrato y exigir la devolución del equipo, sin
        perjuicio de las cantidades ya cubiertas, que se aplicarán como pena convencional
        por el uso del bien.
    </li>

    <li>
        <b>Séptima. Garantía.</b> El equipo cuenta con una garantía de
        <b>{{ $venta->garantia_meses }} meses</b> a partir de la fecha de entrega, en los
        términos de la carta garantía que se entrega por separado y forma parte integrante
        de este contrato.
    </li>

    <li>
        <b>Octava. Entrega.</b> EL VENDEDOR entregará el equipo en el domicilio señalado por
        EL COMPRADOR, quien manifestará su conformidad con el estado y funcionamiento del
        mismo al momento de la recepción.
    </li>

    <li>
        <b>Novena. Jurisdicción.</b> Para la interpretación y cumplimiento de este contrato,
        las partes se someten a los tribunales competentes de {{ $emp['ubicacion'] ?: 'la localidad del vendedor' }},
        renunciando a cualquier otro fuero que pudiera corresponderles.
    </li>
</ol>

@if ($venta->pagos->count())
    <p class="rot">Calendario de pagos</p>
    <table class="pagos">
        @foreach ($venta->pagos as $p)
            <tr>
                <td>
                    {{ $p->nombre }}
                    <div class="f">{{ optional($p->fecha)->translatedFormat('d \d\e F, Y') ?: 'Por definir' }}</div>
                </td>
                <td class="m">${{ $enLetra($p->monto) }}</td>
            </tr>
        @endforeach
    </table>
@endif

<p style="margin-top:20px;">
    Leído que fue el presente contrato y enteradas las partes de su contenido y alcance
    legal, lo firman de conformidad.
</p>

<table class="firmas">
    <tr>
        <td>
            <div class="linea">
                <span class="quien">{{ $vendedor }}</span><br>
                El vendedor
            </div>
        </td>
        <td>
            <div class="linea">
                <span class="quien">{{ $comprador }}</span><br>
                El comprador
            </div>
        </td>
    </tr>
</table>

</body>
</html>
