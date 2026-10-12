@extends('structure.commercial_management.layout')

@section('title', 'Campañas de promoción')
@section('page-title', 'Campañas de promoción')

@section('commercial_content')
    <div class="content-actions" style="display:flex; gap:10px;">
        <a href="{{ route('commercial.promociones.index') }}" class="btn btn--ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Consentimiento de clientes
        </a>
        <a href="{{ route('commercial.promociones.campanas.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva campaña
        </a>
    </div>

    @if (session('status'))
        <div class="card" style="margin:14px 0; padding:12px 16px; border-color:var(--primary);">{{ session('status') }}</div>
    @endif

    <div class="card" style="overflow-x:auto; padding:0; margin-top:14px;">
        <table class="cl-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Campaña</th>
                    <th>Estado</th>
                    <th>Envíos</th>
                    <th>Creada por</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campanas as $campana)
                    @php($resumen = $campana->resumenEnvios())
                    <tr>
                        <td>
                            <a href="{{ route('commercial.promociones.campanas.show', $campana) }}" style="color:inherit; font-weight:600; text-decoration:none;">
                                {{ $campana->nombre }}
                            </a>
                            <div class="muted" style="font-size:12.5px; max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $campana->mensaje }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $campana->estado === 'completada' ? 'badge--ok' : '' }}">{{ $campana->estadoLabel() }}</span>
                        </td>
                        <td>
                            @if ($resumen['total'] > 0)
                                {{ $resumen['enviado'] + $resumen['entregado'] + $resumen['leido'] }}/{{ $resumen['total'] }} enviados
                                @if ($resumen['fallo'] > 0)
                                    <span style="color:var(--danger);">· {{ $resumen['fallo'] }} fallidos</span>
                                @endif
                            @else
                                <span class="muted">Aún no se ha lanzado</span>
                            @endif
                        </td>
                        <td>{{ $campana->creador?->name ?: '—' }}</td>
                        <td>{{ $campana->created_at->format('d/m/Y') }}</td>
                        <td style="text-align:right;">
                            <a href="{{ route('commercial.promociones.campanas.show', $campana) }}" class="btn btn--ghost" style="padding:5px 10px; font-size:12.5px;">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <h3>Aún no hay campañas</h3>
                                <p>Crea la primera para empezar a mandar promociones.</p>
                                <a href="{{ route('commercial.promociones.campanas.create') }}" class="btn">Nueva campaña</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials._paginacion', ['paginator' => $campanas])
@endsection
