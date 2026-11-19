{{--
    Tres documentos de una reclamación:
      acuse   — lo que se le entrega al cliente al recibir el reporte.
      informe — diagnóstico, dictamen y costos (uso técnico y con el fabricante).
      acta    — entrega del equipo al cerrar.
--}}
@extends('structure.garantias.pdf.layout')

@php
    $g = $r->garantia;
    $titulos = ['acuse' => 'Acuse de reclamación', 'informe' => 'Informe técnico', 'acta' => 'Acta de entrega'];
@endphp

@section('tipo', $titulos[$tipo].' '.$r->folio)
@section('meta', now()->format('d/m/Y H:i'))

@section('contenido')
    <h1 class="t">{{ $titulos[$tipo] }}</h1>
    <p class="sub">Reclamación {{ $r->folio }} · recibida el {{ $r->recibida_en->format('d/m/Y H:i') }} por {{ mb_strtolower($r->canalLabel()) }}</p>

    <table>
        <tr>
            <td style="width:50%; padding-right:20px;">
                <div class="rot" style="margin-top:0;">Cliente</div>
                <table class="par">
                    <tr><td class="k">Nombre</td><td class="v">{{ $r->clienteNombre() }}</td></tr>
                    @if ($r->contacto_nombre)<tr><td class="k">Reporta</td><td class="v">{{ $r->contacto_nombre }}</td></tr>@endif
                    @if ($r->contacto_telefono)<tr><td class="k">Teléfono</td><td class="v">{{ $r->contacto_telefono }}</td></tr>@endif
                </table>
            </td>
            <td style="width:50%;">
                <div class="rot" style="margin-top:0;">Equipo</div>
                <table class="par">
                    <tr><td class="k">Equipo</td><td class="v">{{ $r->equipoNombre() }}</td></tr>
                    <tr><td class="k">Serie</td><td class="v">{{ $r->no_serie ?: '—' }}</td></tr>
                    @if ($g)
                        <tr><td class="k">Garantía</td><td class="v">{{ $g->folio }} · {{ $g->tipoLabel() }}</td></tr>
                        <tr><td class="k">Vigencia</td><td class="v">{{ $g->inicio->format('d/m/Y') }} al {{ $g->vence->format('d/m/Y') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="rot">Falla reportada</div>
    <div class="texto caja">{{ $r->sintomas }}</div>

    @if ($tipo === 'acuse')
        <div class="rot">Seguimiento</div>
        <div class="texto">Su reporte quedó registrado con el folio {{ $r->folio }}. Prioridad: {{ mb_strtolower($r->prioridadLabel()) }}.
Tiempo estimado de resolución: a más tardar el {{ $r->limite_en?->format('d/m/Y') ?? '—' }}, sujeto al diagnóstico técnico.
@if ($g)Puede consultar el avance en el portal de su garantía: {{ $g->urlPublica() }}@endif</div>
        @if ($g)
            <table class="qr">
                <tr>
                    <td class="img"><img src="{{ \App\Support\CodigoQr::dataUri($g->urlPublica(), 82) }}" alt="Código QR"></td>
                    <td class="txt"><b>Consulte el avance</b>Escanee el código para ver el estado de su reporte.</td>
                </tr>
            </table>
        @endif
    @endif

    @if (in_array($tipo, ['informe', 'acta'], true))
        @if ($r->diagnostico)
            <div class="rot">Diagnóstico</div><div class="texto">{{ $r->diagnostico }}</div>
        @endif
        @if ($r->pruebas)
            <div class="rot">Pruebas y mediciones</div><div class="texto">{{ $r->pruebas }}</div>
        @endif
        @if ($r->causa)
            <div class="rot">Causa identificada</div><div class="texto">{{ $r->causa }}</div>
        @endif

        <div class="rot">Dictamen</div>
        <div class="texto"><b>{{ $r->dictamenLabel() }}</b>{{ $r->dictamen_motivo ? '. '.$r->dictamen_motivo : '' }}
@if ($r->dictamen_fundamento)Fundamento: {{ $r->dictamen_fundamento }}@endif</div>
    @endif

    @if ($tipo === 'informe' && $r->costos->isNotEmpty())
        <div class="rot">Costos</div>
        <table class="lista">
            <thead><tr><th>Concepto</th><th>Lo absorbe</th><th>Estado</th><th class="r">Monto</th></tr></thead>
            <tbody>
                @foreach ($r->costos as $c)
                    <tr>
                        <td><span class="nom">{{ $c->conceptoLabel() }}</span>{{ $c->descripcion ? ' · '.$c->descripcion : '' }}</td>
                        <td>{{ $c->absorbeLabel() }}</td>
                        <td>{{ $c->estadoLabel() }}</td>
                        <td class="r">${{ number_format($c->monto, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($r->sustituciones->isNotEmpty() && $tipo !== 'acuse')
        <div class="rot">Préstamos y sustituciones</div>
        <table class="lista">
            <tbody>
                @foreach ($r->sustituciones as $s)
                    <tr>
                        <td><span class="nom">{{ $s->tipoLabel() }}</span> · {{ $s->equipo }}</td>
                        <td>{{ $s->tipo === 'definitiva' ? ($s->no_serie_anterior ?: '—').' → '.$s->no_serie_entregado : ($s->no_serie_entregado ?: '') }}</td>
                        <td class="r">{{ $s->entregado_en->format('d/m/Y') }}{{ $s->devuelto_en ? ' · devuelto '.$s->devuelto_en->format('d/m/Y') : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($tipo === 'acta')
        <div class="rot">Resolución</div>
        <div class="texto"><b>{{ \App\Models\GarantiaReclamacion::RESOLUCIONES[$r->resolucion] ?? '—' }}</b>{{ $r->resolucion_detalle ? '. '.$r->resolucion_detalle : '' }}</div>
        <p style="margin-top:14px;">El cliente recibe el equipo descrito, revisado y en funcionamiento{{ $r->cerrada_en ? ', el '.$r->cerrada_en->format('d/m/Y') : '' }}.</p>
    @endif

    <table class="firma">
        <tr>
            <td><div class="linea">{{ $r->responsable?->name ?? config('medibuy.empresa.nombre') }}<br>Por {{ config('medibuy.empresa.nombre') }}</div></td>
            <td><div class="linea">{{ $tipo === 'acta' ? ($r->entregado_a ?: 'Recibe') : ($r->contacto_nombre ?: 'Cliente') }}<br>{{ $tipo === 'acta' ? 'Recibe de conformidad' : 'Cliente' }}</div></td>
        </tr>
    </table>
@endsection
