@extends('layouts.dashboard')
@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto')
@section('page-sub', trim($producto->tipo_equipo.' '.$producto->marca.' '.$producto->modelo))

@php
    $yo = auth()->user();
    $disco = \Illuminate\Support\Facades\Storage::disk(config('filesystems.fotos_disk', 'public'));

    /*
    | Cada pieza se arma una sola vez: lo mismo alimenta la fila, el buscador
    | (data-buscar) y el modal de fotos (data-fotos).
    */
    $filas = $seriales->map(function ($s) use ($yo, $disco) {
        $rutas = array_values(array_filter($s->evidence_paths ?: [$s->foto_path]));
        $enProceso = ! $s->vendido && in_array($s->estado, \App\Models\ProductoSerial::NO_VENDIBLES, true);

        return [
            'pieza' => $s,
            'editable' => \App\Support\EdicionPropia::puedeEditarPieza($yo, $s),
            'borrable' => ! $s->vendido && \App\Support\EdicionPropia::puedeEliminarPieza($yo, $s),
            'fotos' => collect($rutas)->map(fn ($r) => ['path' => $r, 'url' => $disco->url($r)])->all(),
            'grupo' => $s->vendido ? 'vendida' : ($enProceso ? 'proceso' : 'disponible'),
            'buscar' => mb_strtolower(implode(' ', array_filter([$s->no_serie, $s->codigo, $s->estadoLabel(), $s->entrada?->folio]))),
        ];
    });

    $editables = $filas->where('editable', true)->count();
@endphp

