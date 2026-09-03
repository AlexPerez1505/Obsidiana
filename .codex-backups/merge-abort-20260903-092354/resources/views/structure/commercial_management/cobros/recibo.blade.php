{{-- Recibo de un cobro. Mismo criterio que el resto: sin recuadros. --}}
@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');

    $venta = $cobro->venta;
    $cliente = trim(($venta->customer->nombre ?? '') . ' ' . ($venta->customer->apellido ?? ''));

    $logoUri = \App\Support\LogoPdf::dataUri();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96px 46px 56px; }

        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 10px; line-height: 1.55; }

        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .r { text-align: right; }

        .head { position: fixed; top: -84px; left: 0; right: 0; height: 66px; }
        .head td { vertical-align: middle; }
        .head img { height: 42px; }
        .head .tipo { color: #1a1d23; font-size: 12px; font-weight: bold; }
        .head .meta { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; }

        .pie { position: fixed; bottom: -46px; left: 0; right: 0; height: 40px;
               padding-top: 9px; border-top: 1px solid #ededf0; }
        .pie td { font-size: 8px; color: #a8aeb8; line-height: 1.5; }
        .pie .quien { color: #6b7280; font-weight: bold; }

        h1.t { margin: 0 0 4px; color: #1a1d23; font-size: 20px; font-weight: bold; letter-spacing: -.4px; }
        .sub { margin: 0 0 22px; color: #a8aeb8; font-size: 9.5px; }

        .rot { margin: 0 0 9px; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
               letter-spacing: 1.6px; text-transform: uppercase; }

        .par td { padding: 0 0 3px; font-size: 9.5px; }
        .par .k { width: 92px; color: #a8aeb8; }
        .par .v { color: #4b5563; }
        .destinatario { margin: 0 0 4px; color: #1a1d23; font-size: 13px; font-weight: bold; }

        /* El monto es lo que la gente busca al abrir un recibo. */
        .monto { margin-top: 30px; padding: 16px 0; border-top: 1px solid #1a1d23; border-bottom: 1px solid #ededf0; }
        .monto .e { color: #a8aeb8; font-size: 8px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
        .monto .n { margin-top: 2px; color: #1a1d23; font-size: 26px; font-weight: bold; letter-spacing: -.8px; }
        .monto .letra { margin-top: 2px; color: #6b7280; font-size: 9px; }

        .estado { margin-top: 26px; }
        .estado td { padding: 5px 0; font-size: 9.5px; color: #6b7280; }
        .estado .v { text-align: right; color: #1a1d23; font-weight: bold; }
        .estado .saldo td { padding-top: 9px; border-top: 1px solid #ededf0; }

        .parrafo { margin: 26px 0 0; font-size: 9px; color: #a8aeb8; line-height: 1.6; }
        .firma { margin-top: 46px; }
        .firma td { width: 50%; padding-top: 26px; border-top: 1px solid #ededf0;
                    color: #a8aeb8; font-size: 8.5px; text-align: center; }
        .firma td:first-child { padding-right: 30px; border-top: 0; }
        .firma .linea { border-top: 1px solid #c9ced6; padding-top: 6px; }
    </style>
</head>
<body>

<div class="head">
    <table>
        <tr>
            <td>
                @if ($logoUri)
                    <img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">
                @else
                    <b>{{ $emp['nombre'] }}</b>
                @endif
            </td>
            <td class="r">
                <div class="tipo">Recibo {{ $cobro->folio }}</div>
                <div class="meta">{{ $cobro->fecha->format('d/m/Y') }}</div>
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
                Comprobante de pago · MXN
            </td>
        </tr>
    </table>
</div>

<h1 class="t">Recibo de pago</h1>
<p class="sub">{{ $cobro->folio }} · Venta {{ $venta->folio }}</p>

<table>
    <tr>
        <td style="width:52%; padding-right:24px;">
            <p class="rot">Recibimos de</p>
            <div class="destinatario">{{ $cliente ?: '—' }}</div>
            <table class="par">
                @if ($venta->customer?->rfc)
                    <tr><td class="k">RFC</td><td class="v">{{ $venta->customer->rfc }}</td></tr>
                @endif
                @if ($venta->customer?->telefono)
                    <tr><td class="k">Teléfono</td><td class="v">{{ $venta->customer->telefono }}</td></tr>
                @endif
            </table>
        </td>
        <td style="width:48%;">
            <p class="rot">Detalles del pago</p>
            <table class="par">
                <tr><td class="k">Fecha</td><td class="v">{{ $cobro->fecha->translatedFormat('d \d\e F, Y') }}</td></tr>
                <tr><td class="k">Método</td><td class="v">{{ $cobro->metodoLabel() }}</td></tr>
                @if ($cobro->referencia)
                    <tr><td class="k">Referencia</td><td class="v">{{ $cobro->referencia }}</td></tr>
                @endif
                @if ($cobro->parcialidad)
                    <tr><td class="k">Aplicado a</td><td class="v">{{ $cobro->parcialidad->nombre }}</td></tr>
                @endif
                <tr><td class="k">Registró</td><td class="v">{{ $cobro->registradoPor?->name ?: '—' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="monto">
    <div class="e">Importe recibido</div>
    <div class="n">${{ number_format((float) $cobro->monto, 2) }}</div>
    <div class="letra">Pesos mexicanos</div>
</div>

<table class="estado" style="width:44%; margin-left:56%;">
    <tr><td>Total de la venta</td><td class="v">${{ number_format($venta->montoExigible(), 2) }}</td></tr>
    <tr><td>Pagado a la fecha</td><td class="v">${{ number_format($venta->totalCobrado(), 2) }}</td></tr>
    <tr class="saldo">
        <td>{{ $venta->saldo() > 0 ? 'Saldo pendiente' : 'Saldo' }}</td>
        <td class="v">${{ number_format($venta->saldo(), 2) }}</td>
    </tr>
</table>

@if ($cobro->nota)
    <p class="parrafo"><b>Nota.</b> {{ $cobro->nota }}</p>
@endif

<p class="parrafo">
    Este recibo ampara únicamente el importe señalado.
    @if ($venta->saldo() > 0)
        El saldo pendiente se rige por el calendario de pagos acordado.
    @else
        Con este pago la venta {{ $venta->folio }} queda liquidada.
    @endif
</p>

<table class="firma">
    <tr>
        <td>&nbsp;</td>
        <td>
            <div class="linea">{{ $con['nombre'] ?: $emp['nombre'] }}</div>
        </td>
    </tr>
</table>

</body>
</html>
