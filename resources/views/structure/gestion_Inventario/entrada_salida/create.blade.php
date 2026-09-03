@extends('layouts.dashboard')

@section('title', 'Nueva Entrada')
@section('page-title', 'Nueva Entrada')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Nueva entrada')

@push('head')
    <style>
        .rgrid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px 18px; }
        @media (max-width: 520px) { .rgrid-2 { grid-template-columns: 1fr; } }
        .evidencia-preview { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
        .evidencia-preview img { width:84px; height:84px; object-fit:cover; border-radius:8px; border:1px solid var(--border); }
        .unidad-row { display:grid; grid-template-columns: 32px 1fr 140px; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); }
        .unidad-row:last-child { border-bottom:none; }
        .unidad-row .unidad-num { color:var(--muted); font-size:13px; font-weight:600; }
        .unidad-row input[type="text"] { width:100%; padding:9px 10px; border:1px solid var(--border); border-radius:8px; font-size:14px; background:var(--surface); color:var(--text); }
        .unidad-row input[type="file"] { width:100%; font-size:12.5px; }
        .unidad-foto-preview { width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--border); display:none; margin-top:4px; }
    </style>
@endpush

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <p class="header-subtitle" style="margin:0;">Registra una entrada de inventario con evidencia de cómo llegó</p>
            </div>
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Regresar
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('inventory.movimientos.store') }}" enctype="multipart/form-data">
        @csrf
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">¿Qué llegó?</x-ui.section-title>
            <div id="modeloExistenteAviso" class="cat-aviso" style="display:none; margin-bottom:14px;"></div>
            <div class="rgrid-2">
                @include('structure.gestion_Inventario.productos._selects_catalogo')

                <x-ui.form-group label="Precio *" name="precio" type="number" step="0.01" min="0" placeholder="0.00" :required="true" />
                <x-ui.form-group label="Cantidad que llegó *" name="cantidad" type="number" min="1" placeholder="1" :required="true" />
                <x-ui.form-group label="Proveedor" name="proveedor" placeholder="Nombre del proveedor" />
                <x-ui.form-group label="Fecha de llegada *" for="movement_date">
                    <input id="movement_date" type="date" name="movement_date" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required
                           style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </x-ui.form-group>
            </div>

            <div style="display:flex; align-items:center; gap:8px; margin:6px 0 14px;">
                <input type="checkbox" id="es_serializado" name="es_serializado" value="1" style="width:17px; height:17px;" {{ old('es_serializado') ? 'checked' : '' }}>
                <label for="es_serializado" style="margin:0; font-size:14px; cursor:pointer;">Este producto maneja serie y foto individual por unidad</label>
            </div>

            <div id="series-texto-wrap">
                <x-ui.form-group label="Números de serie (uno por línea, opcional)" for="series_texto">
                    <textarea id="series_texto" name="series_texto" rows="3" placeholder="Déjalo vacío si estas unidades no tienen serial individual"
                              style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('series_texto') }}</textarea>
                    <small style="color:var(--muted);">Si capturas todas las series, deben ser exactamente tantas líneas como la cantidad de arriba. Si solo pones una y la cantidad es mayor a 1, el resto de la secuencia se genera solo (ej. 23A12345 → 23A12346, 23A12347...).</small>
                </x-ui.form-group>
            </div>

            <div id="unidades-wrap" style="display:none;">
                <x-ui.section-title style="margin:0 0 8px; font-size:14px;">Unidades (una por una, con su foto)</x-ui.section-title>
                <p style="margin:0 0 10px; color:var(--muted); font-size:13.5px;">
                    El número de serie es opcional por renglón, pero la foto de cada unidad es obligatoria.
                    Cambia la cantidad de arriba para agregar o quitar renglones.
                </p>
                <div id="unidades-rows"></div>
                @error('unidades')
                    <div style="color:var(--danger); font-size:13px; margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>

            <x-ui.form-group label="Descripción" for="descripcion">
                <textarea id="descripcion" name="descripcion" rows="3" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>

            <x-ui.form-group label="Notas de la entrada" for="notas">
                <textarea id="notas" name="notas" rows="2" placeholder="Ej. llegó en buen estado, caja abierta para inspección, etc."
                          style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('notas') }}</textarea>
            </x-ui.form-group>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 12px;">Foto del producto (catálogo)</x-ui.section-title>
            <p style="margin:0 0 12px; color:var(--muted); font-size:13.5px;">
                Es la foto representativa que se ve en el listado de Productos, no la evidencia de esta entrada.
                Si el modelo ya tiene una, no es necesario subir otra.
            </p>
            <div id="imagen-actual-wrap" style="display:none; margin-bottom:12px;">
                <img id="imagen-actual" src="" alt="Foto actual del producto" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid var(--border);">
                <div style="color:var(--muted); font-size:12.5px; margin-top:4px;">Foto actual. Sube una nueva abajo solo si quieres cambiarla.</div>
            </div>
            <input type="file" id="imagen" name="imagen" accept="image/*"
                   style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
            <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB.</small>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title id="evidencias-title" style="margin:0 0 12px;">Evidencia de la entrada *</x-ui.section-title>
            <p id="evidencias-help" style="margin:0 0 12px; color:var(--muted); font-size:13.5px;">
                Sube una o varias fotos que documenten cómo llegó este lote (caja, factura del proveedor, estado del equipo...).
                Es evidencia del envío completo, no se pide una foto por cada unidad.
            </p>
            <input type="file" id="evidencias" name="evidencias[]" accept="image/*" multiple required
                   style="width:100%; padding:8px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
            <small style="color:var(--muted);">Formatos: JPG, PNG, GIF. Máximo 5MB por foto.</small>
            <div id="evidencia-preview-wrap" class="evidencia-preview"></div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Registrar entrada</x-ui.button>
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const evidenciasInput = document.getElementById('evidencias');
                const previewWrap = document.getElementById('evidencia-preview-wrap');

                if (evidenciasInput && previewWrap) {
                    evidenciasInput.addEventListener('change', function () {
                        previewWrap.innerHTML = '';
                        Array.from(evidenciasInput.files || []).forEach(function (file) {
                            const url = URL.createObjectURL(file);
                            const img = document.createElement('img');
                            img.src = url;
                            previewWrap.appendChild(img);
                        });
                    });
                }

                // --- Renglones por unidad (serie + foto), cuando el
                // producto es_serializado ---
                const serializadoCheckbox = document.getElementById('es_serializado');
                const cantidadInput = document.getElementById('cantidad');
                const seriesTextoWrap = document.getElementById('series-texto-wrap');
                const unidadesWrap = document.getElementById('unidades-wrap');
                const unidadesRows = document.getElementById('unidades-rows');
                const evidenciasInputEl = document.getElementById('evidencias');
                const evidenciasTitle = document.getElementById('evidencias-title');
                const evidenciasHelp = document.getElementById('evidencias-help');
                let sugeridoBase = null;

                function incrementarSerial(base, delta) {
                    const m = /^(.*?)(\d+)$/.exec(base || '');
                    if (!m) return '';
                    const numero = parseInt(m[2], 10) + delta;
                    return m[1] + String(numero).padStart(m[2].length, '0');
                }

                function pintarUnidades() {
                    if (!unidadesRows) return;

                    const cantidad = Math.max(0, parseInt((cantidadInput && cantidadInput.value) || '0', 10) || 0);
                    const actuales = unidadesRows.querySelectorAll('.unidad-row').length;

                    if (cantidad === actuales) return;

                    unidadesRows.innerHTML = '';

                    for (let i = 0; i < cantidad; i++) {
                        const row = document.createElement('div');
                        row.className = 'unidad-row';

                        const sugerido = sugeridoBase ? incrementarSerial(sugeridoBase, i) : '';

                        row.innerHTML = `
                            <span class="unidad-num">#${i + 1}</span>
                            <input type="text" name="unidades[${i}][no_serie]" placeholder="No. de serie (opcional)" value="${sugerido}">
                            <div>
                                <input type="file" name="unidades[${i}][foto]" accept="image/*" required data-preview="foto-preview-${i}">
                                <img id="foto-preview-${i}" class="unidad-foto-preview" alt="Vista previa">
                            </div>
                        `;

                        unidadesRows.appendChild(row);
                    }

                    unidadesRows.querySelectorAll('input[type="file"]').forEach(function (input) {
                        input.addEventListener('change', function () {
                            const preview = document.getElementById(input.dataset.preview);
                            if (!preview || !input.files || !input.files[0]) return;
                            preview.src = URL.createObjectURL(input.files[0]);
                            preview.style.display = 'block';
                        });
                    });
                }

                function actualizarModoSerializado() {
                    const activo = serializadoCheckbox && serializadoCheckbox.checked;

                    if (seriesTextoWrap) seriesTextoWrap.style.display = activo ? 'none' : 'block';
                    if (unidadesWrap) unidadesWrap.style.display = activo ? 'block' : 'none';

                    if (evidenciasInputEl) evidenciasInputEl.required = !activo;
                    if (evidenciasTitle) evidenciasTitle.textContent = activo ? 'Evidencia general (opcional)' : 'Evidencia de la entrada *';
                    if (evidenciasHelp) {
                        evidenciasHelp.textContent = activo
                            ? 'Ya queda una foto por cada unidad; esto es solo evidencia adicional del envío completo si quieres agregarla (ej. factura del proveedor).'
                            : 'Sube una o varias fotos que documenten cómo llegó este lote (caja, factura del proveedor, estado del equipo...). Es evidencia del envío completo, no se pide una foto por cada unidad.';
                    }

                    if (activo) pintarUnidades();
                }

                if (serializadoCheckbox) {
                    serializadoCheckbox.addEventListener('change', actualizarModoSerializado);
                }

                if (cantidadInput) {
                    cantidadInput.addEventListener('input', function () {
                        if (serializadoCheckbox && serializadoCheckbox.checked) pintarUnidades();
                    });
                }

                actualizarModoSerializado();

                // Si el modelo elegido ya está registrado, se rellenan solos
                // precio, descripción, proveedor y se muestra la foto que ya
                // tiene. Cantidad y series no se tocan: son propios de esta
                // entrada.
                const modeloSelect = document.getElementById('equipment_model_id');
                const aviso = document.getElementById('modeloExistenteAviso');
                const precioInput = document.getElementById('precio');
                const descripcionInput = document.getElementById('descripcion');
                const proveedorInput = document.getElementById('proveedor');
                const seriesTextoInput = document.getElementById('series_texto');
                const imagenActualWrap = document.getElementById('imagen-actual-wrap');
                const imagenActual = document.getElementById('imagen-actual');
                const buscarPorModeloUrl = @json(route('inventory.productos.buscarPorModelo'));

                if (modeloSelect) {
                    modeloSelect.addEventListener('change', function () {
                        aviso.style.display = 'none';
                        imagenActualWrap.style.display = 'none';

                        if (!modeloSelect.value) return;

                        fetch(buscarPorModeloUrl + '?equipment_model_id=' + encodeURIComponent(modeloSelect.value), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (!data.existe) return;

                                if (precioInput) precioInput.value = data.precio ?? '';
                                if (descripcionInput && !descripcionInput.value) descripcionInput.value = data.descripcion ?? '';
                                if (proveedorInput && !proveedorInput.value) proveedorInput.value = data.proveedor ?? '';

                                let mensaje = 'Este modelo ya está registrado (stock actual: ' + data.stock_actual + '). Lo que llegue se agregará a esa misma fila. Se completaron precio, descripción y proveedor.';

                                sugeridoBase = data.no_serie_sugerido || null;

                                if (seriesTextoInput && !seriesTextoInput.value && data.no_serie_sugerido) {
                                    seriesTextoInput.value = data.no_serie_sugerido;
                                    mensaje += ' El número de serie se sugirió como ' + data.no_serie_sugerido + ' (consecutivo del último registrado).';
                                }

                                // No se deshabilita el checkbox (un input
                                // disabled no se envía en el formulario):
                                // solo se marca y se le avisa al usuario que
                                // este modelo ya quedó definido como
                                // serializado desde su primera entrada.
                                if (data.es_serializado && serializadoCheckbox) {
                                    serializadoCheckbox.checked = true;
                                    actualizarModoSerializado();
                                    mensaje += ' Este modelo ya se maneja con serie y foto por unidad.';
                                }

                                if (data.imagen) {
                                    imagenActual.src = data.imagen;
                                    imagenActualWrap.style.display = 'block';
                                    mensaje += ' Ya tiene foto de catálogo; solo sube una nueva si quieres cambiarla.';
                                } else {
                                    mensaje += ' Todavía no tiene foto de catálogo, considera subir una.';
                                }

                                aviso.textContent = mensaje;
                                aviso.style.display = 'block';
                            })
                            .catch(() => {});
                    });
                }
            });
        </script>
    @endpush
@endsection
