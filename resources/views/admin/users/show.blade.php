@extends('layouts.dashboard')
@section('title', $user->name)
@section('page-title', $user->name)
@section('page-sub', $user->email)

@section('content')
    <div style="margin-bottom:16px;">
        <a class="link" href="{{ route('admin.users.index') }}">← Volver a usuarios</a>
    </div>

    <x-ui.card style="margin-bottom:18px;">
        <p style="margin-top:0;">
            <x-ui.badge :variant="$user->hasVerifiedEmail() ? 'ok' : 'warn'">
                {{ $user->hasVerifiedEmail() ? 'Correo verificado' : 'Correo sin verificar' }}
            </x-ui.badge>

            @if ($user->isApproved())
                <x-ui.badge variant="ok">Con acceso</x-ui.badge>
            @elseif ($user->isBanned())
                <x-ui.badge variant="danger">Baneado</x-ui.badge>
            @else
                <x-ui.badge variant="warn">Pendiente de aprobación</x-ui.badge>
            @endif

            @if ($user->is_admin)
                <x-ui.badge variant="info">Administrador</x-ui.badge>
            @endif

            <x-ui.badge style="background:var(--surface-2);color:var(--muted);">
                {{ $activeCount }} sesión(es) activa(s)
            </x-ui.badge>

            <span class="muted" style="margin-left:6px;">
                Registrado el {{ $user->created_at?->format('d/m/Y H:i') }}
            </span>
        </p>

        @if ($user->isBanned() && $user->banned_reason)
            <x-ui.alert type="err">Motivo del baneo: {{ $user->banned_reason }}</x-ui.alert>
        @endif

        {{-- Control de acceso --}}
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; align-items:flex-end;">
            @if ($user->isPending())
                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                    @csrf
                    <x-ui.button>✓ Aprobar acceso</x-ui.button>
                </form>
            @endif

            @if ($user->isBanned())
                <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                    @csrf
                    <x-ui.button>Reactivar cuenta</x-ui.button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.ban', $user) }}"
                      style="display:flex; gap:8px; align-items:flex-end;">
                    @csrf
                    <div>
                        <label for="banned_reason" style="margin-top:0;">Motivo (opcional)</label>
                        <input id="banned_reason" type="text" name="banned_reason" placeholder="Ej. incumplió las reglas" style="width:240px;">
                    </div>
                    <x-ui.button variant="danger">Banear / desactivar</x-ui.button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.users.toggleAdmin', $user) }}">
                @csrf
                <x-ui.button variant="ghost">
                    {{ $user->is_admin ? 'Quitar admin' : 'Hacer admin' }}
                </x-ui.button>
            </form>

            <a href="{{ route('admin.users.permissions', $user) }}" style="padding:8px 14px; background:var(--primary-soft); color:var(--primary); border-radius:8px; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; align-self:flex-end;">
                Administrar permisos
            </a>
        </div>
    </x-ui.card>

    {{-- Historial de conexiones --}}
    <x-ui.card>
        <x-ui.section-title>Historial de conexiones</x-ui.section-title>
        <p class="muted" style="margin:0 0 12px;">Últimos inicios de sesión de este usuario.</p>

        @if ($logs->isEmpty())
            <x-logs.empty message="Este usuario aún no tiene conexiones registradas." />
        @else
            <x-logs.table :logs="$logs" />
        @endif
    </x-ui.card>
@endsection
