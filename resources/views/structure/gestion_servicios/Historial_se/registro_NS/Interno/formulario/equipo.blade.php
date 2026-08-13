@extends('structure.gestion_servicios.layout')

@section('title', 'Registro del equipo')

@section('service_content')
    <style>
        .ns-page { max-width: 900px; margin: 0 auto; }

        .ns-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 22px;
        }
        .ns-header-title { display: flex; align-items: center; gap: 14px; }
        .ns-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(0,122,255,0.12); color: #007AFF;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .ns-icon svg { width: 24px; height: 24px; }
        .ns-header-title h2 { margin: 0; font-size: 22px; color: #fff; }
        :root[data-theme="light"] .ns-header-title h2 { color: var(--text); }
        .ns-header-title p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 13px; }
        :root[data-theme="light"] .ns-header-title p { color: var(--muted); }
        .ns-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ns-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: all .16s ease;
        }
        .ns-btn svg { width: 16px; height: 16px; }
        .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
        :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-btn--primary { border: none; background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
        .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }
        .ns-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; }

        .ns-stepper {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 26px; padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-stepper { border-color: rgba(15,23,42,0.08); }
        .ns-step { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.45); }
        :root[data-theme="light"] .ns-step { color: var(--muted); }
        .ns-step-number {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800;
            background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);
        }
        :root[data-theme="light"] .ns-step-number { background: rgba(15,23,42,0.08); color: var(--muted); }
        .ns-step.completed { color: #22C55E; }
        :root[data-theme="light"] .ns-step.completed { color: #16A34A; }
        .ns-step.completed .ns-step-number { background: #22C55E; color: #fff; }
        .ns-step.active { color: #fff; }
        :root[data-theme="light"] .ns-step.active { color: var(--text); }
        .ns-step.active .ns-step-number { background: #007AFF; color: #fff; }
        .ns-step-line { flex: 1; height: 1px; min-width: 30px; background: rgba(255,255,255,0.08); }
        :root[data-theme="light"] .ns-step-line { background: rgba(15,23,42,0.08); }

        .ns-customer-summary {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            margin-bottom: 22px;
        }
        .ns-customer-main { display: flex; align-items: center; gap: 14px; }
        .ns-customer-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: #007AFF; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; flex-shrink: 0;
        }
        .ns-customer-info h4 { margin: 0 0 4px; font-size: 15px; color: #fff; }
        :root[data-theme="light"] .ns-customer-info h4 { color: var(--text); }
        .ns-customer-info p { margin: 0; font-size: 12px; color: rgba(255,255,255,0.5); }
        :root[data-theme="light"] .ns-customer-info p { color: var(--muted); }
        .ns-registrar {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: rgba(255,255,255,0.6);
        }
        :root[data-theme="light"] .ns-registrar { color: var(--muted); }
        .ns-registrar svg { width: 18px; height: 18px; }

        .ns-section-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 16px; font-weight: 800; color: #fff;
            margin-bottom: 6px;
        }
        :root[data-theme="light"] .ns-section-title { color: var(--text); }
        .ns-section-title svg { width: 20px; height: 20px; color: #007AFF; }
        .ns-section-subtitle { margin: 0 0 20px; font-size: 13px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-section-subtitle { color: var(--muted); }

        .ns-form-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
            margin-bottom: 18px;
        }
        @media (max-width: 760px) { .ns-form-grid { grid-template-columns: 1fr; } }
        .ns-field { display: flex; flex-direction: column; gap: 6px; }
        .ns-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
        :root[data-theme="light"] .ns-field label { color: var(--text); }
        .ns-field select, .ns-field input, .ns-field textarea {
            padding: 12px 14px; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
            background: rgba(8,18,40,0.55); color: #fff; font-size: 14px; outline: none;
            font-family: inherit;
        }
        :root[data-theme="light"] .ns-field select, :root[data-theme="light"] .ns-field input, :root[data-theme="light"] .ns-field textarea { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-field select option { background: #0b1220; color: #fff; }
        .ns-field textarea { min-height: 90px; resize: vertical; }
        .ns-field select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.5)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
        :root[data-theme="light"] .ns-field select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23334155' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); }

        .ns-upload-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
            margin-bottom: 10px;
        }
        @media (max-width: 760px) { .ns-upload-grid { grid-template-columns: repeat(2, 1fr); } }
        .ns-upload-box {
            position: relative; aspect-ratio: 1 / 1;
            border: 1px dashed rgba(255,255,255,0.2); border-radius: 14px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 8px; cursor: pointer; overflow: hidden;
            background: rgba(8,18,40,0.35); color: rgba(255,255,255,0.65);
            transition: all .16s ease;
        }
        :root[data-theme="light"] .ns-upload-box { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); color: var(--muted); }
        .ns-upload-box:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-upload-box svg { width: 26px; height: 26px; }
        .ns-upload-box .ns-upload-label { font-size: 12px; font-weight: 700; text-align: center; }
        .ns-upload-box .ns-upload-hint { font-size: 10px; opacity: 0.7; }
        .ns-upload-box input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .ns-upload-preview { position: absolute; inset: 0; object-fit: cover; width: 100%; height: 100%; display: none; }
        .ns-upload-hint-text { font-size: 11px; color: rgba(255,255,255,0.4); margin-bottom: 22px; }
        :root[data-theme="light"] .ns-upload-hint-text { color: var(--muted); }

        .ns-signature {
            background: #fff; border-radius: 14px; overflow: hidden;
            width: 100%; height: 160px; position: relative;
        }
        .ns-signature canvas { width: 100%; height: 100%; touch-action: none; }
        .ns-signature-clear {
            position: absolute; right: 10px; bottom: 10px;
            padding: 6px 12px; border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.2); background: rgba(0,0,0,0.6);
            color: #fff; font-size: 11px; cursor: pointer;
        }
        .ns-sig-tabs { display: flex; gap: 8px; margin-bottom: 12px; }
        .ns-sig-tab {
            padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 700;
            border: 1px solid rgba(255,255,255,0.12); background: transparent;
            color: rgba(255,255,255,0.65); cursor: pointer; transition: all .16s ease;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .ns-sig-tab svg { width: 15px; height: 15px; }
        :root[data-theme="light"] .ns-sig-tab { border-color: rgba(15,23,42,0.14); color: var(--muted); }
        .ns-sig-tab:hover { border-color: #007AFF; color: #007AFF; }
        .ns-sig-tab.active { background: #007AFF; border-color: #007AFF; color: #fff; }
        .ns-sig-upload {
            border: 1px dashed rgba(255,255,255,0.2); border-radius: 14px;
            background: rgba(8,18,40,0.35); color: rgba(255,255,255,0.65);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 8px; cursor: pointer; position: relative; overflow: hidden;
            width: 100%; height: 160px;
        }
        :root[data-theme="light"] .ns-sig-upload { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); color: var(--muted); }
        .ns-sig-upload:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-sig-upload svg { width: 28px; height: 28px; }
        .ns-sig-upload .ns-sig-label { font-size: 13px; font-weight: 700; }
        .ns-sig-upload .ns-sig-hint { font-size: 11px; opacity: 0.7; }
        .ns-sig-upload input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .ns-sig-preview {
            position: absolute; inset: 0; width: 100%; height: 100%;
            object-fit: contain; background: #fff; display: none;
        }
        .ns-sig-clear-upload {
            position: absolute; right: 10px; bottom: 10px;
            padding: 6px 12px; border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.2); background: rgba(0,0,0,0.6);
            color: #fff; font-size: 11px; cursor: pointer; display: none; z-index: 2;
        }
        .ns-hidden { display: none; }
    </style>

    @php
        $fullName = trim($customer->nombre . ' ' . ($customer->apellido ?? ''));
        $initials = collect([$customer->nombre, $customer->apellido])->filter()->map(fn($n) => mb_substr($n, 0, 1))->implode('');
    @endphp

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div>
                    <h2>Registro del equipo</h2>
                    <p>Completa la información del equipo para continuar</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancelar e iniciar
                </a>
                <a href="{{ route('gestion.servicios.nuevo.interno') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
                <button type="submit" form="equipmentForm" class="ns-btn ns-btn--primary">
                    Siguiente: Tecnico
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <div class="ns-stepper">
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Cliente</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step active">
                <div class="ns-step-number">2</div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step">
                <div class="ns-step-number">3</div>
                <span>Tecnico</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step">
                <div class="ns-step-number">4</div>
                <span>Cotizacion</span>
            </div>
        </div>

        <div class="ns-customer-summary">
            <div class="ns-customer-main">
                <div class="ns-customer-avatar">{{ $initials ?: 'C' }}</div>
                <div class="ns-customer-info">
                    <h4>{{ $fullName }}</h4>
                    <p>{{ $customer->telefono ?: 'Sin teléfono' }}</p>
                </div>
            </div>
            <div class="ns-registrar">
                Registrado por: <strong>{{ auth()->user()?->name ?? 'Oliver' }}</strong>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>

        <form id="equipmentForm" method="POST" action="{{ route('gestion.servicios.nuevo.interno.equipo.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">

            <div class="catalog-card service-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Datos del equipo
                </div>
                <p class="ns-section-subtitle">Ingresa la informacion del equipo que recibira el servicio tecnico</p>

                <div class="ns-form-grid">
                    <div class="ns-field">
                        <label>Tipo de equipo</label>
                        <select name="tipo_equipo">
                            <option value="">Ej. Equipo médico</option>
                            @foreach ($equipmentTypes as $type)
                                <option value="{{ $type->name }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ns-field">
                        <label>Subtipo</label>
                        <select name="subtipo">
                            <option value="">Ej. Monitor de signos vitales</option>
                            @foreach ($subtypes as $subtype)
                                <option value="{{ $subtype->name }}">{{ $subtype->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ns-field">
                        <label>Marca</label>
                        <select name="marca">
                            <option value="">Ej. Olympus</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ns-field">
                        <label>Modelo</label>
                        <select name="modelo">
                            <option value="">Ej. C-90</option>
                            @foreach ($models as $model)
                                <option value="{{ $model->name }}">{{ $model->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ns-field">
                        <label>Numero de serie</label>
                        <input type="text" name="serie" placeholder="Ej. SN-893-832">
                    </div>
                </div>

                <div class="ns-field" style="margin-bottom: 16px;">
                    <label>Descripcion del equipo</label>
                    <textarea name="descripcion_equipo" placeholder="Describe el equipo y su funcion"></textarea>
                </div>
                <div class="ns-field" style="margin-bottom: 6px;">
                    <label>Observaciones</label>
                    <textarea name="observaciones" placeholder="Anotaciones sobre el estado del equipo"></textarea>
                </div>
            </div>

            <div class="catalog-card service-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Evidencia del equipo
                </div>

                <div class="ns-upload-grid">
                    <label class="ns-upload-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span class="ns-upload-label">Imagen 1</span>
                        <span class="ns-upload-hint">Toca para subir</span>
                        <input type="file" name="evidencia_1" accept="image/*" onchange="previewImage(this, 'preview-1')">
                        <img id="preview-1" class="ns-upload-preview" alt="">
                    </label>
                    <label class="ns-upload-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span class="ns-upload-label">Imagen 2</span>
                        <span class="ns-upload-hint">Toca para subir</span>
                        <input type="file" name="evidencia_2" accept="image/*" onchange="previewImage(this, 'preview-2')">
                        <img id="preview-2" class="ns-upload-preview" alt="">
                    </label>
                    <label class="ns-upload-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span class="ns-upload-label">Imagen 3</span>
                        <span class="ns-upload-hint">Toca para subir</span>
                        <input type="file" name="evidencia_3" accept="image/*" onchange="previewImage(this, 'preview-3')">
                        <img id="preview-3" class="ns-upload-preview" alt="">
                    </label>
                    <label class="ns-upload-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        <span class="ns-upload-label">Video</span>
                        <span class="ns-upload-hint">Toca para subir</span>
                        <input type="file" name="evidencia_video" accept="video/*" onchange="previewVideo(this, 'preview-video')">
                        <video id="preview-video" class="ns-upload-preview" controls></video>
                    </label>
                </div>
                <p class="ns-upload-hint-text">Formatos permitidos: JPG, PNG, MP4. Tamaño máximo: 10MB por archivo.</p>
            </div>

            <div class="catalog-card service-section">
                <div class="ns-section-title">Firma Digital</div>

                <div class="ns-sig-tabs">
                    <button type="button" class="ns-sig-tab active" id="tab-draw" onclick="switchSigMode('draw')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
                        Dibujar
                    </button>
                    <button type="button" class="ns-sig-tab" id="tab-upload" onclick="switchSigMode('upload')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Subir imagen
                    </button>
                </div>

                <div id="sig-draw-mode" class="ns-signature">
                    <canvas id="signaturePad"></canvas>
                    <button type="button" class="ns-signature-clear" onclick="clearSignature()">Limpiar</button>
                </div>

                <div id="sig-upload-mode" class="ns-hidden" style="position: relative;">
                    <label class="ns-sig-upload" id="sigUploadBox">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span class="ns-sig-label">Subir firma</span>
                        <span class="ns-sig-hint">JPG o PNG · fondo transparente recomendado</span>
                        <input type="file" id="sigUploadInput" accept="image/png,image/jpeg,image/jpg" onchange="loadSignatureImage(this)">
                        <img id="sigPreview" class="ns-sig-preview" alt="">
                    </label>
                    <button type="button" class="ns-sig-clear-upload" id="sigClearUpload" onclick="clearUploadSignature()">Quitar</button>
                </div>

                <input type="hidden" name="firma" id="signatureInput">
            </div>
        </form>
    </div>

    <script>
        function previewImage(input, previewId) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(previewId);
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        function previewVideo(input, previewId) {
            const file = input.files[0];
            if (!file) return;
            const video = document.getElementById(previewId);
            video.src = URL.createObjectURL(file);
            video.style.display = 'block';
        }

        const canvas = document.getElementById('signaturePad');
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let sigMode = 'draw';
        let uploadedSignature = '';

        function switchSigMode(mode) {
            sigMode = mode;
            document.getElementById('tab-draw').classList.toggle('active', mode === 'draw');
            document.getElementById('tab-upload').classList.toggle('active', mode === 'upload');
            document.getElementById('sig-draw-mode').classList.toggle('ns-hidden', mode !== 'draw');
            document.getElementById('sig-upload-mode').classList.toggle('ns-hidden', mode !== 'upload');
            syncSignatureInput();
        }

        function syncSignatureInput() {
            const input = document.getElementById('signatureInput');
            if (sigMode === 'upload') {
                input.value = uploadedSignature;
            } else {
                input.value = canvas.toDataURL();
            }
        }

        function loadSignatureImage(inputEl) {
            const file = inputEl.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                alert('La imagen es demasiado grande. Máximo 5MB.');
                inputEl.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('sigPreview');
                img.src = e.target.result;
                img.style.display = 'block';
                uploadedSignature = e.target.result;
                document.getElementById('sigClearUpload').style.display = 'block';
                syncSignatureInput();
            };
            reader.readAsDataURL(file);
        }

        function clearUploadSignature() {
            uploadedSignature = '';
            document.getElementById('sigUploadInput').value = '';
            const img = document.getElementById('sigPreview');
            img.src = '';
            img.style.display = 'none';
            document.getElementById('sigClearUpload').style.display = 'none';
            syncSignatureInput();
        }

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
        }

        window.addEventListener('load', resizeCanvas);
        window.addEventListener('resize', resizeCanvas);

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('touchstart', startDraw, {passive: false});
        canvas.addEventListener('touchmove', draw, {passive: false});
        window.addEventListener('touchend', stopDraw);

        function startDraw(e) {
            drawing = true;
            ctx.beginPath();
            const pos = getPos(e);
            ctx.moveTo(pos.x, pos.y);
            e.preventDefault();
        }

        function draw(e) {
            if (!drawing) return;
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            e.preventDefault();
        }

        function stopDraw() {
            if (!drawing) return;
            drawing = false;
            document.getElementById('signatureInput').value = canvas.toDataURL();
        }

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signatureInput').value = '';
        }

        document.getElementById('equipmentForm').addEventListener('submit', function(e) {
            syncSignatureInput();
            const value = document.getElementById('signatureInput').value || '';
            const isBlank = sigMode === 'draw' ? isBlankSignature(canvas) : !uploadedSignature;
            if (! value || isBlank) {
                e.preventDefault();
                alert('Debes firmar o subir una firma antes de continuar.');
            }
        });

        function isBlankSignature(canvas) {
            const ctx = canvas.getContext('2d');
            const pixelBuffer = new Uint32Array(ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer);
            return ! pixelBuffer.some(color => color !== 0);
        }
    </script>
@endsection
