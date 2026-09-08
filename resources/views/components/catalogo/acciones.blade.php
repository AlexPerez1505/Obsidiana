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
        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
    </button>

    <div class="congress-menu-dropdown">
        <button type="button" class="congress-menu-item"
                data-modal-abrir="{{ $editarModal }}"
                data-url="{{ $editarUrl }}"
                data-valores="{{ json_encode($editarValores) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            <span>Editar</span>
        </button>

        <button type="button" class="congress-menu-item danger"
                data-modal-abrir="{{ $borrarModal }}"
                data-url="{{ $borrarUrl }}"
                data-valores="{{ json_encode($borrarValores) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
            <span>{{ $borrarTexto }}</span>
        </button>
    </div>
</div>
