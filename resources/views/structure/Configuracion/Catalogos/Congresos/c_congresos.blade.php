@extends('layouts.dashboard')

@section('title', 'Crear Congreso')

@section('content')
    <div style="max-width:1000px; margin:0 auto;">
        <h2 class="page-title" style="margin:0 0 8px;">Nuevo Congreso</h2>
        <p class="page-sub" style="margin:0 0 22px;">Registra un nuevo congreso. El Id se genera automáticamente.</p>

        @if (session('status'))
            <div class="alert alert--ok" style="margin-bottom:16px;">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" style="background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:22px; box-shadow:var(--shadow);">
            <form method="POST" action="{{ route('configuracion.congresos.store') }}" enctype="multipart/form-data">
                @csrf

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start;">
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div class="form-section" style="border:1px solid var(--border); border-radius:12px; padding:16px;">
                            <h3 style="margin:0 0 16px; font-size:16px; color:var(--text);">Información general</h3>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start;">
                                <div>
                                    <x-ui.form-group label="Nombre *" name="name" placeholder="Nombre del congreso" :required="true" />

                                    <div style="margin-top:16px;">
                                        <x-ui.form-group for="category_id" label="Categoría *">
                                            <select id="category_id" name="category_id" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                                                <option value="" disabled selected>Seleccione una categoría</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </x-ui.form-group>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 8px; font-size:14px; color:var(--text);">Imagen / Portada</p>
                                    <label for="image" class="image-upload" style="display:flex; align-items:center; justify-content:center; min-height:124px; border:2px dashed var(--border); border-radius:12px; padding:28px; text-align:center; cursor:pointer; color:var(--muted);">
                                        <span style="font-size:14px;">Arrastra una imagen aquí o haz clic para seleccionar</span>
                                        <input id="image" name="image" type="file" accept="image/*" style="display:none;" />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="border:1px solid var(--border); border-radius:12px; padding:16px;">
                            <h3 style="margin:0 0 16px; font-size:16px; color:var(--text);">Programación</h3>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                <x-ui.form-group label="Fecha de inicio *" name="start_date" type="date" :required="true" />
                                <x-ui.form-group label="Fecha de finalización *" name="end_date" type="date" :required="true" />
                                <x-ui.form-group label="Hora de montaje *" name="assembly_time" type="time" :required="true" />
                                <x-ui.form-group label="Hora de desmontaje *" name="disassembly_time" type="time" :required="true" />
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div class="form-section" style="border:1px solid var(--border); border-radius:12px; padding:16px;">
                            <h3 style="margin:0 0 16px; font-size:16px; color:var(--text);">Configuración de acceso</h3>

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <div>
                                    <p style="margin:0; font-weight:500; color:var(--text);">Descarga de recursos</p>
                                    <p style="margin:4px 0 0; font-size:13px; color:var(--muted);">Permitir descargar archivos del congreso</p>
                                </div>
                                <label class="ui-switch">
                                    <input type="hidden" name="download_access" value="0">
                                    <input type="checkbox" id="download_access" name="download_access" value="1" @checked(old('download_access') == '1' || old('download_access') === true)>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div id="download-fields" style="margin-top:12px; @if (!(old('download_access') == '1' || old('download_access') === true)) display:none; @endif">
                                <x-ui.form-group label="Lugar de descarga" name="download_text" placeholder="Lugar o enlace de descarga" />
                            </div>

                            <hr style="border:0; border-top:1px solid var(--border); margin:16px 0;">

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <div>
                                    <p style="margin:0; font-weight:500; color:var(--text);">Carga de archivos</p>
                                    <p style="margin:4px 0 0; font-size:13px; color:var(--muted);">Permitir subir archivos al congreso</p>
                                </div>
                                <label class="ui-switch">
                                    <input type="hidden" name="upload_access" value="0">
                                    <input type="checkbox" id="upload_access" name="upload_access" value="1" @checked(old('upload_access') == '1' || old('upload_access') === true)>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div id="upload-fields" style="margin-top:12px; @if (!(old('upload_access') == '1' || old('upload_access') === true)) display:none; @endif">
                                <x-ui.form-group label="Lugar de carga" name="upload_text" placeholder="Lugar o enlace donde se suben o entregan cosas" />
                            </div>
                        </div>

                        <div class="form-section" style="border:1px solid var(--border); border-radius:12px; padding:16px;">
                            <h3 style="margin:0 0 16px; font-size:16px; color:var(--text);">Ubicación</h3>

                            <x-ui.form-group for="address" label="Dirección / Ubicación">
                                <input id="address" name="address" type="text" value="{{ old('address') }}" placeholder="Ej. Hotel Hilton, Ciudad de México" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" />
                            </x-ui.form-group>

                            <div id="address-suggestions" style="border:1px solid var(--border); border-radius:9px; background:var(--surface); margin-top:4px; overflow:hidden; display:none;"></div>

                            <div style="display:flex; align-items:center; gap:12px; margin-top:8px; font-size:13px; color:var(--muted);">
                                <span>📍</span>
                                <a id="preview-map" href="#" target="_blank" rel="noopener" style="color:var(--primary);">Ver en Google Maps</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:18px;">
                    <button type="button" class="btn btn--secondary" onclick="history.back()">Cancelar</button>
                    <button type="submit" class="btn">Guardar Congreso</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }
        .address-suggestion { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); }
        .address-suggestion:last-child { border-bottom: none; }
        .address-suggestion:hover { background: var(--hover, rgba(0,0,0,0.05)); }
        .image-upload:hover { border-color: var(--primary, #3b82f6); color: var(--text); }
    </style>

    @push('scripts')
        <script>
            (function () {
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
                    if (!results || !results.length) {
                        clearSuggestions();
                        return;
                    }
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

                function toggleUploadFields() {
                    uploadFields.style.display = uploadSwitch.checked ? 'block' : 'none';
                }

                uploadSwitch.addEventListener('change', toggleUploadFields);
                toggleUploadFields();

                var downloadSwitch = document.getElementById('download_access');
                var downloadFields = document.getElementById('download-fields');

                function toggleDownloadFields() {
                    downloadFields.style.display = downloadSwitch.checked ? 'block' : 'none';
                }

                downloadSwitch.addEventListener('change', toggleDownloadFields);
                toggleDownloadFields();
            })();
        </script>
    @endpush
@endsection
