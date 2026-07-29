@extends('layouts.app')
@section('title', 'Crear cuenta')

@section('content')
    <h1>Crear cuenta</h1>
    <p class="sub">Regístrate para empezar.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Nombre</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
        @error('name') <p class="err">{{ $message }}</p> @enderror

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        @error('email') <p class="err">{{ $message }}</p> @enderror

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required>
        @error('password') <p class="err">{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <button type="submit" class="btn">Crear cuenta</button>
    </form>

    <p class="foot">¿Ya tienes cuenta? <a class="link" href="{{ route('login') }}">Inicia sesión</a></p>
@endsection
