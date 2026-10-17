@extends('layouts.dashboard')

@section('title', 'Reclamaciones')
@section('page-title', 'Reclamaciones de garantía')
@section('page-sub', 'Del reporte del cliente hasta el cierre')

@section('content')
    @include('structure.garantias._estilos')

    @php
        $url = fn (array $cambio) => route('garantias.reclamaciones.index', array_filter(array_merge($filtros, $cambio), fn ($v) => $v !== null && $v !== ''));
        $pestanas = [
            'abiertas' => ['Abiertas', $conteos['abiertas']],
            'mias' => ['Mías', $conteos['mias']],
            'atrasadas' => ['Atrasadas', $conteos['atrasadas']],
            'seguridad' => ['Seguridad', $conteos['seguridad']],
            'cerradas' => ['Cerradas', null],
            'todas' => ['Todas', null],
        ];
    @endphp

    @can('garantias.gestionar')
        <div class="content-actions"><a href="{{ route('garantias.reclamaciones.create') }}" class="btn">Registrar reclamación</a></div>
    @endcan

    <div class="gt-tabs">
        @foreach ($pestanas as $clave => [$texto, $n])
            <a href="{{ $url(['vista' => $clave, 'page' => null]) }}" class="gt-tab {{ $vista === $clave ? 'on' : '' }}">{{ $texto }} @if ($n)<span class="n">{{ $n }}</span>@endif</a>
        @endforeach
    </div>

    <form method="GET" class="f-toolbar">
        <input type="hidden" name="vista" value="{{ $vista }}">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Folio, serie, equipo, cliente o falla" autocomplete="off">
        </div>
        <select name="etapa" onchange="this.form.submit()" style="width:auto;">
            <option value="">Todas las etapas</option>
            @foreach (\App\Models\GarantiaReclamacion::ETAPAS as $k => [$t])<option value="{{ $k }}" @selected(($filtros['etapa'] ?? '') === $k)>{{ $t }}</option>@endforeach
        </select>
        <select name="responsable" onchange="this.form.submit()" style="width:auto;">
            <option value="">Cualquier responsable</option>
            @foreach ($responsables as $u)<option value="{{ $u->id }}" @selected(($filtros['responsable'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach
        </select>
        <button class="btn btn--ghost" type="submit">Buscar</button>
    </form>

    <div class="card" style="padding:0;">
        <div class="tbl-scroll">
            <table class="gt-tabla">
                <thead><tr><th>Reclamación</th><th>Equipo</th><th>Cliente</th><th>Etapa</th><th>Dictamen</th><th>Prioridad</th><th>Responsable</th></tr></thead>
                <tbody>
                    @forelse ($reclamaciones as $r)
                        <tr style="cursor:pointer;" onclick="location.href='{{ route('garantias.reclamaciones.show', $r) }}'">
                            <td>
                                <a class="tbl-link" href="{{ route('garantias.reclamaciones.show', $r) }}"><b>{{ $r->folio }}</b></a>
                                <div class="s">{{ $r->recibida_en->format('d/m/Y H:i') }} · {{ $r->canalLabel() }}</div>
                            </td>
                            <td>{{ $r->equipoNombre() }}<div class="s">{{ $r->no_serie ?: 'Sin serie' }}</div></td>
                            <td>{{ $r->clienteNombre() }}</td>
                            <td style="white-space:nowrap;">
                                {{ $r->etapaNumero() }}/6 · {{ $r->etapaLabel() }}
                                @if ($r->atrasada())<div><span class="badge badge--danger">Atrasada</span></div>@endif
                            </td>
                            <td><span class="badge {{ $r->dictamenBadge() }}">{{ $r->dictamenLabel() }}</span></td>
                            <td>
                                <span class="badge {{ $r->prioridadBadge() }}">{{ $r->prioridadLabel() }}</span>
                                @if ($r->es_seguridad)<span class="badge badge--danger">Seguridad</span>@endif
                            </td>
                            <td>{{ $r->responsable?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state">
                            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                            <h3>No hay reclamaciones aquí</h3><p>Cuando un cliente reporte una falla, regístrala para darle seguimiento.</p>
                        </div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:14px;">{{ $reclamaciones->links() }}</div>
@endsection
