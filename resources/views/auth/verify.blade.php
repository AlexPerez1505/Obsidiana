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

        <label for="code">Código de verificación</label>
        <input id="code" class="code-input" type="text" name="code" inputmode="numeric"
               maxlength="6" pattern="[0-9]*" autocomplete="one-time-code" required autofocus>
        @error('code') <p class="err">{{ $message }}</p> @enderror

        <button type="submit" class="btn">Verificar</button>
    </form>

    <form method="POST" action="{{ route('verification.resend') }}" style="margin-top:14px;">
        @csrf
        <button type="submit" class="btn btn--ghost">Reenviar código</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="foot">
        @csrf
        <button type="submit" class="link" style="background:none;border:none;cursor:pointer;">Cerrar sesión</button>
    </form>
@endsection
