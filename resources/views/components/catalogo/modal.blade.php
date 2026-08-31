@props([
    'id',
    'titulo',
    'accion' => '',
    'metodo' => 'POST',
    'boton' => 'Guardar',
    'peligro' => false,
])

{{--
    Modal generico del catalogo.

    La accion puede venir vacia: los disparadores con data-url se la ponen
    al abrirlo, para reutilizar un mismo modal en todas las filas.
--}}

<dialog class="cfg-modal" id="{{ $id }}" aria-labelledby="{{ $id }}T">
    <form method="POST" action="{{ $accion }}" class="cfg-modal-box" data-form>
        @csrf
        @if ($metodo !== 'POST')
            @method($metodo)
        @endif

        <div class="cfg-modal-head">
            <h3 id="{{ $id }}T">{{ $titulo }}</h3>
            <button type="button" class="cfg-modal-x" data-cerrar aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="cfg-modal-body">
            {{ $slot }}
        </div>

        <div class="cfg-modal-foot">
            <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
            <button type="submit" class="btn {{ $peligro ? 'btn--danger' : '' }}">{{ $boton }}</button>
        </div>
    </form>
</dialog>
