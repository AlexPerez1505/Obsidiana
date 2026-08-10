        <!-- Paso 2: Equipo -->
        <div class="step-panel" data-step="2">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:22px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="client-avatar" id="sel-client-avatar">JD</div>
                    <div>
                        <div class="client-name" id="sel-client-name">DR. Jhone Doe</div>
                        <div class="client-info" id="sel-client-info">Cliente seleccionado</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; color:var(--muted); font-size:14px;">
                    Registrado por: <strong style="color:var(--text);">{{ auth()->user()?->name ?? 'Invitado' }}</strong>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>

            <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Datos del equipo
            </h3>
            <p class="muted" style="margin:0 0 18px; font-size:13px;">Ingresa la informacion del equipo que recibira el servicio tecnico</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tipo_equipo">Tipo de equipo</label>
                    <input type="text" name="tipo_equipo" id="tipo_equipo" list="tipo_equipo_list" placeholder="Ej. Equipo médico">
                    <datalist id="tipo_equipo_list">
                        @foreach ($equipmentTypes->unique('name')->sortBy('name')->values() as $type)
                            <option value="{{ $type->name }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="subtipo">Subtipo</label>
                    <input type="text" name="subtipo" id="subtipo" list="subtipo_list" placeholder="Ej. Monitor de signos vitales">
                    <datalist id="subtipo_list"></datalist>
                </div>
                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" name="marca" id="marca" list="marca_list" placeholder="Ej. Olympus">
                    <datalist id="marca_list">
                        @foreach ($brands->unique('name')->sortBy('name')->values() as $brand)
                            <option value="{{ $brand->name }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="modelo">Modelo</label>
                    <input type="text" name="modelo" id="modelo" list="modelo_list" placeholder="Ej. C-90">
                    <datalist id="modelo_list"></datalist>
                </div>
                <div class="form-group">
                    <label>Numero de serie</label>
                    <input type="text" name="serie" placeholder="Ej. SN-893-832">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Descripcion del equipo</label>
                    <textarea name="descripcion_equipo" rows="3" placeholder="Describe el equipo y su funcion"></textarea>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Observaciones</label>
                    <textarea name="observaciones" rows="3" placeholder="Anotaciones sobre el estado del equipo"></textarea>
                </div>
            </div>

            <div class="form-group" style="margin-top:18px;">
                <label>Evidencia del equipo</label>
                <div class="upload-grid">
                    <label class="upload-card" style="position:relative; overflow:hidden; display:block;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 1</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_1" accept="image/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:1;">
                    </label>
                    <label class="upload-card" style="position:relative; overflow:hidden; display:block;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 2</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_2" accept="image/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:1;">
                    </label>
                    <label class="upload-card" style="position:relative; overflow:hidden; display:block;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 3</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_3" accept="image/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:1;">
                    </label>
                    <label class="upload-card" style="position:relative; overflow:hidden; display:block;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 23 17 7 17 7 7 23 7"/><rect x="1" y="3" width="4" height="18" rx="1"/><polyline points="5 7 7 7 7 17 5 17"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Video</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_video" accept="video/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:1;">
                    </label>
                </div>
                <p style="font-size:12px; color:var(--muted); margin-top:8px;">Formatos permitidos: JPG, PNG, MP4. Tamano maximo: 10MB por archivo</p>
            </div>

            <div class="form-group" style="margin-top:18px;">
                <label>Firma Digital</label>
                <canvas class="signature-box" id="signature-pad" style="cursor:crosshair; display:block; width:100%; height:120px; border:1px dashed var(--border); border-radius:12px; background:#fff;"></canvas>
                <div style="display:flex; align-items:center; gap:14px; margin-top:8px;">
                    <a href="#" style="font-size:13px; color:var(--primary);" onclick="clearSignature(); return false;">Limpiar firma</a>
                    <a href="#" style="font-size:13px; color:var(--primary);" onclick="document.getElementById('signature-upload').click(); return false;">Cargar firma</a>
                    <input type="file" id="signature-upload" accept="image/*" style="display:none;">
                </div>
                <input type="hidden" name="firma" id="firma-input">
            </div>
        </div>

