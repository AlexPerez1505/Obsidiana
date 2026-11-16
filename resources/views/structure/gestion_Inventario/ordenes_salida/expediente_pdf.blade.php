{{-- Expediente de salida: las tres etapas con quién las hizo, su checklist,
     lo que agregó o con qué piezas salió, la evidencia y las firmas. --}}
@php
    use App\Models\OrdenSalidaEtapa as E;

    $emp = config('medibuy.empresa');
    $logoUri = \App\Support\LogoPdf::dataUri();
    $venta = $orden->venta;
    $cliente = trim(($venta?->customer?->nombre ?? '') . ' ' . ($venta?->customer?->apellido ?? '')) ?: '—';
    $resp = ['si' => 'Sí', 'no' => 'No', 'na' => 'N/A'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96px 46px 56px; }
        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 9.5px; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .r { text-align: right; } .c { text-align: center; }

        .head { position: fixed; top: -84px; left: 0; right: 0; height: 66px; }
        .head td { vertical-align: middle; }
        .head img { height: 42px; }
        .head .tipo { color: #1a1d23; font-size: 12px; font-weight: bold; }
        .head .meta { margin-top: 2px; color: #a8aeb8; font-size: 8.5px; }
        .pie { position: fixed; bottom: -46px; left: 0; right: 0; height: 40px; padding-top: 9px; border-top: 1px solid #ededf0; }
        .pie td { font-size: 8px; color: #a8aeb8; }
        .pie .num:after { content: counter(page); }

        h1.t { margin: 0 0 4px; color: #1a1d23; font-size: 20px; font-weight: bold; letter-spacing: -.4px; }
        .sub { margin: 0 0 14px; color: #a8aeb8; font-size: 9.5px; }
        .rot { margin: 0 0 7px; color: #a8aeb8; font-size: 7.5px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
        .par td { padding: 0 0 3px; }
        .par .k { width: 96px; color: #a8aeb8; } .par .v { color: #4b5563; }

        .estado { display: inline-block; padding: 2px 9px; border-radius: 10px; font-size: 8px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
        .estado.terminada { background: #e6f6ec; color: #1f7a3f; }
        .estado.en_curso { background: #e8f0fe; color: #1d4ed8; }
        .estado.pendiente { background: #f1f2f4; color: #6b7280; }

        .items th { padding: 5px 6px; border-bottom: 1px solid #1a1d23; color: #a8aeb8; font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; text-align: left; }
        .items td { padding: 6px; border-bottom: 1px solid #ededf0; }
        .items .n { color: #1a1d23; font-weight: bold; }

        .etapa { margin-top: 22px; page-break-inside: auto; }
        .etapa-cab { padding: 9px 12px; background: #f7f8fa; border-left: 3px solid #1a1d23; }
        .etapa-cab td { vertical-align: middle; }
        .etapa-cab .nom { color: #1a1d23; font-size: 13px; font-weight: bold; }
        .etapa-cab .num { color: #a8aeb8; font-size: 8px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        .quien td { padding: 8px 0 2px; font-size: 9px; }
        .quien .k { color: #a8aeb8; }

        .check { margin-top: 8px; }
        .check td { padding: 4px 6px; border-bottom: 1px solid #f1f2f4; }
        .check .g td { padding-top: 8px; color: #a8aeb8; font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; border-bottom: 0; }
        .check .res { width: 42px; font-weight: bold; text-align: center; }
        .res.si { color: #1f7a3f; } .res.no { color: #b42318; } .res.na, .res.x { color: #a8aeb8; }
        .check .nota { color: #6b7280; font-size: 8.5px; }

        .lista { margin: 8px 0 0; padding-left: 14px; }
        .lista li { margin-bottom: 2px; }
        .notas { margin-top: 8px; padding: 8px 10px; background: #f7f8fa; color: #4b5563; }

        .fotos { margin-top: 10px; }
        .fotos td { width: 33.3%; padding: 3px; text-align: center; }
        .fotos img { max-width: 100%; max-height: 150px; border: 1px solid #ededf0; }
        .fotos .pie-f { color: #a8aeb8; font-size: 7.5px; }
        .otros { margin-top: 4px; color: #6b7280; font-size: 8.5px; }

        .firma-etapa { margin-top: 10px; width: 240px; }
        .firma-etapa .caja { height: 64px; text-align: center; }
        .firma-etapa img { max-height: 62px; max-width: 220px; }
        .firma-etapa .linea { border-top: 1px solid #c9ced6; padding-top: 4px; text-align: center; color: #1a1d23; font-weight: bold; }
        .firma-etapa .rol { color: #a8aeb8; font-weight: normal; font-size: 8px; }
    </style>
</head>
<body>
<div class="head">
    <table><tr>
        <td>@if ($logoUri)<img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">@else<b>{{ $emp['nombre'] }}</b>@endif</td>
        <td class="r">
            <div class="tipo">Expediente de salida {{ $orden->folio }}</div>
            <div class="meta">{{ $venta?->folio }} · generado el {{ now()->format('d/m/Y H:i') }}</div>
        </td>
    </tr></table>
</div>
<div class="pie">
    <table><tr>
        <td>{{ $emp['nombre'] }} @if ($emp['web']) · {{ $emp['web'] }} @endif</td>
        <td class="r">Expediente {{ $orden->folio }} · Página <span class="num"></span></td>
    </tr></table>
</div>

<h1 class="t">Expediente de salida</h1>
<p class="sub">{{ $orden->folio }} · Venta {{ $venta?->folio ?? '—' }} · {{ $orden->estadoLabel() }}
    @if ($orden->entregada_en) · entregada el {{ $orden->entregada_en->format('d/m/Y H:i') }} a {{ $orden->recibe_nombre }} @endif</p>

<table>
    <tr>
        <td style="width:55%;">
            <p class="rot">Cliente</p>
            <table class="par">
                <tr><td class="k">Cliente</td><td class="v">{{ $cliente }}</td></tr>
                @if ($venta?->customer?->telefono)<tr><td class="k">Teléfono</td><td class="v">{{ $venta->customer->telefono }}</td></tr>@endif
                @if ($venta?->customer?->direccion)<tr><td class="k">Dirección</td><td class="v">{{ $venta->customer->direccion }}</td></tr>@endif
            </table>
        </td>
        <td>
            <p class="rot">Venta</p>
            <table class="par">
                <tr><td class="k">Asesor</td><td class="v">{{ $venta?->seller?->name ?? '—' }}</td></tr>
                <tr><td class="k">Generada</td><td class="v">{{ $orden->created_at?->format('d/m/Y H:i') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<p class="rot" style="margin-top:16px;">Equipo de la orden</p>
<table class="items">
    <thead><tr><th>Partida</th><th style="width:60px;">Cant.</th><th>Series</th></tr></thead>
    <tbody>
        @foreach ($orden->items as $i)
            <tr>
                <td><span class="n">{{ $i->nombre }}</span>@if ($i->descripcion)<br>{{ $i->descripcion }}@endif</td>
                <td>{{ $i->cantidad }}</td>
                <td>{{ $i->no_series ?: '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@forelse ($etapas as $e)
    @php
        $ev = $evidencia[$e->etapa] ?? ['fotos' => [], 'otros' => [], 'total' => 0, 'firma' => null, 'firma_recibe' => null];
        $grupos = collect($e->checklist ?: [])->groupBy(fn ($p) => $p['grupo'] ?? 'General');
        $r = $e->resumenChecklist();
        $d = $e->datos ?: [];
    @endphp
    <div class="etapa">
        <table class="etapa-cab"><tr>
            <td><div class="num">Etapa {{ $e->numero() }}</div><div class="nom">{{ $e->nombre() }}</div></td>
            <td class="r"><span class="estado {{ $e->estado }}">{{ $e->estadoLabel() }}</span></td>
        </tr></table>

        <table class="quien"><tr>
            <td><span class="k">Responsable:</span> {{ $e->asignadaA?->name ?? 'Sin asignar' }}</td>
            <td><span class="k">Comenzó:</span> {{ $e->iniciada_en?->format('d/m/Y H:i') ?? '—' }}</td>
            <td><span class="k">Terminó:</span> {{ $e->terminadaPor?->name ?? '—' }} {{ $e->terminada_en?->format('d/m/Y H:i') }}</td>
        </tr>
        @if ($e->editada_en)
            <tr><td colspan="3"><span class="k">Corregida por:</span> {{ $e->editadaPor?->name }} el {{ $e->editada_en->format('d/m/Y H:i') }}</td></tr>
        @endif
        </table>

        @if ($r['total'])
            <table class="check">
                @foreach ($grupos as $g => $puntos)
                    <tr class="g"><td colspan="2">{{ $g }}</td></tr>
                    @foreach ($puntos as $p)
                        <tr>
                            <td>{{ $p['texto'] }}@if (! empty($p['nota']))<br><span class="nota">Nota: {{ $p['nota'] }}</span>@endif</td>
                            <td class="res {{ $p['respuesta'] ?? 'x' }}">{{ $resp[$p['respuesta'] ?? ''] ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
            <div class="otros">Checklist {{ $e->checklist_ia ? 'hecho con IA para este equipo' : 'general' }}: {{ $r['si'] }} bien, {{ $r['no'] }} con falla, {{ $r['na'] }} no aplica{{ $r['faltan'] ? ', '.$r['faltan'].' sin contestar' : '' }}.</div>
        @endif

        @if ($e->extras)
            <p class="rot" style="margin-top:10px;">{{ $e->etapa === E::EMPAQUE ? 'Piezas con las que sale' : 'Lo que agregó de más' }}</p>
            <ul class="lista" style="margin-top:0;">
                @foreach ($e->extras as $x)
                    @if ($e->etapa !== E::EMPAQUE || ! empty($x['sale']))
                        <li>{{ $x['texto'] }} × {{ $x['cantidad'] ?? 1 }}</li>
                    @endif
                @endforeach
            </ul>
        @endif

        @if ($e->etapa === E::ENTREGA && ! empty($d['modalidad']))
            <table class="quien"><tr>
                <td><span class="k">Cómo:</span> {{ E::MODALIDADES[$d['modalidad']] ?? '' }}</td>
                @if (! empty($d['paqueteria']))<td><span class="k">Paquetería:</span> {{ $d['paqueteria'] }} · guía {{ $d['guia'] ?? '—' }}</td>@endif
                @if (! empty($d['recibe_nombre']))<td><span class="k">Recibió:</span> {{ $d['recibe_nombre'] }}</td>@endif
            </tr></table>
        @endif

        @if ($e->notas)
            <div class="notas"><b>Notas:</b> {{ $e->notas }}</div>
        @endif

        @if ($ev['fotos'])
            <table class="fotos">
                @foreach (array_chunk($ev['fotos'], 3) as $fila)
                    <tr>
                        @foreach ($fila as $f)
                            <td><img src="{{ $f['src'] }}" alt=""><div class="pie-f">{{ $f['quien'] }} · {{ $f['cuando'] }}</div></td>
                        @endforeach
                        @for ($k = count($fila); $k < 3; $k++)<td></td>@endfor
                    </tr>
                @endforeach
            </table>
        @endif
        @if ($ev['otros'])
            <div class="otros">También: {{ implode(' · ', $ev['otros']) }}</div>
        @endif
        @if (! $ev['total'])
            <div class="otros">Sin evidencia.</div>
        @endif

        <table style="width:auto;"><tr>
            <td>
                <div class="firma-etapa">
                    <div class="caja">@if ($ev['firma'])<img src="{{ $ev['firma'] }}" alt="">@endif</div>
                    <div class="linea">{{ $e->terminadaPor?->name ?? $e->asignadaA?->name ?? '—' }}<br><span class="rot">{{ $e->etapa === E::ENTREGA ? 'Entrega' : $e->nombre() }}</span></div>
                </div>
            </td>
            @if ($e->etapa === E::ENTREGA && ($ev['firma_recibe'] || ! empty($d['recibe_nombre'])))
                <td style="padding-left:30px;">
                    <div class="firma-etapa">
                        <div class="caja">@if ($ev['firma_recibe'])<img src="{{ $ev['firma_recibe'] }}" alt="">@endif</div>
                        <div class="linea">{{ $d['recibe_nombre'] ?? '—' }}<br><span class="rot">Recibe</span></div>
                    </div>
                </td>
            @endif
        </tr></table>
    </div>
@empty
    <p class="otros" style="margin-top:18px;">Esta orden se cerró antes de que existieran las etapas.</p>
@endforelse

@if ($totalSueltas)
    <div class="etapa">
        <table class="etapa-cab"><tr><td><div class="nom">Otra evidencia de la orden</div></td></tr></table>
        @if ($fotosSueltas)
            <table class="fotos">
                @foreach (array_chunk($fotosSueltas, 3) as $fila)
                    <tr>@foreach ($fila as $src)<td><img src="{{ $src }}" alt=""></td>@endforeach @for ($k = count($fila); $k < 3; $k++)<td></td>@endfor</tr>
                @endforeach
            </table>
        @endif
        <div class="otros">{{ $totalSueltas }} archivo(s) subidos en la orden.</div>
    </div>
@endif
</body>
</html>
