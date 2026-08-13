@extends('layouts.dashboard')

@section('title', 'Nuevo Movimiento')
@section('page-title', 'Nuevo Movimiento')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Nuevo')

@push('head')
<style>
    .movement-form {
        display: grid;
        gap: 18px;
    }

    .form-section {
        padding: 18px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #e2e8f0;
    }

    .form-section h3 {
        margin: 0 0 14px;
        font-size: 14px;
        font-weight: 800;
        color: #111827;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 14px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .form-field label {
        color: #718096;
        font-size: 12px;
        font-weight: 700;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        padding: 9px 11px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .14);
    }

    .form-field textarea {
        min-height: 70px;
        resize: vertical;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
    }

    .package-section {
        display: none;
    }

    .package-section.is-active {
        display: block;
    }

    :root[data-theme="dark"] .form-section {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .form-field input,
    :root[data-theme="dark"] .form-field select,
    :root[data-theme="dark"] .form-field textarea {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .form-field label {
        color: var(--muted);
    }

    .dropzone {
        position: relative;
        padding: 24px;
        border: 2px dashed #158be8;
        border-radius: 10px;
        background: #f5fbff;
        text-align: center;
        color: #64748b;
        cursor: pointer;
        transition: background .2s, border-color .2s;
    }

    .dropzone.is-dragover {
        background: #e6f4ff;
        border-color: #0f6fbd;
    }

    .dropzone input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .dropzone svg {
        width: 40px;
        height: 40px;
        color: #158be8;
        margin-bottom: 8px;
    }

    .dropzone p {
        margin: 4px 0;
        font-size: 13px;
    }

    .dropzone .dropzone-title {
        font-weight: 800;
        color: #111827;
    }

    .dropzone .dropzone-hint {
        font-size: 11px;
        color: #718096;
    }

    .dropzone button {
        margin-top: 10px;
        padding: 8px 14px;
        border: 0;
        border-radius: 5px;
        background: #158be8;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        position: relative;
        z-index: 1;
    }

    .dropzone button:hover {
        background: #0f6fbd;
    }

    .preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }

    .preview img,
    .preview video {
        width: 90px;
        height: 70px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
    }

    :root[data-theme="dark"] .dropzone {
        background: rgba(21, 139, 232, .10);
        border-color: #158be8;
    }

    :root[data-theme="dark"] .dropzone.is-dragover {
        background: rgba(21, 139, 232, .20);
    }

    :root[data-theme="dark"] .dropzone-title {
        color: var(--text);
    }
</style>
@endpush

@section('content')
    <div class="dashboard-card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <p class="header-subtitle" style="margin:0;">Registra una entrada, salida o transferencia de equipo por paqueteria</p>
            </div>
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Regresar
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('inventory.movimientos.store') }}" class="movement-form" enctype="multipart/form-data">
        @csrf

        <div class="form-section">
            <h3>Datos generales del movimiento</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label for="movement_type">Tipo de movimiento</label>
                    <select id="movement_type" name="movement_type" required onchange="updateSections()">
                        <option value="entrada" selected>Entrada</option>
                        <option value="salida">Salida</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="movement_date">Fecha del movimiento</label>
                    <input id="movement_date" type="date" name="movement_date" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="form-field">
                    <label for="producto_id">Equipo</label>
                    <select id="producto_id" name="producto_id" required>
                        <option value="">Selecciona un equipo</option>
                        @foreach($equipos as $equipo)
                            <option value="{{ $equipo->id }}" @selected(old('producto_id') == $equipo->id)>
                                {{ $equipo->tipo_equipo }} - {{ $equipo->marca }} {{ $equipo->modelo }} ({{ $equipo->no_serie }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="warehouse">Almacen</label>
                    <select id="warehouse" name="warehouse" required>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse }}" @selected(old('warehouse', 'Almacen Central') == $warehouse)>{{ $warehouse }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="reference">Referencia / Proveedor</label>
                    <input id="reference" type="text" name="reference" value="{{ old('reference') }}" placeholder="Ej. Olimpus Mexico S.A. de C.V.">
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label for="notes">Observaciones generales</label>
                    <textarea id="notes" name="notes" placeholder="Notas adicionales del movimiento">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div id="section-entrada" class="form-section package-section is-active">
            <h3>Detalles de la entrada (como llego por paqueteria)</h3>

            <div class="form-section" style="margin-bottom: 14px;">
                <h3 style="font-size:13px;">Caja del equipo</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="entrada_caja_tipo">Tipo de caja</label>
                        <input id="entrada_caja_tipo" type="text" name="metadata[entrada][caja][tipo]" value="{{ old('metadata.entrada.caja.tipo') }}" placeholder="Ej. Carton corrugado">
                    </div>
                    <div class="form-field">
                        <label for="entrada_caja_estado">Estado de la caja</label>
                        <select id="entrada_caja_estado" name="metadata[entrada][caja][estado]">
                            <option value="">Selecciona</option>
                            <option value="Buena" @selected(old('metadata.entrada.caja.estado') == 'Buena')>Buena</option>
                            <option value="Golpeada" @selected(old('metadata.entrada.caja.estado') == 'Golpeada')>Golpeada</option>
                            <option value="Mojada" @selected(old('metadata.entrada.caja.estado') == 'Mojada')>Mojada</option>
                            <option value="Rota" @selected(old('metadata.entrada.caja.estado') == 'Rota')>Rota</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="entrada_caja_dimensiones">Dimensiones</label>
                        <input id="entrada_caja_dimensiones" type="text" name="metadata[entrada][caja][dimensiones]" value="{{ old('metadata.entrada.caja.dimensiones') }}" placeholder="Ej. 70 x 60 x 40 cm">
                    </div>
                    <div class="form-field">
                        <label for="entrada_caja_peso">Peso aproximado</label>
                        <input id="entrada_caja_peso" type="text" name="metadata[entrada][caja][peso]" value="{{ old('metadata.entrada.caja.peso') }}" placeholder="Ej. 12 kg">
                    </div>
                </div>
            </div>

            <div class="form-section" style="margin-bottom: 14px;">
                <h3 style="font-size:13px;">Equipo envuelto</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="entrada_envoltura_material">Material del envoltorio</label>
                        <input id="entrada_envoltura_material" type="text" name="metadata[entrada][envoltura][material]" value="{{ old('metadata.entrada.envoltura.material') }}" placeholder="Ej. Plastico de burbuja">
                    </div>
                    <div class="form-field">
                        <label for="entrada_envoltura_estado">Estado del envoltorio</label>
                        <select id="entrada_envoltura_estado" name="metadata[entrada][envoltura][estado]">
                            <option value="">Selecciona</option>
                            <option value="Bueno" @selected(old('metadata.entrada.envoltura.estado') == 'Bueno')>Bueno</option>
                            <option value="Rasgado" @selected(old('metadata.entrada.envoltura.estado') == 'Rasgado')>Rasgado</option>
                            <option value="Sin proteccion" @selected(old('metadata.entrada.envoltura.estado') == 'Sin proteccion')>Sin proteccion</option>
                        </select>
                    </div>
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label for="entrada_envoltura_observaciones">Observaciones del envoltorio</label>
                        <textarea id="entrada_envoltura_observaciones" name="metadata[entrada][envoltura][observaciones]" placeholder="Describe como viene envuelto el equipo">{{ old('metadata.entrada.envoltura.observaciones') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 style="font-size:13px;">Contenido de la caja</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="entrada_contenido_accesorios">Accesorios incluidos</label>
                        <input id="entrada_contenido_accesorios" type="text" name="metadata[entrada][contenido][accesorios]" value="{{ old('metadata.entrada.contenido.accesorios') }}" placeholder="Ej. Cables, fuente, manuales">
                    </div>
                    <div class="form-field">
                        <label for="entrada_contenido_acomodo">Acomodo del contenido</label>
                        <select id="entrada_contenido_acomodo" name="metadata[entrada][contenido][acomodo]">
                            <option value="">Selecciona</option>
                            <option value="Bien acomodado" @selected(old('metadata.entrada.contenido.acomodo') == 'Bien acomodado')>Bien acomodado</option>
                            <option value="Suelto" @selected(old('metadata.entrada.contenido.acomodo') == 'Suelto')>Suelto</option>
                            <option value="Con golpes" @selected(old('metadata.entrada.contenido.acomodo') == 'Con golpes')>Con golpes</option>
                        </select>
                    </div>
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label for="entrada_contenido_observaciones">Como vienen dentro de la caja</label>
                        <textarea id="entrada_contenido_observaciones" name="metadata[entrada][contenido][observaciones]" placeholder="Describe la disposicion y estado del equipo y accesorios dentro de la caja">{{ old('metadata.entrada.contenido.observaciones') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div id="section-salida" class="form-section package-section">
            <h3>Detalles de la salida (como se manda por paqueteria)</h3>

            <div class="form-section" style="margin-bottom: 14px;">
                <h3 style="font-size:13px;">Como va el equipo</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="salida_envio_paqueteria">Paqueteria</label>
                        <input id="salida_envio_paqueteria" type="text" name="metadata[salida][envio][paqueteria]" value="{{ old('metadata.salida.envio.paqueteria') }}" placeholder="Ej. DHL, FedEx, Estafeta">
                    </div>
                    <div class="form-field">
                        <label for="salida_envio_guia">Numero de guia</label>
                        <input id="salida_envio_guia" type="text" name="metadata[salida][envio][guia]" value="{{ old('metadata.salida.envio.guia') }}" placeholder="Ej. 1234567890">
                    </div>
                    <div class="form-field">
                        <label for="salida_envio_fecha_envio">Fecha de envio</label>
                        <input id="salida_envio_fecha_envio" type="date" name="metadata[salida][envio][fecha_envio]" value="{{ old('metadata.salida.envio.fecha_envio') }}">
                    </div>
                    <div class="form-field">
                        <label for="salida_envio_responsable">Responsable del envio</label>
                        <input id="salida_envio_responsable" type="text" name="metadata[salida][envio][responsable]" value="{{ old('metadata.salida.envio.responsable') }}" placeholder="Ej. Ing. Joel Diaz">
                    </div>
                </div>
            </div>

            <div class="form-section" style="margin-bottom: 14px;">
                <h3 style="font-size:13px;">Como se envuelve</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="salida_embalaje_tipo">Tipo de embalaje</label>
                        <input id="salida_embalaje_tipo" type="text" name="metadata[salida][embalaje][tipo]" value="{{ old('metadata.salida.embalaje.tipo') }}" placeholder="Ej. Caja de carton reforzado">
                    </div>
                    <div class="form-field">
                        <label for="salida_embalaje_material">Material del embalaje</label>
                        <input id="salida_embalaje_material" type="text" name="metadata[salida][embalaje][material]" value="{{ old('metadata.salida.embalaje.material') }}" placeholder="Ej. Plastico de burbuja, foam">
                    </div>
                    <div class="form-field">
                        <label for="salida_embalaje_estado">Estado del embalaje</label>
                        <select id="salida_embalaje_estado" name="metadata[salida][embalaje][estado]">
                            <option value="">Selecciona</option>
                            <option value="Bueno" @selected(old('metadata.salida.embalaje.estado') == 'Bueno')>Bueno</option>
                            <option value="Regular" @selected(old('metadata.salida.embalaje.estado') == 'Regular')>Regular</option>
                            <option value="Malo" @selected(old('metadata.salida.embalaje.estado') == 'Malo')>Malo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 style="font-size:13px;">Como se manda</h3>
                <div class="form-grid">
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label for="salida_envio_direccion">Direccion de entrega</label>
                        <textarea id="salida_envio_direccion" name="metadata[salida][envio][direccion]" placeholder="Direccion completa de destino">{{ old('metadata.salida.envio.direccion') }}</textarea>
                    </div>
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label for="salida_envio_instrucciones">Instrucciones de envio</label>
                        <textarea id="salida_envio_instrucciones" name="metadata[salida][envio][instrucciones]" placeholder="Instrucciones especiales para la paqueteria">{{ old('metadata.salida.envio.instrucciones') }}</textarea>
                    </div>
                    <div class="form-field">
                        <label for="salida_envio_prioridad">Prioridad</label>
                        <select id="salida_envio_prioridad" name="metadata[salida][envio][prioridad]">
                            <option value="">Selecciona</option>
                            <option value="Normal" @selected(old('metadata.salida.envio.prioridad') == 'Normal')>Normal</option>
                            <option value="Urgente" @selected(old('metadata.salida.envio.prioridad') == 'Urgente')>Urgente</option>
                            <option value="Express" @selected(old('metadata.salida.envio.prioridad') == 'Express')>Express</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Evidencias</h3>
            <div class="form-grid">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label for="imagenes">Imagenes (maximo 4)</label>
                    <div class="dropzone" id="dropzone-imagenes" onclick="document.getElementById('imagenes').click()">
                        <input id="imagenes" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p class="dropzone-title">Arrastra imagenes aqui</p>
                        <p class="dropzone-hint">JPG, PNG, GIF o WebP · Max. 5 MB · Hasta 4 archivos</p>
                        <button type="button" onclick="event.stopPropagation(); document.getElementById('imagenes').click();">Seleccionar imagenes</button>
                    </div>
                    <div id="preview-imagenes" class="preview"></div>
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label for="video">Video (1 archivo)</label>
                    <div class="dropzone" id="dropzone-video" onclick="document.getElementById('video').click()">
                        <input id="video" type="file" name="video" accept="video/mp4,video/quicktime,video/avi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p class="dropzone-title">Arrastra un video aqui</p>
                        <p class="dropzone-hint">MP4, MOV o AVI · Max. 50 MB · 1 archivo</p>
                        <button type="button" onclick="event.stopPropagation(); document.getElementById('video').click();">Seleccionar video</button>
                    </div>
                    <div id="preview-video" class="preview"></div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
            <button type="submit" class="btn btn--primary" style="background:#158be8; color:#fff; border:0; padding:10px 18px; border-radius:5px; font-weight:800; cursor:pointer;">Guardar movimiento</button>
        </div>
    </form>

    <script>
        function updateSections() {
            const type = document.getElementById('movement_type').value;
            document.querySelectorAll('.package-section').forEach((section) => section.classList.remove('is-active'));

            if (type === 'entrada') {
                document.getElementById('section-entrada').classList.add('is-active');
            } else if (type === 'salida') {
                document.getElementById('section-salida').classList.add('is-active');
            }
        }

        function setupDropzone(dropzoneId, inputId, previewId, type, maxFiles) {
            const dropzone = document.getElementById(dropzoneId);
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (e) => e.preventDefault());
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, () => dropzone.classList.add('is-dragover'));
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, () => dropzone.classList.remove('is-dragover'));
            });

            dropzone.addEventListener('drop', (e) => {
                const files = Array.from(e.dataTransfer.files).slice(0, maxFiles);
                const dt = new DataTransfer();
                files.forEach((file) => dt.items.add(file));
                input.files = dt.files;
                input.dispatchEvent(new Event('change'));
            });

            input.addEventListener('change', () => {
                preview.innerHTML = '';
                const files = Array.from(input.files).slice(0, maxFiles);

                files.forEach((file) => {
                    const url = URL.createObjectURL(file);

                    if (type === 'image') {
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = file.name;
                        preview.appendChild(img);
                    } else if (type === 'video') {
                        const video = document.createElement('video');
                        video.src = url;
                        video.controls = true;
                        video.muted = true;
                        preview.appendChild(video);
                    }
                });
            });
        }

        updateSections();
        setupDropzone('dropzone-imagenes', 'imagenes', 'preview-imagenes', 'image', 4);
        setupDropzone('dropzone-video', 'video', 'preview-video', 'video', 1);
    </script>
@endsection
