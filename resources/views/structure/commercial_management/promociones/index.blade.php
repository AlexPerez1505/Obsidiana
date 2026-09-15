@extends('layouts.dashboard')
@section('title', 'Promociones')
@section('page-title', 'Promociones')
@section('page-sub', 'Envía promociones por WhatsApp a los clientes que aceptaron recibirlas')

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('commercial.promociones.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nueva promoción
        </a>
    </div>

    @if(session('status'))
        <div class="badge badge--info" style="display:block; padding:12px 16px; margin-bottom:18px; font-size:13.5px;">{{ session('status') }}</div>
    @endif

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Promoción</th>
                        <th>Segmento</th>
                        <th>Estado</th>
                        <th>Enviados</th>
                        <th>Fallidos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promociones as $promocion)
                        @php
                            $enviados = $promocion->envios->where('estado', 'enviado')->count();
                            $fallidos = $promocion->envios->whereIn('estado', ['fallido', 'sin_destino'])->count();
                            $estadoColores = [
                                'borrador' => 'badge--info',
                                'enviando' => 'badge--info',
                                'completada' => '',
                                'fallida' => 'badge--danger',
                            ];
                        @endphp
                        <tr>
                            <td style="font-weight:700;">{{ $promocion->nombre }}</td>
                            <td style="font-size:13px; color:var(--muted);">
                                {{ $promocion->categoria?->nombre ?? 'Todos los clientes' }}
                            </td>
                            <td>
                                <span class="badge {{ $estadoColores[$promocion->estado] ?? '' }}">{{ ucfirst($promocion->estado) }}</span>
                            </td>
                            <td>{{ $enviados }}</td>
                            <td>{{ $fallidos }}</td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <a href="{{ route('commercial.promociones.show', $promocion) }}" class="btn btn--ghost" style="padding:6px 12px; font-size:13px; text-decoration:none;">Ver</a>
                                    <form method="POST" action="{{ route('commercial.promociones.destroy', $promocion) }}" onsubmit="return confirm('¿Eliminar esta promoción?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger" style="padding:6px 12px; font-size:13px;">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay promociones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
