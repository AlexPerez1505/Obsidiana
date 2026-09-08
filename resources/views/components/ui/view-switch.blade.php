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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    </button>
    <button type="button" data-view="tarjetas" title="Ver como tarjetas" aria-label="Ver como tarjetas">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
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
