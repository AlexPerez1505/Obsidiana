{{--
    Formulario para crear o editar un paquete.

    Izquierda: el catálogo en mosaicos (foto, nombre, modelo · marca,
    existencias) con buscador y píldoras de categoría; tocar un mosaico lo
    agrega o lo quita. Derecha: el resumen fijo con el nombre, la vista
    previa del collage, lo elegido con − / + y los totales.

    Espera:
    - $accion, $metodo ('POST' o 'PUT'), $boton (texto del botón).
    - $paquete: el paquete que se edita, o null.
    - $productos: todos los productos.
    - $seleccionados: [producto_id => cantidad] que ya deben quedar elegidos.
    - $mensajePrecarga (opcional).

    Lo que se envía no cambia: productos[i][id] y productos[i][cantidad].
--}}
@php
    $verPrecio = \App\Support\PrecioVisible::para();
    $tiposForm = $productos->pluck('tipo_equipo')->filter()->unique()->sort()->values();
@endphp

<form method="POST" action="{{ $accion }}" id="paquete-form" class="pqf" novalidate>
    @csrf
    @if ($metodo !== 'POST')
        @method($metodo)
    @endif

    {{-- ================= Catálogo ================= --}}
    <section class="pqf-cat" aria-labelledby="pqf-cat-t">
        <div class="pqf-cat-cab">
            <h2 id="pqf-cat-t">Elige los productos</h2>
            <span class="pqf-cat-n"><b data-pqf-visibles>{{ $productos->count() }}</b> productos</span>
        </div>

        @if ($mensajePrecarga ?? null)
            <p class="pqf-aviso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                {{ $mensajePrecarga }}
            </p>
        @endif

        <label class="pqf-buscar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <span class="pqf-sr">Buscar producto</span>
            <input type="search" id="paquete-producto-search" placeholder="Buscar por nombre, modelo, marca o referencia" autocomplete="off">
        </label>

        <div class="pqf-filtros" role="group" aria-label="Filtrar por categoría">
            <button type="button" class="pqf-pill is-on" data-pqf-tipo="">Todos</button>
            <button type="button" class="pqf-pill" data-pqf-tipo="__sel">Elegidos <span data-pqf-n-pill>0</span></button>
            @foreach ($tiposForm as $t)
                <button type="button" class="pqf-pill" data-pqf-tipo="{{ mb_strtolower($t) }}">{{ \Illuminate\Support\Str::title(mb_strtolower($t)) }}</button>
            @endforeach
        </div>

        <div class="pqf-grid" id="paquete-productos-lista">
            @forelse ($productos as $producto)
                @php
                    $cant = $seleccionados[$producto->id] ?? null;
                    $sub = trim(($producto->modelo ?: '').($producto->marca ? ' · '.$producto->marca : ''), ' ·');
                    $img = $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : '';
                @endphp
                <article class="pqf-item paquete-producto-item {{ $cant !== null ? 'is-sel' : '' }}"
                         data-id="{{ $producto->id }}"
                         data-cant="{{ $cant ?? 0 }}"
                         data-nombre="{{ $producto->nombreVisible() }}"
                         data-sub="{{ $sub }}"
                         data-img="{{ $img }}"
                         data-stock="{{ (int) $producto->stock }}"
                         @if ($verPrecio) data-precio="{{ (float) $producto->precio }}" @endif
                         data-tipo="{{ mb_strtolower((string) $producto->tipo_equipo) }}"
                         data-search="{{ mb_strtolower($producto->nombreVisible().' '.$producto->tipo_equipo.' '.$producto->subtipo.' '.$producto->marca.' '.$producto->modelo.' '.$producto->referencia) }}">
                    <button type="button" class="pqf-toque" data-pqf-toggle aria-pressed="{{ $cant !== null ? 'true' : 'false' }}"
                            aria-label="{{ $cant !== null ? 'Quitar' : 'Agregar' }} {{ $producto->nombreVisible() }}">
                        <span class="pqf-foto">
                            @if ($img)
                                <img src="{{ $img }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                            @endif
                            <span class="pqf-check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            </span>
                        </span>
                        <span class="pqf-txt">
                            <b>{{ $producto->nombreVisible() }}</b>
                            <small>{{ $sub ?: 'Sin modelo ni marca' }}</small>
                            <span class="pqf-meta">
                                <span class="pqf-stock {{ $producto->stock > 0 ? '' : 'is-cero' }}">{{ $producto->stock > 0 ? $producto->stock.' en stock' : 'Sin stock' }}</span>
                                @if ($verPrecio && (float) $producto->precio > 0)
                                    <span class="pqf-precio">${{ number_format((float) $producto->precio, 2) }}</span>
                                @endif
                            </span>
                        </span>
                    </button>
                </article>
            @empty
                <p class="pqf-vacio">No hay productos registrados en el inventario.</p>
            @endforelse
        </div>
        <p id="paquete-sin-resultados" class="pqf-vacio" hidden>No hay productos que coincidan.</p>
    </section>

    {{-- ================= Resumen ================= --}}
    <aside class="pqf-res" aria-labelledby="pqf-res-t">
        <div class="pqf-res-in">
            <h2 id="pqf-res-t" class="pqf-sr">Resumen del paquete</h2>

            <div class="pqf-prev pv-fotos-1" data-pqf-prev aria-hidden="true">
                <span class="pqf-prev-vacio">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22V12"/></svg>
                </span>
            </div>

            <label class="pqf-nombre">
                <span>Nombre del paquete</span>
                <input type="text" name="nombre" value="{{ old('nombre', $paquete?->nombre) }}" placeholder="Ej. Paquete Endoscopía Básico" required maxlength="255" autocomplete="off">
            </label>
            @error('nombre')<p class="pqf-error">{{ $message }}</p>@enderror

            <div class="pqf-elegidos-cab">
                <h3>En el paquete</h3>
                <span><b data-pqf-n>0</b> artículos · <b data-pqf-piezas>0</b> pz</span>
            </div>
            <ul class="pqf-elegidos" data-pqf-lista></ul>
            <p class="pqf-elegidos-vacio" data-pqf-vacio>Toca los productos de la izquierda para agregarlos.</p>
            @error('productos')<p class="pqf-error">Elige al menos un producto.</p>@enderror

            @if ($verPrecio)
                <div class="pqf-total">
                    <span>Suma de precios</span>
                    <b data-pqf-total>$0.00</b>
                </div>
            @endif

            <div class="pqf-acciones">
                <a href="{{ route('inventory.paquetes.index') }}" class="pqf-btn">Cancelar</a>
                <button type="submit" class="pqf-btn pqf-btn--prim" data-pqf-guardar>{{ $boton }}</button>
            </div>
        </div>
    </aside>

    {{-- Justo antes de enviar se generan productos[i][id]/[cantidad]. --}}
    <div id="paquete-productos-hidden"></div>
