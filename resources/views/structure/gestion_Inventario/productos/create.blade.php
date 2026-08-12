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
            'Argón Plasma',
            'Balance de Blancos',
            'Bomba de Irrigación',
            'Bomba de Secreción',
            'Bomba de CO2',
            'Broncoscopio',
            'Cable',
            'Cable Bipolar',
            'Cable Monopolar',
            'Capturador de Video',
            'Capuchón Distal',
            'Carro',
            'Caja de almacenamiento',
            'Cepillo de Limpieza',
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
        $defaultSubtypesByEquipmentType['refacciones de endoscopia'] = [
            'Tubo de inserción',
            'Tubo de guía de luz',
            'Camisa para líneas ensamblado',
            'Camisa para lineas Ensamblada M',
            'Canal de biopsia',
            'Canales de biopsia M',
            'C-Cover M',
            'Fibras de luz P',
            'Fibras de luz M',
            'Perillas de control U/D R/L',
            'Conector eléctrico',
            'Sección flexible ensamblada',
            'Pipeta Aire/Agua P',
            'Adhesivos epóxico',
            'Tornillería',
            'Rubber M',
            'Sección de flexión Ensamblado M',
            'Líneas de angulación M',
            'Tubos de inserción M',
            'Cable guía de luz M',
            'Stopper del cable de angulación M',
            'Pipeta aire/agua M',
            'Body Grip ME',
            'Camisa para lineas Ensamblado ME',
            'Canal de biopsia ME',
            'Rubber ME',
            'Rubber GM',
            'Sección flexible Ensamblada ME',
            'Drums (Poleas) ME',
            'Canal de aire/agua ME',
            'Tarjeta Electrica ME',
            'Membrana Flexible CCD OEM ME',
            'Tornillería ME',
            'Tubos de inserción ME',
            'Tubo Universal ME',
            'Stopper del cable de control ME',
            'Poleas de control U/D R/L ME',
            'Oring ME',
            'Perillas de control U/D R/L E',
            'Freno de perillas U/D R/L E',
            'Membrana Flexible CCD OEM E',
            'Conector Electrico E',
            'Botonera E',
            'Cubierta de Perillas de control E',
            'Tuerca E',
            'Oring E',
            'Fibra de luz ME',
        ];
        $defaultSubtypesByEquipmentType['laparoscopia'] = [
            'Adaptador',
            'Cabezal',
            'Cable Interfaz 1688',
            'Cable Interfaz USB 1588',
            'Cable Bipolar',
            'Cámara',
            'Case de Transporte',
            'Charola de Esterilización',
            'Clarity',
            'Clips para Monitor',
            'Funda para Cámara',
            'Eliminador',
            'Fibra de Luz',
            'Fuente de Luz',
            'Grabador',
            'Instrumental de Laparoscopia',
            'Insuflador',
            'Lente',
            'Manguera de Insuflación',
            'Manguera para Bomba de Agua',
            'Monitor Grado Médico',
            'Parche para Electrocauterio',
            'Pedestal',
            'Pieza de Mano',
            'Pinza',
            'Porta tanque',
            'Transmisor',
            'Trocar',
            'Receptor',
            'Video Carro',
            'Video Grabador',
            'Remotos',
        ];
        $defaultSubtypesByEquipmentType['quirófano'] = [
            'Adaptador',
            'Adaptador para Ligasure',
            'Adaptador para Armonico',
            'Armónico Gen11',
            'Bipap',
            'Brazalete Pani',
            'Bomba de Infusion',
            'Cable Para Pinza Bipolar',
            'Cable Trocal ECG',
            'Cable Interfaz',
            'Carro para Electrocauterio',
            'Carro Rojo Emergencias',
            'CharoLa de Esterilizacion',
            'Circuito de Paciente',
            'Desfribilador',
            'Electrocauterio',
            'Eliminador',
            'Evacuador de Humo',
            'Lámpara de Quirófano',
            'Lapíz para Electrocauterio',
            'Ligasure LS8',
            'Línea de Muestreo de CO2',
            'Laringoscopio',
            'Máquina de Anestesia',
            'Mesa de Cirugía',
            'Monitor Signos Vitales',
            'Oximetro',
            'Pedal Bipolar',
            'Pedal Ligasure',
            'Pedal Monopolar',
            'Pieza de Mano Para Gen11',
            'Placa para Electrocauterio',
            'Sensor de ECG',
            'Sensor de SPO2',
            'Sensor PANI',
            'Sensor de Temperatura',
            'UPS',
            'Vaporizador',
        ];
        $defaultSubtypesByEquipmentType['hospitalización'] = [
            'Aspirador',
            'Cama Hospitalaria Eléctrica',
            'Camilla',
            'Cuna Térmica',
            'Incubadora',
            'Mesa de Exploración',
            'Ventilador',
        ];
        $defaultSubtypesByEquipmentType['material'] = [
            'Limpiador y Desengrasante',
            'Playon',
        ];
        $defaultSubtypesByEquipmentType['otorrinolaringologia'] = [
            'Microdebrilador',
            'Pedal Microdebrilador',
            'Pieza de Mano',
            'Electrocirugia',
            'Pedal',
        ];
        $defaultSubtypesByEquipmentType['radiología'] = [
            'Arco en C',
            'Batería',
            'Chasis',
            'Flat Panel',
            'Rayos X Rodable',
            'Rayos X Portatil',
        ];
        $defaultSubtypesByEquipmentType['urología'] = [
            'Cistoscopio',
            'Histeroscopio',
            'Resectoscopio',
            'Ureteroscopio Flexible',
            'Ureteroscopio Rigido',
        ];
        $defaultSubtypesByEquipmentType['artroscopia'] = [
            'Artroscopio',
            'Bomba de Irrigación',
            'Camisa',
            'Opturador',
            'Cable para pedal',
            'Cable para pieza de mano',
            'Charola de Esterilización',
            'Endogia',
            'Hoja de Sierra Sagital',
            'Pieza de Mano',
            'Pedal',
            'Puntas de Radio Frecuencia',
            'Puntas Serfas de radiofrecuencia',
            'Rasurador Shaver',
            'Radio Frecuencia Serfas',
            'Set de Taladros de Artroscopia System 4',
            'Set de Taladros de Artroscopia System 7',
            'Set de Taladros de Artroscopia System 8',
            'Set de Taladros Electrico Core Azul',
            'Set de Taladros Electrico Core Negro',
            'Transmisores',
            'Set de Cirugia Para Tobillo y Muñeca',
            'Set de Cirugía de Rodilla',
            'Meditronic',
            'Línea de Irrigación',
        ];
        $defaultSubtypesByEquipmentType['ceye'] = [
            'Autoclave de cámara 95 L',
            'Monitor',
        ];
        $defaultSubtypesByEquipmentType['ginecología'] = [
            'Camilla Ginecológica',
            'Mesa de Exploración',
            'Ultrasonido',
            'Impresora',
        ];
        $defaultSubtypesByEquipmentType['Endoscopia Veterinaria'] = [
            'Gastroscopio Veterinaria',
            'Colonoscopio Veterinaria',
            'Procesador Veterinaria',
            'Monitor de Imagen',
            'Coledoscopio Veterinaria',
            'Cabezal Veterinaria',
            'Tapon de Biopsia',
            'Valvula de Succion',
            'Valvula de aire/agua',
            'Tapon de Inmersion',
            'Probador de Fuga',
            'Kit de Limpieza',
            'Adaptador de Limpieza de Succion',
            'Adaptador de limpieza del canal de aire/agua',
            'Tapon de Canal',
            'Cepillo de Limpieza de la apertura del canal',
            'Cepillo de Limpieza del Canal',
        ];

        $defaultBrandsByEquipmentSubtype = [
            'endoscopia' => [
                'Adaptador' => [
                    'Valleylab',
                    'Erbe',
                    'GM',
                    'Cerofict',
                ],
                'Argón Plasma' => [
                    'Erbe',
                ],
                'Argon de plasma' => [
                    'Erbe',
                ],
                'Balance de Blancos' => [
                    'Olympus',
                ],
                'Balance de blancos' => [
                    'Olympus',
                ],
                'Bomba de Irrigación' => [
                    'Olympus',
                    'Medivators',
                    'Erbe',
                ],
                'Bomba de Secreción' => [
                    'Infusomat',
                ],
                'Bomba de CO2' => [
                    'Fujinon',
                    'Olympus',
                ],
                'Bombad de CO2' => [
                    'Fujinon',
                    'Olympus',
                ],
                'Broncoscopio' => [
                    'Olympus',
                ],
                'Cable' => [
                    'GM',
                ],
                'Cable Bipolar' => [
                    'Olympus',
                ],
            ],
        ];
    @endphp

    @php
        // Agrega aqui nuevas opciones para que aparezcan en las barras desplegables.
        $savedOptions = $productoOptions ?? [];
        $productCatalog = $productCatalog ?? [];
        $catalogSubtypesByEquipmentType = $productCatalog['subtypes_by_type'] ?? [];
        $catalogModelsByEquipmentSubtypeBrand = $productCatalog['models_by_type_subtype_brand'] ?? [];
        $equipmentTypes = collect($equipmentTypes)
            ->merge(array_keys($catalogSubtypesByEquipmentType))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($catalogSubtypesByEquipmentType as $equipmentType => $subtypes) {
            $defaultSubtypesByEquipmentType[$equipmentType] = collect($defaultSubtypesByEquipmentType[$equipmentType] ?? [])
                ->merge(is_array($subtypes) ? $subtypes : [])
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

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

        $catalogBrandsByEquipmentSubtype = collect($catalogModelsByEquipmentSubtypeBrand)
            ->mapWithKeys(fn ($subtypes, $equipmentType) => [
                $equipmentType => collect(is_array($subtypes) ? $subtypes : [])
                    ->mapWithKeys(fn ($brands, $subtype) => [
                        $subtype => is_array($brands) ? array_keys($brands) : [],
                    ])
                    ->all(),
            ])
            ->all();

        $savedBrandsByEquipmentSubtype = $savedOptions['brands_by_type_and_subtype'] ?? [];
        $brandTypeKeys = collect(array_keys($defaultBrandsByEquipmentSubtype))
            ->merge(array_keys($catalogBrandsByEquipmentSubtype))
            ->merge(array_keys($savedBrandsByEquipmentSubtype))
            ->unique()
            ->values();

        $brandsByEquipmentSubtype = $brandTypeKeys
            ->mapWithKeys(function ($equipmentType) use ($defaultBrandsByEquipmentSubtype, $catalogBrandsByEquipmentSubtype, $savedBrandsByEquipmentSubtype) {
                $defaultSubtypeBrands = $defaultBrandsByEquipmentSubtype[$equipmentType] ?? [];
                $catalogSubtypeBrands = $catalogBrandsByEquipmentSubtype[$equipmentType] ?? [];
                $savedSubtypeBrands = $savedBrandsByEquipmentSubtype[$equipmentType] ?? [];
                $subtypeKeys = collect(array_keys($defaultSubtypeBrands))
                    ->merge(array_keys($catalogSubtypeBrands))
                    ->merge(array_keys($savedSubtypeBrands))
                    ->unique()
                    ->values();

                return [
                    $equipmentType => $subtypeKeys
                        ->mapWithKeys(fn ($subtype) => [
                            $subtype => collect($defaultSubtypeBrands[$subtype] ?? [])
                                ->merge($catalogSubtypeBrands[$subtype] ?? [])
                                ->merge($savedSubtypeBrands[$subtype] ?? [])
                                ->filter()
                                ->unique()
                                ->values()
                                ->all(),
                        ])
                        ->all(),
                ];
            })
            ->all();

        $catalogBrandOptions = collect($catalogBrandsByEquipmentSubtype)
            ->flatMap(fn ($subtypeBrands) => collect($subtypeBrands)->flatten())
            ->filter()
            ->unique()
            ->values()
            ->all();

        $defaultBrandOptions = collect($defaultBrandsByEquipmentSubtype)
            ->flatMap(fn ($subtypeBrands) => collect($subtypeBrands)->flatten())
            ->merge($catalogBrandOptions)
            ->merge([
                'Olympus',
                'Fujifilm',
                'Pentax',
                'Karl Storz',
                'Stryker',
                'Mindray',
            ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $catalogModelOptions = collect($catalogModelsByEquipmentSubtypeBrand)
            ->flatMap(fn ($subtypes) => collect(is_array($subtypes) ? $subtypes : [])
                ->flatMap(fn ($brands) => collect(is_array($brands) ? $brands : [])->flatten()))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $defaultModelOptions = collect([
            'GIF-H190',
            'CF-H190',
            'PCF-H190',
            'EG-760R',
        ])
            ->merge($catalogModelOptions)
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
                'label' => 'Marca *',
                'name' => 'marca',
                'placeholder' => 'Ej. Olympus',
                'required' => true,
                'options' => $optionsFor('marca', $defaultBrandOptions),
            ],
            [
                'label' => 'Modelo',
                'name' => 'modelo',
                'placeholder' => 'Ej. GIF-H190',
                'options' => $optionsFor('modelo', $defaultModelOptions),
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

    <style>
        .product-combobox {
            position: relative;
        }

        .product-combobox input {
            padding-right: 42px;
        }

        .product-combo-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            display: inline-grid;
            place-items: center;
            border: 0;
            background: transparent;
            color: #9aa3af;
            cursor: pointer;
            padding: 0;
        }

        .product-combo-toggle:hover {
            color: #667085;
        }

        .product-option-list {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            z-index: 60;
            display: none;
            max-height: 330px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #6f7782;
            border-radius: 0;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .12);
            color: #111827;
        }

        .product-option-list.is-open {
            display: block;
        }

        .product-option-list::-webkit-scrollbar {
            width: 14px;
        }

        .product-option-list::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .product-option-list::-webkit-scrollbar-thumb {
            background: #8a8a8a;
            border: 3px solid #f8fafc;
            border-radius: 999px;
        }

        .product-option-item {
            display: block;
            width: 100%;
            border: 0;
            background: #fff;
            color: #111827;
            text-align: left;
            padding: 7px 10px;
            font: inherit;
            line-height: 1.35;
            cursor: pointer;
        }

        .product-option-item:hover,
        .product-option-item.is-active {
            background: #7c7c7c;
            color: #fff;
        }
    </style>

    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data" style="max-width:720px;">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del Producto</x-ui.section-title>
            <div class="rgrid-2">
                @foreach ($productOptionFieldsBeforeNumbers as $field)
                    <x-ui.form-group :label="$field['label']" :for="$field['name']">
                        <div class="product-combobox" data-product-combobox>
                            <input
                                id="{{ $field['name'] }}"
                                type="text"
                                name="{{ $field['name'] }}"
                                value="{{ old($field['name']) }}"
                                placeholder="{{ $field['placeholder'] }}"
                                autocomplete="off"
                                data-combo-input
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="{{ $field['name'] }}_options"
                                {{ ! empty($field['required']) ? 'required' : '' }}>
                            <button type="button" class="product-combo-toggle" data-combo-toggle aria-label="Mostrar opciones">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div id="{{ $field['name'] }}_options" class="product-option-list" role="listbox">
                                @foreach ($field['options'] as $option)
                                    <button type="button" class="product-option-item" role="option" data-value="{{ $option }}">{{ $option }}</button>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.form-group>
                @endforeach
                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" placeholder="0.00" :required="true" />
                <x-ui.form-group label="Stock *" name="stock" type="number" min="0" placeholder="0" :required="true" />
                @foreach ($productOptionFieldsAfterNumbers as $field)
                    <x-ui.form-group :label="$field['label']" :for="$field['name']">
                        <div class="product-combobox" data-product-combobox>
                            <input
                                id="{{ $field['name'] }}"
                                type="text"
                                name="{{ $field['name'] }}"
                                value="{{ old($field['name']) }}"
                                placeholder="{{ $field['placeholder'] }}"
                                autocomplete="off"
                                data-combo-input
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="{{ $field['name'] }}_options">
                            <button type="button" class="product-combo-toggle" data-combo-toggle aria-label="Mostrar opciones">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div id="{{ $field['name'] }}_options" class="product-option-list" role="listbox">
                                @foreach ($field['options'] as $option)
                                    <button type="button" class="product-option-item" role="option" data-value="{{ $option }}">{{ $option }}</button>
                                @endforeach
                            </div>
                        </div>
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
                const brandOptionsByTypeAndSubtype = @json($brandsByEquipmentSubtype);
                const modelOptionsByTypeSubtypeBrand = @json($catalogModelsByEquipmentSubtypeBrand);
                const allBrandOptions = @json($optionsFor('marca', $defaultBrandOptions));
                const typeInput = document.getElementById('tipo_equipo');
                const subtypeInput = document.getElementById('subtipo');
                const subtypeList = document.getElementById('subtipo_options');
                const brandInput = document.getElementById('marca');
                const brandList = document.getElementById('marca_options');
                const modelInput = document.getElementById('modelo');
                const modelList = document.getElementById('modelo_options');

                if (!typeInput || !subtypeInput || !subtypeList) {
                    return;
                }

                const normalize = function (value) {
                    return (value || '')
                        .trim()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLocaleLowerCase();
                };

                const matchingKey = function (map, value) {
                    if (!map) {
                        return '';
                    }

                    if (Object.prototype.hasOwnProperty.call(map, value)) {
                        return value;
                    }

                    const normalizedValue = normalize(value);

                    return Object.keys(map).find(function (key) {
                        return normalize(key) === normalizedValue;
                    }) || '';
                };

                const optionsForType = function (value) {
                    const typeKey = matchingKey(subtypeOptionsByType, value);

                    return typeKey ? subtypeOptionsByType[typeKey] : [];
                };

                const optionsForTypeAndSubtype = function (map, typeValue, subtypeValue) {
                    const matchingTypeKey = matchingKey(map, typeValue);

                    if (!matchingTypeKey) {
                        return [];
                    }

                    const subtypeMap = map[matchingTypeKey] || {};
                    const matchingSubtypeKey = matchingKey(subtypeMap, subtypeValue);

                    return matchingSubtypeKey ? subtypeMap[matchingSubtypeKey] : [];
                };

                const optionsForTypeSubtypeBrand = function (map, typeValue, subtypeValue, brandValue) {
                    const matchingTypeKey = matchingKey(map, typeValue);

                    if (!matchingTypeKey) {
                        return [];
                    }

                    const subtypeMap = map[matchingTypeKey] || {};
                    const matchingSubtypeKey = matchingKey(subtypeMap, subtypeValue);

                    if (!matchingSubtypeKey) {
                        return [];
                    }

                    const brandMap = subtypeMap[matchingSubtypeKey] || {};
                    const matchingBrandKey = matchingKey(brandMap, brandValue);
                    const options = matchingBrandKey ? brandMap[matchingBrandKey] : [];

                    return Array.isArray(options) ? options : [];
                };

                const fillDatalist = function (list, options) {
                    list.innerHTML = '';
                    options.forEach(function (option) {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'product-option-item';
                        item.setAttribute('role', 'option');
                        item.dataset.value = option;
                        item.textContent = option;
                        list.appendChild(item);
                    });
                };

                const closeCombo = function (combo) {
                    const input = combo.querySelector('[data-combo-input]');
                    const list = combo.querySelector('.product-option-list');

                    list?.classList.remove('is-open');
                    input?.setAttribute('aria-expanded', 'false');
                    list?.querySelectorAll('.product-option-item.is-active').forEach(function (item) {
                        item.classList.remove('is-active');
                    });
                };

                const closeOtherCombos = function (currentCombo) {
                    document.querySelectorAll('[data-product-combobox]').forEach(function (combo) {
                        if (combo !== currentCombo) {
                            closeCombo(combo);
                        }
                    });
                };

                const visibleOptions = function (list) {
                    return Array.prototype.filter.call(list.querySelectorAll('.product-option-item'), function (item) {
                        return item.style.display !== 'none';
                    });
                };

                const setActiveOption = function (list, index) {
                    const options = visibleOptions(list);

                    list.querySelectorAll('.product-option-item.is-active').forEach(function (item) {
                        item.classList.remove('is-active');
                    });

                    if (!options.length) {
                        return;
                    }

                    const nextIndex = (index + options.length) % options.length;
                    const item = options[nextIndex];
                    item.classList.add('is-active');
                    item.scrollIntoView({ block: 'nearest' });
                };

                const filterComboOptions = function (input, list) {
                    const query = normalize(input.value);
                    let matches = 0;

                    list.querySelectorAll('.product-option-item').forEach(function (item) {
                        const isMatch = !query || normalize(item.dataset.value).includes(query);
                        item.style.display = isMatch ? '' : 'none';
                        item.classList.remove('is-active');
                        matches += isMatch ? 1 : 0;
                    });

                    return matches;
                };

                const openCombo = function (combo) {
                    const input = combo.querySelector('[data-combo-input]');
                    const list = combo.querySelector('.product-option-list');

                    if (!input || !list) {
                        return;
                    }

                    closeOtherCombos(combo);
                    const matches = filterComboOptions(input, list);
                    list.classList.toggle('is-open', matches > 0);
                    input.setAttribute('aria-expanded', matches > 0 ? 'true' : 'false');
                };

                const selectComboOption = function (input, list, value) {
                    input.value = value;
                    closeCombo(input.closest('[data-product-combobox]'));
                    input.dataset.comboSelecting = '1';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    delete input.dataset.comboSelecting;
                    input.focus();
                };

                document.querySelectorAll('[data-product-combobox]').forEach(function (combo) {
                    const input = combo.querySelector('[data-combo-input]');
                    const toggle = combo.querySelector('[data-combo-toggle]');
                    const list = combo.querySelector('.product-option-list');

                    if (!input || !list) {
                        return;
                    }

                    input.addEventListener('focus', function () {
                        openCombo(combo);
                    });

                    input.addEventListener('input', function () {
                        if (input.dataset.comboSelecting === '1') {
                            return;
                        }

                        openCombo(combo);
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeCombo(combo);
                            return;
                        }

                        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'Enter') {
                            return;
                        }

                        const options = visibleOptions(list);

                        if (!options.length) {
                            return;
                        }

                        if (event.key === 'Enter') {
                            const active = list.querySelector('.product-option-item.is-active');
                            if (active) {
                                event.preventDefault();
                                selectComboOption(input, list, active.dataset.value);
                            }
                            return;
                        }

                        event.preventDefault();
                        if (!list.classList.contains('is-open')) {
                            openCombo(combo);
                            setActiveOption(list, 0);
                            return;
                        }

                        const currentIndex = options.findIndex(function (item) {
                            return item.classList.contains('is-active');
                        });
                        setActiveOption(list, currentIndex + (event.key === 'ArrowDown' ? 1 : -1));
                    });

                    toggle?.addEventListener('click', function () {
                        if (list.classList.contains('is-open')) {
                            closeCombo(combo);
                            return;
                        }

                        input.focus();
                        openCombo(combo);
                    });

                    list.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                    });

                    list.addEventListener('click', function (event) {
                        const item = event.target.closest('.product-option-item');

                        if (!item) {
                            return;
                        }

                        selectComboOption(input, list, item.dataset.value);
                    });
                });

                document.addEventListener('click', function (event) {
                    if (event.target.closest('[data-product-combobox]')) {
                        return;
                    }

                    document.querySelectorAll('[data-product-combobox]').forEach(closeCombo);
                });

                const renderSubtypeOptions = function () {
                    const options = optionsForType(typeInput.value);

                    fillDatalist(subtypeList, options);

                    subtypeInput.placeholder = options.length
                        ? 'Selecciona o escribe un subtipo'
                        : 'Escribe un subtipo';
                };

                let brandContext = '';
                const currentBrandContext = function () {
                    return normalize(typeInput.value) + '|' + normalize(subtypeInput.value);
                };
                let modelContext = '';
                const currentModelContext = function () {
                    return currentBrandContext() + '|' + normalize(brandInput ? brandInput.value : '');
                };

                const renderBrandOptions = function (clearCurrentValue = false) {
                    if (!brandInput || !brandList) {
                        return;
                    }

                    const nextBrandContext = currentBrandContext();
                    if (clearCurrentValue && nextBrandContext !== brandContext) {
                        brandInput.value = '';
                    }
                    brandContext = nextBrandContext;

                    const specificOptions = optionsForTypeAndSubtype(
                        brandOptionsByTypeAndSubtype,
                        typeInput.value,
                        subtypeInput.value
                    );
                    const options = specificOptions.length ? specificOptions : allBrandOptions;

                    fillDatalist(brandList, options);
                    brandInput.placeholder = specificOptions.length
                        ? 'Selecciona o escribe una marca'
                        : 'Escribe una marca';
                };

                const renderModelOptions = function (clearCurrentValue = false) {
                    if (!modelInput || !modelList || !brandInput) {
                        return;
                    }

                    const nextModelContext = currentModelContext();
                    if (clearCurrentValue && nextModelContext !== modelContext) {
                        modelInput.value = '';
                    }
                    modelContext = nextModelContext;

                    const options = optionsForTypeSubtypeBrand(
                        modelOptionsByTypeSubtypeBrand,
                        typeInput.value,
                        subtypeInput.value,
                        brandInput.value
                    );

                    fillDatalist(modelList, options);
                    modelInput.placeholder = options.length
                        ? 'Selecciona o escribe un modelo'
                        : 'Escribe un modelo';
                };

                typeInput.addEventListener('input', function () {
                    renderSubtypeOptions();
                    renderBrandOptions(true);
                    renderModelOptions(true);
                });
                typeInput.addEventListener('change', function () {
                    renderSubtypeOptions();
                    renderBrandOptions(true);
                    renderModelOptions(true);
                });
                subtypeInput.addEventListener('input', function () {
                    renderBrandOptions(true);
                    renderModelOptions(true);
                });
                subtypeInput.addEventListener('change', function () {
                    renderBrandOptions(true);
                    renderModelOptions(true);
                });
                if (brandInput) {
                    brandInput.addEventListener('input', function () {
                        renderModelOptions(true);
                    });
                    brandInput.addEventListener('change', function () {
                        renderModelOptions(true);
                    });
                }
                renderSubtypeOptions();
                renderBrandOptions(false);
                renderModelOptions(false);

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
