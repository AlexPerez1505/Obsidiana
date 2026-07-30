@extends('layouts.dashboard')
@section('title', 'Usuarios')
@section('page-title', 'Usuarios')
@section('page-sub', 'Administra las cuentas del sistema')

@php
    $pending = $users->where('status', \App\Models\User::STATUS_PENDING);
@endphp

@section('content')
    <div class="grid stat-row" style="margin-bottom:18px;">
        <x-ui.stat-card
            :value="$users->count()"
            label="Usuarios registrados"
            color="blue"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$pending->count()"
            label="Pendientes de aprobar"
            color="orange"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    <x-ui.card>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Correo verif.</th>
                        <th>Acceso</th>
                        <th>Rol</th>
                        <th>Conex.</th>
                        <th>Sesiones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><a class="link" href="{{ route('admin.users.show', $u) }}">{{ $u->name }}</a></td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <x-ui.badge :variant="$u->hasVerifiedEmail() ? 'ok' : 'warn'">
                                    {{ $u->hasVerifiedEmail() ? 'Sí' : 'No' }}
                                </x-ui.badge>
                            </td>
                            <td>
                                @if ($u->isApproved())
                                    <x-ui.badge variant="ok">Con acceso</x-ui.badge>
                                @elseif ($u->isBanned())
                                    <x-ui.badge variant="danger">Baneado</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warn">Pendiente</x-ui.badge>
                                @endif
                            </td>
                            <td>
                                @if ($u->is_admin)
                                    <x-ui.badge variant="info">Admin</x-ui.badge>
                                @else
                                    <span class="muted">Usuario</span>
                                @endif
                            </td>
                            <td>{{ $u->login_logs_count }}</td>
                            <td><strong>{{ $activeCounts[$u->id] ?? 0 }}</strong></td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    @if ($u->isPending())
                                        <form method="POST" action="{{ route('admin.users.approve', $u) }}">
                                            @csrf
                                            <button class="badge badge--ok" style="border:none;cursor:pointer;">✓ Aprobar</button>
                                        </form>
                                    @endif

                                    @if ($u->isBanned())
                                        <form method="POST" action="{{ route('admin.users.unban', $u) }}">
                                            @csrf
                                            <button class="badge badge--ok" style="border:none;cursor:pointer;">Reactivar</button>
                                        </form>
                                    @elseif (! $u->is_admin)
                                        <form method="POST" action="{{ route('admin.users.ban', $u) }}">
                                            @csrf
                                            <button class="badge badge--danger" style="border:none;cursor:pointer;">Banear</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
