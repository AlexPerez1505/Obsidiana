@extends('layouts.dashboard')

@section('title', 'Equipos')

@section('content')
    <div class="card" style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px;height:48px;border-radius:14px;background:var(--primary-soft);color:var(--primary);display:flex;align-items:center;justify-content:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <div>
                <h2 class="page-title" style="margin:0;">Equipos <span class="badge badge--info">{{ $equipos->count() }}</span></h2>
                <p class="muted" style="margin:2px 0 0;">Catálogo de equipos y productos para cotizar</p>
            </div>
        </div>
        <a href="{{ route('inventory.equipos.create') }}" class="btn" style="display:inline-flex; align-items:center; gap:6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
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
