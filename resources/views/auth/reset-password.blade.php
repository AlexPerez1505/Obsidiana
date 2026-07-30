@extends('layouts.app')
@section('title', 'Restablecer contraseña')

@section('content')
    <h1>Nueva contraseña</h1>
    <p class="sub">Ingresa el código que te enviamos y tu nueva contraseña.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <x-ui.form-group
            label="Correo electrónico"
            name="email"
            type="email"
            :value="old('email', $email)"
            :required="true"
        />

        <x-ui.form-group
            label="Código de 6 dígitos"
            name="code"
            type="text"
            inputClass="code-input"
            inputmode="numeric"
            maxlength="6"
            pattern="[0-9]*"
            autocomplete="one-time-code"
            :required="true"
            :autofocus="true"
        />

        <x-ui.form-group
            label="Nueva contraseña"
            name="password"
            type="password"
            :required="true"
        />

        <x-ui.form-group
            label="Confirmar nueva contraseña"
            name="password_confirmation"
            type="password"
            :required="true"
        />

        <x-ui.button>Restablecer contraseña</x-ui.button>
    </form>

    <p class="foot"><a class="link" href="{{ route('login') }}">Volver a iniciar sesión</a></p>
@endsection
