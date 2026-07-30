@extends('layouts.dashboard')
@section('title', 'Permisos de '.$user->name)
@section('page-title', $user->name)
@section('page-sub', 'Asignar permisos')

@section('content')
    <div style="margin-bottom:16px;">
        <a class="link" href="{{ route('admin.users.show', $user) }}">← Volver al usuario</a>
    </div>

    <x-ui.card style="margin-bottom:18px;">
        <x-ui.section-title style="margin-top:0;">Permisos asignados</x-ui.section-title>
        <p class="muted" style="margin:0 0 14px;">Marca los permisos que este usuario debe tener.</p>

        @if ($permissions->isEmpty())
            <p class="muted">No hay permisos creados. <a class="link" href="{{ route('admin.permissions.create') }}">Crea uno primero</a>.</p>
        @else
            <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
                @csrf
                <div style="display:grid; gap:10px; margin-bottom:18px;">
                    @foreach ($permissions as $p)
                        <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; background:var(--surface-2); cursor:pointer;">
                            <input type="checkbox" name="permissions[]" value="{{ $p->id }}" {{ in_array($p->id, $userPermissions) ? 'checked' : '' }}>
                            <span style="font-weight:600;">{{ $p->label }}</span>
                            <code style="font-size:12px; color:var(--muted); margin-left:auto;">{{ $p->name }}</code>
                        </label>
                    @endforeach
                </div>
                <x-ui.button type="submit">Guardar permisos</x-ui.button>
            </form>
        @endif
    </x-ui.card>
@endsection
