@extends('layouts.dashboard')

@section('title', 'Nuevo Movimiento')
@section('page-title', 'Nuevo Movimiento')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida > Nuevo')

@push('head')
<style>
    .movement-page {
        display: grid;
        gap: 22px;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
        padding: 18px 22px;
        border-radius: 12px;
        background: linear-gradient(135deg, #158be8 0%, #0f6fbd 100%);
        color: #fff;
        box-shadow: 0 8px 24px rgba(21, 139, 232, .22);
    }

    .page-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 900;
    }

    .page-header p {
        margin: 4px 0 0;
        font-size: 13px;
        opacity: .92;
    }

    .page-header a {
        padding: 9px 14px;
        border-radius: 6px;
        background: rgba(255, 255, 255, .16);
        color: #fff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .2s;
    }

    .page-header a:hover {
        background: rgba(255, 255, 255, .26);
    }

    .page-header svg {
        width: 16px;
        height: 16px;
    }

    .stepper {
        display: flex;
        align-items: center;
        gap: 0;
        counter-reset: step;
    }

    .step {
        flex: 1;
        padding: 14px 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .step:first-child {
        border-radius: 8px 0 0 8px;
    }

    .step:last-child {
        border-radius: 0 8px 8px 0;
    }

    .step.is-active {
        background: #158be8;
        color: #fff;
        border-color: #158be8;
    }

    .step::before {
        counter-increment: step;
        content: counter(step);
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }

    .step.is-active::before {
        background: #fff;
        color: #158be8;
    }

    .form-section {
        padding: 20px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 16px;
        font-size: 15px;
        font-weight: 900;
        color: #111827;
    }

    .section-title svg {
        width: 20px;
        height: 20px;
        color: #158be8;
    }

    .section-subtitle {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 14px;
        font-size: 13px;
        font-weight: 800;
        color: #1f2937;
    }

    .section-subtitle svg {
        width: 16px;
        height: 16px;
        color: #64748b;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-field label {
        color: #475569;
        font-size: 12px;
        font-weight: 800;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .12);
    }

    .form-field textarea {
        min-height: 80px;
        resize: vertical;
    }

    .package-section {
        display: none;
    }

    .package-section.is-active {
        display: block;
    }

    .form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 14px;
    }

    .btn-cancel {
        padding: 11px 18px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .2s;
    }

    .btn-cancel:hover {
        background: #f1f5f9;
    }

    .btn-save {
        padding: 11px 22px;
        border: 0;
        border-radius: 6px;
        background: #158be8;
        color: #fff;
        font-size: 13px;
        font-weight: 900;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .2s, box-shadow .2s;
    }

    .btn-save:hover {
        background: #0f6fbd;
        box-shadow: 0 6px 14px rgba(21, 139, 232, .24);
    }

    .dropzone {
        position: relative;
        padding: 28px 24px;
        border: 2px dashed #158be8;
        border-radius: 12px;
        background: #f5fbff;
        text-align: center;
        color: #64748b;
        cursor: pointer;
        transition: background .2s, border-color .2s, transform .2s;
    }

    .dropzone:hover {
        transform: translateY(-2px);
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

    .dropzone-icon {
        width: 46px;
        height: 46px;
        color: #158be8;
        margin-bottom: 10px;
    }

    .dropzone-title {
        margin: 0 0 4px;
        font-weight: 900;
        color: #111827;
        font-size: 14px;
    }

    .dropzone-hint {
        margin: 0;
        font-size: 11px;
        color: #718096;
    }

    .dropzone-btn {
        margin-top: 12px;
        padding: 8px 16px;
        border: 0;
        border-radius: 6px;
        background: #158be8;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        position: relative;
        z-index: 1;
        transition: background .2s;
    }

    .dropzone-btn:hover {
        background: #0f6fbd;
    }

    .preview {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 14px;
    }

    .preview img,
    .preview video {
        width: 96px;
        height: 72px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
    }

    :root[data-theme="dark"] .page-header {
        background: linear-gradient(135deg, #0f6fbd 0%, #0c5a99 100%);
    }

    :root[data-theme="dark"] .step {
        background: var(--surface);
        border-color: var(--border);
        color: var(--muted);
    }

    :root[data-theme="dark"] .step::before {
        background: var(--surface-2);
        color: var(--text);
    }

    :root[data-theme="dark"] .step.is-active {
        background: #158be8;
        color: #fff;
        border-color: #158be8;
    }

    :root[data-theme="dark"] .step.is-active::before {
        background: #fff;
        color: #158be8;
    }

    :root[data-theme="dark"] .form-section {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .section-title,
    :root[data-theme="dark"] .section-subtitle {
        color: var(--text);
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

    :root[data-theme="dark"] .btn-cancel {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .stepper {
            flex-direction: column;
        }

        .step:first-child,
        .step:last-child {
            border-radius: 0;
        }
    }
</style>
@endpush

@section('content')
    <div class="movement-page">
        <div class="page-header">
            <div>
                <h2>Nuevo Movimiento</h2>
                <p>Registra una entrada, salida o transferencia de equipo por paqueteria</p>
            </div>
            <a href="{{ route('inventory.movimientos.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Regresar
            </a>
        </div>

        <div class="stepper" aria-label="Pasos">
            <div class="step is-active">Tipo y datos</div>
            <div class="step">Detalles de paqueteria</div>
            <div class="step">Evidencias</div>
        </div>

        <form method="POST" action="{{ route('inventory.movimientos.store') }}" class="movement-form" enctype="multipart/form-data">
            @csrf

            <div class="form-section">
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Datos generales del movimiento
                </h3>
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
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    Detalles de la entrada (como llego por paqueteria)
                </h3>

                <div class="form-section" style="margin-bottom: 16px; background:#f8fafc;">
                    <h4 class="section-subtitle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        Caja del equipo
                    </h4>
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

                <div class="form-section" style="margin-bottom: 16px; background:#f8fafc;">
                    <h4 class="section-subtitle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Equipo envuelto
                    </h4>
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

                <div class="form-section" style="background:#f8fafc;">
                    <h4 class="section-subtitle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        Contenido de la caja
                    </h4>
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
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 17H2a3 3 0 0 0 3-3V9a3 3 0 0 0-3-3h20a3 3 0 0 0 3 3v5a3 3 0 0 0-3 3z"></path>
                        <path d="M12 2v4"></path>
                    </svg>
                    Detalles de la salida (como se manda por paqueteria)
                </h3>

                <div class="form-section" style="margin-bottom: 16px; background:#f8fafc;">
                    <h4 class="section-subtitle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 23 3 23 16 16 11"></polygon></svg>
                        Como va el equipo
                    </h4>
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

                <div class="form-section" style="margin-bottom: 16px; background:#f8fafc;">
                    <h4 class="section-subtitle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"></path></svg>
                        Como se envuelve
                    </h4>
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

                <div class="form-section" style="background:#f8fafc;">
                    <h4 class="section-subtitle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Como se manda
                    </h4>
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
                <h3 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    Evidencias
                </h3>
                <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label for="imagenes">Imagenes (maximo 4)</label>
                        <div class="dropzone" id="dropzone-imagenes" onclick="document.getElementById('imagenes').click()">
                            <input id="imagenes" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                            <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p class="dropzone-title">Arrastra imagenes aqui</p>
                            <p class="dropzone-hint">JPG, PNG, GIF o WebP · Max. 5 MB · Hasta 4 archivos</p>
                            <button type="button" class="dropzone-btn" onclick="event.stopPropagation(); document.getElementById('imagenes').click();">Seleccionar imagenes</button>
                        </div>
                        <div id="preview-imagenes" class="preview"></div>
                    </div>
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label for="video">Video (1 archivo)</label>
                        <div class="dropzone" id="dropzone-video" onclick="document.getElementById('video').click()">
                            <input id="video" type="file" name="video" accept="video/mp4,video/quicktime,video/avi">
                            <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p class="dropzone-title">Arrastra un video aqui</p>
                            <p class="dropzone-hint">MP4, MOV o AVI · Max. 50 MB · 1 archivo</p>
                            <button type="button" class="dropzone-btn" onclick="event.stopPropagation(); document.getElementById('video').click();">Seleccionar video</button>
                        </div>
                        <div id="preview-video" class="preview"></div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('inventory.movimientos.index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px; height:16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Guardar movimiento
                </button>
            </div>
        </form>
    </div>

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
