@extends('structure.gestion_servicios.layout')

@section('title', 'Editar servicio interno')

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
            text-decoration: none; cursor: pointer; transition: all .16s ease; border: none;
        }
        .ns-btn svg { width: 16px; height: 16px; }
        .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
        :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-btn--primary { background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
        .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }

        .ns-section { margin-bottom: 22px; }
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

        .ns-table-header {
            display: grid; grid-template-columns: 1.8fr 100px 130px 130px 44px; gap: 10px;
            padding: 10px 0; font-size: 12px; font-weight: 700;
            color: rgba(255,255,255,0.6); border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-table-header { color: var(--muted); border-color: rgba(15,23,42,0.08); }
        .ns-table-row {
            display: grid; grid-template-columns: 1.8fr 100px 130px 130px 44px; gap: 10px;
            align-items: center; padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .ns-table-row input {
            width: 100%; padding: 10px 12px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
        }
        :root[data-theme="light"] .ns-table-row input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-table-row input::placeholder { color: rgba(255,255,255,0.4); }
        :root[data-theme="light"] .ns-table-row input::placeholder { color: var(--muted); }
        .ns-table-row input[type="number"] { text-align: right; }
        .refaccion-cell { display: flex; align-items: center; gap: 10px; }
        .ref-preview-img {
            width: 44px; height: 44px; object-fit: cover; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55);
            flex-shrink: 0;
        }
        :root[data-theme="light"] .ref-preview-img { border-color: rgba(15,23,42,0.14); }
        .refaccion-cell-info { flex: 1; min-width: 0; }
        .refaccion-select {
            width: 100%; padding: 10px 12px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
            margin-bottom: 6px; cursor: pointer;
        }
        :root[data-theme="light"] .refaccion-select { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ref-concepto { margin-bottom: 4px; }
        .ref-subtipo {
            font-size: 12px; color: rgba(255,255,255,0.55); min-height: 16px;
        }
        :root[data-theme="light"] .ref-subtipo { color: var(--muted); }
        .ns-row-total {
            text-align: right; font-size: 14px; font-weight: 700; color: #fff;
        }
        :root[data-theme="light"] .ns-row-total { color: var(--text); }
        .ns-remove-btn {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            border: none; background: rgba(239,68,68,0.12); color: #EF4444; cursor: pointer;
        }
        .ns-remove-btn svg { width: 16px; height: 16px; }

        .ns-add-btn {
            margin-top: 14px; padding: 10px 16px;
            border: 1px dashed rgba(0,122,255,0.5); border-radius: 10px;
            background: transparent; color: #007AFF; font-size: 13px; font-weight: 700;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        }

        .ns-totals {
            display: flex; flex-direction: column; gap: 10px;
            margin-top: 24px; padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-totals { border-color: rgba(15,23,42,0.08); }
        .ns-totals-row {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 14px; color: rgba(255,255,255,0.7);
        }
        :root[data-theme="light"] .ns-totals-row { color: var(--text); }
        .ns-totals-row input {
            width: 120px; padding: 8px 12px; border-radius: 10px; text-align: right;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
        }
        :root[data-theme="light"] .ns-totals-row input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-totals-row.total {
            font-size: 18px; font-weight: 800; color: #fff;
            padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);
        }
        :root[data-theme="light"] .ns-totals-row.total { color: var(--text); border-color: rgba(15,23,42,0.1); }
        .ns-totals-row .ns-total-value { color: #007AFF; font-size: 20px; }

        .ns-iva-check {
            display: flex; align-items: center; gap: 8px; cursor: pointer;
            font-size: 14px; color: rgba(255,255,255,0.75);
        }
        :root[data-theme="light"] .ns-iva-check { color: var(--text); }
        .ns-iva-check input { width: 18px; height: 18px; accent-color: #007AFF; cursor: pointer; }
    </style>

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                    <h2>Editar servicio interno</h2>
                    <p>Modifica la informacion del servicio {{ $service->service_number }}</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
                <button type="submit" form="editIntForm" class="ns-btn ns-btn--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Guardar cambios
                </button>
            </div>
        </div>

        <form id="editIntForm" method="POST" action="{{ route('gestion.servicios.update.interno', $service) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="catalog-card service-section ns-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Equipo
                </div>
                <p class="ns-section-subtitle">Actualiza los datos del equipo registrado</p>

                <div class="ns-form-grid">
                    <div class="ns-field">
                        <label>Tipo de equipo</label>
                        <input type="text" name="tipo_equipo" value="{{ old('tipo_equipo', $service->serviceEquipment?->type_text ?? '') }}" placeholder="Ej. Equipo medico">
                    </div>
                    <div class="ns-field">
                        <label>Subtipo</label>
                        <input type="text" name="subtipo" value="{{ old('subtipo', $service->serviceEquipment?->subtype_text ?? '') }}" placeholder="Ej. Monitor de signos vitales">
                    </div>
                    <div class="ns-field">
                        <label>Marca</label>
                        <input type="text" name="marca" value="{{ old('marca', $service->serviceEquipment?->brand_text ?? '') }}" placeholder="Ej. Olympus">
                    </div>
                    <div class="ns-field">
                        <label>Modelo</label>
                        <input type="text" name="modelo" value="{{ old('modelo', $service->serviceEquipment?->model_text ?? '') }}" placeholder="Ej. C-90">
                    </div>
                    <div class="ns-field">
                        <label>Numero de serie</label>
                        <input type="text" name="serie" value="{{ old('serie', $service->serviceEquipment?->serial_number ?? '') }}" placeholder="Ej. SN-893-832">
                    </div>
                </div>

                <div class="ns-field" style="margin-bottom: 16px;">
                    <label>Descripcion del equipo</label>
                    <textarea name="descripcion_equipo" placeholder="Describe el equipo y su funcion">{{ old('descripcion_equipo', $service->serviceEquipment?->description ?? '') }}</textarea>
                </div>
                <div class="ns-field" style="margin-bottom: 6px;">
                    <label>Observaciones</label>
                    <textarea name="observaciones" placeholder="Observaciones adicionales del equipo">{{ old('observaciones', $service->serviceEquipment?->observations ?? '') }}</textarea>
                </div>
            </div>

            <div class="catalog-card service-section ns-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Tecnico interno
                </div>
                <p class="ns-section-subtitle">Selecciona el tecnico asignado al servicio</p>

                <div class="ns-form-grid" style="grid-template-columns: 1fr;">
                    <div class="ns-field">
                        <label>Tecnico</label>
                        <select name="technician_id" required>
                            @foreach ($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ old('technician_id', $service->internal_technician_id) == $tech->id ? 'selected' : '' }}>
                                    {{ trim($tech->name) }} — {{ $tech->phone ?: 'Sin telefono' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="catalog-card service-section ns-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Cotizacion
                </div>
                <p class="ns-section-subtitle">Edita las refacciones y costos del servicio</p>

                <div class="ns-table-header">
                    <span>Concepto / Refaccion</span>
                    <span style="text-align: right;">Cantidad</span>
                    <span style="text-align: right;">Precio unit.</span>
                    <span style="text-align: right;">Total</span>
                    <span></span>
                </div>

                <div id="refaccionesRows">
                    @php
                        $currentRefacciones = old('refacciones') ?? ($service->maintenance?->refacciones ?? []);
                        if (empty($currentRefacciones)) {
                            $currentRefacciones = [[]];
                        }
                    @endphp
                    @foreach ($currentRefacciones as $index => $ref)
                        @php
                            $refId = $ref['refaccion_id'] ?? '';
                            $concepto = $ref['concepto'] ?? '';
                            $cantidad = $ref['cantidad'] ?? 1;
                            $precio = $ref['precio'] ?? '';
                        @endphp
                        <div class="ns-table-row" data-index="{{ $index }}">
                            <div class="refaccion-cell">
                                <img class="ref-preview-img" src="" alt="" style="display:none;">
                                <div class="refaccion-cell-info">
                                    <select name="refacciones[{{ $index }}][refaccion_id]" class="refaccion-select" onchange="seleccionarRefaccion(this)">
                                        <option value="">Selecciona refaccion</option>
                                        @foreach($refacciones as $r)
                                            <option value="{{ $r->id }}" {{ $refId == $r->id ? 'selected' : '' }} data-nombre="{{ $r->name }}" data-precio="{{ $r->price }}" data-subtipo="{{ $r->subtype }}" data-foto="{{ $r->photo ? asset('storage/'.$r->photo) : '' }}">
                                                {{ $r->name }} — {{ $r->subtype }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="refacciones[{{ $index }}][concepto]" class="ref-concepto" placeholder="Ej. Empaque de sellado" value="{{ $concepto }}" required>
                                    <div class="ref-subtipo"></div>
                                </div>
                            </div>
                            <input type="number" name="refacciones[{{ $index }}][cantidad]" min="0" step="1" value="{{ $cantidad }}" onchange="calcular()" oninput="calcular()">
                            <input type="number" name="refacciones[{{ $index }}][precio]" min="0" step="0.01" placeholder="0.00" value="{{ $precio }}" onchange="calcular()" oninput="calcular()">
                            <div class="ns-row-total">0.00</div>
                            <button type="button" class="ns-remove-btn" onclick="removeRow({{ $index }})" @if (count($currentRefacciones) === 1) disabled @endif>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="ns-add-btn" onclick="addRow()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Agregar refaccion
                </button>

                <div class="ns-totals">
                    <div class="ns-totals-row">
                        <span>Subtotal</span>
                        <strong id="subtotalDisplay">$ 0.00</strong>
                    </div>
                    <div class="ns-totals-row" style="align-items: center;">
                        <span>Costo de envio</span>
                        <input type="number" name="costo_envio" id="costoEnvio" min="0" step="0.01" value="{{ old('costo_envio', $service->maintenance?->envio ?? 0) }}" oninput="calcular()">
                    </div>
                    <div class="ns-totals-row" style="align-items: center;">
                        <span>Descuento</span>
                        <input type="number" name="descuento" id="descuento" min="0" step="0.01" value="{{ old('descuento', $service->maintenance?->descuento ?? 0) }}" oninput="calcular()">
                    </div>
                    <div class="ns-totals-row" style="align-items: center;">
                        <label class="ns-iva-check">
                            <input type="checkbox" name="aplica_iva" id="aplicaIva" value="1" {{ old('aplica_iva', $service->maintenance?->requiere_iva ? '1' : '0') === '1' ? 'checked' : '' }} onchange="calcular()">
                            Aplicar IVA (16%)
                        </label>
                        <strong id="ivaDisplay">$ 0.00</strong>
                    </div>
                    <div class="ns-totals-row total">
                        <span>Total</span>
                        <span class="ns-total-value" id="totalDisplay">$ 0.00</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        let rowIndex = {{ count($currentRefacciones) }};

        function formatMoney(amount) {
            return '$ ' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function updateDisabled() {
            const rows = document.querySelectorAll('#refaccionesRows .ns-table-row');
            rows.forEach((row) => {
                const btn = row.querySelector('.ns-remove-btn');
                if (btn) btn.disabled = rows.length === 1;
            });
        }

        function addRow() {
            const container = document.getElementById('refaccionesRows');
            const first = container.querySelector('.ns-table-row');
            const clone = first.cloneNode(true);
            const idx = rowIndex++;

            clone.setAttribute('data-index', idx);
            clone.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/refacciones\[\d+\]/, `refacciones[${idx}]`));
                }
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else {
                    input.value = name && name.includes('cantidad') ? 1 : '';
                }
            });
            const previewImg = clone.querySelector('.ref-preview-img');
            if (previewImg) {
                previewImg.src = '';
                previewImg.style.display = 'none';
            }
            const subtipo = clone.querySelector('.ref-subtipo');
            if (subtipo) subtipo.textContent = '';

            clone.querySelector('.ns-remove-btn').setAttribute('onclick', `removeRow(${idx})`);
            clone.querySelector('.ns-remove-btn').disabled = false;
            clone.querySelector('.ns-row-total').textContent = '0.00';

            container.appendChild(clone);
            updateDisabled();
            calcular();
        }

        function seleccionarRefaccion(select) {
            const row = select.closest('.ns-table-row');
            const option = select.options[select.selectedIndex];
            const nombre = option.dataset.nombre || '';
            const precio = option.dataset.precio || '';
            const subtipo = option.dataset.subtipo || '';
            const foto = option.dataset.foto || '';

            const concepto = row.querySelector('.ref-concepto');
            const precioInput = row.querySelector('input[name*="[precio]"]');
            const subtipoDiv = row.querySelector('.ref-subtipo');
            const previewImg = row.querySelector('.ref-preview-img');

            if (concepto) concepto.value = nombre;
            if (precioInput) precioInput.value = precio;
            if (subtipoDiv) subtipoDiv.textContent = subtipo;
            if (previewImg) {
                if (foto) {
                    previewImg.src = foto;
                    previewImg.style.display = 'block';
                } else {
                    previewImg.src = '';
                    previewImg.style.display = 'none';
                }
            }

            calcular();
        }

        function removeRow(idx) {
            const row = document.querySelector(`#refaccionesRows .ns-table-row[data-index="${idx}"]`);
            if (!row) return;
            const container = document.getElementById('refaccionesRows');
            if (container.children.length > 1) {
                row.remove();
                updateDisabled();
                calcular();
            }
        }

        function calcular() {
            let subtotal = 0;
            document.querySelectorAll('#refaccionesRows .ns-table-row').forEach(row => {
                const cantidad = parseFloat(row.querySelector('input[name*="[cantidad]"]').value) || 0;
                const precio = parseFloat(row.querySelector('input[name*="[precio]"]').value) || 0;
                const total = cantidad * precio;
                row.querySelector('.ns-row-total').textContent = total.toFixed(2);
                subtotal += total;
            });

            const envio = parseFloat(document.getElementById('costoEnvio').value) || 0;
            const descuento = parseFloat(document.getElementById('descuento').value) || 0;
            const aplicaIva = document.getElementById('aplicaIva').checked;

            const base = Math.max(0, subtotal + envio - descuento);
            const iva = aplicaIva ? base * 0.16 : 0;
            const total = base + iva;

            document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
            document.getElementById('ivaDisplay').textContent = formatMoney(iva);
            document.getElementById('totalDisplay').textContent = formatMoney(total);
        }

        window.onload = calcular;
    </script>
@endsection
