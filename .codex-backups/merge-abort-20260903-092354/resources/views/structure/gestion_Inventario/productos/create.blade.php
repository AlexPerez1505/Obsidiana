@extends('layouts.dashboard')
@section('title', 'Agregar Producto')
@section('page-title', 'Agregar Producto')
@section('page-sub', 'Registra un nuevo equipo en el inventario')

@push('head')
    <style>
        .rgrid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px 18px; }
        @media (max-width: 520px) { .rgrid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    @php
        // Tipo, subtipo, marca y modelo salen del catálogo (Configuración → Catálogos).
        // Aquí solo queda el proveedor, que sigue siendo texto libre.
        $proveedorOptions = collect(($productoOptions ?? [])['proveedor'] ?? [])
            ->filter()->unique()->values()->all();
    @endphp

    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                @include('structure.gestion_Inventario.productos._selects_catalogo')

                <div id="modeloExistenteAviso" class="cat-aviso" style="display:none; grid-column:1 / -1;"></div>

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
            </div>
            <x-ui.form-group label="Números de serie (uno por línea, opcional)" for="series_texto">
                <textarea id="series_texto" name="series_texto" rows="3" placeholder="Un número de serie por unidad. Déjalo vacío si el stock no tiene serial individual."
                          style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('series_texto') }}</textarea>
                <small style="color:var(--muted);">Si capturas todas las series, deben ser exactamente tantas líneas como el stock de arriba: cada línea es una unidad. Si solo pones una y el stock es mayor a 1, el resto de la secuencia se genera solo (ej. 23A12345 → 23A12346, 23A12347...).</small>
            </x-ui.form-group>
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
   
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Si el modelo elegido ya está registrado, se rellenan solos
                // precio, descripción y proveedor. Stock y no. serie NO se
                // tocan: son propios de cada unidad que se va a dar de alta.
                const modeloSelect = document.getElementById('equipment_model_id');
                const aviso = document.getElementById('modeloExistenteAviso');
                const precioInput = document.getElementById('precio');
                const descripcionInput = document.getElementById('descripcion');
                const proveedorInput = document.getElementById('proveedor');
                const seriesTextoInput = document.getElementById('series_texto');
                const buscarPorModeloUrl = @json(route('inventory.productos.buscarPorModelo'));

                if (modeloSelect) {
                    modeloSelect.addEventListener('change', function () {
                        aviso.style.display = 'none';

                        if (!modeloSelect.value) return;

                        fetch(buscarPorModeloUrl + '?equipment_model_id=' + encodeURIComponent(modeloSelect.value), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (!data.existe) return;

                                // El precio depende del modelo elegido: se carga siempre al
                                // momento, aunque ya hubiera algo escrito ahí.
                                if (precioInput) precioInput.value = data.precio ?? '';
                                if (descripcionInput && !descripcionInput.value) descripcionInput.value = data.descripcion ?? '';
                                if (proveedorInput && !proveedorInput.value) proveedorInput.value = data.proveedor ?? '';

                                let mensaje = 'Este modelo ya está registrado (stock actual: ' + data.stock_actual + '). Al guardar, esta cantidad se agregará como unidades nuevas de esa misma fila (no se crea un producto nuevo). Se completaron precio, descripción y proveedor.';

                                if (seriesTextoInput && !seriesTextoInput.value && data.no_serie_sugerido) {
                                    seriesTextoInput.value = data.no_serie_sugerido;
                                    mensaje += ' El número de serie se sugirió como ' + data.no_serie_sugerido + ' (consecutivo del último registrado). Si el stock es mayor a 1, deja solo esa línea: el resto de la secuencia se genera solo. Puedes cambiarlo si no corresponde.';
                                } else {
                                    mensaje += ' Revisa el stock y los números de serie antes de guardar.';
                                }

                                aviso.textContent = mensaje;
                                aviso.style.display = 'block';
                            })
                            .catch(() => {});
                    });
                }

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



