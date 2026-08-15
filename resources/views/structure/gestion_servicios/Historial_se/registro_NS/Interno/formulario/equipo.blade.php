@extends('structure.gestion_servicios.layout')

@section('title', 'Registro del equipo')

@section('service_content')
    @include('structure.gestion_servicios.Historial_se.registro_ns.Interno.interno_estilos_base')
    <style>
        .ns-stepper { margin-bottom: 26px; }
        .ns-section-subtitle { margin-bottom: 20px; }
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
                                <option value="{{ $subtype->name }}" data-cascade-parent="{{ $subtype->equipmentType->name ?? '' }}">{{ $subtype->name }}</option>
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
                                <option value="{{ $model->name }}" data-cascade-parent="{{ $model->brand->name ?? '' }}">{{ $model->name }}</option>
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

        function cascadeSelect(childName, parentName) {
            const parent = document.querySelector('select[name="' + parentName + '"]');
            const child = document.querySelector('select[name="' + childName + '"]');
            if (!parent || !child) return;

            const normalize = function (value) {
                return (value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            };

            const filter = function () {
                const parentValue = normalize(parent.value);
                Array.from(child.options).forEach(function (option) {
                    const optionParent = normalize(option.dataset.cascadeParent);
                    const show = !option.value || !parentValue || optionParent === parentValue;
                    option.style.display = show ? '' : 'none';
                    if (!show && option.selected) {
                        option.selected = false;
                    }
                });
            };

            parent.addEventListener('change', function () {
                child.value = '';
                filter();
            });

            filter();
        }

        cascadeSelect('subtipo', 'tipo_equipo');
        cascadeSelect('modelo', 'marca');
    </script>
@endsection
