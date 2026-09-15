@extends('structure.commercial_management.layout')

@section('title', $campana->nombre)
@section('page-title', $campana->nombre)

@section('commercial_content')
    <div class="content-actions" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <a href="{{ route('commercial.promociones.campanas.index') }}" class="btn btn--ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Regresar
        </a>
        <span class="badge {{ $campana->estado === 'completada' ? 'badge--ok' : '' }}">{{ $campana->estadoLabel() }}</span>
    </div>

    @if (session('status'))
        <div class="card" style="margin:14px 0; padding:12px 16px; border-color:var(--primary);">{{ session('status') }}</div>
    @endif
    @error('campana')
        <div class="card" style="margin:14px 0; padding:12px 16px; border-color:var(--danger); color:var(--danger);">{{ $message }}</div>
    @enderror

    <x-ui.card style="margin:14px 0 18px;">
        <x-ui.section-title style="margin:0 0 12px;">Mensaje</x-ui.section-title>
        <p style="margin:0; white-space:pre-wrap;">{{ $campana->mensaje }}</p>

        @if ($campana->filtros)
            <p class="muted" style="margin:14px 0 0; font-size:13px;">
                Filtro de audiencia:
                {{ collect($campana->filtros)->map(fn ($v, $k) => "$k = $v")->implode(', ') ?: 'ninguno (todos los confirmados)' }}
            </p>
        @endif
    </x-ui.card>

    @if ($campana->puedeLanzarse())
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 6px;">Lista para lanzar</x-ui.section-title>
            <p class="muted" style="margin:0 0 14px; font-size:13.5px;">
                Con el filtro de arriba, esta campaña le llegaría hoy a <strong>{{ $audienciaCalculada }} cliente(s)</strong> confirmado(s).
                Al lanzarla, el mensaje se encola y se manda respetando el ritmo (1 por segundo), no de golpe.
            </p>
            <div style="display:flex; gap:10px;">
                <form method="POST" action="{{ route('commercial.promociones.campanas.lanzar', $campana) }}" onsubmit="return confirm('¿Lanzar esta campaña a {{ $audienciaCalculada }} cliente(s)? No se puede deshacer.');">
                    @csrf
                    <x-ui.button type="submit">Lanzar campaña</x-ui.button>
                </form>
                <form method="POST" action="{{ route('commercial.promociones.campanas.cancelar', $campana) }}" onsubmit="return confirm('¿Cancelar esta campaña?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--ghost" style="color:var(--danger);">Cancelar campaña</button>
                </form>
            </div>
        </x-ui.card>
    @else
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 12px;">Resultados</x-ui.section-title>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
                <div><div class="stat-num">{{ $resumen['total'] }}</div><div class="stat-lbl">Total</div></div>
                <div><div class="stat-num">{{ $resumen['pendiente'] }}</div><div class="stat-lbl">Pendientes</div></div>
                <div><div class="stat-num">{{ $resumen['enviado'] + $resumen['entregado'] + $resumen['leido'] }}</div><div class="stat-lbl">Enviados</div></div>
                <div><div class="stat-num" style="color:var(--danger);">{{ $resumen['fallo'] }}</div><div class="stat-lbl">Fallidos</div></div>
                <div><div class="stat-num">{{ $resumen['excluido'] }}</div><div class="stat-lbl">Excluidos</div></div>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card style="overflow-x:auto; padding:0;">
        <table class="cl-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Enviado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($destinatarios as $destinatario)
                    <tr>
                        <td>{{ trim($destinatario->cliente->nombre.' '.$destinatario->cliente->apellido) }}</td>
                        <td>
                            <span class="badge {{ in_array($destinatario->estado, ['enviado', 'entregado', 'leido']) ? 'badge--ok' : ($destinatario->estado === 'fallo' ? 'badge--danger' : '') }}">
                                {{ $destinatario->estadoLabel() }}
                            </span>
                        </td>
                        <td>{{ $destinatario->enviado_en?->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="muted" style="font-size:12.5px;">{{ $destinatario->error ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <h3>Todavía no hay destinatarios</h3>
                                <p>Se generan al lanzar la campaña.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card>

    @include('partials._paginacion', ['paginator' => $destinatarios])
@endsection
