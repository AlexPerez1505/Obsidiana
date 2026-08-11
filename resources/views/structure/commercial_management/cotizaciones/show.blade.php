@extends('layouts.dashboard')
@section('title', 'Cotización #'.$cotizacion->id)
@section('page-title', 'Cotización #'.$cotizacion->id)
@section('page-sub', ($cotizacion->cliente?->nombre).' '.($cotizacion->cliente?->apellido))

@section('content')
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <a class="link" href="{{ route('commercial.cotizaciones.index') }}">← Volver a cotizaciones</a>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="badge {{ $cotizacion->estado === 'remision' ? 'badge--ok' : 'badge--warn' }}" style="font-size:13px; padding:6px 12px;">
                {{ $cotizacion->estado === 'remision' ? 'Remisión (venta definitiva)' : 'Cotización (solo presupuesto)' }}
            </span>
            <a href="{{ route('commercial.cotizaciones.edit', $cotizacion) }}" class="btn btn--ghost" style="text-decoration:none;">Editar</a>
            @if($cotizacion->estado !== 'remision')
                <form method="POST" action="{{ route('commercial.cotizaciones.remision', $cotizacion) }}" onsubmit="return confirm('¿Convertir esta cotización en remisión? Se volverá una venta definitiva.');">
                    @csrf
                    <button type="submit" class="btn">Realizar remisión</button>
                </form>
            @endif
        </div>
    </div>

    @if($cotizacion->estado !== 'remision')
        <div class="card" style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:14px 18px; margin-bottom:18px; font-size:13.5px; color:var(--muted);">
            Esta es solo una <strong>cotización</strong>: refleja cuánto costaría este producto/paquete. Los pagos aún no se dan de seguimiento formal. Presiona <strong>"Realizar remisión"</strong> para convertirla en una venta definitiva y comenzar a registrar los pagos.
        </div>
    @endif

    <div class="rgrid-sidebar">
        <div>
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Productos / Paquetes</x-ui.section-title>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cant.</th>
                                <th>Precio original</th>
                                <th>Sobreprecio</th>
                                <th>Regalo</th>
                                <th>Precio final</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cotizacion->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->nombre }}
                                        @if($item->producto_id && $item->paquete_id)
                                            <span class="muted" style="font-size:11.5px;">(paquete: {{ $item->paquete?->nombre }})</span>
                                        @elseif($item->producto_id)
                                            <span class="muted" style="font-size:11.5px;">(producto)</span>
                                        @else
                                            <span class="muted" style="font-size:11.5px;">(paquete)</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->cantidad }}</td>
                                    <td>${{ number_format($item->precio_original, 2) }}</td>
                                    <td>${{ number_format($item->sobreprecio, 2) }}</td>
                                    <td>{{ $item->es_regalo ? '✅' : '—' }}</td>
                                    <td>${{ number_format($item->precio_final, 2) }}</td>
                                    <td style="font-weight:700;">${{ $item->es_regalo ? 'Obsequio' : '$'.number_format($item->subtotal_linea, 2) }}</td>
                                </tr>
                                @if(!$item->producto_id && $item->paquete_id && $item->paquete && $item->paquete->productos->isNotEmpty())
                                    <tr>
                                        <td colspan="7" style="background:var(--surface-2); padding:8px 16px;">
                                            <div class="muted" style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px;">Incluye</div>
                                            <div style="font-size:13px;">
                                                @foreach($item->paquete->productos as $prod)
                                                    <div style="display:flex; justify-content:space-between; padding:4px 0;">
                                                        <span>{{ trim($prod->tipo_equipo.' '.$prod->marca.' '.$prod->modelo) }} <span class="muted">x{{ $prod->pivot->cantidad * $item->cantidad }}</span></span>
                                                        <span class="muted">${{ number_format($prod->precio, 2) }} c/u</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="7" style="text-align:center; padding:20px; color:var(--muted);">Sin productos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Resumen</x-ui.section-title>
                <div class="rgrid-3">
                    <div><div class="muted" style="font-size:13px;">Subtotal</div><div style="font-weight:700;">${{ number_format($cotizacion->subtotal, 2) }}</div></div>
                    <div><div class="muted" style="font-size:13px;">Descuentos</div><div style="font-weight:700;">${{ number_format($cotizacion->descuentos, 2) }}</div></div>
                    <div><div class="muted" style="font-size:13px;">IVA {{ $cotizacion->aplica_iva ? '(16%)' : '(no aplica)' }}</div><div style="font-weight:700;">${{ number_format($cotizacion->iva, 2) }}</div></div>
                    <div><div class="muted" style="font-size:13px;">Costo de envío</div><div style="font-weight:700;">${{ number_format($cotizacion->costo_envio, 2) }}</div></div>
                    <div><div class="muted" style="font-size:13px;">Anticipo</div><div style="font-weight:700; color:var(--green);">${{ number_format($cotizacion->anticipo, 2) }}</div></div>
                    <div><div class="muted" style="font-size:13px;">Total</div><div style="font-weight:800; font-size:18px;">${{ number_format($cotizacion->total, 2) }}</div></div>
                    <div><div class="muted" style="font-size:13px;">Lugar</div><div style="font-weight:700;">{{ $cotizacion->lugar ?: '—' }}</div></div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                    <x-ui.section-title style="margin:0;">Plan de Pagos</x-ui.section-title>
                    <button type="button" class="btn btn--ghost" style="padding:6px 12px; font-size:13px;" onclick="document.getElementById('modal-plan-pago').style.display='flex'">
                        + Agregar plan de pago
                    </button>
                </div>

                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Pago</th>
                                <th>Fecha límite</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cotizacion->planPagos->sortBy('no_pago') as $plan)
                                @php $pagado = $plan->pagos->where('pagado', true)->isNotEmpty(); @endphp
                                <tr>
                                    <td>
                                        {{ $plan->no_pago === 0 ? 'Anticipo' : $plan->no_pago }}
                                        @if($plan->no_pago === 0)
                                            <span class="badge" style="background:var(--green-soft); color:var(--green); font-weight:700; font-size:10.5px; margin-left:4px;">ANTICIPO</span>
                                        @endif
                                    </td>
                                    <td>{{ $plan->plazo_pagar->format('d/m/Y') }}</td>
                                    <td style="font-weight:700;">${{ number_format($plan->monto ?? ($cotizacion->total / max($cotizacion->planPagos->count(), 1)), 2) }}</td>
                                    <td>{{ $plan->metodo_pago }}</td>
                                    <td>
                                        <span class="badge {{ $pagado ? 'badge--ok' : 'badge--warn' }}">
                                            {{ $pagado ? 'Pagado' : 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(!$pagado)
                                            @if($cotizacion->estado === 'remision')
                                                <button type="button" class="btn btn--ghost" style="padding:6px 10px; font-size:12.5px;"
                                                        onclick="abrirModalPago({{ $plan->id }}, {{ $plan->monto ?? ($cotizacion->total / max($cotizacion->planPagos->count(), 1)) }})">
                                                    Registrar pago
                                                </button>
                                            @else
                                                <span class="muted" style="font-size:12px;" title="Convierte la cotización en remisión para registrar pagos">Pendiente de remisión</span>
                                            @endif
                                        @else
                                            <span class="muted" style="font-size:12.5px;">
                                                ${{ number_format($plan->pagos->where('pagado', true)->first()?->monto_pagado ?? 0, 2) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--muted);">Sin planes de pago.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        <div>
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Cliente</x-ui.section-title>
                <p style="margin:0; font-weight:700;">{{ $cotizacion->cliente?->nombre }} {{ $cotizacion->cliente?->apellido }}</p>
                <p class="muted" style="margin:4px 0 0; font-size:13.5px;">{{ $cotizacion->cliente?->telefono ?: 'Sin teléfono' }}</p>
                <p class="muted" style="margin:2px 0 0; font-size:13.5px;">{{ $cotizacion->cliente?->gmail ?: 'Sin correo' }}</p>
                <a href="{{ route('commercial.clientes.show', $cotizacion->cliente) }}" class="link" style="display:inline-block; margin-top:10px; font-size:13.5px;">Ver perfil completo →</a>
            </x-ui.card>
        </div>
    </div>

    {{-- Modal: nuevo plan de pago --}}
    <div id="modal-plan-pago" class="modal-overlay" style="display:none;">
        <div class="modal-card">
            <h3 style="margin:0 0 14px; font-size:18px;">Agregar plan de pago</h3>
            <form method="POST" action="{{ route('commercial.cotizaciones.planPagos.store', $cotizacion) }}">
                @csrf
                <x-ui.form-group label="Fecha límite *" name="plazo_pagar" type="date" :required="true" />
                <x-ui.form-group for="metodo_pago" label="Método de pago *">
                    <select id="metodo_pago" name="metodo_pago" required style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </x-ui.form-group>
                <div class="modal-actions">
                    <button type="button" class="btn btn--ghost" onclick="document.getElementById('modal-plan-pago').style.display='none'">Cancelar</button>
                    <x-ui.button type="submit" style="width:auto;">Guardar</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: registrar pago --}}
    <div id="modal-pago" class="modal-overlay" style="display:none;">
        <div class="modal-card">
            <h3 style="margin:0 0 14px; font-size:18px;">Registrar pago</h3>
            <form method="POST" id="form-pago" action="">
                @csrf
                <x-ui.form-group label="Monto pagado *" name="monto_pagado" id="monto_pagado" type="number" step="0.01" min="0" :required="true" />
                <x-ui.form-group for="pago_atrasado" label="¿Pago atrasado?">
                    <input type="hidden" name="pago_atrasado" value="0">
                    <label class="ui-switch">
                        <input type="checkbox" id="pago_atrasado" name="pago_atrasado" value="1">
                        <span class="slider"></span>
                    </label>
                </x-ui.form-group>
                <x-ui.form-group label="Nota" name="nota" placeholder="Opcional" />
                <div class="modal-actions">
                    <button type="button" class="btn btn--ghost" onclick="document.getElementById('modal-pago').style.display='none'">Cancelar</button>
                    <x-ui.button type="submit" style="width:auto;">Guardar pago</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 22px; width: 100%; max-width: 420px; box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }
    </style>

    <script>
        function abrirModalPago(planPagoId, montoSugerido) {
            const form = document.getElementById('form-pago');
            form.action = '{{ url('gestion-comercial/cotizaciones/plan-pagos') }}/' + planPagoId + '/pagos';
            document.getElementById('monto_pagado').value = montoSugerido.toFixed(2);
            document.getElementById('modal-pago').style.display = 'flex';
        }
    </script>
@endsection
