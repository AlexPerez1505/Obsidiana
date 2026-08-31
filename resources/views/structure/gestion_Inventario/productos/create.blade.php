@extends('layouts.dashboard')
@section('title', 'Agregar Producto')
@section('page-title', 'Agregar Producto')
@section('page-sub', 'Registra un nuevo equipo en el inventario')

@section('content')
    @php
        // Tipo, subtipo, marca y modelo salen del catálogo (Configuración → Catálogos).
        // Aquí solo queda el proveedor, que sigue siendo texto libre.
        $proveedorOptions = collect(($productoOptions ?? [])['proveedor'] ?? [])
            ->filter()->unique()->values()->all();
    @endphp

    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data" style="max-width:720px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                @include('structure.gestion_Inventario.productos._selects_catalogo')

                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" placeholder="0.00" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" placeholder="0" :required="true" />

                <x-ui.form-group label="Proveedor" for="proveedor">
                    <input id="proveedor" type="text" name="proveedor" list="proveedor_options"
                           value="{{ old('proveedor') }}" placeholder="Nombre del proveedor" autocomplete="off">
                    <datalist id="proveedor_options">
                        @foreach ($proveedorOptions as $option)
                            <option value="{{ $option }}"></option>
                        @endforeach
                    </datalist>
                </x-ui.form-group>
                <x-ui.form-group label="No. Serie" name="no_serie" placeholder="Número de serie" />
            </div>
            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>
            <x-ui.form-group label="Imagen del Producto" for="imagen">
                <input type="file" id="imagen" name="imagen" accept="image/*" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <div id="image-preview-wrap" style="display:none; margin-top:12px; padding:12px; border:1px solid var(--border); border-radius:12px; background:var(--surface-2);">
                    <img id="image-preview" src="" alt="Vista previa de la imagen del producto" style="display:block; width:100%; max-height:260px; object-fit:contain; border-radius:10px; background:var(--surface); border:1px solid var(--border);">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; flex-wrap:wrap;">
                        <small id="image-preview-name" style="color:var(--muted);"></small>
                        <button type="button" id="image-preview-clear" class="btn btn--ghost" style="padding:7px 12px; font-size:13px;">Quitar imagen</button>
                    </div>
                </div>
                <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB.</small>
            </x-ui.form-group>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Guardar Producto</x-ui.button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const imageInput = document.getElementById('imagen');
                const imagePreviewWrap = document.getElementById('image-preview-wrap');
                const imagePreview = document.getElementById('image-preview');
                const imagePreviewName = document.getElementById('image-preview-name');
                const imagePreviewClear = document.getElementById('image-preview-clear');
                let imagePreviewUrl = null;

                const clearImagePreview = function () {
                    if (imagePreviewUrl) {
                        URL.revokeObjectURL(imagePreviewUrl);
                        imagePreviewUrl = null;
                    }

                    if (imageInput) {
                        imageInput.value = '';
                    }

                    imagePreview.src = '';
                    imagePreviewName.textContent = '';
                    imagePreviewWrap.style.display = 'none';
                };

                if (imageInput && imagePreviewWrap && imagePreview && imagePreviewName && imagePreviewClear) {
                    imageInput.addEventListener('change', function () {
                        const file = imageInput.files && imageInput.files[0];

                        if (!file) {
                            clearImagePreview();
                            return;
                        }

                        if (imagePreviewUrl) {
                            URL.revokeObjectURL(imagePreviewUrl);
                        }

                        imagePreviewUrl = URL.createObjectURL(file);
                        imagePreview.src = imagePreviewUrl;
                        imagePreviewName.textContent = file.name;
                        imagePreviewWrap.style.display = 'block';
                    });

                    imagePreviewClear.addEventListener('click', clearImagePreview);
                }
            });
        </script>
    @endpush
@endsection
