@extends('layouts.dashboard')

@section('title', 'Nuevo equipo')
@section('page-title', 'Nuevo equipo')

@section('content')
<form method="POST" action="{{ route('inventory.equipos.store') }}" enctype="multipart/form-data" autocomplete="off" style="max-width:900px; margin:0 auto;">
    @csrf

        <x-ui.card>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <x-ui.form-group label="Equipo / Tipo *" name="tipo" placeholder="Ej. LAPAROSCOPIA" :required="true" />
                <x-ui.form-group label="Modelo" name="modelo" placeholder="Ej. AIM 1588" />
                <x-ui.form-group label="Marca" name="marca" placeholder="Ej. STRYKER" />
                <x-ui.form-group label="Precio *" name="precio" type="text" inputmode="decimal" placeholder="0.00" :required="true" />
                <x-ui.form-group label="SKU / Clave" name="sku" placeholder="Opcional" />
                <x-ui.form-group for="imagen" label="Imagen">
                    <input id="imagen" type="file" name="imagen" accept="image/*"
                           style="width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);" />
                </x-ui.form-group>
            </div>
            <x-ui.form-group for="descripcion" label="Descripción">
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del equipo">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>
            <label class="ui-switch-row" style="display:flex; align-items:center; gap:10px; margin-top:14px;">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" checked>
                <span>Activo (disponible para cotizar)</span>
            </label>
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
