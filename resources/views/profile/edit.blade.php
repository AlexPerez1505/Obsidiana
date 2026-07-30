@extends('layouts.dashboard')
@section('title', 'Editar perfil')
@section('page-title', 'Editar perfil')
@section('page-sub', 'Actualiza tu información personal')

@section('content')
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); align-items:start;">
        {{-- Datos personales --}}
        <x-ui.card>
            <x-ui.section-title>Datos personales</x-ui.section-title>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <x-ui.form-group
                    label="Nombre"
                    name="name"
                    type="text"
                    :value="old('name', $user->name)"
                    :required="true"
                />

                <x-ui.form-group
                    label="Correo electrónico"
                    name="email"
                    type="email"
                    :value="old('email', $user->email)"
                    :required="true"
                />

                <p class="muted" style="margin-top:6px; font-size:13px;">
                    Si cambias el correo, tendrás que verificarlo de nuevo con un código.
                </p>

                <x-ui.button style="margin-top:16px;">Guardar cambios</x-ui.button>
            </form>
        </x-ui.card>

        {{-- Cambiar contraseña --}}
        <x-ui.card>
            <x-ui.section-title>Cambiar contraseña</x-ui.section-title>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <x-ui.form-group
                    label="Contraseña actual"
                    name="current_password"
                    type="password"
                    :required="true"
                />

                <x-ui.form-group
                    label="Nueva contraseña"
                    name="password"
                    type="password"
                    :required="true"
                />

                <x-ui.form-group
                    label="Confirmar nueva contraseña"
                    name="password_confirmation"
                    type="password"
                    :required="true"
                />

                <x-ui.button style="margin-top:16px;">Actualizar contraseña</x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
