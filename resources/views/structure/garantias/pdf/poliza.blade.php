{{--
    Póliza de garantía por escrito: quién garantiza, qué equipo, alcance,
    duración, exclusiones y cómo reclamar. Si el equipo es parte de una
    torre, se listan todos sus componentes con su propio plazo.
--}}
@extends('structure.garantias.pdf.layout')

@php
    $cliente = $g->clienteNombre();
    $emp = config('medibuy.empresa');
    $con = config('medibuy.contacto');
@endphp

@section('tipo', 'Póliza de garantía '.$g->folio)
@section('meta', 'Emitida el '.now()->format('d/m/Y'))

@section('contenido')
    <h1 class="t">{{ $g->grupo ?: $g->equipoNombre() }}</h1>
    <p class="sub">{{ $g->tipoLabel() }} · {{ $g->venta ? 'Venta '.$g->venta->folio : 'Registro '.$g->folio }}</p>

    <table>
        <tr>
            <td style="width:50%; padding-right:20px;">
                <div class="rot" style="margin-top:0;">Garantiza</div>
                <table class="par">
                    <tr><td class="k">Empresa</td><td class="v">{{ $emp['nombre'] }}</td></tr>
                    @if ($emp['rfc'])<tr><td class="k">RFC</td><td class="v">{{ $emp['rfc'] }}</td></tr>@endif
                    @if ($emp['ubicacion'])<tr><td class="k">Domicilio</td><td class="v">{{ $emp['ubicacion'] }}</td></tr>@endif
                </table>
            </td>
            <td style="width:50%;">
                <div class="rot" style="margin-top:0;">Cliente</div>
                <table class="par">
                    <tr><td class="k">Nombre</td><td class="v">{{ $cliente }}</td></tr>
                    @if ($g->customer?->rfc)<tr><td class="k">RFC</td><td class="v">{{ $g->customer->rfc }}</td></tr>@endif
                    @if ($g->customer?->direccion)<tr><td class="k">Domicilio</td><td class="v">{{ $g->customer->direccion }}</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <div class="rot">Equipo cubierto</div>
    <table class="lista">
        <thead><tr><th>Equipo o componente</th><th>Serie</th><th>Condición</th><th>Plazo</th><th style="text-align:right;">Vigencia</th></tr></thead>
        <tbody>
            @foreach ($componentes as $c)
                <tr>
                    <td><span class="nom">{{ $c->equipoNombre() }}</span><br>{{ $c->folio }}</td>
                    <td>{{ $c->no_serie ?: ($c->codigo_interno ?: '—') }}</td>
                    <td>{{ $c->condicionLabel() }}</td>
                    <td>{{ $c->meses }} meses</td>
                    <td class="r">{{ $c->inicio->format('d/m/Y') }} al {{ $c->vence->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="color:#a8aeb8; font-size:8.5px; margin:6px 0 0;">
        La cobertura corre a partir de la fecha de entrega del equipo{{ $g->fecha_entrega ? ' ('.$g->fecha_entrega->format('d/m/Y').')' : '' }}.
        @if ($componentes->count() > 1) Cada componente tiene su propia garantía: la falla de uno no modifica la cobertura de los demás. @endif
    </p>

    <div class="rot">Alcance de la garantía</div>
    <div class="texto">{{ $g->cubre ?: 'Defectos de fabricación y fallas de funcionamiento en condiciones normales de uso.' }}</div>

    <div class="rot">Exclusiones</div>
    <div class="texto">{{ $g->exclusiones ?: 'Sin exclusiones adicionales.' }}</div>

    @if ($g->condiciones)
        <div class="rot">Condiciones</div>
        <div class="texto">{{ $g->condiciones }}</div>
    @endif

    <div class="rot">Cómo hacer válida la garantía</div>
    <div class="texto">1. Reporte la falla en el portal de su garantía (código QR de esta póliza){{ $con['telefono'] ? ', al teléfono '.$con['telefono'] : '' }}{{ $con['correo'] ? ' o al correo '.$con['correo'] : '' }}, indicando el número de serie y describiendo la falla.
2. Recibirá un número de seguimiento. Nuestro servicio biomédico revisará el equipo y le informará el diagnóstico y la resolución.
3. Presente esta póliza o el número de folio. La garantía cubre la reparación o, en su caso, la sustitución del componente afectado, sin costo para el cliente dentro de los términos aquí señalados.
4. Si el equipo pudo haber causado un incidente que afectó a un paciente o usuario, repórtelo de inmediato: se atiende con prioridad independientemente de la vigencia.</div>

    <table class="qr">
        <tr>
            <td class="img"><img src="{{ \App\Support\CodigoQr::dataUri($g->urlPublica(), 82) }}" alt="Código QR"></td>
            <td class="txt"><b>Consulte su garantía y reporte fallas</b>Escanee el código para ver la vigencia, descargar esta póliza y dar seguimiento a sus reportes.</td>
        </tr>
    </table>

    <table class="firma">
        <tr>
            <td><div class="linea">{{ $emp['nombre'] }}</div></td>
            <td><div class="linea">{{ $cliente }}</div></td>
        </tr>
    </table>
@endsection
