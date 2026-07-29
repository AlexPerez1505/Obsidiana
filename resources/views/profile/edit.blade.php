@extends('layouts.dashboard')
@section('title', 'Editar perfil')
@section('page-title', 'Editar perfil')
@section('page-sub', 'Actualiza tu información personal')

@section('content')
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); align-items:start;">
        {{-- Datos personales --}}
        <div class="card">
            <h2 class="section-title">Datos personales</h2>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <label for="name">Nombre</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <p class="err">{{ $message }}</p> @enderror

                <label for="email">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <p class="err">{{ $message }}</p> @enderror
                <p class="muted" style="margin-top:6px; font-size:13px;">
                    Si cambias el correo, tendrás que verificarlo de nuevo con un código.
                </p>

                <button type="submit" class="btn" style="margin-top:16px;">Guardar cambios</button>
            </form>
        </div>

        {{-- Cambiar contraseña --}}
        <div class="card">
            <h2 class="section-title">Cambiar contraseña</h2>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <label for="current_password">Contraseña actual</label>
                <input id="current_password" type="password" name="current_password" required>
                @error('current_password') <p class="err">{{ $message }}</p> @enderror

                <label for="new_password">Nueva contraseña</label>
                <input id="new_password" type="password" name="password" required>
                @error('password') <p class="err">{{ $message }}</p> @enderror

                <label for="password_confirmation">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>

                <button type="submit" class="btn" style="margin-top:16px;">Actualizar contraseña</button>
            </form>
        </div>
    </div>
@endsection