@push('head')
    <style>
        .rgrid-2 { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px 18px; }
        @media (max-width:520px) { .rgrid-2 { grid-template-columns:1fr; } }

        .ed-plazo { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; margin-bottom:16px;
                    border:1px solid var(--border); border-radius:10px; background:var(--primary-soft);
                    color:var(--text); font-size:13.5px; line-height:1.5; }
        .ed-plazo svg { flex:none; margin-top:2px; color:var(--primary); }

        .ed-dato { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px 18px; font-size:14px; }
        .ed-dato span { display:block; color:var(--muted); font-size:12px; }

        /* Herramientas de la tabla de piezas */
        .pz-barra { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin:14px 0 10px; }
        .pz-barra input[type=search] { flex:1 1 220px; min-width:0; }
        .pz-barra select { flex:0 1 210px; }
        .pz-conteo { color:var(--muted); font-size:12.5px; margin:0 0 8px; }

        .pz-tabla { overflow-x:auto; }
        .pz-tabla td { vertical-align:middle; }
        .pz-tabla .cod { font-family:ui-monospace, Consolas, monospace; font-weight:700; font-size:12.5px; white-space:nowrap; }
        .pz-tabla .s { display:block; color:var(--muted); font-size:12px; margin-top:2px; }
        .pz-serie { width:100%; min-width:150px; font-family:ui-monospace, Consolas, monospace; }
        tr.pz-cambiada .pz-serie { border-color:var(--primary); background:var(--primary-soft); }
        .pz-fotos { display:flex; gap:4px; align-items:center; }
        .pz-fotos img { width:34px; height:34px; object-fit:cover; border-radius:6px; border:1px solid var(--border); }
        .pz-acciones { display:flex; gap:6px; justify-content:flex-end; white-space:nowrap; }
        .pz-vacio { padding:22px; text-align:center; color:var(--muted); font-size:13.5px; }

        /* Barra que aparece cuando hay series sin guardar */
        .pz-guardar { position:sticky; bottom:12px; z-index:20; display:flex; align-items:center; gap:10px;
                      flex-wrap:wrap; margin-top:12px; padding:10px 14px; border:1px solid var(--border);
                      border-radius:12px; background:var(--surface); box-shadow:0 6px 18px rgba(15,23,42,.08); }
        .pz-guardar .txt { flex:1; min-width:160px; font-size:13.5px; }
        /* .btn y la barra traen su propio display: sin esto, hidden no los esconde. */
        .pz-guardar[hidden], .btn[hidden] { display:none; }

        .pz-modal { width:min(520px, calc(100vw - 32px)); max-height:calc(100vh - 48px); padding:22px;
                    border:1px solid var(--border); border-radius:16px; background:var(--surface); color:var(--text); }
        .pz-modal::backdrop { background:rgba(15,23,42,.45); }
        .pz-modal-pie { display:flex; justify-content:flex-end; gap:10px; margin-top:16px; }
        .pz-actuales { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin:6px 0 4px; }
        .pz-actual { position:relative; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
        .pz-actual img { display:block; width:100%; aspect-ratio:1; object-fit:cover; }
        .pz-actual label { display:flex; gap:6px; align-items:center; padding:6px 8px; font-size:12px; cursor:pointer; }
        .pz-actual.fuera img { opacity:.35; }
        .pz-nuevas { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .pz-nuevas .mini { position:relative; width:64px; height:64px; }
        .pz-nuevas .mini img { width:100%; height:100%; object-fit:cover; border-radius:8px; border:1px solid var(--border); }
        .pz-nuevas .mini button { position:absolute; top:-6px; right:-6px; width:22px; height:22px; border-radius:50%;
                                  border:1px solid var(--border); background:var(--surface); color:var(--text); cursor:pointer; line-height:1; }
    </style>
@endpush

@section('content')
    @if ($vencePlazo && ! $yo->can('inventario.editar'))
        <div class="ed-plazo">
            <x-ui.icon name="clock" />
            <span>
                Tú registraste este producto: puedes corregirlo o eliminarlo durante
                <b>{{ \App\Support\EdicionPropia::restanteTexto($vencePlazo) }}</b> más. Después solo podrá quien tenga permiso de editar inventario.
            </span>
        </div>
    @elseif (! $puedeProducto && $editables)
        <div class="ed-plazo">
            <x-ui.icon name="clock" />
            <span>
                Puedes corregir las piezas que registraste en las últimas {{ \App\Support\EdicionPropia::HORAS }} horas.
                Los datos del producto solo los cambia quien tiene permiso de editar inventario.
            </span>
        </div>
    @endif

    @if ($puedeProducto)
        <form method="POST" action="{{ route('inventory.productos.update', $producto) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Datos del producto</x-ui.section-title>
                <div class="rgrid-2">
                    @include('structure.gestion_Inventario.productos._selects_catalogo')

                    {{-- El campo solo se dibuja para quien tiene precios.editar. --}}
                    @if (\App\Support\PrecioVisible::editable())
                        <x-ui.form-group label="Precio de venta" name="precio" type="number" step="0.01" min="0" :value="$producto->precio" />
                    @endif

                    <x-ui.form-group label="Referencia" name="referencia" maxlength="120"
                                     :value="$producto->referencia" placeholder="Ej. REF o número de parte (opcional)" />

                    <x-ui.form-group label="Stock" for="stock_display">
                        <input id="stock_display" type="number" value="{{ $producto->stock }}" disabled
                               style="background:var(--surface-2); color:var(--muted);">
                        <input type="hidden" name="stock" value="{{ $producto->stock }}">
                        <small class="campo-nota">Se calcula solo, según las piezas de abajo.</small>
                    </x-ui.form-group>
                </div>
                <x-ui.form-group label="Descripción" for="descripcion">
                    <textarea id="descripcion" name="descripcion" rows="3">{{ old('descripcion', $producto->descripcion) }}</textarea>
                </x-ui.form-group>
                <x-ui.form-group label="Imagen del producto" for="imagen">
                    @if ($producto->imagen_path)
                        <div style="margin-bottom:10px;">
                            <img src="{{ asset('storage/'.$producto->imagen_path) }}" alt="Imagen actual" style="max-height:150px; border-radius:8px; border:1px solid var(--border);">
                            <small class="campo-nota">Imagen actual</small>
                        </div>
                    @endif
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <small class="campo-nota">JPG, PNG o GIF. Máximo 5 MB. Déjalo vacío para conservar la actual.</small>
                </x-ui.form-group>
            </x-ui.card>

            <div style="display:flex; gap:10px; margin-bottom:18px;">
                <x-ui.button>Actualizar producto</x-ui.button>
                <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost">Cancelar</a>
            </div>
        </form>
    @else
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 12px;">Datos del producto</x-ui.section-title>
            <div class="ed-dato">
                <div><span>Equipo</span>{{ trim($producto->tipo_equipo.' '.$producto->subtipo) ?: '—' }}</div>
                <div><span>Marca y modelo</span>{{ trim($producto->marca.' '.$producto->modelo) ?: '—' }}</div>
                <div><span>Referencia</span>{{ $producto->referencia ?: '—' }}</div>
                <div><span>Stock</span>{{ $producto->stock }}</div>
            </div>
        </x-ui.card>
    @endif

    {{-- ===================== Piezas ===================== --}}
    <x-ui.card>
        <x-ui.section-title style="margin:0 0 4px;">Piezas ({{ $seriales->count() }})</x-ui.section-title>
        <p class="campo-nota" style="margin:0;">
            Busca la pieza por su serie o etiqueta. Las series se corrigen aquí mismo, en la tabla,
            y se guardan todas juntas. Con lector o con Enter pasas a la siguiente.
        </p>

        @if ($seriales->isEmpty())
            <p class="pz-vacio">No hay piezas registradas.</p>
        @else
            <div class="pz-barra">
                <input type="search" data-pz-buscar placeholder="Buscar por serie, etiqueta o folio de entrada…" aria-label="Buscar pieza">
                <select data-pz-filtro aria-label="Filtrar piezas">
                    <option value="">Todas</option>
                    <option value="disponible">Disponibles</option>
                    <option value="proceso">En proceso</option>
                    <option value="vendida">Vendidas</option>
                    <option value="sin-serie">Sin número de serie</option>
                    @if ($editables)
                        <option value="editable">Las que puedo corregir</option>
                    @endif
                </select>
                @if ($editables)
                    <button type="button" class="btn btn--ghost" data-pz-pegar>Pegar varias series</button>
                @endif
            </div>
            <p class="pz-conteo" data-pz-conteo></p>

            <form method="POST" action="{{ route('inventory.productos.seriales.lote', $producto) }}" id="formLote">
                @csrf
                @method('PUT')

                <div class="pz-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>Etiqueta</th>
                                <th>No. de serie</th>
                                <th>Fotos</th>
                                <th>Estado</th>
                                <th>Entró</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filas as $fila)
                                @php($s = $fila['pieza'])
                                <tr data-pz
                                    data-buscar="{{ $fila['buscar'] }}"
                                    data-grupo="{{ $fila['grupo'] }}"
                                    data-sin-serie="{{ $s->no_serie ? '0' : '1' }}"
                                    data-editable="{{ $fila['editable'] ? '1' : '0' }}">
                                    <td class="cod">{{ $s->codigo ?: '—' }}</td>
                                    <td>
                                        @if ($fila['editable'])
                                            <input type="text" class="pz-serie" name="series[{{ $s->id }}]"
                                                   value="{{ old('series.'.$s->id, $s->no_serie) }}"
                                                   data-original="{{ $s->no_serie }}" maxlength="255"
                                                   placeholder="Sin serie" aria-label="Serie de la pieza {{ $s->codigo }}">
                                        @else
                                            {{ $s->no_serie ?: '— sin serie' }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="pz-fotos">
                                            @forelse (array_slice($fila['fotos'], 0, 3) as $foto)
                                                <a href="{{ $foto['url'] }}" target="_blank" rel="noopener"><img src="{{ $foto['url'] }}" alt="Foto de la pieza" loading="lazy"></a>
                                            @empty
                                                <span class="s">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $s->vendido ? '' : ($fila['grupo'] === 'proceso' ? 'badge--info' : 'badge--ok') }}">{{ $s->estadoLabel() }}</span>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        {{ $s->entrada?->movement_date?->format('d/m/Y') ?? $s->created_at?->format('d/m/Y') }}
                                        @if ($s->entrada)<span class="s">{{ $s->entrada->folio }}</span>@endif
                                    </td>
                                    <td>
                                        <div class="pz-acciones">
                                            @if ($fila['editable'])
                                                <button type="button" class="btn btn--ghost" style="padding:5px 10px; font-size:12.5px;"
                                                        data-pz-fotos="{{ json_encode($fila['fotos']) }}"
                                                        data-url="{{ route('inventory.productos.seriales.update', $s) }}"
                                                        data-codigo="{{ $s->codigo }}">Fotos</button>
                                            @endif
                                            @if ($fila['borrable'])
                                                <button type="button" class="btn-icono" title="Eliminar pieza" aria-label="Eliminar pieza {{ $s->codigo }}"
                                                        data-pz-borrar="{{ route('inventory.productos.seriales.destroy', $s) }}"
                                                        data-codigo="{{ $s->codigo }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="pz-vacio" data-pz-vacio hidden>Ninguna pieza coincide con la búsqueda.</p>

                <div style="text-align:center; margin-top:10px;">
                    <button type="button" class="btn btn--ghost" data-pz-mas hidden>Mostrar más</button>
                </div>

                <div class="pz-guardar" data-pz-guardar hidden>
                    <span class="txt" data-pz-cambios></span>
                    <button type="button" class="btn btn--ghost" data-pz-descartar>Descartar</button>
                    <button type="submit" class="btn">Guardar series</button>
                </div>
            </form>
        @endif
    </x-ui.card>

    @if ($puedeAgregar)
        <x-ui.card style="margin-top:18px;">
            <x-ui.section-title style="margin:0 0 6px;">Agregar piezas (ajuste rápido, sin evidencia)</x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 12px;">
                Úsalo solo para correcciones puntuales. Si llegó mercancía de verdad, regístrala como
                <a href="{{ route('inventory.movimientos.create') }}" class="link">Entrada</a> para dejar su evidencia.
            </p>
            <form method="POST" action="{{ route('inventory.productos.seriales.store', $producto) }}">
                @csrf
                <div class="rgrid-2">
                    <x-ui.form-group label="Cantidad a agregar *" name="cantidad" type="number" min="1" placeholder="1" :required="true" />
                </div>
                <x-ui.form-group label="Números de serie (uno por línea, opcional)" for="series_texto">
                    <textarea id="series_texto" name="series_texto" rows="3" placeholder="Una serie por pieza. Déjalo vacío si no tienen serie.">{{ old('series_texto') }}</textarea>
                    <small class="campo-nota">Si pones todas, deben ser tantas líneas como la cantidad. Si pones solo una y la cantidad es mayor, el resto sigue la secuencia (23A12345 → 23A12346…).</small>
                </x-ui.form-group>
                <x-ui.button type="submit">Agregar al inventario</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    {{-- Un solo modal para las fotos de cualquier pieza (antes había uno por pieza). --}}
    <dialog id="modalFotos" class="pz-modal">
        <form method="POST" action="" enctype="multipart/form-data" id="formFotos">
            @csrf
            @method('PUT')

            <x-ui.section-title style="margin:0 0 4px;">Fotos de la pieza <span data-mf-codigo></span></x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 10px;">
                Hasta 3 por pieza. Las nuevas se suman a las que ya tiene; marca «Quitar» en las que ya no sirvan.
            </p>

            <div class="pz-actuales" data-mf-actuales></div>

            <x-ui.form-group label="Agregar fotos" for="mf-input" style="margin-top:12px;">
                <input type="file" id="mf-input" accept="image/*" multiple>
                <small class="campo-nota" data-mf-nota></small>
            </x-ui.form-group>
            <div class="pz-nuevas" data-mf-nuevas></div>
            <input type="file" name="fotos[]" multiple hidden data-mf-envio>

            <div class="pz-modal-pie">
                <button type="button" class="btn btn--ghost" data-mf-cerrar>Cancelar</button>
                <button type="submit" class="btn">Guardar fotos</button>
            </div>
        </form>
    </dialog>

    {{-- Pegar una columna de series (por ejemplo, de Excel) en las piezas que se ven. --}}
    <dialog id="modalPegar" class="pz-modal">
        <x-ui.section-title style="margin:0 0 4px;">Pegar varias series</x-ui.section-title>
        <p class="campo-nota" style="margin:0 0 10px;">
            Una serie por renglón. Se ponen en las piezas que ves ahora en la tabla, de arriba hacia abajo,
            solo en las que puedes corregir. Después revisa y dale «Guardar series».
        </p>
        <textarea rows="8" data-pg-texto placeholder="23A12345&#10;23A12346&#10;…" style="width:100%; font-family:ui-monospace, Consolas, monospace;"></textarea>
        <label style="display:flex; gap:8px; align-items:center; margin-top:10px; font-size:13.5px;">
            <input type="checkbox" data-pg-solo-vacias checked> Solo en las piezas que no tienen serie
        </label>
        <div class="pz-modal-pie">
            <button type="button" class="btn btn--ghost" data-pg-cerrar>Cancelar</button>
            <button type="button" class="btn" data-pg-aplicar>Poner series</button>
        </div>
    </dialog>

    <form method="POST" action="" id="formBorrarPieza" hidden>
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
        <script>
        (function () {
            const POR_PAGINA = 25;
            const MAX_FOTOS = 3;

            const filas = Array.from(document.querySelectorAll('[data-pz]'));
            const buscar = document.querySelector('[data-pz-buscar]');
            const filtro = document.querySelector('[data-pz-filtro]');
            const conteo = document.querySelector('[data-pz-conteo]');
            const vacio = document.querySelector('[data-pz-vacio]');
            const btnMas = document.querySelector('[data-pz-mas]');
            const barra = document.querySelector('[data-pz-guardar]');
            const textoCambios = document.querySelector('[data-pz-cambios]');
            const formLote = document.getElementById('formLote');

            let visibles = POR_PAGINA;

            const normalizar = (t) => (t || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
            filas.forEach(tr => { tr.dataset.buscar = normalizar(tr.dataset.buscar); });

            function pasaFiltro(tr, f) {
                if (!f) return true;
                if (f === 'sin-serie') return tr.dataset.sinSerie === '1';
                if (f === 'editable') return tr.dataset.editable === '1';
                return tr.dataset.grupo === f;
            }

            // Las que coinciden con búsqueda y filtro, en el orden de la tabla.
            function coincidentes() {
                const q = normalizar(buscar?.value);
                const f = filtro?.value || '';

                return filas.filter(tr => (!q || tr.dataset.buscar.includes(q)) && pasaFiltro(tr, f));
            }

            function aplicar() {
                const lista = coincidentes();

                filas.forEach(tr => { tr.hidden = true; });
                lista.slice(0, visibles).forEach(tr => { tr.hidden = false; });

                if (conteo) {
                    const mostradas = Math.min(visibles, lista.length);
                    conteo.textContent = lista.length === filas.length
                        ? `Mostrando ${mostradas} de ${filas.length} piezas`
                        : `${lista.length} de ${filas.length} piezas coinciden · mostrando ${mostradas}`;
                }

                if (vacio) vacio.hidden = lista.length > 0;
                if (btnMas) btnMas.hidden = lista.length <= visibles;
            }

            buscar?.addEventListener('input', () => { visibles = POR_PAGINA; aplicar(); });
            filtro?.addEventListener('change', () => { visibles = POR_PAGINA; aplicar(); });
            btnMas?.addEventListener('click', () => { visibles += POR_PAGINA * 2; aplicar(); });

            /* ===== Series cambiadas: se marcan y se guardan juntas ===== */
            const inputs = Array.from(document.querySelectorAll('.pz-serie'));

            function revisarCambios() {
                let n = 0;

                inputs.forEach(function (input) {
                    const cambio = input.value.trim() !== (input.dataset.original || '');
                    input.closest('tr')?.classList.toggle('pz-cambiada', cambio);
                    if (cambio) n++;
                });

                if (barra) barra.hidden = n === 0;
                if (textoCambios) textoCambios.textContent = n === 1 ? '1 serie sin guardar' : n + ' series sin guardar';
            }

            inputs.forEach(function (input, i) {
                input.addEventListener('input', revisarCambios);

                // Enter (o el lector, que manda Enter) pasa a la siguiente
                // pieza visible en vez de enviar el formulario.
                input.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();

                    const siguiente = inputs.slice(i + 1).find(x => !x.closest('tr')?.hidden);
                    if (siguiente) { siguiente.focus(); siguiente.select(); }
                });
            });

            document.querySelector('[data-pz-descartar]')?.addEventListener('click', function () {
                inputs.forEach(input => { input.value = input.dataset.original || ''; });
                revisarCambios();
            });

            // Solo viaja lo que cambió: con 500 piezas no tiene caso mandar todo.
            formLote?.addEventListener('submit', function (e) {
                const cambiados = inputs.filter(x => x.value.trim() !== (x.dataset.original || ''));

                if (!cambiados.length) {
                    e.preventDefault();
                    window.showToast?.('No hay series nuevas que guardar.', 'err');
                    return;
                }

                inputs.forEach(x => { x.disabled = !cambiados.includes(x); });
            });

            /* ===== Pegar una columna de series ===== */
            const modalPegar = document.getElementById('modalPegar');

            document.querySelector('[data-pz-pegar]')?.addEventListener('click', () => modalPegar.showModal());
            modalPegar?.querySelector('[data-pg-cerrar]')?.addEventListener('click', () => modalPegar.close());

            modalPegar?.querySelector('[data-pg-aplicar]')?.addEventListener('click', function () {
                const series = (modalPegar.querySelector('[data-pg-texto]').value || '')
                    .split(/[\r\n\t,;]+/).map(t => t.trim()).filter(Boolean);
                const soloVacias = modalPegar.querySelector('[data-pg-solo-vacias]').checked;

                // En el orden en que se ven: lo filtrado, aunque esté en "mostrar más".
                const destino = coincidentes()
                    .map(tr => tr.querySelector('.pz-serie'))
                    .filter(x => x && (!soloVacias || !x.value.trim()));

                const n = Math.min(series.length, destino.length);
                for (let i = 0; i < n; i++) destino[i].value = series[i];

                revisarCambios();
                modalPegar.close();

                window.showToast?.(n
                    ? `Se pusieron ${n} serie(s). Revísalas y dale «Guardar series».`
                    : 'No había piezas donde ponerlas.', n ? 'ok' : 'err');

                if (series.length > destino.length && destino.length) {
                    window.showToast?.(`Sobraron ${series.length - destino.length} serie(s): no había más piezas.`, 'err');
                }
            });

            /* ===== Fotos de una pieza (un solo modal) ===== */
            const modalFotos = document.getElementById('modalFotos');
            const formFotos = document.getElementById('formFotos');
            const actuales = modalFotos?.querySelector('[data-mf-actuales]');
            const elegir = document.getElementById('mf-input');
            const envio = modalFotos?.querySelector('[data-mf-envio]');
            const nuevas = modalFotos?.querySelector('[data-mf-nuevas]');
            const nota = modalFotos?.querySelector('[data-mf-nota]');
            let fotosNuevas = [];

            function espacio() {
                const quedan = actuales.querySelectorAll('input[name="quitar_fotos[]"]:not(:checked)').length;
                return MAX_FOTOS - quedan - fotosNuevas.length;
            }

            function pintarNuevas() {
                const dt = new DataTransfer();
                fotosNuevas.forEach(f => dt.items.add(f));
                envio.files = dt.files;

                nuevas.innerHTML = '';
                fotosNuevas.forEach(function (f, i) {
                    const caja = document.createElement('div');
                    caja.className = 'mini';
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(f);
                    const quitar = document.createElement('button');
                    quitar.type = 'button';
                    quitar.textContent = '×';
                    quitar.setAttribute('aria-label', 'Quitar foto nueva');
                    quitar.addEventListener('click', () => { fotosNuevas.splice(i, 1); pintarNuevas(); });
                    caja.append(img, quitar);
                    nuevas.appendChild(caja);
                });

                const libre = espacio();
                nota.textContent = libre > 0
                    ? `Caben ${libre} foto(s) más. Toma una y luego otra: se van sumando.`
                    : 'Ya tiene 3 fotos. Quita alguna para agregar otra.';
            }

            document.addEventListener('click', function (e) {
                const boton = e.target.closest('[data-pz-fotos]');
                if (boton) {
                    formFotos.action = boton.dataset.url;
                    modalFotos.querySelector('[data-mf-codigo]').textContent = boton.dataset.codigo || '';
                    fotosNuevas = [];

                    actuales.innerHTML = '';
                    JSON.parse(boton.dataset.pzFotos || '[]').forEach(function (foto) {
                        const caja = document.createElement('div');
                        caja.className = 'pz-actual';
                        caja.innerHTML = `<img alt="Foto actual"><label><input type="checkbox" name="quitar_fotos[]"> Quitar</label>`;
                        caja.querySelector('img').src = foto.url;
                        const check = caja.querySelector('input');
                        check.value = foto.path;
                        check.addEventListener('change', () => { caja.classList.toggle('fuera', check.checked); pintarNuevas(); });
                        actuales.appendChild(caja);
                    });

                    if (!actuales.children.length) {
                        actuales.innerHTML = '<p class="campo-nota" style="grid-column:1/-1; margin:0;">Esta pieza todavía no tiene fotos.</p>';
                    }

                    pintarNuevas();
                    modalFotos.showModal();
                    return;
                }

                if (e.target === modalFotos || e.target.closest('[data-mf-cerrar]')) modalFotos.close();
            }, true);

            elegir?.addEventListener('change', function () {
                const elegidas = Array.from(elegir.files || []);
                const libre = Math.max(0, espacio());

                if (elegidas.length > libre) {
                    window.showToast?.('Cada pieza lleva máximo 3 fotos.', 'err');
                }

                fotosNuevas = fotosNuevas.concat(elegidas.slice(0, libre));
                elegir.value = '';
                pintarNuevas();
            });

            formFotos?.addEventListener('submit', function (e) {
                const quitar = actuales.querySelectorAll('input[name="quitar_fotos[]"]:checked').length;
                if (!fotosNuevas.length && !quitar) {
                    e.preventDefault();
                    window.showToast?.('No hay cambios en las fotos.', 'err');
                }
            });

            /* ===== Eliminar una pieza ===== */
            document.addEventListener('click', async function (e) {
                const boton = e.target.closest('[data-pz-borrar]');
                if (!boton) return;

                const ok = await window.confirmModal({
                    title: '¿Eliminar la pieza?',
                    message: `La pieza ${boton.dataset.codigo || ''} sale del inventario y baja el stock.`,
                    confirmText: 'Eliminar',
                    danger: true,
                });

                if (!ok) return;

                const form = document.getElementById('formBorrarPieza');
                form.action = boton.dataset.pzBorrar;
                form.submit();
            });

            aplicar();
            revisarCambios();
        })();
        </script>
    @endpush
@endsection
