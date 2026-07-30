@extends('layouts.dashboard')
@section('title', 'Editar permiso')
@section('page-title', 'Editar permiso')
@section('page-sub', $permission->label)

@section('content')
    <x-ui.card style="max-width:600px; margin-bottom:18px;">
        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
            @csrf
            @method('PATCH')

            <x-ui.form-group label="Identificador (name)" for="name">
                <input id="name" type="text" name="name" value="{{ old('name', $permission->name) }}" pattern="[a-z_]+" required>
            </x-ui.form-group>

            <x-ui.form-group label="Etiqueta visible" for="label">
                <input id="label" type="text" name="label" value="{{ old('label', $permission->label) }}" required>
            </x-ui.form-group>

            <x-ui.form-group label="Descripción" for="description">
                <textarea id="description" name="description" rows="3">{{ old('description', $permission->description) }}</textarea>
            </x-ui.form-group>

            <div style="display:flex; gap:10px; margin-top:18px;">
                <x-ui.button type="submit">Actualizar permiso</x-ui.button>
                <a class="link" href="{{ route('admin.permissions.index') }}" style="align-self:center;">Cancelar</a>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card style="max-width:600px;">
        <x-ui.section-title style="margin-top:0;">Eliminar permiso</x-ui.section-title>
        <p class="muted">Esta acción no se puede deshacer. Los usuarios perderán este permiso.</p>
        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" style="margin-top:12px;">
            @csrf
            @method('DELETE')
            <x-ui.button variant="danger" type="submit">Eliminar</x-ui.button>
        </form>
    </x-ui.card>
@endsection
