{{-- Marco común de los PDF de garantías: mismo criterio que recibos y ventas. --}}
@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');
    $logoUri = \App\Support\LogoPdf::dataUri();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96px 46px 60px; }
        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 9.5px; line-height: 1.55; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .r { text-align: right; }

        .head { position: fixed; top: -84px; left: 0; right: 0; height: 66px; }
        .head td { vertical-align: middle; }
        .head img { height: 42px; }
        .head .tipo { color: #1a1d23; font-size: 12px; font-weight: bold; }
        .head .meta { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; }

        .pie { position: fixed; bottom: -48px; left: 0; right: 0; height: 40px; padding-top: 9px; border-top: 1px solid #ededf0; }
        .pie td { font-size: 8px; color: #a8aeb8; line-height: 1.5; }
        .pie .quien { color: #6b7280; font-weight: bold; }

        h1.t { margin: 0 0 4px; color: #1a1d23; font-size: 19px; font-weight: bold; letter-spacing: -.4px; }
        .sub { margin: 0 0 18px; color: #a8aeb8; font-size: 9.5px; }
        .rot { margin: 18px 0 7px; color: #a8aeb8; font-size: 7.5px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }

        .par td { padding: 0 0 3px; font-size: 9.5px; }
        .par .k { width: 120px; color: #a8aeb8; }
        .par .v { color: #1a1d23; }

        .lista th { padding: 0 0 6px; border-bottom: 1px solid #1a1d23; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
                    letter-spacing: 1.4px; text-transform: uppercase; text-align: left; }
        .lista td { padding: 6px 0; border-bottom: 1px solid #ededf0; font-size: 9px; color: #4b5563; }
        .lista .nom { color: #1a1d23; font-weight: bold; }

        .texto { white-space: pre-line; color: #4b5563; }
        .caja { margin-top: 6px; padding: 10px 12px; background: #f5f7fa; }

        .qr { margin-top: 22px; }
        .qr td { vertical-align: middle; }
        .qr .img { width: 92px; }
        .qr .img img { width: 82px; height: 82px; }
        .qr .txt { padding-left: 13px; color: #a8aeb8; font-size: 9px; }
        .qr .txt b { display: block; color: #3d4450; font-size: 9.5px; }

        .firma { margin-top: 46px; }
        .firma td { width: 50%; padding-top: 6px; text-align: center; font-size: 8.5px; color: #6b7280; }
        .firma td:first-child { padding-right: 30px; }
        .firma .linea { border-top: 1px solid #c9ced6; padding-top: 6px; }
    </style>
</head>
<body>

<div class="head">
    <table>
        <tr>
            <td>@if ($logoUri)<img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">@else<b>{{ $emp['nombre'] }}</b>@endif</td>
            <td class="r">
                <div class="tipo">@yield('tipo')</div>
                <div class="meta">@yield('meta')</div>
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
                {{ $emp['nombre'] }}
            </td>
        </tr>
    </table>
</div>

@yield('contenido')

</body>
</html>
