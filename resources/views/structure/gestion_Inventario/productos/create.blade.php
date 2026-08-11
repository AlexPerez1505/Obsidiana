@extends('layouts.dashboard')
@section('title', 'Agregar Producto')
@section('page-title', 'Agregar Producto')
@section('page-sub', 'Registra un nuevo equipo en el inventario')

@section('content')
    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data" style="max-width:720px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                <x-ui.form-group label="Tipo de Equipo *" name="tipo_equipo" placeholder="Ej. Endoscopio" :required="true" />
                <x-ui.form-group label="Subtipo" name="subtipo" placeholder="Ej. Flexible" />
                <x-ui.form-group label="Marca" name="marca" placeholder="Ej. Olympus" />
                <x-ui.form-group label="Modelo" name="modelo" placeholder="Ej. GIF-H190" />
                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" placeholder="0.00" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" placeholder="0" :required="true" />
                <x-ui.form-group label="Proveedor" name="proveedor" placeholder="Nombre del proveedor" />
                <x-ui.form-group label="No. Serie" name="no_serie" placeholder="Número de serie" />
            </div>
            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>
            <x-ui.form-group label="Imagen del Producto" for="imagen">
                <input type="file" id="imagen" name="imagen" accept="image/*" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB.</small>
            </x-ui.form-group>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Producto</x-ui.button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>
@endsection
