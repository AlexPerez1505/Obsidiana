@extends('layouts.dashboard')

@section('title', 'Equipos')
@section('page-title', 'Equipos')

@section('content')
    <div class="content-actions">
        <a href="{{ route('inventory.equipos.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo equipo
        </a>
    </div>

    <div class="card" style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Equipo</th>
                    <th>Modelo</th>
                    <th>Marca</th>
                    {{-- La columna no se dibuja para quien no ve precios. --}}
                    @if (\App\Support\PrecioVisible::para())
                        <th>Precio de venta</th>
                    @endif
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipos as $equipo)
                    <tr>
                        <td>
                            @if ($equipo->imagen)
                                <img src="{{ asset('storage/'.$equipo->imagen) }}" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                            @else
                                <div style="width:44px;height:44px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                            @endif
                        </td>
                        <td style="font-weight:600;">{{ $equipo->tipo }}</td>
                        <td>{{ $equipo->modelo ?? '—' }}</td>
                        <td>{{ $equipo->marca ?? '—' }}</td>
                        @if (\App\Support\PrecioVisible::para())
                            <td>{{ $equipo->precio === null ? 'Sin precio definido' : '$'.number_format($equipo->precio, 2) }}</td>
                        @endif
                        <td>
                            <span class="badge {{ $equipo->activo ? 'badge--ok' : 'badge--danger' }}">
                                {{ $equipo->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                            No hay equipos registrados. <a href="{{ route('inventory.equipos.create') }}" class="link">Registrar el primero</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