</form>

<script>
(function () {
    const form = document.getElementById('paquete-form');
    if (!form) return;
    const items = Array.from(form.querySelectorAll('.pqf-item'));
    const porId = new Map(items.map(it => [it.dataset.id, it]));
    const buscar = document.getElementById('paquete-producto-search');
    const sinRes = document.getElementById('paquete-sin-resultados');
    const lista = form.querySelector('[data-pqf-lista]');
    const vacio = form.querySelector('[data-pqf-vacio]');
    const prev = form.querySelector('[data-pqf-prev]');
    const total = form.querySelector('[data-pqf-total]');
    const visibles = form.querySelector('[data-pqf-visibles]');
    const hidden = document.getElementById('paquete-productos-hidden');
    const dinero = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    let tipo = '';

    // Lo elegido, en el orden en que se fue agregando.
    const elegidos = new Map();
    items.filter(it => Number(it.dataset.cant) > 0).forEach(it => elegidos.set(it.dataset.id, Number(it.dataset.cant)));

    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function alternar(id) {
        if (elegidos.has(id)) elegidos.delete(id); else elegidos.set(id, 1);
        pintar();
    }

    function cambiar(id, delta) {
        const n = (elegidos.get(id) || 0) + delta;
        if (n <= 0) elegidos.delete(id); else elegidos.set(id, Math.min(n, 999));
        pintar();
    }

    function pintar() {
        // Mosaicos
        items.forEach(function (it) {
            const sel = elegidos.has(it.dataset.id);
            it.classList.toggle('is-sel', sel);
            const b = it.querySelector('[data-pqf-toggle]');
            b.setAttribute('aria-pressed', sel ? 'true' : 'false');
            b.setAttribute('aria-label', (sel ? 'Quitar ' : 'Agregar ') + it.dataset.nombre);
        });

        // Lista del resumen
        let piezas = 0, suma = 0, fotos = [];
        lista.innerHTML = Array.from(elegidos.entries()).map(function ([id, cant]) {
            const it = porId.get(id);
            if (!it) return '';
            piezas += cant;
            suma += (Number(it.dataset.precio) || 0) * cant;
            if (it.dataset.img && fotos.length < 3) fotos.push(it.dataset.img);
            const falta = cant > Number(it.dataset.stock);
            return '<li class="pqf-el" data-id="' + id + '">' +
                '<span class="pqf-el-foto">' + (it.dataset.img ? '<img src="' + esc(it.dataset.img) + '" alt="" onerror="this.remove()">' : '') + '</span>' +
                '<span class="pqf-el-txt"><b>' + esc(it.dataset.nombre) + '</b><small>' + esc(it.dataset.sub || 'Sin modelo ni marca') + '</small>' +
                (falta ? '<em>Solo hay ' + esc(it.dataset.stock) + ' en stock</em>' : '') + '</span>' +
                '<span class="pqf-paso" role="group" aria-label="Cantidad de ' + esc(it.dataset.nombre) + '">' +
                    '<button type="button" data-pqf-menos aria-label="Uno menos">−</button>' +
                    '<output aria-live="polite">' + cant + '</output>' +
                    '<button type="button" data-pqf-mas aria-label="Uno más">+</button>' +
                '</span>' +
                '<button type="button" class="pqf-el-x" data-pqf-quitar aria-label="Quitar ' + esc(it.dataset.nombre) + '">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg></button>' +
                '</li>';
        }).join('');

        vacio.hidden = elegidos.size > 0;
        form.querySelector('[data-pqf-n]').textContent = elegidos.size;
        form.querySelector('[data-pqf-piezas]').textContent = piezas;
        form.querySelector('[data-pqf-n-pill]').textContent = elegidos.size;
        if (total) total.textContent = suma > 0 ? dinero.format(suma) : 'Sin precio';

        // Vista previa del collage (igual que en la vitrina).
        prev.className = 'pqf-prev pv-fotos-' + Math.max(1, fotos.length);
        prev.innerHTML = fotos.length
            ? fotos.map(f => '<span class="pqf-prev-foto"><img src="' + esc(f) + '" alt="" onerror="this.remove()"></span>').join('')
            : '<span class="pqf-prev-vacio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22V12"/></svg></span>';

        if (tipo === '__sel') filtrar();
    }

    function filtrar() {
        const q = (buscar.value || '').trim().toLowerCase();
        let n = 0;
        items.forEach(function (it) {
            const okTipo = tipo === '' || (tipo === '__sel' ? elegidos.has(it.dataset.id) : it.dataset.tipo === tipo);
            const ok = okTipo && (!q || it.dataset.search.includes(q));
            it.hidden = !ok;
            if (ok) n++;
        });
        visibles.textContent = n;
        sinRes.hidden = n > 0;
    }

    form.addEventListener('click', function (e) {
        const t = e.target.closest('[data-pqf-toggle]');
        if (t) { alternar(t.closest('.pqf-item').dataset.id); return; }
        const el = e.target.closest('.pqf-el');
        if (el) {
            if (e.target.closest('[data-pqf-mas]')) cambiar(el.dataset.id, 1);
            else if (e.target.closest('[data-pqf-menos]')) cambiar(el.dataset.id, -1);
            else if (e.target.closest('[data-pqf-quitar]')) { elegidos.delete(el.dataset.id); pintar(); }
            return;
        }
        const pill = e.target.closest('[data-pqf-tipo]');
        if (pill) {
            tipo = pill.dataset.pqfTipo;
            form.querySelectorAll('[data-pqf-tipo]').forEach(p => p.classList.toggle('is-on', p === pill));
            filtrar();
        }
    });

    buscar.addEventListener('input', filtrar);
    // Enter en el buscador no envía el formulario.
    buscar.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });

    form.addEventListener('submit', function (e) {
        const nombre = form.querySelector('input[name="nombre"]');
        if (!nombre.value.trim()) {
            e.preventDefault();
            nombre.focus();
            window.showToast?.('Escribe el nombre del paquete.', 'error');
            return;
        }
        if (elegidos.size === 0) {
            e.preventDefault();
            window.showToast?.('Agrega al menos un producto al paquete.', 'error');
            return;
        }
        hidden.innerHTML = '';
        let i = 0;
        elegidos.forEach(function (cant, id) {
            hidden.insertAdjacentHTML('beforeend',
                '<input type="hidden" name="productos[' + i + '][id]" value="' + id + '">' +
                '<input type="hidden" name="productos[' + i + '][cantidad]" value="' + cant + '">');
            i++;
        });
    });

    pintar();
})();
</script>
