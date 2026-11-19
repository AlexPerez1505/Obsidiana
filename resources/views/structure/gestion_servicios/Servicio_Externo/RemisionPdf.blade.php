<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 50px 46px 50px; }

        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #1a1d23; font-size: 10px; line-height: 1.55; }

        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }

        .r { text-align: right; }
        .c { text-align: center; }

        .head td { vertical-align: middle; }
        .head img { height: 52px; }

        .doc-titulo { font-size: 13px; font-weight: bold; color: #1a1d23; }
        .doc-folio { color: #e11d48; font-size: 10px; font-weight: bold; margin-top: 2px; }

        .cli td { padding: 3px 0; font-size: 9.5px; }
        .cli .k { font-weight: bold; width: 86px; }
        .meta td { padding: 3px 0; font-size: 9.5px; }
        .meta .k { font-weight: bold; text-align: right; padding-right: 8px; }

        /* Tabla de partidas */
        .partidas { margin-top: 26px; }
        .partidas th { background: #4a90d9; color: #fff; font-size: 9px; font-weight: bold;
                       padding: 8px 10px; text-align: center; }
        .partidas td { padding: 9px 10px; font-size: 9.5px; text-align: center; vertical-align: top; }
        .partidas .desc { text-align: left; }

        .totales { width: 46%; margin-left: 54%; margin-top: 14px; }
        .totales td { padding: 4px 0; font-size: 9.5px; }
        .totales .k { font-weight: bold; text-align: right; padding-right: 12px; }
        .totales .v { text-align: right; }
        .totales .pagar td { padding-top: 8px; }
        .pagar-box { display: inline-block; background: #4a90d9; color: #fff; font-weight: bold;
                     font-size: 10px; padding: 5px 14px; }

        .nota { margin-top: 22px; font-size: 9px; }
        .nota b { font-weight: bold; }

        .salto { page-break-before: always; }

        /* Página 2: firma + contacto */
        .firma-bloque { border: 1px solid #e8eaee; border-radius: 8px; padding: 14px 18px; }
        .firma-bloque td { vertical-align: middle; }
        .firma-espacio { width: 130px; height: 44px; border-bottom: 1px solid #c9ccd3; }
        .firma-nom { font-size: 11px; font-weight: bold; }
        .firma-cargo { font-size: 8.5px; color: #6b7280; }
        .firma-sep { width: 1px; background: #e8eaee; }
        .firma-contacto td { padding: 2px 0 2px 18px; font-size: 8.5px; color: #3d4450; }
        .firma-contacto .k { font-weight: bold; width: 58px; }

        .tyc-titulo { margin: 34px 0 18px; text-align: center; color: #2f6fc4;
                      font-size: 14px; font-weight: bold; }

        .sec-titulo { color: #2f6fc4; font-size: 10.5px; font-weight: bold;
                      border-bottom: 2px solid #4a90d9; padding-bottom: 6px; }

        .tyc-box { margin-top: 14px; background: #eef4fc; border-left: 3px solid #4a90d9;
                   padding: 14px 18px; }
        .tyc-box ul { margin: 0; padding-left: 14px; }
        .tyc-box li { margin-bottom: 7px; font-size: 8.8px; color: #3d4450; }

        .banco { margin-top: 14px; }
        .banco td { padding: 4px 0; font-size: 9.5px; }
        .banco .k { font-weight: bold; width: 120px; }

        .comprobante { margin-top: 18px; font-size: 9px; }
    </style>
</head>
<body>

@php
    $emp = config('medibuy.empresa', ['nombre' => config('app.name')]);
    $con = config('medibuy.contacto', []);
    $banco = config('medibuy.banco', []);
    $comprobantes = config('medibuy.comprobantes', []);
    $logoUri = \App\Support\LogoPdf::dataUri();
    $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
    $folio = $service->service_number ?? ('OS-' . $service->id);

    if (! isset($partidas)) {
        $partidas = $service->spareParts->map(fn ($p) => [
            'item' => $p->nombre,
            'descripcion' => $p->refaccion?->subtype ?? '',
            'unidad' => 'PIEZA',
            'cantidad' => $p->cantidad,
            'precio' => $p->precio_unitario,
            'importe' => $p->subtotal,
        ]);
        if (($service->mano_obra ?? 0) > 0) {
            $partidas->push(['item' => 'Mano de obra', 'descripcion' => 'Servicio externo ' . ($service->service_type ?? ''), 'unidad' => 'SERVICIO', 'cantidad' => 1, 'precio' => $service->mano_obra, 'importe' => $service->mano_obra]);
        }
        $envio = 0;
        $anticipo = 0;
        $conIva = false;
        $descripcionGeneral = null;
    }

    $subtotal = $partidas->sum('importe') + ($envio ?? 0);
    $ivaMonto = ($conIva ?? false) ? $subtotal * 0.16 : 0;
    $total = $subtotal + $ivaMonto;
    $pagar = $total - ($anticipo ?? 0);

    $nota = $descripcionGeneral ?? $service->serviceEquipment?->observations;
@endphp

{{-- PÁGINA 1 --}}
<table class="head">
    <tr>
        <td style="width:40%;">
            @if ($logoUri)
                <img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">
            @endif
        </td>
        <td class="r">
            <div class="doc-titulo">REMISIÓN MANTENIMIENTO</div>
            <div class="doc-folio">No.{{ $folio }}</div>
        </td>
    </tr>
</table>

<table style="margin-top:10px;">
    <tr>
        <td style="width:55%;">
            <table class="cli">
                <tr><td class="k">CLIENTE:</td><td>{{ strtoupper($customerName) }}</td></tr>
                <tr><td class="k">TELÉFONO:</td><td>{{ $service->customer?->telefono ?? '—' }}</td></tr>
                <tr><td class="k">DIRECCIÓN:</td><td>{{ strtoupper($service->customer?->direccion ?? '—') }}</td></tr>
            </table>
        </td>
        <td style="width:45%;">
            <table class="meta">
                <tr><td class="k">FECHA:</td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
                <tr><td class="k">VIGENCIA:</td><td>10 DÍAS</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="partidas">
    <tr>
        <th style="width:14%;">Unidad</th>
        <th style="width:12%;">Cantidad</th>
        <th>Descripción</th>
        <th style="width:14%;">P. Unitario</th>
        <th style="width:14%;">Importe</th>
    </tr>
    @foreach ($partidas as $part)
        <tr>
            <td>{{ strtoupper($part['unidad']) }}</td>
            <td>{{ $part['cantidad'] + 0 }}</td>
            <td class="desc">{{ $part['descripcion'] ?: $part['item'] }}</td>
            <td>${{ number_format($part['precio'], 2) }}</td>
            <td>${{ number_format($part['importe'], 2) }}</td>
        </tr>
    @endforeach
    @if (($envio ?? 0) > 0)
        <tr>
            <td>SERVICIO</td>
            <td>1</td>
            <td class="desc">Envío</td>
            <td>${{ number_format($envio, 2) }}</td>
            <td>${{ number_format($envio, 2) }}</td>
        </tr>
    @endif
</table>

<table class="totales">
    <tr><td class="k">Subtotal</td><td class="v">${{ number_format($subtotal, 2) }}</td></tr>
    @if ($conIva ?? false)
        <tr><td class="k">IVA (16%)</td><td class="v">${{ number_format($ivaMonto, 2) }}</td></tr>
    @endif
    <tr><td class="k">Total</td><td class="v">${{ number_format($total, 2) }}</td></tr>
    @if (($anticipo ?? 0) > 0)
        <tr><td class="k">Anticipo</td><td class="v">-${{ number_format($anticipo, 2) }}</td></tr>
    @endif
    <tr class="pagar"><td></td><td class="v"><span class="pagar-box">Total a pagar: ${{ number_format($pagar, 2) }}</span></td></tr>
</table>

@if (!empty($nota))
    <p class="nota"><b>Nota:</b> {{ $nota }}</p>
@endif

{{-- PÁGINA 2 --}}
<div class="salto"></div>

<table class="firma-bloque">
    <tr>
        <td style="width:150px;"><div class="firma-espacio"></div></td>
        <td style="width:160px; padding-left:14px;">
            <div class="firma-nom">{{ $con['nombre'] ?? '—' }}</div>
            <div class="firma-cargo">{{ $con['cargo'] ?? '' }}</div>
        </td>
        <td class="firma-sep"></td>
        <td>
            <table class="firma-contacto">
                <tr><td class="k">Tel:</td><td>{{ $con['telefono'] ?? '—' }}</td></tr>
                <tr><td class="k">Email:</td><td>{{ $con['correo'] ?? '—' }}</td></tr>
                <tr><td class="k">Web:</td><td>{{ $emp['web'] ?? '—' }}</td></tr>
                <tr><td class="k">Ubicación:</td><td>{{ $emp['ubicacion'] ?? '—' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<h2 class="tyc-titulo">Términos y condiciones · Remisión {{ $folio }}</h2>

<div class="sec-titulo">Términos y Condiciones del Servicio</div>
<div class="tyc-box">
    <ul>
        <li>Garantía del servicio aplica únicamente a la mano de obra realizada y/o refacciones instaladas; no cubre daños por golpes, humedad, mal uso, variaciones eléctricas, manipulaciones de terceros o desgaste normal.</li>
        <li>Si el equipo presenta fallas ajenas al servicio solicitado, se informará al cliente y se generará una cotización adicional.</li>
        <li>No nos hacemos responsables por accesorios no entregados o no descritos.</li>
        <li>Equipos abandonados por más de 60 días naturales podrán considerarse en abandono para recuperación de costos, previa notificación al contacto registrado.</li>
        <li>Para garantías se requiere esta remisión y evidencia del servicio.</li>
        <li>La garantía no aplica si el equipo fue abierto o intervenido por terceros.</li>
        <li>No aplica garantía debido a daños ocasionados por sobrecarga eléctrica en los componentes electrónicos.</li>
        <li>{{ $emp['nombre'] }} no se responsabiliza por daños estéticos preexistentes si no fueron reportados al ingreso.</li>
        <li>En caso de envío o paquetería, el cliente acepta el costo de envío indicado en la remisión y el riesgo inherente al transporte.</li>
    </ul>
</div>

<div class="sec-titulo" style="margin-top:26px;">Datos Bancarios para Transferencia</div>
<table class="banco">
    <tr><td class="k">Banco:</td><td>{{ $banco['nombre'] ?? '—' }}</td></tr>
    <tr><td class="k">Beneficiario:</td><td>{{ $banco['beneficiario'] ?? '—' }}</td></tr>
    <tr><td class="k">Cuenta:</td><td>{{ $banco['cuenta'] ?? '—' }}</td></tr>
    <tr><td class="k">CLABE:</td><td>{{ $banco['clabe'] ?? '—' }}</td></tr>
    <tr><td class="k">No. Tarjeta:</td><td>{{ $banco['tarjeta'] ?? '—' }}</td></tr>
    <tr><td class="k">Concepto:</td><td>Remisión {{ $folio }} - {{ strtoupper($customerName) }}</td></tr>
</table>

<p class="comprobante">Envíe su comprobante a <b>{{ $comprobantes['correo'] ?? '—' }}</b> o WhatsApp <b>{{ $comprobantes['whatsapp'] ?? '—' }}</b>.</p>

</body>
</html>
