{{--
    Catalogo de equipo: tipo -> subtipo -> marca -> modelo.

    Todo se da de alta desde aqui, sin tocar codigo. Las cuatro listas
    comparten el mismo modal generico: el disparador lleva a que modal
    apunta, a que URL y con que valores llenarlo.
--}}

@php
    // Marcas por subtipo, para encadenar los selects del modal de modelos
    // sin ir al servidor: el catalogo es chico y cabe en la pagina.
    $marcasPorSubtipo = $subtypes->mapWithKeys(fn ($s) => [
        $s->id => $s->brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
    ]);

    $secciones = [
        [
            'clave' => 'tipo',
            'titulo' => 'Tipos de equipo',
            'singular' => 'tipo de equipo',
            'total' => $equipmentTypes->count(),
        ],
        [
            'clave' => 'subtipo',
            'titulo' => 'Subtipos',
            'singular' => 'subtipo',
            'total' => $subtypes->count(),
        ],
        [
            'clave' => 'marca',
            'titulo' => 'Marcas',
            'singular' => 'marca',
            'total' => $brandLinks->count(),
        ],
        [
            'clave' => 'modelo',
            'titulo' => 'Modelos',
            'singular' => 'modelo',
            'total' => $equipmentModels->count(),
        ],
    ];
@endphp

