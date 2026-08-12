@extends('layouts.dashboard')
@section('title', 'Nueva Cotización')
@section('page-title', 'Nueva Cotización')
@section('page-sub', 'Selecciona un cliente, un producto o paquete, y define el plan de pagos')

@section('content')
    <form method="POST" action="{{ route('commercial.cotizaciones.store') }}" id="form-cotizacion">
        @csrf

        {{-- Cliente --}}
        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Cliente</x-ui.section-title>

            <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $clienteSeleccionado?->id }}">

            <div id="cliente-buscador" style="{{ $clienteSeleccionado ? 'display:none;' : '' }}">
                <div style="position:relative;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="cliente-search" placeholder="Buscar cliente por nombre o teléfono..." autocomplete="off"
                           style="width:100%; padding:11px 12px 11px 38px; border:1px solid var(--field-border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                    <div id="cliente-resultados" style="position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--surface); border:1px solid var(--field-border); border-radius:9px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:20; display:none; max-height:220px; overflow-y:auto;"></div>
                </div>
                <button type="button" id="btn-nuevo-cliente" class="btn btn--ghost" style="margin-top:10px;">
                    + Agregar cliente nuevo
                </button>
            </div>

            <div id="cliente-seleccionado" style="{{ $clienteSeleccionado ? '' : 'display:none;' }} display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border:1px solid var(--field-border); border-radius:9px; cursor:pointer;" title="Clic para buscar otro cliente">
                <span id="cliente-seleccionado-nombre">
                    @if($clienteSeleccionado)
                        {{ $clienteSeleccionado->nombre }} {{ $clienteSeleccionado->apellido }} — {{ $clienteSeleccionado->telefono }}
                    @endif
                </span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--muted); flex:0 0 auto;"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </div>
        </x-ui.card>

        {{-- Producto / Paquete --}}
        <x-ui.card style="margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div class="qbox-ico blue" style="width:42px; height:42px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <div>
                    <div style="font-weight:700; font-size:16px;">Agregar productos o paquetes</div>
                    <div class="muted" style="font-size:13px;">Selecciona productos o paquetes para agregarlos a la orden.</div>
                </div>
            </div>

            <div style="max-width:520px;">
                <label class="qlabel">Buscar producto o paquete</label>
                <div style="position:relative;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="buscador-items" class="qinput" placeholder="Escribe el nombre, marca o modelo..." autocomplete="off" style="padding-left:38px;">
                    <div id="resultados-items" style="position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--surface); border:1px solid var(--field-border); border-radius:9px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:20; display:none; max-height:320px; overflow-y:auto;"></div>
                </div>
            </div>

            <div style="margin-top:24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                    <div style="font-weight:700; font-size:15px;">Productos Seleccionados</div>
                    <div class="muted" style="font-size:12.5px;">Marca <span class="badge" style="background:var(--green-soft); color:var(--green); font-weight:700;">REGALO</span> para excluir del total y ocultar precio.</div>
                </div>
                <div id="items-list"></div>
                <div id="items-empty" style="margin-top:8px; text-align:center; padding:32px 16px;">
                    <div class="qbox-ico blue" style="width:52px; height:52px; margin:0 auto 14px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                    </div>
                    <div style="font-weight:700; font-size:15px;">Aún no has agregado productos ni paquetes.</div>
                    <div class="muted" style="font-size:13.5px; margin-top:4px;">Selecciona un producto o paquete arriba para agregarlo automáticamente.</div>
                </div>
            </div>
        </x-ui.card>

        {{-- Montos --}}
        <x-ui.card style="margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div class="qbox-ico blue" style="width:42px; height:42px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a3 3 0 0 0-3-1.5c-1.7 0-3 1-3 2.5S10.3 13 12 13s3 1 3 2.5-1.3 2.5-3 2.5a3 3 0 0 1-3-1.5"/></svg>
                </div>
                <x-ui.section-title style="margin:0;">Montos</x-ui.section-title>
            </div>

            <div class="qgrid">
                <div class="qbox">
                    <div class="qbox-head">
                        <div class="qbox-ico blue"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg></div>
                        <span class="qbox-label">Subtotal</span>
                    </div>
                    <div class="qbox-value" id="subtotal-display">$0.00</div>
                </div>
                <div class="qbox">
                    <div class="qbox-head">
                        <div class="qbox-ico green"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41 12 22l-9.41-9.41A2 2 0 0 1 2 11.17V4a2 2 0 0 1 2-2h7.17a2 2 0 0 1 1.42.59l9.41 9.41a2 2 0 0 1 0 2.41Z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></div>
                        <span class="qbox-label">Descuentos</span>
                    </div>
                    <div class="qbox-value" id="descuentos-display" style="color:var(--green);">$0.00</div>
                </div>
                <div class="qbox">
                    <span class="qbox-label" title="Descuento manual adicional aplicado a la cotización">Descuento adicional
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 1.8-2.5 3.5"/><path d="M12 17h.01"/></svg>
                    </span>
                    <div style="position:relative; margin-top:8px;">
                        <span class="qprefix">$</span>
                        <input id="descuentos" name="descuentos" type="number" step="0.01" min="0" value="0" class="qinput" style="padding-left:26px;">
                    </div>
                </div>

                <div class="qbox">
                    <div class="qbox-head">
                        <div class="qbox-ico blue"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                        <span class="qbox-label">Costo de envío</span>
                    </div>
                    <div style="position:relative; margin-top:8px;">
                        <span class="qprefix">$</span>
                        <input id="costo_envio" name="costo_envio" type="number" step="0.01" min="0" value="0" class="qinput" style="padding-left:26px;">
                    </div>
                </div>
                <div class="qbox">
                    <div class="qbox-head">
                        <div class="qbox-ico orange"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <span class="qbox-label">Lugar de entrega</span>
                    </div>
                    <input id="lugar" name="lugar" type="text" placeholder="Lugar de entrega" class="qinput" style="margin-top:8px;">
                </div>
                <div class="qbox" style="justify-content:center;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" id="aplica_iva" name="aplica_iva" value="1" style="width:18px; height:18px;">
                        <span style="font-size:14px; font-weight:600; color:var(--text);">¿Aplica IVA?</span>
                        <span class="badge badge--info">16%</span>
                    </label>
                </div>

                <div class="qbox">
                    <div class="qbox-head">
                        <div class="qbox-ico purple"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg></div>
                        <span class="qbox-label">IVA calculado</span>
                    </div>
                    <div class="qbox-value" id="iva-display" style="color:#9333ea;">$0.00</div>
                </div>

                <div class="qbox">
                    <div class="qbox-head">
                        <div class="qbox-ico green"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                        <span class="qbox-label" title="Monto que el cliente adelanta. Se resta del total antes de repartirlo en el plan de pagos.">Anticipo
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 1.8-2.5 3.5"/><path d="M12 17h.01"/></svg>
                        </span>
                    </div>
                    <div style="position:relative; margin-top:8px;">
                        <span class="qprefix">$</span>
                        <input id="anticipo" name="anticipo" type="number" step="0.01" min="0" value="0" class="qinput" style="padding-left:26px;">
                    </div>
                </div>
            </div>

            <div style="margin-top:16px; padding:18px 20px; border:1px solid var(--field-border); border-radius:12px; display:flex; justify-content:space-between; align-items:center; background:var(--surface-2);">
                <div>
                    <div style="font-size:16px; font-weight:700;">Total</div>
                    <div class="muted" style="font-size:13px;">Importe final a pagar</div>
                </div>
                <span id="total-display" style="font-size:26px; font-weight:800; color:var(--primary);">$0.00</span>
            </div>
        </x-ui.card>

        {{-- Plan de pagos --}}
        <x-ui.card style="margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div class="qbox-ico purple" style="width:42px; height:42px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg>
                </div>
                <div>
                    <div style="font-weight:700; font-size:16px;">Plan de Pagos</div>
                    <div class="muted" style="font-size:13px;">Selecciona un plan predeterminado y ajusta los montos si lo necesitas.</div>
                </div>
            </div>

            {{-- Selección de plan de pago (siempre visible) --}}
            <div id="plan-pagos-form">
                <div class="qgrid" style="grid-template-columns:repeat(3, 1fr);">
                    <div class="qbox">
                        <label class="qlabel"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg> Plan de pago *</label>
                        <select id="plan-pago-plantilla" class="qinput" required>
                            <option value="">— Selecciona un plan de pago —</option>
                            @foreach($planesPago as $plan)
                                <option value="{{ $plan->id }}" data-numero-pagos="{{ $plan->numero_pagos }}" data-dias-entre-pagos="{{ $plan->dias_entre_pagos }}" data-metodo-pago="{{ $plan->metodo_pago }}">
                                    {{ $plan->nombre }} - {{ $plan->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="qbox">
                        <label class="qlabel" for="metodo_pago"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg> Método de pago *</label>
                        <select id="metodo_pago" name="metodo_pago" required class="qinput">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="qbox">
                        <label class="qlabel"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> Fecha del primer pago *</label>
                        <input id="fecha_inicio" name="fecha_inicio" type="date" value="{{ now()->toDateString() }}" required class="qinput">
                    </div>
                </div>
                <input type="hidden" id="numero_pagos" name="numero_pagos" value="">
                <input type="hidden" id="dias_entre_pagos" name="dias_entre_pagos" value="">
            </div>

            {{-- Estado vacío: aún no se ha seleccionado un plan --}}
            <div id="plan-pagos-empty" style="text-align:center; padding:24px 16px;">
                <div class="muted" style="font-size:13.5px;">Selecciona un plan de pago arriba para ver y ajustar las fechas y montos de cada cuota.</div>
            </div>

            {{-- Resumen: cómo se organizarán los pagos --}}
            <div id="plan-pagos-resumen" style="display:none; margin-top:20px;">
                <div class="qgrid" style="grid-template-columns:repeat(3, 1fr);">
                    <div class="qbox">
                        <div class="qbox-head">
                            <div class="qbox-ico blue"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                            <span class="qbox-label">Método de pago</span>
                        </div>
                        <div class="qbox-value" id="resumen-metodo" style="font-size:16px;">—</div>
                    </div>
                    <div class="qbox">
                        <div class="qbox-head">
                            <div class="qbox-ico green"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg></div>
                            <span class="qbox-label">Monto por pago (estimado)</span>
                        </div>
                        <div class="qbox-value" id="resumen-monto-pago" style="color:var(--green);">$0.00</div>
                    </div>
                    <div class="qbox">
                        <div class="qbox-head">
                            <div class="qbox-ico orange"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                            <span class="qbox-label">Número de pagos</span>
                        </div>
                        <div class="qbox-value" id="resumen-numero-pagos">—</div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-top:16px;">
                    <div style="font-size:13.5px;">Restante por asignar (total - anticipo): <strong id="resumen-restante" style="font-size:15px;">$0.00</strong></div>
                    <button type="button" id="btn-redistribuir-pagos" class="btn btn--ghost" style="padding:7px 14px; font-size:13px;">Distribuir equitativamente</button>
                </div>

                <div class="responsive-table-wrap" style="margin-top:10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>No. de pago</th>
                                <th>Fecha estimada</th>
                                <th>Monto (editable)</th>
                            </tr>
                        </thead>
                        <tbody id="resumen-pagos-body"></tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <button type="submit" name="accion" value="cotizacion" class="btn btn--ghost">Guardar como cotización</button>
            <button type="submit" name="accion" value="remision" class="btn">Realizar remisión</button>
            <a href="{{ route('commercial.cotizaciones.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    {{-- Modal: nuevo cliente --}}
    <div id="modal-cliente" class="modal-overlay" style="display:none;">
        <div class="modal-card" style="max-width:520px;">
            <h3 style="margin:0 0 14px; font-size:18px;">Nuevo cliente</h3>
            <form id="form-nuevo-cliente">
                @csrf
                <div class="rgrid-2">
                    <x-ui.form-group label="Nombre *" name="nombre" :required="true" />
                    <x-ui.form-group label="Apellido *" name="apellido" :required="true" />
                    <x-ui.form-group label="Teléfono *" name="telefono" type="tel" inputmode="tel" maxlength="20" :required="true" />
                    <x-ui.form-group label="RFC" name="rfc" maxlength="13" />
                    <x-ui.form-group label="Correo (Gmail)" name="gmail" type="email" />
                    <x-ui.form-group label="Dirección *" name="direccion" :required="true" />
                    <x-ui.form-group for="categoria_id" label="Categoría *">
                        <select id="categoria_id" name="categoria_id" required class="qinput">
                            <option value="" disabled selected>Selecciona una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group for="congreso_id" label="Congreso Conocido *">
                        <select id="congreso_id" name="congreso_id" required class="qinput">
                            <option value="" disabled selected>Selecciona un congreso</option>
                            @foreach ($congresses as $congress)
                                <option value="{{ $congress->id }}">{{ $congress->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                </div>
                <div class="modal-actions">
                    <button type="button" id="btn-cancelar-cliente" class="btn btn--ghost">Cancelar</button>
                    <x-ui.button type="submit" style="width:auto;">Guardar cliente</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <style>
        :root { --field-border: #c9ccd2; }
        :root[data-theme="dark"] { --field-border: var(--border); }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-card { background: var(--surface); border: 1px solid var(--field-border); border-radius: 12px; padding: 22px; width: 100%; box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .resultado-item { padding:10px 12px; cursor:pointer; font-size:14px; }
        .resultado-item:hover { background: var(--surface-2); }
        .resultado-item.disabled { cursor:not-allowed; opacity:.55; }
        .resultado-item.disabled:hover { background:transparent; }
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }

        /* ===== Nuevo estilo de cotización ===== */
        .qbox-ico { border-radius:11px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .qbox-ico.blue { background:var(--primary-soft); color:var(--primary); }
        .qbox-ico.green { background:var(--green-soft); color:var(--green); }
        .qbox-ico.orange { background:var(--accent-soft); color:var(--accent); }
        .qbox-ico.purple { background:rgba(147,51,234,.12); color:#9333ea; }

        .qflow-row { display:flex; flex-wrap:wrap; gap:14px; align-items:end; }
        .qlabel { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text); }
        .qinput { width:100%; padding:11px 12px; border:1px solid var(--field-border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); }
        .qinput:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.15); }
        .qprefix { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; }

        .qstepper { display:flex; align-items:center; border:1px solid var(--field-border); border-radius:9px; overflow:hidden; background:var(--surface); }
        .qstepper button { width:36px; height:44px; border:none; background:var(--surface-2); color:var(--text); cursor:pointer; font-size:18px; flex:0 0 auto; }
        .qstepper button:hover { background:var(--border); }
        .qstepper input { flex:1; min-width:0; width:100%; text-align:center; border:none; background:transparent; color:var(--text); font-size:15px; padding:11px 2px; -moz-appearance:textfield; }
        .qstepper input::-webkit-outer-spin-button, .qstepper input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

        .qchip { display:flex; align-items:center; gap:8px; border:1px solid var(--field-border); border-radius:9px; padding:11px 16px; cursor:pointer; white-space:nowrap; user-select:none; color:var(--text); background:var(--surface); transition:border-color .15s, color .15s, background .15s; }
        .qchip input { width:16px; height:16px; margin-left:2px; }
        .qchip:has(input:checked) { border-color:var(--primary); color:var(--primary); background:var(--primary-soft); }

        .qgrid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
        .qbox { border:1px solid var(--field-border); border-radius:12px; padding:14px 16px; background:var(--surface); display:flex; flex-direction:column; }
        .qbox-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .qbox-head .qbox-ico { width:32px; height:32px; }
        .qbox-label { font-size:13px; font-weight:600; color:var(--muted); }
        .qbox-value { font-size:21px; font-weight:800; }

        @media (max-width:900px) { .qgrid { grid-template-columns:repeat(2, 1fr); } }
        @media (max-width:640px) { .qgrid { grid-template-columns:1fr; } .qflow-row > * { width:100% !important; flex:1 1 100% !important; } }
    </style>

    @php
        $productosData = [];
        foreach ($productos as $producto) {
            $productosData[$producto->id] = [
                'tipo_equipo' => $producto->tipo_equipo,
                'marca' => $producto->marca,
                'modelo' => $producto->modelo,
                'imagen' => $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null,
                'precio' => (float) $producto->precio,
                'stock' => (int) $producto->stock,
            ];
        }

        $paquetesDesgloseData = [];
        $paquetesNombres = [];
        $paquetesInfo = [];
        foreach ($paquetes as $paquete) {
            $paquetesNombres[$paquete->id] = $paquete->nombre;
            $lista = [];
            $disponible = true;
            $stockInfo = [];
            foreach ($paquete->productos as $prod) {
                if ($prod->pivot->cantidad > $prod->stock) {
                    $disponible = false;
                    $stockInfo[] = "{$prod->tipo_equipo} (req: {$prod->pivot->cantidad}, stock: {$prod->stock})";
                }
                $lista[] = [
                    'producto_id' => $prod->id,
                    'tipo_equipo' => $prod->tipo_equipo,
                    'marca' => $prod->marca,
                    'modelo' => $prod->modelo,
                    'imagen' => $prod->imagen_path ? asset('storage/'.$prod->imagen_path) : null,
                    'cantidad' => $prod->pivot->cantidad,
                    'precio' => (float) $prod->precio,
                ];
            }
            $paquetesDesgloseData[$paquete->id] = $lista;
            $paquetesInfo[$paquete->id] = [
                'nombre' => $paquete->nombre,
                'precio' => (float) $paquete->productos->sum(fn ($p) => $p->precio * $p->pivot->cantidad),
                'disponible' => $disponible,
                'stockInfo' => implode(', ', $stockInfo),
            ];
        }
    @endphp

    <script>
        // Datos de productos individuales
        const productosData = @json($productosData);
        // Desglose de productos incluidos en cada paquete
        const paquetesDesglose = @json($paquetesDesgloseData);
        const paquetesNombres = @json($paquetesNombres);
        const paquetesInfo = @json($paquetesInfo);

        // Búsqueda de cliente
        const clienteSearch = document.getElementById('cliente-search');
        const clienteResultados = document.getElementById('cliente-resultados');
        const clienteIdInput = document.getElementById('cliente_id');
        const clienteBuscador = document.getElementById('cliente-buscador');
        const clienteSeleccionadoBox = document.getElementById('cliente-seleccionado');
        const clienteSeleccionadoNombre = document.getElementById('cliente-seleccionado-nombre');

        function seleccionarCliente(cliente) {
            clienteIdInput.value = cliente.id;
            clienteSeleccionadoNombre.textContent = `${cliente.nombre} ${cliente.apellido ?? ''} — ${cliente.telefono ?? 'sin teléfono'}`;
            clienteBuscador.style.display = 'none';
            clienteSeleccionadoBox.style.display = 'flex';
            clienteResultados.style.display = 'none';
            clienteSearch.value = '';
        }

        function renderResultadosCliente(data, esVacio) {
            clienteResultados.innerHTML = '';
            if (esVacio && data.length > 0) {
                const encabezado = document.createElement('div');
                encabezado.className = 'muted';
                encabezado.style.cssText = 'font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:8px 12px 4px;';
                encabezado.textContent = 'Clientes recientes';
                clienteResultados.appendChild(encabezado);
            }
            if (data.length === 0) {
                clienteResultados.innerHTML = '<div class="resultado-item" style="color:var(--muted);">Sin resultados</div>';
            } else {
                data.forEach(cliente => {
                    const item = document.createElement('div');
                    item.className = 'resultado-item';
                    item.textContent = `${cliente.nombre} ${cliente.apellido ?? ''} — ${cliente.telefono ?? ''}`;
                    item.addEventListener('click', () => seleccionarCliente(cliente));
                    clienteResultados.appendChild(item);
                });
            }
            clienteResultados.style.display = 'block';
        }

        function buscarClientes(q) {
            fetch('{{ route('commercial.cotizaciones.buscarCliente') }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => renderResultadosCliente(data, q.length === 0));
        }

        let debounceTimer;
        clienteSearch?.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            debounceTimer = setTimeout(() => buscarClientes(q), 250);
        });

        // Al enfocar el buscador (vacío) se muestran los clientes más recientes
        clienteSearch?.addEventListener('focus', function () {
            if (this.value.trim().length === 0) buscarClientes('');
        });

        document.addEventListener('click', function (e) {
            if (!clienteResultados.contains(e.target) && e.target !== clienteSearch) {
                clienteResultados.style.display = 'none';
            }
        });

        // Clic en el cliente ya seleccionado reabre el buscador (sin botón "Cambiar")
        clienteSeleccionadoBox.addEventListener('click', function () {
            clienteIdInput.value = '';
            clienteBuscador.style.display = 'block';
            clienteSeleccionadoBox.style.display = 'none';
        });

        // Modal nuevo cliente
        const modalCliente = document.getElementById('modal-cliente');
        const formNuevoCliente = document.getElementById('form-nuevo-cliente');

        document.getElementById('btn-nuevo-cliente')?.addEventListener('click', () => modalCliente.style.display = 'flex');
        document.getElementById('btn-cancelar-cliente')?.addEventListener('click', () => {
            modalCliente.style.display = 'none';
            formNuevoCliente.reset();
        });
        modalCliente.addEventListener('click', (e) => { if (e.target === modalCliente) modalCliente.style.display = 'none'; });

        formNuevoCliente.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('{{ route('commercial.clientes.store') }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: formData,
            })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw new Error(data.message || 'No se pudo guardar el cliente.');
                }
                return data;
            })
            .then(data => {
                seleccionarCliente(data);
                modalCliente.style.display = 'none';
                formNuevoCliente.reset();
            })
            .catch(error => alert(error.message || 'No se pudo guardar el cliente.'));
        });

        // ---- Manejo de items (productos / paquetes) ----
        let items = [];
        let itemSeq = 0;

        const buscadorItems = document.getElementById('buscador-items');
        const resultadosItems = document.getElementById('resultados-items');
        const itemsList = document.getElementById('items-list');
        const itemsEmpty = document.getElementById('items-empty');

        function agregarProductoItem(id, precioOriginal, datos, origenPaqueteId, origenPaqueteNombre, cantidadInicial) {
            items.push({
                seq: itemSeq++,
                id: parseInt(id),
                tipo_equipo: datos.tipo_equipo,
                marca: datos.marca,
                modelo: datos.modelo,
                imagen: datos.imagen,
                cantidad: cantidadInicial || 1,
                precioOriginal: precioOriginal,
                sobreprecio: 0,
                esRegalo: false,
                paqueteOrigenId: origenPaqueteId || null,
                paqueteOrigenNombre: origenPaqueteNombre || null,
            });
        }

        // ---- Buscador combinado de productos y paquetes (misma UX que el buscador de clientes) ----
        function renderResultadosItems(q) {
            const texto = q.trim().toLowerCase();

            const productosMatch = Object.entries(productosData).filter(([id, p]) => {
                const t = `${p.tipo_equipo} ${p.marca} ${p.modelo}`.toLowerCase();
                return texto === '' || t.includes(texto);
            }).map(([id, p]) => ({ tipo: 'producto', id, data: p }));

            const paquetesMatch = Object.entries(paquetesInfo).filter(([id, p]) => {
                return texto === '' || p.nombre.toLowerCase().includes(texto);
            }).map(([id, p]) => ({ tipo: 'paquete', id, data: p }));

            const coincidencias = [...paquetesMatch, ...productosMatch].slice(0, 20);

            resultadosItems.innerHTML = '';
            if (coincidencias.length === 0) {
                resultadosItems.innerHTML = '<div class="resultado-item" style="color:var(--muted);">Sin resultados</div>';
            } else {
                coincidencias.forEach(({ tipo, id, data }) => {
                    const esPaquete = tipo === 'paquete';
                    const disponible = esPaquete ? data.disponible : data.stock >= 1;
                    const nombre = esPaquete ? data.nombre : `${data.tipo_equipo} ${data.marca} ${data.modelo}`;
                    const badge = esPaquete
                        ? '<span class="badge" style="background:var(--primary-soft); color:var(--primary); font-weight:700; font-size:10.5px; margin-right:6px;">PAQUETE</span>'
                        : '<span class="badge" style="background:var(--surface-2); color:var(--muted); font-weight:700; font-size:10.5px; margin-right:6px;">PRODUCTO</span>';
                    const detalle = esPaquete
                        ? (data.disponible ? 'Disponible' : 'Sin stock: ' + data.stockInfo)
                        : (disponible ? 'Stock: ' + data.stock : 'Sin stock');

                    const item = document.createElement('div');
                    item.className = 'resultado-item' + (!disponible ? ' disabled' : '');
                    item.innerHTML = `<div style="font-weight:600;">${badge}${nombre}</div>
                        <div class="muted" style="font-size:12px;">$${data.precio.toFixed(2)} — ${detalle}</div>`;

                    if (disponible) {
                        item.addEventListener('click', () => {
                            if (esPaquete) {
                                const nombrePaquete = paquetesNombres[id] || '';
                                const productosPaquete = paquetesDesglose[id] || [];
                                productosPaquete.forEach(prod => {
                                    const datos = productosData[prod.producto_id] || { tipo_equipo: prod.tipo_equipo, marca: prod.marca, modelo: prod.modelo, imagen: prod.imagen };
                                    agregarProductoItem(prod.producto_id, prod.precio, datos, id, nombrePaquete, prod.cantidad);
                                });
                            } else {
                                agregarProductoItem(id, data.precio, data, null, null, 1);
                            }
                            renderItems();
                            buscadorItems.value = '';
                            resultadosItems.style.display = 'none';
                        });
                    }
                    resultadosItems.appendChild(item);
                });
            }
            resultadosItems.style.display = 'block';
        }

        buscadorItems.addEventListener('input', () => renderResultadosItems(buscadorItems.value));
        buscadorItems.addEventListener('focus', () => renderResultadosItems(buscadorItems.value));

        document.addEventListener('click', function (e) {
            if (!resultadosItems.contains(e.target) && e.target !== buscadorItems) {
                resultadosItems.style.display = 'none';
            }
        });

        function calcularPrecioFinal(item) {
            return item.esRegalo ? 0 : (item.precioOriginal + item.sobreprecio);
        }

        function calcularSubtotalLinea(item) {
            return calcularPrecioFinal(item) * item.cantidad;
        }

        function renderItems() {
            itemsList.innerHTML = '';

            if (items.length === 0) {
                itemsEmpty.style.display = 'block';
            } else {
                itemsEmpty.style.display = 'none';

                const wrap = document.createElement('div');
                wrap.className = 'responsive-table-wrap';

                const table = document.createElement('table');
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Equipo</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Sobreprecio</th>
                            <th>Regalo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                `;
                const tbody = table.querySelector('tbody');

                items.forEach(item => {
                    const subtotalLinea = calcularSubtotalLinea(item);
                    const tr = document.createElement('tr');

                    const imgHtml = item.imagen
                        ? `<img src="${item.imagen}" alt="${item.tipo_equipo || ''}" style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">`
                        : `<div style="width:44px; height:44px; background:var(--surface-2); border:1px solid var(--border); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:18px;">—</div>`;

                    const equipoHtml = item.paqueteOrigenId
                        ? `${item.tipo_equipo || '-'}<br><span class="muted" style="font-size:10.5px;">Paquete: ${item.paqueteOrigenNombre}</span>`
                        : (item.tipo_equipo || '-');

                    tr.innerHTML = `
                        <td>${imgHtml}</td>
                        <td>${equipoHtml}</td>
                        <td>${item.modelo || '-'}</td>
                        <td>${item.marca || '-'}</td>
                        <td><input type="number" class="qty-input qinput" min="1" value="${item.cantidad}" style="width:70px; padding:8px;"></td>
                        <td class="subtotal-cell" style="font-weight:700; ${item.esRegalo ? 'color:var(--green);' : ''}">${item.esRegalo ? 'Obsequio' : '$' + subtotalLinea.toFixed(2)}</td>
                        <td>
                            <div style="position:relative;">
                                <span class="qprefix" style="left:8px;">$</span>
                                <input type="number" class="sobreprecio-input qinput" min="0" step="0.01" value="${item.sobreprecio}" style="width:100px; padding:8px 8px 8px 22px;" ${item.esRegalo ? 'disabled' : ''}>
                            </div>
                        </td>
                        <td>
                            <label class="ui-switch">
                                <input type="checkbox" class="regalo-toggle" ${item.esRegalo ? 'checked' : ''}>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td><button type="button" class="btn btn--ghost btn-quitar" style="padding:6px 12px; font-size:12px; white-space:nowrap;">Eliminar</button></td>
                    `;

                    function actualizarFilaLocal() {
                        const nuevoSubtotal = calcularSubtotalLinea(item);
                        const subtotalCell = tr.querySelector('.subtotal-cell');
                        subtotalCell.textContent = item.esRegalo ? 'Obsequio' : ('$' + nuevoSubtotal.toFixed(2));
                        subtotalCell.style.color = item.esRegalo ? 'var(--green)' : '';
                        const sobreprecioInput = tr.querySelector('.sobreprecio-input');
                        sobreprecioInput.disabled = item.esRegalo;
                        actualizarTotales();
                    }

                    tr.querySelector('.qty-input').addEventListener('input', function () {
                        item.cantidad = Math.max(1, parseInt(this.value) || 1);
                        actualizarFilaLocal();
                    });
                    tr.querySelector('.sobreprecio-input').addEventListener('input', function () {
                        item.sobreprecio = Math.max(0, parseFloat(this.value) || 0);
                        actualizarFilaLocal();
                    });
                    tr.querySelector('.regalo-toggle').addEventListener('change', function () {
                        item.esRegalo = this.checked;
                        actualizarFilaLocal();
                    });
                    tr.querySelector('.btn-quitar').addEventListener('click', function () {
                        items = items.filter(i => i.seq !== item.seq);
                        renderItems();
                    });

                    tbody.appendChild(tr);
                });

                wrap.appendChild(table);
                itemsList.appendChild(wrap);
            }

            actualizarTotales();
        }

        function actualizarTotales() {
            const subtotal = items.reduce((sum, item) => sum + calcularSubtotalLinea(item), 0);
            const descuentos = parseFloat(document.getElementById('descuentos').value) || 0;
            const costoEnvio = parseFloat(document.getElementById('costo_envio').value) || 0;
            const aplicaIva = document.getElementById('aplica_iva').checked;

            const baseIva = Math.max(subtotal - descuentos, 0);
            const iva = aplicaIva ? Math.round(baseIva * 0.16 * 100) / 100 : 0;
            const total = subtotal - descuentos + iva + costoEnvio;

            document.getElementById('subtotal-display').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('descuentos-display').textContent = '$' + descuentos.toFixed(2);
            document.getElementById('iva-display').textContent = '$' + iva.toFixed(2);
            document.getElementById('total-display').textContent = '$' + total.toFixed(2);
        }

        document.getElementById('descuentos').addEventListener('input', actualizarTotales);
        document.getElementById('costo_envio').addEventListener('input', actualizarTotales);
        document.getElementById('aplica_iva').addEventListener('change', actualizarTotales);
        document.getElementById('anticipo').addEventListener('input', actualizarTotales);

        // ---- Plan de pagos: selección directa / resumen editable ----
        const planPagosEmpty = document.getElementById('plan-pagos-empty');
        const planPagosResumen = document.getElementById('plan-pagos-resumen');
        const selectorPlanPlantilla = document.getElementById('plan-pago-plantilla');
        const numeroPagosHidden = document.getElementById('numero_pagos');
        const diasEntrePagosHidden = document.getElementById('dias_entre_pagos');
        let planConfigurado = false;

        // Al seleccionar un plan de pago guardado, autocompleta número de pagos, frecuencia y método
        // y muestra el resumen editable de inmediato (sin botón "Configurar").
        selectorPlanPlantilla.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!this.value) {
                numeroPagosHidden.value = '';
                diasEntrePagosHidden.value = '';
                planConfigurado = false;
                mostrarVacioPlan();
                return;
            }
            numeroPagosHidden.value = opt.dataset.numeroPagos;
            diasEntrePagosHidden.value = opt.dataset.diasEntrePagos;
            document.getElementById('metodo_pago').value = opt.dataset.metodoPago;
            planConfigurado = true;
            mostrarResumenPlan();
        });

        // Cambiar el método de pago o la fecha del primer pago también refresca el resumen ya visible.
        document.getElementById('metodo_pago').addEventListener('change', function () {
            if (planConfigurado) generarResumenPagos(false);
        });
        document.getElementById('fecha_inicio').addEventListener('change', function () {
            if (planConfigurado) actualizarFechasPagos();
        });

        function mostrarResumenPlan() {
            generarResumenPagos(true);
            planPagosEmpty.style.display = 'none';
            planPagosResumen.style.display = 'block';
        }

        function mostrarVacioPlan() {
            planPagosResumen.style.display = 'none';
            planPagosEmpty.style.display = 'block';
        }

        function calcularTotalCotizacion() {
            const subtotal = items.reduce((sum, item) => sum + calcularSubtotalLinea(item), 0);
            const descuentos = parseFloat(document.getElementById('descuentos').value) || 0;
            const costoEnvio = parseFloat(document.getElementById('costo_envio').value) || 0;
            const aplicaIva = document.getElementById('aplica_iva').checked;
            const baseIva = Math.max(subtotal - descuentos, 0);
            const iva = aplicaIva ? Math.round(baseIva * 0.16 * 100) / 100 : 0;
            const anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
            return Math.max(subtotal - descuentos + iva + costoEnvio - anticipo, 0);
        }

        function actualizarRestantePagos() {
            const total = calcularTotalCotizacion();
            const inputsMonto = document.querySelectorAll('.monto-pago-input');
            let suma = 0;
            inputsMonto.forEach(input => suma += parseFloat(input.value) || 0);
            const restante = total - suma;
            const restanteEl = document.getElementById('resumen-restante');
            restanteEl.textContent = '$' + restante.toFixed(2);
            restanteEl.style.color = Math.abs(restante) < 0.01 ? 'var(--green)' : '#dc2626';
        }

        // Refresca únicamente las fechas estimadas de cada cuota, sin tocar los montos ya editados.
        function actualizarFechasPagos() {
            const diasEntrePagos = Math.max(1, parseInt(document.getElementById('dias_entre_pagos').value) || 1);
            const fechaInicioVal = document.getElementById('fecha_inicio').value;
            const filas = document.querySelectorAll('#resumen-pagos-body tr');
            filas.forEach((tr, index) => {
                let fechaTexto = '—';
                if (fechaInicioVal) {
                    const fecha = new Date(fechaInicioVal + 'T00:00:00');
                    fecha.setDate(fecha.getDate() + diasEntrePagos * index);
                    fechaTexto = fecha.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
                }
                tr.children[1].textContent = fechaTexto;
            });
        }

        // Al editar el monto de un pago, se redistribuye automáticamente el resto del total
        // entre los demás pagos (en partes iguales) para que la suma siempre cubra el total.
        function manejarEdicionMonto(e) {
            const inputs = Array.from(document.querySelectorAll('.monto-pago-input'));
            const actual = e.target;
            const total = calcularTotalCotizacion();
            const valorActual = parseFloat(actual.value) || 0;
            const otros = inputs.filter(inp => inp !== actual);

            if (otros.length > 0) {
                const restante = total - valorActual;
                const base = Math.floor((restante / otros.length) * 100) / 100;
                otros.forEach((inp, idx) => {
                    if (idx === otros.length - 1) {
                        // El último de los "otros" absorbe el ajuste de redondeo para que la suma sea exacta.
                        const sumaBase = base * (otros.length - 1);
                        inp.value = (restante - sumaBase).toFixed(2);
                    } else {
                        inp.value = base.toFixed(2);
                    }
                });
            }

            actualizarRestantePagos();
        }

        function generarResumenPagos(reconstruir = true) {
            const total = calcularTotalCotizacion();
            const numeroPagos = Math.max(1, parseInt(document.getElementById('numero_pagos').value) || 1);
            const diasEntrePagos = Math.max(1, parseInt(document.getElementById('dias_entre_pagos').value) || 1);
            const fechaInicioVal = document.getElementById('fecha_inicio').value;
            const metodoPago = document.getElementById('metodo_pago').value;
            const montoPorPago = total / numeroPagos;

            document.getElementById('resumen-metodo').textContent = metodoPago;
            document.getElementById('resumen-numero-pagos').textContent = numeroPagos;
            document.getElementById('resumen-monto-pago').textContent = '$' + montoPorPago.toFixed(2);

            const tbody = document.getElementById('resumen-pagos-body');

            // Si sólo cambió el total pero el número de filas es el mismo, se conservan los montos
            // ya editados manualmente por el usuario y solo se refresca el indicador de restante.
            if (!reconstruir && tbody.querySelectorAll('tr').length === numeroPagos) {
                actualizarRestantePagos();
                return;
            }

            tbody.innerHTML = '';
            for (let i = 1; i <= numeroPagos; i++) {
                let fechaTexto = '—';
                if (fechaInicioVal) {
                    const fecha = new Date(fechaInicioVal + 'T00:00:00');
                    fecha.setDate(fecha.getDate() + diasEntrePagos * (i - 1));
                    fechaTexto = fecha.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
                }
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>Pago ${i} de ${numeroPagos}</td>
                    <td>${fechaTexto}</td>
                    <td>
                        <div style="position:relative; max-width:140px;">
                            <span class="qprefix" style="left:8px;">$</span>
                            <input type="number" class="monto-pago-input qinput" min="0" step="0.01" value="${montoPorPago.toFixed(2)}" style="padding:8px 8px 8px 22px; font-weight:700;">
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            }

            tbody.querySelectorAll('.monto-pago-input').forEach(input => {
                input.addEventListener('input', manejarEdicionMonto);
            });

            actualizarRestantePagos();
        }

        document.getElementById('btn-redistribuir-pagos').addEventListener('click', function () {
            generarResumenPagos(true);
        });

        const actualizarTotalesOriginal = actualizarTotales;
        actualizarTotales = function () {
            actualizarTotalesOriginal();
            if (planConfigurado) generarResumenPagos(false);
        };

        // Inyecta los hidden inputs de items al enviar el formulario
        document.getElementById('form-cotizacion').addEventListener('submit', function (e) {
            if (items.length === 0) {
                e.preventDefault();
                alert('Agrega al menos un producto o paquete a la cotización.');
                return;
            }

            if (!planConfigurado) {
                e.preventDefault();
                alert('Selecciona un plan de pago antes de guardar la cotización.');
                selectorPlanPlantilla.focus();
                return;
            }

            const form = this;
            items.forEach((item, index) => {
                const fields = {
                    tipo: 'producto',
                    id: item.id,
                    cantidad: item.cantidad,
                    sobreprecio: item.sobreprecio,
                    es_regalo: item.esRegalo ? 1 : 0,
                    paquete_origen_id: item.paqueteOrigenId || '',
                };
                Object.entries(fields).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${index}][${key}]`;
                    input.value = value;
                    form.appendChild(input);
                });
            });

            document.querySelectorAll('.monto-pago-input').forEach((input, index) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `montos[${index}]`;
                hidden.value = parseFloat(input.value) || 0;
                form.appendChild(hidden);
            });
        });

        renderItems();
    </script>
@endsection
