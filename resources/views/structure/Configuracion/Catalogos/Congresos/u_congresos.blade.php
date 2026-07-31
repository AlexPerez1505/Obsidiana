@extends('structure.Configuracion.layout')

@section('title', 'Editar Congreso')

@section('configuracion_content')
    <div style="max-width:1200px; margin:0 auto;">
        <div class="card catalog-card">
            <div class="catalog-header">
                <h2 class="page-title">Editar Congreso</h2>
            </div>
            <p class="page-sub">Actualiza los datos del congreso.</p>

            @if (session('status_congress'))
                <div class="alert alert--ok" style="margin:16px 0 0;">{{ session('status_congress') }}</div>
            @endif

            <form id="congress-form" method="POST" action="{{ route('configuracion.congresos.update', $congress) }}" enctype="multipart/form-data" style="margin-top:18px;">
                @csrf
                @method('PUT')

                <div id="step-1" style="display:grid; grid-template-columns:5fr 4fr; gap:16px; align-items:start;">
                    <div class="form-section section-info">
                        <h3 class="form-section-title">Información general</h3>

                        <x-ui.form-group label="Nombre *" name="name" placeholder="Nombre del congreso" :value="$congress->name" :required="true" />

                        <div style="margin-top:16px;">
                            <x-ui.form-group for="category_id" label="Categoría *">
                                <input type="hidden" name="category_id" id="category_id_value" value="{{ old('category_id', $congress->category_id) }}" required>
                                <div id="category_id" class="custom-select" role="combobox" tabindex="0" aria-expanded="false" aria-haspopup="listbox" data-placeholder="Seleccione una categoría">
                                    <div class="custom-select-trigger">
                                        <span class="custom-select-label">Seleccione una categoría</span>
                                        <svg class="custom-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9" /></svg>
                                    </div>
                                    <div class="custom-select-options" role="listbox">
                                        @foreach ($categories as $category)
                                            <div class="custom-select-option" data-value="{{ $category->id }}" role="option" @if(old('category_id', $congress->category_id) == $category->id) aria-selected="true" @endif>{{ $category->name }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </x-ui.form-group>
                        </div>

                        <div style="margin-top:16px;">
                            <p class="form-label">Archivos / Portada</p>
                            <label for="images" class="image-upload">
                                <span>Arrastra archivos aquí o haz clic para seleccionar (imagen, PDF u Office · máx. 10 MB c/u · hasta 10)</span>
                                <input id="images" name="images[]" type="file" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" multiple />
                            </label>
                            <div id="files-preview" class="files-preview"></div>
                        </div>

                        <div style="margin-top:16px;">
                            <x-ui.form-group for="comments" label="Comentarios">
                                <textarea id="comments" name="comments" rows="4" maxlength="5000" placeholder="Notas o comentarios internos sobre el congreso" class="form-input">{{ old('comments', $congress->comments) }}</textarea>
                            </x-ui.form-group>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div class="form-section section-schedule">
                            <h3 class="form-section-title">Programación</h3>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                <x-ui.form-group label="Fecha de inicio *" name="start_date" type="text" :required="true" inputClass="form-input flatpickr-date" :value="$congress->start_date->format('Y-m-d')" />
                                <x-ui.form-group label="Fecha de finalización *" name="end_date" type="text" :required="true" inputClass="form-input flatpickr-date" :value="$congress->end_date->format('Y-m-d')" />
                                <x-ui.form-group label="Hora de montaje *" name="assembly_time" type="text" :required="true" inputClass="form-input flatpickr-time" :value="$congress->assembly_time->format('H:i')" />
                                <x-ui.form-group label="Hora de desmontaje *" name="disassembly_time" type="text" :required="true" inputClass="form-input flatpickr-time" :value="$congress->disassembly_time->format('H:i')" />
                            </div>
                        </div>

                        <div class="form-section section-access">
                            <h3 class="form-section-title">Configuración de acceso</h3>

                            <div class="access-row">
                                <div>
                                    <p class="access-title">Descarga de recursos</p>
                                    <p class="access-desc">Permitir descargar archivos del congreso</p>
                                </div>
                                <label class="ui-switch">
                                    <input type="hidden" name="download_access" value="0">
                                    <input type="checkbox" id="download_access" name="download_access" value="1" @checked(old('download_access', $congress->download_access))>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div id="download-fields" class="access-fields @if (! old('download_access', $congress->download_access)) is-hidden @endif">
                                <x-ui.form-group label="Lugar de descarga" name="download_text" placeholder="Lugar o enlace de descarga" :value="$congress->download_text" />
                            </div>

                            <hr class="form-divider">

                            <div class="access-row">
                                <div>
                                    <p class="access-title">Carga de archivos</p>
                                    <p class="access-desc">Permitir subir archivos al congreso</p>
                                </div>
                                <label class="ui-switch">
                                    <input type="hidden" name="upload_access" value="0">
                                    <input type="checkbox" id="upload_access" name="upload_access" value="1" @checked(old('upload_access', $congress->upload_access))>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div id="upload-fields" class="access-fields @if (! old('upload_access', $congress->upload_access)) is-hidden @endif">
                                <x-ui.form-group label="Lugar de carga" name="upload_text" placeholder="Lugar o enlace donde se suben o entregan cosas" :value="$congress->upload_text" />
                            </div>
                        </div>

                        <div class="form-section section-location">
                            <h3 class="form-section-title">Ubicación</h3>

                            <x-ui.form-group for="address" label="Dirección / Ubicación">
                                <input id="address" name="address" type="text" value="{{ old('address', $congress->address) }}" placeholder="Ej. Hotel Hilton, Ciudad de México" class="form-input" />
                            </x-ui.form-group>

                            <div id="address-suggestions" class="address-suggestions"></div>

                            <div class="address-map-hint">
                                <span></span>
                                <a id="preview-map" href="#" target="_blank" rel="noopener">Ver en Google Maps</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn--secondary" onclick="history.back()">Regresar</button>
                    <button type="button" class="btn" id="btn-next-step">Continuar</button>
                </div>

                <!-- ===== Paso 2: Notificaciones ===== -->
                <div id="step-2" class="step-2 is-hidden">
                    <div class="step-indicator">
                        <span class="step-dot">1</span> Información
                        <span class="step-arrow">→</span>
                        <span class="step-dot step-dot--active">2</span> Notificaciones
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Notificaciones</h3>
                        <p class="access-desc" style="margin:0 0 12px;">Selecciona los usuarios que serán notificados sobre este congreso.</p>

                        <x-ui.form-group for="notify_users" label="Usuarios a notificar">
                            <select id="notify_users" name="notify_users[]" class="form-input" multiple size="8">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(in_array($user->id, old('notify_users', $congress->notifiedUsers->pluck('id')->toArray())))>{{ $user->name }} — {{ $user->email }}</option>
                                @endforeach
                            </select>
                        </x-ui.form-group>

                        <p class="access-desc" style="margin:8px 0 0;">Mantén Ctrl (o Cmd en Mac) para seleccionar varios. Puedes no seleccionar ninguno.</p>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn--secondary" id="btn-back-step">Regresar</button>
                        <button type="submit" class="btn">Actualizar Congreso</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    
        

    @push('head')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
        @include('structure.Configuracion.Catalogos.Congresos.styles_congreso')
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
        <script>
            (function () {
                /* ===== Flatpickr (calendario y hora personalizados) ===== */
                flatpickr.localize(flatpickr.l10ns.es);

                var startDatePicker = flatpickr('input[name="start_date"]', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: false,
                    altInputClass: 'form-input flatpickr-alt flatpickr-date',
                    onChange: function () { if (typeof syncEndDateMin === 'function') syncEndDateMin(); },
                    onReady: function (selectedDates, dateStr, instance) {
                        instance.altInput.setAttribute('aria-label', 'Fecha de inicio');
                    }
                });

                var endDatePicker = flatpickr('input[name="end_date"]', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: false,
                    altInputClass: 'form-input flatpickr-alt flatpickr-date',
                    onReady: function (selectedDates, dateStr, instance) {
                        instance.altInput.setAttribute('aria-label', 'Fecha de finalización');
                    }
                });

                flatpickr('input[name="assembly_time"]', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altInput: true,
                    altFormat: 'h:i K',
                    allowInput: false,
                    time_24hr: false,
                    altInputClass: 'form-input flatpickr-alt flatpickr-time',
                    onReady: function (selectedDates, dateStr, instance) {
                        instance.altInput.setAttribute('aria-label', 'Hora de montaje');
                    }
                });

                flatpickr('input[name="disassembly_time"]', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altInput: true,
                    altFormat: 'h:i K',
                    allowInput: false,
                    time_24hr: false,
                    altInputClass: 'form-input flatpickr-alt flatpickr-time',
                    onReady: function (selectedDates, dateStr, instance) {
                        instance.altInput.setAttribute('aria-label', 'Hora de desmontaje');
                    }
                });

                /* ===== Referencia al formulario ===== */
                var congressForm = document.getElementById('congress-form');

                var addrInput = document.getElementById('address');
                var previewLink = document.getElementById('preview-map');
                var suggestionsBox = document.getElementById('address-suggestions');
                var debounceTimer;

                function updateMapLink() {
                    var q = addrInput.value.trim();
                    if (q) {
                        previewLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q);
                        previewLink.style.display = 'inline';
                    } else {
                        previewLink.href = '#';
                        previewLink.style.display = 'none';
                    }
                }

                function clearSuggestions() {
                    suggestionsBox.style.display = 'none';
                    suggestionsBox.innerHTML = '';
                }

                function showSuggestions(results) {
                    if (!results || !results.length) { clearSuggestions(); return; }
                    suggestionsBox.innerHTML = results.map(function (r) {
                        var name = r.display_name.replace(/"/g, '&quot;');
                        return '<div class="address-suggestion" data-name="' + name + '">' + r.display_name + '</div>';
                    }).join('');
                    suggestionsBox.style.display = 'block';
                }

                function searchAddress() {
                    clearTimeout(debounceTimer);
                    var q = addrInput.value.trim();
                    updateMapLink();
                    if (!q) { clearSuggestions(); return; }
                    debounceTimer = setTimeout(function () {
                        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(q))
                            .then(function (r) { return r.json(); })
                            .then(function (data) { showSuggestions(data); })
                            .catch(function () { clearSuggestions(); });
                    }, 350);
                }

                addrInput.addEventListener('input', searchAddress);
                addrInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (suggestionsBox.firstChild) { suggestionsBox.firstChild.click(); }
                    }
                });

                suggestionsBox.addEventListener('click', function (e) {
                    if (e.target.classList.contains('address-suggestion')) {
                        addrInput.value = e.target.getAttribute('data-name');
                        clearSuggestions();
                        updateMapLink();
                    }
                });

                updateMapLink();
                searchAddress();

                var uploadSwitch = document.getElementById('upload_access');
                var uploadFields = document.getElementById('upload-fields');
                function toggleUploadFields() { uploadFields.classList.toggle('is-hidden', !uploadSwitch.checked); }
                uploadSwitch.addEventListener('change', toggleUploadFields);

                var downloadSwitch = document.getElementById('download_access');
                var downloadFields = document.getElementById('download-fields');
                function toggleDownloadFields() { downloadFields.classList.toggle('is-hidden', !downloadSwitch.checked); }
                downloadSwitch.addEventListener('change', toggleDownloadFields);

                /* ===== Validación de fechas: end_date >= start_date ===== */
                var startDateInput = document.getElementById('start_date');
                var endDateInput = document.getElementById('end_date');

                function syncEndDateMin() {
                    if (startDateInput && startDateInput.value) {
                        endDatePicker.set('minDate', startDateInput.value);
                        if (endDateInput && endDateInput.value && endDateInput.value < startDateInput.value) {
                            endDatePicker.setDate(startDateInput.value);
                        }
                    } else {
                        endDatePicker.set('minDate', null);
                    }
                }

                if (congressForm) {
                    congressForm.addEventListener('submit', function (e) {
                        if (startDateInput && startDateInput.value && endDateInput && endDateInput.value && endDateInput.value < startDateInput.value) {
                            e.preventDefault();
                            endDatePicker.setDate(startDateInput.value);
                            alert('La fecha de finalización no puede ser anterior a la fecha de inicio.');
                        }
                    });
                }

                syncEndDateMin();
                // Sincronizar campos condicionales tras restaurar el borrador
                toggleUploadFields();
                toggleDownloadFields();

                /* ===== Flujo de dos pasos: Paso 1 → Paso 2 (Notificaciones) ===== */
                var step1Content = document.getElementById('step-1');
                var step2 = document.getElementById('step-2');
                var btnNext = document.getElementById('btn-next-step');
                var btnBack = document.getElementById('btn-back-step');
                var step1Actions = step2.previousElementSibling; // .form-actions del paso 1

                // Campos requeridos del paso 1
                var step1Required = ['name', 'category_id', 'start_date', 'end_date', 'assembly_time', 'disassembly_time'];

                function validateStep1() {
                    var missing = [];
                    step1Required.forEach(function (name) {
                        var el = document.querySelector('[name="' + name + '"]');
                        var visualEl = el;
                        if (name === 'category_id' && document.getElementById('category_id')) {
                            visualEl = document.getElementById('category_id').querySelector('.custom-select-trigger');
                        }
                        if (!el || !el.value.trim()) {
                            missing.push(name);
                            if (visualEl) visualEl.style.borderColor = '#ef4444';
                        } else if (visualEl) {
                            visualEl.style.borderColor = '';
                        }
                    });
                    // Validar fechas
                    if (startDateInput.value && endDateInput.value && endDateInput.value < startDateInput.value) {
                        missing.push('end_date');
                        var endAlt = endDateInput ? endDateInput.nextElementSibling : null;
                        if (endAlt) endAlt.style.borderColor = '#ef4444';
                        alert('La fecha de finalización no puede ser anterior a la fecha de inicio.');
                    }
                    return missing.length === 0;
                }

                function goToStep2() {
                    if (!validateStep1()) {
                        alert('Por favor completa todos los campos obligatorios (*) antes de continuar.');
                        return;
                    }
                    if (step1Content) step1Content.classList.add('is-hidden');
                    if (step1Actions) step1Actions.classList.add('is-hidden');
                    step2.classList.remove('is-hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                function goToStep1() {
                    step2.classList.add('is-hidden');
                    if (step1Content) step1Content.classList.remove('is-hidden');
                    if (step1Actions) step1Actions.classList.remove('is-hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                if (btnNext) btnNext.addEventListener('click', goToStep2);
                if (btnBack) btnBack.addEventListener('click', goToStep1);

                /* ===== Previsualización de archivos seleccionados ===== */
                var fileInput = document.getElementById('images');
                var previewBox = document.getElementById('files-preview');
                var dt = new DataTransfer();

                function iconFor(file) {
                    var ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp'].indexOf(ext) !== -1) return null; // se usa miniatura
                    if (ext === 'pdf') return '📄';
                    if (['doc', 'docx'].indexOf(ext) !== -1) return '📝';
                    if (['xls', 'xlsx'].indexOf(ext) !== -1) return '📊';
                    if (['ppt', 'pptx'].indexOf(ext) !== -1) return '📽️';
                    return '📎';
                }

                function renderPreview() {
                    previewBox.innerHTML = '';
                    Array.from(dt.files).forEach(function (file, idx) {
                        var card = document.createElement('div');
                        card.className = 'file-card';

                        var thumb = document.createElement('div');
                        thumb.className = 'thumb';
                        var ic = iconFor(file);
                        if (ic === null) {
                            var img = document.createElement('img');
                            img.src = URL.createObjectURL(file);
                            img.onload = function () { URL.revokeObjectURL(this.src); };
                            thumb.appendChild(img);
                        } else {
                            thumb.textContent = ic;
                        }
                        card.appendChild(thumb);

                        var name = document.createElement('div');
                        name.className = 'name';
                        name.textContent = file.name;
                        card.appendChild(name);

                        var rm = document.createElement('button');
                        rm.type = 'button';
                        rm.className = 'remove';
                        rm.textContent = '×';
                        rm.title = 'Quitar archivo';
                        rm.addEventListener('click', function (e) {
                            e.preventDefault();
                            var ndt = new DataTransfer();
                            Array.from(dt.files).forEach(function (f, i) { if (i !== idx) ndt.items.add(f); });
                            dt = ndt;
                            fileInput.files = ndt.files;
                            renderPreview();
                        });
                        card.appendChild(rm);

                        previewBox.appendChild(card);
                    });
                }

                fileInput.addEventListener('change', function () {
                    Array.from(fileInput.files).forEach(function (f) {
                        if (dt.files.length >= 10) return;
                        dt.items.add(f);
                    });
                    fileInput.files = dt.files;
                    renderPreview();
                });

                /* Soporte para arrastrar y soltar sobre la zona */
                var dropZone = document.querySelector('label.image-upload');
                ['dragenter', 'dragover'].forEach(function (ev) {
                    dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.style.borderColor = '#00A8FF'; });
                });
                ['dragleave', 'drop'].forEach(function (ev) {
                    dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.style.borderColor = ''; });
                });
                dropZone.addEventListener('drop', function (e) {
                    if (!e.dataTransfer || !e.dataTransfer.files) return;
                    Array.from(e.dataTransfer.files).forEach(function (f) {
                        if (dt.files.length >= 10) return;
                        dt.items.add(f);
                    });
                    fileInput.files = dt.files;
                    renderPreview();
                });

                /* ===== Custom select de Categoría ===== */
                var catSelect = document.getElementById('category_id');
                var catValue = document.getElementById('category_id_value');
                var catTrigger = catSelect ? catSelect.querySelector('.custom-select-trigger') : null;
                var catOptionsBox = catSelect ? catSelect.querySelector('.custom-select-options') : null;
                var catLabel = catSelect ? catSelect.querySelector('.custom-select-label') : null;

                function setCategory(value, text) {
                    if (catValue) catValue.value = value || '';
                    if (catLabel) catLabel.textContent = text || (catSelect ? catSelect.dataset.placeholder : 'Seleccione una categoría');
                    if (catOptionsBox) {
                        catOptionsBox.querySelectorAll('.custom-select-option').forEach(function (opt) {
                            opt.setAttribute('aria-selected', opt.dataset.value === value ? 'true' : 'false');
                        });
                    }
                }

                function toggleCategory() {
                    if (!catSelect) return;
                    catSelect.classList.toggle('is-open');
                    catSelect.setAttribute('aria-expanded', catSelect.classList.contains('is-open') ? 'true' : 'false');
                }

                function closeCategory() {
                    if (!catSelect) return;
                    catSelect.classList.remove('is-open');
                    catSelect.setAttribute('aria-expanded', 'false');
                }

                if (catSelect && catTrigger && catOptionsBox && catValue) {
                    // Inicializar con old() o borrador
                    var initialValue = catValue.value;
                    if (initialValue) {
                        var initialOpt = catOptionsBox.querySelector('[data-value="' + initialValue + '"]');
                        if (initialOpt) setCategory(initialValue, initialOpt.textContent);
                    }

                    catTrigger.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleCategory();
                    });

                    catOptionsBox.addEventListener('click', function (e) {
                        if (e.target.classList.contains('custom-select-option') && e.target.dataset.value) {
                            setCategory(e.target.dataset.value, e.target.textContent);
                            closeCategory();
                        }
                    });

                    document.addEventListener('click', function (e) {
                        if (catSelect && !catSelect.contains(e.target)) closeCategory();
                    });

                    catSelect.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') { e.preventDefault(); closeCategory(); }
                    });
                }
            })();
        </script>
    @endpush
@endsection