<div class="card catalog-card eq-card">
    <div class="catalog-header">
        <h2 class="page-title">Catálogo de equipo</h2>
        <span class="catalog-count">Tipo → Subtipo → Marca → Modelo</span>
    </div>

    <p class="eq-intro">
        Lo que se dé de alta aquí es lo que aparece en las listas del formulario de productos.
    </p>

    <div class="eq-grid">
        {{-- ---------- Tipos ---------- --}}
        <section class="eq-col">
            <header class="eq-col-head">
                <h3>Tipos de equipo</h3>
                <span class="eq-num">{{ $equipmentTypes->count() }}</span>
            </header>

            <div class="eq-list">
                @forelse ($equipmentTypes as $tipo)
                    @php
                        // Ojo: @json parte su argumento por comas, asi que el
                        // arreglo se arma aqui y se le pasa una sola variable.
                        $valEditar = ['name' => $tipo->name];
                        $valBorrar = [
                            '__nombre' => $tipo->name,
                            '__aviso' => 'Se eliminarán también sus subtipos, marcas y modelos.',
                        ];
                    @endphp
                    <div class="eq-item">
                        <div class="eq-item-txt">
                            <span class="eq-item-name">{{ $tipo->name }}</span>
                            <span class="eq-item-meta">{{ $tipo->subtypes_count }} {{ $tipo->subtypes_count === 1 ? 'subtipo' : 'subtipos' }}</span>
                        </div>
                        <x-catalogo.acciones
                            etiqueta="Acciones del tipo de equipo"
                            editar-modal="modalTipoEditar"
                            :editar-url="route('configuracion.catalogo_equipo.tipos.update', $tipo)"
                            :editar-valores="$valEditar"
                            borrar-modal="modalEqEliminar"
                            :borrar-url="route('configuracion.catalogo_equipo.tipos.destroy', $tipo)"
                            :borrar-valores="$valBorrar" />
                    </div>
                @empty
                    <p class="eq-vacio">Sin tipos todavía.</p>
                @endforelse
            </div>

            <button type="button" class="eq-add" data-modal-abrir="modalTipoCrear">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Agregar tipo
            </button>
        </section>

        {{-- ---------- Subtipos ---------- --}}
        <section class="eq-col">
            <header class="eq-col-head">
                <h3>Subtipos</h3>
                <span class="eq-num">{{ $subtypes->count() }}</span>
            </header>

            <div class="eq-list">
                @forelse ($subtypes as $sub)
                    @php
                        $valEditar = ['name' => $sub->name, 'equipment_type_id' => $sub->equipment_type_id];
                        $valBorrar = [
                            '__nombre' => $sub->name,
                            '__aviso' => 'Se eliminarán también sus marcas y modelos.',
                        ];
                    @endphp
                    <div class="eq-item">
                        <div class="eq-item-txt">
                            <span class="eq-item-name">{{ $sub->name }}</span>
                            <span class="eq-item-meta">{{ $sub->equipmentType?->name ?? 'Sin tipo' }}</span>
                        </div>
                        <x-catalogo.acciones
                            etiqueta="Acciones del subtipo"
                            editar-modal="modalSubtipoEditar"
                            :editar-url="route('configuracion.catalogo_equipo.subtipos.update', $sub)"
                            :editar-valores="$valEditar"
                            borrar-modal="modalEqEliminar"
                            :borrar-url="route('configuracion.catalogo_equipo.subtipos.destroy', $sub)"
                            :borrar-valores="$valBorrar" />
                    </div>
                @empty
                    <p class="eq-vacio">Sin subtipos todavía.</p>
                @endforelse
            </div>

            <button type="button" class="eq-add" data-modal-abrir="modalSubtipoCrear"
                    @disabled($equipmentTypes->isEmpty())>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Agregar subtipo
            </button>
        </section>

        {{-- ---------- Marcas ---------- --}}
        <section class="eq-col">
            <header class="eq-col-head">
                <h3>Marcas</h3>
                <span class="eq-num">{{ $brandLinks->count() }}</span>
            </header>

            <div class="eq-list">
                @forelse ($brandLinks as $fila)
                    @php
                        $valEditar = [
                            'name' => $fila['brand']->name,
                            'subtype_id' => $fila['subtype']->id,
                            'subtype_anterior' => $fila['subtype']->id,
                        ];
                        $valBorrar = [
                            'subtype_id' => $fila['subtype']->id,
                            '__nombre' => $fila['brand']->name . ' · ' . $fila['subtype']->name,
                            '__aviso' => 'Se quita de ese subtipo, junto con sus modelos ahí. Sigue existiendo en los demás subtipos.',
                        ];
                    @endphp
                    <div class="eq-item">
                        <div class="eq-item-txt">
                            <span class="eq-item-name">{{ $fila['brand']->name }}</span>
                            <span class="eq-item-meta">{{ $fila['subtype']->name }}</span>
                        </div>
                        <x-catalogo.acciones
                            etiqueta="Acciones de la marca"
                            editar-modal="modalMarcaEditar"
                            :editar-url="route('configuracion.catalogo_equipo.marcas.update', $fila['brand'])"
                            :editar-valores="$valEditar"
                            borrar-modal="modalMarcaEliminar"
                            :borrar-url="route('configuracion.catalogo_equipo.marcas.destroy', $fila['brand'])"
                            :borrar-valores="$valBorrar"
                            borrar-texto="Quitar del subtipo" />
                    </div>
                @empty
                    <p class="eq-vacio">Sin marcas todavía.</p>
                @endforelse
            </div>

            <button type="button" class="eq-add" data-modal-abrir="modalMarcaCrear"
                    @disabled($subtypes->isEmpty())>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Agregar marca
            </button>
        </section>

        {{-- ---------- Modelos ---------- --}}
        <section class="eq-col">
            <header class="eq-col-head">
                <h3>Modelos</h3>
                <span class="eq-num">{{ $equipmentModels->count() }}</span>
            </header>

            <div class="eq-list">
                @forelse ($equipmentModels as $modelo)
                    @php
                        $valEditar = [
                            'name' => $modelo->name,
                            'subtype_id' => $modelo->subtype_id,
                            'brand_id' => $modelo->brand_id,
                        ];
                        $valBorrar = [
                            '__nombre' => $modelo->name,
                            '__aviso' => 'Esta acción no se puede deshacer.',
                        ];
                    @endphp
                    <div class="eq-item">
                        <div class="eq-item-txt">
                            <span class="eq-item-name">{{ $modelo->name }}</span>
                            <span class="eq-item-meta">{{ $modelo->brand?->name }} · {{ $modelo->subtype?->name ?? 'Sin subtipo' }}</span>
                        </div>
                        <x-catalogo.acciones
                            etiqueta="Acciones del modelo"
                            editar-modal="modalModeloEditar"
                            :editar-url="route('configuracion.catalogo_equipo.modelos.update', $modelo)"
                            :editar-valores="$valEditar"
                            borrar-modal="modalEqEliminar"
                            :borrar-url="route('configuracion.catalogo_equipo.modelos.destroy', $modelo)"
                            :borrar-valores="$valBorrar" />
                    </div>
                @empty
                    <p class="eq-vacio">Sin modelos todavía.</p>
                @endforelse
            </div>

            <button type="button" class="eq-add" data-modal-abrir="modalModeloCrear"
                    @disabled($subtypes->isEmpty())>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Agregar modelo
            </button>
        </section>
    </div>
