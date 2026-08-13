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
        'base_serial' => '',
        'brand' => '',
        'model' => '',
        'description' => '',
        'acquisition_date' => '',
        'registered_by' => '',
        'observations' => '',
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
    :root[data-theme="light"] .equipment-card { background: #ffffff; border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 8px 28px rgba(0,168,255,0.12), 0 0 14px rgba(0,168,255,0.18), inset 0 1px 0 rgba(255,255,255,0.6); }
    :root[data-theme="light"] .equipment-card__title { color: #00A8FF; }
    :root[data-theme="light"] .equipment-input { background: #ffffff; color: #1e1b4b; border-color: rgba(0,168,255,0.55); }
    :root[data-theme="light"] .equipment-input::placeholder { color: rgba(0,168,255,0.45); }
    :root[data-theme="light"] .equipment-input:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.35); }
    :root[data-theme="light"] .equipment-field label { color: #3730a3; }
    :root[data-theme="light"] .equipment-unit__suffix { background: #e8f2ff; color: #00A8FF; border-color: rgba(0,168,255,0.55); }
    :root[data-theme="light"] .equipment-dropzone { background: rgba(0,168,255,0.06); border: 1px dashed rgba(0,168,255,0.55); }
    :root[data-theme="light"] .equipment-dropzone__text { color: #3730a3; }
    :root[data-theme="light"] .equipment-dropzone__link { color: #00A8FF; }
    :root[data-theme="light"] .equipment-ghost { background: rgba(0,168,255,0.08); color: #00A8FF; border-color: rgba(0,168,255,0.55); }
    :root[data-theme="light"] .equipment-ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    .catalog-modal { position: fixed; inset: 0; z-index: 1000; display: none; }
    .catalog-modal__overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: grid; place-items: center; padding: 20px; }
    .catalog-modal__card { width: 100%; max-width: 360px; padding: 24px; border-radius: 14px; background: var(--surface); border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 20px rgba(0,168,255,0.35); }
    .catalog-modal__title { margin: 0 0 12px; color: var(--text); font-size: 1.1rem; font-weight: 800; }
    .catalog-modal__text { margin: 0 0 16px; color: var(--muted); font-size: 14px; }
    .catalog-modal__input { width: 100%; padding: 11px 14px; border: 1px solid rgba(0,168,255,0.55); border-radius: 10px; background: var(--surface); color: var(--text); font: inherit; font-size: 15px; box-sizing: border-box; }
    .catalog-modal__input:focus { outline: none; border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); }
    .catalog-modal__actions { display: flex; gap: 12px; margin-top: 18px; }
    .catalog-btn { display: inline-flex; align-items: center; justify-content: center; flex: 1; padding: 11px 22px; border-radius: 12px; background: linear-gradient(135deg, #00A8FF, #7C3AED); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition: all 0.2s ease; text-decoration: none; border: 1px solid rgba(0,168,255,0.55); }
    .catalog-btn:hover { filter: brightness(1.1); }
    .catalog-btn--ghost { background: rgba(0,168,255,0.12); color: #00A8FF; border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 10px rgba(0,168,255,0.15); }
    .catalog-btn--ghost:hover { background: rgba(0,168,255,0.22); border-color: #00A8FF; }
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
                <h3 class="equipment-card__title">Datos del equipo</h3>
                <div class="rgrid-2">
                    <div class="equipment-field">
                        <label for="category">Tipo de equipo *</label>
                        <select class="equipment-input equipment-select" id="category" name="category" data-selected="{{ old('category', $equipmentData['category']) }}" required>
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                    <div class="equipment-field">
                        <label for="brand">Marca *</label>
                        <select class="equipment-input equipment-select" id="brand" name="brand" data-selected="{{ old('brand', $equipmentData['brand']) }}" required>
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                    <div class="equipment-field">
                        <label for="subcategory">Subtipo *</label>
                        <select class="equipment-input equipment-select" id="subcategory" name="subcategory" data-selected="{{ old('subcategory', $equipmentData['subcategory']) }}" required>
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                    <div class="equipment-field">
                        <label for="model">Modelo *</label>
                        <select class="equipment-input equipment-select" id="model" name="model" data-selected="{{ old('model', $equipmentData['model']) }}" required>
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                    <div class="equipment-field" style="grid-column:1 / -1;">
                        <label for="serial_number">Numero de serie</label>
                        <input class="equipment-input" id="serial_number" name="serial_number" value="{{ old('serial_number', $equipmentData['serial_number']) }}" type="text" autocomplete="off">
                    </div>
                    <div class="equipment-field" style="grid-column:1 / -1;">
                        <label>Serie base</label>
                        <div id="base_serial_preview" class="equipment-input" style="display:flex; align-items:center; min-height:46px; background:rgba(8,18,40,0.55); color:#00A8FF; font-weight:700;">—</div>
                    </div>
                    <div class="equipment-field" style="grid-column:1 / -1;">
                        <label for="description">Descripcion</label>
                        <input class="equipment-input" id="description" name="description" value="{{ old('description', $equipmentData['description']) }}" type="text" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="equipment-card">
                <h3 class="equipment-card__title">Registro</h3>
                <div class="rgrid-2">
                    <div class="equipment-field">
                        <label for="acquisition_date">Fecha de adquisicion</label>
                        <input class="equipment-input" id="acquisition_date" name="acquisition_date" value="{{ old('acquisition_date', $equipmentData['acquisition_date']) }}" type="date">
                    </div>
                    <div class="equipment-field" style="grid-column:1 / -1;">
                        <label for="observations">Observaciones</label>
                        <textarea class="equipment-input equipment-note" id="observations" name="observations">{{ old('observations', $equipmentData['observations']) }}</textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="catalogModal" class="catalog-modal">
        <div class="catalog-modal__overlay">
            <div class="catalog-modal__card">
                <h3 id="catalogModalTitle" class="catalog-modal__title">Agregar</h3>
                <p id="catalogModalText" class="catalog-modal__text">Ingresa el nombre:</p>
                <input type="text" id="catalogModalInput" class="catalog-modal__input" placeholder="Nombre" autocomplete="off">
                <div class="catalog-modal__actions">
                    <button type="button" id="catalogModalCancel" class="catalog-btn catalog-btn--ghost">Cancelar</button>
                    <button type="button" id="catalogModalAccept" class="catalog-btn">Aceptar</button>
                </div>
            </div>
        </div>
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

        function bindCatalogSelect(config) {
            const select = document.getElementById(config.selectId);
            const catalog = config.catalog;
            if (!select) return;

            function render(options) {
                fillSelect(select, options, 'Seleccionar', config.addLabel);
            }

            select.addEventListener('change', function () {
                if (select.value === NEW_OPTION) {
                    openCatalogModal(config.prompt, 'Ingresa el nombre y presiona Aceptar.', function (name) {
                        name = (name || '').trim();
                        if (name && !catalog.includes(name)) {
                            catalog.push(name);
                        }
                        select.dataset.selected = name && catalog.includes(name) ? name : '';
                        render(catalog);
                    });
                } else {
                    select.dataset.selected = select.value;
                }
            });

            render(catalog);
        }

        let catalogModalResolve = null;
        const catalogModal = document.getElementById('catalogModal');
        const catalogModalTitle = document.getElementById('catalogModalTitle');
        const catalogModalText = document.getElementById('catalogModalText');
        const catalogModalInput = document.getElementById('catalogModalInput');

        function openCatalogModal(title, text, callback) {
            catalogModalTitle.textContent = title;
            catalogModalText.textContent = text;
            catalogModalInput.value = '';
            catalogModalResolve = callback;
            catalogModal.style.display = 'block';
            catalogModalInput.focus();
        }

        function closeCatalogModal(accepted) {
            catalogModal.style.display = 'none';
            if (catalogModalResolve) {
                catalogModalResolve(accepted ? catalogModalInput.value : null);
                catalogModalResolve = null;
            }
        }

        document.getElementById('catalogModalAccept').addEventListener('click', function () { closeCatalogModal(true); });
        document.getElementById('catalogModalCancel').addEventListener('click', function () { closeCatalogModal(false); });
        catalogModal.querySelector('.catalog-modal__overlay').addEventListener('click', function (e) {
            if (e.target === e.currentTarget) closeCatalogModal(false);
        });
        catalogModalInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') closeCatalogModal(true);
        });

        const allSubtypes = Object.values(categoryCatalog).flat();
        const allModels = Object.values(brandCatalog).flat();

        bindCatalogSelect({
            selectId: 'category',
            catalog: Object.keys(categoryCatalog),
            addLabel: '+ Agregar nuevo tipo...',
            prompt: 'Nombre del nuevo tipo de equipo:'
        });

        bindCatalogSelect({
            selectId: 'brand',
            catalog: Object.keys(brandCatalog),
            addLabel: '+ Agregar nueva marca...',
            prompt: 'Nombre de la nueva marca:'
        });

        bindCatalogSelect({
            selectId: 'subcategory',
            catalog: allSubtypes,
            addLabel: '+ Agregar nuevo subtipo...',
            prompt: 'Nombre del nuevo subtipo:'
        });

        bindCatalogSelect({
            selectId: 'model',
            catalog: allModels,
            addLabel: '+ Agregar nuevo modelo...',
            prompt: 'Nombre del nuevo modelo:'
        });

        const baseSerialPreview = document.getElementById('base_serial_preview');

        function updateBaseSerial() {
            if (!baseSerialPreview) return;
            const type = document.getElementById('category')?.value || '';
            const brand = document.getElementById('brand')?.value || '';
            const model = document.getElementById('model')?.value || '';
            const serial = document.getElementById('serial_number')?.value || '';

            const clean = function (value) {
                return value.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase();
            };

            const baseSerial = [clean(type), clean(brand), clean(model), clean(serial)].filter(Boolean).join('-');
            baseSerialPreview.textContent = baseSerial || '—';
        }

        ['category', 'brand', 'model', 'serial_number'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updateBaseSerial);
        });

        document.getElementById('catalogModalAccept')?.addEventListener('click', function () {
            setTimeout(updateBaseSerial, 50);
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

        updateBaseSerial();
    </script>
@endsection
