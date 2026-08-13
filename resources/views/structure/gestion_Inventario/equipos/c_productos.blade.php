@extends('layouts.dashboard')

@php
    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';
    $productData = array_merge([
        'id' => '',
        'serial_number' => '',
        'name' => '',
        'category' => '',
        'subtype' => '',
        'unit' => '',
        'brand' => '',
        'model' => '',
        'description' => '',
        'price' => '',
        'stock_current' => '',
        'warehouse' => '',
        'type' => '',
        'technical_category' => '',
        'specifications' => '',
        'supplier' => '',
        'supplier_code' => '',
        'thumb' => 'scope',
        'image_path' => '',
    ], $product ?? []);
    $selectOptions = array_merge([
        'categories' => ['Endoscopia', 'Consumibles', 'Instrumental', 'Refacciones'],
        'subtypes' => [],
        'subtypesByCategory' => [],
        'brands' => [],
        'models' => [],
    ], $selectOptions ?? []);
    $currentCategory = old('category', $productData['category']);
    $currentSubtype = old('subtype', $productData['subtype']);
    $currentBrand = old('brand', $productData['brand']);
    $currentModel = old('model', $productData['model']);
    $imageUrl = $productData['image_path'] ? asset($productData['image_path']) : null;
    $pageTitle = $isEdit ? 'Editar producto' : 'Nuevo Producto';
    $pageSub = $isEdit ? 'Gestion de Inventario > Productos > Editar Producto' : 'Gestion de Inventario > Productos > Nuevo Producto';
    $cardTitle = $isEdit ? 'Editar producto' : 'Agregar producto';
    $cardSub = $isEdit ? 'Actualiza los datos del producto y su imagen.' : 'Crea un nuevo producto y sube su imagen.';
    $imageAction = $isEdit ? 'Cambiar imagen' : 'Subir imagen';
    $submitLabel = $isEdit ? 'Guardar cambios' : 'Guardar';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-sub', $pageSub)

