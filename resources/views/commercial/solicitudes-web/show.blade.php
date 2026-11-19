@extends('layouts.dashboard')

@php($s = $solicitud)

@section('title', 'Solicitud '.$s->folio)
@section('page-title', 'Solicitud '.$s->folio)
@section('page-sub', 'Recibida desde la tienda en línea el '.$s->created_at->format('d/m/Y \a \l\a\s H:i'))

@section('content')
    <x-ui.page-header :title="$s->nombre" :back="route('commercial.solicitudes-web.index')">
        <span class="badge {{ $s->estadoBadge() }}">{{ $s->estadoTexto() }}</span>
        <a class="btn btn--ghost" target="_blank" rel="noopener"
           href="https://wa.me/{{ $s->whatsapp() }}?text={{ rawurlencode('Hola '.$s->nombre.', te escribimos de '.config('medibuy.empresa.nombre').' por tu solicitud '.$s->folio.'.') }}">
            WhatsApp
        </a>
        @can('cotizaciones.crear')
            <a class="btn" href="{{ route('commercial.cotizaciones.create') }}">Hacer cotización</a>
        @endcan
    </x-ui.page-header>

    <div class="sw-layout">
        <div class="card">
            <div class="card-head"><h3>Datos del cliente</h3></div>
            <dl class="sw-dl">
                <div><dt>Nombre</dt><dd>{{ $s->nombre }}</dd></div>
                <div><dt>Empresa / institución</dt><dd>{{ $s->empresa ?: '—' }}</dd></div>
                <div><dt>Teléfono</dt><dd>{{ $s->telefono }}</dd></div>
                <div><dt>Correo</dt><dd>{{ $s->correo ?: '—' }}</dd></div>
                <div><dt>Ciudad</dt><dd>{{ $s->ciudad ?: '—' }}</dd></div>
                <div><dt>Especialidad</dt><dd>{{ $s->especialidad ?: '—' }}</dd></div>
                <div>
                    <dt>En Clientes</dt>
                    <dd>
                        @if ($s->cliente)
                            <a class="tbl-link" href="{{ route('commercial.clientes.show', $s->cliente) }}">
                                {{ trim($s->cliente->nombre.' '.$s->cliente->apellido) }}
                            </a>
                        @else
                            No está registrado
                        @endif
                    </dd>
                </div>
            </dl>
            @if ($s->mensaje && ! $s->esContacto())
                <div class="sw-mensaje">
                    <div class="campo-nota">Mensaje</div>
                    <p>{{ $s->mensaje }}</p>
                </div>
            @endif
        </div>

        @if ($s->esContacto())
        <div class="card">
            <div class="card-head"><h3>Mensaje de contacto</h3><span class="right"><span class="badge badge--info">{{ $s->motivoTexto() }}</span></span></div>
            <p style="margin:0; white-space:pre-line;">{{ $s->mensaje }}</p>
        </div>
        @else
        <div class="card" style="padding:0;">
            <div class="card-head"><h3>Equipos solicitados</h3><span class="right campo-nota">{{ $s->totalPiezas() }} pz</span></div>
            <table class="sw-table">
                <thead><tr><th>Equipo</th><th>Marca / modelo</th><th>Origen</th><th style="text-align:right;">Cant.</th></tr></thead>
                <tbody>
                    @foreach ($s->articulos as $a)
                        <tr>
                            <td>{{ $a['nombre'] }}</td>
                            <td>{{ trim(($a['marca'] ?? '').' '.($a['modelo'] ?? '')) ?: '—' }}</td>
                            <td>
                                @if ($a['disponible'])
                                    <span class="badge badge--ok">En almacén</span>
                                @else
                                    <span class="badge badge--plain">{{ $a['fuente'] === 'paquete' ? 'Paquete' : 'Sobre pedido' }}</span>
                                @endif
                            </td>
                            <td style="text-align:right;">{{ $a['cantidad'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="card">
            <div class="card-head"><h3>Seguimiento</h3></div>
            @if ($s->atendidaPor)
                <p class="campo-nota" style="margin-top:0;">
                    Último cambio por {{ $s->atendidaPor->name }} el {{ $s->atendida_en?->format('d/m/Y H:i') }}.
                </p>
            @endif
            @can('tienda.atender')
                <form method="POST" action="{{ route('commercial.solicitudes-web.update', $s) }}">
                    @csrf
                    @method('PUT')
                    <x-ui.form-group label="Estado" for="estado">
                        <select id="estado" name="estado">
                            @foreach (\App\Models\TiendaSolicitud::ESTADOS as $clave => $estado)
                                <option value="{{ $clave }}" @selected($s->estado === $clave)>{{ $estado['texto'] }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="Nota interna" for="nota_interna">
                        <textarea id="nota_interna" name="nota_interna" rows="4" placeholder="Qué se habló, cuándo volver a llamar…">{{ old('nota_interna', $s->nota_interna) }}</textarea>
                    </x-ui.form-group>
                    <button type="submit" class="btn">Guardar</button>
                </form>
            @else
                <p>{{ $s->nota_interna ?: 'Sin notas.' }}</p>
            @endcan
        </div>
    </div>

    <style>
        .sw-layout { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; align-items:start; }
        .sw-dl { display:grid; gap:10px; margin:0; }
        .sw-dl > div { display:flex; justify-content:space-between; gap:12px; border-bottom:1px solid var(--border); padding-bottom:8px; }
        .sw-dl dt { color:var(--muted); font-size:13px; }
        .sw-dl dd { margin:0; text-align:right; font-weight:500; }
        .sw-mensaje { margin-top:14px; }
        .sw-mensaje p { margin:4px 0 0; white-space:pre-line; }
        .sw-table { width:100%; border-collapse:collapse; }
    </style>
@endsection
