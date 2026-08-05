@extends('layouts.dashboard')

@php
    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';
    $productData = array_merge([
        'code' => '',
        'name' => '',
        'category' => '',
        'unit' => '',
        'brand' => '',
        'model' => '',
        'description' => '',
        'stock_current' => '',
        'stock_max' => '',
        'stock_min' => '',
        'warehouse' => '',
        'type' => '',
        'technical_category' => '',
        'specifications' => '',
        'supplier' => '',
        'supplier_code' => '',
        'location' => '',
        'warranty' => '',
        'notes' => '',
        'thumb' => 'scope',
    ], $product ?? []);
    $pageTitle = $isEdit ? 'Editar producto' : 'Nuevo Producto';
    $pageSub = $isEdit ? 'Gestion de Inventario > Productos > Editar Producto' : 'Gestion de Inventario > Productos > Nuevo Producto';
    $introText = $isEdit ? 'Actualiza la informacion del producto del inventario.' : 'Registra un nuevo producto del inventario.';
    $imageAction = $isEdit ? 'Cambiar imagen' : 'Seleccionar imagen';
    $submitLabel = $isEdit ? 'Guardar cambios' : 'Guardar producto';
    $toastMessage = $isEdit ? 'Cambios del producto preparados para guardar.' : 'Producto preparado para guardar.';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-sub', $pageSub)

