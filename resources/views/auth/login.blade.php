@extends('layouts.app')
@section('title', 'Iniciar sesión')

@section('content')
    <h1>Iniciar sesión</h1>
    <p class="sub">Bienvenido de nuevo.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <x-ui.form-group
            label="Correo electrónico"
            name="email"
            type="email"
            :value="old('email')"
            :required="true"
            :autofocus="true"
        />

        <x-ui.form-group
            label="Contraseña"
            name="password"
            type="password"
            :required="true"
        />

        <div class="row" style="margin-top:14px;">
            <label class="check" style="margin:0;">
                <input type="checkbox" name="remember"> Recordarme
            </label>
            <a class="link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        </div>

        <x-ui.button>Entrar</x-ui.button>
    </form>

    <p class="foot">¿No tienes cuenta? <a class="link" href="{{ route('register') }}">Regístrate</a></p>
@endsection
