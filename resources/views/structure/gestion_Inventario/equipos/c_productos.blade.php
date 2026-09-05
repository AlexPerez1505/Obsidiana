@extends('layouts.dashboard')

@section('title', 'Nuevo Equipo')
@section('page-title', 'Nuevo equipo')

@push('head')
<style>
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-top:18px; }
.form-group label { font-size:13px; font-weight:700; margin-bottom:6px; display:block; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); outline:none; }
.form-group input::placeholder, .form-group textarea::placeholder { color:#aaa; }
.form-group input:disabled { opacity:0.55; cursor:not-allowed; }
.upload-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-top:8px; }
.upload-card { position:relative; border:1px dashed var(--border); border-radius:12px; padding:14px; text-align:center; cursor:pointer; color:var(--muted); background:var(--surface); overflow:hidden; }
.upload-card:hover { border-color:var(--primary); color:var(--primary); }
.upload-card .evidence-preview { max-width:100%; max-height:100px; object-fit:contain; border-radius:8px; }
.upload-card .file-name { font-size:11px; margin-top:6px; word-break:break-word; color:var(--text); }
.upload-card .remove-evidence { position:absolute; top:6px; right:6px; width:22px; height:22px; background:var(--danger, #ff4a4a); color:#fff; border:none; border-radius:50%; cursor:pointer; font-size:14px; line-height:1; display:flex; align-items:center; justify-content:center; z-index:10; }
.signature-box { border:1px dashed var(--border); border-radius:12px; width:100%; height:120px; touch-action:none; }

.combobox { position:relative; display:flex; align-items:center; }
.combobox input { padding-right:38px; }
.combobox-arrow { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--muted); cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; }
.combobox-list { position:absolute; top:calc(100% + 6px); left:0; right:0; max-height:220px; overflow-y:auto; background:var(--surface); border:1px solid var(--border); border-radius:9px; box-shadow:var(--shadow); z-index:100; list-style:none; margin:0; padding:6px 0; display:none; }
.combobox-list.open { display:block; }
.combobox-list li { padding:10px 14px; cursor:pointer; color:var(--text); font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.combobox-list li:hover,
.combobox-list li.active { background:var(--primary-soft); color:var(--primary); }
.combobox-list .no-results { color:var(--muted); cursor:default; text-align:center; font-size:13px; }
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('inventory.equipos.store') }}" enctype="multipart/form-data" autocomplete="off" style="max-width:900px; margin:0 auto;">
    @csrf

    <x-ui.card>
        <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            Datos del equipo
        </h3>
        <p class="muted" style="margin:0 0 18px; font-size:13px;">Ingresa la información del equipo para registrarlo en inventario</p>

        <div class="form-grid">
            <div class="form-group">
                <label for="tipo_equipo">Tipo de equipo</label>
                <input type="text" name="tipo_equipo" id="tipo_equipo" list="tipo_equipo_list" placeholder="Ej. Equipo médico" value="{{ old('tipo_equipo') }}" required>
                <datalist id="tipo_equipo_list">
                    @foreach ($equipmentTypes->unique('name')->sortBy('name')->values() as $type)
                        <option value="{{ $type->name }}">
                    @endforeach
                </datalist>
            </div>
            <div class="form-group">
                <label for="subtipo">Subtipo</label>
                <input type="text" name="subtipo" id="subtipo" list="subtipo_list" placeholder="Ej. Monitor de signos vitales" value="{{ old('subtipo') }}" disabled>
                <datalist id="subtipo_list"></datalist>
            </div>
            <div class="form-group">
                <label for="marca">Marca</label>
                <input type="text" name="marca" id="marca" list="marca_list" placeholder="Ej. Olympus" value="{{ old('marca') }}">
                <datalist id="marca_list">
                    @foreach ($brands->unique('name')->sortBy('name')->values() as $brand)
                        <option value="{{ $brand->name }}">
                    @endforeach
                </datalist>
            </div>
            <div class="form-group">
                <label for="modelo">Modelo</label>
                <input type="text" name="modelo" id="modelo" list="modelo_list" placeholder="Ej. C-90" value="{{ old('modelo') }}" disabled>
                <datalist id="modelo_list"></datalist>
            </div>
            <div class="form-group">
                <label for="serie">Número de serie</label>
                <input type="text" name="serie" id="serie" placeholder="Ej. SN-893-832" value="{{ old('serie') }}">
            </div>
            <div class="form-group">
                <label for="externo_interno">Externo / Interno</label>
                <select name="externo_interno" id="externo_interno" required>
                    <option value="" disabled {{ old('externo_interno') ? '' : 'selected' }}>Seleccionar</option>
                    <option value="Externo" {{ old('externo_interno') === 'Externo' ? 'selected' : '' }}>Externo</option>
                    <option value="Interno" {{ old('externo_interno') === 'Interno' ? 'selected' : '' }}>Interno</option>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label for="descripcion_equipo">Descripción del equipo</label>
                <textarea name="descripcion_equipo" id="descripcion_equipo" rows="3" placeholder="Describe el equipo y su función">{{ old('descripcion_equipo') }}</textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="3" placeholder="Anotaciones sobre el estado del equipo">{{ old('observaciones') }}</textarea>
            </div>
        </div>

        <div class="form-group" style="margin-top:18px;">
            <label>Evidencia del equipo</label>
            <div class="upload-grid">
                <label class="upload-card">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div style="font-size:13px; margin-top:8px;">Imagen 1</div>
                    <div style="font-size:12px;">Toca para subir</div>
                    <input type="file" name="evidencia_1" accept="image/*" style="display:none;">
                </label>
                <label class="upload-card">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div style="font-size:13px; margin-top:8px;">Imagen 2</div>
                    <div style="font-size:12px;">Toca para subir</div>
                    <input type="file" name="evidencia_2" accept="image/*" style="display:none;">
                </label>
                <label class="upload-card">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div style="font-size:13px; margin-top:8px;">Imagen 3</div>
                    <div style="font-size:12px;">Toca para subir</div>
                    <input type="file" name="evidencia_3" accept="image/*" style="display:none;">
                </label>
                <label class="upload-card">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 23 17 7 17 7 7 23 7"/><rect x="1" y="3" width="4" height="18" rx="1"/><polyline points="5 7 7 7 7 17 5 17"/></svg>
                    <div style="font-size:13px; margin-top:8px;">Video</div>
                    <div style="font-size:12px;">Toca para subir</div>
                    <input type="file" name="evidencia_video" accept="video/*" style="display:none;">
                </label>
            </div>
            <p style="font-size:12px; color:var(--muted); margin-top:8px;">Formatos permitidos: JPG, PNG, MP4. Tamaño máximo: 10MB por archivo</p>
        </div>

        <div class="form-group" style="margin-top:18px;">
            <label>Firma Digital</label>
            <canvas class="signature-box" id="signature-pad" style="cursor:crosshair;"></canvas>
            <div style="display:flex; align-items:center; gap:14px; margin-top:8px;">
                <a href="#" style="font-size:13px; color:var(--primary);" onclick="clearSignature(); return false;">Limpiar firma</a>
                <a href="#" style="font-size:13px; color:var(--primary);" onclick="document.getElementById('signature-upload').click(); return false;">Cargar firma</a>
                <input type="file" id="signature-upload" accept="image/*" style="display:none;">
            </div>
            <input type="hidden" name="firma" id="firma-input">
        </div>
    </x-ui.card>

    <div class="page-foot">
        <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost">Cancelar</a>
        <x-ui.button>Guardar equipo</x-ui.button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    const firmaInput = document.getElementById('firma-input');
    const signatureUpload = document.getElementById('signature-upload');
    let drawing = false;

    function resizeCanvas() {
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = getComputedStyle(document.body).color || '#000';
    }

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;
        if (e.touches && e.touches.length) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height),
        };
    }

    function updateFirmaInput() {
        if (firmaInput) firmaInput.value = canvas.toDataURL('image/png');
    }

    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function endDraw() {
        if (!drawing) return;
        drawing = false;
        ctx.closePath();
        updateFirmaInput();
    }

    function drawImageToCanvas(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                resizeCanvas();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const scale = Math.min(canvas.width / img.width, canvas.height / img.height, 1);
                const x = (canvas.width - img.width * scale) / 2;
                const y = (canvas.height - img.height * scale) / 2;
                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                updateFirmaInput();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function clearSignature() {
        resizeCanvas();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (firmaInput) firmaInput.value = '';
        if (signatureUpload) signatureUpload.value = '';
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', endDraw);
    canvas.addEventListener('mouseout', endDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', endDraw);

    if (signatureUpload) {
        signatureUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) drawImageToCanvas(this.files[0]);
        });
    }

    window.clearSignature = clearSignature;
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    (function () {
        function Combobox(input) {
            input.removeAttribute('list');
            input.setAttribute('autocomplete', 'off');

            var wrapper = document.createElement('div');
            wrapper.className = 'combobox';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            var arrow = document.createElement('button');
            arrow.type = 'button';
            arrow.className = 'combobox-arrow';
            arrow.setAttribute('tabindex', '-1');
            arrow.setAttribute('aria-label', 'Mostrar opciones');
            arrow.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9" /></svg>';
            wrapper.appendChild(arrow);

            var list = document.createElement('ul');
            list.className = 'combobox-list';
            wrapper.appendChild(list);

            var options = [];
            var open = false;
            var active = -1;

            function render(filter) {
                filter = filter || '';
                list.innerHTML = '';
                var term = filter.trim().toLowerCase();
                var matches = options.filter(function (o) { return o.toLowerCase().indexOf(term) !== -1; });
                matches.forEach(function (text, i) {
                    var li = document.createElement('li');
                    li.textContent = text;
                    if (i === active) li.classList.add('active');
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        pick(text);
                    });
                    list.appendChild(li);
                });
                if (matches.length === 0) {
                    var li = document.createElement('li');
                    li.className = 'no-results';
                    li.textContent = 'Sin coincidencias';
                    list.appendChild(li);
                }
            }

            function pick(text) {
                input.value = text;
                active = -1;
                close();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function openList() {
                if (input.disabled) return;
                open = true;
                list.classList.add('open');
                active = -1;
                render(input.value);
            }

            function close() {
                open = false;
                active = -1;
                list.classList.remove('open');
            }

            input.addEventListener('focus', openList);
            input.addEventListener('blur', function () { setTimeout(close, 150); });
            input.addEventListener('input', function () {
                if (!open) openList();
                else render(input.value);
            });

            arrow.addEventListener('mousedown', function (e) {
                e.preventDefault();
                if (open) close();
                else input.focus();
            });

            input.addEventListener('keydown', function (e) {
                var items;
                if (!open) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        openList();
                    }
                    return;
                }
                items = list.querySelectorAll('li:not(.no-results)');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    active = (active + 1) % items.length;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    active = (active - 1 + items.length) % items.length;
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (active >= 0 && items[active]) {
                        items[active].click();
                    } else if (input.value.trim()) {
                        close();
                    }
                } else if (e.key === 'Escape') {
                    close();
                    input.blur();
                } else {
                    return;
                }
                render(input.value);
                if (items[active]) items[active].scrollIntoView({ block: 'nearest' });
            });

            return {
                setOptions: function (arr) {
                    options = arr;
                    if (open) render(input.value);
                }
            };
        }

        var typeInput = document.getElementById('tipo_equipo');
        var subtypeInput = document.getElementById('subtipo');
        var brandInput = document.getElementById('marca');
        var modelInput = document.getElementById('modelo');

        var typeCb = Combobox(typeInput);
        var subtypeCb = Combobox(subtypeInput);
        var brandCb = Combobox(brandInput);
        var modelCb = Combobox(modelInput);

        function debounce(fn, ms) {
            var t;
            return function () {
                clearTimeout(t);
                t = setTimeout(fn.bind(this), ms);
            };
        }

        function setEnabled(input, enabled) {
            var cb = input === subtypeInput ? subtypeCb : (input === modelInput ? modelCb : null);
            input.disabled = !enabled;
            if (!enabled) {
                input.value = '';
                if (cb) cb.setOptions([]);
            }
        }

        function loadSubtypes() {
            var value = typeInput.value.trim();
            setEnabled(subtypeInput, false);

            if (!value) return;

            fetch('{{ route('configuracion.tipos_equipo.subtypes') }}?equipment_type_name=' + encodeURIComponent(value))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    var names = data.map(function (i) { return i.name; });
                    subtypeCb.setOptions([...new Set(names)]);
                    setEnabled(subtypeInput, true);
                })
                .catch(function () {
                    setEnabled(subtypeInput, false);
                });
        }

        function loadModels() {
            var value = brandInput.value.trim();
            setEnabled(modelInput, false);

            if (!value) return;

            fetch('{{ route('configuracion.tipos_equipo.models') }}?brand_name=' + encodeURIComponent(value))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    var names = data.map(function (i) { return i.name; });
                    modelCb.setOptions([...new Set(names)]);
                    setEnabled(modelInput, true);
                })
                .catch(function () {
                    setEnabled(modelInput, false);
                });
        }

        typeCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#tipo_equipo_list option')).map(function (o) { return o.value; }));
        brandCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#marca_list option')).map(function (o) { return o.value; }));

        typeInput.addEventListener('input', debounce(loadSubtypes, 250));
        brandInput.addEventListener('input', debounce(loadModels, 250));

        if (typeInput.value) typeInput.dispatchEvent(new Event('input', { bubbles: true }));
        if (brandInput.value) brandInput.dispatchEvent(new Event('input', { bubbles: true }));
    })();

    // Previsualización de evidencias (imágenes y video)
    (function () {
        function clearCard(card, input, icon, textDivs) {
            input.value = '';
            card.querySelectorAll('.evidence-preview, .file-name, .remove-evidence').forEach(function (el) { el.remove(); });
            if (icon) icon.style.display = '';
            textDivs.forEach(function (d) { d.style.display = ''; });
        }

        function renderPreview(card, input, file) {
            var icon = card.querySelector('svg');
            var textDivs = Array.prototype.slice.call(card.querySelectorAll('div')).filter(function (d) { return !d.classList.contains('evidence-preview') && !d.classList.contains('file-name') && !d.classList.contains('remove-evidence'); });

            // Limpia previsualización anterior
            card.querySelectorAll('.evidence-preview, .file-name').forEach(function (el) { el.remove(); });
            if (icon) icon.style.display = 'none';
            textDivs.forEach(function (d) { d.style.display = 'none'; });

            var isVideo = file.type.indexOf('video/') === 0;
            var preview;

            if (isVideo) {
                preview = document.createElement('div');
                preview.className = 'evidence-preview';
                preview.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 23 17 7 17 7 7 23 7"/><rect x="1" y="3" width="4" height="18" rx="1"/><polyline points="5 7 7 7 7 17 5 17"/></svg><div style="margin-top:4px;">Video seleccionado</div>';
            } else {
                preview = document.createElement('img');
                preview.className = 'evidence-preview';
                preview.src = URL.createObjectURL(file);
                preview.onload = function () { URL.revokeObjectURL(preview.src); };
            }
            card.appendChild(preview);

            var fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            card.appendChild(fileName);

            if (!card.querySelector('.remove-evidence')) {
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-evidence';
                removeBtn.innerHTML = '&times;';
                removeBtn.setAttribute('aria-label', 'Eliminar archivo');
                removeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clearCard(card, input, icon, textDivs);
                });
                card.appendChild(removeBtn);
            }
        }

        document.querySelectorAll('.upload-card input[type="file"]').forEach(function (input) {
            var card = input.closest('.upload-card');
            if (!card) return;

            input.addEventListener('change', function () {
                if (input.files && input.files[0]) {
                    renderPreview(card, input, input.files[0]);
                }
            });
        });
    })();
</script>
@endpush