@push('head')
<style>
    .product-create {
        display: grid;
        gap: 18px;
    }

    .product-create__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .product-create__intro {
        color: var(--muted);
        margin: 0;
        font-size: 0.94rem;
    }

    .product-create__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .product-create__grid {
        display: grid;
        grid-template-columns: minmax(170px, 0.65fr) minmax(380px, 1.85fr) minmax(240px, 0.85fr);
        gap: 18px;
        align-items: stretch;
    }

    .product-create__row {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
        gap: 18px;
    }

    .product-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        padding: 20px;
        min-width: 0;
    }

    .product-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .product-panel__title {
        color: var(--text);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }

    .product-pill {
        border: 1px solid rgba(34, 197, 94, 0.65);
        color: #22c55e;
        background: rgba(34, 197, 94, 0.08);
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1;
        padding: 5px 10px;
        white-space: nowrap;
    }

    .product-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 18px;
    }

    .product-form-grid--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .product-form-grid--compact {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .product-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .product-field--wide {
        grid-column: 1 / -1;
    }

    .product-field label {
        color: var(--muted);
        font-size: 0.78rem;
        font-weight: 700;
        margin: 0;
    }

    .product-field .product-input,
    .product-field .product-select,
    .product-field .product-textarea {
        width: 100%;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text);
        border-radius: 8px;
        min-height: 38px;
        padding: 8px 12px;
        font: inherit;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .product-field .product-textarea {
        min-height: 78px;
        resize: vertical;
    }

    .product-field .product-input:focus,
    .product-field .product-select:focus,
    .product-field .product-textarea:focus {
        border-color: rgba(37, 99, 235, 0.9);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
    }

    .product-image-card {
        display: grid;
        grid-template-rows: auto 1fr auto;
        gap: 14px;
    }

    .product-image-card__preview {
        border: 1px dashed rgba(37, 99, 235, 0.55);
        background: rgba(37, 99, 235, 0.04);
        border-radius: 8px;
        min-height: 154px;
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .product-image-card__preview svg {
        width: min(128px, 80%);
        height: auto;
    }

    .product-image-card__preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        padding: 12px;
    }

    .product-image-card__button {
        color: #1e90ff;
        cursor: pointer;
        display: inline-flex;
        justify-content: center;
        text-align: center;
        font-weight: 800;
        font-size: 0.86rem;
        margin: 0;
    }

    .product-image-card__input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .product-create .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    @media (max-width: 1180px) {
        .product-create__grid,
        .product-create__row {
            grid-template-columns: 1fr;
        }

        .product-image-card {
            max-width: 320px;
        }
    }

    @media (max-width: 760px) {
        .product-create__bar,
        .product-create__actions {
            align-items: stretch;
            justify-content: stretch;
        }

        .product-create__actions,
        .product-create__actions .btn {
            width: 100%;
        }

        .product-form-grid,
        .product-form-grid--three,
        .product-form-grid--compact {
            grid-template-columns: 1fr;
        }

        .product-panel {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
    <form class="product-create" id="productCreateForm" method="POST" action="#" enctype="multipart/form-data">
        @csrf

        <div class="product-create__bar">
            <p class="product-create__intro">{{ $introText }}</p>

            <div class="product-create__actions">
                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                    Volver
                </a>
                <button type="submit" class="btn btn--primary">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>

        <div class="product-create__grid">
            <section class="product-panel product-image-card" aria-labelledby="product-image-title">
                <h3 class="product-panel__title" id="product-image-title">Imagen del producto</h3>

                <div class="product-image-card__preview" id="productImagePreview">
                    @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $productData['thumb']])
                </div>

                <label class="product-image-card__button" for="product_image">{{ $imageAction }}</label>
                <input class="product-image-card__input" type="file" id="product_image" name="product_image" accept="image/*">
            </section>

            <section class="product-panel" aria-labelledby="product-general-title">
                <div class="product-panel__header">
                    <h3 class="product-panel__title" id="product-general-title">Informacion general</h3>
                    <span class="product-pill">Activo</span>
                </div>

                <div class="product-form-grid">
                    <div class="product-field">
                        <label for="code">Codigo</label>
                        <input class="product-input" type="text" id="code" name="code" value="{{ old('code', $productData['code']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="name">Nombre del producto</label>
                        <input class="product-input" type="text" id="name" name="name" value="{{ old('name', $productData['name']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="category">Categoria</label>
                        <select class="product-select" id="category" name="category">
                            <option value="">Seleccionar</option>
                            <option value="Endoscopia" @selected(old('category', $productData['category']) === 'Endoscopia')>Endoscopia</option>
                            <option value="Consumibles" @selected(old('category', $productData['category']) === 'Consumibles')>Consumibles</option>
                            <option value="Instrumental" @selected(old('category', $productData['category']) === 'Instrumental')>Instrumental</option>
                            <option value="Refacciones" @selected(old('category', $productData['category']) === 'Refacciones')>Refacciones</option>
                        </select>
                    </div>

                    <div class="product-field">
                        <label for="unit">Unidad de medida</label>
                        <select class="product-select" id="unit" name="unit">
                            <option value="">Seleccionar</option>
                            <option value="Pza" @selected(old('unit', $productData['unit']) === 'Pza')>Pza</option>
                            <option value="Caja" @selected(old('unit', $productData['unit']) === 'Caja')>Caja</option>
                            <option value="Kit" @selected(old('unit', $productData['unit']) === 'Kit')>Kit</option>
                            <option value="Paquete" @selected(old('unit', $productData['unit']) === 'Paquete')>Paquete</option>
                        </select>
                    </div>

                    <div class="product-field">
                        <label for="brand">Marca</label>
                        <input class="product-input" type="text" id="brand" name="brand" value="{{ old('brand', $productData['brand']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="model">Modelo</label>
                        <input class="product-input" type="text" id="model" name="model" value="{{ old('model', $productData['model']) }}" autocomplete="off">
                    </div>

                    <div class="product-field product-field--wide">
                        <label for="description">Descripcion</label>
                        <input class="product-input" type="text" id="description" name="description" value="{{ old('description', $productData['description']) }}" autocomplete="off">
                    </div>
                </div>
            </section>

            <section class="product-panel" aria-labelledby="product-stock-title">
                <div class="product-panel__header">
                    <h3 class="product-panel__title" id="product-stock-title">Stock y control</h3>
                    <span class="product-pill">Activo</span>
                </div>

                <div class="product-form-grid product-form-grid--compact">
                    <div class="product-field product-field--wide">
                        <label for="stock_current">Stock actual</label>
                        <input class="product-input" type="number" id="stock_current" name="stock_current" value="{{ old('stock_current', $productData['stock_current']) }}" min="0" step="1">
                    </div>

                    <div class="product-field product-field--wide">
                        <label for="stock_max">Stock maximo</label>
                        <input class="product-input" type="number" id="stock_max" name="stock_max" value="{{ old('stock_max', $productData['stock_max']) }}" min="0" step="1">
                    </div>

                    <div class="product-field product-field--wide">
                        <label for="stock_min">Stock minimo</label>
                        <input class="product-input" type="number" id="stock_min" name="stock_min" value="{{ old('stock_min', $productData['stock_min']) }}" min="0" step="1">
                    </div>

                    <div class="product-field product-field--wide">
                        <label for="warehouse">Almacen predeterminado</label>
                        <select class="product-select" id="warehouse" name="warehouse">
                            <option value="">Seleccionar</option>
                            <option value="Almacen Central" @selected(old('warehouse', $productData['warehouse']) === 'Almacen Central')>Almacen Central</option>
                            <option value="Quirofano 1" @selected(old('warehouse', $productData['warehouse']) === 'Quirofano 1')>Quirofano 1</option>
                            <option value="Quirofano 2" @selected(old('warehouse', $productData['warehouse']) === 'Quirofano 2')>Quirofano 2</option>
                            <option value="Servicio tecnico" @selected(old('warehouse', $productData['warehouse']) === 'Servicio tecnico')>Servicio tecnico</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>

        <div class="product-create__row">
            <section class="product-panel" aria-labelledby="product-tech-title">
                <h3 class="product-panel__title" id="product-tech-title">Informacion tecnica</h3>

                <div class="product-form-grid product-form-grid--three" style="margin-top:16px;">
                    <div class="product-field">
                        <label for="type">Tipo</label>
                        <input class="product-input" type="text" id="type" name="type" value="{{ old('type', $productData['type']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="technical_category">Categoria tecnica</label>
                        <input class="product-input" type="text" id="technical_category" name="technical_category" value="{{ old('technical_category', $productData['technical_category']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="specifications">Especificaciones</label>
                        <input class="product-input" type="text" id="specifications" name="specifications" value="{{ old('specifications', $productData['specifications']) }}" autocomplete="off">
                    </div>
                </div>
            </section>

            <section class="product-panel" aria-labelledby="product-supplier-title">
                <h3 class="product-panel__title" id="product-supplier-title">Proveedor</h3>

                <div class="product-form-grid product-form-grid--compact" style="margin-top:16px;">
                    <div class="product-field">
                        <label for="supplier">Proveedor</label>
                        <input class="product-input" type="text" id="supplier" name="supplier" value="{{ old('supplier', $productData['supplier']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="supplier_code">Codigo de proveedor</label>
                        <input class="product-input" type="text" id="supplier_code" name="supplier_code" value="{{ old('supplier_code', $productData['supplier_code']) }}" autocomplete="off">
                    </div>
                </div>
            </section>
        </div>

        <div class="product-create__row">
            <section class="product-panel" aria-labelledby="product-extra-title">
                <h3 class="product-panel__title" id="product-extra-title">Informacion adicional</h3>

                <div class="product-form-grid product-form-grid--compact" style="margin-top:16px;">
                    <div class="product-field">
                        <label for="location">Ubicacion</label>
                        <input class="product-input" type="text" id="location" name="location" value="{{ old('location', $productData['location']) }}" autocomplete="off">
                    </div>

                    <div class="product-field">
                        <label for="warranty">Garantia</label>
                        <input class="product-input" type="text" id="warranty" name="warranty" value="{{ old('warranty', $productData['warranty']) }}" autocomplete="off">
                    </div>
                </div>
            </section>

            <section class="product-panel" aria-labelledby="product-notes-title">
                <h3 class="product-panel__title" id="product-notes-title">Notas</h3>

                <div class="product-field" style="margin-top:16px;">
                    <label class="sr-only" for="notes">Notas</label>
                    <textarea class="product-textarea" id="notes" name="notes">{{ old('notes', $productData['notes']) }}</textarea>
                </div>
            </section>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('productCreateForm');
            const imageInput = document.getElementById('product_image');
            const imagePreview = document.getElementById('productImagePreview');

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (window.showToast) {
                        window.showToast(@json($toastMessage));
                    }
                });
            }

            if (imageInput && imagePreview) {
                imageInput.addEventListener('change', function () {
                    const file = imageInput.files && imageInput.files[0];

                    if (!file) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function () {
                        imagePreview.innerHTML = '';

                        const image = document.createElement('img');
                        image.src = reader.result;
                        image.alt = 'Vista previa del producto';
                        imagePreview.appendChild(image);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
@endsection
