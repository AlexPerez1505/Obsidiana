@extends('layouts.dashboard')
@section('title', 'Agregar Producto')
@section('page-title', 'Agregar Producto')
@section('page-sub', 'Registra un nuevo equipo en el inventario')

@section('content')
    @php
        $equipmentTypes = [
            'endoscopia',
            'refacciones de endoscopia',
            'laparoscopia',
            'quirófano',
            'hospitalización',
            'material',
            'otorrinolaringologia',
            'radiología',
            'urología',
            'artroscopia',
            'ceye',
            'ginecología',
            'Endoscopia Veterinaria',
        ];

        $defaultSubtypesByEquipmentType = array_fill_keys($equipmentTypes, []);
        $defaultSubtypesByEquipmentType['endoscopia'] = [
            'Adaptador',
            'Argon de plasma',
            'Balance de blancos',
            'Bomba de Irrigación',
            'Bomba de Secreción',
            'Bombad de CO2',
            'Broncoscopio',
            'Cable',
            'Cable Bipolar',
            'Cable Monopolar',
            'Capturador de video',
            'Capuchon Distal',
            'Carro',
            'Caja de Almacenamiento',
            'Cepillo de limpieza',
            'Cepillos de limpieza',
            'Colonoscopio',
            'Conjunto de Irrigación',
            'Contenedor de liquidos',
            'Comvertidor de video',
            'Duodenoscopio',
            'Eliminador',
            'Foco',
            'Fuente de luz',
            'Gastroscopio',
            'Grabador',
            'Interfas',
            'Instrumento endosciopico',
            'Interfaz Monopolar para Erbe',
            'Kit de limpieza',
            'Linea de Irrigación',
            'Manguera Yugo',
            'Monitor',
            'Mouse',
            'Multicontacto',
            'PC SIIMED Analogo',
            'PC SIIMED HD',
            'Pedal',
            'Pilas',
            'Pigtail',
            'Pinzas de Endoscopia',
            'Probador de Fuga',
            'Procesador',
            'Protector Bucal para Endoscopio',
            'Protector de Punta de Endoscopio',
            'Protector de Argon de Endoscopio',
            'Sonda Para Argon',
            'Sistema Endoscopia',
            'Tapon de Biopsia',
            'Tapon-EPO',
            'Tanque de Argon',
            'Teclado',
            'Tuallitas humedas',
            'Transductor para USG-400',
            'Remoto para Endoscopios',
            'Solucion desinfectante',
            'Solucion detergente',
            'Valvulas desechables',
            'Valvulas Reusables',
        ];
    @endphp

    @php
        // Agrega aqui nuevas opciones para que aparezcan en las barras desplegables.
        $savedOptions = $productoOptions ?? [];
        $optionsFor = fn (string $key, array $defaults = []) => collect($defaults)
            ->merge($savedOptions[$key] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $savedSubtypesByEquipmentType = $savedOptions['subtypes_by_type'] ?? [];
        $subtypeTypeKeys = collect($equipmentTypes)
            ->merge(array_keys($defaultSubtypesByEquipmentType))
            ->merge(array_keys($savedSubtypesByEquipmentType))
            ->unique()
            ->values();

        $subtypesByEquipmentType = $subtypeTypeKeys
            ->mapWithKeys(fn ($equipmentType) => [
                $equipmentType => collect($defaultSubtypesByEquipmentType[$equipmentType] ?? [])
                    ->merge($savedSubtypesByEquipmentType[$equipmentType] ?? [])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->all();

        $allSubtypeOptions = collect($subtypesByEquipmentType)
            ->flatten()
            ->merge($savedOptions['subtipo'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $productOptionFieldsBeforeNumbers = [
            [
                'label' => 'Tipo de Equipo *',
                'name' => 'tipo_equipo',
                'placeholder' => 'Ej. Endoscopio',
                'required' => true,
                'options' => $optionsFor('tipo_equipo', $equipmentTypes),
            ],
            [
                'label' => 'Subtipo',
                'name' => 'subtipo',
                'placeholder' => 'Ej. Flexible',
                'options' => $allSubtypeOptions,
            ],
            [
                'label' => 'Marca',
                'name' => 'marca',
                'placeholder' => 'Ej. Olympus',
                'options' => $optionsFor('marca', [
                    'Olympus',
                    'Fujifilm',
                    'Pentax',
                    'Karl Storz',
                    'Stryker',
                    'Mindray',
                ]),
            ],
            [
                'label' => 'Modelo',
                'name' => 'modelo',
                'placeholder' => 'Ej. GIF-H190',
                'options' => $optionsFor('modelo', [
                    'GIF-H190',
                    'CF-H190',
                    'PCF-H190',
                    'EG-760R',
                ]),
            ],
        ];

        $productOptionFieldsAfterNumbers = [
            [
                'label' => 'Proveedor',
                'name' => 'proveedor',
                'placeholder' => 'Nombre del proveedor',
                'options' => $optionsFor('proveedor'),
            ],
        ];
    @endphp

    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data" style="max-width:720px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                @foreach ($productOptionFieldsBeforeNumbers as $field)
                    <x-ui.form-group :label="$field['label']" :for="$field['name']">
                        <input
                            id="{{ $field['name'] }}"
                            type="text"
                            name="{{ $field['name'] }}"
                            list="{{ $field['name'] }}_options"
                            value="{{ old($field['name']) }}"
                            placeholder="{{ $field['placeholder'] }}"
                            autocomplete="off"
                            {{ ! empty($field['required']) ? 'required' : '' }}>

                        <datalist id="{{ $field['name'] }}_options">
                            @foreach ($field['options'] as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                    </x-ui.form-group>
                @endforeach
                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" placeholder="0.00" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" placeholder="0" :required="true" />
                @foreach ($productOptionFieldsAfterNumbers as $field)
                    <x-ui.form-group :label="$field['label']" :for="$field['name']">
                        <input
                            id="{{ $field['name'] }}"
                            type="text"
                            name="{{ $field['name'] }}"
                            list="{{ $field['name'] }}_options"
                            value="{{ old($field['name']) }}"
                            placeholder="{{ $field['placeholder'] }}"
                            autocomplete="off">

                        <datalist id="{{ $field['name'] }}_options">
                            @foreach ($field['options'] as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                    </x-ui.form-group>
                @endforeach
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
                const subtypeOptionsByType = @json($subtypesByEquipmentType);
                const typeInput = document.getElementById('tipo_equipo');
                const subtypeInput = document.getElementById('subtipo');
                const subtypeList = document.getElementById('subtipo_options');

                if (!typeInput || !subtypeInput || !subtypeList) {
                    return;
                }

                const normalize = function (value) {
                    return (value || '').trim().toLocaleLowerCase();
                };

                const optionsForType = function (value) {
                    if (subtypeOptionsByType[value]) {
                        return subtypeOptionsByType[value];
                    }

                    const normalizedValue = normalize(value);
                    const matchingKey = Object.keys(subtypeOptionsByType).find(function (key) {
                        return normalize(key) === normalizedValue;
                    });

                    return matchingKey ? subtypeOptionsByType[matchingKey] : [];
                };

                const renderSubtypeOptions = function () {
                    const options = optionsForType(typeInput.value);

                    subtypeList.innerHTML = '';
                    options.forEach(function (option) {
                        const item = document.createElement('option');
                        item.value = option;
                        subtypeList.appendChild(item);
                    });

                    subtypeInput.placeholder = options.length
                        ? 'Selecciona o escribe un subtipo'
                        : 'Escribe un subtipo';
                };

                typeInput.addEventListener('input', renderSubtypeOptions);
                typeInput.addEventListener('change', renderSubtypeOptions);
                renderSubtypeOptions();

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
