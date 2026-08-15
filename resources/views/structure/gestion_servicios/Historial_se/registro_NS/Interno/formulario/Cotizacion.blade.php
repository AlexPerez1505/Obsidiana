@extends('structure.gestion_servicios.layout')

@section('title', 'Generar cotizacion')

@section('service_content')
    @include('structure.gestion_servicios.Historial_se.registro_ns.Interno.interno_estilos_base')
    <style>
        .ns-page { max-width: 1000px; }
    </style>

    @php
        $customerName = trim($customer->nombre . ' ' . ($customer->apellido ?? ''));
        $customerInitials = collect([$customer->nombre, $customer->apellido])->filter()->map(fn($n) => mb_substr($n, 0, 1))->implode('');
    @endphp

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div>
                    <h2>Generar cotizacion</h2>
                    <p>Agrega las refacciones y costos del servicio interno</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.nuevo.interno.tecnico') }}?customer_id={{ $customer->id }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
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
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Tecnico</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step active">
                <div class="ns-step-number">4</div>
                <span>Cotizacion</span>
            </div>
        </div>

        <div class="ns-summary">
            <div class="ns-summary-avatar">{{ $customerInitials ?: 'C' }}</div>
            <div class="ns-summary-info">
                <h4>{{ $customerName }}</h4>
                <p>{{ $customer->telefono ?: 'Sin teléfono' }}</p>
            </div>
        </div>

        <form id="cotizacionForm" method="POST" action="{{ route('gestion.servicios.nuevo.interno.guardar') }}">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="technician_id" value="{{ $technician->id }}">

            <div class="catalog-card service-section">
                <div class="ns-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Refacciones
                </div>
                <p class="ns-section-subtitle">Agrega las refacciones que se consideran para el servicio</p>

                <div class="ns-table-header">
                    <span>Concepto / Refaccion</span>
                    <span style="text-align: right;">Cantidad</span>
                    <span style="text-align: right;">Precio unit.</span>
                    <span style="text-align: right;">Total</span>
                    <span></span>
                </div>

                <div id="refaccionesRows">
                    <div class="ns-table-row" data-index="0">
                        <div class="refaccion-cell">
                            <img class="ref-preview-img" src="" alt="" style="display:none;">
                            <div class="refaccion-cell-info">
                                <select name="refacciones[0][refaccion_id]" class="refaccion-select" onchange="seleccionarRefaccion(this)">
                                    <option value="">Selecciona refacción</option>
                                    @foreach($refacciones as $ref)
                                        <option value="{{ $ref->id }}" data-nombre="{{ $ref->name }}" data-precio="{{ $ref->price }}" data-subtipo="{{ $ref->subtype }}" data-foto="{{ $ref->photo ? asset('storage/'.$ref->photo) : '' }}">
                                            {{ $ref->name }} — {{ $ref->subtype }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="text" name="refacciones[0][concepto]" class="ref-concepto" placeholder="Ej. Empaque de sellado" required>
                                <div class="ref-subtipo"></div>
                            </div>
                        </div>
                        <input type="number" name="refacciones[0][cantidad]" min="0" step="1" value="1" onchange="calcular()" oninput="calcular()">
                        <input type="number" name="refacciones[0][precio]" min="0" step="0.01" placeholder="0.00" onchange="calcular()" oninput="calcular()">
                        <div class="ns-row-total">0.00</div>
                        <button type="button" class="ns-remove-btn" onclick="removeRow(0)" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
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
                        <input type="number" name="costo_envio" id="costoEnvio" min="0" step="0.01" value="0" oninput="calcular()">
                    </div>
                    <div class="ns-totals-row" style="align-items: center;">
                        <span>Descuento</span>
                        <input type="number" name="descuento" id="descuento" min="0" step="0.01" value="0" oninput="calcular()">
                    </div>
                    <div class="ns-totals-row" style="align-items: center;">
                        <label class="ns-iva-check">
                            <input type="checkbox" name="aplica_iva" id="aplicaIva" value="1" checked onchange="calcular()">
                            Aplicar IVA (16%)
                        </label>
                        <strong id="ivaDisplay">$ 0.00</strong>
                    </div>
                    <div class="ns-totals-row total">
                        <span>Total</span>
                        <span class="ns-total-value" id="totalDisplay">$ 0.00</span>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="ns-btn ns-btn--primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Guardar servicio
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let rowIndex = 1;

        function formatMoney(amount) {
            return '$ ' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function updateDisabled() {
            const rows = document.querySelectorAll('#refaccionesRows .ns-table-row');
            rows.forEach((row, i) => {
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
