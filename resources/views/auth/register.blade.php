@extends('layouts.app')
@section('title', 'Crear cuenta')

@section('content')
    <h1>Crear cuenta</h1>
    <p class="sub">Regístrate para empezar.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <x-ui.form-group
            label="Nombre"
            name="name"
            type="text"
            :value="old('name')"
            :required="true"
            :autofocus="true"
        />

        <x-ui.form-group
            label="Correo electrónico"
            name="email"
            type="email"
            :value="old('email')"
            :required="true"
        />

        <x-ui.form-group
            label="Contraseña"
            name="password"
            type="password"
            :required="true"
        />

        <x-ui.form-group
            label="Confirmar contraseña"
            name="password_confirmation"
            type="password"
            :required="true"
        />

        <x-ui.form-group label="Rol" for="role_id">
            <select id="role_id" name="role_id" required>
                <option value="" disabled selected>Selecciona tu rol</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->label }}
                    </option>
                @endforeach
            </select>
        </x-ui.form-group>

        <x-ui.button>Crear cuenta</x-ui.button>
    </form>

    <p class="foot">¿Ya tienes cuenta? <a class="link" href="{{ route('login') }}">Inicia sesión</a></p>
@endsection
