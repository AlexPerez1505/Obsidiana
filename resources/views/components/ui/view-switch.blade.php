@props(['key' => 'vista'])

{{--
    Cambio de vista lista / tarjetas.

    Uso en la vista:
        <x-ui.view-switch key="ventas" />
        <div data-view-list>   ...tabla...   </div>
        <div data-view-cards>  ...tarjetas... </div>

    El componente busca [data-view-list] y [data-view-cards] dentro de la
    pagina y alterna cual se muestra. La eleccion se recuerda por pantalla
    gracias al parametro key.
--}}

<div class="view-switch" role="group" aria-label="Cambiar vista" data-view-switch="{{ $key }}">
    <button type="button" data-view="lista" class="active" title="Ver como lista" aria-label="Ver como lista">
        <x-gravityui-list-ul />
    </button>
    <button type="button" data-view="tarjetas" title="Ver como tarjetas" aria-label="Ver como tarjetas">
        <x-gravityui-layout-cells />
    </button>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        document.querySelectorAll('[data-view-switch]').forEach(function (grupo) {
            var clave    = 'vista:' + grupo.dataset.viewSwitch;
            var lista    = document.querySelector('[data-view-list]');
            var tarjetas = document.querySelector('[data-view-cards]');
            if (!lista || !tarjetas) return;

            function aplicar(vista) {
                var esLista = vista !== 'tarjetas';
                lista.style.display    = esLista ? '' : 'none';
                tarjetas.style.display = esLista ? 'none' : 'grid';
                grupo.querySelectorAll('button').forEach(function (b) {
                    b.classList.toggle('active', b.dataset.view === (esLista ? 'lista' : 'tarjetas'));
                });
                try { localStorage.setItem(clave, esLista ? 'lista' : 'tarjetas'); } catch (e) {}
            }

            grupo.querySelectorAll('button').forEach(function (b) {
                b.addEventListener('click', function () { aplicar(b.dataset.view); });
            });

            var guardada = 'lista';
            try { guardada = localStorage.getItem(clave) || 'lista'; } catch (e) {}
            aplicar(guardada);
        });
    })();
    </script>
    @endpush
@endonce
