@extends('layouts.app')
@section('title', 'Cuenta pendiente')

@section('content')
    <h1>Cuenta pendiente de aprobación</h1>
    <p class="sub">Hola {{ $user->name }}, tu correo ya está verificado. ✅</p>

    <x-ui.alert style="background:#fffbeb; color:#92400e; border:1px solid #fde68a;">
        Un administrador debe aprobar tu cuenta antes de que puedas entrar.
        Te avisaremos / podrás iniciar sesión en cuanto la activen.
    </x-ui.alert>

    <p class="muted" style="margin-top:14px;">
        Si crees que esto es un error, contacta al administrador.
    </p>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:18px;">
        @csrf
        <x-ui.button variant="ghost">Cerrar sesión</x-ui.button>
    </form>
@endsection
