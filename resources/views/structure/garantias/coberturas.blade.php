@extends('layouts.dashboard')

@section('title', 'Coberturas')
@section('page-title', 'Pólizas y coberturas')
@section('page-sub', 'Las condiciones que se copian a cada garantía nueva')

@section('content')
    @include('structure.garantias._estilos')

    <div class="content-actions">
        <button type="button" class="btn" data-abrir="dlgCob_nueva">Nueva cobertura</button>
    </div>

    <div class="gt-aviso azul">
        <div>Los <b style="display:inline;">meses</b> de cada venta mandan sobre los de la cobertura. Al vender, el sistema elige la cobertura según la condición del equipo (nuevo o seminuevo). Editar una cobertura no cambia las pólizas que ya se emitieron.</div>
    </div>

    <div class="card gt-mb" style="padding:0;">
        <div class="tbl-scroll">
            <table class="gt-tabla">
                <thead><tr><th>Cobertura</th><th>Tipo</th><th>Plazo</th><th>Aplica a</th><th>Garantías</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @foreach ($coberturas as $c)
                        <tr>
                            <td><b>{{ $c->nombre }}</b><div class="s">{{ mb_strimwidth((string) $c->cubre, 0, 90, '…') }}</div></td>
                            <td>{{ $c->tipoLabel() }}</td>
                            <td class="gt-num">{{ $c->meses }} meses</td>
                            <td>{{ $c->aplicaLabel() }}</td>
                            <td class="gt-num">{{ $c->garantias_count }}</td>
                            <td><span class="badge {{ $c->activo ? 'badge--ok' : '' }}">{{ $c->activo ? 'Activa' : 'Inactiva' }}</span></td>
                            <td style="text-align:right;"><button type="button" class="tbl-link" style="border:0;background:none;cursor:pointer;" data-abrir="dlgCob_{{ $c->id }}">Editar</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="padding:0;">
        <div class="card-head"><h3>Parámetros del módulo</h3><span class="right gt-s">config/garantias.php</span></div>
        <dl class="gt-kv">
            <div><dt>"Próxima a vencer" a partir de</dt><dd>{{ $parametros['dias_por_vencer'] }} días</dd></div>
            <div><dt>Avisos de vencimiento</dt><dd>{{ implode(', ', $parametros['avisos']) }} días antes</dd></div>
            <div><dt>Gastos que necesitan aprobación</dt><dd>más de ${{ number_format($parametros['limite'], 2) }}</dd></div>
            <div><dt>Primera respuesta</dt><dd>{{ $parametros['respuesta'] }} horas</dd></div>
            @foreach ($parametros['resolucion'] as $p => $h)
                <div><dt>Resolver prioridad {{ \App\Models\GarantiaReclamacion::PRIORIDADES[$p][0] ?? $p }}</dt><dd>{{ $h }} horas</dd></div>
            @endforeach
        </dl>
    </div>

    @foreach ($coberturas->prepend(new \App\Models\GarantiaCobertura(['activo' => true, 'meses' => 12, 'tipo' => 'medibuy'])) as $c)
        @php $id = $c->exists ? $c->id : 'nueva'; @endphp
        <dialog id="dlgCob_{{ $id }}" class="gt-modal">
            <form method="POST" action="{{ $c->exists ? route('garantias.coberturas.update', $c) : route('garantias.coberturas.store') }}">
                @csrf @if ($c->exists) @method('PUT') @endif
                <div class="gt-modal-head"><div><h2>{{ $c->exists ? 'Editar cobertura' : 'Nueva cobertura' }}</h2></div>
                    <button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Nombre *" for="n_{{ $id }}"><input id="n_{{ $id }}" name="nombre" value="{{ $c->nombre }}" required maxlength="160"></x-ui.form-group>
                    <div class="gt-form-grid">
                        <x-ui.form-group label="Tipo" for="t_{{ $id }}">
                            <select id="t_{{ $id }}" name="tipo">@foreach (\App\Models\Garantia::TIPOS as $k => $t)<option value="{{ $k }}" @selected($c->tipo === $k)>{{ $t }}</option>@endforeach</select>
                        </x-ui.form-group>
                        <x-ui.form-group label="Meses" for="m_{{ $id }}"><input type="number" id="m_{{ $id }}" name="meses" min="1" max="120" value="{{ $c->meses }}" required></x-ui.form-group>
                        <x-ui.form-group label="Se usa al vender equipo" for="a_{{ $id }}">
                            <select id="a_{{ $id }}" name="aplica_a">
                                <option value="" @selected(! $c->aplica_a)>Cualquiera</option>
                                <option value="nuevo" @selected($c->aplica_a === 'nuevo')>Nuevo</option>
                                <option value="usado" @selected($c->aplica_a === 'usado')>Seminuevo o reacondicionado</option>
                            </select>
                        </x-ui.form-group>
                    </div>
                    <x-ui.form-group label="Qué cubre" for="c_{{ $id }}"><textarea id="c_{{ $id }}" name="cubre" rows="3">{{ $c->cubre }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Exclusiones" for="e_{{ $id }}"><textarea id="e_{{ $id }}" name="exclusiones" rows="3">{{ $c->exclusiones }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Condiciones y cómo reclamar" for="d_{{ $id }}"><textarea id="d_{{ $id }}" name="condiciones" rows="3">{{ $c->condiciones }}</textarea></x-ui.form-group>
                    <label class="gt-check"><input type="checkbox" name="activo" value="1" @checked($c->activo)> Activa</label>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button type="submit" class="btn">Guardar</button></div>
            </form>
        </dialog>
    @endforeach
@endsection
