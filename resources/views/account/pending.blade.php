@extends('layouts.app')
@section('title', 'Cuenta pendiente')

@section('content')
    <h1>Cuenta pendiente de aprobación</h1>
    <p class="sub">Hola {{ $user->name }}, tu correo ya está verificado. ✅</p>

    <div class="alert" style="background:#fffbeb; color:#92400e; border:1px solid #fde68a;">
        Un administrador debe aprobar tu cuenta antes de que puedas entrar.
        Te avisaremos / podrás iniciar sesión en cuanto la activen.
    </div>

    <p class="muted" style="margin-top:14px;">
        Si crees que esto es un error, contacta al administrador.
    </p>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:18px;">
        @csrf
        <button type="submit" class="btn btn--ghost">Cerrar sesión</button>
    </form>
@endsection
