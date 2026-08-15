@extends('structure.gestion_servicios.layout')

@section('title', 'Generar Orden de Servicio')

@section('service_content')
    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $equipment = $service->serviceEquipment;
        $maintenance = $service->maintenance ?? new \App\Models\ServiceMaintenance(['service_id' => $service->id]);
        $checklist = $maintenance->checklist ?? [];
        $partidas = old('partidas_remision', $maintenance->partidas_remision ?? []);
        if (empty($partidas)) {
            $partidas = [
                ['item' => 'Partida 1', 'descripcion' => 'Mantenimiento preventivo general', 'unidad' => 'SERVICIO', 'cantidad' => 1, 'precio_unitario' => 0],
                ['item' => 'Partida 2', 'descripcion' => 'Limpieza, inspección, ajuste y pruebas', 'unidad' => 'SERVICIO', 'cantidad' => 1, 'precio_unitario' => 0],
            ];
        }
        $defaultDescription = 'MANTENIMIENTO ' . strtoupper($maintenance->tipo_reparacion ?? 'PREVENTIVO') . ' A ' . strtoupper($equipment->type_text ?? 'EQUIPO') . ' ' . strtoupper($equipment->brand_text ?? '') . ' ' . strtoupper($equipment->model_text ?? '') . ' CON NÚMERO DE SERIE ' . ($equipment->serial_number ?? 'N/A') . '. ' . ($maintenance->descripcion ?? '') . ' ' . ($maintenance->fallas_encontradas ?? '');
        $techName = $service->externalTechnician
            ? trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''))
            : trim(($service->internalTechnician->name ?? '') . ' ' . ($service->internalTechnician->last_name ?? ''));
        $startDate = $service->started_at?->format('Y-m-d') ?? $service->created_at?->format('Y-m-d');
        $endDate = $maintenance->proximo_mantenimiento?->format('Y-m-d') ?? now()->addMonths(3)->format('Y-m-d');
        $proxMonths = $startDate && $endDate ? ceil(now()->parse($startDate)->diffInMonths(now()->parse($endDate))) : 3;
    @endphp

    <style>
        .os-wrapper { max-width: 900px; margin: 0 auto; padding: 24px; }
        .os-card {
            background: #fff; border-radius: 16px; padding: 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06); margin-bottom: 20px;
        }
        :root[data-theme="dark"] .os-card { background: #0b1221; box-shadow: 0 4px 24px rgba(0,0,0,0.25); }
        .os-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .os-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
        .os-badge { background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        :root[data-theme="dark"] .os-badge { background: rgba(14,165,233,0.16); color: #38bdf8; }
        .review-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .review-row { display: flex; justify-content: space-between; font-size: 14px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        :root[data-theme="dark"] .review-row { border-bottom-color: rgba(255,255,255,0.08); }
        .review-label { color: #64748b; text-transform: uppercase; font-size: 11px; font-weight: 700; }
        :root[data-theme="dark"] .review-label { color: #94a3b8; }
        .review-value { font-weight: 600; color: #0f172a; text-align: right; }
        :root[data-theme="dark"] .review-value { color: #f8fafc; }
        .os-section-title { font-size: 16px; font-weight: 700; margin: 0 0 14px; }
        .checklist-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .checklist-table th { text-align: left; padding: 10px; color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        :root[data-theme="dark"] .checklist-table th { border-bottom-color: rgba(255,255,255,0.1); color: #94a3b8; }
        .checklist-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        :root[data-theme="dark"] .checklist-table td { border-bottom-color: rgba(255,255,255,0.06); }
        .status-pill { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .status-done { background: #dcfce7; color: #166534; }
        :root[data-theme="dark"] .status-done { background: rgba(34,197,94,0.16); color: #4ade80; }
        .status-pending { background: #fee2e2; color: #991b1b; }
        :root[data-theme="dark"] .status-pending { background: rgba(239,68,68,0.16); color: #f87171; }
        .chip { display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 20px; background: #f1f5f9; color: #334155; font-size: 13px; margin: 0 6px 6px 0; }
        :root[data-theme="dark"] .chip { background: rgba(255,255,255,0.08); color: #e2e8f0; }
        .partida-card { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 14px; }
        :root[data-theme="dark"] .partida-card { background: rgba(255,255,255,0.04); }
        .partida-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .partida-title { font-weight: 700; font-size: 15px; }
        .btn-remove { color: #dc2626; background: transparent; border: 1px solid #fecaca; padding: 5px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; }
        :root[data-theme="dark"] .btn-remove { border-color: rgba(239,68,68,0.4); color: #f87171; }
        .partida-grid { display: grid; grid-template-columns: 1fr 2fr 1fr 0.7fr 1fr; gap: 10px; }
        .partida-grid .field { display: flex; flex-direction: column; }
        .partida-grid label { font-size: 11px; color: #64748b; margin-bottom: 4px; text-transform: uppercase; font-weight: 700; }
        :root[data-theme="dark"] .partida-grid label { color: #94a3b8; }
        .partida-grid input { padding: 9px 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #fff; }
        :root[data-theme="dark"] .partida-grid input { background: #0f172a; border-color: rgba(255,255,255,0.12); color: #f8fafc; }
        .partida-import { text-align: right; font-weight: 700; margin-top: 10px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .totals-row.total { font-weight: 800; font-size: 17px; }
        .totals-row.anticipo { color: #dc2626; }
        .totals-row.pagar { font-weight: 800; font-size: 18px; }
        .btn-add { background: transparent; border: 1px dashed #94a3b8; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; margin-right: 8px; }
        .btn-regenerate { background: transparent; border: 1px solid #e2e8f0; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #fff; }
        :root[data-theme="dark"] .form-input { background: #0f172a; border-color: rgba(255,255,255,0.12); color: #f8fafc; }
        .iva-row { display: flex; align-items: center; gap: 8px; font-size: 14px; margin: 12px 0; }
        .actions-footer { display: flex; gap: 12px; margin-top: 20px; }
        .btn-primary { flex: 1; background: #2563eb; color: #fff; border: none; padding: 14px; border-radius: 12px; font-weight: 700; cursor: pointer; }
        .btn-secondary { background: #f1f5f9; color: #334155; border: none; padding: 14px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; }
        :root[data-theme="dark"] .btn-secondary { background: rgba(255,255,255,0.08); color: #e2e8f0; }
        textarea.form-input { min-height: 90px; resize: vertical; }
        @media (max-width: 640px) {
            .review-grid { grid-template-columns: 1fr; }
            .partida-grid { grid-template-columns: 1fr; }
        }
    </style>

    @if (session('success'))
        <div class="os-card" style="color: #16a34a; margin-bottom: 20px;">{{ session('success') }}</div>
    @endif

    <form id="osForm" action="{{ route('gestion.servicios.os.store', $service) }}" method="POST">
        @csrf

        <div class="os-wrapper">
            {{-- Revisión Final --}}
            <div class="os-card">
                <div class="os-header">
                    <h2>Revisión Final</h2>
                    <span class="os-badge">{{ strtoupper($customerName) }}</span>
                </div>
                <div class="review-grid">
                    <div class="review-row"><span class="review-label">Cliente</span><span class="review-value">{{ strtoupper($customerName) }}</span></div>
                    <div class="review-row"><span class="review-label">Equipo</span><span class="review-value">{{ $equipment->type_text ?? 'N/A' }}</span></div>
                    <div class="review-row"><span class="review-label">Fechas</span><span class="review-value">{{ $startDate }} / {{ $endDate }}</span></div>
                    <div class="review-row"><span class="review-label">Técnico</span><span class="review-value">{{ $techName ?: 'N/A' }}</span></div>
                    <div class="review-row"><span class="review-label">Identificación</span><span class="review-value">{{ $equipment->brand_text ?? '' }} | {{ $equipment->model_text ?? '' }} | SN: {{ $equipment->serial_number ?? 'N/A' }}</span></div>
                    <div class="review-row"><span class="review-label">Próx. Mto.</span><span class="review-value">{{ $proxMonths }} meses</span></div>
                </div>
            </div>

            {{-- Checklist --}}
            <div class="os-card">
                <h3 class="os-section-title">Checklist Procesado</h3>
                <table class="checklist-table">
                    <thead>
                        <tr><th>Item</th><th>Sección</th><th>Estatus</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($checklist as $item)
                            @php $done = is_array($item) ? ($item['done'] ?? false) : false; $text = is_array($item) ? ($item['text'] ?? '') : $item; @endphp
                            <tr>
                                <td>{{ $text }}</td>
                                <td>General</td>
                                <td><span class="status-pill {{ $done ? 'status-done' : 'status-pending' }}">{{ $done ? 'Realizado' : 'Pendiente' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="color:#94a3b8;">Sin checklist registrado</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Acciones realizadas --}}
            <div class="os-card">
                <h3 class="os-section-title">Acciones Realizadas</h3>
                <div>
                    @php
                        $acciones = [];
                        foreach ($checklist as $c) {
                            if (is_array($c) && ($c['done'] ?? false) && ($c['text'] ?? '')) {
                                $acciones[] = $c['text'];
                            }
                        }
                        if (empty($acciones) && $maintenance->descripcion) {
                            $acciones = array_filter(array_map('trim', preg_split('/[,.\s]+/', $maintenance->descripcion)));
                        }
                    @endphp
                    @forelse ($acciones as $accion)
                        <span class="chip">{{ $accion }}</span>
                    @empty
                        <span style="color:#94a3b8; font-size:14px;">Sin acciones registradas</span>
                    @endforelse
                </div>
            </div>

            {{-- Partidas para remisión --}}
            <div class="os-card">
                <h3 class="os-section-title">Partidas para remisión</h3>
                <p style="color:#94a3b8; font-size:13px; margin-top:-10px; margin-bottom:16px;">Configuración final de cobro.</p>

                <div id="partidasContainer">
                    @foreach ($partidas as $i => $partida)
                        <div class="partida-card" data-index="{{ $i }}">
                            <div class="partida-header">
                                <span class="partida-title">Partida {{ $i + 1 }}</span>
                                <button type="button" class="btn-remove" onclick="removePartida(this)">Eliminar</button>
                            </div>
                            <div class="partida-grid">
                                <div class="field">
                                    <label>Item</label>
                                    <input type="text" name="partidas_remision[{{ $i }}][item]" value="{{ $partida['item'] ?? '' }}" onchange="recalc()">
                                </div>
                                <div class="field">
                                    <label>Descripción</label>
                                    <input type="text" name="partidas_remision[{{ $i }}][descripcion]" value="{{ $partida['descripcion'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>Unidad</label>
                                    <input type="text" name="partidas_remision[{{ $i }}][unidad]" value="{{ $partida['unidad'] ?? 'SERVICIO' }}">
                                </div>
                                <div class="field">
                                    <label>Cantidad</label>
                                    <input type="number" name="partidas_remision[{{ $i }}][cantidad]" value="{{ $partida['cantidad'] ?? 1 }}" min="0" step="any" onchange="recalc()">
                                </div>
                                <div class="field">
                                    <label>Precio unitario</label>
                                    <input type="number" name="partidas_remision[{{ $i }}][precio_unitario]" value="{{ $partida['precio_unitario'] ?? 0 }}" min="0" step="any" onchange="recalc()">
                                </div>
                            </div>
                            <div class="partida-import">Importe $<span class="importe-val">0.00</span></div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-bottom: 20px;">
                    <button type="button" class="btn-add" onclick="addPartida()">+ Agregar</button>
                    <button type="button" class="btn-regenerate" onclick="regenerateDefaults()">Regenerar</button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label class="review-label">Envío</label>
                        <input type="number" name="envio" id="envio" value="{{ old('envio', $maintenance->envio ?? 0) }}" min="0" step="any" class="form-input" onchange="recalc()">
                    </div>
                    <div>
                        <label class="review-label">Anticipo</label>
                        <input type="number" name="anticipo" id="anticipo" value="{{ old('anticipo', $maintenance->anticipo ?? 0) }}" min="0" step="any" class="form-input" onchange="recalc()">
                    </div>
                </div>

                <label class="iva-row">
                    <input type="checkbox" name="requiere_iva" id="requiere_iva" value="1" {{ old('requiere_iva', $maintenance->requiere_iva ?? false) ? 'checked' : '' }} onchange="recalc()">
                    Requiere IVA (16%)
                </label>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <div class="totals-row"><span>Subtotal</span><span id="subtotal">$0.00</span></div>
                    <div class="totals-row"><span>IVA</span><span id="iva">$0.00</span></div>
                    <div class="totals-row total"><span>Total</span><span id="total">$0.00</span></div>
                    <div class="totals-row anticipipo"><span>Anticipo</span><span id="anticipoDisplay">$0.00</span></div>
                    <div class="totals-row pagar"><span>Pagar</span><span id="pagar">$0.00</span></div>
                </div>
            </div>

            {{-- Descripción general --}}
            <div class="os-card">
                <h3 class="os-section-title">Descripción general remisión</h3>
                <textarea name="descripcion_general" id="descripcion_general" class="form-input">{{ old('descripcion_general', $maintenance->descripcion_general ?? $defaultDescription) }}</textarea>
            </div>

            {{-- Footer actions --}}
            <div class="actions-footer">
                <button type="submit" name="action" value="save" class="btn-secondary">← Guardar</button>
                <button type="submit" name="action" value="generate-pdf" class="btn-primary">Generar PDF</button>
            </div>
        </div>
    </form>

    <script>
        function formatMoney(value) {
            return '$' + (parseFloat(value) || 0).toFixed(2);
        }

        function recalc() {
            let subtotal = 0;
            const cards = document.querySelectorAll('.partida-card');
            cards.forEach(card => {
                const cantidad = parseFloat(card.querySelector('[name*="[cantidad]"]').value) || 0;
                const precio = parseFloat(card.querySelector('[name*="[precio_unitario]"]').value) || 0;
                const importe = cantidad * precio;
                card.querySelector('.importe-val').textContent = importe.toFixed(2);
                subtotal += importe;
            });

            const envio = parseFloat(document.getElementById('envio').value) || 0;
            subtotal += envio;

            const requiereIva = document.getElementById('requiere_iva').checked;
            const iva = requiereIva ? subtotal * 0.16 : 0;
            const total = subtotal + iva;
            const anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
            const pagar = total - anticipo;

            document.getElementById('subtotal').textContent = formatMoney(subtotal);
            document.getElementById('iva').textContent = formatMoney(iva);
            document.getElementById('total').textContent = formatMoney(total);
            document.getElementById('anticipoDisplay').textContent = '-' + formatMoney(anticipo);
            document.getElementById('pagar').textContent = formatMoney(pagar);
        }

        function addPartida() {
            const container = document.getElementById('partidasContainer');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'partida-card';
            div.dataset.index = index;
            div.innerHTML = `
                <div class="partida-header">
                    <span class="partida-title">Partida ${index + 1}</span>
                    <button type="button" class="btn-remove" onclick="removePartida(this)">Eliminar</button>
                </div>
                <div class="partida-grid">
                    <div class="field"><label>Item</label><input type="text" name="partidas_remision[${index}][item]" value="Partida ${index + 1}" onchange="recalc()"></div>
                    <div class="field"><label>Descripción</label><input type="text" name="partidas_remision[${index}][descripcion]"></div>
                    <div class="field"><label>Unidad</label><input type="text" name="partidas_remision[${index}][unidad]" value="SERVICIO"></div>
                    <div class="field"><label>Cantidad</label><input type="number" name="partidas_remision[${index}][cantidad]" value="1" min="0" step="any" onchange="recalc()"></div>
                    <div class="field"><label>Precio unitario</label><input type="number" name="partidas_remision[${index}][precio_unitario]" value="0" min="0" step="any" onchange="recalc()"></div>
                </div>
                <div class="partida-import">Importe $<span class="importe-val">0.00</span></div>
            `;
            container.appendChild(div);
            recalc();
        }

        function removePartida(btn) {
            btn.closest('.partida-card').remove();
            reindexPartidas();
            recalc();
        }

        function reindexPartidas() {
            const container = document.getElementById('partidasContainer');
            Array.from(container.children).forEach((card, i) => {
                card.dataset.index = i;
                card.querySelector('.partida-title').textContent = 'Partida ' + (i + 1);
                card.querySelectorAll('input').forEach(input => {
                    const name = input.name.replace(/partidas_remision\[\d+\]/, `partidas_remision[${i}]`);
                    input.name = name;
                });
            });
        }

        function regenerateDefaults() {
            document.getElementById('descripcion_general').value = `{!! addslashes($defaultDescription) !!}`;
            recalc();
        }

        document.addEventListener('DOMContentLoaded', recalc);
    </script>
@endsection
