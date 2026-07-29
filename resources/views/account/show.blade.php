@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('page-sub', 'Resumen general de tu cuenta')

@section('content')
    {{-- Tarjetas de resumen --}}
    <div class="grid stat-row" style="margin-bottom:18px;">
        <div class="card stat">
            <div class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
            </div>
            <div>
                <div class="stat-num">{{ $activeSessions->count() }}</div>
                <div class="stat-lbl">Sesiones activas</div>
            </div>
        </div>

        <div class="card stat">
            <div class="stat-ico {{ $user->hasVerifiedEmail() ? 'green' : 'orange' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg>
            </div>
            <div>
                <div class="stat-num" style="font-size:20px;">{{ $user->hasVerifiedEmail() ? 'Verificado' : 'Pendiente' }}</div>
                <div class="stat-lbl">Correo</div>
            </div>
        </div>

        <div class="card stat">
            <div class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5l-8-3z"/></svg>
            </div>
            <div>
                <div class="stat-num" style="font-size:20px;">
                    {{ $user->isAdmin() ? 'Administrador' : ($user->isApproved() ? 'Con acceso' : $user->statusLabel()) }}
                </div>
                <div class="stat-lbl">Estado de la cuenta</div>
            </div>
        </div>
    </div>

    {{-- Sesiones activas --}}
    <div class="card" style="margin-bottom:18px;">
        <h2 class="section-title">Sesiones activas <span class="badge badge--info" style="margin-left:6px;">{{ $activeSessions->count() }}</span></h2>
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
                            <td>@if ($s->is_current)<span class="badge badge--ok">Esta sesión</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($activeSessions->count() > 1)
            <form method="POST" action="{{ route('account.sessions.destroyOthers') }}" style="margin-top:14px; max-width:360px;">
                @csrf
                <label for="sess_password">Cerrar las demás sesiones (confirma tu contraseña)</label>
                <input id="sess_password" type="password" name="password" required>
                @error('password') <p class="err">{{ $message }}</p> @enderror
                <button type="submit" class="btn btn--ghost" style="margin-top:12px;">Cerrar otras sesiones</button>
            </form>
        @endif
    </div>

    {{-- Historial de conexiones --}}
    <div class="card" style="margin-bottom:18px;">
        <h2 class="section-title">Historial de conexiones</h2>
        <p class="muted" style="margin:0 0 12px;">Últimos inicios de sesión registrados en tu cuenta.</p>

        @if ($logs->isEmpty())
            <p class="muted">Aún no hay registros.</p>
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

    {{-- Cerrar cuenta --}}
    <div class="card">
        <h2 class="section-title" style="color:var(--danger);">Cerrar cuenta</h2>
        <p class="muted" style="margin:0;">Esta acción elimina tu cuenta y todos tus datos de forma permanente.</p>

        <div class="danger-box">
            <form method="POST" action="{{ route('account.destroy') }}" style="max-width:360px;">
                @csrf
                @method('DELETE')
                <label for="del_password">Confirma tu contraseña</label>
                <input id="del_password" type="password" name="password" required>
                <label for="confirm">Escribe <strong>ELIMINAR</strong> para confirmar</label>
                <input id="confirm" type="text" name="confirm" placeholder="ELIMINAR" required>
                <button type="submit" class="btn btn--danger" style="margin-top:14px;">
                    Eliminar mi cuenta
                </button>
            </form>
        </div>
    </div>
@endsection
