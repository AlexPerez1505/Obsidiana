@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('page-sub', 'Resumen general de tu cuenta')

@php
    $emailStatus = $user->hasVerifiedEmail() ? 'Verificado' : 'Pendiente';
    $emailColor  = $user->hasVerifiedEmail() ? 'green' : 'orange';
    $accountText = $user->isAdmin() ? 'Administrador' : ($user->isApproved() ? 'Con acceso' : $user->statusLabel());
@endphp

@section('content')
    {{-- Tarjetas de resumen --}}
    <div class="grid stat-row" style="margin-bottom:18px;">
        <x-ui.stat-card
            :value="$activeSessions->count()"
            label="Sesiones activas"
            color="blue"
            value-style=""
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$emailStatus"
            label="Correo"
            :color="$emailColor"
            value-style="font-size:20px;"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$accountText"
            label="Estado de la cuenta"
            color="blue"
            value-style="font-size:20px;"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5l-8-3z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    @if (auth()->user()->hasPermission('ver_estadisticas'))
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0;">Estadísticas avanzadas</x-ui.section-title>
            <p class="muted" style="margin:0 0 12px;">Este componente solo se muestra si tienes el permiso <code>ver_estadisticas</code>.</p>
            <p style="margin:0; font-size:28px; font-weight:700; color:var(--green);">1,240</p>
            <p class="muted" style="margin:0;">Visitas hoy</p>
        </x-ui.card>
    @endif

    {{-- Sesiones activas --}}
    <x-ui.card style="margin-bottom:18px;">
        <x-ui.section-title style="margin:0;">
            Sesiones activas
            <x-ui.badge variant="info" style="margin-left:6px;">{{ $activeSessions->count() }}</x-ui.badge>
        </x-ui.section-title>
        <p class="muted" style="margin:0 0 12px;">Dispositivos donde tu cuenta está conectada ahora mismo.</p>

        <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>Dispositivo</th><th>IP</th><th>Última actividad</th><th></th></tr></thead>
                <tbody>
                    @foreach ($activeSessions as $s)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($s->user_agent, 45) ?: '—' }}</td>
                            <td>{{ $s->ip_address ?? '—' }}</td>
                            <td>{{ $s->last_activity->diffForHumans() }}</td>
                            <td>
                                @if ($s->is_current)
                                    <x-ui.badge variant="ok">Esta sesión</x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($activeSessions->count() > 1)
            <form method="POST" action="{{ route('account.sessions.destroyOthers') }}" style="margin-top:14px; max-width:360px;">
                @csrf
                <x-ui.form-group
                    label="Cerrar las demás sesiones (confirma tu contraseña)"
                    name="password"
                    type="password"
                    :required="true"
                />
                <x-ui.button variant="ghost" style="margin-top:12px;">Cerrar otras sesiones</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    {{-- Historial de conexiones --}}
    <x-ui.card style="margin-bottom:18px;">
        <x-ui.section-title>Historial de conexiones</x-ui.section-title>
        <p class="muted" style="margin:0 0 12px;">Últimos inicios de sesión registrados en tu cuenta.</p>

        @if ($logs->isEmpty())
            <x-logs.empty message="Aún no hay registros." />
        @else
            <x-logs.table :logs="$logs" />
        @endif
    </x-ui.card>

    {{-- Cerrar cuenta --}}
    <x-ui.card>
        <x-ui.section-title style="color:var(--danger);">Cerrar cuenta</x-ui.section-title>
        <p class="muted" style="margin:0;">Esta acción elimina tu cuenta y todos tus datos de forma permanente.</p>

        <div class="danger-box">
            <form method="POST" action="{{ route('account.destroy') }}" style="max-width:360px;">
                @csrf
                @method('DELETE')

                <x-ui.form-group
                    label="Confirma tu contraseña"
                    name="password"
                    type="password"
                    :required="true"
                />

                <x-ui.form-group
                    label="Escribe ELIMINAR para confirmar"
                    name="confirm"
                    type="text"
                    placeholder="ELIMINAR"
                    :required="true"
                />

                <x-ui.button variant="danger" style="margin-top:14px;">Eliminar mi cuenta</x-ui.button>
            </form>
        </div>
    </x-ui.card>
@endsection