</div>

{{-- ===================== Modales del catálogo de equipo ===================== --}}

@php
    // Se reutiliza en varios selects: subtipos agrupados por su tipo.
    $subtiposPorTipo = $subtypes->groupBy(fn ($s) => $s->equipmentType?->name ?? 'Sin tipo');
@endphp

<x-catalogo.modal id="modalTipoCrear" titulo="Nuevo tipo de equipo"
                  :accion="route('configuracion.catalogo_equipo.tipos.store')" boton="Guardar tipo">
    <label for="tipoCrearNombre">Nombre</label>
    <input id="tipoCrearNombre" type="text" name="name" required maxlength="255" autocomplete="off"
           placeholder="Ej. Endoscopia">
</x-catalogo.modal>

<x-catalogo.modal id="modalTipoEditar" titulo="Editar tipo de equipo" metodo="PUT" boton="Guardar cambios">
    <label for="tipoEditarNombre">Nombre</label>
    <input id="tipoEditarNombre" type="text" name="name" required maxlength="255" autocomplete="off">
</x-catalogo.modal>

<x-catalogo.modal id="modalSubtipoCrear" titulo="Nuevo subtipo"
                  :accion="route('configuracion.catalogo_equipo.subtipos.store')" boton="Guardar subtipo">
    <label for="subtipoCrearTipo">Tipo de equipo</label>
    <select id="subtipoCrearTipo" name="equipment_type_id" required>
        @foreach ($equipmentTypes as $tipo)
            <option value="{{ $tipo->id }}">{{ $tipo->name }}</option>
        @endforeach
    </select>

    <label for="subtipoCrearNombre" class="eq-mt">Nombre</label>
    <input id="subtipoCrearNombre" type="text" name="name" required maxlength="255" autocomplete="off"
           placeholder="Ej. Gastroscopio">
</x-catalogo.modal>

<x-catalogo.modal id="modalSubtipoEditar" titulo="Editar subtipo" metodo="PUT" boton="Guardar cambios">
    <label for="subtipoEditarTipo">Tipo de equipo</label>
    <select id="subtipoEditarTipo" name="equipment_type_id" required>
        @foreach ($equipmentTypes as $tipo)
            <option value="{{ $tipo->id }}">{{ $tipo->name }}</option>
        @endforeach
    </select>

    <label for="subtipoEditarNombre" class="eq-mt">Nombre</label>
    <input id="subtipoEditarNombre" type="text" name="name" required maxlength="255" autocomplete="off">
</x-catalogo.modal>

