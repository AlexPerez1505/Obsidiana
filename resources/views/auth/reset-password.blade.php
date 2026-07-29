@extends('layouts.app')
@section('title', 'Restablecer contraseña')

@section('content')
    <h1>Nueva contraseña</h1>
    <p class="sub">Ingresa el código que te enviamos y tu nueva contraseña.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
        @error('email') <p class="err">{{ $message }}</p> @enderror

        <label for="code">Código de 6 dígitos</label>
        <input id="code" class="code-input" type="text" name="code" inputmode="numeric"
               maxlength="6" pattern="[0-9]*" autocomplete="one-time-code" required autofocus>
        @error('code') <p class="err">{{ $message }}</p> @enderror

        <label for="password">Nueva contraseña</label>
        <input id="password" type="password" name="password" required>
        @error('password') <p class="err">{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmar nueva contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <button type="submit" class="btn">Restablecer contraseña</button>
    </form>

    <p class="foot"><a class="link" href="{{ route('login') }}">Volver a iniciar sesión</a></p>
@endsection
