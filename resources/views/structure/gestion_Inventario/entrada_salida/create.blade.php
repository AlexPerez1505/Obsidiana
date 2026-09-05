@extends('layouts.dashboard')

@section('title', 'Nueva Entrada')
@section('page-title', 'Nueva Entrada')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Nueva entrada')

@push('head')
    <style>
        .rgrid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px 18px; }
        @media (max-width: 520px) { .rgrid-2 { grid-template-columns: 1fr; } }
        .signature-box { width:100%; height:160px; border:1px solid var(--border); border-radius:9px; background:#fff; touch-action:none; }

        /* --- Tarjeta por unidad --- */
        .unidad-card { border:1px solid var(--border); border-radius:14px; padding:16px; margin-bottom:14px; background:var(--surface); }
        .unidad-card-head { display:flex; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
        .unidad-badge { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:9px; background:var(--primary-soft); color:var(--primary); font-weight:800; font-size:13px; flex-shrink:0; }
        .unidad-card-head input[type="text"] { flex:1; min-width:160px; padding:9px 12px; border:1px solid var(--border); border-radius:8px; font-size:14px; background:var(--surface); color:var(--text); }
        .unidad-seccion-label { font-size:12.5px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; margin:14px 0 8px; display:block; }

        /* Slots de foto: botón grande con icono, no el input feo por defecto */
        .foto-slots { display:flex; gap:10px; flex-wrap:wrap; }
        .foto-slot { position:relative; width:96px; height:96px; border-radius:12px; border:2px dashed var(--border); background:var(--surface-2); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; cursor:pointer; overflow:hidden; transition:border-color .15s, background .15s, transform .1s; }
        .foto-slot:hover { border-color:var(--primary); background:var(--surface); }
        .foto-slot:active { transform:scale(.97); }
        .foto-slot input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
        .foto-slot-empty { display:flex; flex-direction:column; align-items:center; gap:4px; color:var(--muted); pointer-events:none; }
        .foto-slot-empty svg { width:22px; height:22px; }
        .foto-slot-empty span { font-size:11px; font-weight:600; }
        .foto-slot img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; }
        .foto-slot.has-image { border-style:solid; border-color:var(--border); }
        .foto-slot.has-image .foto-slot-empty { display:none; }
        .foto-slot.has-image img { display:block; }
        .foto-slot-remove { position:absolute; top:4px; right:4px; width:22px; height:22px; border-radius:50%; background:rgba(0,0,0,.62); color:#fff; border:none; display:none; align-items:center; justify-content:center; font-size:15px; line-height:1; cursor:pointer; z-index:2; }
        .foto-slot.has-image .foto-slot-remove { display:flex; }
        .foto-slot-num { position:absolute; bottom:3px; left:5px; font-size:10px; font-weight:700; color:var(--muted); background:rgba(255,255,255,.85); border-radius:5px; padding:1px 5px; z-index:1; }
        .foto-slot.has-image .foto-slot-num { display:none; }

        /* Slot de video: rectángulo ancho */
        .video-slot { position:relative; width:100%; min-height:74px; border-radius:12px; border:2px dashed var(--border); background:var(--surface-2); display:flex; align-items:center; gap:12px; padding:12px 16px; cursor:pointer; transition:border-color .15s, background .15s; }
        .video-slot:hover { border-color:var(--primary); background:var(--surface); }
        .video-slot input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
        .video-slot-icon { width:38px; height:38px; border-radius:10px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .video-slot-icon svg { width:19px; height:19px; }
        .video-slot-text { display:flex; flex-direction:column; gap:2px; }
        .video-slot-text strong { font-size:13.5px; }
        .video-slot-text span { font-size:12px; color:var(--muted); }
        .video-slot.has-video { border-style:solid; }
        .video-slot-remove { position:relative; z-index:2; margin-left:auto; width:26px; height:26px; border-radius:50%; background:var(--surface-2); border:1px solid var(--border); display:none; align-items:center; justify-content:center; font-size:15px; cursor:pointer; flex-shrink:0; }
        .video-slot.has-video .video-slot-remove { display:flex; }
        .unidad-video-preview { margin-top:10px; max-width:220px; border-radius:8px; border:1px solid var(--border); display:none; }
        .unidad-video-progreso { display:none; margin-top:8px; }
        .unidad-video-progreso .barra-wrap { height:7px; border-radius:5px; background:var(--border); overflow:hidden; }
        .unidad-video-progreso .barra { height:100%; width:0%; background:var(--primary); transition:width .15s; }
        .unidad-video-progreso .texto { font-size:12px; color:var(--muted); margin-top:4px; }
        .unidad-video-error { color:var(--danger); font-size:12.5px; margin-top:6px; display:none; }
        .unidad-error { color:var(--danger); font-size:12.5px; margin-top:8px; }
    </style>
@endpush

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <p class="header-subtitle" style="margin:0;">Registra una entrada de inventario con evidencia de cómo llegó cada unidad</p>
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
                Si no subes una y el modelo no tiene, se usa la primera foto de evidencia de la unidad #1.
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
            <x-ui.section-title style="margin:0 0 6px;">Evidencia de cada unidad *</x-ui.section-title>
            <p style="margin:0 0 14px; color:var(--muted); font-size:13.5px;">
                Cada unidad que llega tiene su propio espacio: sube de 1 a 3 fotos de cómo llegó esa pieza en particular
                (no es una evidencia general del lote). El video es opcional. El número de serie también es por unidad;
                si solo capturas el de la unidad #1, el resto de la secuencia se genera sola.
            </p>
            <div id="unidades-rows"></div>
            @error('unidades')
                <div style="color:var(--danger); font-size:13px; margin-top:6px;">{{ $message }}</div>
            @enderror
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 12px;">Firma digital de quien registró la entrada *</x-ui.section-title>
            <p style="margin:0 0 12px; color:var(--muted); font-size:13.5px;">
                Firma en el recuadro con el mouse o el dedo para confirmar quién capturó esta entrada.
            </p>
            <canvas class="signature-box" id="signature-pad"></canvas>
            <div style="margin-top:8px;">
                <a href="#" id="limpiar-firma" style="font-size:13px; color:var(--primary);">Limpiar firma</a>
            </div>
            <input type="hidden" name="firma" id="firma-input">
            @error('firma')
                <div style="color:var(--danger); font-size:13px; margin-top:6px;">{{ $message }}</div>
            @enderror
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button>Registrar entrada</x-ui.button>
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ICONO_CAMARA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
                const ICONO_VIDEO = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>';
                const VIDEO_CHUNK_URL = @json(route('inventory.movimientos.videoChunk'));
                const CHUNK_SIZE = 4 * 1024 * 1024; // 4MB por pedazo
                const EXTENSIONES_VALIDAS = ['mp4', 'mov', 'm4v', 'webm'];
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

                let videosSubiendo = 0;

                function toggleSubmit(deshabilitado) {
                    document.querySelectorAll('form button[type="submit"]').forEach(function (btn) {
                        btn.disabled = deshabilitado;
                    });
                }

                // --- Genera la tarjeta de una unidad: serie + 3 fotos + video ---
                function crearUnidadCard(index, sugerido) {
                    const card = document.createElement('div');
                    card.className = 'unidad-card';
                    card.dataset.index = index;

                    card.innerHTML = `
                        <div class="unidad-card-head">
                            <span class="unidad-badge">#${index + 1}</span>
                            <input type="text" name="unidades[${index}][no_serie]" placeholder="No. de serie de esta unidad (opcional)" value="${sugerido || ''}">
                        </div>

                        <span class="unidad-seccion-label">Fotos de cómo llegó esta unidad (1 a 3)</span>
                        <div class="foto-slots">
                            ${[0, 1, 2].map(function (slot) {
                                return `
                                <label class="foto-slot" data-slot="${slot}">
                                    <input type="file" name="unidades[${index}][evidencias][]" accept="image/*">
                                    <span class="foto-slot-num">${slot + 1}</span>
                                    <button type="button" class="foto-slot-remove" aria-label="Quitar foto">&times;</button>
                                    <span class="foto-slot-empty">${ICONO_CAMARA}<span>Foto ${slot + 1}</span></span>
                                    <img alt="Vista previa">
                                </label>`;
                            }).join('')}
                        </div>

                        <span class="unidad-seccion-label">Video de esta unidad (opcional)</span>
                        <label class="video-slot">
                            <input type="file" accept="video/*" class="unidad-video-input">
                            <span class="video-slot-icon">${ICONO_VIDEO}</span>
                            <span class="video-slot-text">
                                <strong class="video-slot-nombre">Subir video corto</strong>
                                <span>MP4, MOV o WEBM. Máximo 150MB.</span>
                            </span>
                            <button type="button" class="video-slot-remove" aria-label="Quitar video">&times;</button>
                        </label>
                        <input type="hidden" name="unidades[${index}][video_path]" class="unidad-video-path">
                        <div class="unidad-video-progreso">
                            <div class="barra-wrap"><div class="barra"></div></div>
                            <div class="texto">Subiendo video...</div>
                        </div>
                        <div class="unidad-video-error"></div>
                        <video class="unidad-video-preview" controls></video>
                        <div class="unidad-error" style="display:none;"></div>
                    `;

                    inicializarFotoSlots(card);
                    inicializarVideoSlot(card, index);

                    return card;
                }

                function inicializarFotoSlots(card) {
                    card.querySelectorAll('.foto-slot').forEach(function (slot) {
                        const input = slot.querySelector('input[type="file"]');
                        const img = slot.querySelector('img');
                        const removeBtn = slot.querySelector('.foto-slot-remove');

                        input.addEventListener('change', function () {
                            if (!input.files || !input.files[0]) {
                                slot.classList.remove('has-image');
                                img.src = '';
                                return;
                            }
                            img.src = URL.createObjectURL(input.files[0]);
                            slot.classList.add('has-image');
                        });

                        removeBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            input.value = '';
                            img.src = '';
                            slot.classList.remove('has-image');
                        });
                    });
                }

                function inicializarVideoSlot(card, index) {
                    const slot = card.querySelector('.video-slot');
                    const input = card.querySelector('.unidad-video-input');
                    const pathInput = card.querySelector('.unidad-video-path');
                    const preview = card.querySelector('.unidad-video-preview');
                    const progresoWrap = card.querySelector('.unidad-video-progreso');
                    const barra = card.querySelector('.unidad-video-progreso .barra');
                    const texto = card.querySelector('.unidad-video-progreso .texto');
                    const errorBox = card.querySelector('.unidad-video-error');
                    const nombre = card.querySelector('.video-slot-nombre');
                    const removeBtn = card.querySelector('.video-slot-remove');

                    async function subirPorChunks(file) {
                        errorBox.style.display = 'none';
                        pathInput.value = '';

                        const extension = (file.name.split('.').pop() || '').toLowerCase();
                        if (!EXTENSIONES_VALIDAS.includes(extension)) {
                            errorBox.textContent = 'Formato de video no permitido. Usa MP4, MOV o WEBM.';
                            errorBox.style.display = 'block';
                            input.value = '';
                            return;
                        }

                        const uploadId = (crypto.randomUUID ? crypto.randomUUID() : (Date.now() + '-' + Math.random().toString(36).slice(2))).replace(/[^a-zA-Z0-9-]/g, '');
                        const total = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));

                        videosSubiendo++;
                        toggleSubmit(true);
                        progresoWrap.style.display = 'block';

                        try {
                            for (let i = 0; i < total; i++) {
                                const inicio = i * CHUNK_SIZE;
                                const pedazo = file.slice(inicio, inicio + CHUNK_SIZE);

                                const formData = new FormData();
                                formData.append('chunk', pedazo, 'chunk');
                                formData.append('upload_id', uploadId);
                                formData.append('index', i);
                                formData.append('total', total);
                                formData.append('extension', extension);

                                const respuesta = await fetch(VIDEO_CHUNK_URL, {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                                    body: formData,
                                });

                                const json = await respuesta.json();

                                if (!respuesta.ok) {
                                    throw new Error(json.message || 'No se pudo subir el video.');
                                }

                                const porcentaje = Math.round(((i + 1) / total) * 100);
                                barra.style.width = porcentaje + '%';
                                texto.textContent = 'Subiendo video... ' + porcentaje + '%';

                                if (json.status === 'listo') {
                                    pathInput.value = json.video_path;
                                    texto.textContent = 'Video subido correctamente.';
                                }
                            }
                        } catch (err) {
                            errorBox.textContent = err.message || 'No se pudo subir el video. Vuelve a intentarlo.';
                            errorBox.style.display = 'block';
                            pathInput.value = '';
                            progresoWrap.style.display = 'none';
                            slot.classList.remove('has-video');
                        } finally {
                            videosSubiendo = Math.max(0, videosSubiendo - 1);
                            toggleSubmit(videosSubiendo > 0);
                        }
                    }

                    input.addEventListener('change', function () {
                        if (!input.files || !input.files[0]) return;

                        const file = input.files[0];
                        preview.src = URL.createObjectURL(file);
                        preview.style.display = 'block';
                        nombre.textContent = file.name;
                        slot.classList.add('has-video');
                        subirPorChunks(file);
                    });

                    removeBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        input.value = '';
                        pathInput.value = '';
                        preview.style.display = 'none';
                        preview.src = '';
                        nombre.textContent = 'Subir video corto';
                        slot.classList.remove('has-video');
                        progresoWrap.style.display = 'none';
                        errorBox.style.display = 'none';
                    });
                }

                // --- Renglones por unidad, tantos como diga "cantidad" ---
                const cantidadInput = document.getElementById('cantidad');
                const unidadesRows = document.getElementById('unidades-rows');
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
                    const actuales = unidadesRows.querySelectorAll('.unidad-card').length;

                    if (cantidad === actuales) return;

                    unidadesRows.innerHTML = '';

                    for (let i = 0; i < cantidad; i++) {
                        const sugerido = sugeridoBase ? incrementarSerial(sugeridoBase, i) : '';
                        unidadesRows.appendChild(crearUnidadCard(i, i === 0 ? sugerido : ''));
                    }
                }

                if (cantidadInput) {
                    cantidadInput.addEventListener('input', pintarUnidades);
                }

                pintarUnidades();

                const form = cantidadInput ? cantidadInput.closest('form') : null;
                if (form) {
                    form.addEventListener('submit', function (e) {
                        if (videosSubiendo > 0) {
                            e.preventDefault();
                            alert('Espera a que terminen de subirse los videos.');
                        }
                    });
                }

                // Si el modelo elegido ya está registrado, se rellenan solos
                // precio, descripción, proveedor y se muestra la foto que ya
                // tiene. Cantidad y series no se tocan: son propios de esta
                // entrada.
                const modeloSelect = document.getElementById('equipment_model_id');
                const aviso = document.getElementById('modeloExistenteAviso');
                const precioInput = document.getElementById('precio');
                const descripcionInput = document.getElementById('descripcion');
                const proveedorInput = document.getElementById('proveedor');
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

                                if (sugeridoBase) {
                                    const primerInput = unidadesRows.querySelector('.unidad-card[data-index="0"] input[type="text"]');
                                    if (primerInput && !primerInput.value) {
                                        primerInput.value = sugeridoBase;
                                    }
                                    mensaje += ' El número de serie de la unidad #1 se sugirió como ' + sugeridoBase + ' (consecutivo del último registrado).';
                                }

                                if (data.imagen) {
                                    imagenActual.src = data.imagen;
                                    imagenActualWrap.style.display = 'block';
                                    mensaje += ' Ya tiene foto de catálogo; solo sube una nueva si quieres cambiarla.';
                                } else {
                                    mensaje += ' Todavía no tiene foto de catálogo, se usará la primera foto de evidencia de la unidad #1.';
                                }

                                aviso.textContent = mensaje;
                                aviso.style.display = 'block';
                            })
                            .catch(() => {});
                    });
                }

                // --- Firma digital de quien registró la entrada ---
                const signatureCanvas = document.getElementById('signature-pad');
                const firmaInput = document.getElementById('firma-input');
                const limpiarFirma = document.getElementById('limpiar-firma');

                if (signatureCanvas && firmaInput) {
                    const ctx = signatureCanvas.getContext('2d');

                    function resizeSignatureCanvas() {
                        const rect = signatureCanvas.getBoundingClientRect();
                        signatureCanvas.width = rect.width;
                        signatureCanvas.height = rect.height;
                        ctx.lineWidth = 2;
                        ctx.lineCap = 'round';
                        ctx.strokeStyle = '#1a1a1a';
                    }
                    resizeSignatureCanvas();
                    window.addEventListener('resize', resizeSignatureCanvas);

                    function updateFirmaInput() {
                        firmaInput.value = signatureCanvas.toDataURL('image/png');
                    }

                    let firmando = false;
                    signatureCanvas.addEventListener('mousedown', function (e) {
                        firmando = true;
                        ctx.beginPath();
                        ctx.moveTo(e.offsetX, e.offsetY);
                    });
                    signatureCanvas.addEventListener('mousemove', function (e) {
                        if (!firmando) return;
                        ctx.lineTo(e.offsetX, e.offsetY);
                        ctx.stroke();
                    });
                    signatureCanvas.addEventListener('mouseup', function () { firmando = false; updateFirmaInput(); });
                    signatureCanvas.addEventListener('mouseout', function () { firmando = false; updateFirmaInput(); });

                    signatureCanvas.addEventListener('touchstart', function (e) {
                        e.preventDefault();
                        firmando = true;
                        const t = e.touches[0];
                        const r = signatureCanvas.getBoundingClientRect();
                        ctx.beginPath();
                        ctx.moveTo(t.clientX - r.left, t.clientY - r.top);
                    });
                    signatureCanvas.addEventListener('touchmove', function (e) {
                        e.preventDefault();
                        if (!firmando) return;
                        const t = e.touches[0];
                        const r = signatureCanvas.getBoundingClientRect();
                        ctx.lineTo(t.clientX - r.left, t.clientY - r.top);
                        ctx.stroke();
                    });
                    signatureCanvas.addEventListener('touchend', function () { firmando = false; updateFirmaInput(); });

                    if (limpiarFirma) {
                        limpiarFirma.addEventListener('click', function (e) {
                            e.preventDefault();
                            ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                            firmaInput.value = '';
                        });
                    }

                    const firmaForm = signatureCanvas.closest('form');
                    if (firmaForm) {
                        firmaForm.addEventListener('submit', function (e) {
                            if (!firmaInput.value) {
                                e.preventDefault();
                                alert('Firma en el recuadro antes de registrar la entrada.');
                                signatureCanvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    }
                }
            });
        </script>
    @endpush
@endsection