<x-catalogo.modal id="modalMarcaCrear" titulo="Nueva marca"
                  :accion="route('configuracion.catalogo_equipo.marcas.store')" boton="Guardar marca">
    <label for="marcaCrearSubtipo">Subtipo donde se ofrece</label>
    <select id="marcaCrearSubtipo" name="subtype_id" required>
        @foreach ($subtiposPorTipo as $nombreTipo => $lista)
            <optgroup label="{{ $nombreTipo }}">
                @foreach ($lista as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    <label for="marcaCrearNombre" class="eq-mt">Nombre</label>
    <input id="marcaCrearNombre" type="text" name="name" required maxlength="255" autocomplete="off"
           placeholder="Ej. Olympus">
    <p class="eq-nota">Si la marca ya existe en otro subtipo, se reutiliza la misma.</p>
</x-catalogo.modal>

<x-catalogo.modal id="modalMarcaEditar" titulo="Editar marca" metodo="PUT" boton="Guardar cambios">
    <input type="hidden" name="subtype_anterior">

    <label for="marcaEditarSubtipo">Subtipo donde se ofrece</label>
    <select id="marcaEditarSubtipo" name="subtype_id" required>
        @foreach ($subtiposPorTipo as $nombreTipo => $lista)
            <optgroup label="{{ $nombreTipo }}">
                @foreach ($lista as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    <label for="marcaEditarNombre" class="eq-mt">Nombre</label>
    <input id="marcaEditarNombre" type="text" name="name" required maxlength="255" autocomplete="off">
    <p class="eq-nota">El nombre se cambia en todo el sistema. Cambiar el subtipo mueve también sus modelos.</p>
</x-catalogo.modal>

<x-catalogo.modal id="modalMarcaEliminar" titulo="Quitar marca del subtipo" metodo="DELETE"
                  boton="Quitar marca" :peligro="true">
    <input type="hidden" name="subtype_id">
    <x-catalogo.aviso-borrado />
</x-catalogo.modal>

<x-catalogo.modal id="modalModeloCrear" titulo="Nuevo modelo"
                  :accion="route('configuracion.catalogo_equipo.modelos.store')" boton="Guardar modelo">
    <label for="modeloCrearSubtipo">Subtipo</label>
    <select id="modeloCrearSubtipo" name="subtype_id" required data-cascada-subtipo>
        @foreach ($subtiposPorTipo as $nombreTipo => $lista)
            <optgroup label="{{ $nombreTipo }}">
                @foreach ($lista as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    <label for="modeloCrearMarca" class="eq-mt">Marca</label>
    <select id="modeloCrearMarca" name="brand_id" required data-cascada-marca></select>

    <label for="modeloCrearNombre" class="eq-mt">Nombre</label>
    <input id="modeloCrearNombre" type="text" name="name" required maxlength="255" autocomplete="off"
           placeholder="Ej. GIF-H190">
</x-catalogo.modal>

<x-catalogo.modal id="modalModeloEditar" titulo="Editar modelo" metodo="PUT" boton="Guardar cambios">
    <label for="modeloEditarSubtipo">Subtipo</label>
    <select id="modeloEditarSubtipo" name="subtype_id" required data-cascada-subtipo>
        @foreach ($subtiposPorTipo as $nombreTipo => $lista)
            <optgroup label="{{ $nombreTipo }}">
                @foreach ($lista as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    <label for="modeloEditarMarca" class="eq-mt">Marca</label>
    <select id="modeloEditarMarca" name="brand_id" required data-cascada-marca></select>

    <label for="modeloEditarNombre" class="eq-mt">Nombre</label>
    <input id="modeloEditarNombre" type="text" name="name" required maxlength="255" autocomplete="off">
</x-catalogo.modal>

{{-- Modal de borrado compartido por tipos, subtipos y modelos --}}
<x-catalogo.modal id="modalEqEliminar" titulo="Eliminar del catálogo" metodo="DELETE"
                  boton="Eliminar" :peligro="true">
    <x-catalogo.aviso-borrado />
</x-catalogo.modal>

<script>
    window.EQ_MARCAS_POR_SUBTIPO = @json($marcasPorSubtipo);
</script>
