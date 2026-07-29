@extends('layouts.app')
@section('title', 'Iniciar sesión')

@section('content')
    <h1>Iniciar sesión</h1>
    <p class="sub">Bienvenido de nuevo.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <p class="err">{{ $message }}</p> @enderror

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required>
        @error('password') <p class="err">{{ $message }}</p> @enderror

        <div class="row" style="margin-top:14px;">
            <label class="check" style="margin:0;">
                <input type="checkbox" name="remember"> Recordarme
            </label>
            <a class="link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn">Entrar</button>
    </form>

    <p class="foot">¿No tienes cuenta? <a class="link" href="{{ route('register') }}">Regístrate</a></p>
@endsection
