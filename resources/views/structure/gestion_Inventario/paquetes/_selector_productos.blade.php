{{--
    Selector de productos para armar un paquete: lista con imagen
    representativa y checkbox (igual que el listado de Productos), con un
    buscador en vivo arriba, igual al buscador de clientes en Cotización.

    Espera:
    - $productos: todos los productos disponibles.
    - $seleccionados: array [producto_id => cantidad] de los que ya deben
      quedar marcados (precargados desde Productos, o los del paquete que
      se está editando).
--}}
<style>
    .paquete-producto-item:hover { border-color: var(--primary, #2563eb); }
</style>
<x-ui.card style="margin-bottom:18px;">
    <x-ui.section-title style="margin:0 0 16px;">Productos del Paquete</x-ui.section-title>

    @if(($mensajePrecarga ?? null))
        <p style="margin:0 0 14px; color:var(--muted); font-size:13.5px;">{{ $mensajePrecarga }}</p>
    @endif

    <div style="position:relative; margin-bottom:14px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="paquete-producto-search" placeholder="Buscar producto por tipo, marca o modelo..." autocomplete="off"
               style="width:100%; padding:11px 12px 11px 38px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
    </div>

    <div id="paquete-productos-lista" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:8px; max-height:560px; overflow-y:auto; border:1px solid var(--border); border-radius:9px; padding:8px;">
        @forelse($productos as $producto)
            @php $cantidadActual = $seleccionados[$producto->id] ?? null; @endphp
            <label class="paquete-producto-item"
                   data-search="{{ str()->lower($producto->tipo_equipo.' '.$producto->subtipo.' '.$producto->marca.' '.$producto->modelo) }}"
                   style="display:flex; align-items:center; gap:10px; padding:8px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:var(--surface);">
                <input type="checkbox" class="paquete-producto-check" value="{{ $producto->id }}"
                       @checked($cantidadActual !== null)
                       style="width:17px; height:17px; flex:0 0 auto; cursor:pointer;">

                @if($producto->imagen_path)
                    <img src="{{ asset('storage/'.$producto->imagen_path) }}" alt="" style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--border); flex:0 0 auto;">
                @else
                    <div style="width:44px; height:44px; border-radius:6px; border:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:16px; flex:0 0 auto;">—</div>
                @endif

                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; font-size:13.5px;">{{ $producto->tipo_equipo }}</div>
                    <div class="muted" style="font-size:12px;">{{ trim($producto->marca.' '.$producto->modelo) ?: '—' }} — ${{ number_format($producto->precio, 2) }} · Stock: {{ $producto->stock }}</div>
                </div>

                <input type="number" class="paquete-producto-cantidad" min="1" value="{{ $cantidadActual ?? 1 }}"
                       {{ $cantidadActual === null ? 'disabled' : '' }}
                       onclick="event.stopPropagation();"
                       style="width:64px; padding:8px; border:1px solid var(--border); border-radius:8px; font-size:14px; text-align:center; background:var(--surface); color:var(--text);">
            </label>
        @empty
            <p class="muted" style="padding:16px; text-align:center; margin:0;">No hay productos registrados en el inventario.</p>
        @endforelse
    </div>

    <p id="paquete-sin-resultados" class="muted" style="display:none; padding:12px; text-align:center;">No hay productos que coincidan con la búsqueda.</p>

    {{-- Aquí se generan, justo antes de enviar, los productos[i][id]/[cantidad] de los que sí quedaron marcados --}}
    <div id="paquete-productos-hidden"></div>
</x-ui.card>

<script>
    (function () {
        const items = Array.from(document.querySelectorAll('.paquete-producto-item'));
        const search = document.getElementById('paquete-producto-search');
        const sinResultados = document.getElementById('paquete-sin-resultados');
        const form = document.getElementById('paquete-form');
        const hiddenWrap = document.getElementById('paquete-productos-hidden');

        items.forEach(function (item) {
            const checkbox = item.querySelector('.paquete-producto-check');
            const cantidad = item.querySelector('.paquete-producto-cantidad');

            checkbox.addEventListener('change', function () {
                cantidad.disabled = !checkbox.checked;
                if (checkbox.checked && (!cantidad.value || Number(cantidad.value) < 1)) {
                    cantidad.value = 1;
                }
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let visibles = 0;

                items.forEach(function (item) {
                    const coincide = item.dataset.search.includes(q);
                    item.style.display = coincide ? 'flex' : 'none';
                    if (coincide) visibles++;
                });

                sinResultados.style.display = visibles === 0 ? 'block' : 'none';
            });
        }

        if (form) {
            form.addEventListener('submit', function () {
                hiddenWrap.innerHTML = '';
                let i = 0;

                items.forEach(function (item) {
                    const checkbox = item.querySelector('.paquete-producto-check');
                    if (!checkbox.checked) return;

                    const cantidad = item.querySelector('.paquete-producto-cantidad');
                    hiddenWrap.insertAdjacentHTML('beforeend',
                        '<input type="hidden" name="productos[' + i + '][id]" value="' + checkbox.value + '">' +
                        '<input type="hidden" name="productos[' + i + '][cantidad]" value="' + (cantidad.value || 1) + '">'
                    );
                    i++;
                });
            });
        }
    })();
</script>
