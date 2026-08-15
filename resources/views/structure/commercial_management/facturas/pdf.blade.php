<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .wrap { padding: 28px 34px; }
        .head { width: 100%; border-bottom: 3px solid #2f7cff; padding-bottom: 12px; margin-bottom: 8px; }
        .head td { vertical-align: middle; }
        .brand { font-size: 22px; font-weight: bold; color: #111827; }
        .brand small { display: block; font-size: 10px; color: #6b7280; font-weight: normal; letter-spacing: 1px; }
        .folio { text-align: right; }
        .folio .tipo { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .folio .num { font-size: 16px; font-weight: bold; color: #2f7cff; }
        .stamp { display:inline-block; border:2px solid #f59e0b; color:#b45309; padding:3px 10px; border-radius:6px; font-weight:bold; font-size:10px; letter-spacing:1px; margin-top:4px; }
        .meta { width:100%; margin:14px 0; }
        .meta td { width:50%; vertical-align:top; padding-right:12px; }
        .box { border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; }
        .box b { color:#2f7cff; font-size:10px; text-transform:uppercase; }
        .box .l { margin:3px 0 0; font-size:11px; }
        table.items { width:100%; border-collapse:collapse; margin-bottom:14px; }
        table.items th { background:#f1f5ff; color:#1e3a8a; text-align:left; padding:8px; font-size:10px; text-transform:uppercase; }
        table.items td { padding:8px; border-bottom:1px solid #eef1f6; }
        .r { text-align:right; } .c { text-align:center; }
        .totes { width:45%; margin-left:55%; border-collapse:collapse; }
        .totes td { padding:5px 8px; font-size:12px; }
        .totes .strong td { font-size:14px; font-weight:bold; border-top:2px solid #2f7cff; }
        .foot { margin-top:26px; border-top:1px solid #e5e7eb; padding-top:10px; font-size:10px; color:#9ca3af; text-align:center; }
    </style>
</head>
<body>
<div class="wrap">
    @php $f = $factura; @endphp
    <table class="head">
        <tr>
            <td><div class="brand">Grupo MediBuy <small>EQUIPO MÉDICO</small></div></td>
            <td class="folio">
                <div class="tipo">Borrador de factura</div>
                <div class="num">{{ $f->folio }}</div>
                <div style="font-size:11px;color:#6b7280;">Fecha: {{ $f->created_at?->format('d/m/Y') }}</div>
                <div><span class="stamp">BORRADOR · SIN VALIDEZ FISCAL</span></div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="box">
                    <b>Receptor</b>
                    <div class="l">{{ $f->razon_social ?: ($f->customer?->nombre.' '.$f->customer?->apellido) }}</div>
                    <div class="l">RFC: {{ $f->rfc ?: '—' }}</div>
                    <div class="l">{{ $f->customer?->correo }}</div>
                </div>
            </td>
            <td>
                <div class="box">
                    <b>Datos CFDI</b>
                    <div class="l">Uso CFDI: {{ $f->uso_cfdi ?: '—' }}</div>
                    <div class="l">Forma de pago: {{ $f->forma_pago ?: '—' }}</div>
                    <div class="l">Método de pago: {{ $f->metodo_pago ?: '—' }}</div>
                    @if ($f->venta)<div class="l">Origen: {{ $f->venta->folio }}</div>@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th>Concepto</th><th>Modelo</th><th>Marca</th><th class="c">Cant.</th><th class="r">P. Unitario</th><th class="r">Importe</th></tr></thead>
        <tbody>
            @foreach ($f->items as $it)
                <tr>
                    <td>{{ $it->nombre }}</td><td>{{ $it->modelo ?? '—' }}</td><td>{{ $it->marca ?? '—' }}</td>
                    <td class="c">{{ $it->cantidad }}</td>
                    <td class="r">@if($it->es_regalo)REGALO @else ${{ number_format($it->precio_unitario, 2) }} @endif</td>
                    <td class="r">@if($it->es_regalo)— @else ${{ number_format($it->importe(), 2) }} @endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totes">
        <tr><td>Subtotal</td><td class="r">${{ number_format($f->subtotal, 2) }}</td></tr>
        @if ($f->descuento_monto > 0)<tr><td>Descuento</td><td class="r">-${{ number_format($f->descuento_monto, 2) }}</td></tr>@endif
        @if ($f->envio > 0)<tr><td>Envío</td><td class="r">${{ number_format($f->envio, 2) }}</td></tr>@endif
        <tr><td>IVA (16%)</td><td class="r">${{ number_format($f->iva_monto, 2) }}</td></tr>
        <tr class="strong"><td>Total</td><td class="r">${{ number_format($f->total, 2) }}</td></tr>
    </table>

    @if ($f->observaciones)<p style="margin-top:16px; font-size:11px;"><b>Observaciones:</b> {{ $f->observaciones }}</p>@endif

    <div class="foot">Documento interno de Grupo MediBuy · Este borrador NO constituye un CFDI válido ante el SAT.</div>
</div>
</body>
</html>
