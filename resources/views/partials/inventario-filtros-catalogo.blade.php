@php
    $tipoFiltro = (string) ($filters['equipment_type_id'] ?? '');
    $subtipoFiltro = (string) ($filters['subtype_id'] ?? '');
    $subtiposFiltro = $tipoFiltro !== ''
        ? collect($catalogo['subtipos'][$tipoFiltro] ?? [])
        : collect($catalogo['subtipos'])->flatten(1)->sortBy('name')->values();
    $marcasFiltro = $subtiposFiltro->flatMap(fn ($s) => $catalogo['marcas'][$s['id']] ?? []);
    if ($subtipoFiltro !== '') {
        $marcasFiltro = collect($catalogo['marcas'][$subtipoFiltro] ?? []);
    }
    $marcasFiltro = $marcasFiltro->unique('id')->sortBy('name')->values();
    $marcaFiltro = (string) ($filters['brand_id'] ?? '');
    $modelosFiltro = collect($catalogo['modelos'] ?? [])
        ->filter(fn ($lista, $clave) => ($subtipoFiltro === '' || str_starts_with($clave, $subtipoFiltro.'-'))
            && ($marcaFiltro === '' || str_ends_with($clave, '-'.$marcaFiltro)))
        ->flatten(1)->unique('id')->sortBy('name')->values();
@endphp

<div data-catalogo-filtros style="display:flex;gap:10px;flex-wrap:wrap;">
    <label style="flex:1;min-width:160px;">Tipo de equipo
        <select name="equipment_type_id" data-catalogo="tipo" style="width:100%;">
            <option value="">Todos los tipos</option>
            @foreach ($catalogo['tipos'] as $tipo)
                <option value="{{ $tipo->id }}" @selected($tipoFiltro === (string) $tipo->id)>{{ $tipo->name }}</option>
            @endforeach
        </select>
    </label>
    <label style="flex:1;min-width:160px;">Subtipo
        <select name="subtype_id" data-catalogo="subtipo" style="width:100%;">
            <option value="">Todos los subtipos</option>
            @foreach ($subtiposFiltro as $subtipo)
                <option value="{{ $subtipo['id'] }}" @selected($subtipoFiltro === (string) $subtipo['id'])>{{ $subtipo['name'] }}</option>
            @endforeach
        </select>
    </label>
    <label style="flex:1;min-width:160px;">Marca
        <select name="brand_id" data-catalogo="marca" style="width:100%;">
            <option value="">Todas las marcas</option>
            @foreach ($marcasFiltro as $marca)
                <option value="{{ $marca['id'] }}" @selected((string) ($filters['brand_id'] ?? '') === (string) $marca['id'])>{{ $marca['name'] }}</option>
            @endforeach
        </select>
    </label>
    <label style="flex:1;min-width:160px;">Modelo
        <select name="equipment_model_id" data-catalogo="modelo" style="width:100%;">
            <option value="">Todos los modelos</option>
            @foreach ($modelosFiltro as $modelo)
                <option value="{{ $modelo['id'] }}" @selected((string) ($filters['equipment_model_id'] ?? '') === (string) $modelo['id'])>{{ $modelo['name'] }}</option>
            @endforeach
        </select>
    </label>
</div>

@push('scripts')
<script>
(function () {
    const catalogo = @json($catalogo);
    document.querySelectorAll('[data-catalogo-filtros]').forEach(function (grupo) {
        const tipo = grupo.querySelector('[data-catalogo="tipo"]');
        const subtipo = grupo.querySelector('[data-catalogo="subtipo"]');
        const marca = grupo.querySelector('[data-catalogo="marca"]');
        const modelo = grupo.querySelector('[data-catalogo="modelo"]');
        const subtipos = () => tipo.value
            ? (catalogo.subtipos[tipo.value] || [])
            : Object.values(catalogo.subtipos).flat();
        function llenar(select, opciones, titulo) {
            select.replaceChildren(new Option(titulo, ''));
            opciones.sort((a, b) => a.name.localeCompare(b.name, 'es'))
                .forEach(item => select.add(new Option(item.name, item.id)));
        }
        function marcas() {
            const opciones = subtipo.value
                ? (catalogo.marcas[subtipo.value] || [])
                : subtipos().flatMap(s => catalogo.marcas[s.id] || []);
            llenar(marca, Array.from(new Map(opciones.map(m => [m.id, m])).values()), 'Todas las marcas');
        }
        function modelos() {
            // Las llaves del mapa son "subtipo-marca": el modelo depende de
            // las dos cosas; se conservan solo las combinaciones elegidas.
            const opciones = Object.entries(catalogo.modelos || {})
                .filter(([clave]) => (!subtipo.value || clave.startsWith(subtipo.value + '-'))
                    && (!marca.value || clave.endsWith('-' + marca.value)))
                .flatMap(([, lista]) => lista);
            llenar(modelo, Array.from(new Map(opciones.map(m => [m.id, m])).values()), 'Todos los modelos');
        }
        tipo.addEventListener('change', function () {
            llenar(subtipo, subtipos(), 'Todos los subtipos');
            marcas();
            modelos();
        });
        subtipo.addEventListener('change', function () { marcas(); modelos(); });
        marca.addEventListener('change', modelos);
    });
})();
</script>
@endpush
