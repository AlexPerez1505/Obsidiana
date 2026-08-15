@extends('layouts.dashboard')

@section('title', 'Nuevo Movimiento')
@section('page-title', 'Nuevo Movimiento')

@push('head')
<style>
    .product-edit-page { display: grid; gap: 24px; }
    .product-edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; align-items: start; }

    .product-card, .settings-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 26px;
        box-shadow: var(--shadow);
        display: grid;
        gap: 22px;
    }

    .card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 2px; }
    .card-header svg { width: 22px; height: 22px; color: var(--primary); }
    .card-header h4 { font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0; }

    .settings-sub { color: var(--muted); font-size: 0.92rem; margin: 0; }
    .settings-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

    .input-group { display: grid; gap: 6px; }
    .input-group__label { font-size: 0.85rem; font-weight: 700; color: var(--text); }
    .input-wrap input,
    .input-wrap select,
    .input-wrap textarea {
        width: 100%;
        padding: 13px 14px;
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        outline: none;
        background: var(--surface);
        color: var(--text);
        transition: border .15s, box-shadow .15s;
    }
    .input-wrap input:focus,
    .input-wrap select:focus,
    .input-wrap textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0,122,255,.12); }
    .input-wrap textarea { min-height: 80px; resize: vertical; }

    .package-section { display: none; }
    .package-section.is-active { display: grid; gap: 18px; }

    .subsection-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--text);
        margin: 0 0 4px;
    }
    .subsection-title svg { width: 18px; height: 18px; color: var(--muted); }

    .upload-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 24px 20px;
        border: 2px dashed #3b82f6;
        border-radius: 14px;
        background: #eff6ff;
        color: #3b82f6;
        text-align: center;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    :root[data-theme="dark"] .upload-zone { background: #0d1528; }
    .upload-zone:hover, .upload-zone.drag-over { background: #dbeafe; border-color: #2563eb; }
    :root[data-theme="dark"] .upload-zone:hover, :root[data-theme="dark"] .upload-zone.drag-over { background: #132142; }
    .upload-zone__icon svg { width: 38px; height: 38px; display: block; }
    .upload-zone__text { font-size: 0.9rem; font-weight: 700; margin: 0; color: var(--text); }
    .upload-zone__hint { font-size: 0.75rem; color: var(--muted); margin: 0; }
    .upload-zone__btn {
        display: inline-block;
        margin-top: 4px;
        padding: 8px 16px;
        border-radius: 8px;
        background: #3b82f6;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        pointer-events: none;
    }
    .upload-zone input[type=file] { display: none; }

    .preview-box {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--surface-2);
    }
    .preview-box.is-visible { display: flex; }
    .preview-box__thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); background: #fff; }
    .preview-box__thumb.is-video { object-fit: contain; background: #000; }
    .preview-box__info { flex: 1; min-width: 0; }
    .preview-box__label { font-size: 0.78rem; color: var(--muted); font-weight: 700; margin: 0 0 2px; }
    .preview-box__name { font-size: 0.85rem; color: var(--text); font-weight: 700; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .preview-box__remove {
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        padding: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    .preview-box__remove:hover { background: var(--danger-soft); }
    .preview-box__remove svg { width: 18px; height: 18px; display: block; }

    .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px; }
    .form-actions .btn { display: inline-flex; align-items: center; gap: 8px; }
    .form-actions .btn svg { width: 18px; height: 18px; }

    .btn--ghost {
        padding: 11px 18px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        font-size: 0.9rem;
        font-weight: 800;
        text-decoration: none;
    }
    .btn--ghost:hover { background: var(--surface-2); }

    .btn--primary {
        padding: 11px 18px;
        border-radius: 12px;
        border: 0;
        background: var(--primary);
        color: #fff;
        font-size: 0.9rem;
        font-weight: 800;
        cursor: pointer;
    }
    .btn--primary:hover { filter: brightness(.95); }

    @media (max-width: 980px) {
        .product-edit-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .settings-inputs { grid-template-columns: 1fr; }
        .form-actions { flex-direction: column; }
        .form-actions .btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<section class="product-edit-page">
    <form method="POST" action="{{ route('inventory.movimientos.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="product-edit-grid">
            <div class="product-card">
                <div class="card-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <h4>Datos del movimiento</h4>
                </div>

                <p class="settings-sub">Completa la informacion principal del registro.</p>

                <div class="settings-inputs">
                    <div class="input-group">
                        <label class="input-group__label" for="movement_type">Tipo de movimiento</label>
                        <div class="input-wrap">
                            <select id="movement_type" name="movement_type" onchange="updateSections()">
                                <option value="entrada" selected>Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-group__label" for="movement_date">Fecha del movimiento</label>
                        <div class="input-wrap">
                            <input id="movement_date" type="date" name="movement_date" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-group__label" for="producto_id">Equipo</label>
                        <div class="input-wrap">
                            <select id="producto_id" name="producto_id" required>
                                <option value="">Selecciona un equipo</option>
                                @foreach($equipos as $equipo)
                                    <option value="{{ $equipo->id }}" @selected(old('producto_id') == $equipo->id)>
                                        {{ $equipo->tipo_equipo }} - {{ $equipo->marca }} {{ $equipo->modelo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-group__label" for="warehouse">Almacen</label>
                        <div class="input-wrap">
                            <select id="warehouse" name="warehouse" required>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse }}" @selected(old('warehouse', 'Almacen Central') == $warehouse)>{{ $warehouse }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-group__label" for="reference">Referencia / Proveedor</label>
                        <div class="input-wrap">
                            <input id="reference" type="text" name="reference" value="{{ old('reference') }}" placeholder="Ej. Olimpus Mexico">
                        </div>
                    </div>
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label class="input-group__label" for="notes">Observaciones generales</label>
                        <div class="input-wrap">
                            <textarea id="notes" name="notes" placeholder="Notas adicionales del movimiento">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-header" style="margin-top:4px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <h4 style="font-size:.95rem;">Evidencias</h4>
                </div>

                <div class="settings-inputs">
                    <div class="input-group" style="gap:10px;">
                        <span class="input-group__label">Imagenes (maximo 4)</span>
                        <label class="upload-zone" for="imagenes" id="upload-zone-imagenes">
                            <input type="file" id="imagenes" name="imagenes[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                            <span class="upload-zone__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
                            </span>
                            <p class="upload-zone__text">Arrastra imagenes aqui</p>
                            <p class="upload-zone__hint">JPG, PNG, GIF o WebP · Max. 5 MB · Hasta 4</p>
                            <span class="upload-zone__btn">Seleccionar imagenes</span>
                        </label>
                        <div class="preview-box" id="preview-box-imagenes">
                            <img class="preview-box__thumb" id="preview-img-imagenes" src="" alt="Vista previa">
                            <div class="preview-box__info">
                                <p class="preview-box__label">Imagenes seleccionadas</p>
                                <p class="preview-box__name" id="preview-name-imagenes">-</p>
                            </div>
                            <button type="button" class="preview-box__remove" id="remove-preview-imagenes">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="input-group" style="gap:10px;">
                        <span class="input-group__label">Video (1 archivo)</span>
                        <label class="upload-zone" for="video" id="upload-zone-video">
                            <input type="file" id="video" name="video" accept="video/mp4,video/quicktime,video/avi">
                            <span class="upload-zone__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
                            </span>
                            <p class="upload-zone__text">Arrastra un video aqui</p>
                            <p class="upload-zone__hint">MP4, MOV o AVI · Max. 50 MB · 1 archivo</p>
                            <span class="upload-zone__btn">Seleccionar video</span>
                        </label>
                        <div class="preview-box" id="preview-box-video">
                            <img class="preview-box__thumb" id="preview-img-video" src="" alt="Vista previa">
                            <div class="preview-box__info">
                                <p class="preview-box__label">Video seleccionado</p>
                                <p class="preview-box__name" id="preview-name-video">-</p>
                            </div>
                            <button type="button" class="preview-box__remove" id="remove-preview-video">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <h4>Detalles del movimiento</h4>
                </div>

                <p class="settings-sub">Captura como llego o como se envia el equipo por paqueteria.</p>

                <div id="section-entrada" class="package-section is-active">
                    <p class="subsection-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Caja
                    </p>
                    <div class="settings-inputs">
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_caja_tipo">Tipo de caja</label>
                            <div class="input-wrap"><input id="entrada_caja_tipo" type="text" name="metadata[entrada][caja][tipo]" value="{{ old('metadata.entrada.caja.tipo') }}" placeholder="Ej. Carton corrugado"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_caja_estado">Estado de la caja</label>
                            <div class="input-wrap">
                                <select id="entrada_caja_estado" name="metadata[entrada][caja][estado]">
                                    <option value="">Selecciona</option>
                                    <option value="Buena" @selected(old('metadata.entrada.caja.estado') == 'Buena')>Buena</option>
                                    <option value="Golpeada" @selected(old('metadata.entrada.caja.estado') == 'Golpeada')>Golpeada</option>
                                    <option value="Mojada" @selected(old('metadata.entrada.caja.estado') == 'Mojada')>Mojada</option>
                                    <option value="Rota" @selected(old('metadata.entrada.caja.estado') == 'Rota')>Rota</option>
                                </select>
                            </div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_caja_dimensiones">Dimensiones</label>
                            <div class="input-wrap"><input id="entrada_caja_dimensiones" type="text" name="metadata[entrada][caja][dimensiones]" value="{{ old('metadata.entrada.caja.dimensiones') }}" placeholder="Ej. 70 x 60 x 40 cm"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_caja_peso">Peso aproximado</label>
                            <div class="input-wrap"><input id="entrada_caja_peso" type="text" name="metadata[entrada][caja][peso]" value="{{ old('metadata.entrada.caja.peso') }}" placeholder="Ej. 12 kg"></div>
                        </div>
                    </div>

                    <p class="subsection-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Envoltura
                    </p>
                    <div class="settings-inputs">
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_envoltura_material">Material del envoltorio</label>
                            <div class="input-wrap"><input id="entrada_envoltura_material" type="text" name="metadata[entrada][envoltura][material]" value="{{ old('metadata.entrada.envoltura.material') }}" placeholder="Ej. Plastico de burbuja"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_envoltura_estado">Estado del envoltorio</label>
                            <div class="input-wrap">
                                <select id="entrada_envoltura_estado" name="metadata[entrada][envoltura][estado]">
                                    <option value="">Selecciona</option>
                                    <option value="Bueno" @selected(old('metadata.entrada.envoltura.estado') == 'Bueno')>Bueno</option>
                                    <option value="Rasgado" @selected(old('metadata.entrada.envoltura.estado') == 'Rasgado')>Rasgado</option>
                                    <option value="Sin proteccion" @selected(old('metadata.entrada.envoltura.estado') == 'Sin proteccion')>Sin proteccion</option>
                                </select>
                            </div>
                        </div>
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label class="input-group__label" for="entrada_envoltura_observaciones">Observaciones del envoltorio</label>
                            <div class="input-wrap"><textarea id="entrada_envoltura_observaciones" name="metadata[entrada][envoltura][observaciones]" placeholder="Describe como viene envuelto el equipo">{{ old('metadata.entrada.envoltura.observaciones') }}</textarea></div>
                        </div>
                    </div>

                    <p class="subsection-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        Contenido de la caja
                    </p>
                    <div class="settings-inputs">
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_contenido_accesorios">Accesorios incluidos</label>
                            <div class="input-wrap"><input id="entrada_contenido_accesorios" type="text" name="metadata[entrada][contenido][accesorios]" value="{{ old('metadata.entrada.contenido.accesorios') }}" placeholder="Ej. Cables, fuente, manuales"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="entrada_contenido_acomodo">Acomodo del contenido</label>
                            <div class="input-wrap">
                                <select id="entrada_contenido_acomodo" name="metadata[entrada][contenido][acomodo]">
                                    <option value="">Selecciona</option>
                                    <option value="Bien acomodado" @selected(old('metadata.entrada.contenido.acomodo') == 'Bien acomodado')>Bien acomodado</option>
                                    <option value="Suelto" @selected(old('metadata.entrada.contenido.acomodo') == 'Suelto')>Suelto</option>
                                    <option value="Con golpes" @selected(old('metadata.entrada.contenido.acomodo') == 'Con golpes')>Con golpes</option>
                                </select>
                            </div>
                        </div>
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label class="input-group__label" for="entrada_contenido_observaciones">Como vienen dentro de la caja</label>
                            <div class="input-wrap"><textarea id="entrada_contenido_observaciones" name="metadata[entrada][contenido][observaciones]" placeholder="Describe la disposicion y estado del equipo y accesorios dentro de la caja">{{ old('metadata.entrada.contenido.observaciones') }}</textarea></div>
                        </div>
                    </div>
                </div>

                <div id="section-salida" class="package-section">
                    <p class="subsection-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 17H2a3 3 0 0 0 3-3V9a3 3 0 0 0-3-3h20a3 3 0 0 0 3 3v5a3 3 0 0 0-3 3z"/><path d="M12 2v4"/></svg>
                        Envio
                    </p>
                    <div class="settings-inputs">
                        <div class="input-group">
                            <label class="input-group__label" for="salida_envio_paqueteria">Paqueteria</label>
                            <div class="input-wrap"><input id="salida_envio_paqueteria" type="text" name="metadata[salida][envio][paqueteria]" value="{{ old('metadata.salida.envio.paqueteria') }}" placeholder="Ej. DHL, FedEx, Estafeta"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="salida_envio_guia">Numero de guia</label>
                            <div class="input-wrap"><input id="salida_envio_guia" type="text" name="metadata[salida][envio][guia]" value="{{ old('metadata.salida.envio.guia') }}" placeholder="Ej. 1234567890"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="salida_envio_fecha_envio">Fecha de envio</label>
                            <div class="input-wrap"><input id="salida_envio_fecha_envio" type="date" name="metadata[salida][envio][fecha_envio]" value="{{ old('metadata.salida.envio.fecha_envio') }}"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="salida_envio_responsable">Responsable del envio</label>
                            <div class="input-wrap"><input id="salida_envio_responsable" type="text" name="metadata[salida][envio][responsable]" value="{{ old('metadata.salida.envio.responsable') }}" placeholder="Ej. Ing. Joel Diaz"></div>
                        </div>
                    </div>

                    <p class="subsection-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/></svg>
                        Embalaje
                    </p>
                    <div class="settings-inputs">
                        <div class="input-group">
                            <label class="input-group__label" for="salida_embalaje_tipo">Tipo de embalaje</label>
                            <div class="input-wrap"><input id="salida_embalaje_tipo" type="text" name="metadata[salida][embalaje][tipo]" value="{{ old('metadata.salida.embalaje.tipo') }}" placeholder="Ej. Caja de carton reforzado"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="salida_embalaje_material">Material del embalaje</label>
                            <div class="input-wrap"><input id="salida_embalaje_material" type="text" name="metadata[salida][embalaje][material]" value="{{ old('metadata.salida.embalaje.material') }}" placeholder="Ej. Plastico de burbuja, foam"></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="salida_embalaje_estado">Estado del embalaje</label>
                            <div class="input-wrap">
                                <select id="salida_embalaje_estado" name="metadata[salida][embalaje][estado]">
                                    <option value="">Selecciona</option>
                                    <option value="Bueno" @selected(old('metadata.salida.embalaje.estado') == 'Bueno')>Bueno</option>
                                    <option value="Regular" @selected(old('metadata.salida.embalaje.estado') == 'Regular')>Regular</option>
                                    <option value="Malo" @selected(old('metadata.salida.embalaje.estado') == 'Malo')>Malo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <p class="subsection-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Entrega
                    </p>
                    <div class="settings-inputs">
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label class="input-group__label" for="salida_envio_direccion">Direccion de entrega</label>
                            <div class="input-wrap"><textarea id="salida_envio_direccion" name="metadata[salida][envio][direccion]" placeholder="Direccion completa de destino">{{ old('metadata.salida.envio.direccion') }}</textarea></div>
                        </div>
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label class="input-group__label" for="salida_envio_instrucciones">Instrucciones de envio</label>
                            <div class="input-wrap"><textarea id="salida_envio_instrucciones" name="metadata[salida][envio][instrucciones]" placeholder="Instrucciones especiales para la paqueteria">{{ old('metadata.salida.envio.instrucciones') }}</textarea></div>
                        </div>
                        <div class="input-group">
                            <label class="input-group__label" for="salida_envio_prioridad">Prioridad</label>
                            <div class="input-wrap">
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

                <div class="form-actions">
                    <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn--primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Guardar movimiento
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>

@push('scripts')
<script>
    (function(){
        const typeInput = document.getElementById('movement_type');
        const sectionEntrada = document.getElementById('section-entrada');
        const sectionSalida = document.getElementById('section-salida');

        function updateSections() {
            const type = typeInput.value;
            sectionEntrada.classList.toggle('is-active', type === 'entrada');
            sectionSalida.classList.toggle('is-active', type === 'salida');
        }

        typeInput.addEventListener('change', updateSections);
        updateSections();

        function setupUpload(inputId, zoneId, previewBoxId, thumbId, nameId, removeId, isMultiple) {
            const input = document.getElementById(inputId);
            const zone = document.getElementById(zoneId);
            const previewBox = document.getElementById(previewBoxId);
            const thumb = document.getElementById(thumbId);
            const name = document.getElementById(nameId);
            const remove = document.getElementById(removeId);

            function showPreview() {
                const files = input.files;
                if (files && files.length) {
                    const file = files[0];
                    if (file.type.startsWith('video/')) {
                        thumb.src = '';
                        thumb.classList.add('is-video');
                    } else {
                        thumb.classList.remove('is-video');
                        const reader = new FileReader();
                        reader.onload = function(e) { thumb.src = e.target.result; };
                        reader.readAsDataURL(file);
                    }
                    name.textContent = isMultiple && files.length > 1
                        ? files.length + ' archivos seleccionados'
                        : file.name;
                    previewBox.classList.add('is-visible');
                }
            }

            input.addEventListener('change', showPreview);

            remove.addEventListener('click', function(e) {
                e.preventDefault();
                input.value = '';
                thumb.src = '';
                name.textContent = '-';
                previewBox.classList.remove('is-visible');
            });

            ['dragenter','dragover','dragleave','drop'].forEach(function(evt){
                zone.addEventListener(evt, function(e){ e.preventDefault(); e.stopPropagation(); });
            });
            ['dragenter','dragover'].forEach(function(evt){
                zone.addEventListener(evt, function(){ zone.classList.add('drag-over'); });
            });
            ['dragleave','drop'].forEach(function(evt){
                zone.addEventListener(evt, function(){ zone.classList.remove('drag-over'); });
            });
            zone.addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                if (files.length) {
                    const dt = new DataTransfer();
                    const limit = isMultiple ? Math.min(files.length, 4) : 1;
                    for (let i = 0; i < limit; i++) dt.items.add(files[i]);
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        setupUpload('imagenes', 'upload-zone-imagenes', 'preview-box-imagenes', 'preview-img-imagenes', 'preview-name-imagenes', 'remove-preview-imagenes', true);
        setupUpload('video', 'upload-zone-video', 'preview-box-video', 'preview-img-video', 'preview-name-video', 'remove-preview-video', false);
    })();
</script>
@endpush
@endsection
