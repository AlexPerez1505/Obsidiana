@extends('structure.commercial_management.erp')

@section('title', 'Orden de servicio · ' . ($service->service_number ?? 'OS-' . $service->id))

@section('erp_content')
    @php
        $equipment = $service->serviceEquipment;
        $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
        $technician = $service->internalTechnician?->name ?? '—';
        $acciones = collect($datos['acciones'] ?? []);
        $inputStyle = 'width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:13px;';
        $labelStyle = 'display:block; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px;';
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <a href="{{ route('gestion.servicios.servicio_externo.orden_servicio', $service) }}" class="erp-back" title="Volver">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <h1 class="erp-h1">Orden de servicio · {{ $service->service_number ?? ('OS-' . $service->id) }}</h1>
            <span class="erp-badge info"><span class="dot"></span>{{ $customerName }}</span>
        </div>
    </div>

    <div style="margin-bottom:14px; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em;">Paso 4 de 4 · Resumen y cierre</div>

    <form method="POST" action="{{ route('gestion.servicios.servicio_externo.orden_servicio.generar', $service) }}">
        @csrf
        @foreach (['fecha_ingreso', 'fecha_mantenimiento', 'equipo', 'marca', 'modelo', 'serie', 'observaciones', 'proximo_mantenimiento', 'tipo_mantenimiento'] as $campo)
            <input type="hidden" name="{{ $campo }}" value="{{ $datos[$campo] ?? '' }}">
        @endforeach
        @foreach ($acciones as $accion)
            <input type="hidden" name="acciones[]" value="{{ $accion }}">
        @endforeach

        <div class="erp-card" style="margin-bottom:16px;">
            <div style="padding:14px 16px; border-bottom:1px solid var(--border);">
                <h2 style="margin:0; font-size:15px; font-weight:700;">Revisión final</h2>
            </div>
            <div style="padding:0 16px;">
                @php
                    $revision = [
                        'Cliente' => $customerName,
                        'Equipo' => $datos['equipo'] ?? $equipment?->type_text ?? '—',
                        'Fechas' => \Illuminate\Support\Carbon::parse($datos['fecha_ingreso'])->format('Y-m-d') . ' / ' . \Illuminate\Support\Carbon::parse($datos['fecha_mantenimiento'])->format('Y-m-d'),
                        'Técnico' => $technician,
                        'Identificación' => ($datos['marca'] ?? '—') . ' | ' . ($datos['modelo'] ?? '—') . ' | SN: ' . ($datos['serie'] ?? '—'),
                        'Próx. mto.' => ! empty($datos['proximo_mantenimiento']) ? $datos['proximo_mantenimiento'] . ' meses' : '—',
                    ];
                @endphp
                @foreach ($revision as $k => $v)
                    <div style="display:flex; justify-content:space-between; padding:11px 0; border-bottom:1px solid var(--border); font-size:14px;">
                        <span style="color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.03em;">{{ $k }}</span>
                        <strong>{{ $v }}</strong>
                    </div>
                @endforeach
            </div>

            <div style="padding:14px 16px 16px;">
                <h3 style="margin:0 0 10px; font-size:14px; font-weight:700;">Checklist procesado</h3>
                <table class="erp-table">
                    <thead>
                        <tr><th>Ítem</th><th>Sección</th><th>Estatus</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($acciones as $accion)
                            <tr>
                                <td>{{ $accion }}</td>
                                <td>Mantenimiento realizado</td>
                                <td><span class="erp-badge ok"><span class="dot"></span>OK</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="erp-empty">Sin acciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap;">
                    @foreach ($acciones as $accion)
                        <span class="erp-badge neutral">{{ $accion }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="erp-card" style="margin-bottom:16px;">
            <div style="padding:14px 16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="margin:0; font-size:15px; font-weight:700;">Partidas para remisión</h2>
                    <div style="font-size:12px; color:var(--muted);">Configuración final de cobro — tomadas de la cotización del servicio.</div>
                </div>
                <span class="erp-count" id="items-count">Ítems: {{ $partidas->count() }}</span>
            </div>
            <div style="padding:16px;" id="partidas-list">
                @foreach ($partidas as $i => $p)
                    <div class="partida" style="border:1px solid var(--border); border-radius:10px; padding:14px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <strong style="font-size:14px;">Partida <span class="num">{{ $i + 1 }}</span></strong>
                            <button type="button" class="partida-del" style="background:none; border:1px solid var(--danger); color:var(--danger); border-radius:8px; padding:4px 12px; font-size:12px; cursor:pointer;">Eliminar</button>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 2fr .7fr .5fr .8fr; gap:10px;">
                            <div><label style="{{ $labelStyle }}">Ítem</label><input type="text" name="partidas[{{ $i }}][item]" value="{{ $p['item'] }}" style="{{ $inputStyle }}"></div>
                            <div><label style="{{ $labelStyle }}">Descripción</label><input type="text" name="partidas[{{ $i }}][descripcion]" value="{{ $p['descripcion'] }}" style="{{ $inputStyle }}"></div>
                            <div><label style="{{ $labelStyle }}">Unidad</label><input type="text" name="partidas[{{ $i }}][unidad]" value="{{ $p['unidad'] }}" style="{{ $inputStyle }}"></div>
                            <div><label style="{{ $labelStyle }}">Cantidad</label><input type="number" step="0.01" min="0" name="partidas[{{ $i }}][cantidad]" value="{{ $p['cantidad'] }}" class="p-cant" style="{{ $inputStyle }}"></div>
                            <div><label style="{{ $labelStyle }}">Precio unitario</label><input type="number" step="0.01" min="0" name="partidas[{{ $i }}][precio]" value="{{ $p['precio'] }}" class="p-precio" style="{{ $inputStyle }}"></div>
                        </div>
                        <div style="text-align:right; margin-top:10px; font-size:14px;">Importe <strong class="p-importe">${{ number_format($p['cantidad'] * $p['precio'], 2) }}</strong></div>
                    </div>
                @endforeach
            </div>
            <div style="padding:0 16px 16px; display:flex; gap:8px;">
                <button type="button" id="agregar-partida" class="erp-btn ghost" style="font-size:13px;">+ Agregar</button>
            </div>

            <div style="padding:0 16px 16px; display:grid; grid-template-columns:1fr 1fr; gap:12px; max-width:520px;">
                <div>
                    <label style="{{ $labelStyle }}">Envío</label>
                    <input type="number" step="0.01" min="0" name="envio" id="envio" value="0" style="{{ $inputStyle }}">
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Anticipo</label>
                    <input type="number" step="0.01" min="0" name="anticipo" id="anticipo" value="0" style="{{ $inputStyle }}">
                </div>
            </div>

            <div style="padding:0 16px 16px;">
                <label style="display:flex; align-items:center; gap:10px; padding:12px; background:var(--surface-2, var(--surface)); border-radius:9px; font-size:14px; cursor:pointer; max-width:280px;">
                    Requiere IVA (16%)
                    <input type="checkbox" name="iva" id="iva" value="1" style="width:17px; height:17px; accent-color:var(--primary); margin-left:auto;">
                </label>
            </div>

            <div style="padding:0 16px 16px; max-width:420px; margin-left:auto; font-size:14px;">
                <div style="display:flex; justify-content:space-between; padding:6px 0;"><span style="color:var(--muted);">Subtotal</span><strong id="t-subtotal">$0.00</strong></div>
                <div style="display:flex; justify-content:space-between; padding:6px 0;"><span style="color:var(--muted);">IVA</span><strong id="t-iva">$0.00</strong></div>
                <div style="display:flex; justify-content:space-between; padding:6px 0; border-top:1px solid var(--border);"><span>Total</span><strong id="t-total">$0.00</strong></div>
                <div style="display:flex; justify-content:space-between; padding:6px 0;"><span style="color:var(--danger);">Anticipo</span><strong id="t-anticipo" style="color:var(--danger);">$0.00</strong></div>
                <div style="display:flex; justify-content:space-between; padding:10px 0; border-top:1px solid var(--border); font-size:16px;"><span>Pagar</span><strong id="t-pagar">$0.00</strong></div>
            </div>

            <div style="padding:0 16px 16px;">
                <label for="descripcion_general" style="{{ $labelStyle }}">Descripción general remisión</label>
                <textarea id="descripcion_general" name="descripcion_general" rows="2" style="{{ $inputStyle }} resize:vertical;">{{ old('descripcion_general', $descripcionGeneral) }}</textarea>
            </div>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" class="erp-btn ghost" onclick="history.back()">← Editar</button>
            <button type="submit" class="erp-btn" style="flex:1; max-width:420px;">Generar PDF</button>
        </div>
    </form>

    <script>
        var partidaTpl = '<div class="partida" style="border:1px solid var(--border); border-radius:10px; padding:14px; margin-bottom:12px;">'
            + '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">'
            + '<strong style="font-size:14px;">Partida <span class="num"></span></strong>'
            + '<button type="button" class="partida-del" style="background:none; border:1px solid var(--danger); color:var(--danger); border-radius:8px; padding:4px 12px; font-size:12px; cursor:pointer;">Eliminar</button>'
            + '</div>'
            + '<div style="display:grid; grid-template-columns:1fr 2fr .7fr .5fr .8fr; gap:10px;">'
            + '<div><label style="{{ $labelStyle }}">Ítem</label><input type="text" name="ITEM" style="{{ $inputStyle }}"></div>'
            + '<div><label style="{{ $labelStyle }}">Descripción</label><input type="text" name="DESC" style="{{ $inputStyle }}"></div>'
            + '<div><label style="{{ $labelStyle }}">Unidad</label><input type="text" name="UNIDAD" value="SERVICIO" style="{{ $inputStyle }}"></div>'
            + '<div><label style="{{ $labelStyle }}">Cantidad</label><input type="number" step="0.01" min="0" name="CANT" value="1" class="p-cant" style="{{ $inputStyle }}"></div>'
            + '<div><label style="{{ $labelStyle }}">Precio unitario</label><input type="number" step="0.01" min="0" name="PRECIO" value="0" class="p-precio" style="{{ $inputStyle }}"></div>'
            + '</div>'
            + '<div style="text-align:right; margin-top:10px; font-size:14px;">Importe <strong class="p-importe">$0.00</strong></div>'
            + '</div>';

        function fmt(n) { return '$' + n.toFixed(2); }

        function reindex() {
            var items = document.querySelectorAll('#partidas-list .partida');
            items.forEach(function (el, i) {
                el.querySelector('.num').textContent = i + 1;
                el.querySelectorAll('input').forEach(function (inp) {
                    inp.name = inp.name.replace(/partidas\[\d+\]/, 'partidas[' + i + ']');
                });
            });
            document.getElementById('items-count').textContent = 'Ítems: ' + items.length;
        }

        function recalc() {
            var subtotal = 0;
            document.querySelectorAll('#partidas-list .partida').forEach(function (el) {
                var cant = parseFloat(el.querySelector('.p-cant').value) || 0;
                var precio = parseFloat(el.querySelector('.p-precio').value) || 0;
                el.querySelector('.p-importe').textContent = fmt(cant * precio);
                subtotal += cant * precio;
            });
            var envio = parseFloat(document.getElementById('envio').value) || 0;
            var anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
            var iva = document.getElementById('iva').checked ? (subtotal + envio) * 0.16 : 0;
            var total = subtotal + envio + iva;
            document.getElementById('t-subtotal').textContent = fmt(subtotal + envio);
            document.getElementById('t-iva').textContent = fmt(iva);
            document.getElementById('t-total').textContent = fmt(total);
            document.getElementById('t-anticipo').textContent = fmt(anticipo);
            document.getElementById('t-pagar').textContent = fmt(total - anticipo);
        }

        document.getElementById('agregar-partida').addEventListener('click', function () {
            var i = document.querySelectorAll('#partidas-list .partida').length;
            var html = partidaTpl.replace('ITEM', 'partidas[' + i + '][item]')
                .replace('DESC', 'partidas[' + i + '][descripcion]')
                .replace('UNIDAD', 'partidas[' + i + '][unidad]')
                .replace('CANT', 'partidas[' + i + '][cantidad]')
                .replace('PRECIO', 'partidas[' + i + '][precio]');
            document.getElementById('partidas-list').insertAdjacentHTML('beforeend', html);
            reindex();
        });

        document.getElementById('partidas-list').addEventListener('click', function (e) {
            if (e.target.classList.contains('partida-del')) {
                e.target.closest('.partida').remove();
                reindex();
                recalc();
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('p-cant') || e.target.classList.contains('p-precio')
                || e.target.id === 'envio' || e.target.id === 'anticipo') recalc();
        });
        document.getElementById('iva').addEventListener('change', recalc);
        recalc();
    </script>
@endsection
