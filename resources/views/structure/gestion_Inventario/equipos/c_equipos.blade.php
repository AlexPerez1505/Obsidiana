@extends('layouts.dashboard')

@php
    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';
    $equipmentData = array_merge([
        'code' => '',
        'name' => '',
        'category' => '',
        'subcategory' => '',
        'serial_number' => '',
        'brand' => '',
        'model' => '',
        'description' => '',
        'stock_current' => '',
        'stock_max' => '',
        'stock_min' => '',
        'warehouse' => '',
        'assigned_to' => '',
        'department' => '',
        'service_date' => '',
        'next_maintenance' => '',
        'notes' => '',
        'voltage' => '',
        'frequency' => '',
        'power' => '',
        'weight' => '',
        'dimensions' => '',
        'color' => '',
        'technical_specs' => '',
        'supplier' => '',
        'contact' => '',
        'phone' => '',
        'email' => '',
        'invoice_number' => '',
        'invoice_date' => '',
        'image_path' => null,
        'thumb' => 'tower',
    ], $equipment ?? []);
    $catalogs = $catalogs ?? ['types' => [], 'brands' => []];
    $pageTitle = $isEdit ? 'Editar Equipo' : 'Nuevo Equipo';
    $pageSub = $isEdit ? 'Gestion de Inventario > Equipos > Editar Equipo' : 'Gestion de Inventario > Equipos > Nuevo Equipo';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-sub', $pageSub)

