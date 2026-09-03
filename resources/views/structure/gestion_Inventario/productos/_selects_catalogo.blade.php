{{--
    Tipo -> Subtipo -> Marca -> Modelo, encadenados y leidos del catalogo.

    Ya no hay listas escritas en el codigo: todo sale de Configuracion >
    Catalogos. El arbol completo viaja en la pagina, asi que cambiar un
    select no consulta al servidor.

    Espera:
      $catalogo  arreglo con tipos, subtipos, marcas y modelos
      $producto  opcional, para precargar en edicion
--}}

@php
    $sel = [
        'equipment_type_id' => old('equipment_type_id', $producto->equipment_type_id ?? ''),
        'subtype_id' => old('subtype_id', $producto->subtype_id ?? ''),
        'brand_id' => old('brand_id', $producto->brand_id ?? ''),
        'equipment_model_id' => old('equipment_model_id', $producto->equipment_model_id ?? ''),
    ];

    $campos = [
        ['name' => 'equipment_type_id', 'label' => 'Tipo de Equipo *', 'vacio' => 'Selecciona un tipo', 'req' => true],
        ['name' => 'subtype_id', 'label' => 'Subtipo', 'vacio' => 'Selecciona un subtipo', 'req' => false],
        ['name' => 'brand_id', 'label' => 'Marca', 'vacio' => 'Selecciona una marca', 'req' => false],
        ['name' => 'equipment_model_id', 'label' => 'Modelo', 'vacio' => 'Selecciona un modelo', 'req' => false],
    ];
@endphp

@foreach ($campos as $campo)
    <x-ui.form-group :label="$campo['label']" :for="$campo['name']">
        <select id="{{ $campo['name'] }}" name="{{ $campo['name'] }}"
                class="cat-select" data-cat="{{ $campo['name'] }}"
                {{ $campo['req'] ? 'required' : '' }}>
            <option value="">{{ $campo['vacio'] }}</option>

            {{-- Solo el primero se dibuja en servidor; los demas los llena el
                 script segun lo que se vaya eligiendo. --}}
            @if ($campo['name'] === 'equipment_type_id')
                @foreach ($catalogo['tipos'] as $tipo)
                    <option value="{{ $tipo['id'] }}" @selected((string) $sel['equipment_type_id'] === (string) $tipo['id'])>{{ $tipo['name'] }}</option>
                @endforeach
            @endif
        </select>
    </x-ui.form-group>
@endforeach

@if (empty($catalogo['tipos']))
    <p class="cat-aviso">
        Todavía no hay nada en el catálogo.
        <a href="{{ route('configuracion.catalogos.index') }}">Dalo de alta en Configuración → Catálogos</a>.
    </p>
@endif

@once
    @push('scripts')
        <script>
        (function () {
            var CAT = @json($catalogo);
            var ELEGIDO = @json($sel);

            var tipo = document.querySelector('[data-cat="equipment_type_id"]');
            var subtipo = document.querySelector('[data-cat="subtype_id"]');
            var marca = document.querySelector('[data-cat="brand_id"]');
            var modelo = document.querySelector('[data-cat="equipment_model_id"]');

            if (!tipo || !subtipo || !marca || !modelo) return;

            function llenar(select, lista, elegido, textoVacio) {
                var previo = select.value;
                select.innerHTML = '';

                var vacio = document.createElement('option');
                vacio.value = '';
                vacio.textContent = lista.length ? textoVacio : 'Sin opciones';
                select.appendChild(vacio);

                lista.forEach(function (item) {
                    var op = document.createElement('option');
                    op.value = item.id;
                    op.textContent = item.name;
                    select.appendChild(op);
                });

                select.disabled = lista.length === 0;

                var valor = elegido || previo;
                if (valor && select.querySelector('option[value="' + valor + '"]')) {
                    select.value = valor;
                }
            }

            function pintarSubtipos(elegido) {
                llenar(subtipo, (CAT.subtipos || {})[tipo.value] || [], elegido, 'Selecciona un subtipo');
            }

            function pintarMarcas(elegido) {
                llenar(marca, (CAT.marcas || {})[subtipo.value] || [], elegido, 'Selecciona una marca');
            }

            function pintarModelos(elegido) {
                var llave = subtipo.value + '-' + marca.value;
                llenar(modelo, (CAT.modelos || {})[llave] || [], elegido, 'Selecciona un modelo');
            }

            tipo.addEventListener('change', function () {
                pintarSubtipos(null);
                pintarMarcas(null);
                pintarModelos(null);
            });

            subtipo.addEventListener('change', function () {
                pintarMarcas(null);
                pintarModelos(null);
            });

            marca.addEventListener('change', function () { pintarModelos(null); });

            // Carga inicial: respeta lo que ya traia el producto o el old().
            pintarSubtipos(ELEGIDO.subtype_id);
            pintarMarcas(ELEGIDO.brand_id);
            pintarModelos(ELEGIDO.equipment_model_id);
        })();
        </script>
    @endpush

    @push("head")
        <style>
            .cat-select { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px;
                          background:var(--surface); color:var(--text); font-family:inherit; font-size:15px;
                          outline:none; cursor:pointer; }
            .cat-select:focus { border-color:var(--primary); }
            .cat-select:disabled { color:var(--muted); cursor:not-allowed; background:var(--surface-2); }
            .cat-aviso { grid-column:1 / -1; margin:0; padding:12px 14px; border:1px solid var(--border);
                         border-radius:9px; background:var(--surface-2); color:var(--muted); font-size:13.5px; }
            .cat-aviso a { color:var(--primary); }
        </style>
    @endpush
@endonce
