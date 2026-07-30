@extends('layouts.dashboard')
@section('title', 'Nuevo permiso')
@section('page-title', 'Nuevo permiso')
@section('page-sub', 'Define un nuevo permiso del sistema')

@section('content')
    <x-ui.card style="max-width:600px;">
        <form method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf

            <x-ui.form-group label="Identificador (name)" for="name">
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="ver_reportes_ventas" pattern="[a-z_]+" required>
                <p class="muted" style="margin:6px 0 0; font-size:12px;">Solo minúsculas y guiones bajos. Se usa en el código.</p>
            </x-ui.form-group>

            <x-ui.form-group label="Etiqueta visible" for="label">
                <input id="label" type="text" name="label" value="{{ old('label') }}" required>
            </x-ui.form-group>

            <x-ui.form-group label="Descripción" for="description">
                <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </x-ui.form-group>

            <div style="display:flex; gap:10px; margin-top:18px;">
                <x-ui.button type="submit">Guardar permiso</x-ui.button>
                <a class="link" href="{{ route('admin.permissions.index') }}" style="align-self:center;">Cancelar</a>
            </div>
        </form>
    </x-ui.card>
@endsection
