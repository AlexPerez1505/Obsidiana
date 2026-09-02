{{-- Carta garantía del equipo vendido. Aplica a toda venta, no solo a plazos. --}}
@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');
    $ban = config('medibuy.banco');

    $c = $venta->customer;
    $cliente = trim(($c->nombre ?? '') . ' ' . ($c->apellido ?? '')) ?: '—';
    $firmante = $con['nombre'] ?: ($ban['beneficiario'] ?: $emp['nombre']);

    $meses = $venta->garantia_meses ?: 6;
    $hasta = $venta->garantiaHasta();

    $logoUri = \App\Support\LogoPdf::dataUri();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96px 52px 62px; }

        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 10px; line-height: 1.65; }

        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .r { text-align: right; } .c { text-align: center; }

        .head { position: fixed; top: -84px; left: 0; right: 0; height: 66px; }
        .head td { vertical-align: middle; }
        .head img { height: 42px; }
        .head .tipo { color: #1a1d23; font-size: 12px; font-weight: bold; text-align: right; }
        .head .meta { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; text-align: right; }

        .pie { position: fixed; bottom: -46px; left: 0; right: 0; height: 40px;
               padding-top: 9px; border-top: 1px solid #ededf0; }
        .pie td { font-size: 8px; color: #a8aeb8; line-height: 1.5; }
        .pie .num:after { content: counter(page); }

        h1.t { margin: 0 0 4px; color: #1a1d23; font-size: 19px; font-weight: bold; letter-spacing: -.4px; }
        .sub { margin: 0 0 24px; color: #a8aeb8; font-size: 9.5px; }

        .rot { margin: 22px 0 8px; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
               letter-spacing: 1.6px; text-transform: uppercase; }

        p { margin: 0 0 9px; }
        b { color: #1a1d23; }

        /* El plazo es el dato que la gente busca al abrir la carta. */
        .plazo { margin: 22px 0; padding: 16px 0; border-top: 1px solid #1a1d23; border-bottom: 1px solid #ededf0; }
        .plazo .e { color: #a8aeb8; font-size: 8px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
        .plazo .n { margin-top: 2px; color: #1a1d23; font-size: 26px; font-weight: bold; letter-spacing: -.8px; }
        .plazo .d { margin-top: 2px; color: #6b7280; font-size: 9.5px; }

        .par td { padding: 0 0 3px; font-size: 9.5px; }
        .par .k { width: 92px; color: #a8aeb8; }
        .par .v { color: #4b5563; }

        .equipo td { padding: 8px 0; border-bottom: 1px solid #f0f0f2; font-size: 9.5px; }
        .equipo tr:last-child td { border-bottom: 0; }
        .equipo .nom { color: #1a1d23; font-weight: bold; }
        .equipo .det { color: #a8aeb8; font-size: 8.5px; }

        ul { margin: 0; padding-left: 15px; }
        ul li { margin-bottom: 5px; font-size: 9.5px; }

        .firma { margin-top: 50px; page-break-inside: avoid; }
        .firma td { width: 50%; padding-top: 40px; text-align: center; font-size: 9px; color: #a8aeb8; }
        .firma td:first-child { padding-right: 26px; border: 0; }
        .firma .linea { border-top: 1px solid #a8aeb8; padding-top: 7px; }
        .firma .quien { color: #1a1d23; font-size: 9.5px; font-weight: bold; }
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
                <div class="tipo">Garantía {{ $venta->folio }}</div>
                <div class="meta">{{ $venta->created_at?->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="pie">
    <table>
        <tr>
            <td>{{ $emp['nombre'] }} @if ($emp['web']) · {{ $emp['web'] }} @endif</td>
            <td class="r">Carta garantía · Página <span class="num"></span></td>
        </tr>
    </table>
</div>

<h1 class="t">Carta garantía</h1>
<p class="sub">Venta {{ $venta->folio }} · {{ $venta->created_at?->translatedFormat('d \d\e F \d\e Y') }}</p>

<p>
    <b>{{ $emp['nombre'] }}</b> otorga a <b>{{ $cliente }}</b> la presente garantía sobre
    el equipo que se describe a continuación, adquirido mediante la venta {{ $venta->folio }}.
</p>

<div class="plazo">
    <div class="e">Vigencia de la garantía</div>
    <div class="n">{{ $meses }} meses</div>
    <div class="d">
        A partir de la fecha de entrega
        @if ($hasta) · vence el {{ $hasta->translatedFormat('d \d\e F \d\e Y') }} @endif
    </div>
</div>

<p class="rot">Equipo amparado</p>
<table class="equipo">
    @foreach ($venta->items as $it)
        <tr>
            <td>
                <div class="nom">{{ $it->nombre }}</div>
                <div class="det">{{ collect([$it->marca, $it->modelo])->filter()->implode(' · ') ?: 'Sin especificar' }}</div>
            </td>
            <td class="r" style="width:80px;">{{ $it->cantidad }} pza(s)</td>
        </tr>
    @endforeach
</table>

<p class="rot">Titular</p>
<table class="par">
    <tr><td class="k">Cliente</td><td class="v">{{ $cliente }}</td></tr>
    @if ($c?->rfc)<tr><td class="k">RFC</td><td class="v">{{ $c->rfc }}</td></tr>@endif
    @if ($c?->telefono)<tr><td class="k">Teléfono</td><td class="v">{{ $c->telefono }}</td></tr>@endif
    @if ($c?->direccion)<tr><td class="k">Domicilio</td><td class="v">{{ $c->direccion }}</td></tr>@endif
</table>

<p class="rot">Qué cubre</p>
<ul>
    <li>Defectos de fabricación y fallas de funcionamiento no atribuibles al uso.</li>
    <li>Mano de obra y refacciones necesarias para corregir dichas fallas.</li>
    <li>Diagnóstico técnico sin costo durante la vigencia.</li>
</ul>

<p class="rot">Qué no cubre</p>
<ul>
    <li>Daños por mal uso, descuido, caídas, derrames o conexión a instalaciones eléctricas inadecuadas.</li>
    <li>Equipo intervenido, reparado o modificado por personal ajeno a {{ $emp['nombre'] }}.</li>
    <li>Desgaste natural de consumibles y accesorios de uso rutinario.</li>
    <li>Daños por siniestro, transporte realizado por el cliente o causas de fuerza mayor.</li>
</ul>

<p class="rot">Cómo hacerla válida</p>
<p>
    Comunícate
    @if ($con['telefono']) al <b>{{ $con['telefono'] }}</b> @endif
    @if ($con['correo']) o al correo <b>{{ $con['correo'] }}</b> @endif
    presentando esta carta y el comprobante de la venta {{ $venta->folio }}.
    El equipo será revisado por personal técnico autorizado.
</p>

<table class="firma">
    <tr>
        <td>&nbsp;</td>
        <td>
            <div class="linea">
                <span class="quien">{{ $firmante }}</span><br>
                {{ $con['cargo'] ?: $emp['nombre'] }}
            </div>
        </td>
    </tr>
</table>

</body>
</html>
