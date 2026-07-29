@extends('layouts.app')
@section('title', 'Recuperar contraseña')

@section('content')
    <h1>Recuperar contraseña</h1>
    <p class="sub">Escribe tu correo y te enviaremos un código para restablecerla.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <p class="err">{{ $message }}</p> @enderror

        <button type="submit" class="btn">Enviar código</button>
    </form>

    <p class="foot">
        <a class="link" href="{{ route('password.reset') }}">Ya tengo un código</a>
        &nbsp;·&nbsp;
        <a class="link" href="{{ route('login') }}">Volver a iniciar sesión</a>
    </p>
@endsection