@push('head')
<style>
    .product-combo {
        position: relative;
        display: flex;
        align-items: center;
    }
    .product-combo input {
        width: 100%;
        padding: 11px 38px 11px 12px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 15px;
    }
    .product-combo__toggle {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: transparent;
        color: var(--muted);
        cursor: pointer;
    }
    .product-combo__menu {
        position: absolute;
        left: 0;
        top: calc(100% + 4px);
        width: 100%;
        max-height: 220px;
        overflow-y: auto;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        box-shadow: var(--shadow);
        display: none;
        z-index: 20;
    }
    .product-combo.is-open .product-combo__menu { display: grid; gap: 2px; }
    .product-combo__option {
        padding: 8px 10px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: var(--text);
        text-align: left;
        font-size: 14px;
        cursor: pointer;
    }
    .product-combo__option:hover { background: #eef4ff; color: #0879d0; }
    .product-money {
        display: flex;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        overflow: hidden;
    }
    .product-money__prefix,
    .product-money__suffix {
        padding: 0 12px;
        color: var(--muted);
        font-weight: 700;
        font-size: 15px;
    }
    .product-money__input {
        flex: 1;
        min-width: 0;
        padding: 11px 8px;
        border: 0;
        border-left: 1px solid var(--border);
        border-right: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 15px;
    }
    .product-image-upload {
        display: grid;
        gap: 14px;
    }
    .product-image-preview {
        border: 1px dashed rgba(37, 99, 235, 0.55);
        background: rgba(37, 99, 235, 0.04);
        border-radius: 8px;
        min-height: 154px;
        display: grid;
        place-items: center;
        overflow: hidden;
    }
    .product-image-preview img { width: 100%; height: 100%; object-fit: contain; padding: 12px; }
    .product-upload-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border-radius: 8px;
        background: #1e90ff;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }
    .product-image-help {
        margin: 0;
        color: var(--muted);
        font-size: 13px;
        text-align: center;
    }
    .product-file-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
</style>
@endpush

@section('content')
    <form method="POST" action="{{ $isEdit ? route('inventory.productos.update', ['producto' => $productData['id']]) : route('inventory.productos.store') }}" enctype="multipart/form-data" style="max-width:1040px; margin:0 auto; display:grid; gap:18px;">
        @csrf
        @if($isEdit)
            @method('PATCH')
        @endif

        <x-ui.card>
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:18px; flex-wrap:wrap;">
                <div>
                    <x-ui.section-title style="margin:0;">{{ $cardTitle }}</x-ui.section-title>
                    <p class="muted" style="margin:4px 0 0; font-weight:600;">{{ $cardSub }}</p>
                </div>
                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Volver</a>
            </div>

            @if ($errors->any())
                <div style="border:1px solid #ef4444; border-radius:9px; padding:14px; color:#ef4444; margin-top:18px; background:rgba(239,68,68,0.05);">
                    <strong>Revisa los datos del producto.</strong>
                    <ul style="margin:8px 0 0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rgrid-2" style="margin-top:18px; gap:14px;">
                <div class="equipment-field" style="display:grid; gap:6px;">
                    <label for="category">Tipo de equipo</label>
                    <div class="product-combo" data-combo data-combo-name="category">
                        <input type="text" id="category" name="category" value="{{ $currentCategory }}" autocomplete="off" data-combo-input>
                        <button class="product-combo__toggle" type="button" aria-label="Mostrar tipos de equipo" data-combo-toggle>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="product-combo__menu" role="listbox" data-combo-menu>
                            @foreach($selectOptions['categories'] as $category)
                                <button class="product-combo__option" type="button" data-combo-option="{{ $category }}">{{ $category }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="equipment-field" style="display:grid; gap:6px;">
                    <label for="subtype">Subtipo</label>
                    <div class="product-combo" data-combo data-combo-name="subtype">
                        <input type="text" id="subtype" name="subtype" value="{{ $currentSubtype }}" autocomplete="off" data-combo-input>
                        <button class="product-combo__toggle" type="button" aria-label="Mostrar subtipos" data-combo-toggle>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="product-combo__menu" role="listbox" data-combo-menu>
                            @foreach($selectOptions['subtypes'] as $subtype)
                                <button class="product-combo__option" type="button" data-combo-option="{{ $subtype }}">{{ $subtype }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="equipment-field" style="display:grid; gap:6px;">
                    <label for="brand">Marca</label>
                    <div class="product-combo" data-combo data-combo-name="brand">
                        <input type="text" id="brand" name="brand" value="{{ $currentBrand }}" autocomplete="off" data-combo-input>
                        <button class="product-combo__toggle" type="button" aria-label="Mostrar marcas" data-combo-toggle>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="product-combo__menu" role="listbox" data-combo-menu>
                            @foreach($selectOptions['brands'] as $brand)
                                <button class="product-combo__option" type="button" data-combo-option="{{ $brand }}">{{ $brand }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="equipment-field" style="display:grid; gap:6px;">
                    <label for="model">Modelo</label>
                    <div class="product-combo" data-combo data-combo-name="model">
                        <input type="text" id="model" name="model" value="{{ $currentModel }}" autocomplete="off" data-combo-input>
                        <button class="product-combo__toggle" type="button" aria-label="Mostrar modelos" data-combo-toggle>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="product-combo__menu" role="listbox" data-combo-menu>
                            @foreach($selectOptions['models'] as $model)
                                <button class="product-combo__option" type="button" data-combo-option="{{ $model }}">{{ $model }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <x-ui.form-group label="Serial number" name="serial_number" :value="$productData['serial_number']" />
                <x-ui.form-group label="Nombre del producto" name="name" :value="$productData['name']" />
                <x-ui.form-group label="Unidad" name="unit" :value="$productData['unit']" placeholder="Ej. Pza" />
                <x-ui.form-group label="Stock actual" name="stock_current" type="number" :value="$productData['stock_current']" />
                <x-ui.form-group for="warehouse" label="Almacen predeterminado" style="grid-column:1 / -1;">
                    <select id="warehouse" name="warehouse" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="">Seleccionar</option>
                        <option value="Almacen Central" @selected(old('warehouse', $productData['warehouse']) === 'Almacen Central')>Almacen Central</option>
                        <option value="Quirofano 1" @selected(old('warehouse', $productData['warehouse']) === 'Quirofano 1')>Quirofano 1</option>
                        <option value="Quirofano 2" @selected(old('warehouse', $productData['warehouse']) === 'Quirofano 2')>Quirofano 2</option>
                        <option value="Servicio tecnico" @selected(old('warehouse', $productData['warehouse']) === 'Servicio tecnico')>Servicio tecnico</option>
                    </select>
                </x-ui.form-group>

                <div class="equipment-field" style="display:grid; gap:6px; grid-column:1 / -1;">
                    <label for="price">Precio</label>
                    <div class="product-money">
                        <span class="product-money__prefix">$</span>
                        <input class="product-money__input" type="number" id="price" name="price" value="{{ old('price', $productData['price']) }}" min="0" step="0.01" inputmode="decimal">
                        <span class="product-money__suffix">MXN</span>
                    </div>
                </div>

                <x-ui.form-group for="description" label="Descripcion" style="grid-column:1 / -1;">
                    <input id="description" name="description" value="{{ old('description', $productData['description']) }}" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" autocomplete="off">
                </x-ui.form-group>
            </div>

            <div class="product-image-upload" style="margin-top:24px;">
                <x-ui.section-title style="margin:0;">Imagen del producto</x-ui.section-title>
                <div class="product-image-preview" id="productImagePreview" aria-label="Vista previa">
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="Vista previa del producto">
                    @else
                        @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $productData['thumb']])
                    @endif
                </div>

                <div style="display:grid; place-items:center; gap:6px;">
                    <label class="product-upload-button" for="product_image">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        {{ $imageAction }}
                    </label>
                    <p class="product-image-help">Formatos: JPG/PNG. Max 2MB.</p>
                </div>
                <input class="product-file-input" type="file" id="product_image" name="product_image" accept="image/jpeg,image/png">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px; padding-top:18px; border-top:1px solid var(--border);">
                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
                <x-ui.button>{{ $submitLabel }}</x-ui.button>
            </div>
        </x-ui.card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const imageInput = document.getElementById('product_image');
            const imagePreview = document.getElementById('productImagePreview');
            const categoryInput = document.getElementById('category');
            const subtypeInput = document.getElementById('subtype');
            const allSubtypeOptions = @json($selectOptions['subtypes'] ?? []);
            const subtypesByCategory = @json($selectOptions['subtypesByCategory'] ?? []);
            const comboControllers = new Map();

            const normalizeKey = function (value) {
                return String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();
            };

            const uniqueSorted = function (values) {
                return Array.from(new Set(values.filter(Boolean).map(function (value) {
                    return String(value).replace(/\s+/g, ' ').trim();
                }))).sort(function (first, second) {
                    return first.localeCompare(second, 'es-MX');
                });
            };

            const relationMap = new Map();
            Object.keys(subtypesByCategory).forEach(function (category) {
                relationMap.set(normalizeKey(category), uniqueSorted(subtypesByCategory[category]));
            });

            const closeOtherCombos = function (currentCombo) {
                document.querySelectorAll('[data-combo]').forEach(function (combo) {
                    if (combo !== currentCombo) {
                        combo.classList.remove('is-open');
                    }
                });
            };

            const filterOptions = function (combo, input) {
                const query = normalizeKey(input.value);

                combo.querySelectorAll('[data-combo-option]').forEach(function (option) {
                    const value = normalizeKey(option.dataset.comboOption);
                    option.hidden = query !== '' && !value.includes(query);
                });
            };

            const bindOptionClicks = function (combo, input) {
                combo.querySelectorAll('[data-combo-option]').forEach(function (option) {
                    option.addEventListener('click', function () {
                        input.value = option.dataset.comboOption;
                        combo.classList.remove('is-open');
                        input.focus();
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            };

            const renderComboOptions = function (combo, input, values) {
                const menu = combo.querySelector('[data-combo-menu]');

                if (!menu) return;

                menu.innerHTML = '';
                uniqueSorted(values).forEach(function (value) {
                    const button = document.createElement('button');
                    button.className = 'product-combo__option';
                    button.type = 'button';
                    button.dataset.comboOption = value;
                    button.textContent = value;
                    menu.appendChild(button);
                });

                bindOptionClicks(combo, input);
            };

            document.querySelectorAll('[data-combo]').forEach(function (combo) {
                const input = combo.querySelector('[data-combo-input]');
                const toggle = combo.querySelector('[data-combo-toggle]');
                const name = combo.dataset.comboName || input.id;

                toggle.addEventListener('click', function () {
                    closeOtherCombos(combo);
                    combo.classList.toggle('is-open');
                    filterOptions(combo, input);
                    input.focus();
                });

                input.addEventListener('focus', function () {
                    closeOtherCombos(combo);
                    combo.classList.add('is-open');
                    filterOptions(combo, input);
                });

                input.addEventListener('input', function () {
                    combo.classList.add('is-open');
                    filterOptions(combo, input);
                });

                bindOptionClicks(combo, input);
                comboControllers.set(name, {
                    render: function (values) {
                        renderComboOptions(combo, input, values);
                    },
                    combo: combo,
                    input: input,
                });
            });

            const updateSubtypesForCategory = function (clearInvalid) {
                if (!categoryInput || !subtypeInput || !comboControllers.has('subtype')) {
                    return;
                }

                let values = relationMap.get(normalizeKey(categoryInput.value)) || uniqueSorted(allSubtypeOptions);
                const currentSubtype = subtypeInput.value.trim();
                const currentExists = values.some(function (value) {
                    return normalizeKey(value) === normalizeKey(currentSubtype);
                });

                if (currentSubtype !== '' && !currentExists) {
                    if (clearInvalid) {
                        subtypeInput.value = '';
                    } else {
                        values = uniqueSorted(values.concat([currentSubtype]));
                    }
                }

                const subtypeCombo = comboControllers.get('subtype');
                subtypeCombo.render(values);
                filterOptions(subtypeCombo.combo, subtypeCombo.input);
            };

            if (categoryInput) {
                categoryInput.addEventListener('change', function () {
                    updateSubtypesForCategory(true);
                });

                categoryInput.addEventListener('input', function () {
                    updateSubtypesForCategory(false);
                });

                updateSubtypesForCategory(false);
            }

            document.addEventListener('click', function (event) {
                if (!event.target.closest('[data-combo]')) {
                    document.querySelectorAll('[data-combo]').forEach(function (combo) {
                        combo.classList.remove('is-open');
                    });
                }
            });

            if (imageInput && imagePreview) {
                imageInput.addEventListener('change', function () {
                    const file = imageInput.files && imageInput.files[0];

                    if (!file) return;

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
