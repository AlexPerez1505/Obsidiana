@extends('layouts.dashboard')

@section('title', 'Equipos')
@section('page-title', 'Equipos')

@section('content')
    <div class="content-actions">
        <a href="{{ route('inventory.equipos.create') }}" class="btn">
            <x-gravityui-plus width="15" height="15" />
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
                    <th>Precio</th>
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
                                    <x-gravityui-picture width="18" height="18" />
                                </div>
                            @endif
                        </td>
                        <td style="font-weight:600;">{{ $equipo->tipo }}</td>
                        <td>{{ $equipo->modelo ?? '—' }}</td>
                        <td>{{ $equipo->marca ?? '—' }}</td>
                        <td>${{ number_format($equipo->precio, 2) }}</td>
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
