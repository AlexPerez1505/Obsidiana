@extends('layouts.dashboard')
@section('title', $user->name)
@section('page-title', $user->name)
@section('page-sub', $user->email)

@section('content')
    <div style="margin-bottom:16px;">
        <a class="link" href="{{ route('admin.users.index') }}">← Volver a usuarios</a>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <p style="margin-top:0;">
            @if ($user->hasVerifiedEmail())
                <span class="badge badge--ok">Correo verificado</span>
            @else
                <span class="badge badge--warn">Correo sin verificar</span>
            @endif
            @if ($user->isApproved())
                <span class="badge badge--ok">Con acceso</span>
            @elseif ($user->isBanned())
                <span class="badge badge--danger">Baneado</span>
            @else
                <span class="badge badge--warn">Pendiente de aprobación</span>
            @endif
            @if ($user->is_admin)
                <span class="badge badge--info">Administrador</span>
            @endif
            <span class="badge" style="background:var(--surface-2);color:var(--muted);">{{ $activeCount }} sesión(es) activa(s)</span>
            <span class="muted" style="margin-left:6px;">Registrado el {{ $user->created_at?->format('d/m/Y H:i') }}</span>
        </p>

        @if ($user->isBanned() && $user->banned_reason)
            <div class="alert alert--err">Motivo del baneo: {{ $user->banned_reason }}</div>
        @endif

        {{-- Control de acceso --}}
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; align-items:flex-end;">
            @if ($user->isPending())
                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                    @csrf
                    <button type="submit" class="btn">✓ Aprobar acceso</button>
                </form>
            @endif

            @if ($user->isBanned())
                <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                    @csrf
                    <button type="submit" class="btn">Reactivar cuenta</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.ban', $user) }}"
                      style="display:flex; gap:8px; align-items:flex-end;">
                    @csrf
                    <div>
                        <label for="banned_reason" style="margin-top:0;">Motivo (opcional)</label>
                        <input id="banned_reason" type="text" name="banned_reason" placeholder="Ej. incumplió las reglas" style="width:240px;">
                    </div>
                    <button type="submit" class="btn btn--danger">Banear / desactivar</button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.users.toggleAdmin', $user) }}">
                @csrf
                <button type="submit" class="btn btn--ghost">{{ $user->is_admin ? 'Quitar admin' : 'Hacer admin' }}</button>
            </form>
        </div>
    </div>

    {{-- Historial de conexiones --}}
    <div class="card">
        <h2 class="section-title">Historial de conexiones</h2>
        <p class="muted" style="margin:0 0 12px;">Últimos inicios de sesión de este usuario.</p>
        @if ($logs->isEmpty())
            <p class="muted">Este usuario aún no tiene conexiones registradas.</p>
        @else
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Fecha</th><th>IP</th><th>Ubicación</th><th>Navegador</th><th>Sistema</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->logged_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                                <td>{{ $log->location ?? '—' }}</td>
                                <td>{{ $log->browser ?? '—' }}</td>
                                <td>{{ $log->platform ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