@push('head')
<style>
    .equipment-form { max-width: 1400px; margin: 0 auto; }
    .equipment-form .rgrid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    @media (max-width: 880px) { .equipment-form .rgrid-2 { grid-template-columns: 1fr; } }
    .equipment-card { padding: 18px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; }
    .equipment-card__title { font-size: 15px; font-weight: 800; margin: 0 0 14px; color: var(--text); }
    .equipment-field { display: grid; gap: 4px; }
    .equipment-field label { font-size: 12px; font-weight: 700; color: var(--muted); }
    .equipment-input {
        width: 100%;
        padding: 9px 11px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 14px;
        outline: none;
    }
    .equipment-input:focus { border-color: #7c3aed; }
    .equipment-select { appearance: none; cursor: pointer; }
    .equipment-unit { display: grid; grid-template-columns: 1fr auto; align-items: center; }
    .equipment-unit .equipment-input { border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .equipment-unit__suffix {
        padding: 9px 11px;
        border: 1px solid var(--border);
        border-left: 0;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
        background: var(--surface-2);
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }
    .equipment-dropzone {
        flex: 1;
        min-height: 180px;
        border: 1px dashed var(--border);
        border-radius: 12px;
        background: var(--surface-2);
        display: grid;
        place-items: center;
        padding: 16px;
        text-align: center;
    }
    .equipment-dropzone img { max-width: 100%; max-height: 140px; object-fit: contain; }
    .equipment-dropzone svg { max-width: 110px; max-height: 110px; }
    .equipment-dropzone__text { color: var(--muted); font-size: 13px; font-weight: 700; margin-top: 10px; }
    .equipment-dropzone__link { color: #2563eb; cursor: pointer; text-decoration: none; }
    .equipment-dropzone input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .equipment-actions { display: flex; justify-content: flex-end; gap: 10px; margin: 2px 0 6px; }
    .equipment-note { width: 100%; min-height: 70px; resize: vertical; }
</style>
@endpush

@section('content')
    <div class="equipment-form">
        <div class="equipment-actions">
            <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
            <x-ui.button>Guardar equipo</x-ui.button>
        </div>

        <form method="POST" action="{{ $isEdit ? route('inventory.equipos.update', ['equipo' => $equipmentData['code']]) : route('inventory.equipos.store') }}" enctype="multipart/form-data" style="display:grid; gap:14px;">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            @if ($errors->any())
                <x-ui.card style="border-color:#ef4444; color:#ef4444; padding:14px;">
                    <strong>Revisa los siguientes campos:</strong>
                    <ul style="margin:6px 0 0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            <div class="rgrid-2" style="align-items:stretch;">
                <div class="equipment-card">
                    <h3 class="equipment-card__title">Imagen del equipo</h3>
                    <label class="equipment-dropzone" id="equipmentImagePreview" for="equipment_image">
                        @if (!empty($equipmentData['image_path']))
                            <img src="{{ asset('storage/' . $equipmentData['image_path']) }}" alt="Imagen del equipo">
                        @else
                            @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipmentData['thumb']])
                            <p class="equipment-dropzone__text">Arrastra una imagen aqui <br>o <span class="equipment-dropzone__link">seleccionar archivo</span></p>
                        @endif
                        <input type="file" id="equipment_image" name="equipment_image" accept="image/*">
                    </label>
                </div>

                <div class="equipment-card">
                    <h3 class="equipment-card__title">Informacion general</h3>
                    <div class="rgrid-2" style="gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="equipment-field">
                            <label for="code">Codigo del equipo</label>
                            <input class="equipment-input" id="code" name="code" value="{{ old('code', $equipmentData['code']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="serial_number">Numero de serie</label>
                            <input class="equipment-input" id="serial_number" name="serial_number" value="{{ old('serial_number', $equipmentData['serial_number']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="name">Nombre del equipo</label>
                            <input class="equipment-input" id="name" name="name" value="{{ old('name', $equipmentData['name']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="brand">Marca *</label>
                            <select class="equipment-input equipment-select" id="brand" name="brand" data-selected="{{ old('brand', $equipmentData['brand']) }}" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                        <div class="equipment-field">
                            <label for="category">Tipo de equipo *</label>
                            <select class="equipment-input equipment-select" id="category" name="category" data-selected="{{ old('category', $equipmentData['category']) }}" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                        <div class="equipment-field">
                            <label for="model">Modelo *</label>
                            <select class="equipment-input equipment-select" id="model" name="model" data-selected="{{ old('model', $equipmentData['model']) }}" disabled required>
                                <option value="">Seleccionar marca primero</option>
                            </select>
                        </div>
                        <div class="equipment-field" style="grid-column:1 / -1;">
                            <label for="subcategory">Subtipo *</label>
                            <select class="equipment-input equipment-select" id="subcategory" name="subcategory" data-selected="{{ old('subcategory', $equipmentData['subcategory']) }}" disabled required>
                                <option value="">Seleccionar tipo primero</option>
                            </select>
                        </div>
                        <div class="equipment-field" style="grid-column:1 / -1;">
                            <label for="description">Descripcion</label>
                            <input class="equipment-input" id="description" name="description" value="{{ old('description', $equipmentData['description']) }}" type="text" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="rgrid-2" style="align-items:stretch;">
                <div class="equipment-card">
                    <h3 class="equipment-card__title">Control de existencias</h3>
                    <div class="rgrid-2" style="gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="equipment-field">
                            <label for="stock_current">Stock real</label>
                            <input class="equipment-input" id="stock_current" name="stock_current" value="{{ old('stock_current', $equipmentData['stock_current']) }}" type="number" min="0">
                        </div>
                        <div class="equipment-field">
                            <label for="stock_max">Maximo de existencias</label>
                            <input class="equipment-input" id="stock_max" name="stock_max" value="{{ old('stock_max', $equipmentData['stock_max']) }}" type="number" min="0">
                        </div>
                        <div class="equipment-field">
                            <label for="stock_min">Stock minimo</label>
                            <input class="equipment-input" id="stock_min" name="stock_min" value="{{ old('stock_min', $equipmentData['stock_min']) }}" type="number" min="0">
                        </div>
                        <div class="equipment-field">
                            <label for="warehouse">Almacen predeterminado *</label>
                            <select class="equipment-input equipment-select" id="warehouse" name="warehouse" required>
                                <option value="">Seleccionar</option>
                                <option value="Almacen Central" @selected(old('warehouse', $equipmentData['warehouse']) === 'Almacen Central')>Almacen Central</option>
                                <option value="Quirofano 1" @selected(old('warehouse', $equipmentData['warehouse']) === 'Quirofano 1')>Quirofano 1</option>
                                <option value="Quirofano 2" @selected(old('warehouse', $equipmentData['warehouse']) === 'Quirofano 2')>Quirofano 2</option>
                                <option value="Servicio tecnico" @selected(old('warehouse', $equipmentData['warehouse']) === 'Servicio tecnico')>Servicio tecnico</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="equipment-card">
                    <h3 class="equipment-card__title">Informacion adicional</h3>
                    <div class="rgrid-2" style="gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="equipment-field">
                            <label for="assigned_to">Responsable asignado</label>
                            <input class="equipment-input" id="assigned_to" name="assigned_to" value="{{ old('assigned_to', $equipmentData['assigned_to']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="department">Departamento</label>
                            <input class="equipment-input" id="department" name="department" value="{{ old('department', $equipmentData['department']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="service_date">Fecha de puesta en servicio</label>
                            <input class="equipment-input" id="service_date" name="service_date" value="{{ old('service_date', $equipmentData['service_date']) }}" type="date">
                        </div>
                        <div class="equipment-field">
                            <label for="next_maintenance">Proximo mantenimiento</label>
                            <input class="equipment-input" id="next_maintenance" name="next_maintenance" value="{{ old('next_maintenance', $equipmentData['next_maintenance']) }}" type="date">
                        </div>
                        <div class="equipment-field" style="grid-column:1 / -1;">
                            <label for="notes">Notas</label>
                            <textarea class="equipment-input equipment-note" id="notes" name="notes">{{ old('notes', $equipmentData['notes']) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rgrid-2" style="align-items:stretch;">
                <div class="equipment-card">
                    <h3 class="equipment-card__title">Informacion tecnica</h3>
                    <div class="rgrid-2" style="gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="equipment-field">
                            <label for="voltage">Voltaje</label>
                            <div class="equipment-unit">
                                <input class="equipment-input" id="voltage" name="voltage" value="{{ old('voltage', $equipmentData['voltage']) }}" type="number" min="0" step="0.1">
                                <span class="equipment-unit__suffix">V</span>
                            </div>
                        </div>
                        <div class="equipment-field">
                            <label for="dimensions">Dimensiones (cm)</label>
                            <div class="equipment-unit">
                                <input class="equipment-input" id="dimensions" name="dimensions" value="{{ old('dimensions', $equipmentData['dimensions']) }}" type="text" autocomplete="off">
                                <span class="equipment-unit__suffix">cm</span>
                            </div>
                        </div>
                        <div class="equipment-field">
                            <label for="frequency">Frecuencia</label>
                            <div class="equipment-unit">
                                <input class="equipment-input" id="frequency" name="frequency" value="{{ old('frequency', $equipmentData['frequency']) }}" type="number" min="0">
                                <span class="equipment-unit__suffix">Hz</span>
                            </div>
                        </div>
                        <div class="equipment-field">
                            <label for="color">Color</label>
                            <input class="equipment-input" id="color" name="color" value="{{ old('color', $equipmentData['color']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="power">Potencia</label>
                            <div class="equipment-unit">
                                <input class="equipment-input" id="power" name="power" value="{{ old('power', $equipmentData['power']) }}" type="number" min="0">
                                <span class="equipment-unit__suffix">W</span>
                            </div>
                        </div>
                        <div class="equipment-field">
                            <label for="weight">Peso (kg)</label>
                            <div class="equipment-unit">
                                <input class="equipment-input" id="weight" name="weight" value="{{ old('weight', $equipmentData['weight']) }}" type="number" min="0" step="0.01">
                                <span class="equipment-unit__suffix">kg</span>
                            </div>
                        </div>
                        <div class="equipment-field" style="grid-column:1 / -1;">
                            <label for="technical_specs">Especificaciones tecnicas</label>
                            <textarea class="equipment-input equipment-note" id="technical_specs" name="technical_specs">{{ old('technical_specs', $equipmentData['technical_specs']) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="equipment-card">
                    <h3 class="equipment-card__title">Proveedor</h3>
                    <div class="rgrid-2" style="gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="equipment-field">
                            <label for="supplier">Proveedor</label>
                            <input class="equipment-input" id="supplier" name="supplier" value="{{ old('supplier', $equipmentData['supplier']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="email">Correo electronico</label>
                            <input class="equipment-input" id="email" name="email" value="{{ old('email', $equipmentData['email']) }}" type="email" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="contact">Contacto</label>
                            <input class="equipment-input" id="contact" name="contact" value="{{ old('contact', $equipmentData['contact']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="invoice_number">Numero de factura</label>
                            <input class="equipment-input" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $equipmentData['invoice_number']) }}" type="text" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="phone">Telefono</label>
                            <input class="equipment-input" id="phone" name="phone" value="{{ old('phone', $equipmentData['phone']) }}" type="tel" autocomplete="off">
                        </div>
                        <div class="equipment-field">
                            <label for="invoice_date">Fecha de factura</label>
                            <input class="equipment-input" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $equipmentData['invoice_date']) }}" type="date">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const equipmentImagePreview = document.getElementById('equipmentImagePreview');
        const equipmentImageInput = document.getElementById('equipment_image');

        const categoryCatalog = @json($catalogs['types']);
        const brandCatalog = @json($catalogs['brands']);
        const NEW_OPTION = '__new__';

        function fillSelect(select, options, placeholder, addLabel) {
            const selected = select.dataset.selected || '';
            select.innerHTML = '';

            const first = document.createElement('option');
            first.value = '';
            first.textContent = placeholder;
            select.appendChild(first);

            options.forEach(function (value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                option.selected = value === selected;
                select.appendChild(option);
            });

            if (addLabel) {
                const addOption = document.createElement('option');
                addOption.value = NEW_OPTION;
                addOption.textContent = addLabel;
                select.appendChild(addOption);
            }
        }

        function bindCascade(config) {
            const parent = document.getElementById(config.parentId);
            const child = document.getElementById(config.childId);
            const catalog = config.catalog;

            if (!parent || !child) return;

            function renderParent() {
                fillSelect(parent, Object.keys(catalog), 'Seleccionar', config.addParentLabel);
            }

            function renderChild() {
                const hasParent = parent.value !== '' && parent.value !== NEW_OPTION;
                const options = hasParent ? (catalog[parent.value] || []) : [];
                fillSelect(child, options, hasParent ? 'Seleccionar' : config.emptyPlaceholder, hasParent ? config.addChildLabel : null);
                child.disabled = !hasParent;
            }

            parent.addEventListener('change', function () {
                if (parent.value === NEW_OPTION) {
                    const name = (prompt(config.promptParent) || '').trim();
                    if (name && !catalog[name]) catalog[name] = [];
                    parent.dataset.selected = name && catalog[name] ? name : '';
                    renderParent();
                } else {
                    parent.dataset.selected = parent.value;
                }
                child.dataset.selected = '';
                renderChild();
            });

            child.addEventListener('change', function () {
                if (child.value === NEW_OPTION) {
                    const name = (prompt(config.promptChild) || '').trim();
                    const options = catalog[parent.value] || [];
                    if (name && !options.includes(name)) {
                        options.push(name);
                        catalog[parent.value] = options;
                    }
                    child.dataset.selected = name && options.includes(name) ? name : '';
                    renderChild();
                } else {
                    child.dataset.selected = child.value;
                }
            });

            renderParent();
            renderChild();
        }

        bindCascade({
            parentId: 'category',
            childId: 'subcategory',
            catalog: categoryCatalog,
            emptyPlaceholder: 'Seleccionar tipo primero',
            addParentLabel: '+ Agregar nuevo tipo...',
            addChildLabel: '+ Agregar nuevo subtipo...',
            promptParent: 'Nombre del nuevo tipo de equipo:',
            promptChild: 'Nombre del nuevo subtipo:'
        });

        bindCascade({
            parentId: 'brand',
            childId: 'model',
            catalog: brandCatalog,
            emptyPlaceholder: 'Seleccionar marca primero',
            addParentLabel: '+ Agregar nueva marca...',
            addChildLabel: '+ Agregar nuevo modelo...',
            promptParent: 'Nombre de la nueva marca:',
            promptChild: 'Nombre del nuevo modelo:'
        });

        if (equipmentImageInput && equipmentImagePreview) {
            equipmentImageInput.addEventListener('change', function () {
                const file = equipmentImageInput.files && equipmentImageInput.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function () {
                    equipmentImagePreview.innerHTML = '';
                    const image = document.createElement('img');
                    image.src = reader.result;
                    image.alt = 'Vista previa del equipo';
                    equipmentImagePreview.appendChild(image);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
@endsection
