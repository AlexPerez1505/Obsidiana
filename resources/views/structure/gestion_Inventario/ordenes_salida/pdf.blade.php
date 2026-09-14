{{-- Hoja impresa de la orden de salida: qué salió, cuándo, quién lo preparó,
     quién autorizó la salida y quién lo recibió, con las firmas. --}}
@php
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');
    $logoUri = \App\Support\LogoPdf::dataUri();

    $venta = $orden->venta;
    $cliente = trim(($venta?->customer?->nombre ?? '') . ' ' . ($venta?->customer?->apellido ?? '')) ?: '—';
    $entregada = $orden->estado === \App\Models\OrdenSalida::ENTREGADA;
    $cancelada = $orden->estado === \App\Models\OrdenSalida::CANCELADA;
    $avance = $orden->avance();
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
        td, th { vertical-align: top; }
        .r { text-align: right; }
        .c { text-align: center; }

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
        .sub { margin: 0 0 18px; color: #a8aeb8; font-size: 9.5px; }

        .estado { display: inline-block; padding: 2px 9px; border-radius: 10px; font-size: 8.5px; font-weight: bold;
                  letter-spacing: .6px; text-transform: uppercase; }
        .estado.ok { background: #e6f6ec; color: #1f7a3f; }
        .estado.mal { background: #fde8e8; color: #b42318; }
        .estado.pend { background: #f1f2f4; color: #6b7280; }

        .rot { margin: 0 0 9px; color: #a8aeb8; font-size: 7.5px; font-weight: bold;
               letter-spacing: 1.6px; text-transform: uppercase; }
        .par td { padding: 0 0 3px; font-size: 9.5px; }
        .par .k { width: 96px; color: #a8aeb8; }
        .par .v { color: #4b5563; }
        .destinatario { margin: 0 0 4px; color: #1a1d23; font-size: 13px; font-weight: bold; }

        .bloque { margin-top: 22px; }

        /* Cronología: quién hizo qué y a qué hora. */
        .crono td { padding: 6px 0; border-bottom: 1px solid #f1f2f4; font-size: 9.5px; }
        .crono .paso { width: 150px; color: #1a1d23; font-weight: bold; }
        .crono .cuando { width: 130px; color: #4b5563; }
        .crono .quien { color: #6b7280; }
        .crono .falta { color: #c9ced6; }

        .items th { padding: 6px 6px; border-bottom: 1px solid #1a1d23; color: #a8aeb8; font-size: 7.5px;
                    font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; text-align: left; }
        .items td { padding: 7px 6px; border-bottom: 1px solid #ededf0; font-size: 9.5px; }
        .items .n { color: #1a1d23; font-weight: bold; }
        .items .s { color: #a8aeb8; font-size: 8.5px; }
        .items .num { width: 22px; color: #a8aeb8; }
        .ok { color: #1f7a3f; font-weight: bold; }
        .no { color: #a8aeb8; }

        .notas { margin-top: 18px; padding: 10px 12px; background: #f7f8fa; color: #4b5563; font-size: 9.5px; }

        .firmas { margin-top: 34px; }
        .firmas td { width: 50%; text-align: center; padding: 0 18px; }
        .firmas .caja { height: 78px; }
        .firmas img { max-height: 74px; max-width: 100%; }
        .firmas .linea { border-top: 1px solid #c9ced6; padding-top: 6px; color: #1a1d23; font-size: 9.5px; font-weight: bold; }
        .firmas .rol { color: #a8aeb8; font-size: 8px; }

        .aviso { margin-top: 18px; padding: 8px 12px; border: 1px dashed #c9ced6; color: #6b7280; font-size: 9px; }
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
                <div class="tipo">Orden de salida {{ $orden->folio }}</div>
                <div class="meta">Venta {{ $venta?->folio ?? '—' }} · Impreso el {{ now()->format('d/m/Y H:i') }}</div>
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
                Control de salida de almacén
            </td>
        </tr>
    </table>
</div>

<h1 class="t">Orden de salida</h1>
<p class="sub">
    {{ $orden->folio }} · generada el {{ $orden->created_at?->format('d/m/Y H:i') }}
    &nbsp; <span class="estado {{ $entregada ? 'ok' : ($cancelada ? 'mal' : 'pend') }}">{{ $orden->estadoLabel() }}</span>
</p>

<table>
    <tr>
        <td style="width:52%; padding-right:24px;">
            <p class="rot">Sale para</p>
            <div class="destinatario">{{ $cliente }}</div>
            <table class="par">
                @if ($venta?->customer?->telefono)
                    <tr><td class="k">Teléfono</td><td class="v">{{ $venta->customer->telefono }}</td></tr>
                @endif
                @if ($venta?->customer?->direccion)
                    <tr><td class="k">Dirección</td><td class="v">{{ $venta->customer->direccion }}</td></tr>
                @endif
                <tr><td class="k">Venta</td><td class="v">{{ $venta?->folio ?? '—' }} · {{ $venta?->created_at?->format('d/m/Y') }}</td></tr>
                <tr><td class="k">Asesor</td><td class="v">{{ $venta?->seller?->name ?? '—' }}</td></tr>
            </table>
        </td>
        <td style="width:48%;">
            <p class="rot">Resumen</p>
            <table class="par">
                <tr><td class="k">Partidas</td><td class="v">{{ $orden->items->count() }} · {{ $orden->items->sum('cantidad') }} pieza(s)</td></tr>
                <tr><td class="k">Se emplayan</td><td class="v">{{ $orden->items->where('requiere_emplayado', true)->count() }} de {{ $orden->items->count() }}</td></tr>
                <tr><td class="k">Avance</td><td class="v">{{ $avance['hechos'] }} de {{ $avance['total'] }} pasos ({{ $avance['pct'] }}%)</td></tr>
                <tr><td class="k">Generó la orden</td><td class="v">{{ $orden->creator?->name ?? '—' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===================== Cronología ===================== --}}
<div class="bloque">
    <p class="rot">Cuándo y quién</p>
    <table class="crono">
        <tr>
            <td class="paso">Orden generada</td>
            <td class="cuando">{{ $orden->created_at?->format('d/m/Y H:i') }}</td>
            <td class="quien">{{ $orden->creator?->name ?? 'Sistema, al registrar la venta' }}</td>
        </tr>
        <tr>
            <td class="paso">Equipo preparado</td>
            @if ($orden->preparada_en)
                <td class="cuando">{{ $orden->preparada_en->format('d/m/Y H:i') }}</td>
                <td class="quien">{{ $orden->preparadaPor?->name ?? '—' }}</td>
            @else
                <td class="cuando falta">Pendiente</td><td class="quien falta">—</td>
            @endif
        </tr>
        <tr>
            <td class="paso">Salida autorizada y entregada</td>
            @if ($entregada)
                <td class="cuando">{{ $orden->entregada_en?->format('d/m/Y H:i') }}</td>
                <td class="quien">{{ $orden->entregadaPor?->name ?? '—' }} (almacén)</td>
            @elseif ($cancelada)
                <td class="cuando falta">Cancelada</td><td class="quien falta">—</td>
            @else
                <td class="cuando falta">Pendiente</td><td class="quien falta">—</td>
            @endif
        </tr>
        <tr>
            <td class="paso">Recibió el equipo</td>
            <td class="cuando">{{ $entregada ? $orden->entregada_en?->format('d/m/Y H:i') : '' }}</td>
            <td class="quien {{ $entregada ? '' : 'falta' }}">{{ $entregada ? $orden->recibe_nombre : '—' }}</td>
        </tr>
    </table>
</div>

{{-- ===================== Qué salió ===================== --}}
<div class="bloque">
    <p class="rot">Qué salió</p>
    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Equipo</th>
                <th class="c" style="width:40px;">Cant.</th>
                <th style="width:120px;">Preparado</th>
                <th style="width:120px;">Emplayado</th>
                <th style="width:130px;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orden->items as $i => $item)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>
                        <div class="n">{{ $item->nombre }}</div>
                        @if ($item->descripcion)<div class="s">{{ $item->descripcion }}</div>@endif
                        @if ($item->no_series)<div class="s">Series: {{ $item->no_series }}</div>@endif
                    </td>
                    <td class="c">{{ $item->cantidad }}</td>
                    <td>
                        @if ($item->preparado)
                            <span class="ok">Sí</span> <span class="s">{{ $item->preparado_en?->format('d/m H:i') }}</span>
                        @else
                            <span class="no">No</span>
                        @endif
                    </td>
                    <td>
                        @if (! $item->requiere_emplayado)
                            <span class="no">No aplica</span>
                        @elseif ($item->emplayado)
                            <span class="ok">Sí</span> <span class="s">{{ $item->emplayado_en?->format('d/m H:i') }}</span>
                        @else
                            <span class="no">No</span>
                        @endif
                    </td>
                    <td class="s" style="color:#4b5563;">{{ $item->observaciones ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if ($orden->notas)
    <div class="notas"><b>Notas.</b> {{ $orden->notas }}</div>
@endif

{{-- ===================== Firmas ===================== --}}
<table class="firmas">
    <tr>
        <td>
            <div class="caja">
                @if ($firmaEntrega)<img src="{{ $firmaEntrega }}" alt="Firma de quien entrega">@endif
            </div>
            <div class="linea">{{ $entregada ? ($orden->entregadaPor?->name ?? 'Almacén') : 'Nombre y firma' }}</div>
            <div class="rol">Entrega y autoriza la salida (almacén)</div>
        </td>
        <td>
            <div class="caja">
                @if ($firmaRecibe)<img src="{{ $firmaRecibe }}" alt="Firma de quien recibe">@endif
            </div>
            <div class="linea">{{ $entregada ? $orden->recibe_nombre : 'Nombre y firma' }}</div>
            <div class="rol">Recibe el equipo</div>
        </td>
    </tr>
</table>

@if (! $entregada && ! $cancelada)
    <p class="aviso">Esta orden todavía no tiene la salida firmada: el documento se imprime como avance. La versión final se genera al firmar la salida en el sistema.</p>
@elseif ($cancelada)
    <p class="aviso">Orden cancelada junto con la venta: el equipo no salió del almacén.</p>
@endif

</body>
</html>
