@extends('layouts.dashboard')
@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto')
@section('page-sub', $producto->tipo_equipo)

@section('content')
    <form method="POST" action="{{ route('inventory.productos.update', $producto) }}" enctype="multipart/form-data" style="max-width:720px;">
        @csrf
        @method('PUT')
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                <x-ui.form-group label="Tipo de Equipo *" name="tipo_equipo" :value="$producto->tipo_equipo" :required="true" />
                <x-ui.form-group label="Subtipo" name="subtipo" :value="$producto->subtipo" />
                <x-ui.form-group label="Marca" name="marca" :value="$producto->marca" />
                <x-ui.form-group label="Modelo" name="modelo" :value="$producto->modelo" />
                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" :value="$producto->precio" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" :value="$producto->stock" :required="true" />
                <x-ui.form-group label="Proveedor" name="proveedor" :value="$producto->proveedor" />
                <x-ui.form-group label="No. Serie" name="no_serie" :value="$producto->no_serie" />
            </div>
            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </x-ui.form-group>
            <x-ui.form-group label="Imagen del Producto" for="imagen">
                @if($producto->imagen_path)
                    <div style="margin-bottom:10px;">
                        <img src="{{ asset('storage/' . $producto->imagen_path) }}" alt="Imagen actual" style="max-height:150px; border-radius:8px; border:1px solid var(--border);">
                        <small style="color:var(--muted); display:block; margin-top:4px;">Imagen actual</small>
                    </div>
                @endif
                <input type="file" id="imagen" name="imagen" accept="image/*" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB. Deja vacío para mantener la imagen actual.</small>
            </x-ui.form-group>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Actualizar Producto</x-ui.button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>
@endsection
