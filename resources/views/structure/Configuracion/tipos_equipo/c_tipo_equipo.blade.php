@extends('structure.Configuracion.layout')

@section('title', 'Nuevo equipo')
@section('page-title', 'Nuevo equipo')

@section('configuracion_content')
    <div class="catalog-card">
        @if (session('status'))
            <div class="alert alert--ok" style="margin:0 0 18px;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('configuracion.tipos_equipo.store') }}" id="equipment-form" autocomplete="off">
            @csrf

            <div class="form-section" style="margin-bottom:18px;">
                <h3 class="form-section-title">Información básica</h3>
                <p class="section-desc" style="margin:0 0 16px; font-size:13px;">Completa los datos principales del equipo. Los campos marcados con * son obligatorios.</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="equipment_type_name">Tipo de equipo *</label>
                        <input id="equipment_type_name"
                               list="equipment_type_list"
                               name="equipment_type_name"
                               class="form-input"
                               value="{{ old('equipment_type_name') }}"
                               placeholder="Ingrese el tipo de equipo"
                               required>
                        <datalist id="equipment_type_list">
                            @foreach ($equipmentTypes as $type)
                                <option value="{{ $type->name }}"></option>
                            @endforeach
                        </datalist>
                        <p class="form-hint">Ej. Equipo médico, Equipo de laboratorio, Mobiliario clínico, etc.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subtype_name">Subtipo *</label>
                        <input id="subtype_name"
                               list="subtype_list"
                               name="subtype_name"
                               class="form-input"
                               value="{{ old('subtype_name') }}"
                               placeholder="Ingrese el subtipo de equipo"
                               required
                               disabled>
                        <datalist id="subtype_list"></datalist>
                        <p class="form-hint">Ej. Monitor de signos vitales, Microscopía, Ultrasonido, etc.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="brand_name">Marca *</label>
                        <input id="brand_name"
                               list="brand_list"
                               name="brand_name"
                               class="form-input"
                               value="{{ old('brand_name') }}"
                               placeholder="Ingrese la marca del equipo"
                               required>
                        <datalist id="brand_list">
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->name }}"></option>
                            @endforeach
                        </datalist>
                        <p class="form-hint">Ej. Philips, Mindray, Samsung, Leica, etc.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="equipment_model_name">Modelo *</label>
                        <input id="equipment_model_name"
                               list="model_list"
                               name="equipment_model_name"
                               class="form-input"
                               value="{{ old('equipment_model_name') }}"
                               placeholder="Ingrese el modelo del equipo"
                               required
                               disabled>
                        <datalist id="model_list"></datalist>
                        <p class="form-hint">Ej. IntelliVue MX450, BeneVision N17, CX23, etc.</p>
                    </div>
                </div>
            </div>

            <div class="form-section" style="margin-bottom:18px;">
                <button type="button" id="desc-toggle" class="form-section-title" aria-expanded="false">
                    <span>Descripción (opcional)</span>
                    <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9" /></svg>
                </button>

                <div class="desc-panel" id="desc-panel">
                    <p class="section-desc" style="margin:0 0 16px; font-size:13px;">Agrega información adicional que ayude a identificar mejor el equipo.</p>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="type_description">Descripción del tipo (opcional)</label>
                            <textarea id="type_description" name="type_description" class="form-input description-field" rows="3" maxlength="120" placeholder="Agrega una descripción breve del tipo de equipo...">{{ old('type_description') }}</textarea>
                            <span class="char-count">0 / 120</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="subtype_description">Descripción del subtipo (opcional)</label>
                            <textarea id="subtype_description" name="subtype_description" class="form-input description-field" rows="3" maxlength="120" placeholder="Agrega una descripción breve del subtipo...">{{ old('subtype_description') }}</textarea>
                            <span class="char-count">0 / 120</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="brand_description">Descripción de la marca (opcional)</label>
                            <textarea id="brand_description" name="brand_description" class="form-input description-field" rows="3" maxlength="120" placeholder="Agrega una descripción breve de la marca...">{{ old('brand_description') }}</textarea>
                            <span class="char-count">0 / 120</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="model_description">Descripción del modelo (opcional)</label>
                            <textarea id="model_description" name="model_description" class="form-input description-field" rows="3" maxlength="120" placeholder="Agrega una descripción breve del modelo...">{{ old('model_description') }}</textarea>
                            <span class="char-count">0 / 120</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pie de acciones del formulario --}}
            <div class="page-foot">
                <a href="{{ route('configuracion.tipos_equipo.index') }}" class="btn btn--ghost">Cancelar</a>
                <button type="submit" class="btn">Guardar</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <style>
        .form-section {
            background: linear-gradient(145deg, rgba(8,18,40,0.88), rgba(4,12,30,0.88));
            border: 1px solid rgba(0,168,255,0.55);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(0,168,255,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
        }
        .form-section-title {
            margin: 0 0 8px;
            font-size: 17px;
            color: #00A8FF;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-group { display: flex; flex-direction: column; }
        .form-label {
            margin: 0 0 8px;
            font-size: 14px;
            color: #fff;
            font-weight: 600;
        }
        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(0,168,255,0.55);
            border-radius: 10px;
            font-size: 15px;
            background: rgba(4,10,24,0.72);
            color: #fff;
            outline: none;
            font-family: inherit;
            transition: border-color .18s ease, box-shadow .18s ease, transform .12s ease;
        }
        .form-input:focus {
            border-color: #00A8FF;
            box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45);
            transform: translateY(-1px);
        }
        .form-input::placeholder { color: rgba(255,255,255,0.4); }
        .form-input:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .form-hint {
            margin: 8px 0 0;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        .description-field { min-height: 80px; resize: vertical; }
        .char-count {
            text-align: right;
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-top: 6px;
        }
        /* Los estilos de .btn vienen del layout unificado */
        .modal-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: 1px solid rgba(0,168,255,0.55);
            border-radius: 12px;
            background: rgba(8,18,40,0.45);
            color: #00A8FF;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .16s ease, border-color .16s ease;
        }
        .modal-back:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
        :root[data-theme="light"] .form-section { background: linear-gradient(145deg, rgba(15,23,42,0.04), rgba(15,23,42,0.08)); border-color: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .form-input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.18); }
        :root[data-theme="light"] .form-input::placeholder { color: var(--muted); }
        :root[data-theme="light"] .form-input:disabled { color: var(--muted); }
        :root[data-theme="light"] .form-label { color: var(--text); }
        :root[data-theme="light"] .form-hint { color: var(--muted); }
        :root[data-theme="light"] .char-count { color: var(--muted); }
        .combobox { position: relative; display: flex; align-items: center; }
        .combobox .form-input { padding-right: 38px; }
        .combobox-arrow {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color .18s ease;
        }
        .combobox-arrow:hover { color: #00A8FF; }
        .combobox-arrow svg { pointer-events: none; }
        .combobox-list {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            max-height: 220px;
            overflow-y: auto;
            background: rgba(4,10,24,0.96);
            border: 1px solid rgba(0,168,255,0.55);
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.4);
            z-index: 100;
            list-style: none;
            margin: 0;
            padding: 6px 0;
            display: none;
        }
        .combobox-list.open { display: block; }
        .combobox-list li {
            padding: 10px 14px;
            cursor: pointer;
            color: #fff;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background .14s ease, color .14s ease;
        }
        .combobox-list li:hover,
        .combobox-list li.active {
            background: rgba(0,168,255,0.18);
            color: #00A8FF;
        }
        .combobox-list .no-results {
            color: rgba(255,255,255,0.45);
            cursor: default;
            text-align: center;
            font-size: 13px;
        }
        :root[data-theme="light"] .combobox-list { background: #fff; border-color: rgba(15,23,42,0.18); }
        :root[data-theme="light"] .combobox-list li { color: var(--text); }
        :root[data-theme="light"] .combobox-list li:hover,
        :root[data-theme="light"] .combobox-list li.active { background: rgba(0,168,255,0.12); color: #00A8FF; }
        :root[data-theme="light"] .combobox-list .no-results { color: var(--muted); }
        :root[data-theme="light"] .combobox-arrow { color: var(--muted); }

        /* ===== Clases auxiliares del tema claro ===== */
        .page-title { color: #fff; }
        :root[data-theme="light"] .page-title { color: var(--text); }
        .page-desc { color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .page-desc { color: var(--muted); }
        .back-link {
            background: rgba(8,18,40,0.45);
            border: 1px solid rgba(80,130,220,0.22);
            color: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .back-link:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
        :root[data-theme="light"] .back-link {
            background: rgba(15,23,42,0.06);
            border: 1px solid rgba(15,23,42,0.14);
            color: var(--text);
        }
        :root[data-theme="light"] .back-link:hover { background: rgba(0,122,255,0.1); border-color: var(--primary); }
        .section-desc { color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .section-desc { color: var(--muted); }

        :root[data-theme="light"] .modal-back {
            background: rgba(15,23,42,0.04);
            border: 1px solid rgba(15,23,42,0.18);
            color: var(--text);
        }
        :root[data-theme="light"] .modal-back:hover { background: rgba(0,122,255,0.1); border-color: var(--primary); color: var(--primary); }

        /* ===== Panel desplegable de descripciones ===== */
        .desc-panel { display: none; }
        .desc-panel.open { display: block; }
        #desc-toggle {
            width: 100%;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
            text-align: left;
            color: #00A8FF;
        }
        #desc-toggle .chevron { margin-left: auto; transition: transform .2s ease; }
        #desc-toggle[aria-expanded="true"] .chevron { transform: rotate(180deg); }
        :root[data-theme="light"] #desc-toggle { color: var(--primary); }
    </style>
    <script>
        (function () {
            var typeInput = document.getElementById('equipment_type_name');
            var subtypeInput = document.getElementById('subtype_name');
            var brandInput = document.getElementById('brand_name');
            var modelInput = document.getElementById('equipment_model_name');

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
                var cb = input === subtypeInput ? subtypeCb : modelCb;
                input.disabled = !enabled;
                if (!enabled) {
                    input.value = '';
                    cb.setOptions([]);
                }
            }

            function loadSubtypes() {
                var value = typeInput.value.trim();
                setEnabled(subtypeInput, false);

                if (!value) return;

                fetch('{{ route('configuracion.tipos_equipo.subtypes') }}?equipment_type_name=' + encodeURIComponent(value))
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        subtypeCb.setOptions(data.map(function (i) { return i.name; }));
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
                        modelCb.setOptions(data.map(function (i) { return i.name; }));
                        setEnabled(modelInput, true);
                    })
                    .catch(function () {
                        setEnabled(modelInput, false);
                    });
            }

            // Inicializar listas estáticas
            typeCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#equipment_type_list option')).map(function (o) { return o.value; }));
            brandCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#brand_list option')).map(function (o) { return o.value; }));

            typeInput.addEventListener('input', debounce(loadSubtypes, 250));
            brandInput.addEventListener('input', debounce(loadModels, 250));

            // Restaurar old() cuando el tipo/marca ya tienen valor
            if (typeInput.value.trim()) {
                loadSubtypes();
            }
            if (brandInput.value.trim()) {
                loadModels();
            }

            // Toggle del panel de descripciones opcionales
            var descToggle = document.getElementById('desc-toggle');
            var descPanel = document.getElementById('desc-panel');
            if (descToggle && descPanel) {
                descToggle.addEventListener('click', function () {
                    var open = descPanel.classList.toggle('open');
                    descToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            }

            document.querySelectorAll('.description-field').forEach(function (textarea) {
                var counter = textarea.parentElement.querySelector('.char-count');
                function update() {
                    counter.textContent = (textarea.value.length || 0) + ' / ' + textarea.maxLength;
                }
                textarea.addEventListener('input', update);
                update();
            });
        })();
    </script>
    @endpush
@endsection
