@extends('layouts.app')
@section('title', 'Verificar correo')

@section('content')
    <h1>Verifica tu correo</h1>
    <p class="sub">
        Enviamos un código de 6 dígitos a <strong>{{ auth()->user()->email }}</strong>.
        Ingrésalo abajo para activar tu cuenta.
    </p>

    <form method="POST" action="{{ route('verification.verify') }}">
        @csrf

        <x-ui.form-group
            label="Código de verificación"
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

        <x-ui.button>Verificar</x-ui.button>
    </form>

    <form method="POST" action="{{ route('verification.resend') }}" style="margin-top:14px;">
        @csrf
        <x-ui.button variant="ghost">Reenviar código</x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="foot">
        @csrf
        <button type="submit" class="link" style="background:none;border:none;cursor:pointer;">Cerrar sesión</button>
    </form>
@endsection
