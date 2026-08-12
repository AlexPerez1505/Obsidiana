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
    .equipment-form { max-width: 1600px; margin: 0 auto; }
    .equipment-form .rgrid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    @media (max-width: 520px) { .equipment-form .rgrid-2 { grid-template-columns: 1fr; } }
    #equipment-form {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        align-items: stretch;
    }
    @media (max-width: 1200px) { #equipment-form { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 820px) { #equipment-form { grid-template-columns: 1fr; } }
    .equipment-card {
        padding: 18px;
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(8,18,40,0.88), rgba(4,12,30,0.88));
        border: 1px solid rgba(0,168,255,0.55);
        box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(0,168,255,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
        display: flex;
        flex-direction: column;
    }
    .equipment-card__title { font-size: 16px; font-weight: 700; margin: 0 0 14px; color: #00A8FF; }
    .equipment-field { display: grid; gap: 6px; }
    .equipment-field label { font-size: 14px; font-weight: 600; color: #fff; }
    .equipment-input {
        width: 100%;
        padding: 11px 14px;
        border: 1px solid rgba(0,168,255,0.55);
        border-radius: 10px;
        background: rgba(4,10,24,0.72);
        color: #fff;
        font: inherit;
        font-size: 15px;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .equipment-input:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); }
    .equipment-input::placeholder { color: rgba(255,255,255,0.4); }
    .equipment-input:disabled { opacity: 0.55; cursor: not-allowed; }
    .equipment-select { appearance: none; cursor: pointer; }
    .equipment-unit { display: grid; grid-template-columns: 1fr auto; align-items: center; }
    .equipment-unit .equipment-input { border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .equipment-unit__suffix {
        padding: 11px 14px;
        border: 1px solid rgba(0,168,255,0.55);
        border-left: 0;
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
        background: rgba(8,18,40,0.7);
        color: #00A8FF;
        font-size: 13px;
        font-weight: 700;
    }
    .equipment-dropzone {
        flex: 1;
        min-height: 180px;
        border: 1px dashed rgba(0,168,255,0.55);
        border-radius: 12px;
        background: rgba(4,10,24,0.6);
        display: grid;
        place-items: center;
        padding: 16px;
        text-align: center;
        cursor: pointer;
    }
    .equipment-dropzone img { max-width: 100%; max-height: 140px; object-fit: contain; }
    .equipment-dropzone svg { max-width: 110px; max-height: 110px; }
    .equipment-dropzone__text { color: rgba(255,255,255,0.55); font-size: 13px; font-weight: 700; margin-top: 10px; }
    .equipment-dropzone__link { color: #00A8FF; cursor: pointer; text-decoration: none; }
    .equipment-dropzone input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .equipment-actions { display: flex; justify-content: flex-end; gap: 12px; margin: 2px 0 8px; }
    .equipment-note { width: 100%; min-height: 80px; resize: vertical; }
    .equipment-btn {
        background: linear-gradient(135deg, #00A8FF, #7C3AED);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.15);
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2);
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .equipment-btn:hover { filter: brightness(1.1); }
    .equipment-ghost {
        padding: 10px 18px;
        border: 1px solid rgba(0,168,255,0.55);
        border-radius: 12px;
        background: rgba(8,18,40,0.45);
        color: #00A8FF;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background .16s ease, border-color .16s ease;
    }
    .equipment-ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    :root[data-theme="light"] .equipment-card { background: linear-gradient(145deg, rgba(15,23,42,0.04), rgba(15,23,42,0.08)); border-color: rgba(15,23,42,0.14); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    :root[data-theme="light"] .equipment-input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.18); }
    :root[data-theme="light"] .equipment-input::placeholder { color: var(--muted); }
    :root[data-theme="light"] .equipment-field label { color: var(--text); }
    :root[data-theme="light"] .equipment-unit__suffix { background: rgba(15,23,42,0.06); color: var(--text); border-color: rgba(15,23,42,0.18); }
    :root[data-theme="light"] .equipment-dropzone { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); }
    :root[data-theme="light"] .equipment-dropzone__text { color: var(--muted); }
    :root[data-theme="light"] .equipment-dropzone__link { color: #00A8FF; }
    :root[data-theme="light"] .equipment-ghost { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); color: var(--text); }
</style>
@endpush

@section('content')
    <div class="equipment-form">
        <div class="equipment-actions">
            <a href="{{ route('inventory.equipos.index') }}" class="equipment-ghost" style="text-decoration:none;">Volver</a>
            <button type="submit" class="equipment-btn" form="equipment-form">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar equipo
            </button>
        </div>

        <form id="equipment-form" method="POST" action="{{ $isEdit ? route('inventory.equipos.update', ['equipo' => $equipmentData['code']]) : route('inventory.equipos.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            @if ($errors->any())
                <x-ui.card style="grid-column: 1 / -1; border-color:#ef4444; color:#ef4444; padding:14px;">
                    <strong>Revisa los siguientes campos:</strong>
                    <ul style="margin:6px 0 0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

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
                <div class="rgrid-2">
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

            <div class="equipment-card">
                <h3 class="equipment-card__title">Control de existencias</h3>
                <div class="rgrid-2">
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
                <div class="rgrid-2">
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

            <div class="equipment-card">
                <h3 class="equipment-card__title">Informacion tecnica</h3>
                <div class="rgrid-2">
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
                <div class="rgrid-2">
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
