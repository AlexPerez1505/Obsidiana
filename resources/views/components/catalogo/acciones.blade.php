@props([
    'editarModal',
    'editarUrl',
    'editarValores' => [],
    'borrarModal',
    'borrarUrl',
    'borrarValores' => [],
    'borrarTexto' => 'Eliminar',
    'etiqueta' => 'Acciones',
])

{{--
    Menu de tres puntos de una fila del catalogo.

    Los valores van con json_encode dentro de un atributo con comillas
    dobles: Blade los escapa y el navegador los devuelve enteros. Se evita
    @json a proposito, porque parte su argumento por comas y rompe
    cualquier arreglo de mas de una clave.
--}}

<div class="congress-menu">
    <button type="button" class="congress-menu-trigger" aria-label="{{ $etiqueta }}" aria-expanded="false">
        <x-gravityui-ellipsis-vertical />
    </button>

    <div class="congress-menu-dropdown">
        <button type="button" class="congress-menu-item"
                data-modal-abrir="{{ $editarModal }}"
                data-url="{{ $editarUrl }}"
                data-valores="{{ json_encode($editarValores) }}">
            <x-gravityui-pencil />
            <span>Editar</span>
        </button>

        <button type="button" class="congress-menu-item danger"
                data-modal-abrir="{{ $borrarModal }}"
                data-url="{{ $borrarUrl }}"
                data-valores="{{ json_encode($borrarValores) }}">
            <x-gravityui-trash-bin />
            <span>{{ $borrarTexto }}</span>
        </button>
    </div>
</div>
