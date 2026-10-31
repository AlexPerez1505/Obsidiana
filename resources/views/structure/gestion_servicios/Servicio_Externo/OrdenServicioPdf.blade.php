<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px 40px 60px; }

        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #3d4450; font-size: 9px; line-height: 1.5; }

        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }

        .r { text-align: right; }

        /* Encabezado */
        .head td { vertical-align: middle; }
        .head img { height: 44px; }
        .head .titulo { text-align: center; }
        .head .titulo .t1 { font-size: 17px; font-weight: bold; letter-spacing: 4px; color: #1a1d23; }
        .head .titulo .t2 { margin-top: 4px; font-size: 8px; letter-spacing: 2.5px; color: #8a919c; text-transform: uppercase; }
        .folio { display: inline-block; padding: 5px 14px; background: #eef4ff; border-radius: 999px;
                 color: #3b82f6; font-size: 11px; font-weight: bold; }

        /* Tarjetas */
        .card { border: 1px solid #e8eaee; border-radius: 10px; padding: 14px 18px; margin-top: 14px; }
        .card-titulo { font-size: 8px; font-weight: bold; letter-spacing: 1.8px; color: #1a1d23;
                       text-transform: uppercase; padding-bottom: 10px; }

        .par td { padding: 2px 0; font-size: 9px; }
        .par .k { width: 110px; color: #a8aeb8; font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase;
                  padding-top: 3px; }
        .par .v { color: #1a1d23; font-weight: bold; }

        /* Dos columnas */
        .col-izq { width: 52%; padding-right: 10px; }
        .col-der { width: 48%; padding-left: 10px; }

        ul.acciones { margin: 0; padding: 0; list-style: none; }
        ul.acciones li { padding: 3px 0 3px 14px; font-size: 9px; color: #3d4450; position: relative; }
        ul.acciones li:before { content: ''; position: absolute; left: 0; top: 8px;
                                width: 5px; height: 5px; border-radius: 50%; background: #3d4450; }

        /* Fotos */
        .foto { display: inline-block; width: 46%; margin: 0 1% 10px; text-align: center; }
        .foto .marco { border: 1px solid #e8eaee; border-radius: 8px; padding: 6px; }
        .foto img { width: 100%; height: 110px; }
        .foto .cap { margin-top: 5px; font-size: 7px; letter-spacing: 1.5px; color: #a8aeb8; text-transform: uppercase; }

        /* Dictamen */
        .chk { display: inline-block; width: 11px; height: 11px; border: 1.5px solid #a8aeb8; border-radius: 3px;
               vertical-align: -1px; margin-right: 6px; }
        .chk.on { background: #3b82f6; border-color: #3b82f6; }
        .dictamen-txt { margin-top: 8px; border: 1px solid #e8eaee; border-radius: 8px; padding: 10px;
                        font-size: 9px; color: #3d4450; min-height: 40px; }

        /* Firmas */
        .firmas { margin-top: 60px; }
        .firmas td { width: 33.3%; padding: 0 20px; text-align: center; }
        .firmas .linea { border-top: 1px solid #1a1d23; padding-top: 7px; }
        .firmas .nom { font-size: 8.5px; font-weight: bold; color: #1a1d23; letter-spacing: 1px; text-transform: uppercase; }
        .firmas .rol { margin-top: 2px; font-size: 7px; letter-spacing: 1.5px; color: #a8aeb8; text-transform: uppercase; }

        .pie { position: fixed; bottom: -50px; left: 0; right: 0; padding-top: 8px;
               border-top: 1px solid #ededf0; font-size: 7.5px; color: #a8aeb8; letter-spacing: 1px; }
    </style>
</head>
<body>

@php
    $emp = config('medibuy.empresa', ['nombre' => config('app.name')]);
    $con = config('medibuy.contacto', []);
    $logoUri = \App\Support\LogoPdf::dataUri();

    $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
    $tecnico = $service->internalTechnician?->name ?? $service->externalTechnician?->name ?? '—';
    $autorizo = $con['nombre'] ?? '—';

    $equipment = $service->serviceEquipment;
    $folio = $service->service_number ?? ('OS-' . $service->id);

    $eq = $equipment?->type_text ?? '—';
    $marca = $equipment?->brand_text ?? '—';
    $modelo = $equipment?->model_text ?? '—';
    $serie = $equipment?->serial_number ?? '—';
    $observaciones = $equipment?->observations;
    $proximo = null;
    $tipoMant = null;
    $acciones = collect();
    if (!empty($orden ?? [])) {
        $eq = ($orden['equipo'] ?? null) ?: $eq;
        $marca = ($orden['marca'] ?? null) ?: $marca;
        $modelo = ($orden['modelo'] ?? null) ?: $modelo;
        $serie = ($orden['serie'] ?? null) ?: $serie;
        $observaciones = ($orden['observaciones'] ?? null) ?: $observaciones;
        $proximo = $orden['proximo_mantenimiento'] ?? null;
        $tipoMant = $orden['tipo_mantenimiento'] ?? null;
        $acciones = collect($orden['acciones'] ?? []);
    }

    $fIngreso = $fechaIngreso ?? $service->created_at;
    $fServicio = $fechaMantenimiento ?? now();
    $fProximo = $proximo ? (clone $fServicio)->addMonths((int) $proximo) : null;

    $fotos = collect([$equipment?->evidence_1_path, $equipment?->evidence_2_path, $equipment?->evidence_3_path])
        ->filter()
        ->map(fn ($p) => \App\Support\ImagenPdf::dataUri(asset('storage/' . $p)))
        ->filter()
        ->values();

    $telPie = $con['telefono'] ?? '+52 722 448 5915';
    $correoPie = $con['correo'] ?? 'ventas@grupomedibuy.com';
@endphp

<table class="head">
    <tr>
        <td style="width:22%;">
            @if ($logoUri)
                <img src="{{ $logoUri }}" alt="{{ $emp['nombre'] }}">
            @endif
        </td>
        <td class="titulo" style="width:56%;">
            <div class="t1">ORDEN DE SERVICIO</div>
            <div class="t2">{{ $emp['nombre'] }} · Departamento técnico</div>
        </td>
        <td class="r" style="width:22%;">
            <span class="folio">{{ $folio }}</span>
        </td>
    </tr>
</table>

{{-- INFORMACIÓN GENERAL --}}
<div class="card">
    <div class="card-titulo">Información general</div>
    <table>
        <tr>
            <td style="width:55%;">
                <table class="par">
                    <tr><td class="k">Cliente</td><td class="v">{{ strtoupper($customerName) }}</td></tr>
                    <tr><td class="k">Representante</td><td class="v">{{ strtoupper($tecnico) }}</td></tr>
                    <tr><td class="k">Teléfono</td><td class="v">{{ $service->customer?->telefono ?? '—' }}</td></tr>
                    <tr><td class="k">Dirección</td><td class="v">{{ strtoupper($service->customer?->direccion ?? '—') }}</td></tr>
                </table>
            </td>
            <td style="width:45%;">
                <table class="par">
                    <tr><td class="k" style="width:105px;">Fecha de ingreso</td><td class="v">{{ $fIngreso?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="k">Fecha servicio</td><td class="v">{{ $fServicio?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="k">Próximo serv.</td><td class="v">{{ $fProximo ? $fProximo->format('d/m/Y') . ' (' . $proximo . ' meses)' : '—' }}</td></tr>
                    <tr><td class="k">Clasificación</td><td class="v" style="text-transform:capitalize;">{{ $tipoMant ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- ESPECIFICACIONES DEL EQUIPO --}}
<div class="card">
    <div class="card-titulo">Especificaciones del equipo</div>
    <table class="par">
        <tr>
            <td class="k" style="width:90px;">Equipo</td><td class="v" style="width:38%;">{{ $eq }}</td>
            <td class="k" style="width:90px;">Marca</td><td class="v">{{ $marca }}</td>
        </tr>
        <tr>
            <td class="k">Modelo</td><td class="v">{{ $modelo }}</td>
            <td class="k">No. de serie</td><td class="v">{{ $serie }}</td>
        </tr>
    </table>
</div>

{{-- ACCIONES / FOTOS + DICTAMEN --}}
<table style="margin-top:14px;">
    <tr>
        <td class="col-izq">
            <div class="card" style="margin-top:0; min-height:180px;">
                <div class="card-titulo">Acciones realizadas</div>
                <ul class="acciones">
                    @forelse ($acciones as $accion)
                        <li>{{ $accion }}</li>
                    @empty
                        <li>Diagnóstico general del equipo</li>
                        <li>Mantenimiento preventivo</li>
                        <li>Pruebas de funcionamiento</li>
                    @endforelse
                </ul>
            </div>
        </td>
        <td class="col-der">
            @if ($fotos->isNotEmpty())
                <div class="card" style="margin-top:0;">
                    <div class="card-titulo">Registro fotográfico</div>
                    @foreach ($fotos as $i => $foto)
                        <div class="foto">
                            <div class="marco"><img src="{{ $foto }}"></div>
                            <div class="cap">Perspectiva {{ $i + 1 }}{{ $i === 0 ? ' (SN: ' . $serie . ')' : '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="card" style="margin-top:12px;">
                <div class="card-titulo">Dictamen / observaciones</div>
                <div>
                    <span class="chk {{ $tipoMant === 'preventivo' ? 'on' : '' }}"></span>Preventivo
                    &nbsp;&nbsp;&nbsp;
                    <span class="chk {{ $tipoMant === 'correctivo' ? 'on' : '' }}"></span>Correctivo
                </div>
                <div class="dictamen-txt">{{ $observaciones ?? '' }}</div>
            </div>
        </td>
    </tr>
</table>

{{-- FIRMAS --}}
<table class="firmas">
    <tr>
        <td>
            <div class="linea">
                <div class="nom">{{ strtoupper($tecnico) }}</div>
                <div class="rol">Ingeniero / Técnico responsable</div>
            </div>
        </td>
        <td>
            <div class="linea">
                <div class="nom">{{ strtoupper($customerName) }}</div>
                <div class="rol">Conformidad del cliente</div>
            </div>
        </td>
        <td>
            <div class="linea">
                <div class="nom">{{ strtoupper($autorizo) }}</div>
                <div class="rol">Autorizó</div>
            </div>
        </td>
    </tr>
</table>

<div class="pie">
    <table>
        <tr>
            <td>Teléfono: {{ $telPie }}</td>
            <td class="r">Correo: {{ $correoPie }}</td>
        </tr>
    </table>
</div>

</body>
</html>
