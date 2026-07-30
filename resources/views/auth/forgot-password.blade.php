@extends('layouts.app')
@section('title', 'Recuperar contraseña')

@section('content')
    <h1>Recuperar contraseña</h1>
    <p class="sub">Escribe tu correo y te enviaremos un código para restablecerla.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <x-ui.form-group
            label="Correo electrónico"
            name="email"
            type="email"
            :value="old('email')"
            :required="true"
            :autofocus="true"
        />

        <x-ui.button>Enviar código</x-ui.button>
    </form>

    <p class="foot">
        <a class="link" href="{{ route('password.reset') }}">Ya tengo un código</a>
        &nbsp;·&nbsp;
        <a class="link" href="{{ route('login') }}">Volver a iniciar sesión</a>
    </p>
@endsection
