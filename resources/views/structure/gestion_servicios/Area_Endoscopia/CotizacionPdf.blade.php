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
        .c { text-align: center; }

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

        .rot { margin: 0 0 9px; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
               letter-spacing: 1.6px; text-transform: uppercase; }

        h1.t { margin: 0 0 16px; color: #1a1d23; font-size: 20px; font-weight: bold; letter-spacing: -.4px; }

        .par td { padding: 0 0 3px; font-size: 9.5px; }
        .par .k { width: 76px; color: #a8aeb8; }
        .par .v { color: #4b5563; }
        .destinatario { margin: 0 0 4px; color: #1a1d23; font-size: 13px; font-weight: bold; }

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

        .totales { width: 42%; margin-left: 58%; margin-top: 12px; }
        .totales td { padding: 4px 0; font-size: 9.5px; color: #6b7280; }
        .totales .v { text-align: right; color: #3d4450; }
        .totales .granTotal td { padding-top: 12px; border-top: 1px solid #1a1d23;
                                 color: #1a1d23; font-weight: bold; }
        .totales .granTotal .e { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; }
        .totales .granTotal .v { font-size: 17px; letter-spacing: -.4px; }

        .parrafo { margin: 14px 0 0; font-size: 9.5px; color: #6b7280; }
    </style>
</head>
<body>

@php
    $emp = config('medibuy.empresa', ['nombre' => config('app.name')]);
    $con = config('medibuy.contacto', []);
    $logoUri = \App\Support\LogoPdf::dataUri();
    $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
    $equipment = $service->serviceEquipment;
    $technician = $service->service_type === 'externo'
        ? ($service->externalTechnician?->name ?? '—')
        : ($service->internalTechnician?->name ?? '—');
@endphp

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
                <div class="tipo">Cotización</div>
                <div class="meta">{{ $service->service_number ?? ('OS-' . $service->id) }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="pie">
    <table>
        <tr>
            <td>
                @if (!empty($con['nombre']))<span class="quien">{{ $con['nombre'] }}</span>@endif
                @if (!empty($con['telefono'])) · {{ $con['telefono'] }}@endif
            </td>
            <td class="r">
                Precios en MXN
            </td>
        </tr>
    </table>
</div>

<h1 class="t">Cotización {{ $service->service_number ?? ('OS-' . $service->id) }}</h1>

<table>
    <tr>
        <td style="width:52%; padding-right:24px;">
            <p class="rot">Cliente</p>
            <div class="destinatario">{{ $customerName }}</div>
            <table class="par">
                @if ($service->customer?->rfc)
                    <tr><td class="k">RFC</td><td class="v">{{ $service->customer->rfc }}</td></tr>
                @endif
                @if ($service->customer?->telefono)
                    <tr><td class="k">Teléfono</td><td class="v">{{ $service->customer->telefono }}</td></tr>
                @endif
            </table>
        </td>
        <td style="width:48%;">
            <p class="rot">Detalles del servicio</p>
            <table class="par">
                <tr><td class="k">Equipo</td><td class="v">{{ $equipment?->type_text ?? '—' }}</td></tr>
                <tr><td class="k">Marca / Modelo</td><td class="v">{{ $equipment?->brand_text ?? '—' }} / {{ $equipment?->model_text ?? '—' }}</td></tr>
                <tr><td class="k">Serie</td><td class="v">{{ $equipment?->serial_number ?? '—' }}</td></tr>
                <tr><td class="k">Técnico</td><td class="v">{{ $technician }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="conceptos">
    <tr class="cab">
        <td colspan="2">Refacción</td>
        <td class="c" style="width:11%;">Cant.</td>
        <td class="r" style="width:22%;">Importe</td>
    </tr>
    @foreach ($service->spareParts as $part)
        <tr class="fila">
            <td class="foto">
                @php ($foto = $part->refaccion?->photo_path ? \App\Support\ImagenPdf::dataUri(asset('storage/' . $part->refaccion->photo_path)) : null)
                @if ($foto)
                    <img src="{{ $foto }}" alt="">
                @endif
            </td>
            <td>
                <div class="nom">{{ $part->nombre }}</div>
                <div class="det">{{ $part->refaccion?->subtype ?? '—' }}</div>
            </td>
            <td class="c">{{ $part->cantidad }}</td>
            <td class="r">
                <div class="imp">${{ number_format($part->subtotal, 2) }}</div>
                @if ($part->cantidad > 1)
                    <div class="uni">${{ number_format($part->precio_unitario, 2) }} c/u</div>
                @endif
            </td>
        </tr>
    @endforeach
</table>

<table class="totales">
    <tr><td>Refacciones</td><td class="v">${{ number_format($totalRefacciones, 2) }}</td></tr>
    <tr><td>Mano de obra</td><td class="v">${{ number_format($service->mano_obra ?? 0, 2) }}</td></tr>
    <tr class="granTotal"><td class="e">Total</td><td class="v">${{ number_format($total, 2) }}</td></tr>
</table>

<p class="parrafo">Cotización válida por 15 días. Precios sujetos a disponibilidad de refacciones.</p>

</body>
</html>