@push('scripts')
<script>
    // Firma basica (canvas vacio)
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const firmaInput = document.getElementById('firma-input');
    const signatureUpload = document.getElementById('signature-upload');
    
    function initializeContext() {
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#000';
    }
    
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        initializeContext();
    }
    
    setTimeout(resizeCanvas, 100);
    window.addEventListener('resize', resizeCanvas);

    function updateFirmaInput() {
        if (firmaInput) firmaInput.value = canvas.toDataURL('image/png');
    }

    let drawing = false;
    canvas.addEventListener('mousedown', e => { 
        drawing = true; 
        const rect = canvas.getBoundingClientRect();
        ctx.beginPath(); 
        ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top); 
    });
    canvas.addEventListener('mousemove', e => { 
        if (drawing) { 
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top); 
            ctx.stroke(); 
        } 
    });
    canvas.addEventListener('mouseup', () => { drawing = false; updateFirmaInput(); });
    canvas.addEventListener('mouseout', () => { drawing = false; updateFirmaInput(); });
    canvas.addEventListener('touchstart', e => { 
        e.preventDefault(); 
        drawing = true; 
        const t = e.touches[0]; 
        const r = canvas.getBoundingClientRect(); 
        ctx.beginPath(); 
        ctx.moveTo(t.clientX - r.left, t.clientY - r.top); 
    });
    canvas.addEventListener('touchmove', e => { 
        e.preventDefault(); 
        if (drawing) { 
            const t = e.touches[0]; 
            const r = canvas.getBoundingClientRect(); 
            ctx.lineTo(t.clientX - r.left, t.clientY - r.top); 
            ctx.stroke(); 
        } 
    });
    canvas.addEventListener('touchend', () => { drawing = false; updateFirmaInput(); });

    function drawImageToCanvas(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const rect = canvas.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                ctx.clearRect(0, 0, rect.width, rect.height);
                const scale = Math.min(rect.width / img.width, rect.height / img.height, 1);
                const x = (rect.width - img.width * scale) / 2;
                const y = (rect.height - img.height * scale) / 2;
                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                initializeContext();
                updateFirmaInput();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    if (signatureUpload) {
        signatureUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) drawImageToCanvas(this.files[0]);
        });
    }

    function clearSignature() {
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        initializeContext();
        if (firmaInput) firmaInput.value = '';
        if (signatureUpload) signatureUpload.value = '';
    }
    window.clearSignature = clearSignature;

    // Manejo de carga de evidencias
    const uploadCards = document.querySelectorAll('.upload-card');
    uploadCards.forEach(card => {
        const fileInput = card.querySelector('input[type="file"]');
        if (!fileInput) return;

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    const isVideo = file.type.startsWith('video/');
                    const preview = document.createElement('div');
                    preview.style.cssText = 'position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:var(--primary-soft); border-radius:12px; overflow:hidden;';
                    
                    if (isVideo) {
                        const video = document.createElement('video');
                        video.src = e.target.result;
                        video.style.cssText = 'max-width:100%; max-height:100%; object-fit:contain;';
                        preview.appendChild(video);
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'max-width:100%; max-height:100%; object-fit:contain;';
                        preview.appendChild(img);
                    }

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.style.cssText = 'position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.6); color:#fff; border:none; border-radius:6px; width:28px; height:28px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; padding:0;';
                    removeBtn.innerHTML = '×';
                    removeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        fileInput.value = '';
                        card.innerHTML = card.innerHTML.replace(preview.outerHTML, '');
                        card.style.cssText = '';
                    });
                    preview.appendChild(removeBtn);

                    card.innerHTML = '';
                    card.appendChild(preview);
                    card.style.cssText = 'border:none; padding:0; background:transparent; cursor:default;';
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endpush

@push('scripts')
<style>
    .combobox { position: relative; display: flex; align-items: center; }
    .combobox input { padding-right: 38px; }
    .combobox-arrow {
        position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        background: transparent; border: none; color: var(--muted); cursor: pointer;
        padding: 4px; display: flex; align-items: center; justify-content: center;
    }
    .combobox-list {
        position: absolute; top: calc(100% + 6px); left: 0; right: 0;
        max-height: 220px; overflow-y: auto; background: var(--surface);
        border: 1px solid var(--border); border-radius: 9px; box-shadow: var(--shadow);
        z-index: 100; list-style: none; margin: 0; padding: 6px 0; display: none;
    }
    .combobox-list.open { display: block; }
    .combobox-list li {
        padding: 10px 14px; cursor: pointer; color: var(--text); font-size: 14px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .combobox-list li:hover,
    .combobox-list li.active { background: var(--primary-soft); color: var(--primary); }
    .combobox-list .no-results { color: var(--muted); cursor: default; text-align: center; font-size: 13px; }
</style>
<script>
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
                    li.textContent = term
                        ? '"' + filter.trim() + '" se registrara como nuevo al guardar'
                        : 'Sin opciones registradas, escribe una nueva';
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

            if (!value) {
                subtypeCb.setOptions([]);
                return;
            }

            fetch('{{ route('gestion.servicios.historial.nueva_orden.subtipos') }}?equipment_type_name=' + encodeURIComponent(value))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    var names = Array.isArray(data) ? data.map(function (i) { return i.name; }) : [];
                    subtypeCb.setOptions([...new Set(names)]);
                })
                .catch(function () {
                    subtypeCb.setOptions([]);
                });
        }

        function loadModels() {
            var value = brandInput.value.trim();

            if (!value) {
                modelCb.setOptions([]);
                return;
            }

            fetch('{{ route('gestion.servicios.historial.nueva_orden.modelos') }}?brand_name=' + encodeURIComponent(value))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    var names = Array.isArray(data) ? data.map(function (i) { return i.name; }) : [];
                    modelCb.setOptions([...new Set(names)]);
                })
                .catch(function () {
                    modelCb.setOptions([]);
                });
        }

        typeCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#tipo_equipo_list option')).map(function (o) { return o.value; }));
        brandCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#marca_list option')).map(function (o) { return o.value; }));

        typeInput.addEventListener('input', debounce(loadSubtypes, 250));
        brandInput.addEventListener('input', debounce(loadModels, 250));
    })();
</script>
@endpush
