{{--
    PDF compartido para cotización / venta / factura-borrador.
    Variables: $doc (con customer, seller, items, pagos, fichas y montos),
               $titulo (ej. "Cotización"), $leyenda (texto del pie).
--}}
@php $leyenda = $leyenda ?? 'Grupo MediBuy · Documento sin compromiso · Precios en MXN.'; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .wrap { padding: 28px 34px; }
        .head { width: 100%; border-bottom: 3px solid #2f7cff; padding-bottom: 12px; margin-bottom: 16px; }
        .head td { vertical-align: middle; }
        .brand { font-size: 22px; font-weight: bold; color: #111827; }
        .brand small { display: block; font-size: 10px; color: #6b7280; font-weight: normal; letter-spacing: 1px; }
        .folio { text-align: right; }
        .folio .tipo { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .folio .num { font-size: 16px; font-weight: bold; color: #2f7cff; }
        .folio .fecha { font-size: 11px; color: #6b7280; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { width: 50%; vertical-align: top; padding-right: 12px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; }
        .box b { color: #2f7cff; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
        .box .l { margin: 4px 0 0; font-size: 12px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th { background: #f1f5ff; color: #1e3a8a; text-align: left; padding: 8px 8px; font-size: 10px; text-transform: uppercase; }
        table.items td { padding: 8px 8px; border-bottom: 1px solid #eef1f6; }
        .r { text-align: right; } .c { text-align: center; }
        .regalo { color: #059669; font-weight: bold; }
        .totes { width: 45%; margin-left: 55%; border-collapse: collapse; }
        .totes td { padding: 5px 8px; font-size: 12px; }
        .totes .strong td { font-size: 14px; font-weight: bold; border-top: 2px solid #2f7cff; }
        .totes .contrato td { color: #059669; font-weight: bold; }
        h3 { color: #1e3a8a; font-size: 13px; margin: 18px 0 8px; }
        table.pay { width: 100%; border-collapse: collapse; }
        table.pay th { background: #f1f5ff; color: #1e3a8a; text-align: left; padding: 6px 8px; font-size: 10px; }
        table.pay td { padding: 6px 8px; border-bottom: 1px solid #eef1f6; }
        .nota { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin-top: 14px; background: #fafbfc; }
        .ficha { page-break-before: always; }
        .ficha h2 { color: #2f7cff; font-size: 15px; border-bottom: 2px solid #2f7cff; padding-bottom: 6px; }
        .foot { margin-top: 26px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 10px; color: #9ca3af; text-align: center; }
        .stamp { display: inline-block; border: 2px solid #f59e0b; color: #b45309; padding: 4px 12px; border-radius: 6px; font-weight: bold; font-size: 11px; letter-spacing: 1px; }
    </style>
</head>
<body>
<div class="wrap">
    <table class="head">
        <tr>
            <td><div class="brand">Grupo MediBuy <small>EQUIPO MÉDICO</small></div></td>
            <td class="folio">
                <div class="tipo">{{ $titulo }}</div>
                <div class="num">{{ $doc->folio }}</div>
                <div class="fecha">Fecha: {{ $doc->created_at?->format('d/m/Y') }}</div>
                <div class="fecha">Modalidad: {{ ucfirst($doc->modalidad) }}@if($doc->modalidad==='financiamiento') · {{ $doc->num_meses }} meses @endif</div>
                @if (!empty($sello))<div style="margin-top:6px;"><span class="stamp">{{ $sello }}</span></div>@endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="box">
                    <b>Cliente</b>
                    <div class="l">{{ $doc->customer?->nombre }} {{ $doc->customer?->apellido }}</div>
                    <div class="l">{{ $doc->customer?->rfc }}</div>
                    <div class="l">{{ $doc->customer?->telefono }} {{ $doc->customer?->correo ? '· '.$doc->customer->correo : '' }}</div>
                </div>
            </td>
            <td>
                <div class="box">
                    <b>Documento</b>
                    <div class="l">Lugar: {{ $doc->lugar_propuesta ?: '—' }}</div>
                    <div class="l">Registró: {{ $doc->seller?->name ?: '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Equipo</th><th>Modelo</th><th>Marca</th><th class="c">Cant.</th><th class="r">P. Unitario</th><th class="r">Sobreprecio</th><th class="r">Importe</th></tr>
        </thead>
        <tbody>
            @foreach ($doc->items as $it)
                <tr>
                    <td>{{ $it->nombre }}</td>
                    <td>{{ $it->modelo ?? '—' }}</td>
                    <td>{{ $it->marca ?? '—' }}</td>
                    <td class="c">{{ $it->cantidad }}</td>
                    <td class="r">@if($it->es_regalo)<span class="regalo">REGALO</span>@else ${{ number_format($it->precio_unitario, 2) }} @endif</td>
                    <td class="r">${{ number_format($it->sobreprecio, 2) }}</td>
                    <td class="r">@if($it->es_regalo)—@else ${{ number_format($it->importe(), 2) }} @endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totes">
        <tr><td>Subtotal</td><td class="r">${{ number_format($doc->subtotal, 2) }}</td></tr>
        @if ($doc->descuento_monto > 0)<tr><td>Descuento</td><td class="r">-${{ number_format($doc->descuento_monto, 2) }}</td></tr>@endif
        @if ($doc->envio > 0)<tr><td>Envío</td><td class="r">${{ number_format($doc->envio, 2) }}</td></tr>@endif
        @if ($doc->aplica_iva)<tr><td>IVA (16%)</td><td class="r">${{ number_format($doc->iva_monto, 2) }}</td></tr>@endif
        <tr class="strong"><td>Total</td><td class="r">${{ number_format($doc->total, 2) }}</td></tr>
        @if ($doc->valor_a_cuenta > 0)
            <tr><td>Valor a cuenta</td><td class="r">-${{ number_format($doc->valor_a_cuenta, 2) }}</td></tr>
            <tr class="strong contrato"><td>Total del contrato</td><td class="r">${{ number_format($doc->total_contrato, 2) }}</td></tr>
        @endif
    </table>

    @if ($doc->modalidad === 'financiamiento' && $doc->pagos->count())
        <h3>Plan de pagos — {{ $doc->num_meses }} meses</h3>
        <table class="pay">
            <thead><tr><th>Pago</th><th>Fecha</th><th class="r">%</th><th class="r">Monto</th></tr></thead>
            <tbody>
                @foreach ($doc->pagos as $p)
                    <tr><td>{{ $p->nombre }}</td><td>{{ optional($p->fecha)->format('d/m/Y') }}</td><td class="r">{{ $p->porcentaje }}%</td><td class="r">${{ number_format($p->monto, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($doc->nota_cliente)
        <div class="nota"><b>Nota al cliente:</b> {{ $doc->nota_cliente }}</div>
    @endif

    <div class="foot">{{ $leyenda }}</div>

    @if (isset($doc->fichas))
        @foreach ($doc->fichas as $f)
            <div class="ficha">
                <h2>{{ $f->titulo }}</h2>
                <p>{!! nl2br(e($f->contenido)) !!}</p>
            </div>
        @endforeach
    @endif
</div>
</body>
</html>
