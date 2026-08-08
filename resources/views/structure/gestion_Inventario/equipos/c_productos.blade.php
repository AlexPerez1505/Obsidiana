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
    .product-create {
        max-width: 1040px;
        margin: 0 auto;
    }

    .product-card {
        overflow: visible;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        box-shadow: var(--shadow);
    }

    .product-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 26px 30px;
        border-bottom: 1px solid var(--border);
    }

    .product-card__header h2 {
        margin: 0;
        color: var(--text);
        font-size: clamp(1.7rem, 3vw, 2.45rem);
        font-weight: 900;
        line-height: 1.05;
    }

    .product-card__header p {
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 0.98rem;
        font-weight: 600;
    }

    .product-card__body {
        display: grid;
        gap: 22px;
        padding: 30px;
    }

    .product-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px 26px;
    }

    .product-field {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .product-field label {
        color: var(--muted);
        font-size: 0.86rem;
        font-weight: 800;
    }

    .product-input,
    .product-money {
        width: 100%;
        min-height: 58px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface-2);
        color: var(--text);
        font: inherit;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .product-input {
        padding: 0 16px;
    }

    .product-input:focus,
    .product-money:focus-within {
        border-color: rgba(37, 99, 235, 0.9);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
    }

    .product-combo {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 50px;
        align-items: stretch;
    }

    .product-combo .product-input {
        border-radius: 8px 0 0 8px;
    }

    .product-combo__toggle {
        border: 1px solid var(--border);
        border-left: 0;
        border-radius: 0 8px 8px 0;
        background: var(--surface-2);
        color: var(--muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .product-combo__toggle svg {
        width: 18px;
        height: 18px;
        transition: transform 0.15s ease;
    }

    .product-combo.is-open .product-combo__toggle svg {
        transform: rotate(180deg);
    }

    .product-combo__menu {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 40;
        display: none;
        max-height: 230px;
        overflow-y: auto;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        box-shadow: var(--shadow);
    }

    .product-combo.is-open .product-combo__menu {
        display: grid;
        gap: 3px;
    }

    .product-combo__option {
        min-height: 36px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: var(--text);
        cursor: pointer;
        font: inherit;
        font-weight: 700;
        padding: 0 10px;
        text-align: left;
    }

    .product-combo__option:hover,
    .product-combo__option:focus {
        background: rgba(21, 139, 232, 0.16);
        outline: none;
    }

    .product-money {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
        padding: 0 16px;
    }

    .product-money__prefix,
    .product-money__suffix {
        color: var(--muted);
        font-weight: 900;
        white-space: nowrap;
    }

    .product-money__input {
        min-width: 0;
        width: 100%;
        border: 0;
        background: transparent;
        color: var(--text);
        font: inherit;
        font-size: 1rem;
        outline: none;
        padding: 12px 0;
    }

    .product-money__input::-webkit-outer-spin-button,
    .product-money__input::-webkit-inner-spin-button {
        margin: 0;
    }

    .product-image-upload {
        display: flex;
        align-items: center;
        gap: 18px;
        min-height: 190px;
        padding: 18px;
        border: 1px dashed var(--border);
        border-radius: 8px;
        background: rgba(37, 99, 235, 0.03);
    }

    .product-image-preview {
        width: 158px;
        aspect-ratio: 1 / 1;
        flex: 0 0 auto;
        border-radius: 8px;
        background: var(--surface-2);
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .product-image-preview svg {
        width: 90px;
        height: auto;
    }

    .product-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .product-image-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        min-width: 0;
    }

    .product-upload-button {
        min-height: 50px;
        padding: 0 22px;
        border-radius: 8px;
        background: #45cdb4;
        color: #fff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 0.95rem;
        font-weight: 900;
        box-shadow: 0 14px 28px rgba(69, 205, 180, 0.22);
    }

    .product-upload-button svg {
        width: 18px;
        height: 18px;
    }

    .product-image-help {
        margin: 0;
        color: var(--muted);
        font-size: 0.88rem;
        font-weight: 700;
    }

    .product-file-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .product-card__footer {
        display: flex;
        justify-content: flex-end;
        gap: 14px;
        padding: 0 30px 30px;
    }

    .product-error-box {
        border: 1px solid rgba(239, 68, 68, 0.35);
        border-radius: 8px;
        background: rgba(239, 68, 68, 0.1);
        color: #fca5a5;
        padding: 14px 16px;
        font-weight: 700;
    }

    .product-error-box ul {
        margin: 8px 0 0;
        padding-left: 18px;
    }

    @media (max-width: 760px) {
        .product-card__header,
        .product-card__footer {
            padding-left: 18px;
            padding-right: 18px;
        }

        .product-card__header,
        .product-card__footer,
        .product-image-upload,
        .product-image-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .product-card__body {
            padding: 22px 18px;
        }

        .product-form-grid {
            grid-template-columns: 1fr;
        }

        .product-image-preview {
            width: 100%;
            max-width: 190px;
        }

        .product-card__header .btn,
        .product-card__footer .btn,
        .product-upload-button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    <form class="product-create" id="productCreateForm" method="POST" action="{{ $isEdit ? route('inventory.productos.update', ['producto' => $productData['id']]) : route('inventory.productos.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PATCH')
        @endif

        <section class="product-card" aria-labelledby="product-form-title">
            <div class="product-card__header">
                <div>
                    <h2 id="product-form-title">{{ $cardTitle }}</h2>
                    <p>{{ $cardSub }}</p>
                </div>

                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                    Volver
                </a>
            </div>

            <div class="product-card__body">
                @if ($errors->any())
                    <div class="product-error-box">
                        Revisa los datos del producto.
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="product-form-grid">
                    <div class="product-field">
                        <label for="category">Tipo de equipo</label>
                        <div class="product-combo" data-combo data-combo-name="category">
                            <input class="product-input" type="text" id="category" name="category" value="{{ $currentCategory }}" autocomplete="off" data-combo-input>
                            <button class="product-combo__toggle" type="button" aria-label="Mostrar tipos de equipo" data-combo-toggle>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div class="product-combo__menu" role="listbox" data-combo-menu>
                                @foreach($selectOptions['categories'] as $category)
                                    <button class="product-combo__option" type="button" data-combo-option="{{ $category }}">{{ $category }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="product-field">
                        <label for="subtype">Subtipo</label>
                        <div class="product-combo" data-combo data-combo-name="subtype">
                            <input class="product-input" type="text" id="subtype" name="subtype" value="{{ $currentSubtype }}" autocomplete="off" data-combo-input>
                            <button class="product-combo__toggle" type="button" aria-label="Mostrar subtipos" data-combo-toggle>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div class="product-combo__menu" role="listbox" data-combo-menu>
                                @foreach($selectOptions['subtypes'] as $subtype)
                                    <button class="product-combo__option" type="button" data-combo-option="{{ $subtype }}">{{ $subtype }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="product-field">
                        <label for="brand">Marca</label>
                        <div class="product-combo" data-combo data-combo-name="brand">
                            <input class="product-input" type="text" id="brand" name="brand" value="{{ $currentBrand }}" autocomplete="off" data-combo-input>
                            <button class="product-combo__toggle" type="button" aria-label="Mostrar marcas" data-combo-toggle>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div class="product-combo__menu" role="listbox" data-combo-menu>
                                @foreach($selectOptions['brands'] as $brand)
                                    <button class="product-combo__option" type="button" data-combo-option="{{ $brand }}">{{ $brand }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="product-field">
                        <label for="model">Modelo</label>
                        <div class="product-combo" data-combo data-combo-name="model">
                            <input class="product-input" type="text" id="model" name="model" value="{{ $currentModel }}" autocomplete="off" data-combo-input>
                            <button class="product-combo__toggle" type="button" aria-label="Mostrar modelos" data-combo-toggle>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div class="product-combo__menu" role="listbox" data-combo-menu>
                                @foreach($selectOptions['models'] as $model)
                                    <button class="product-combo__option" type="button" data-combo-option="{{ $model }}">{{ $model }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="product-field">
                        <label for="price">Precio</label>
                        <div class="product-money">
                            <span class="product-money__prefix">$</span>
                            <input class="product-money__input" type="number" id="price" name="price" value="{{ old('price', $productData['price']) }}" min="0" step="0.01" inputmode="decimal">
                            <span class="product-money__suffix">MXN</span>
                        </div>
                    </div>
                </div>

                <div class="product-image-upload">
                    <div class="product-image-preview" id="productImagePreview" aria-label="Vista previa">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="Vista previa del producto">
                        @else
                            @include('structure.gestion_Inventario.equipos.partials.product-thumb', ['type' => $productData['thumb']])
                        @endif
                    </div>

                    <div class="product-image-actions">
                        <label class="product-upload-button" for="product_image">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14"></path>
                                <path d="M5 12h14"></path>
                            </svg>
                            {{ $imageAction }}
                        </label>
                        <p class="product-image-help">Formatos: JPG/PNG. Max 2MB.</p>
                    </div>

                    <input class="product-file-input" type="file" id="product_image" name="product_image" accept="image/jpeg,image/png">
                </div>
            </div>

            <div class="product-card__footer">
                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                    Cancelar
                </a>
                <button type="submit" class="btn btn--primary">
                    {{ $submitLabel }}
                </button>
            </div>
        </section>
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

                if (!menu) {
                    return;
                }

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
